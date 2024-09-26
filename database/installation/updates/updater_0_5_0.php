<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

require_once __DIR__ . '/../fields/story_content_definitions.php';
require_once __DIR__ . '/../../../libraries/utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Story_content_definitions;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Updater_0_5_0
{
    public function update(): void
    {
        $this->add_image_height();
    }

    private function add_image_height(): void
    {
        $height_col_def = $this->load_height_def();
        if (count($height_col_def) <= 0) {
            return;
        }

        // load model
        $model = ee('Model')->get('ChannelField')->filter('field_name', 'npr_images')
            ->with('GridColumns')
            ->first();

        // check model has column
        $col_exists = $model->GridColumns->filter('col_name', $height_col_def['col_name'])->count() === 1;

        if ($col_exists) {
            return;
        }

        // create column
        $height_column = ee('Model')->make('grid:GridColumn', $height_col_def);
        $model->GridColumns->add($height_column);

        $model->validate();
        $model->save();

        // add missing db column
        $this->insert_height_column($model->field_id, $height_column->col_id);

        // reset column order
        $this->reset_image_column_order($model->field_id);
    }

    private function insert_height_column(string | int $image_field_id, string | int $height_col_id): bool
    {
        $image_field_table = 'channel_grid_field_' . $image_field_id;
        $height_col_name = 'col_id_' . $height_col_id;

        $query_result = ee()->db->query("SHOW COLUMNS FROM `exp_" . $image_field_table . "` LIKE '" . $height_col_name . "'")->result_array();
        $height_column_exists = count($query_result) > 0;

        if ($height_column_exists) {
            return true;
        }

        $column_def = [
            $height_col_name => [
                'type' => 'text',
            ],
        ];

        ee()->load->dbforge();
        ee()->dbforge->add_column($image_field_table, $column_def);

        $this->log_message();

        return true;
    }

    private function load_height_def(): array
    {
        // load definitions
        $definitions = Story_content_definitions::$fields;
        $image_def = $definitions['npr_images'];
        $height_col = $image_def['field_settings']['file_grid']['cols']['new_3'];

        // fail if height def not found
        if ($height_col['col_name'] !== 'crop_height') {
            ee('CP/Alert')->makeInline('image-height-fail')->asWarning()
                ->withTitle('NPR CDS update failed')
                ->addToBody('Unable to add image height column. Wrong content definition found.')
                ->defer();

            return [];
        }

        return $height_col;
    }

    private function log_message()
    {
        ee('CP/Alert')->makeInline('npr-db-update')
            ->asAttention()
            ->withTitle("NPR Data Fields Updated")
            ->addToBody("Added height column to NPR Images field.")
            ->defer();
    }

    private function reset_image_column_order(string | int $field_id): void
    {
        $columns = ee('Model')->get('grid:GridColumn')->filter('field_id', $field_id)->all();
        $height_column = $columns->filter('col_name', 'crop_height')->first();

        $width_order = $columns->filter('col_name', 'crop_width')->first()->col_order;
        $height_column->col_order = $width_order + 1;
        $height_column->save();

        foreach ($columns as $column) {
            if ($column->col_order < $height_column->col_order) {
                continue;
            }

            if ($column->col_name == $height_column->col_name) {
                continue;
            }

            $new_order = $column->col_order + 1;
            $column->col_order = $new_order;
            $column->save();
        }
    }
}
