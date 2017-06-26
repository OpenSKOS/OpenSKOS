<?php

/*
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\Api\Response;

use OpenSkos2\Tenant;

class BackwardCompatibility
{
    public function backwardCompatibilityMap($newStyleBody, $rdfType)
    {
        switch ($rdfType) {
            case Tenant::TYPE:
                return $this->backwardCompatibilityMapTenant($newStyleBody);
            default:
                return $newStyleBody;
        }
    }
    
    private function backwardCompatibilityMapTenant($newStyleBody)
    {
        $oldStyleBodyArray = [
            "code" => ($newStyleBody["code"]),
            "name" => ($newStyleBody["vcard_org"][0]["vcard_orgname"]),
            "disableSearchInOtherTenants" => ($newStyleBody["disableSearchInOtherTenants"]),
            "enableStatussesSystem" => ($newStyleBody["enableStatussesSystem"]),
        ];
        
        if (isset($newStyleBody["vcard_org"][0]["vcard_orgunit"])) {
            $oldStyleBodyArray["organisationUnit"] = $newStyleBody["vcard_org"][0]["vcard_orgunit"];
        }
        if (isset($newStyleBody["vcard_email"])) {
            $oldStyleBodyArray["email"] = $newStyleBody["vcard_email"];
        }
        if (isset($newStyleBody["vcard_url"])) {
            $oldStyleBodyArray["webpage"] = $newStyleBody["vcard_url"];
        }
        if (isset($newStyleBody["vcard_adr"][0]["vcard_street"])) {
            $oldStyleBodyArray["streetAddress"] = $newStyleBody["vcard_adr"][0]["vcard_street"];
        }
        if (isset($newStyleBody["vcard_adr"][0]["vcard_locality"])) {
            $oldStyleBodyArray["locality"] = $newStyleBody["vcard_adr"][0]["vcard_locality"];
        }
        if (isset($newStyleBody["vcard_adr"][0]["vcard_pcode"])) {
            $oldStyleBodyArray["postalCode"] = $newStyleBody["vcard_adr"][0]["vcard_pcode"];
        }
        if (isset($newStyleBody["vcard_adr"][0]["vcard_country"])) {
            $oldStyleBodyArray["countryName"] = $newStyleBody["vcard_adr"][0]["vcard_country"];
        }
        if (isset($newStyleBody["enableSkosXl"])) {
            $oldStyleBodyArray["enableSkosXl"] = $newStyleBody["enableSkosXl"];
        } else {
            $oldStyleBodyArray["enableSkosXl"] = true;
        }
        if (isset($newStyleBody["sets"])) {
            $oldStyleSet = [];
            $oldStyleBodyArray["collections"] = array();
            foreach ($newStyleBody["sets"] as $set) {
                $oldStyleSet["uri"] = $set["uri"];
                $oldStyleSet["code"] = $set["code"];
                $oldStyleSet["tenant"] = $set["dcterms_publisher"];
                if (isset($set["dcterms_title"])) {
                    $oldStyleSet["dc_title"] = $set["dcterms_title"];
                } else {
                    if (isset($set["dcterms_title@en"])) {
                        $oldStyleSet["dc_title"] = $set["dcterms_title@en"];
                    } else {
                        $oldStyleSet["dc_title"] = "error in defining set title";
                    }
                }
                if (isset($set["dcterms_description"])) {
                    $oldStyleBodyArray["dc_description"] = $set["dcterms_description"];
                }
                $oldStyleSet["license_url"] = $set["dcterms_license"];
                if (isset($set["license_name"])) {
                    $oldStyleBodyArray["license_name"] = $set["license_name"];
                }
                if (isset($set["webpage"])) {
                    $oldStyleBodyArray["webpage"] = $set["webpage"];
                }
                $oldStyleBodyArray["allow_oai"] = $set["allow_oai"];
                $oldStyleBodyArray["conceptBaseUrl"] = $set["conceptBaseUri"];
                $oldStyleBodyArray["OAI_baseUrl"] = $set["OAI_base_uri"];
                $oldStyleBodyArray["collections"][] = (object) $oldStyleSet;
                $oldStyleSet = [];
            }
        }
        $oldStyleBody = (object) $oldStyleBodyArray;
        return $oldStyleBody;
    }
}
