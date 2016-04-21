if [[ $# -eq 0 ]] ; then
    echo 'usage: ./download.sh urilist.tsv dossier'
    exit 0
fi
INPUT=$1
DESTDIR=$2
mkdir -p $DESTDIR
while  read -r URL INUTILE_MAIS_NECESSAIRE
do
  DESTNAME="${URL##*/}"
  DESTFILE=$DESTDIR/$DESTNAME
  curl $URL -z $DESTFILE -o $DESTFILE --location
done < "$INPUT"
