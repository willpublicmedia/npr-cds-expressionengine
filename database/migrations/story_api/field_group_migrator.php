<?php

namespace IllinoisPublicMedia\NprCds\Database\Migrations\StoryApi;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../../libraries/utilities/config_utils.php';
require_once __DIR__ . '/../../installation/fields/field_installer.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_installer;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Config_utils;

class Field_group_migrator
{
    private $settings = [
        'mapped_channels' => '',
    ];

    public function __construct()
    {
        $this->settings = Config_utils::load_settings(array_keys($this->settings));
    }

    public function migrate()
    {
        $cds_field_group = ee('Model')->get('ChannelFieldGroup')->filter('group_name', Field_installer::DEFAULT_FIELD_GROUP['group_name'])->first();
        $legacy_field_group = ee('Model')->get('ChannelFieldGroup')->filter('group_name', Field_installer::LEGACY_FIELD_GROUP['group_name'])->first();

        $channel_ids = explode('|', $this->settings['mapped_channels']);
        foreach ($channel_ids as $channel_id) {
            $channel = ee('Model')->get('Channel')->filter('channel_id', $channel_id)->first();

            if (is_null($channel)) {
                // this shouldn't happen
                continue;
            }

            if ($channel->FieldGroups->filter('group_name', Field_installer::DEFAULT_FIELD_GROUP['group_name'])->count() > 0) {
                continue;
            }

            // assign cds field group
            $channel->FieldGroups->add($cds_field_group);

            // remove api field group
            $channel->FieldGroups->remove($legacy_field_group);

            $channel->save();
            $cds_field_group->save();
            $legacy_field_group->save();
        }
    }
}
