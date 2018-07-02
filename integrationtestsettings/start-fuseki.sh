#!/bin/bash
cd /opt/apache-jena-fuseki
exec ./fuseki-server --update &>> /opt/apache-jena-fuseki/logs/fuseki-`date '+%Y%m%d'`.log
