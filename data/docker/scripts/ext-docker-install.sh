#!/bin/sh

DIR=$(dirname $0)
LOGFILE=${2:-/dev/null}

$DIR/ext-composer.php $1 | grep -f "${DIR}/ext-list.txt" | while read ext; do
  if [ -z "${ext}" ]; then continue; fi
  echo "      - ${ext}"
  docker-php-ext-install -j$(nproc) $ext &>${LOGFILE} || { cat ${LOGFILE} ; exit 1 ; }
done
