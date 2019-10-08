#!/bin/sh

DIR=$(dirname ${0})
$DIR/ext-docker-install.sh $1
$DIR/ext-pecl-install.sh $1
$DIR/ext-custom-install.sh $1
