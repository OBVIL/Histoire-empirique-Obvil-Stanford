<?php
set_time_limit(-1);
if (php_sapi_name() == "cli") Doc::cli();

/**
 * Sample pilot for Teinte transformations of XML/TEI
 */
class Doc {
  /** TEI/XML DOM Document to process */
  private $_dom;
  /** Xpath processor */
  private $_xpath;
  /** filepath */
  private $_file;
  /** filename without extension */
  private $_filename;
  /** file freshness */
  private $_filemtime;
  /** file size */
  private $_filesize;
  /** XSLTProcessor */
  private $_trans;
  /** XSL DOM document */
  private $_xsldom;
  /** Keep memory of last xsl, to optimize transformations */
  private $_xslfile;

  /**
   * Constructor, load file and prepare work
   */
  public function __construct($tei) {
    if (is_a( $tei, 'DOMDocument' ) ) {
      $this->_dom = $tei;
    }
    else if( is_string( $tei ) ) { // maybe file or url
      $this->_file = $tei;
      $this->_filemtime = filemtime( $tei );
      $this->_filesize = filesize( $tei ); // ?? URL ?
      $this->_filename = pathinfo( $tei, PATHINFO_FILENAME );
      $this->_dom( $tei );
    }
    else {
      throw new Exception('What is it? '.print_r($tei, true));
    }
    $this->_xsldom = new DOMDocument();
    $this->xpath();
  }

 /**
  * Set and return an XPath processor
  */
  public function xpath()
  {
    if ($this->_xpath) return $this->_xpath;
    $this->_xpath = new DOMXpath($this->_dom);
    $this->_xpath->registerNamespace( 'tei', "http://www.tei-c.org/ns/1.0" );
    return $this->_xpath;
  }
  /**
   * Get the filename (with no extention)
   */
   public function filename( $filename=null )
   {
     if ( $filename ) $this->_filename = $filename;
     return $this->_filename;
   }
  /**
   * Read a readonly property
   */
  public function filemtime( $filemtime=null )
  {
    if ( $filemtime ) $this->_filemtime = $filemtime;
    return $this->_filemtime;
  }
  /**
   * For a readonly property
   */
  public function filesize( $filesize=null )
  {
    if ( $filesize ) $this->_filesize = $filesize;
    return $this->_filesize;
  }
  /**
   * Book metadata
   */
  public function meta() {
    $meta = array();
    $meta['code'] = pathinfo($this->_file, PATHINFO_FILENAME);
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author");
    $meta['creator'] = array();
    $meta['byline'] = null;
    $first = true;
    foreach ($nl as $node) {
      $value = $node->getAttribute("key");
      if ( !$value ) $value = $node->textContent;
      if (($pos = strpos($value, '('))) $value = trim( preg_replace( "@\s+@u", " ", substr( $value, 0, $pos ) ) );
      $meta['creator'][] = $value;
      if ( $first ) $first = false;
      else $meta['byline'] .= " ; ";
      $meta['byline'] .= $value;
    }
    // title
    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:title");
    if ($nl->length) $meta['title'] = trim( preg_replace( "@\s+@u", " ", $nl->item(0)->textContent ));
    else $meta['title'] = null;
    // publisher
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:sourceDesc//tei:publisher");
    if ($nl->length) $meta['publisher'] = trim( preg_replace( "@\s+@u", " ", $nl->item(0)->textContent ));
    else $meta['publisher'] = null;
    // identifier
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:sourceDesc//tei:idno");
    if ($nl->length) $meta['identifier'] = trim( preg_replace( "@\s+@u", " ", $nl->item(0)->textContent ));
    else $meta['identifier'] = null;
    // dates
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:profileDesc/tei:creation/tei:date");
    // loop on dates
    $meta['created'] = null;
    $meta['issued'] = null;
    $meta['date'] = null;
    foreach ($nl as $date) {
      $value = $date->getAttribute ('when');
      if (!$value) $value = $date->nodeValue;
      $value = substr(trim($value), 0, 4);
      if (!is_numeric($value)) {
        $value = null;
        continue;
      }
      if (!$meta['date']) $meta['date'] = $value;
      if ($date->getAttribute ('type') == "created" && !$meta['created']) $meta['created'] = $value;
      else if ($date->getAttribute ('type') == "issued" && !$meta['issued']) $meta['issued'] = $value;
    }
    if (!$meta['issued'] && isset($value) && is_numeric($value)) $meta['issued'] = $value;
    $meta['source'] = null;
    $meta['filename'] = $this->filename();
    $meta['filemtime'] = $this->filemtime();
    $meta['filesize'] = $this->filesize();


    return $meta;
  }

  /**
   * Set and build a dom privately
   */
  private function _dom( $xmlfile ) {
    $this->_dom = new DOMDocument();
    $this->_dom->preserveWhiteSpace = false;
    $this->_dom->formatOutput=true;
    $this->_dom->substituteEntities=true;
    $this->_dom->load($xmlfile, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOWARNING);
    return $this->_dom;
  }
  /**
   * Get the dom
   */
  public function dom() {
    return $this->_dom;
  }

  /**
   * Command line transform
   */
  public static function cli() {
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    if (!count($_SERVER['argv'])) exit('
    usage     : php tei2tsv.php destfile.tsv "*.xml"
');

    $destfile = array_shift($_SERVER['argv']);
    if ( !file_exists( dirname( $destfile ) ) ) mkdir( dirname( $destfile ), null, true );
    if ( file_exists( dirname( $destfile ) ) && pathinfo( $destfile, PATHINFO_EXTENSION ) == "xml" ) {
      exit ("Êtes-vous sûr de vouloir écraser ".$destfile." ?\n");
    }
    $out = fopen( $destfile, "w" );
    $sep = "	";
    fwrite( $out, "select".$sep."source".$sep."code".$sep."creator".$sep."date".$sep."title".$sep."identifier".$sep."publisher"."\n");
    $count = 0;
    foreach ($_SERVER['argv'] as $glob) {
      foreach( glob($glob) as $srcfile ) {
        $doc= new Doc($srcfile);
        $count++;
        fwrite(STDERR, "$count. $srcfile\n");
        $meta = $doc->meta();
        fwrite ( $out, "1".$sep.$srcfile.$sep.$meta['filename'].$sep.$meta['creator'][0].$sep.$meta['date'].$sep.$meta['title'].$sep.$meta['identifier'].$sep.$meta['publisher']."\n" );
      }
    }
  }
}
?>
