#!/usr/local/zend/bin/php
<?php
/**
#BASH SCRIPT
seq -w 0 173 | while read i
do 
	./rdf2solr.php /Users/mlindeman/Downloads/gtaanew.rdf $i | curl http://localhost:8983/solr/update?commit=true -H 'Content-Type:text/xml' --data-binary @-
done

 */
define('TENANT', 'nyc');
define('DEFAULT_LANG', 'nl');
$stopAt = 1000;

$startAt = $stopAt * (isset($argv[2]) ? (int)$argv[2] : 0);

$docCounter = 0;

//Simple parser for GTAA SKOS-RDF to Solr Documents

$simpleMapping = array(
	'SKOS:INSCHEME' => 'inScheme',
	'SKOS:CONCEPTSCHEME' => 'ConceptScheme',
	'SKOS:HASTOPCONCEPT' => 'hasTopConcept',
	'SKOS:TOPCONCEPTOF' => 'topConceptOf',
	'SKOS:SEMANTICRELATION' => 'semanticRelation',
	'SKOS:BROADER' => 'broader',
	'SKOS:NARROWER' => 'narrower',
	'SKOS:RELATED' => 'related',
	'SKOS:BROADERTRANSITIVE' => 'broaderTransitive',
	'SKOS:NARROWERTRANSITIVE' => 'narrowerTransitive',
);

$langMapping = array(
	'SKOS:PREFLABEL' => 'prefLabel',
	'SKOS:ALTLABEL' => 'altLabel',
	'SKOS:HIDDENLABEL' => 'hiddenLabel',
	'SKOS:NOTE' => 'note',
	'SKOS:CHANGENOTE' => 'changeNote',
	'SKOS:DEFINITION' => 'definition',
	'SKOS:EDITORIALNOTE' => 'editorialNote',
	'SKOS:EXAMPLE' => 'example',
	'SKOS:HISTORYNOTE' => 'historyNote',
	'SKOS:SCOPENOTE' => 'scopeNote',
	'SKOS:NOTATION' => 'notation'
);

function startElement($parser, $name, $attrs) 
{
	global $docCounter, $startAt, $simpleMapping, $langMapping;
	
	$DATA = '';
	if (isset($simpleMapping[$name])) {
		$name = $simpleMapping[$name];
		if (!isset($attrs['RDF:RESOURCE'])) {
			print_r($attrs);
			exit($name."\n");
		}
		$DATA = $attrs['RDF:RESOURCE'];
	} elseif (isset($langMapping[$name])) {
		$name = $langMapping[$name];
		if (isset($attrs['XML:LANG'])) {
			$name .= '@'.$attrs['XML:LANG'];
		}
	} elseif ($name == 'RDF:RDF' || $name == 'RDF:DESCRIPTION' || $name == 'RDF:TYPE') {
		
	} else {
		$name = strtolower(str_replace(':', '_', $name));
		if (isset($attrs['RDF:RESOURCE'])) {
			$DATA = $attrs['RDF:RESOURCE'];
		}
		
	}
	switch ($name) {
		case 'RDF:RDF':
			echo "<add>\n";
			break;
		case 'RDF:DESCRIPTION':
			$docCounter++;
			if ($docCounter <= $startAt) {
				return;
			}
			echo "  <doc>";
			echo "\n    <field name=\"tenant\">".TENANT."</field>";
			echo "\n    <field name=\"uri\">{$attrs['RDF:ABOUT']}</field>";
			echo "\n    <field name=\"uuid\">".md5_uuid($attrs['RDF:ABOUT'])."</field>";
			break;
		case 'RDF:TYPE';
			$urlParts = parse_url($attrs['RDF:RESOURCE']);
			echo "\n    <field name=\"class\">{$urlParts['fragment']}</field>";
			break;
		default:
			if ($docCounter <= $startAt) return;
			echo "\n    <field name=\"{$name}\">{$DATA}";
			break;
	}
}

function md5_uuid($value)
{
	$hash = md5($value);
	return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) 
		. '-' . substr($hash, 12, 4)
		. '-' . substr($hash, 16, 4)
		. '-' . substr($hash, 20);
}

function printElement($parser, $name, $value)
{
	startElement($parser, $name, array());
	characterData($parser, $value);
	endElement($parser, $name);
}

function endElement($parser, $name) 
{
	global $docCounter, $stopAt, $startAt, $simpleMapping, $langMapping;
	if (isset($simpleMapping[$name])) $name = $simpleMapping[$name];
	switch ($name) {
		case 'RDF:RDF':
			echo "</add>\n";
			break;
		case 'RDF:DESCRIPTION':
			if ($docCounter <= $startAt) return;
			echo "\n  </doc>\n";
			if ($docCounter >= $stopAt + $startAt) {
				endElement($parser, 'RDF:RDF');
				exit;
			}
			break;
		case 'RDF:TYPE';
			break;
		default:
			if ($docCounter <= $startAt) return;
			echo "</field>";
			break;
	}
}

function characterData($parser, $data) 
{
	global $docCounter, $startAt;
	if ($docCounter <= $startAt) return;
	else echo trim($data) == '{}' ? '' : htmlspecialchars(trim($data));
}

$xml_parser = xml_parser_create();
xml_parser_set_option( $xml_parser, XML_OPTION_SKIP_WHITE, 1 );
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterData");
if (!($fp = fopen($argv[1], "r"))) {
    die("could not open XML input");
}

while ($data = fread($fp, 4096)) {
    if (!xml_parse($xml_parser, $data, feof($fp))) {
        die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser)));
    }
}
xml_parser_free($xml_parser);