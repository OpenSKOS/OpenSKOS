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
2. Install stand alone fuseki server. Instructions copied from [getting started](https://jena.apache.org/documentation/serving_data/#getting-started-with-fuseki)
  1. Unpack the downloaded file with unzip or tar zxfv
  2. `chmod +x fuseki-server s-*`
3. Symlink or copy the file 
`data/fuseki/configuration/openskos.ttl` into `<fuseki server folder>/run/configuration/`
4. Run the fuseki server with
`./fuseki-server --update`


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
