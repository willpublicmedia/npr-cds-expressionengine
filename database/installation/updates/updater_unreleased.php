<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

require_once __DIR__ . '/../fields/story_content_definitions.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Story_content_definitions;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Updater_unreleased
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

        $columns = ee('Model')->get('grid:GridColumn')->filter('field_id', $model->field_id)->all();

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
}
