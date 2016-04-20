<?php
// utiliser seulement en ligne de commande
Galltei::cli();
/**
 * Un truc pour parser du texte Gallica et sortir du XML/TEI
 */
class Galltei {
  /** ressource sur le fichier en cours de lecture */
  private $_reader;
  /** ressource sur le fichier en cours d’écriture */
  private $_writer;
  /** Correspondance entre les champs Gallica et du Dublin Core */
  static $dc = array(
    "Auteur" => "creator",
    "Date d'édition" => "date",
    "Titre" => "title",
    "Identifiant" => "identifier",
    "Éditeur" => "publisher",
    "Nombre de pages" => "extent",
    "Description" => "description",
  );

  /**
   * un objet Galltei est un fichier texte Gallica avec la méthode toTei()
   */
  function __construct($txtfile, $teifile) {
    $this->_reader = fopen($txtfile, "r");
    // on sort une ligne pour la biblio
    $meta = $this->meta();
    echo pathinfo($teifile, PATHINFO_FILENAME);
    // pas le bon format de fichiers, on fait quoi ?
    if (!$meta || !count($meta)) return;
    foreach (self::$dc as  $dc) {
      echo "\t";
      if (!isset($meta[$dc])) continue;
      if ($dc == "date" ) {
        $meta[$dc] = substr($meta[$dc], 0, 4);
      }
      if ($dc == "identifier" && strpos($meta[$dc], 'ark:') === 0 ) {
        $meta[$dc] = "http://gallica.bnf.fr/".$meta[$dc];
      }
      echo strtr($meta[$dc], array('"' => '”'));
    }
    echo "\n";
    $this->_writer = fopen($teifile, "w");
    // on écrit du TEI simple pour PhiloLogic
    fwrite($this->_writer, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($this->_writer, '<TEI xmlns="http://www.tei-c.org/ns/1.0" xml:lang="fr">'."\n");
    fwrite($this->_writer, '  <meta>'."\n");
    foreach ($meta as $dc=>$value) {
      fwrite($this->_writer, "    <$dc>$value</$dc>\n");
    }
    fwrite($this->_writer, '  </meta>'."\n");
    fwrite($this->_writer, '  <text>'."\n");
    fwrite($this->_writer, '    <body>'."\n");
    $this->text();
    fwrite($this->_writer, '    </body>'."\n");
    fwrite($this->_writer, '  </text>'."\n");
    fwrite($this->_writer, '</TEI>'."\n");
  }

  /**
   * Ramasser le texte
   */
  function text() {
    $p = "";
    while(($l = fgets($this->_reader)) !== false) {
      $l = trim($l);
      // ligne vide, on envoie le paragraphe
      if (!$l) {
        $p = trim(preg_replace(
          array('@(\pL)-\n(\pL[^ ]*)@u', '@&@',     '@<@',    '@>@'),
          array('$1$2'."\n",             '&amp;', '&lt;', '&gt;'),
          $p
        ));
        if (!$p) continue;
        fwrite($this->_writer, "<p>".$p."</p>\n");
        $p = '';
        continue;
      }
      $p .= "\n".$l;
    }
  }
  /**
   * À appeler d’abord pour parser l’entête de métadonnées
   */
  private function meta() {
    // tableau clé=>valeur de métas ramassées
    $meta = array();
    // la ligne de fichier
    $l = '';
    // un bloc de lignes (titres sur 2 lignes)
    $p = '';
    $i = 0;
    while(true) {
      $i++;
      if ($i > 200 ) return $meta;
      $l = trim(fgets($this->_reader));
      // ligne vide, c’est ici qu‘on traite la méta en cours
      if (!$l) {
        // copier le paragraphe pour pouvoir l’annuler tout de suite
        $p2 = trim(strtr($p, array('*'=>'')));
        $p = '';
        if (!$p2) continue;
        if (!preg_match('@([^:]+):(.*)@u', $p2, $matches)) continue;
        if (!isset($matches[1]) || !isset($matches[2])) continue;
        $label = trim($matches[1]);
        if (!isset(self::$dc[$label])) continue; // champ non retenu
        $dc = self::$dc[$label]; // terme Dublin Core
        if (isset($meta[$dc])) continue; // multivalué, on ne prend que le premier ?

        $meta[$dc] = trim($matches[2]);
      }
      if (strpos($l, "-------------------") !== false) return $meta;
      $p .= " ".$l; // ajouter la ligne au paragraphe en cours
    }
  }
  /**
   * La ligne de commande
   */
  static function cli() {
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    if (!count($_SERVER['argv'])) exit('
    php -f Galltei.php dest/ "txt/*.txt"
    dest/    : ? dossier de destination, optionnel
    glob     : fichiers à transformer, avec ou sans guillemets

');
    $lastc = substr($_SERVER['argv'][0], -1);
    if ('/' == $lastc || '\\' == $lastc) {
      $destdir = array_shift($_SERVER['argv']);
      $destdir = rtrim($destdir, '/\\').'/';
      if (!file_exists($destdir)) {
        mkdir($destdir, 0775, true);
        @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
      }
    }
    $ext = ".xml";
    $count = 0;
    echo "filename";
    foreach (self::$dc as  $dc) {
      echo "\t";
      echo $dc;
    }
    echo "\n";
    foreach ($_SERVER['argv'] as $glob) {
      foreach(glob($glob) as $srcfile) {
        $count++;
        if (isset($destdir) ) $destfile = $destdir.pathinfo($srcfile,  PATHINFO_FILENAME).$ext;
        else $destfile = dirname($srcfile).'/'.pathinfo($srcfile,  PATHINFO_FILENAME).$ext;
        // fwrite(STDERR, $count." ".$srcfile." > ".$destfile."\n");

        $gall = new Galltei($srcfile, $destfile);
      }
    }
  }

}


 ?>
