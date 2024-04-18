<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Fields;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../../libraries/utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class Field_conditioner
{
    private $field_conditions = [
        'nprone_featured' => [
            'match' => 'all',
            'conditions' => [
                [
                    'condition_field_name' => 'channel_entry_source',
                    'evaluation_rule' => 'matches',
                    'value' => 'local',
                    'order' => 0,
                ],
                [
                    'condition_field_name' => 'publish_to_npr',
                    'evaluation_rule' => 'turnedOn',
                    'order' => 1,
                ],
            ],
        ],
        'overwrite_local_values' => [
            'match' => 'all',
            'conditions' => [
                [
                    'condition_field_name' => 'channel_entry_source',
                    'evaluation_rule' => 'matches',
                    'value' => 'npr',
                    'order' => 0,
                ],
            ],
        ],
        'publish_to_npr' => [
            'match' => 'all',
            'conditions' => [
                [
                    'condition_field_name' => 'channel_entry_source',
                    'evaluation_rule' => 'matches',
                    'value' => 'local',
                    'order' => 0,
                ],
            ],
        ],
        'send_to_one' => [
            'match' => 'all',
            'conditions' => [
                [
                    'condition_field_name' => 'channel_entry_source',
                    'evaluation_rule' => 'matches',
                    'value' => 'local',
                    'order' => 0,
                ],
                [
                    'condition_field_name' => 'publish_to_npr',
                    'evaluation_rule' => 'turnedOn',
                    'order' => 1,
                ],
            ],
        ],

    ];

    private $field_utils;

    public function __construct()
    {
        $this->field_utils = new Field_utils();
    }

    public function condition_fields(): void
    {
        foreach ($this->field_conditions as $field_name => $rulesets) {
            $field = ee('Model')->get('ChannelField')->filter('field_name', $field_name)->first();

            if ($field === null) {
                continue;
            }

            // make conditional
            $field->field_is_conditional = 'y';

            // add conditions
            foreach ($rulesets['conditions'] as $rules) {
                $condition = ee('Model')->make('FieldCondition');
                $condition->condition_field_id = '';
            }
            // sync conditional logic

            dd($field_name, $rules);
        }
        throw new \Exception('not implemented');
    }
}
