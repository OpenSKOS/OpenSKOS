#!/bin/sh

cp  /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/application.ini /home/travis/build/OpenSKOS/OpenSKOS/application/configs/application.ini.dist 

# install solr:

mkdir -p /tmp/solr
cd /tmp/solr
wget "https://archive.apache.org/dist/lucene/solr/6.3.0/solr-6.3.0.zip" 
unzip solr-6.3.0.zip 
mkdir /opt/solr 
cp -r /tmp/solr/solr-6.3.0/* /opt/solr 
mkdir -p /opt/solr/server/solr/openskos/conf 
touch /opt/solr/server/solr/openskos/core.properties
cp /home/travis/build/OpenSKOS/OpenSKOS/data/solr/solrconfig.xml /opt/solr/server/solr/openskos/conf/solrconfig.xml
cp /home/travis/build/OpenSKOS/OpenSKOS/data/solr/schema.xml /opt/solr/server/solr/openskos/conf/schema.xml
cp /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/start-solr.sh /home/travis/build/start-solr.sh
chmod 755 /home/travis/build/start-solr.sh

# install fuseki:
tar -zxvf /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/apache-jena-fuseki-2.3.0.tar.gz /opt
mv /opt/apache-jena-fuseki-2.3.0 /opt/apache-jena-fuseki
chmod -R ugo+rw /opt/apache-jena-fuseki 
chmod +x /opt/apache-jena-fuseki/fuseki-server /opt/apache-jena-fuseki/bin/* 
mkdir -p /opt/apache-jena-fuseki/run 
cp -r /home/travis/build/OpenSKOS/OpenSKOS/data/fuseki/configuration /opt/apache-jena-fuseki/run/configuration
mkdir /opt/apache-jena-fuseki/logs
cp /home/travis/build/OpenSKOS/OpenSKOS/start-fuseki.sh /home/travis/build/start-fuseki.sh
chmod 755 /home/travis/build/start-fuseki.sh

# mysql:

cp /home/travis/build/OpenSKOS/OpenSKOS/openskos-create.sql /home/travis/build/openskos-create.sql

# initialisation
cp /home/travis/build/OpenSKOS/OpenSKOS/openskos-init.sh /home/travis/build/openskos-init.sh
chmod 755 /home/travis/build/openskos-init.sh