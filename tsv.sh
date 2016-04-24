if [[ $# -eq 0 ]] ; then
    echo 'Génère une table tsv avec des métadonnées de fichier tei.xml'
    echo 'usage: ./tsv.sh dossier/*.xml >> biblio.tsv'
    exit 0
fi
BASEDIR=$(dirname "$0")
XSL=$BASEDIR/tei2tsv.xsl
for f in $*
do
  xbase=${f##*/}
  filename=${xbase%.*}
  xsltproc --stringparam filename "$filename" "$XSL" $f
done
