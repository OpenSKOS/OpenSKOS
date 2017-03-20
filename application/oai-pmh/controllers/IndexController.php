<?php
/**
 * OpenSKOS
*
* LICENSE
*
* This source file is subject to the GPLv3 license that is bundled
* with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://www.gnu.org/licenses/gpl-3.0.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@zend.com so we can send you a copy immediately.
*
* @category   OpenSKOS
* @package    OpenSKOS
* @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
* @author     Mark Lindeman
* @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
*/

class OaiPmh_IndexController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf8');
    }

    /**
     * @api {Identify} /oai-pmh?verb=Identify Identify
     * @apiVersion 1.0.0
     * @apiGroup OAI-PMH
     * @apiDescription Identify the OAI-PMH endpoint<br>
     * <a href='/oai-pmh?verb=Identify' target='_blank'>/oai-pmh?verb=Identify</a>
     * @apiParam {String} verb=Identify
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T12:28:18Z&lt;/responseDate>
     *   &lt;request verb="Identify">http://openskos/oai-pmh&lt;/request>
     *   &lt;Identify>
     *     &lt;repositoryName>OpenSKOS - OAI-PMH Service provider&lt;/repositoryName>
     *     &lt;baseURL>http://openskos/oai-pmh&lt;/baseURL>
     *     &lt;protocolVersion>2.0&lt;/protocolVersion>
     *     &lt;adminEmail>oai-pmh@openskos.org&lt;/adminEmail>
     *     &lt;earliestDatestamp>2017-02-14T16:28:32Z&lt;/earliestDatestamp>
     *     &lt;deletedRecord>persistent&lt;/deletedRecord>
     *     &lt;granularity>YYYY-MM-DDThh:mm:ssZ&lt;/granularity>
     *   &lt;/Identify>
     * &lt;/OAI-PMH>
     */
    
    /**
     * @api {ListMetadataFormats} /oai-pmh?verb=ListMetadataFormats ListMetadataFormats
     * @apiVersion 1.0.0
     * @apiGroup OAI-PMH
     * @apiDescription List metadataPrefixes<br>
     * <a href='/oai-pmh?verb=ListMetadataFormats' target='_blank'>/oai-pmh?verb=ListMetadataFormats</a>
     * @apiParam {String} verb=ListMetadataFormats
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T12:29:29Z&lt;/responseDate>
     *   &lt;request verb="ListMetadataFormats">http://openskos/oai-pmh&lt;/request>
     *   &lt;ListMetadataFormats>
     *     &lt;metadataFormat>
     *       &lt;metadataPrefix>oai_rdf&lt;/metadataPrefix>
     *       &lt;schema>http://www.openarchives.org/OAI/2.0/rdf.xsd&lt;/schema>
     *       &lt;metadataNamespace>http://www.w3.org/2004/02/skos/core#&lt;/metadataNamespace>
     *     &lt;/metadataFormat>
     *     &lt;metadataFormat>
     *       &lt;metadataPrefix>oai_rdf_xl&lt;/metadataPrefix>
     *       &lt;schema>http://www.openarchives.org/OAI/2.0/rdf.xsd&lt;/schema>
     *       &lt;metadataNamespace>http://www.w3.org/2008/05/skos-xl#&lt;/metadataNamespace>
     *     &lt;/metadataFormat>
     *   &lt;/ListMetadataFormats>
     * &lt;/OAI-PMH>
     */
    
    /**
     * @api {ListSets} /oai-pmh?verb=ListSets ListSets
     * @apiVersion 1.0.0
     * @apiGroup OAI-PMH
     * @apiDescription List ConceptSchemes (as OAI-PMH "sets")<br>
     * <a href='/oai-pmh?verb=ListSets' target='_blank'>/oai-pmh?verb=ListSets</a>
     * @apiParam {String} verb=ListSets
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T12:29:39Z&lt;/responseDate>
     *   &lt;request verb="ListSets">http://openskos/oai-pmh&lt;/request>
     *   &lt;ListSets>
     *     &lt;set>
     *       &lt;setSpec>pic&lt;/setSpec>
     *       &lt;setName>maldimirov&lt;/setName>
     *     &lt;/set>
     *     &lt;set>
     *       &lt;setSpec>pic:man&lt;/setSpec>
     *       &lt;setName>mandarin&lt;/setName>
     *     &lt;/set>
     *     &lt;set>
     *       &lt;setSpec>pic:gtaa&lt;/setSpec>
     *       &lt;setName>Hard coded by amitsev&lt;/setName>
     *     &lt;/set>
     *     &lt;set>
     *       &lt;setSpec>pic:gtaa:c171215b-3891-476b-be0c-02ff7be5e50c&lt;/setSpec>
     *       &lt;setName>cs3&lt;/setName>
     *     &lt;/set>
     *   &lt;/ListSets>
     * &lt;/OAI-PMH>
     */
    
    /**
     * @api {ListRecords} /oai-pmh?verb=ListRecords ListRecords
     * @apiVersion 1.0.0
     * @apiGroup OAI-PMH
     * @apiDescription List Concepts based on params <br>
     * <br>
     * Example request URLs:<br>
     * <a href='/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf' target='_blank'>/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf</a><br>
     * <a href='/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf_xl' target='_blank'>/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf_xl</a><br>
     * <a href='/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf&from=2011-09-01T12:00:00Z' target='_blank'>/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf&from=2011-09-01T12:00:00Z</a><br>
     * <a href='/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf&from=2011-09-01&until=2011-09-10' target='_blank'>/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf&from=2011-09-01&until=2011-09-10</a><br>
     * <a href='/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf&set=4' target='_blank'>/oai-pmh?verb=ListRecords&metadataPrefix=oai_rdf&set=4</a><br>
     * @apiParam {String} verb=ListSets
     * @apiParam {String=oai_rdf, oai_rdf_xl} metadataPrefix
     * @apiParam {String=2011-09-10,2011-09-01T12:00:00Z} [from] Start date for filtering by period
     * @apiParam {String=2011-09-10,2011-09-01T12:00:00Z} [until] End date for filtering by period
     * @apiParam {String} [set] List Concepts from specific set
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T12:31:56Z&lt;/responseDate>
     *   &lt;request verb="ListRecords" metadataPrefix="oai_rdf" from="2017-03-15T10:31:48Z">http://openskos/oai-pmh&lt;/request>
     *   &lt;ListRecords xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#">
     *     &lt;record xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#">
     *       &lt;header>
     *         &lt;identifier>4e8f9b50-aa25-4a02-ba9e-f0873c5a6942&lt;/identifier>
     *         &lt;datestamp>2017-03-15T10:31:48Z&lt;/datestamp>
     *         &lt;setSpec>pic&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa:c171215b-3891-476b-be0c-02ff7be5e50c&lt;/setSpec>
     *       &lt;/header>
     *       &lt;metadata xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#">
     *         &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#">
     *
     *   &lt;rdf:Description rdf:about="http://openskos/api/collections/pic:gtaa/16">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *     &lt;skos:prefLabel xml:lang="en">Dog&lt;/skos:prefLabel>
     *     &lt;skos:prefLabel xml:lang="nl">Hond&lt;/skos:prefLabel>
     *     &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *     &lt;skos:hiddenLabel xml:lang="en">Ddog&lt;/skos:hiddenLabel>
     *     &lt;skos:hiddenLabel xml:lang="nl">Hoond&lt;/skos:hiddenLabel>
     *     &lt;skos:hiddenLabel xml:lang="en">Doug&lt;/skos:hiddenLabel>
     *     &lt;skos:hiddenLabel xml:lang="nl">Hont&lt;/skos:hiddenLabel>
     *     &lt;openskos:modifiedBy rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;openskos:set rdf:resource="http://openskos/api/collections/pic:gtaa"/>
     *     &lt;skos:altLabel xml:lang="nl">Reu&lt;/skos:altLabel>
     *     &lt;skos:altLabel xml:lang="en">Doggy&lt;/skos:altLabel>
     *     &lt;skos:inScheme rdf:resource="http://openskos/api/collections/pic:gtaa/cs3"/>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-15T09:51:53+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *     &lt;openskos:toBeChecked xml:lang="nl">0&lt;/openskos:toBeChecked>
     *     &lt;openskos:uuid>4e8f9b50-aa25-4a02-ba9e-f0873c5a6942&lt;/openskos:uuid>
     *     &lt;dcterms:creator rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;openskos:status>candidate&lt;/openskos:status>
     *     &lt;skos:notation>16&lt;/skos:notation>
     *   &lt;/rdf:Description>
     *
     * &lt;/rdf:RDF>
     *       &lt;/metadata>
     *     &lt;/record>
     *     &lt;resumptionToken completeListSize="1" cursor="0"/>
     *   &lt;/ListRecords>
     * &lt;/OAI-PMH>
     *
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T13:12:38Z&lt;/responseDate>
     *   &lt;request verb="ListRecords" metadataPrefix="oai_rdf_xl" from="2017-03-01T12:00:00Z">http://openskos/oai-pmh&lt;/request>
     *   &lt;ListRecords xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *     &lt;record xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *       &lt;header>
     *         &lt;identifier>4e8f9b50-aa25-4a02-ba9e-f0873c5a6942&lt;/identifier>
     *         &lt;datestamp>2017-03-15T10:31:48Z&lt;/datestamp>
     *         &lt;setSpec>pic&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa:c171215b-3891-476b-be0c-02ff7be5e50c&lt;/setSpec>
     *       &lt;/header>
     *       &lt;metadata xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *         &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *
     *   &lt;rdf:Description rdf:about="http://openskos/api/collections/pic:gtaa/16">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *     &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *     &lt;openskos:modifiedBy rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;openskos:set rdf:resource="http://openskos/api/collections/pic:gtaa"/>
     *     &lt;skos:inScheme rdf:resource="http://openskos/api/collections/pic:gtaa/cs3"/>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-15T09:51:53+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *     &lt;openskos:toBeChecked xml:lang="nl">0&lt;/openskos:toBeChecked>
     *     &lt;openskos:uuid>4e8f9b50-aa25-4a02-ba9e-f0873c5a6942&lt;/openskos:uuid>
     *     &lt;dcterms:creator rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;openskos:status>candidate&lt;/openskos:status>
     *     &lt;skos:notation>16&lt;/skos:notation>
     *     &lt;skosxl:prefLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/2914fa31-92bb-4081-a9a7-fed039d58cb8">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="en">Dog&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:prefLabel>
     *
     *     &lt;skosxl:prefLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/8590f74a-4995-4367-8f1a-7cd5a9f28a9c">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Hond&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:prefLabel>
     *
     *     &lt;skosxl:altLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/24892eec-3467-4d9c-860f-c1cfb57f6fff">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Reu&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:altLabel>
     *
     *     &lt;skosxl:altLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/4f19b15f-7fe0-4262-b171-525d1431a16a">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="en">Doggy&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:altLabel>
     *
     *     &lt;skosxl:hiddenLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/2abe51c6-61b3-483f-a1e0-fb9d80d1aa5d">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="en">Ddog&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:hiddenLabel>
     *
     *     &lt;skosxl:hiddenLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/e29d4d03-e617-49cc-b75e-03192995db9c">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Hont&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:hiddenLabel>
     *
     *     &lt;skosxl:hiddenLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/82d58731-a73a-4586-9109-48567b13ffe6">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="en">Doug&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:hiddenLabel>
     *
     *     &lt;skosxl:hiddenLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/16715066-6c5d-479f-a35c-591df2833920">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-15T10:31:48+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Hoond&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:hiddenLabel>
     *
     *   &lt;/rdf:Description>
     *
     * &lt;/rdf:RDF>
     *       &lt;/metadata>
     *     &lt;/record>
     *     &lt;resumptionToken completeListSize="1" cursor="0"/>
     *   &lt;/ListRecords>
     * &lt;/OAI-PMH>
     *
     */
    
    /**
     * @api {GetRecord} /oai-pmh?verb=GetRecord GetRecord
     * @apiVersion 1.0.0
     * @apiGroup OAI-PMH
     * @apiDescription Get a specific concept <br>
     * <br>
     * Example request URLs:<br>
     * <a href='/oai-pmh?verb=GetRecord&metadataPrefix=oai_rdf&identifier=238c8bfb-8915-0379-b52e-0885bfc9e500' target='_blank'>/oai-pmh?verb=GetRecord&metadataPrefix=oai_rdf&identifier=238c8bfb-8915-0379-b52e-0885bfc9e500</a><br>
     * <a href='/oai-pmh?verb=GetRecord&metadataPrefix=oai_rdf_xl&identifier=238c8bfb-8915-0379-b52e-0885bfc9e500' target='_blank'>/oai-pmh?verb=GetRecord&metadataPrefix=oai_rdf_xl&identifier=238c8bfb-8915-0379-b52e-0885bfc9e500</a><br>
     * @apiParam {String} verb=GetRecord
     * @apiParam {String=oai_rdf, oai_rdf_xl} metadataPrefix
     * @apiParam {String} identifier The concept identifier
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T13:19:16Z&lt;/responseDate>
     *   &lt;request verb="GetRecord" metadataPrefix="oai_rdf" identifier="2d31586b-2d29-4922-a4c6-94a41597ebf4">http://openskos/oai-pmh&lt;/request>
     *   &lt;GetRecord xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#">
     *     &lt;record xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#">
     *       &lt;header>
     *         &lt;identifier>2d31586b-2d29-4922-a4c6-94a41597ebf4&lt;/identifier>
     *         &lt;datestamp>2017-02-14T16:28:32Z&lt;/datestamp>
     *         &lt;setSpec>pic&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa:c171215b-3891-476b-be0c-02ff7be5e50c&lt;/setSpec>
     *       &lt;/header>
     *       &lt;metadata xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#">
     *         &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#">
     *
     *   &lt;rdf:Description rdf:about="http://openskos/api/collections/pic:gtaa/13">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *     &lt;dcterms:creator rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;skos:inScheme rdf:resource="http://openskos/api/collections/pic:gtaa/cs3"/>
     *     &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *     &lt;skos:hiddenLabel xml:lang="nl">Food n Agriculture Organization&lt;/skos:hiddenLabel>
     *     &lt;skos:hiddenLabel xml:lang="nl">Fod and Agriculture Organization&lt;/skos:hiddenLabel>
     *     &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-14T16:28:32+00:00&lt;/dcterms:modified>
     *     &lt;openskos:modifiedBy rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;skos:notation>13&lt;/skos:notation>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-14T16:28:32+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;skos:altLabel xml:lang="nl">FAO&lt;/skos:altLabel>
     *     &lt;skos:prefLabel xml:lang="nl">Food and Agriculture Organization&lt;/skos:prefLabel>
     *     &lt;openskos:status>candidate&lt;/openskos:status>
     *     &lt;openskos:uuid>2d31586b-2d29-4922-a4c6-94a41597ebf4&lt;/openskos:uuid>
     *     &lt;openskos:set rdf:resource="http://openskos/api/collections/pic:gtaa"/>
     *   &lt;/rdf:Description>
     *
     * &lt;/rdf:RDF>
     *       &lt;/metadata>
     *     &lt;/record>
     *   &lt;/GetRecord>
     * &lt;/OAI-PMH>
     *
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * &lt;?xml version="1.0" encoding="UTF-8"?>
     * &lt;OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
     *   &lt;responseDate>2017-03-15T13:19:41Z&lt;/responseDate>
     *   &lt;request verb="GetRecord" metadataPrefix="oai_rdf_xl" identifier="2d31586b-2d29-4922-a4c6-94a41597ebf4">http://openskos/oai-pmh&lt;/request>
     *   &lt;GetRecord xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *     &lt;record xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *       &lt;header>
     *         &lt;identifier>2d31586b-2d29-4922-a4c6-94a41597ebf4&lt;/identifier>
     *         &lt;datestamp>2017-02-14T16:28:32Z&lt;/datestamp>
     *         &lt;setSpec>pic&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa&lt;/setSpec>
     *         &lt;setSpec>pic:gtaa:c171215b-3891-476b-be0c-02ff7be5e50c&lt;/setSpec>
     *       &lt;/header>
     *       &lt;metadata xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *         &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skosxl="http://www.w3.org/2008/05/skos-xl#">
     *
     *   &lt;rdf:Description rdf:about="http://openskos/api/collections/pic:gtaa/13">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *     &lt;dcterms:creator rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;skos:inScheme rdf:resource="http://openskos/api/collections/pic:gtaa/cs3"/>
     *     &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *     &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-14T16:28:32+00:00&lt;/dcterms:modified>
     *     &lt;openskos:modifiedBy rdf:resource="http://openskos/api/users/24efcec8-4c28-46d9-ab5f-3bcb13bc8761"/>
     *     &lt;skos:notation>13&lt;/skos:notation>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-14T16:28:32+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;openskos:status>candidate&lt;/openskos:status>
     *     &lt;openskos:uuid>2d31586b-2d29-4922-a4c6-94a41597ebf4&lt;/openskos:uuid>
     *     &lt;openskos:set rdf:resource="http://openskos/api/collections/pic:gtaa"/>
     *     &lt;skosxl:prefLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/b765debf-63b6-41a8-9fcd-723e4abbb7e1">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-15T14:59:25+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Food and Agriculture Organization&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:prefLabel>
     *
     *     &lt;skosxl:altLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/9b6c21d7-087b-4cb8-b132-0357e747a7f3">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-15T14:59:27+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">FAO&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:altLabel>
     *
     *     &lt;skosxl:hiddenLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/f935e4d8-460a-43e9-bb7a-40c46353f84d">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-15T14:59:30+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Food n Agriculture Organization&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:hiddenLabel>
     *
     *     &lt;skosxl:hiddenLabel>
     *       &lt;rdf:Description rdf:about="http://openskos/api/labels/8cb51012-ecfe-4797-8017-660c4f56e9d6">
     *         &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *         &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-15T14:59:32+00:00&lt;/dcterms:modified>
     *         &lt;openskos:tenant>pic&lt;/openskos:tenant>
     *         &lt;skosxl:literalForm xml:lang="nl">Fod and Agriculture Organization&lt;/skosxl:literalForm>
     *       &lt;/rdf:Description>
     *     &lt;/skosxl:hiddenLabel>
     *
     *   &lt;/rdf:Description>
     *
     * &lt;/rdf:RDF>
     *       &lt;/metadata>
     *     &lt;/record>
     *   &lt;/GetRecord>
     * &lt;/OAI-PMH>
     */
    public function indexAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $repository = new OpenSkos2\OaiPmh\Repository(
            $this->getDI()->get('OpenSkos2\ConceptManager'),
            $this->getDI()->get('OpenSkos2\ConceptSchemeManager'),
            $this->getDI()->get('OpenSkos2\Search\Autocomplete'),
            'OpenSKOS - OAI-PMH Service provider',
            $this->getBaseUrl(),
            ['oai-pmh@openskos.org'],
            new \OpenSKOS_Db_Table_Collections(),
            null
        );
        
        $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $provider = new Picturae\OaiPmh\Provider($repository, $request);
        $response = $provider->getResponse();
        (new Zend\Diactoros\Response\SapiEmitter())->emit($response);
    }

    public function getAction()
    {
        $this->_501('GET');
    }

    public function postAction()
    {
        $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('POST');
    }

    public function deleteAction()
    {
        $this->_501('DELETE');
    }

    /**
     * Get base url
     * @return string
     */
    private function getBaseUrl()
    {
        return $this->view->serverUrl() . $this->view->url();
    }
}
