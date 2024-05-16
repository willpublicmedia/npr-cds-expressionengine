<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../../libraries/utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class Updater_0_3_1
{
    public function update(): bool
    {
        $success = $this->insert_missing_column();
        return $success;
    }

    private function insert_missing_column(): bool
    {
        $utils = new Field_utils();

        $image_field_id = $utils->get_field_id('npr_images');
        $alt_column = ee()->db->select('col_id, col_name')->from('grid_columns')
            ->where('field_id', $image_field_id)
            ->where('col_name', 'alt_text')
            ->get()
            ->result_array();

        $image_field_table = 'channel_grid_field_' . $image_field_id;
        $col_name = 'col_id_' . $alt_column[0]['col_id'];

        $query_result = ee()->db->query("SHOW COLUMNS FROM `exp_" . $image_field_table . "` LIKE '" . $col_name . "'")->result_array();
        $alt_column_exists = count($query_result) > 0;

        if ($alt_column_exists) {
            return true;
        }

        $column_def = [
            $col_name => [
                'type' => 'text',
            ],
        ];

        ee()->load->dbforge();
        ee()->dbforge->add_column($image_field_table, $column_def);

        $this->log_message();

        return true;
    }

    private function log_message()
    {
        ee('CP/Alert')->makeInline('npr-db-update')
            ->asAttention()
            ->withTitle("NPR Data Fields Updated")
            ->addToBody("Added alt text column to NPR Images field.")
            ->defer();
    }
}
