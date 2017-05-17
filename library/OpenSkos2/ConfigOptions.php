<?php

namespace OpenSkos2;

class ConfigOptions
{

    const BACKWARD_COMPATIBLE = true;
    const DEFAULT_AUTHORISATION = true;
    const DEFAULT_DELETION = true;
    const DEFAULT_RELATIONTYPES = true;
    const MAXIMAL_ROWS = 500;
    const MAXIMAL_TIME_LIMIT = 120;
    const NORMAL_TIME_LIMIT = 30;
    
    const ROOT = 'root';
    const ADMINISTRATOR = 'administrator';
    const EDITOR = 'editor';
    const USER = 'user';
    const GUEST = 'guest';
    
    const ALLOWED_CONCEPTS_FOR_OTHER_TENANT_SCHEMES=true;
    const EPIC_IS_ON = true;
    
    const BACKEND = 'clavas';
    const ERROR_LOG = '/data/ValidationErrors.txt';
}
