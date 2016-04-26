<?php
$usage = "usage    : php -f ".basename(__FILE__)." liste.tsv destdir/\n\n";
$timeStart = microtime(true);
array_shift($_SERVER['argv']); // shift first arg, the script filepath
if (!count($_SERVER['argv'])) exit($usage);
$tsvfile = array_shift($_SERVER['argv']);
$destdir = array_shift($_SERVER['argv']);
$destdir = rtrim( $destdir, '/\\ ' ).'/';

$stream = fopen( $tsvfile, "r" );
// on prend les noms de colonnes
$keys = fgetcsv( $stream, 0, "\t" );
// boucler sur les lignes
while ( ( $values = fgetcsv( $stream, 0, "\t" ) ) !== FALSE) {
  // ligne vide, on passe
  if ( count( $values ) < 1) continue;
  // ligne à moitié pleine on égalise le nombre de cellules avec l’entête
  if ( count( $keys ) > count( $values ) )
    $values = array_merge( $values, array_fill( 0, count( $keys ) - count( $values ), null ) ) ;
  // et là on a de qui travailler
  $row = array_combine($keys, $values);
  // première colonne "select" est vide, on ne charge pas le fichier
  if ( !$row['select'] ) continue;
  // le fichier de destination
  $destfile = $destdir.$row['code'].'.xml';
  // il existe, on sort
  if ( file_exists($destfile) ) continue;
  // ici on télécharge et on copie
  echo $row['source']."\n > ";
  copy( $row['source'], $destfile );
  echo $destfile."\n";
}
?>
