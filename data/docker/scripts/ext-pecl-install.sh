#!/bin/sh

DIR=$(dirname $0)
LOGFILE=${2:-/dev/null}

$DIR/ext-pecl.php $1 | while read ext; do
  if [ -z "${ext}" ]; then continue; fi
  echo "      - ${ext}"
  pecl install $ext &>${LOGFILE} || { cat ${LOGFILE} ; exit 1 ; }
  docker-php-ext-enable $ext &>${LOGFILE} || { cat ${LOGFILE} ; exit 1 ; }
done
