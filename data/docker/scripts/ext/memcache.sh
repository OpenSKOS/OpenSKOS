#!/bin/sh

DIR=$(dirname $0)
LOGFILE=${2:-/dev/null}

# Reason: 3.0.8 doesn't work with php7
pecl install memcache-2.2.7 || exit 1
docker-php-ext-enable memcache || exit 1
