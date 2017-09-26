#!/bin/bash
cd /opt/solr
find . -name write.lock -exec rm {} \;
mkdir -p /opt/solr/logs
exec /opt/solr/bin/solr start -f &>> /opt/solr/logs/solr-`date '+%Y%m%d'`.log
