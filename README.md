[![Build Status](https://travis-ci.org/picturae/OpenSKOS.svg)](https://travis-ci.org/picturae/OpenSKOS)

1 Install the OpenSKOS code
===========================
Copy the code to a location of your choice.

Make sure all files are readable by your webserver. Make sure the directories
`data/uploads`, `cache`, `public/data/icons/assigned` and
`public/data/icons/uploads` are writable for the webserver.

For security reasons you can place the `data` directory outside your
webserver's document root.

1.1 Composer
------------
Run composer install to install some dependencies like zend framework 1.12

1.2 Configuration
-----------------
To configure OpenSKOS you have to rename:

    APPROOT/application/configs/application.ini.dist

to

    APPROOT/application/configs/application.ini

Now you can edit the `APPROOT/application/configs/application.ini`.

You can have separate config settings for specific deployments. The
configuration section marked by the Environment Variable `APPLICATION_ENV` (see
*2.1 Setting Up Your VHOST*). Most settings are self explanatory.

If you experience any problems you may want to modify settings in the config,
to show you more verbose error messages:

    resources.frontController.params.displayExceptions=1
    phpSettings.display_errors = 1


1.2.1 OAI-PMH setup
-------------------
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
the key `example1` is used).

1.2.2 ConceptScheme ordering
----------------------------
The application.ini allows you to change the order in which concept schemes are listed everywhere.
The scheme order is made in this sequence:
 - group the schemes according to their collection
 - order the groups by the desired collection order
 - sort the schemes inside each group alphabetically

The collection order can be set in the ini by setting the editor.schemeOrder.collections[]="<collectionUri>"
All unlisted collections will be ordered after the listed ones.
All listed collections that re not present in the DB will be skipped.
In this way the ini supports collection ordering for more than 1 instances.

# 2. Webserver with PHP support
You can install your favourite webserver with PHP support.
All development and testing was done using Apache/2.2.15 with PHP 5.3.8
Make sure your PHP installation supports at least one supported Database
adapters (see http://framework.zend.com/manual/en/zend.db.adapter.html or otherwise: https://docs.zendframework.com/zend-db/adapter/ )

## 2.1 Setting Up Your VHOST
The following is a sample VHOST you might want to consider for your project.

```
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
```

# 3. Database setup
Install your choice of Zend Framework supported Database engine (see
http://framework.zend.com/manual/en/zend.db.adapter.html). The credentials to
access your database can be configured in the application's configuration.

Once you have created an empty database, you have to run the SQL script
`APPROOT/data/openskos-create.sql` to create the db-tables.

You also have to run the php-script to create a tenant:

    php APPROOT/tools/tenant.php --code INST_CODE --name INST_NAME --email EMAIL --password PWD create

With this account created you can login into the dashboard,
where you can manage all the other entities of the application.


# 4. Apache Jena Fuseki setup
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

# 5. Apache Solr Setup
You have to have a java VM installed prior to installing Solr!
Download a 3.4 release of Apache Solr and extract it somewhere on your server:
http://www.apache.org/dyn/closer.cgi/lucene/solr/

- go to the `example` directory and create a directory named `openskos`
- copy the `data/solr/conf` directory of the OpenSKOS checkout to the
  `SOLR-INSTALL_DIR/example/openskos` directory

You can now start Solr (in this example with 1,024 MB memory assigned):

    java -Dsolr.solr.home="./openskos" -Xms1024m -Xmx1024m -jar start.jar


# 6. Data Ingest
Once you have the application running you can start adding data,
managed in `collections`.

You can create a collection in the dashboard.

There are three ways to populate a collection:

## 6.1 REST-interface
Send data via the REST-API, e.g. like this:

> curl -H "Accept: text/xml" -X POST -T sample-concept.rdf http://localhost/OpenSKOS/public/api/concept

You find the required format of the input data described in the API-docs under:
http://openskos.org/api#concept-create

You may send only one concept per call.
Also, you have to identify the tenant and provide the API key,
which you assign to the user in the dashboard.


## 6.2 Uploader
Upload a dataset (a SKOS/RDF file) via a form in the dashboard:Manage collections.
Here you can provide many concepts within one file (XPath: `/rdf:RDF/rdf:Description`)

Once you successfully upload the file, it is scheduled for import,
as seen in *dashboard:Manage jobs*.

The import job can be started with `./tools/jobs.php`,
a CLI script intended to be run with a Cron like task runner.


## 6.3 OAI ???
Third possiblity is to replicate an existing dataset via OAI-PMH,
either from other OpenSKOS-instances or from an external source providing SKOS-data.

???
For this, you set the [OAI baseURL]-field of a collection to the OAI-PMH endpoint of an external provider
and let the source be harvested.

The harvest job can be started with ./tools/harvest.php,
another CLI script meant to be run as a cron-task.
???

## 6.4 Migrate from OpenSKOS v1
It is possible to migrate the data from the SOLR core used by a OpenSKOS v1 instance directly into a v2 instance

`tools/migrate.php --endpoint http://<solr server>:8180/ciss/<core name>/select`

Once this is complete the data from the v1 instance will be available in the triple store used by OpenSKOS v2.

## 6.5 API Documentation
Generate API Documentation

```
npm install
npm run doc
```
Visit: http://example.com/apidoc/

## 7 Development
To test / develop the application you can run

```
docker-compose up --build
docker exec -it openskos-php-fpm ./vendor/bin/phing install.dev
```

Go to `http://localhost:9001/manage.html?tab=datasets` login with admin / admin
create a persistent dataset named `openskos`
Create a test tenant / user in the openskos application

```
docker exec -it openskos-php-fpm php ./tools/tenant.php create -e development --code=pic --name=Picturae --email=test@example.com --password=test
```

Now you can login on http://localhost:9000/editor/login
