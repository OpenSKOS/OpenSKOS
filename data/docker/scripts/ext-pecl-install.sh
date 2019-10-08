#!/bin/sh

DIR=$(dirname $0)

echo 'PECL INSTALL'
$DIR/ext-pecl.php $1 | while read ext; do
  pecl install $ext
  docker-php-ext-enable $ext
done
