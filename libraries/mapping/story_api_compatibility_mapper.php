<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Story_api_compatibility_mapper
{
    public static function map_cds_to_story(array $cds_data): array
    {
        return $cds_data;
    }
}
