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

            // create condition set
            $condition_set = ee('Model')->make('FieldConditionSet');
            $condition_set->match = $rulesets['match'];

            // add conditions
            foreach ($rulesets['conditions'] as $order => $rules) {
                $condition = ee('Model')->make('FieldCondition');

                $condition_field_id = $this->field_utils->get_field_id($rules['condition_field_name']);
                $condition->condition_field_id = $condition_field_id;

                $condition->evaluation_rule = $rules['evaluation_rule'];

                if (array_key_exists('value', $rules)) {
                    $condition->value = $rules['value'];
                }

                $condition->order = $order;

                $condition_set->FieldConditions->add($condition);
            }

            $field->FieldConditionSets->add($condition_set);
            $condition_set->save();
            $field->save();

            // sync conditional logic
            $channels = $field->getAllChannels();
            foreach ($channels as $channel) {
                $channel->conditional_sync_required = 'y';
                $channel->save();
            }
        }
    }
}
