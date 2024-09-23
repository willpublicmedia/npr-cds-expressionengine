<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

require_once __DIR__ . '/../libraries/utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class AfterChannelEntrySave extends AbstractRoute
{
    private $field_utils;

    public function process($entry, $values)
    {
        $this->field_utils = new Field_utils();

        $tagger_installed = ee('Addon')->get('tagger')->isInstalled();

        if ($tagger_installed) {
            // throw new \Exception('see legacy/libraries/api_channel_entries #1287');
            $field_id = $this->field_utils->get_field_id('keywords');
            $field_name = $this->field_utils->get_field_name('keywords');
            $data = $entry->{$field_name};

            $field_settings = [
                'entry_id' => $entry->entry_id,
                'field_data' => $data,
                'field_id' => $field_id,
                'field_type' => 'tagger',
            ];

            // load handler
            ee()->api_channel_fields->set_settings($field_name, $field_settings);
            ee()->api_channel_fields->setup_handler('tagger');
            ee()->api_channel_fields->apply('_init', array(array(
                'content_id' => $entry->entry_id,
                'field_id' => $field_id,
            )));

            // exec post-save
            // missing field id
            ee()->api_channel_fields->apply('post_save', [$data]);

            // reload handler
            ee()->api_channel_fields->set_settings($field_name, $field_settings);
            ee()->api_channel_fields->setup_handler('tagger');
            ee()->api_channel_fields->apply('_init', array(array(
                'content_id' => $entry->entry_id,
                'field_id' => $field_id,
            )));

            // exec display
            ee()->api_channel_fields->apply('display_field', [$entry->{$field_name}]);
        }
    }
}
