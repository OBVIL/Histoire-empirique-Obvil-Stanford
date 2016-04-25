if [[ $# -eq 0 ]] ; then
    echo 'usage: ./download.sh urilist.tsv dossier'
    exit 0
fi
INPUT=$1
DESTDIR=$2
mkdir -p $DESTDIR
while  read -r URL CODE INUTILE_MAIS_NECESSAIRE
do
  SRCNAME="${URL##*/}" # Inutilisé, laissé pour mémoire
  DESTFILE=$DESTDIR/$CODE".xml"
  if [ ! -f $DESTFILE ]
  then
    echo $DESTFILE
    # -f fail on 404
    curl -f $URL -o $DESTFILE --location
  fi
done < "$INPUT"
