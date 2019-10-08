#!/bin/sh

DIR=$(dirname $0)

echo 'DOCKER INSTALL'
$DIR/ext-docker.php $1 | while read ext; do
  docker-php-ext-install -j$(nproc) $ext
done
