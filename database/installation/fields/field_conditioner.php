<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Fields;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Field_conditioner
{
    private $field_conditions = [
        'nprone_featured' => [
            'match' => 'all',
            'conditions' => [
                // source matches local
                // publish turnedOn
                'channel_entry_source' => 'local',
                'publish_to_npr' => 'turnedOn',
            ],
        ],
        'overwrite_local_values' => [
            'match' => 'all',
            'conditions' => [
                'channel_entry_source' => 'npr',
            ],
        ],
        'publish_to_npr' => [
            'match' => 'all',
            'conditions' => [
                'channel_entry_source' => 'local',
            ],
        ],
        'send_to_one' => [
            'match' => 'all',
            'conditions' => [
                'channel_entry_source' => 'local',
                'publish_to_npr' => 'turnedOn',
            ],
        ],

    ];

    public function condition_fields(): void
    {
        foreach ($this->field_conditions as $field_name => $rulesets) {
            $field = ee('Model')->get('ChannelField')->filter('field_name', $field_name)->first();

            if ($field === null) {
                continue;
            }

            // make conditional
            // add conditions
            // sync conditional logic

            dd($field_name, $rulesets);
        }
        throw new \Exception('not implemented');
    }
}
