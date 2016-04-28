[![Build Status](https://travis-ci.org/picturae/OpenSKOS.svg)](https://travis-ci.org/picturae/OpenSKOS)

1. Install the OpenSKOS code
===============================================================================
Copy the code to a location of your choice. Make sure all files are readable by
your webserver. Make sure the "data/uploads" directory is writable for the webserver.
For security reasons you can place this "data" directory outside your
webserver's document root.

1.1 Composer
-------------------------------------------------------------------------------
Run composer install to install some dependencies like zend framework 1.12

1.2 Configuration
-------------------------------------------------------------------------------
To configure OpenSKOS you have to rename:
  APPROOT/application/configs/application.ini.dist
to
  APPROOT/application/configs/application.ini

Now you van edit the APPROOT/application/configs/application.ini
You can have separate config settings for specific deployments. The 
configuration section marked by the Environment Variable "APPLICATION_ENV" (see
2.1 Setting Up Your VHOST). Most settings are self explanatory.

If you experience any problems you may want to modify settings in the config,
to show you more verbose error messages:

resources.frontController.params.displayExceptions=1
phpSettings.display_errors = 1


1.2.1 OAI-PMH setup
-------------------------------------------------------------------------------
OpenSKOS includes a OAI harvester. To configure OAI Service providers, use the
"instances" part of the configuration. Two types of instances are supported:
- openskos (instances of OpenSKOS)
- external (any OAI-PMH provider that provides SKOS/XML-RDF data)

The setup for "openskos" types is easy:
instances.openskos.type=openskos
instances.openskos.url=http://HOSTNAME
instances.openskos.label=YOUR LABEL

For "external" types use this syntax: 
instances.example1.type=external
instances.example1.url=http://HOSTNAME
instances.example1.label=EXAMPLE LABEL
#optional, default=oai_rdf
instances.example1.metadataPrefix=METADATAPREFIX
#optional:
instances.example1.set=SETSPEC

You can define multiple instances by using a different key (in the above example
the key "example1" is used").

2. Webserver with PHP support
===============================================================================
You can install your favorite webserver with PHP support.
All development and testing was done using Apache/2.2.15 with PHP 5.3.8
Make sure your PHP installation supports at least one supported Database
adapters (see http://framework.zend.com/manual/en/zend.db.adapter.html)

2.1 Setting Up Your VHOST
-------------------------------------------------------------------------------

The following is a sample VHOST you might want to consider for your project.

<VirtualHost *:80>
   DocumentRoot "/PATH/TO/CODE/public"
   ServerName YOUR.SERVER.NAME

   # This should be omitted in the production environment
   SetEnv APPLICATION_ENV development
    
   <Directory "/PATH/TO/CODE/public">
       Options Indexes MultiViews FollowSymLinks
       AllowOverride All
       Order allow,deny
       Allow from all
   </Directory>
    
</VirtualHost>


3. Database setup
===============================================================================
Install your choice of Zend Framework supported Database engine (see
http://framework.zend.com/manual/en/zend.db.adapter.html). The credentials to
access your database can be configured in the application's configuration. 

Once you have created an empty database, you have to run the SQL script 
APPROOT/data/openskos-create.sql to create the db-tables.

You also have to run the php-script to create a tenant:
php APPROOT/tools/tenant.php --code INST_CODE --name INST_NAME --email EMAIL --password PWD create

With this account created you can login into the dashboard,
where you can manage all the other entities of the application.


4. Apache Jena Fuseki setup
===============================================================================
Openskos uses Fuseki 2 for storage. At the time of writing this doc latest stable version is 2.3.0

Installing Fuseki 2 for development purposes:

1. Download Fuseki 2 from here [download](https://jena.apache.org/download/)
2. Install stand alone fuseki server. The instructions are the same as on [getting started page](https://jena.apache.org/documentation/serving_data/#getting-started-with-fuseki)
  1. Unpack the downloaded file with `unzip` or `tar zxfv` to a `<fuseki folder>` of your choice
  2. `chmod +x fuseki-server`
3. Symlink or copy the content of:
`<openskos folder>/data/fuseki/configuration/` to `<fuseki folder>/run/configuration/`
4. Go to `<fuseki folder>` and run the server with
`./fuseki-server --update`
  1. The docs say that Fuseki requires Java 7, but if you have the error `Unsupported major.minor version 52.0` try updating your Java, or go for Java 8 directly.
5. Now you will have the fuseki server up and running on [http://localhost:3030/](http://localhost:3030/) with "openskos" dataset defined. This is also the default config in openskos' `application.ini.dist` - item `sparql`

5. Data Ingest
===============================================================================
Once you have the application running you can start adding data,
managed in "collections".

You can create a collection in the dashboard.

There are three ways to populate a collection:

5.1 REST-interface
-------------------------------------------------------------------------------
Send data via the REST-API, e.g. like this:

> curl -H "Accept: text/xml" -X POST -T sample-concept.rdf http://localhost/OpenSKOS/public/api/concept

You find the required format of the input data described in the API-docs under:
http://openskos.org/api#concept-create

You may send only one concept per call.
Also, you have to identify the tenant and provide the API key, 
which you assign to the user in the dashboard.


5.2 Uploader
-------------------------------------------------------------------------------
Upload a dataset (a SKOS/RDF file) via a form in the dashboard:Manage collections.
Here you can provide many concepts within one file (XPath: /rdf:RDF/rdf:Description)

Once you successfully upload the file, it is scheduled for import,
as seen in dashboard:Manage jobs.

The import job can be started with ./tools/jobs.php, 
a CLI script intended to be run with a Cron like task runner. 


5.3 OAI ???
-------------------------------------------------------------------------------
Third possiblity is to replicate an existing dataset via OAI-PMH, 
either from other OpenSKOS-instances or from an external source providing SKOS-data.

???
For this, you set the [OAI baseURL]-field of a collection to the OAI-PMH endpoint of an external provider
and let the source be harvested.

The harvest job can be started with ./tools/harvest.php, 
another CLI script meant to be run as a cron-task.
???

5.4 Migrate from OpenSKOS v1
-------------------------------------------------------------------------------
It is possible to migrate the data from the SOLR core used by a OpenSKOS v1 instance directly into a v2 instance

`tools/migrate.php --endpoint http://<solr server>:8180/ciss/<core name>/select`

Once this is complete the data from the v1 instance will be available in the triple store used by OpenSKOS v2.

5.5
-------------------------------------------------------------------------------

Generate API Documentation

```
npm install
npm run doc
```
Visit: http://example.com/apidoc/

5.6. Using API
--------------------------------------------------------------------------------

Getting the list of institutions (tenants): 
<base uri>/public/api/institutions?format=json

Getting the list of sets (former tenant collections, still called "collections" 
in API): <base uri>/public/api/collections?format=json

Getting concepts sorted (added by Meertens): 
<server>/public/api/find-concepts?q=prefLabel@en:*&sorts=prefLabel@en
<server>/public/api/find-concepts?q=prefLabel@en:*&sorts=prefLabel@en%20desc
<server>/public/api/find-concepts?q=prefLabel@en:*&sorts=prefLabel@en%20asc
(However it seems that sorting e.g. on definitions does not work on solr level, 
even with sort_c_definition)

Getting the list of all statuses (added by Meertens)
<server>/public/api/statuses?format=json
Throws an error on other formats (html and rdf/xml). Implemented via a call to jena.

Getting the list of all schemata (added by Meertens)
<server>/public/api/statuses?format=json
Throws an error on other formats (html and rdf/xml). Implemented via a call to jena.

5.7. Check URI's (examples)
----------------------------------------------------------------------------------

Getting relations for certain types with given schemata for sources and targets
http://192.168.99.100/public/api/find-relations?q=broader,narrower&sourceSchemata=http://data.beeldengeluid.nl/gtaa/Onderwerpen2,http://data.beeldengeluid.nl/gtaa/Onderwerpen1&targetSchemata=http://data.beeldengeluid.nl/gtaa/Onderwerpen2

Getting concepts within given skos:collection(s)

http://192.168.99.100/public/api/find-concepts?q=*:*&skosCollections=http://data.beeldengeluid.nl/gtaa/skoscollectionA%20http://data.beeldengeluid.nl/gtaa/skoscollectionB

Getting deleted concepts:

http://192.168.99.100/public/api/concept?q=*:*&status=deleted

5.8. Relations

List complete URI's of all relations
http://192.168.99.100/public/api/relation?format=json

Fetch relation description (work only for user-defined relations)
http://192.168.99.100/public/api/relation?id=http://menzo.org/xmlns%23slower

Fetch all pairs for a given relations (filtering by source-target schemes is possible)
http://192.168.99.100/public/api/relation?id=http://menzo.org/xmlns%23slower&members=true
http://192.168.99.100/public/api/relation?id=http://www.w3.org/2004/02/skos/core%23broader&members=true

Fetch all pairs for a given relations with a given concept as a source
http://192.168.99.100/public/api/relation?id=http://www.w3.org/2004/02/skos/core%23broader&conceptUri=http://hdl.handle.net/11148/CCR_C-2731_5853a464-7c2d-53f9-d3cf-2f75a4dc4870
http://192.168.99.100/public/api/relation?id=http://menzo.org/xmlns%23faster&conceptUri=http://hdl.handle.net/11148/CCR_C-2731_5853a464-7c2d-53f9-d3cf-2f75a4dc4870