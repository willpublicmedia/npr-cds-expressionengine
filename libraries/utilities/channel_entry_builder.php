<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Utilities;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class Channel_entry_builder
{
    private $field_utils;

    public function __construct()
    {
        ee()->load->model('grid_model');
        $this->field_utils = new Field_utils();
    }

    /**
     * @param data Array of channel entry field name : field value pairs.
     * @param entry A ChannelEntry model.
     * @param values Array of input post data.
     */
    public function assign_data_to_entry($data, $entry, $values)
    {
        foreach ($data as $field => $value) {
            $name = $field;
            if ($field !== 'title' && $field !== 'url_title') {
                $field = $this->field_utils->get_field_name($field);
            }

            if ($name === 'keywords' && array_key_exists('tags', $value)) {
                $value = $this->save_keywords($value, $name, $entry->entry_id);
            }

            $entry->{$field} = $value;
            $values[$field] = $value;

            if ($this->field_is_grid($name)) {
                // Grid_ft->post_save stomps data values with cache.
                ee()->session->set_cache('Grid_ft', $field, $value);
            }
        }

        $objects = array(
            'entry' => $entry,
            'values' => $values,
        );

        return $objects;
    }

    /**
     * @param name Channel name.
     * @return bool
     */
    public function field_is_grid($name)
    {
        $type = ee('Model')->get('ChannelField')
            ->filter('field_name', $name)
            ->fields('field_type')
            ->first()
            ->field_type ?? '';

        $is_grid = ($type === 'grid' || $type === 'file_grid');
        return $is_grid;
    }

    private function save_keywords(array $data, string $field_title, string | int | null $entry_id): string
    {
        $tagger_installed = ee('Addon')->get('tagger')->isInstalled();

        $value = '';
        if ($tagger_installed) {
            // throw new \Exception('see legacy/libraries/api_channel_entries #1287');
            $field_id = $this->field_utils->get_field_id($field_title);
            $field_name = $this->field_utils->get_field_name($field_title);

            ee()->api_channel_fields->settings[$field_name]['entry_id'] = $entry_id;
            ee()->api_channel_fields->settings[$field_name]['field_id'] = $field_id;

            ee()->api_channel_fields->setup_handler('tagger');
            ee()->api_channel_fields->apply('_init', array(array(
                'content_id' => $entry_id,
            )));

            $value = ee()->api_channel_fields->apply('save', array($data));
            $_POST[$field_name] = $data; // we shouldn't have to do this, but Tagger_ft->display_field uses post.
        } else {
            $value = implode(',', array_values($data['tags']));
        }

        return $value;
    }
}
