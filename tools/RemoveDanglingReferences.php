<?php


namespace Tools;
class RemoveDanglingReferences {

    public static function remove_dangling_references($manager, $blacklist) {
        $removed = 0;
        foreach ($blacklist as $badObject) {
            $subject_type_property_list = $manager->fetchSubjectTypePropertyForObject($badObject);
            foreach ($subject_type_property_list as $subject_type_property) {
                $resource = $manager->fetchByUri($subject_type_property['subject'], $subject_type_property['type']);
                $property = $subject_type_property['property'];
                $values = $resource->getProperty($property);
                $resource->unsetProperty($property);
                foreach ($values as $value) {
                    if ($value instanceof OpenSkos2\Rdf\Uri) {
                        if ($value->getUri() !== $badObject) {
                            $resource->addProperty($property, $value);
                        }
                    } else {
                        $resource->addProperty($property, $value);
                    }
                }
                $manager->replace($resource);
                $removed += 1;
                echo "\n Removed " . $badObject . "\n as property " . $property . "\n from " . $subject_type_property['subject'] . "\n of type " . $subject_type_property['type'] . "\n";
            }
        }
        
        echo "\n Removed " . $removed . " references \n";
    }

}
