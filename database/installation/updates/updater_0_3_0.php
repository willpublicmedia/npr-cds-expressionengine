<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Updater_0_3_0
{

    private $alt_text_def = [
        'col_type' => 'text',
        'col_label' => 'Alt Text',
        'col_name' => 'alt_text',
        'col_instructions' => "The alt text for the image, describing the image contents for screen readers.",
        'col_required' => 'n',
        'col_search' => 'n',
        'content_type' => 'channel',
        'col_order' => 5,
        'col_settings' => [
            'field_maxl' => '',
            'field_fmt' => 'none',
            'field_text_direction' => 'ltr',
            'field_content_type' => 'all',
        ],
    ];

    public function update(): bool
    {
        $success = $this->add_alt_text();
        return $success;
    }

    private function add_alt_text(): bool
    {
        $model = ee('Model')->get('ChannelField')->filter('field_name', 'npr_images')
            ->with('GridColumns')
            ->first();

        if ($model === null) {
            ee('CP/Alert')->makeInline('npr-db-update')
                ->asError()
                ->withTitle("NPR Data Fields Update Failure")
                ->addToBody("Unable to add alt text to NPR Image field.")
                ->defer();
        }

        $alt_text_column = ee('Model')->make('grid:GridColumn', $this->alt_text_def);
        $model->GridColumns->add($alt_text_column);
        $model->validate();
        $model->save();

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
