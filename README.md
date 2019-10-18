[![Build Status](https://travis-ci.org/picturae/OpenSKOS.svg)](https://travis-ci.org/picturae/OpenSKOS)

# 1. Install the OpenSKOS code

***

> ___Docker development___
>
> Docker images were added to the OpenSkos project in 2017. If you wish to develop using docker, please proceed to 
section 8

***

Copy the code to a location of your choice.

Make sure all files are readable by your webserver. Make sure the directories
`data/uploads`, `cache`, `public/data/icons/assigned` and
`public/data/icons/uploads` are writable for the webserver.

For security reasons you can place the `data` directory outside your
webserver's document root.


1.1 Composer
------------
Run `composer install` to install some dependencies such as zend framework 1.12

## 1.2 Configuration
To configure OpenSKOS you have to rename:

`APPROOT/application/configs/application.ini.dist`

to

`APPROOT/application/configs/application.ini`

Now you can edit the `APPROOT/application/configs/application.ini`.

You can have separate config settings for specific deployments. The
configuration section marked by the Environment Variable `APPLICATION_ENV` (see
*2.1 Setting Up Your VHOST*). Most settings are self explanatory.

If you experience any problems you may want to modify settings in the config,
to show you more verbose error messages:

```ini
    resources.frontController.params.displayExceptions=1
    phpSettings.display_errors = 1
```

### 1.2.1 OAI-PMH setup
OpenSKOS includes a OAI harvester. To configure OAI Service providers, use the
"instances" part of the configuration. Two types of instances are supported:

- openskos (instances of OpenSKOS)
- external (any OAI-PMH provider that provides SKOS/XML-RDF data)

The setup for "openskos" types is easy:

```ini
    instances.openskos.type=openskos
    instances.openskos.url=http://HOSTNAME
    instances.openskos.label=YOUR LABEL
```

For "external" types use this syntax:

```ini
    instances.example1.type=external
    instances.example1.url=http://HOSTNAME
    instances.example1.label=EXAMPLE LABEL
    #optional, default=oai_rdf
    instances.example1.metadataPrefix=METADATAPREFIX
    #optional:
    instances.example1.set=SETSPEC
```

You can define multiple instances by using a different key (in the above example
the key `example1` is used).

### 1.2.2 ConceptScheme ordering
The application.ini allows you to change the order in which concept schemes are listed everywhere.
The scheme order is made in this sequence:
 - group the schemes according to their collection
 - order the groups by the desired collection order
 - sort the schemes inside each group alphabetically

The collection order can be set in the ini by setting the `editor.schemeOrder.collections[]="<collectionUri>"`
All unlisted collections will be ordered after the listed ones.
All listed collections that re not present in the DB will be skipped.
In this way the ini supports collection ordering for more than 1 instances.

# 2. Webserver with PHP support
You can install your favourite webserver with PHP support.
All development and testing was done using Apache/2.2.15 with PHP 5.3.8
Make sure your PHP installation supports at least one supported Database
adapters (see http://framework.zend.com/manual/en/zend.db.adapter.html or otherwise: https://docs.zendframework.com/zend-db/adapter/)

## 2.1 Setting Up Your VHOST
The following is a sample VHOST you might want to consider for your project.

```apache_conf
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
http://framework.zend.com/manual/en/zend.db.adapter.html or otherwise: https://docs.zendframework.com/zend-db/adapter/). The credentials to
access your database can be configured in the application's configuration.

Once you have created an empty database, you have to run the SQL script
`APPROOT/data/openskos-create.sql` to create the db-tables.

You also have to run the php-script to create a tenant:

```sh
php APPROOT/tools/tenant.php --code INST_CODE --name INST_NAME --email EMAIL --password PWD create
```

With this account created you can login into the dashboard,
where you can manage all the other entities of the application.


# 4. Apache Jena Fuseki setup
Openskos is compatible with Fuseki 2 or Fuzeki 3 for storage. It has been tested up to Fuzeki 3.8 (the latest stable version at time of writing)

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


## 4.1 Jena Updates
Several bug fixes were made to the rules/openskos.ttl file in October 2018, on both the OpenSkos 2.2 (Master at time of
 update ) and Meertens Merge (Development at time of update) branches. When upgrading to these versions, please update 
 the configuration files on the Jena server to the versions located in `./data/fuseki/configuration`.   

# 5. Apache Solr Setup
You have to have a java VM installed prior to installing Solr!
The version of Solr used during development was 7.4.0. Other versions going back to Solr 4 are supported, but it will be 
necessary to adapt the Solr configuration files to the syntax for these versions.

http://www.apache.org/dyn/closer.cgi/lucene/solr/

- go to the `example` directory and create a directory named `openskos`
- copy the `data/solr/conf` directory of the OpenSKOS checkout to the
  `SOLR-INSTALL_DIR/example/openskos` directory

You can now start Solr (in this example with 1,024 MB memory assigned):

```sh
    java -Dsolr.solr.home="./openskos" -Xms1024m -Xmx1024m -jar start.jar
```

## 5.1 Solr Updates
The Solr configuration file was substantially altered during the Meertens Merge project. If upgrading, you will need
to take the example `solrconfig.xml` and `schema.xml` from `./data/solr`, and adapt them to your Solr version.

After updating the configuration, you should delete the contents of the Solr database and re-index using the 
`./tools/jena2Solr.php` script. If you skip this step, OpenSkos will remain functional, but the internal content of the 
Solr core will become inconsistent as records are updated.

# 6. Data Ingest
Once you have the application running you can start adding data,
managed in `collections`.

You can create a collection in the dashboard.

There are three ways to populate a collection:

## 6.1 REST-interface
Send data via the REST-API, e.g. like this:

```sh
curl -H "Accept: text/xml" -X POST -T sample-concept.rdf http://localhost/OpenSKOS/public/api/concept
```

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

```sh
npm install
npm run doc
```
Visit: http://example.com/apidoc/

## 6.6. Using the API
Full HTML documentation of the API is supplied and is available in HTML at `<baseruri>/apidoc`

# 7. Development

## 7.1. Migration from OpenSKOS-1 to OpenSKOS-2.2
_**WARNING:** It is very strongly recommended to back up all data before performing the following steps_

In OpenSkos 2.2 Tenants and Collections in MySQL have been migrated from MySQL to the 
Jena triple store.

To migrate from OpenSKOS 1.0 or 2.1 to 2.2, first read sections 4.1 and 5.1 about updating the Jena and Solr 
configurations. Both steps are necessary when upgrading to OpenSkos 2.2

Then perform the following steps:

-- `/tools/migrate_tenant_collection.php` (migrates tenants and collections from MySQL 
to institutions and sets of Triple store)

-- optionally `/tools/labelsToXl.php` (this is a picturae script slightly extended by 
Meertens), if skos xl labels are demanded.

Examples of the corresponding command lines are:

```
php migrate_tenant_collection.php --db-hostname=localhost --db-database=geheim 
--db-password=geheim --db-username=ookgeheim --debug=1
```

Adding skos xl labels is also possible since version 2.1. To activate, first edit the tenant to enable SkosXL, and then 
update Jena with: 

`labelsToXl.php –add=1`

The SOLR schema.xml file has been updated in version 2.2. Having completed the migration, please 
empty the core, and then update the schema.xml file. Then fill the Solr database with the script: 

`php tools/jena2solr.php`

Max notations are now maintained in a separate MySQL table. Use the script `./data/dbchanges/20180724.sql` to update the 
db schema.

Then execute:
`php tools/updateMaxNotation.php`

-- Publisher URI
And extra triple needs to be added to concepts to allow continued functioning of OpenSKOS.

`php tools/fillConceptPublisher.php`




## 7.2. Import (`/tools/skos2openskos.php`)
Example of a command line:

```sh
php skos2openskos.php --setUri=http://htdl/clavas-org/set 
--userUri=http://localhost:89/clavas/public/api/users/4d1140e5-f5ff-45da-b8de-3d8a2c28415f 
--file=clavas-organisations.xml
```

# 8 Development using Docker

### TL;DR;
```sh
composer install [--ignore-platform-reqs]
php vendor/bin/phing config

docker network create openskos
docker-compose up 

# go to localhost:9001 and create a dataset matching in name with application.ini sparql.queryUri

sudo chmod 777 data/solr
sudo chmod 666 data/solr/*
sudo chown -R 8983:8983 data/solr
docker exec -it openskos-php-fpm php vendor/bin/phing solr.create.core
docker exec -it openskos-php-fpm php tools/tenant.php --code CODE --name NAME --email EMAIL --password PASSWORD create
docker exec -it openskos-php-fpm php tools/jena2solr.php

# go to localhost:9000 and log in using your just-created credentials
```

## 8.1 Installing docker

To test / develop the application go to the root folder, and run: 

```sh
docker-compose up
composer install
docker exec -it openskos-php-fpm ./vendor/bin/phing install.dev
```


## 8.2 Updating the configuration

Then copy the file `./application/configs/application.ini.dist` to `./application/configs/application.ini`  

Under the section `; Solr configuration:` add the following:
```
solr.host=openskos-solr
solr.port=8983
solr.context=/solr/openskos

```

Under the section '; Apache Jena Fuseki configuration:' add the following
```
sparql.queryUri=http://openskos-jena-fuseki:3030/openskos/query
sparql.updateUri=http://openskos-jena-fuseki:3030/openskos/update
```

>**Configuring Jena**
>
>You can then create an empty graph with the name _openskos_ in the Jena interface at http://localhost:9001. 
The admin username:password combination is `admin` and `admin`.
Here you can create a **persistent** dataset named `openskos`

Under the section `; Database configuration:` add the following

```
resources.db.adapter=pdo_mysql
resources.db.params.host=openskos-mysql
resources.db.params.username=root
resources.db.params.password=secr3t
resources.db.params.charset=utf8
resources.db.params.dbname=openskos
resources.db.isDefaultTableAdapter = true
```

## 8.3 Running OpenSkos

Create a test tenant / user in the openskos application

```
docker exec -it openskos-php-fpm php ./tools/tenant.php create -e development --code=pic --name=Picturae --email=test@example.com --password=test
```

Now you can login on http://localhost:9000/editor/login
