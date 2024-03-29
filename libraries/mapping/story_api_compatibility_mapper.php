<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

require_once __DIR__ . '/../utilities/field_utils.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

// see /ee/ExpressionEngine/Config/constants.php
if (!defined('FILE_WRITE_MODE')) {
    define('FILE_WRITE_MODE', 0666);
}

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Story_api_compatibility_mapper
{
    private Field_utils $field_utils;

    private $file_manager_compatibility_mode = true;

    private $settings = [
        'npr_image_destination' => '',
    ];

    public function __construct()
    {
        $this->field_utils = new Field_utils();

        if (APP_VER >= 7) {
            $compatibility_mode = ee()->config->item('file_manager_compatibility_mode');
            if ($compatibility_mode === 'n') {
                $this->file_manager_compatibility_mode = false;
            }
        }

        $this->settings = $this->load_settings();
    }

    public function map_cds_to_story(array $cds_data): array
    {
        $story_api_compatible_data = [];

        foreach ($cds_data as $key => $value) {
            switch ($key) {
                case ($key === 'audio'):
                    $audio = is_null($value) ? [] : $this->map_audio_to_api($value);
                    $story_api_compatible_data['audio_files'] = $audio;
                    if (array_key_exists('transcripts', $audio)) {
                        $story_api_compatible_data['transcript'] = $audio['transcripts'][0];
                    }
                    break;
                case ($key === 'bylines'):
                    $story_api_compatible_data['byline'] = is_null($value) ? '' : implode(', ', $value);
                    break;
                case ($key) === 'collections':
                    $story_api_compatible_data['keywords'] = is_null($value) ? [] : $this->map_collections($value);
                    break;
                case ($key === 'corrections'):
                    $story_api_compatible_data['corrections'] = is_null($value) ? [] : $this->map_corrections($value);
                    break;
                case ($key === 'images'):
                    $story_api_compatible_data['npr_images'] = is_null($value) ? [] : $this->map_images($value);
                    break;
                case ($key === 'recommendUntilDateTime'):
                    $story_api_compatible_data['audio_runby_date'] = $value;
                    break;
                case ($key === 'transcripts'):
                    $story_api_compatible_data['transcript'] = is_null($value) ? '' : $value[0];
                    break;
                default:
                    if (!is_null($value)) {
                        $story_api_compatible_data[$key] = $value;
                    }
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

    private function load_settings()
    {
        $fields = array_keys($this->settings);

        $settings = ee()->db->select(implode(',', $fields))
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

    private function map_collections(array $data): string
    {
        $titles = [];
        foreach ($data as $collection) {
            $titles[] = $collection['title'];
        }

        $keywords = implode(',', $titles);

        return $keywords;
    }

    private function map_corrections(array $data): array
    {
        $corrections = [];

        foreach ($data as $item) {
            $correction = [
                'correction_date' => $item['date'],
                'correction_text' => $item['text'],
            ];

            $corrections[] = $correction;
        }

        return $corrections;
    }

    private function map_image_credit(array $data): string
    {
        $credit = "{$data['producer']}/{$data['provider']}";

        if (!is_null($data['copyright'])) {
            $credit = "Copyright {$data['copyright']} {$credit}";
        }

        return $credit;
    }

    private function map_image_crops(array $image_data, string $description, string $credit): array
    {
        $enclosures = $image_data['enclosures'];
        $crop_array = [];
        foreach ($enclosures as $enclosure) {
            //     // we only care about the largest image size.
            //     // caution: watch for <image primary='false' /> edge case.
            //     if (!$primary) {
            //         continue;
            //     }

            $file_segments = $this->sideload_file($enclosure, $description, $credit);
            $file = $this->file_manager_compatibility_mode === true ?
            $file_segments['dir'] . $file_segments['file']->file_name :
            '{' . $file_segments['dir'] . ':' . $file_segments['file']->file_id . ':url}';

            $crop_array[] = [
                'file' => $file,
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

            $crops = $this->map_image_crops($data, $data['caption'], $credit);

            foreach ($crops as $crop) {
                //         // we only care about the largest image size.
                //         if (!$crop['primary']) {
                //             continue;
                //         }

                // should be row_id_x if row exists, but this doesn't seem to duplicate entries.
                $row_name = "new_row_$count";

                $image = array(
                    $grid_column_names['file'] => $crop['file'],
                    $grid_column_names['crop_type'] => $crop['type'][0],
                    $grid_column_names['crop_src'] => $crop['src'],
                    $grid_column_names['crop_width'] => $crop['width'],
                    $grid_column_names['crop_primary'] => $primary,
                    $grid_column_names['crop_caption'] => $data['caption'],
                    $grid_column_names['crop_provider_url'] => $data['providerLink'],
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

    private function sideload_file(array $data, string $description, string $credit, $field = 'userfile')
    {
        // rename file if it'll be problematic.
        $filename = $this->strip_sideloaded_query_strings($data['href']);

        // see if file has already been uploaded
        $file = ee('Model')->get('File')
            ->filter('upload_location_id', $this->settings['npr_image_destination'])
            ->filter('file_name', $filename)
            ->first();

        if ($file != null) {
            $dir = $this->file_manager_compatibility_mode ?
            '{filedir_' . $this->settings['npr_image_destination'] . '}' :
            'file';

            return array(
                'dir' => $dir,
                'file' => $file,
            );
        }

        $destination = ee('Model')->get('UploadDestination')
            ->filter('id', $this->settings['npr_image_destination'])
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        ee()->load->library('upload', array('upload_path' => $destination->server_path));
        // upload path should be set by library loader, but it's not.
        ee()->upload->set_upload_path($destination->server_path);

        $raw = file_get_contents($data['href']);

        if (ee()->upload->raw_upload($filename, $raw) === false) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('upload_filedata_error'))
                ->addToBody('')
                ->now();

            return false;
        }

        // from filemanager
        $upload_data = ee()->upload->data();

        // (try to) Set proper permissions
        @chmod($upload_data['full_path'], FILE_WRITE_MODE);
        // --------------------------------------------------------------------
        // Add file the database

        ee()->load->library('filemanager', array('upload_path' => dirname($destination->server_path)));
        $thumb_info = ee()->filemanager->get_thumb($upload_data['file_name'], $destination->id);

        // Build list of information to save and return
        $file_data = [
            'upload_location_id' => $destination->id,
            'site_id' => ee()->config->item('site_id'),

            'file_name' => $upload_data['file_name'],
            'orig_name' => $filename, // name before any upload library processing
            'file_data_orig_name' => $upload_data['orig_name'], // name after upload lib but before duplicate checks

            'is_image' => $upload_data['is_image'],
            'mime_type' => $upload_data['file_type'],

            'file_thumb' => $thumb_info['thumb'],
            'thumb_class' => $thumb_info['thumb_class'],

            'modified_by_member_id' => ee()->session->userdata('member_id'),
            'uploaded_by_member_id' => ee()->session->userdata('member_id'),

            'file_size' => $upload_data['file_size'],
            'file_height' => $upload_data['image_height'],
            'file_width' => $upload_data['image_width'],
            'file_hw_original' => $upload_data['image_height'] . ' ' . $upload_data['image_width'],
            'max_width' => $destination->max_width,
            'max_height' => $destination->max_height,
        ];

        $file_data['title'] = $filename;
        $file_data['description'] = $description;
        $file_data['credit'] = $credit;

        $saved = ee()->filemanager->save_file($upload_data['full_path'], $destination->id, $upload_data);

        if ($saved['status'] === false) {
            return;
        }

        $file = ee('Model')->get('File')
            ->filter('file_id', $saved['file_id'])
            ->limit(1)
            ->first();

        $file->title = $file_data['title'];
        // $file->description = $file_data['description'];
        // $file->credit = $file_data['credit'];
        $file->save();

        $dir = $this->file_manager_compatibility_mode === true ?
        '{filedir_' . $destination->id . '}' :
        'file';

        $results = [
            'dir' => $dir,
            'file' => $file,
        ];

        return $results;
    }

    private function strip_sideloaded_query_strings($url)
    {
        $url_data = parse_url($url);
        $filename = basename($url_data['path']);

        if (!array_key_exists('query', $url_data)) {
            return $filename;
        }

        $path_data = pathinfo($filename);
        $filename = "{$path_data['filename']}-{$url_data['query']}.{$path_data['extension']}";

        ee()->load->library('upload');
        $filename = ee()->upload->clean_file_name($filename);

        return $filename;
    }
}
