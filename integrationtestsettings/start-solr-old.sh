#!/bin/bash
cd /opt/solr/example
exec java -Dsolr.solr.home="./openskos" -Xms1024m -Xmx1024m -jar /opt/solr/example/start.jar &>> /opt/solr/logs/solr-`date '+%Y%m%d'`.log
