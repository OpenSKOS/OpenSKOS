1. Install the OpenSKOS code
===============================================================================
Copy the code to a location of your choice. 

Make sure all files are readable by your webserver. Make sure the directories
"data/uploads", "cache", "public/data/icons/assigned" and 
"public/data/icons/uploads" are writable for the webserver.

For security reasons you can place the "data" directory outside your
webserver's document root.

1.1 Configuration
-------------------------------------------------------------------------------
To configure OpenSKOS you have to rename:
  APPROOT/application/configs/application.ini.dist
to
  APPROOT/application/configs/application.ini

Now you van edit the APPROOT/application/configs/application.ini
You can have separate config settings for specific deployments. The 
configuration section marked by the Environment Variable "APPLICATION_ENV" (see
3.1 Setting Up Your VHOST). Most settings are self explanatory.

If you experience any problems you may want to modify settings in the config,
to show you more verbose error messages:

resources.frontController.params.displayExceptions=1
phpSettings.display_errors = 1


1.1.1 OAI-PMH setup
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


2. Zend Framework
===============================================================================
Download a 1.11 branch from http://framework.zend.com/ and make sure it is in 
you php include path. You can do this by setting the "include_path" directive
in your php.ini. 

3. Webserver with PHP support
===============================================================================
You can install your favorite webserver with PHP support.
All development and testing was done using Apache/2.2.15 with PHP 5.3.8
Make sure your PHP installation supports at least one supported Database
adapters (see http://framework.zend.com/manual/en/zend.db.adapter.html)

3.1 Setting Up Your VHOST
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


4. Database setup
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


5. Apache Solr Setup
===============================================================================
You have to have a java VM installed prior to installing Solr!
Download a 3.4 release of Apache Solr and extract it somewhere on your server:
http://www.apache.org/dyn/closer.cgi/lucene/solr/

- go to the "example" directory and create a directory named "openskos"
- copy the "data/solr/conf" directory of the OpenSKOS checkout to the 
  SOLR-INSTALL_DIR/example/openskos directory

You can now start Solr (in this example with 1.024Mb memory assigned):
java -Dsolr.solr.home="./openskos" -Xms1024m -Xmx1024m -jar start.jar


6. Data Ingest
===============================================================================
Once you have the application running you can start adding data,
managed in "collections".

You can create a collection in the dashboard.

There are three ways to populate a collection:

6.1 REST-interface
-------------------------------------------------------------------------------
Send data via the REST-API, e.g. like this:

> curl -H "Accept: text/xml" -X POST -T sample-concept.rdf http://localhost/OpenSKOS/public/api/concept

You find the required format of the input data described in the API-docs under:
http://openskos.org/api#concept-create

You may send only one concept per call.
Also, you have to identify the tenant and provide the API key, 
which you assign to the user in the dashboard.


6.2 Uploader
-------------------------------------------------------------------------------
Upload a dataset (a SKOS/RDF file) via a form in the dashboard:Manage collections.
Here you can provide many concepts within one file (XPath: /rdf:RDF/rdf:Description)

Once you successfully upload the file, it is scheduled for import,
as seen in dashboard:Manage jobs.

The import job can be started with ./tools/jobs.php, 
a CLI script intended to be run with a Cron like task runner. 


6.3 OAI ???
-------------------------------------------------------------------------------
Third possiblity is to replicate an existing dataset via OAI-PMH, 
either from other OpenSKOS-instances or from an external source providing SKOS-data.

???
For this, you set the [OAI baseURL]-field of a collection to the OAI-PMH endpoint of an external provider
and let the source be harvested.

The harvest job can be started with ./tools/harvest.php, 
another CLI script meant to be run as a cron-task.
???
