#!/bin/sh

DIR=$(dirname $0)
LOGFILE=${2:-/dev/null}

ls $DIR/ext/ | while read installer; do
  if [ -z "${ext}" ]; then continue; fi
  echo "      - ${ext%.sh}"
  $DIR/ext/${installer} &>${LOGFILE} || { cat ${LOGFILE} ; exit 1 ; }
done
