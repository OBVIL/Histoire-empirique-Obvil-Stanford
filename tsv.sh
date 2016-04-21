BASEDIR=$(dirname "$0")
XSL=$BASEDIR/tei2tsv.xsl
for f in $*
do
  xbase=${f##*/}
  filename=${xbase%.*}
  xsltproc --stringparam filename "$filename" "$XSL" $f
done
