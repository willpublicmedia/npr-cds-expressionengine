<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

require_once __DIR__ . '/../utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Story_api_compatibility_mapper
{
    private Field_utils $field_utils;

    public function __construct()
    {
        $this->field_utils = new Field_utils();
    }

    public function map_cds_to_story(array $cds_data): array
    {
        $story_api_compatible_data = [];

        foreach ($cds_data as $key => $value) {
            switch ($key) {
                case ($key === 'audio'):
                    $audio = $this->map_audio_to_api($value);
                    $story_api_compatible_data['audio_files'] = $audio;
                    if (array_key_exists('transcripts', $audio)) {
                        $story_api_compatible_data['transcript'] = $audio['transcripts'][0];
                    }
                    break;
                case ($key === 'bylines'):
                    $story_api_compatible_data['byline'] = implode(', ', $value);
                    break;
                case ($key === 'images'):
                    $story_api_compatible_data['images'] = $this->map_images($value);
                    break;
                case ($key === 'recommendUntilDateTime'):
                    $story_api_compatible_data['audio_runby_date'] = $value;
                    break;
                case ($key === 'transcripts'):
                    $story_api_compatible_data['transcript'] = $value[0];
                    break;
                default:
                    $story_api_compatible_data[$key] = $value;
                    break;
            }
        }
        return $story_api_compatible_data;
    }

    public function map_audio_to_api(array $cds_audio): array
    {
        $api_audio = [];

        /* get column names */
        $field_id = $this->field_utils->get_field_id('audio_files');
        $grid_column_names = $this->field_utils->get_grid_column_names($field_id);

        $count = 1;
        foreach ($cds_audio as $item => $data) {
            $enclosures = $data['enclosures'];
            $stream = [
                // 'primary' => in_array('primary', $enclosures[0]['rels']),
                'filesize' => $enclosures[0]['filesize'],
                'format' => $enclosures[0]['type'],
                'url' => $enclosures[0]['url'],
            ];

            $permissions = [
                'stream' => $data['streamable'], 'download' => $data['downloadable'], 'embed' => $data['embeddable'],
            ];
            $allowed = $this->parse_audio_permissions($permissions);

            // should be row_id_x if row exists, but this doesn't seem to duplicate entries.
            $row_name = "new_row_$count";

            $audio = [
                // $grid_column_names['audio_type'] => $stream['primary'], // col_id => value?
                $grid_column_names['audio_duration'] => ltrim(gmdate('H:i:s', $data['duration']), "00:"),
                // $grid_column_names['audio_description'] => $data['description'],
                // $grid_column_names['audio_filesize'] => $stream['filesize'],
                // $grid_column_names['audio_format'] => $stream['format'],
                $grid_column_names['audio_url'] => $stream['url'],
                //     $grid_column_names['audio_rights'] => $model->rights,
                $grid_column_names['audio_permissions'] => $allowed,
                $grid_column_names['audio_title'] => $data['title'],
                //     $grid_column_names['audio_region'] => $model->region,
                //     $grid_column_names['audio_rightsholder'] => $model->rightsholder,
            ];

            // this is a cheat, not a valid column
            if (!is_null($data['transcript'])) {
                $api_audio['transcripts'][] = $data['transcript'];
            }

            $api_audio['rows'][$row_name] = $audio;
            $count++;
        }

        return $api_audio;
    }

    private function map_image_credit(array $data): string
    {
        $credit = "{$data['producer']}/{$data['provider']}";

        if (!is_null($data['copyright'])) {
            $credit = "Copyright {$data['copyright']} {$credit}";
        }

        return $credit;
    }

    private function map_image_crops(array $enclosures): array
    {
        $crop_array = [];
        foreach ($enclosures as $enclosure) {
            dd($enclosure);
            // $primary = in_array('primary', $data['rels']);

            //     // we only care about the largest image size.
            //     // caution: watch for <image primary='false' /> edge case.
            //     if (!$primary) {
            //         continue;
            //     }

            //     $file_segments = $this->sideload_file($model);
            //     $file = $this->file_manager_compatibility_mode === true ?
            //         $file_segments['dir'] . $file_segments['file']->file_name :
            //         '{' . $file_segments['dir'] . ':' . $file_segments['file']->file_id . ':url}';

            $crop_array[] = [
                // 'file' => $file,
                'type' => $enclosure['rels'],
                'src' => $enclosure['href'],
                'height' => array_key_exists('height', $enclosure) ? $enclosure['height'] : '',
                'width' => array_key_exists('width', $enclosure) ? $enclosure['width'] : '',
            ];
        }

        return $crop_array;
    }

    private function map_images(array $image_data): array
    {
        $image_array = [];

        $field_id = $this->field_utils->get_field_id('npr_images');
        $grid_column_names = $this->field_utils->get_grid_column_names($field_id);

        $count = 1;
        foreach ($image_data as $id => $data) {
            $credit = $this->map_image_credit($data);
            $primary = in_array('primary', $data['rels']);

            $crops = $this->map_image_crops($data['enclosures']);

            foreach ($crops as $crop) {
                //         // we only care about the largest image size.
                //         if (!$crop['primary']) {
                //             continue;
                //         }

                // should be row_id_x if row exists, but this doesn't seem to duplicate entries.
                $row_name = "new_row_$count";

                $image = array(
                    //             $grid_column_names['file'] => $crop['file'],
                    //             $grid_column_names['crop_type'] => $crop['type'],
                    //             $grid_column_names['crop_src'] => $crop['src'],
                    //             $grid_column_names['crop_width'] => $crop['width'],
                    $grid_column_names['crop_primary'] => $primary,
                    $grid_column_names['crop_caption'] => $data['caption'],
                    //             $grid_column_names['crop_provider_url'] => $model->providerUrl,
                    $grid_column_names['crop_credit'] => $credit,
                );

                $image_array['rows'][$row_name] = $image;
                $count++;
            }
        }

        return $image_array;
    }

    private function parse_audio_permissions(array $permissions): string
    {
        $allowed = array_keys($permissions, 'true');
        return implode(', ', $allowed);

    }
}
