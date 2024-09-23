<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

require_once __DIR__ . '/../libraries/utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class AfterChannelEntrySave extends AbstractRoute
{
    private $field_utils;

    public function update_entry_tags($entry, $values)
    {
        $tag_field = 'keywords';
        $tagger_installed = ee('Addon')->get('tagger')->isInstalled();
        if ($tagger_installed) {
            $this->run_tagger_hooks($entry, $tag_field);
        }
    }

    private function run_tagger_hooks(ChannelEntry $entry, string $field_title): void
    {
        $this->field_utils = new Field_utils();

        $field_id = $this->field_utils->get_field_id($field_title);
        $field_name = $this->field_utils->get_field_name($field_title);
        $data = $entry->{$field_name};

        $field_settings = [
            'entry_id' => $entry->entry_id,
            'field_data' => $data,
            'field_id' => $field_id,
            'field_type' => 'tagger',
        ];

        $actions = [
            'post_save' => $data,
            'display_field' => $entry->{$field_name}
        ];

        foreach ($actions as $method => $params) {
            // handler is unloaded after apply, so must be reloaded
            ee()->api_channel_fields->set_settings($field_name, $field_settings);
            ee()->api_channel_fields->setup_handler('tagger');
            ee()->api_channel_fields->apply('_init', [
                [
                    'content_id' => $entry->entry_id,
                    'field_id' => $field_id,
                ],
            ]);

            ee()->api_channel_fields->apply($method, [$params]);
        }
    }
}
