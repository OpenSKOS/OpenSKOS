#!/bin/sh

ncpu() {
  if command -v nproc &>/dev/null; then
    nproc
  else
    echo 1
  fi
}

export MAKEOPTS="-j$(($(ncpu)+1))"
export CFLAGS=
export CONFOPTS=

# Gentoo stuff
if [ -f /etc/portage/make.conf ]; then
  source /etc/portage/make.conf
fi

# Detect PHP version
target="$(php -v | head -1 | awk '{print $2}')"

# Decode minor & major versions
minor=$(echo "${target}" | tr '.' ' ' | awk '{print $2}')
major=$(echo "${target}" | tr '.' ' ' | awk '{print $1}')

# Download/update memcached repo
[ -d "/usr/src/php/ext/memcached" ] && {
  cd /usr/src/php/ext/memcached
  git fetch --all
} || {
  git clone https://github.com/php-memcached-dev/php-memcached /usr/src/php/ext/memcached
  cd /usr/src/php/ext/memcached
  git fetch --all
}

# Go to the ext-memcached source
cd /usr/src/php/ext/memcached
git checkout "php${major}"
git pull

# Compile & install ext-memcached
phpize
./configure --disable-memcached-sasl
make $MAKEOPTS
make install

echo "extension=memcached.so" >> /usr/local/lib/php.ini
