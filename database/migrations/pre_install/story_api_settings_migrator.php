<?php

namespace IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Story_api_settings_migrator
{
    public function migrate()
    {
        $legacy_settings = ee()->db->get('npr_story_api_settings')->result_array();

        if (count($legacy_settings) <= 0) {
            return;
        }

        $legacy_settings = $legacy_settings[0];
        $data = array(
            'mapped_channels' => $legacy_settings['mapped_channels'],
            'npr_image_destination' => $legacy_settings['npr_image_destination'],
            'org_id' => $legacy_settings['org_id'],
        );

        ee()->db->where('id', 1);
        ee()->db->update('npr_cds_settings', $data);

        return;
    }
}
