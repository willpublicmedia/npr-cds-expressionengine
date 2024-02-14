<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class BeforeChannelEntrySave extends AbstractRoute
{
    private $fields = array(
        // 'audio_files' => null,
        'channel_entry_source' => null,
        // 'npr_images' => null,
        // 'npr_story_id' => null,
        // 'overwrite_local_values' => null,
        // 'publish_to_npr' => null,
    );

    private $settings = [
        'cds_token' => '',
        'document_prefix' => '',
        // 'pull_url' => '',
        // 'push_url' => ''
        'org_id' => null,
        // 'document_prefix' => '',
        // 'theme_uses_featured_image' => false,
        // 'max_image_width' => 1200,
        // 'image_quality' => 75,
        // 'image_format' => 'jpeg',
        'mapped_channels' => '',
        // 'npr_permissions' => '',
        'npr_image_destination' => '',
    ];

    public function __construct()
    {
        $this->settings = $this->load_settings();
        $this->map_model_fields(array_keys($this->fields));
    }

    public function query_cds($entry, $values)
    {
        $source_field = $this->fields['channel_entry_source'];
        $is_external_story = array_key_exists($source_field, $values) ? $this->check_external_story_source($values[$source_field]) : false;
        $overwrite_field = $this->fields['overwrite_local_values'];
        $overwrite = array_key_exists($overwrite_field, $values) ? $values[$overwrite_field] : false;

        // WARNING: check for push stories!
        if (!$is_external_story || !$overwrite) {
            return;
        }

        // $abort = false;

        // $is_mapped_channel = $this->check_mapped_channel($entry->channel_id);
        // if ($is_mapped_channel === false) {
        //     $abort = true;
        // }

        // $has_required_fields = $this->check_required_fields($entry->Channel->FieldGroups);
        // if ($has_required_fields === false) {
        //     $abort = true;
        // }

        // if ($abort === true) {
        //     return;
        // }

        // $id_field = $this->fields['npr_story_id'];
        // $npr_story_id = $values[$id_field];

        // $result = $this->validate_story_id($entry, $values);
        // if ($result instanceof ValidationResult) {
        //     if ($result->isNotValid()) {
        //         return $this->display_error($result);
        //     }
        // }

        // // WARNING: story pull executes loop. Story may be an array.
        // $story = $this->pull_npr_story($npr_story_id);
        // if (!$story) {
        //     return;
        // }

        // if (isset($story[0])) {
        //     $story = $story[0];
        // }

        // $objects = $this->map_story_values($entry, $values, $story);
        // $story = $objects['story'];
        // $values = $objects['values'];
        // $entry = $objects['entry'];

        // // Flip overwrite value
        // $values[$overwrite_field] = false;
        // $entry->{$overwrite_field} = false;

        // $story->ChannelEntry = $entry;
        // $story->save();
    }

    private function check_external_story_source($story_source)
    {
        if (is_null($story_source) || $story_source == 'local') {
            return false;
        }

        return true;
    }

    private function load_settings()
    {
        $settings = ee()->db->select('*')
            ->from('npr_story_api_settings')
            ->get()
            ->result_array();

        if (isset($settings[0])) {
            $settings = $settings[0];
        }

        return $settings;
    }

    private function map_model_fields($field_array)
    {
        $field_names = array();
        foreach ($field_array as $model_field) {
            $field = ee('Model')->get('ChannelField')
                ->filter('field_name', $model_field)
                ->first();

            if ($field === null) {
                continue;
            }

            $field_id = $field->field_id;
            $field_names[$model_field] = "field_id_{$field_id}";
        }

        $this->fields = $field_names;
    }
}
