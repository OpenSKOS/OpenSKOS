Application ini file must contain a section "customize application" which is used to
ensure flexibility for setting up and managing a particular openskos instance
and support legacy. Here these parameters are explained one by one.

## custom.backward_compatible ##

Must be set to "true" to run an instance supporting openskos-1 API:
custom.backward_compatible = true

## custom.default_authorisation ##

Must be set to "true" if POST and PUT actions are delegated to the editor, 
and POST and PUT actions for API give 501:

custom.default_authorisation = true

When set to "false" then the instance administrator must provide its 
institution authorisation procedures in OpenSkos2/Library/Custom/Authorisation
class which must implement shared Authorisation interface.

## custom.default_deletion ##

Standard setting is 

custom.default_deletion = true

If set to "true" then an attempt to delete a resource which is referred from 
another resource is blocked. For instance, the deletion of a schema from triple 
store is blocked once there are concepts in this scheme. Default deletion 
performs a function similar to integrity check in MySQL.

When set to "false" then the instance administrator should provide its 
institution deletion procedures in OpenSkos2/Library/Custom/Deletion
class which must implement shared Deletion interface.

## custom.default_relationtypes ##

Standard setting is 

custom.default_relationtypes = true

which means that there is no other relations than skos relations. For instance,
this is the case for the current "Beeld en Geluid" instance.

If set to "false" then the instance administrator must provide 
OpenSkos2/Library/Custom/RelationTypes implementing the corresponding shared 
interface.  

## custom.default_urigenerate ##

Standard setting is 

custom.default_urigenerate = true

If set to "false" then the instance administrator should provide 
OpenSkos2/Library/Custom/UriGeneration implementing the corresponding shared 
interface. For instance, for meertens the customized uri generation involves
call to an EPIC server. The EPIC settings are added to meertens application.ini 
as well.

## custom.relations_strict_reference_check ##

Standard setting is:

custom.relations_strict_reference_check = 
"http://www.w3.org/2004/02/skos/core#broader,http://www.w3.org/2004/02/skos/core#broaderTransitive,http://www.w3.org/2004/02/skos/core#narrower,http://ww.w3.org/2004/02/skos/core#narrowerTransitive"

It means that during validation a resource, if it refers via one of these 
properties to a resource which is not in "this" triple store, it will be 
declared invalid, and error will be thrown.

## custom.relations_soft_reference_check ##

Standard setting is 

custom.relations_soft_reference_check = 
"http://www.w3.org/2004/02/skos/core#related,http://www.w3.org/2004/02/skos/core#semanticRelation,http://www.w3.org/2004/02/skos/core#broadMatch,http://www.w3.org/2004/02/skos/core#closeMatch,http://www.w3.org/2004/02/skos/core#exactMatch,http://www.w3.org/2004/02/skos/core#mappingRelation,http://www.w3.org/2004/02/skos/core#narrowMatch,http://www.w3.org/2004/02/skos/core#relatedMatch"

It means that during validation a resource, if it refers via one of these 
properties to a resource which is not in "this" triple store, it will NOT be 
declared invalid, but the waring will be generated.

## custom.allowed_concepts_for_other_tenant_schemes ##

Standard setting is 
custom.allowed_concepts_for_other_tenant_schemes = true

The name speaks for itself.

## number of numeric settings, the names speak for themselves ##

custom.limit = 30;
custom.maximal_rows = 500
custom.maximal_time_limit = 120
custom.normal_time_limit = 30

## custom.backend ##

Example 

custom.backend = clavas

Used when one needs to run several openskos instances under one apache, to 
distinguish them. May be used in custom uri generation. 


## custom.uuid_regexp_prefixes ##

When pure uuid is used, without prefices, must be an empty string:

custom.uuid_regexp_prefixes = ''

Otherwise, it must contain a php regexp describing the prefix, for instance:

custom.uuid_regexp_prefixes = '/CCR_(.*)_/'