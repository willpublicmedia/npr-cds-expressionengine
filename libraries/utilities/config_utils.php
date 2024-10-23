<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Utilities;

require_once __DIR__ . '/../dto/http/api_response.php';
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_response;

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

    public static function log_push_results(string $doc_type, int | string $entry_id, string $doc_id, Api_response $api_response): void
    {
        $table_name = 'npr_cds_push_status';
        $timestamp = time();
        $data = [
            'entry_id' => (int) $entry_id,
            'doc_id' => $doc_id,
            'doc_type' => $doc_type,
            'last_push_date' => $timestamp,
            'status_code' => (int) $api_response->code,
            'messages' => $api_response->messages,
        ];

        $record_exists = ee()->db->where('doc_id', $doc_id)->from($table_name)->count_all_results() > 0 ? true : false;

        if ($record_exists) {
            ee()->db->where('doc_id', $doc_id)->update($table_name, $data);
        } else {
            ee()->db->where('doc_id', $doc_id)->insert($table_name, $data);
        }
    }
}
