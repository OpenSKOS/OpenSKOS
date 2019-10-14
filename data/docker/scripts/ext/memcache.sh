#!/usr/bin/env bash

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

# Gentoo support
if [ -f /etc/portage/make.conf ]; then
  source /etc/portage/make.conf
fi

# Detect PHP version
target="$(php -v | head -1 | awk '{print $2}')"

# Decode minor & major versions
minor=$(echo "${target}" | tr '.' ' ' | awk '{print $2}')
major=$(echo "${target}" | tr '.' ' ' | awk '{print $1}')

# PECL memcache for php <7
if [ "${major}" -lt 7 ]; then
  printf "\n" | pecl install memcache || exit 1
  echo "extension=memcache.so" >> /usr/local/lib/php.ini
  exit 0
fi

# Download/update memcache repo
[ -d "/usr/src/php/ext/memcache" ] && {
  cd /usr/src/php/ext/memcache
  git fetch --all
} || {
  git clone https://github.com/websupport-sk/pecl-memcache /usr/src/php/ext/memcache
  cd /usr/src/php/ext/memcache
  git fetch --all
}

# Go to the ext-memcache source
cd /usr/src/php/ext/memcached
git checkout "NON_BLOCKING_IO_php${major}"
git pull

# Compile & install ext-memcache
printf "\n" | phpize || exit 1
printf "\n" | ./configure --enable-memcache || exit 1
make $MAKEOPTS || exit 1
make install || exit 1

echo "extension=memcache.so" >> /usr/local/lib/php.ini
