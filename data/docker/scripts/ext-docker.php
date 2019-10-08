#!/usr/bin/env php
<?php

function normalize_newlines( $str ) {
  $str = str_replace( "\r\n", "\n", $str ); // Windows
  $str = str_replace( "\n\r", "\n", $str ); // Odd systems
  $str = str_replace( "\r", "\n", $str );   // Legacy OSX
  return $str;
}

// Fetch list of docker-installable extensions
// Origin: https://gist.github.com/chronon/95911d21928cff786e306c23e7d1d3f3
$dir         = dirname(realpath($argv[0]));
$docker_exts = explode("\n",normalize_newlines(file_get_contents($dir.'/ext-list.txt')));

// Fetch dependency list
$json         = file_get_contents($argv[1]);
$composer     = json_decode($json, true);
$dependencies = array_keys($composer['require']);

// Remove non-php-extension dependencies
$dependencies = array_filter( $dependencies, function( $dependency ) {
  return substr( $dependency, 0, 4 ) === 'ext-';
});

// Remove 'ext-' prefix
$dependencies = array_map(function( $dependency ) {
  return substr( $dependency, 4 );
}, $dependencies);

// Remove ones with a custom install script
$dependencies = array_filter( $dependencies, function( $dependency ) use ($dir) {
  return !file_exists($dir . '/ext/' . $dependency . '.sh');
});

// Remove the ones not known to docker
$dependencies = array_filter( $dependencies, function( $dependency ) use ($docker_exts) {
  return in_array($dependency, $docker_exts);
});

// Return a list of extensions to install
echo implode(PHP_EOL, $dependencies) . PHP_EOL;
