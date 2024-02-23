<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../utilities/channel_entry_builder.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Channel_entry_builder;

class Publish_form_mapper
{
    public function map($entry, $values, $story)
    {
        $url_title = $this->generate_url_title($entry, $story->title);
        $data = [
            'teaser' => $story->teaser,
            'title' => $story->title,
            'url_title' => $url_title,
        ];

        $entry_builder = new Channel_entry_builder();
        $objects = $entry_builder->assign_data_to_entry($data, $entry, $values);
        $objects['story'] = $story;

        return $objects;
    }

    private function generate_url_title($entry, $story_title)
    {
        $url_title = $entry->isNew() ?
        (string) ee('Format')->make('Text', $story_title)->urlSlug() :
        $entry->url_title;

        if (empty($url_title)) {
            $url_title = $entry->url_title;
        }

        return $url_title;
    }
}
