#!/bin/sh

#install apache2

cp  ${TRAVIS_BUILD_DIR}/integrationtestsettings/application.ini ${TRAVIS_BUILD_DIR}/application/configs/application.ini

# install solr:

mkdir -p /tmp/solr
cd /tmp/solr
# wget "https://archive.apache.org/dist/lucene/solr/6.3.0/solr-6.3.0.zip" 
wget "http://apache.mirror.triple-it.nl/lucene/solr/5.5.4/solr-5.5.4.zip"
unzip solr-5.5.4.zip 
mkdir /opt/solr 
cp -r /tmp/solr/solr-5.5.4/* /opt/solr 
mkdir -p /opt/solr/server/solr/openskos/conf 
touch /opt/solr/server/solr/openskos/core.properties
cp ${TRAVIS_BUILD_DIR}/data/solr/solrconfig.xml /opt/solr/server/solr/openskos/conf/solrconfig.xml
cp ${TRAVIS_BUILD_DIR}/data/solr/schema.xml /opt/solr/server/solr/openskos/conf/schema.xml
chmod 755 ${TRAVIS_BUILD_DIR}/integrationtestsettings/start-solr.sh

# install fuseki:
tar -zxvf ${TRAVIS_BUILD_DIR}/integrationtestsettings/apache-jena-fuseki-2.3.0.tar.gz -C /opt
mv /opt/apache-jena-fuseki-2.3.0 /opt/apache-jena-fuseki
chmod -R ugo+rw /opt/apache-jena-fuseki 
chmod +x /opt/apache-jena-fuseki/fuseki-server /opt/apache-jena-fuseki/bin/* 
mkdir -p /opt/apache-jena-fuseki/run 
cp -r ${TRAVIS_BUILD_DIR}/data/fuseki/configuration /opt/apache-jena-fuseki/run/configuration
mkdir /opt/apache-jena-fuseki/logs
chmod 755 ${TRAVIS_BUILD_DIR}/integrationtestsettings/start-fuseki.sh

#mysql
chmod 755  ${TRAVIS_BUILD_DIR}/integrationtestsettings/openskos-create.sql

# initialisation
chmod 755  ${TRAVIS_BUILD_DIR}/integrationtestsettings/openskos-init.sh