<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Utilities;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Config_utils
{
    public static function load_settings(array $setting_fields)
    {
        $settings = ee()->db->select(implode(',', $setting_fields))
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
