<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Configuration;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/npr_constants.php';

use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;

/**
 * Tools for building NPR CDS control panel forms.
 */
class Config_form_builder
{
    private $api_settings_form = [
        [
            // mapped_channels added dynamically
            // npr_image_destination added dynamically
            [
                'title' => 'CDS Token',
                'fields' => [
                    'cds_token' => [
                        'type' => 'text',
                        'value' => '',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Document Prefix',
                'fields' => [
                    'document_prefix' => [
                        'type' => 'text',
                        'value' => '',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Service ID',
                'fields' => [
                    'service_id' => [
                        'type' => 'text',
                        'value' => '',
                    ],
                    'service_name' => [
                        'type' => 'text',
                        'value' => '',
                        'disabled' => true,
                    ],
                ],
            ],
            [
                'title' => 'Pull URL',
                'fields' => [
                    'pull_url' => [
                        'type' => 'radio',
                        'choices' => [
                            Npr_constants::NPR_STAGING_URL => 'Staging',
                            Npr_constants::NPR_PRODUCTION_URL => 'Production',
                        ],
                        'default_value' => 0,
                    ],
                ],
            ],
            [
                'title' => 'Push URL',
                'fields' => [
                    'push_url' => [
                        'type' => 'radio',
                        'choices' => [
                            Npr_constants::NPR_STAGING_URL => 'Staging',
                            Npr_constants::NPR_PRODUCTION_URL => 'Production',
                        ],
                        'default_value' => 0,
                    ],
                ],
            ],
            [
                'title' => 'Theme Uses Featured Image',
                'desc' => 'Enable if a story\'s featured image will be displayed by the page template as a hero image. Disable if the featured image should be displayed inline in the story body.',
                'fields' => [
                    'theme_uses_featured_image' => [
                        'type' => 'toggle',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Build control panel form for API settings.
     *
     * @param  mixed $settings NPR Story API setting values.
     *
     * @return mixed Control panel form.
     */
    public function build_api_settings_form($settings)
    {
        $this->api_settings_form[0][] = $this->get_upload_destinations();
        $this->api_settings_form[0][] = $this->get_mappable_channels();
        $this->add_form_values($settings);
        $form_data = $this->api_settings_form;

        return $form_data;
    }

    private function add_form_values($settings)
    {
        foreach ($this->api_settings_form[0] as &$item) {
            reset($item['fields']);

            foreach ($item['fields'] as $field_name => $definition) {
                $value = $settings[$field_name];
                $item['fields'][$field_name]['value'] = $value;
            }
        }
    }

    private function get_mappable_channels()
    {
        $channels = ee('Model')->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title')
            ->all();

        $mappable = array();
        foreach ($channels as $channel) {
            $mappable[$channel->channel_id] = $channel->channel_title;
        }

        $channel_field = [
            'title' => 'Map channels to CDS',
            'desc' => 'Select channels to use with NPR CDS. You must create a valid channel entry form for each mapped channel.',
            'fields' => [
                'mapped_channels' => [
                    'type' => 'checkbox',
                    'choices' => $mappable,
                    'value' => '',
                ],
            ],
            'required' => false,
        ];

        return $channel_field;
    }

    private function get_upload_destinations()
    {
        $destinations = ee('Model')->get('UploadDestination')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('module_id', 0) // limit selection to user-defined destinations
            ->all();

        $file_choices = [];
        foreach ($destinations as $dest) {
            $file_choices[$dest->id] = $dest->name;
        }

        $upload_field = array(
            'title' => 'Image Upload Destination',
            // should be able to use BASE here, but url swaps session token and uri.
            'desc' => 'Choose an appropriate image gallery from the <a href="/admin.php?cp/files">Files</a> menu.',
            'fields' => [
                'npr_image_destination' => [
                    'type' => 'radio',
                    'choices' => $file_choices,
                    'value' => '',
                ],
            ],
            'required' => true,
        );

        return $upload_field;
    }
}
