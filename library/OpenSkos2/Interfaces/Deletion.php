<?php

namespace OpenSkos2\Interfaces;

interface Deletion
{

    public function __construct($manager);

    public function canBeDeleted($uri);
}
