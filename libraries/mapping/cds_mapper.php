<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cds_mapper
{
    public function create_json(array $entry, array $values, string $profile)
    {
        if ($profile !== 'document') {
            throw new \Exception('non-document profiles not supported');
        }

        throw new \Exception('not implemented');
    }
}
