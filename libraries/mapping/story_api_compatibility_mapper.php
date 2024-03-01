<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Story_api_compatibility_mapper
{
    public static function map_cds_to_story(array $cds_data): array
    {
        $story_api_compatible_data = [];

        foreach ($cds_data as $key => $value) {
            switch ($key) {
                case ($key === 'bylines'):
                    $story_api_compatible_data['byline'] = implode(', ', $value);
                    break;
                default:
                    $story_api_compatible_data[$key] = $value;
                    break;
            }
        }
        return $story_api_compatible_data;
    }
}
