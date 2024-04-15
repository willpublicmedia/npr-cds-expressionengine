<?php

namespace IllinoisPublicMedia\NprCds\Model;

use ExpressionEngine\Model\Channel\Display\DefaultChannelLayout;
use IllinoisPublicMedia\NprCds\Constants;

class DefaultNprStoryLayout extends DefaultChannelLayout
{
    // Documentation: https://docs.expressionengine.com/latest/development/services/model/building-your-own.html
    // You can get this all instances of this model by using:
    // ee('Model')->get('npr_cds:DefaultNprStoryLayout')->all();

    public const LAYOUT_NAME = 'NPR CDS v' . Constants::VERSION;

    private $custom_options_fields = [
        // publish
        'byline' => null,
        'teaser' => null,
        'text' => null,
        'npr_images' => null,
        'audio_files' => null,
        'transcript' => null,
        'videoembed_grid' => null,
        // metadata
        'summary' => null,
        'keywords' => null,
        // options
        'channel_entry_source' => null,
        'npr_story_id' => null,
        'overwrite_local_values' => null,
        'publish_to_npr' => null,
        'send_to_one' => null,
        'nprone_featured' => null,
        // date
        'audio_runby_date' => null,
        'last_modified_date' => null,
        'story_date' => null,
        'pub_date' => null,
    ];

    /**
     * Create a default publish layout for the NPR Story API channel.
     *
     * @return void
     */
    protected function createLayout()
    {
        // prevent channel custom fields from stomping layout custom fields.
        $this->synchronize_custom_fields($this->custom_options_fields);

        $channel = ee('Model')->get('Channel', $this->channel_id)->first();

        $layout = [];

        $layout[] = [
            'id' => 'publish',
            'name' => 'publish',
            'visible' => true,
            'fields' => [
                [
                    'field' => 'title',
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => 'url_title',
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['byline'],
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['teaser'],
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['text'],
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['npr_images'],
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['audio_files'],
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['transcript'],
                    'visible' => true,
                    'collapsed' => false,
                ],
                [
                    'field' => $this->custom_options_fields['videoembed_grid'],
                    'visible' => true,
                    'collapsed' => false,
                ],
            ],
        ];

        // Metadata Tab ------------------------------------------------------------
        $metadata_fields = [
            [
                'field' => $this->custom_options_fields['keywords'],
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['keywords'],
                'visible' => true,
                'collapsed' => false,
            ],
        ];

        $layout[] = [
            'id' => 'metadata',
            'name' => 'metadata',
            'visible' => true,
            'fields' => $metadata_fields,
        ];

        // Options Tab ---------------------------------------------------------

        $option_fields = [
            [
                'field' => 'sticky',
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => 'status',
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['channel_entry_source'],
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['npr_story_id'],
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['overwrite_local_values'],
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['publish_to_npr'],
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['send_to_one'],
                'visible' => true,
                'collapsed' => true,
            ],
            [
                'field' => $this->custom_options_fields['nprone_featured'],
                'visible' => true,
                'collapsed' => true,
            ],
            [
                'field' => 'channel_id',
                'visible' => true,
                'collapsed' => true,
            ],
            [
                'field' => 'author_id',
                'visible' => true,
                'collapsed' => true,
            ],
        ];

        if (bool_config_item('enable_comments') && $channel->comment_system_enabled) {
            $option_fields[] = [
                'field' => 'allow_comments',
                'visible' => true,
                'collapsed' => true,
            ];
        }

        $layout[] = [
            'id' => 'options',
            'name' => 'options',
            'visible' => true,
            'fields' => $option_fields,
        ];

        // Date Tab ------------------------------------------------------------

        $date_fields = [
            [
                'field' => 'entry_date',
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => 'expiration_date',
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => 'audio_runby_date',
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['pub_date'],
                'visible' => true,
                'collapsed' => false,
            ],
            [
                'field' => $this->custom_options_fields['last_modified_date'],
                'visible' => true,
                'collapsed' => true,
            ],
            [
                'field' => $this->custom_options_fields['story_date'],
                'visible' => true,
                'collapsed' => true,
            ],
            [
                'field' => $this->custom_options_fields['audio_runby_date'],
                'visible' => true,
                'collapsed' => true,
            ],
        ];

        if (bool_config_item('enable_comments') && $channel->comment_system_enabled) {
            $date_fields[] = [
                'field' => 'comment_expiration_date',
                'visible' => true,
                'collapsed' => false,
            ];
        }

        $layout[] = [
            'id' => 'date',
            'name' => 'date',
            'visible' => true,
            'fields' => $date_fields,
        ];

        // Category Tab --------------------------------------------------------

        $cat_groups = ee('Model')->get('CategoryGroup')
            ->filter('group_id', 'IN', explode('|', $channel->cat_group))
            ->all();

        $category_group_fields = [];
        foreach ($cat_groups as $cat_group) {
            $category_group_fields[] = [
                'field' => 'categories[cat_group_id_' . $cat_group->getId() . ']',
                'visible' => true,
                'collapsed' => false,
            ];
        }

        $layout[] = [
            'id' => 'categories',
            'name' => 'categories',
            'visible' => true,
            'fields' => $category_group_fields,
        ];

        // -- End tab definitions --

        if ($this->channel_id) {
            // Here comes the ugly! @TODO don't do this
            ee()->legacy_api->instantiate('channel_fields');

            $module_tabs = ee()->api_channel_fields->get_module_fields(
                $this->channel_id,
                $this->entry_id
            );
            $module_tabs = $module_tabs ?: [];

            foreach ($module_tabs as $tab_id => $fields) {
                $tab = [
                    'id' => $tab_id,
                    'name' => $tab_id,
                    'visible' => true,
                    'fields' => [],
                ];

                foreach ($fields as $key => $field) {
                    $tab['fields'][] = [
                        'field' => $field['field_id'],
                        'visible' => true,
                        'collapsed' => (array_key_exists('field_is_hidden', $field) && $field['field_is_hidden'] === 'y') ? true : false,
                    ];
                }

                $layout[] = $tab;
            }
        }

        if ($channel->enable_versioning) {
            $layout[] = [
                'id' => 'revisions',
                'name' => 'revisions',
                'visible' => true,
                'fields' => [
                    [
                        'field' => 'versioning_enabled',
                        'visible' => true,
                        'collapsed' => false,
                    ],
                    [
                        'field' => 'revisions',
                        'visible' => true,
                        'collapsed' => false,
                    ],
                ],
            ];
        }

        return $layout;
    }

    private function synchronize_custom_fields(&$fields)
    {
        foreach ($fields as $key => $value) {
            $model = ee('Model')->get('ChannelField')->filter('field_name', $key)->first();

            $value = "field_id_{$model->field_id}";

            $fields[$key] = $value;
        }
    }
}
