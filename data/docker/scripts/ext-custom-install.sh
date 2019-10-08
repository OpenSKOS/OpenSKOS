#!/bin/sh

DIR=$(dirname $0)

echo 'CUSTOM INSTALL'
ls $DIR/ext/ | while read installer; do
  $DIR/ext/${installer}
done
