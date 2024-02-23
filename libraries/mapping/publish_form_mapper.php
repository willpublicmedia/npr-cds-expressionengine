<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../utilities/channel_entry_builder.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Channel_entry_builder;

class Publish_form_mapper
{
    public function map($entry, $values, $json)
    {
        dump($entry);
        dump($values);
        dump($json);

        $data = [
            'teaser' => $json->teaser,
            'title' => $json->title,
        ];

        dd($data);

        $entry_builder = new Channel_entry_builder();
        $objects = $entry_builder->assign_data_to_entry($data, $entry, $values);
        $objects['json'] = $json;

        return $objects;
    }

    private function map_audio($json)
    {
        return null;
    }
}
