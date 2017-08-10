#!/bin/sh

cp  /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/application.ini /home/travis/build/OpenSKOS/OpenSKOS/application/configs/application.ini 

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
chmod 755 /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/start-solr.sh 

# install fuseki:
tar -zxvf /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/apache-jena-fuseki-2.3.0.tar.gz -C /opt
mv /opt/apache-jena-fuseki-2.3.0 /opt/apache-jena-fuseki
chmod -R ugo+rw /opt/apache-jena-fuseki 
chmod +x /opt/apache-jena-fuseki/fuseki-server /opt/apache-jena-fuseki/bin/* 
mkdir -p /opt/apache-jena-fuseki/run 
cp -r /home/travis/build/OpenSKOS/OpenSKOS/data/fuseki/configuration /opt/apache-jena-fuseki/run/configuration
mkdir /opt/apache-jena-fuseki/logs
chmod 755 /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/start-fuseki.sh 

#mysql
chmod 755  /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/openskos-create.sql 

# initialisation
chmod 755  /home/travis/build/OpenSKOS/OpenSKOS/integrationtestsettings/openskos-init.sh 