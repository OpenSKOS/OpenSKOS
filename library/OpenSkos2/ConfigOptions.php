<?php

namespace OpenSkos2;

class ConfigOptions
{

    const BACKWARD_COMPATIBLE = false;
    const DEFAULT_AUTHORISATION = false;
    const DEFAULT_DELETION = false;
    const DEFAULT_RELATIONTYPES = false;
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
    
    const BACKEND = 'test';
    const ERROR_LOG = '/data/ValidationErrors.txt';
}
