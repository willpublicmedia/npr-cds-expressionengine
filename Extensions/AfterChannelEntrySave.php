<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

require_once __DIR__ . '/../libraries/utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class AfterChannelEntrySave extends AbstractRoute
{
    private $field_utils;

    private $fields = array(
        'channel_entry_source' => null,
    );

    public function __construct()
    {
        $this->map_model_fields(array_keys($this->fields));
    }

    public function update_entry_tags($entry, $values)
    {
        // check whether pulled story
        $source_field = $this->fields['channel_entry_source'];
        $is_external_story = array_key_exists($source_field, $values) ? $this->check_external_story_source($values[$source_field]) : false;

        if (!$is_external_story) {
            return;
        }

        $tag_field = 'keywords';
        $tagger_installed = ee('Addon')->get('tagger')->isInstalled();
        if ($tagger_installed) {
            $this->run_tagger_hooks($entry, $tag_field);
        }
    }

    private function check_external_story_source($story_source)
    {
        if (is_null($story_source) || $story_source == 'local') {
            return false;
        }

        return true;
    }

    private function map_model_fields($field_array)
    {
        $field_utils = new Field_utils();
        $field_names = array();
        foreach ($field_array as $model_field) {
            $field_names[$model_field] = $field_utils->get_field_name($model_field);
        }

        $this->fields = $field_names;
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
            // 'display_field' => $data
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
