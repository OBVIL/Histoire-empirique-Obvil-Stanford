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
  if [ ! -f $DESTFILE ]
  then
    echo $DESTFILE
    curl $URL -o $DESTFILE --location
  fi
done < "$INPUT"
