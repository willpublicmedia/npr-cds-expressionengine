<?php

namespace IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../installation/fields/field_installer.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_installer;

class Field_group_migrator
{
    private $settings = [
        'mapped_channels' => '',
    ];

    public function __construct()
    {
        $this->settings = $this->load_settings();
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

            // assign cds field group
            $channel->FieldGroups->add($cds_field_group->group_id);

            // remove api field group
            $channel->FieldGroups->remove($legacy_field_group->group_id);

            $channel->save();
        }
    }

    private function load_settings()
    {
        $fields = array_keys($this->settings);

        $settings = ee()->db->select(implode(',', $fields))
            ->limit(1)
            ->get('npr_cds_settings')
            ->result_array();

        if (isset($settings[0])) {
            $settings = $settings[0];
        }

        if (in_array('theme_uses_featured_image', $settings)) {
            $settings['theme_uses_featured_image'] = (bool) $settings['theme_uses_featured_image'];
        }

        return $settings;
    }
}
