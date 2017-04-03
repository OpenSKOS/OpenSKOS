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
/* VOORBEELD!!!!
 * Run the file as :  php tenant.php --epic=true --code=testcode8 --name=testtenant8 --disableSearchInOtherTenants=true --enableStatussesSystem=true --email=o4@mail.com --uri=http://ergens/xxx5 --uuid=yyy5 --password=xxx create
 */

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Set;

function check_if_admin($tenant_code, $key, $resourceManager, $user_model) {

  if (null === $tenant_code) {
    fwrite(STDERR, "missing required `tenant_code` argument\n");
    exit(1);
  }

  if (null === $key) {
    fwrite(STDERR, "missing required `key` argument\n");
    exit(1);
  } else {
    $admin = $resourceManager->fetchRowWithRetries($user_model, 'apikey = ' . $user_model->getAdapter()->quote($key) . ' '
      . 'AND tenant = ' . $user_model->getAdapter()->quote($tenant_code)
    );

    if (null === $admin) {
      fwrite(STDERR, 'There is no user with the key ' . $key . ' in the tenant with the code ' . $tenant_code . "\n");
      exit(1);
    }
    if ($admin->role !== OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR) {
      fwrite(STDERR, "The user with the key " . $key . ' is not the administrator of the tenant with the code ' . $tenant_code . "\n");
      exit(1);
    }
  }
}

function set_property_with_check(&$resource, $property, $val, $isURI = false, $isBOOL = false, $lang = null) {
  if ($isURI) {
    if (isset($val)) {
      if (trim($val) !== '') {
        $resource->setProperty($property, new Uri($val));
      }
    }
    return;
  };

  if ($isBOOL) {
    if (isset($val)) {
      if (strtolower(strtolower($val)) === 'y' || strtolower($val) === "yes") {
        $val = 'true';
      }
      if (strtolower(strtolower($val)) === 'n' || strtolower($val) === "no") {
        $val = 'false';
      }
      $resource->setProperty($property, new Literal($val, null, Literal::TYPE_BOOL));
    } else {
      // default value is 'false'
      $resource->setProperty($property, new Literal('false', null, Literal::TYPE_BOOL));
    }
    return;
  }

  // the property must be a literal
  if ($lang == null) {
    $resource->setProperty($property, new Literal($val));
  } else {
    $resource->setProperty($property, new Literal($val, $lang));
  };
}

function insert_set($tenant_code, $resourceManager, $uri, $uuid, $code, $title, $lang, $license, $description, $concep_base_uri, $oai_base_uri, $allow_oai, $web_page) {
  $count_sets = $resourceManager->countTriples("<".$uri.">", "<".Rdf::TYPE.">", "<".Dcmi::DATASET.">");
  if ($count_sets > 0) {
    fwrite(STDERR, 'There set with uri ' . $uri . "' already exists in the triple store.\n");
    exit(1);
  }
  $count_sets = count($resourceManager->fetchSubjectWithPropertyGiven(OpenSKOS::UUID, "'".$uuid."'", Dcmi::DATASET));
  if ($count_sets > 0) {
    fwrite(STDERR, 'There set with uuid ' . $uuid . "' already exists in the triple store.\n");
    exit(1);
  }
  $count_sets = count($resourceManager->fetchSubjectWithPropertyGiven(OpenSKOS::CODE, "'".$code."'", Dcmi::DATASET));
  if ($count_sets > 0) {
    fwrite(STDERR, 'There set with code ' . $code . "' already exists in the triple store.\n");
    exit(1);
  }
  
  $setResource = new Set();
  $setResource->setUri($uri);
  set_property_with_check($setResource, OpenSkos::CODE, $code);
  set_property_with_check($setResource, OpenSkos::UUID, $uuid);

  $publisherURI = $resourceManager->fetchInstitutionUriByCode($tenant_code);
  if ($publisherURI === null) {
    throw new Exception("Something went terribly worng: the tenant with the code " . $tenant_code . " has not been found in the triple store.");
  } else {
    var_dump("PublisherURI: " . $publisherURI . "\n");
  }
  set_property_with_check($setResource, DcTerms::PUBLISHER, $publisherURI, true);
  set_property_with_check($setResource, DcTerms::TITLE, $title, false, false, $lang);
  if ($description !== NULL) {
    set_property_with_check($setResource, DcTerms::DESCRIPTION, $description);
  }
  set_property_with_check($setResource, OpenSkos::WEBPAGE, $web_page, true);
  set_property_with_check($setResource, DcTerms::LICENSE, $license, true);
  set_property_with_check($setResource, OpenSkos::OAI_BASEURL, $oai_base_uri, true);
  set_property_with_check($setResource, OpenSkos::ALLOW_OAI, $allow_oai, false, true);
  set_property_with_check($setResource, OpenSkos::CONCEPTBASEURI, $concep_base_uri, true);
  $resourceManager->insert($setResource);
  return $uri;
}
