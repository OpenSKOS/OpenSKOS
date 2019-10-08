#!/bin/sh

DIR=$(dirname $0)
LOGFILE=${2:-/dev/null}

$DIR/ext-docker.php $1 | while read ext; do
  if [ -z "${ext}" ]; then continue; fi
  echo "      - ${ext}"
  docker-php-ext-install -j$(nproc) $ext &>${LOGFILE} || { cat ${LOGFILE} ; exit 1 ; }
done
