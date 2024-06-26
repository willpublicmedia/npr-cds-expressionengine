<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Fields;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Story_source_definitions
{
    public static $fields = array(
        'channel_entry_source' => array(
            'field_name' => 'channel_entry_source',
            'field_label' => 'Story Source',
            'field_instructions' => 'Import a story from NPR or create a story for export.',
            'field_type' => 'radio',
            'field_list_items' => '',
            'field_settings' => array(
                'value_label_pairs' => array(
                    'local' => 'Local',
                    'npr' => 'NPR',
                ),
            ),
            'field_pre_populate' => 'n',
            'field_pre_field_id' => 0,
            'field_pre_channel_id' => 0,
            'field_order' => 1,
        ),
        'overwrite_local_values' => array(
            'field_name' => 'overwrite_local_values',
            'field_label' => 'Overwrite Local',
            'field_instructions' => 'Overwrite entry using document content from NPR CDS.',
            'field_type' => 'toggle',
            'field_list_items' => '',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => 0,
            'field_pre_channel_id' => 0,
            'field_order' => 1,
            'field_settings' => array(
                'field_default_value' => 0,
            ),
        ),
        'npr_story_id' => array(
            'field_name' => 'npr_story_id',
            'field_label' => 'NPR Story ID',
            'field_instructions' => 'Enter an NPR Document ID as described in https://npr.github.io/content-distribution-service.',
            'field_type' => 'text',
            'field_maxl' => '64',
            'field_list_items' => '',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => 0,
            'field_pre_channel_id' => 0,
            'field_order' => 1,
            'field_settings' => array(
                'field_fmt' => 'none',
                'field_show_fmt' => 'n',
            ),
        ),
        // nprml.php: 185
        'nprone_featured' => array(
            'field_name' => 'nprone_featured',
            'field_label' => 'NPR One Featured',
            'field_instructions' => 'Set as featured story in NPR One.',
            'field_type' => 'toggle',
            'field_list_items' => '',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => 0,
            'field_pre_channel_id' => 0,
            'field_order' => 1,
            'field_settings' => array(
                'field_default_value' => 0,
            ),
        ),
        'publish_to_npr' => array(
            'field_name' => 'publish_to_npr',
            'field_label' => 'Publish to NPR',
            'field_instructions' => 'Enable to publish the story on the NPR CDS.',
            'field_type' => 'toggle',
            'field_list_items' => '',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => 0,
            'field_pre_channel_id' => 0,
            'field_order' => 1,
            'field_settings' => array(
                'field_default_value' => 0,
            ),
        ),
        // nprml.php: 172
        'send_to_one' => array(
            'field_name' => 'send_to_one',
            'field_label' => 'Send to NPR One',
            'field_instructions' => '',
            'field_type' => 'toggle',
            'field_list_items' => '',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => 0,
            'field_pre_channel_id' => 0,
            'field_order' => 1,
            'field_settings' => array(
                'field_default_value' => 0,
            ),
        ),
    );
}
