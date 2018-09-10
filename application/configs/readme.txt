Application ini file must contain a section "customize application" which is used to
ensure flexibility for setting up and managing a particular openskos instance
and support legacy. Here these parameters are explained one by one.

## options.backward_compatible ##

Must be set to "true" to run an instance supporting openskos-1 API:
options.backward_compatible = true

## options.authorisation ##

Must be set to "" if POST and PUT actions are delegated to the editor, 
and POST and PUT actions for API give 501:

options.authorisation = ""

When set e.g.to "Custom\Authorisation" then the instance administrator
must provide its  institution authorisation procedures in 
/Library/OpenSkos2/Custom/Authorisation class which must implement shared 
Authorisation interface.

## options.delete.integrity_check ##

Standard setting is 

options.delete.integrity_check = true

If set to "true" then an attempt to delete a resource which is referred from 
another resource is blocked. For instance, the deletion of a schema from triple 
store is blocked once there are concepts in this scheme. Default deletion 
performs a function similar to integrity check in MySQL.

If set to "false" then the integrity check during deletion is not performed.

## options.relation_types ##

Standard setting is 

options.relation_types = ""

which means that there is no other relations than skos relations. For instance,
this is the case for the current "Beeld en Geluid" instance.

If set, e.g. to "Custom\RelationTypes" then the instance administrator
must provide  OpenSkos2/Library/Custom/RelationTypes implementing the 
corresponding shared interface.  

## options.uri_generate ##

Standard setting is 

options.uri_generate = true

If set to "" then the uri will be generated as in OpenSkos1. Otherwise if it is
set e.g. to \Custom\UriGeneration then  the instance administrator 
should provide  OpenSkos2/Library/Custom/UriGeneration implementing the 
corresponding shared  interface. For instance, for meertens the customized uri 
generation involves call to an EPIC server. The EPIC settings are added to 
meertens application.ini as well.

## options.relations_strict_reference_check ##

Standard setting is:

options.relations_strict_reference_check = 
"http://www.w3.org/2004/02/skos/core#broader,http://www.w3.org/2004/02/skos/core#broaderTransitive,http://www.w3.org/2004/02/skos/core#narrower,http://ww.w3.org/2004/02/skos/core#narrowerTransitive"

It means that during validation a resource, if it refers via one of these 
properties to a resource which is not in "this" triple store, it will be 
declared invalid, and error will be thrown.

## options.relations_soft_reference_check ##

Standard setting is 

options.relations_soft_reference_check = 
"http://www.w3.org/2004/02/skos/core#related,http://www.w3.org/2004/02/skos/core#semanticRelation,http://www.w3.org/2004/02/skos/core#broadMatch,http://www.w3.org/2004/02/skos/core#closeMatch,http://www.w3.org/2004/02/skos/core#exactMatch,http://www.w3.org/2004/02/skos/core#mappingRelation,http://www.w3.org/2004/02/skos/core#narrowMatch,http://www.w3.org/2004/02/skos/core#relatedMatch"

It means that during validation a resource, if it refers via one of these 
properties to a resource which is not in "this" triple store, it will NOT be 
declared invalid, but the waring will be generated.

## options.allowed_concepts_for_other_tenant_schemes ##

Standard setting is 
options.allowed_concepts_for_other_tenant_schemes = true

The name speaks for itself.

## number of numeric settings, the names speak for themselves ##

options.limit = 30;
options.maximal_rows = 500
options.maximal_time_limit = 120
options.normal_time_limit = 30

## options.backend ##

Example 

options.backend = clavas

Used when one needs to run several openskos instances under one apache, to 
distinguish them. May be used in custom uri generation. 


## options.uuid_regexp_prefixes ##

When pure uuid is used, without prefixes, must be an empty string:

options.uuid_regexp_prefixes = ''

Otherwise, it must contain a php regexp describing the prefix, for instance:

options.uuid_regexp_prefixes = '/CCR_(.*)_/'