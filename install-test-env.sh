#!/bin/sh

# install solr:
mkdir -p /tmp/solr
cd /tmp/solr
wget "https://archive.apache.org/dist/lucene/solr/6.3.0/solr-6.3.0.zip" 
unzip solr-6.3.0.zip 
mkdir /opt/solr 
cp -r /tmp/solr/solr-6.3.0/* /opt/solr 
mkdir -p /opt/solr/server/solr/openskos/conf 
touch /opt/solr/server/solr/openskos/core.properties
cp solrconfig.xml /opt/solr/server/solr/openskos/conf/solrconfig.xml
cp /data/solr/schema.xml /opt/solr/server/solr/openskos/conf/schema.xml
cp /data/solr/start-solr.sh /start-solr.sh
chmod 755 /start-solr.sh
cp supervisord-solr.conf /etc/supervisor/conf.d/supervisord-solr.conf