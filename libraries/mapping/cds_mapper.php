<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../utilities/config_utils.php';
require_once __DIR__ . '/../configuration/npr_constants.php';
require_once __DIR__ . '/../utilities/field_utils.php';
require_once __DIR__ . '/../utilities/mp3file.php';

use DateInterval;
use DOMDocument;
use ExpressionEngine\Model\Channel\ChannelEntry;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Config_utils;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\MP3File;
use \stdClass;

class Cds_mapper
{
    private $field_utils;

    private $file_manager_compatibility_mode = true;

    private $settings = [
        'document_prefix' => '',
        'pull_url' => '',
        'push_url' => '',
        'service_id' => null,
        'theme_uses_featured_image' => false,
        // 'max_image_width' => 1200,
        // 'image_quality' => 75,
        // 'image_format' => 'jpeg',
        'mapped_channels' => '',
        // 'npr_permissions' => '',
        'npr_image_destination' => '',
    ];

    public function __construct()
    {
        $this->field_utils = new Field_utils();
        $this->settings = Config_utils::load_settings(array_keys($this->settings));

        if (APP_VER >= 7) {
            $compatibility_mode = ee()->config->item('file_manager_compatibility_mode');
            if ($compatibility_mode === 'n') {
                $this->file_manager_compatibility_mode = false;
            }
        }

        // load helpers for shortcode expansion.
        ee()->load->helper('url');
    }

    public function create_json(ChannelEntry $entry, array $values, string $profile)
    {
        if ($profile !== 'document') {
            throw new \Exception('non-document profiles not supported');
        }

        $cds_version = Npr_constants::NPR_CDS_VERSION;
        $story = new stdClass;

        $story->title = $entry->title;

        $cds_id = $this->create_story_id($entry);
        $story->id = $cds_id;

        $org_id = $this->settings['service_id'];
        $npr_org = new stdClass;
        $npr_org->href = 'https://organization.api.npr.org/v4/services/' . $org_id;

        $webPage = new stdClass;
        $webPage->href = $this->construct_canonical_url($entry->Channel->preview_url, $entry->url_title);
        $webPage->rels = ['canonical'];

        $cds_count = 0;

        $story->brandings = [$npr_org];
        $story->owners = [$npr_org];
        $story->authorizedOrgServiceIds = [$org_id];
        $story->webPages = [$webPage];
        $story->layout = [];
        $story->assets = new stdClass;
        $story->collections = [];
        $story->profiles = $this->get_npr_cds_base_profiles($cds_version);
        $story->bylines = [];

        $edit_date = date('c', $entry->edit_date);
        $story->publishDateTime = $edit_date;
        $story->editorialLastModifiedDateTime = $edit_date;

        $story->teaser = $this->get_text($entry, 'teaser', true);
        $content = $this->get_text($entry, 'text', false);

        $bylines = $this->get_bylines($entry);
        foreach ($bylines as $byline) {
            $byl = new stdClass;
            $byl_asset = new stdClass;
            $byline_id = $cds_id . '-' . $cds_count;
            $byl->id = $byline_id;
            $byl->name = $byline;
            $byl->profiles = $this->get_npr_cds_asset_profile('byline');
            $story->assets->{$byline_id} = $byl;
            $byl_asset->href = '#/assets/' . $byline_id;
            $story->bylines[] = $byl_asset;
            $cds_count++;
        }

        $corrections = $this->get_corrections($entry);
        if (!empty($corrections)) {
            $story->corrections = [];
            $correction_profile = new stdClass;
            $correction_profile->href = '/' . $cds_version . '/profiles/has-corrections';
            $correction_profile->rels = ['interface'];
            $story->profiles[] = $correction_profile;
        }

        foreach ($corrections as $correction) {
            $cor = new stdClass;
            $cor_asset = new stdClass;
            $cor_id = $cds_id . '-' . $cds_count;
            $cor->id = $cor_id;
            $cor->text = $correction['correction_text'];

            $cor->dateTime = (new \DateTime($correction['correction_date']))->format(\DateTime::ATOM);
            $cor->profiles = $this->get_npr_cds_asset_profile('correction');
            $story->assets->{$cor_id} = $cor;
            $cor_asset->href = '#/assets/' . $cor_id;
            $story->corrections[] = $cor_asset;
            $cds_count++;
        }

        $send_to_one = $entry->{$this->field_utils->get_field_name('send_to_one')} === 1 ? true : false;
        if ($send_to_one) {
            $collect = new stdClass;
            $collect->rels = ['collection'];
            $collect->href = '/' . $cds_version . '/documents/319418027';
            $story->collections[] = $collect;
        }

        $nprone_featured = $entry->{$this->field_utils->get_field_name('nprone_featured')} === 1 ? true : false;
        if ($nprone_featured) {
            $collect = new stdClass;
            $collect->rels = ['collection'];
            $collect->href = '/' . $cds_version . '/documents/500549367';
            $story->collections[] = $collect;
        }

        // NPR One audio run-by date
        // if expiry date is not set, returns publication date plus 7 days
        $story->recommendUntilDateTime = $this->get_post_expiry_datetime($entry);

        // Parse through the paragraphs, add references to layout array, and paragraph text to assets
        $parts = array_filter(
            array_map('trim', preg_split("/<\/?p>/", $content))
        );

        foreach ($parts as $part) {
            $para = new stdClass;
            $para_asset = new stdClass;
            $para_id = $cds_id . '-' . $cds_count;
            $para->id = $para_id;

            $para_type = 'text';
            if (preg_match('/^<(figure|div)/', $part)) {
                $para_type = 'html';
            }
            if ($para_type == 'html') {
                $para->profiles = $this->get_npr_cds_asset_profile($para_type . '-block');
            } else {
                $para->profiles = $this->get_npr_cds_asset_profile($para_type);
            }
            $para->{$para_type} = $part;
            $story->assets->{$para_id} = $para;
            $para_asset->href = '#/assets/' . $para_id;
            $story->layout[] = $para_asset;
            $cds_count++;
        }

        /*
         * Attach images to the post
         */

        // see story api nprml_mapper.php #667
        $images = $this->get_media($entry, 'npr_images');
        $image_credits = $this->process_image_credits($images);

        if (!empty($images)) {
            $story->images = [];
            $image_profile = new stdClass;
            $image_profile->href = '/' . $cds_version . '/profiles/has-images';
            $image_profile->rels = ['interface'];
            $story->profiles[] = $image_profile;
        }

        foreach ($images as $image) {
            $manipulations = $this->get_manipulations($image);
            $crops = $this->create_image_crops($manipulations);

            // If the image field for distribute is set and polarity then send it.
            // All kinds of other math when polarity is negative or the field isn't set.
            $image_type = [];
            if ($image['crop_primary'] === 1) {
                $image_type = ['primary', 'promo-image-standard'];
            }

            // Is the image in the content?  If so, tell the API with a flag that CorePublisher knows.
            $in_body = $this->check_image_in_body($image, $content);
            $image_meta = ee('Model')->get('File')->filter('file_id', $image['file_id'])->first();

            $new_image = new stdClass;
            $image_asset = new stdClass;
            $image_asset_id = $this->settings['document_prefix'] . '-' . $image['file_id'];
            $image_asset->id = $image_asset_id;
            $image_asset->profiles = $this->get_npr_cds_asset_profile('image');
            $image_asset->title = $entry->title;
            $image_asset->caption = $entry->{$this->field_utils->get_field_name('teaser')};
            $image_asset->producer = $image_credits['media_credit'];
            $image_asset->provider = $image_credits['media_agency'];
            $image_asset->enclosures = [];

            $image_attach_url = $image['url'];
            $image_enc = new stdClass;
            $image_enc->href = $image_attach_url . $in_body;

            $image_enc->rels = ['image-custom'];
            if (!empty($image_type)) {
                $image_enc->rels[] = 'primary';
                $new_image->rels = $image_type;
            }

            if (!empty($image_meta)) {
                $image_enc->type = $image_meta->mime_type;
                $image_enc->width = intval($image_meta->width);
                $image_enc->height = intval($image_meta->height);
            }

            $image_asset->enclosures[] = $image_enc;
            $story->assets->{$image_asset_id} = $image_asset;

            foreach ($crops as $data) {
                $crop = $data['attr'];

                $enclosure = new stdClass;
                $enclosure->href = base_url() . $crop['src'];

                $image_format = 'image-custom';

                $enclosure->rels = [
                    $image_format,
                ];

                $enclosure->type = $image_meta->mime_type;
                $enclosure->width = intval($crop['width']);
                // $enclosure->height = intval($crop['height']);

                $image_asset->enclosures[] = $enclosure;
            }

            $new_image->href = '#/assets/' . $image_asset_id;
            $story->images[] = $new_image;
        }

        /*
         * Attach audio to the post
         *
         * Should be able to do the same as image for audio, with post_mime_type = 'audio' or something.
         */
        $audios = $this->get_media($entry, 'audio_files');
        $audio_files = [];

        if (!empty($audios)) {
            $story->audio = [];
            $audio_has = new stdClass;
            $audio_has->href = '/' . $cds_version . '/profiles/has-audio';
            $audio_has->rels = ['interface'];
            $story->profiles[] = $audio_has;
            $audio_listen = new stdClass;
            $audio_listen->href = '/' . $cds_version . '/profiles/listenable';
            $audio_listen->rels = ['interface'];
            $story->profiles[] = $audio_listen;
        }

        foreach ($audios as $audio) {
            $audio_meta = ee('Model')->get('FileSystemEntity')->filter('file_id', $audio['file_id'])->first();
            $audio_guid = $audio['url'];
            $audio_files[] = $audio['file_id'];

            $new_audio = new stdClass;
            $audio_asset = new stdClass;
            $audio_asset_id = $this->settings['document_prefix'] . '-' . $audio['file_id'];
            $audio_asset->id = $audio_asset_id;
            $audio_asset->profiles = $this->get_npr_cds_asset_profile('audio');
            $audio_asset->title = $entry->title;
            $audio_asset->isAvailable = true;
            $audio_asset->isDownloadable = true;
            $audio_asset->isEmbeddable = false;
            $audio_asset->isStreamable = false;

            $file_path = $audio_meta->getBaseServerPath() . $audio_meta->file_name;
            $audio_asset->duration = $audio['audio_duration'] === '' ?
            $this->get_audio_duration($file_path) :
            $audio['audio_duration'];

            $audio_enc = new stdClass;
            $audio_enc->href = $audio_guid;
            $audio_enc->type = $audio_meta->mime_type;

            $audio_asset->enclosures = [$audio_enc];
            $story->assets->{$audio_asset_id} = $audio_asset;

            $new_audio->href = '#/assets/' . $audio_asset_id;
            if (count($audio_files) == 1) {
                $new_audio->rels = ['headline', 'primary'];
            }

            $story->audio[] = $new_audio;
        }

        /**
         * attach video to post
         */
        $videos = $this->get_video_codes($entry, 'videoembed_grid');

        if (!empty($videos)) {
            $story->videos = [];
            $video_has = new stdClass;
            $video_has->href = '/' . $cds_version . '/profiles/has-videos';
            $video_has->rels = ['interface'];
            $story->profiles[] = $video_has;
        }

        foreach ($videos as $video) {
            $video_info = $this->process_video_info($video);
            $video_asset_id = $cds_id . '-' . $cds_count;
            $cds_count++;

            // add asset id to videos[]
            $video_asset = new stdClass;
            $video_asset->href = '#/assets/' . $video_asset_id;
            $story->videos[] = $video_asset;

            // add video document to assets[]
            $video_document = new stdClass;
            $video_document->id = $video_asset_id;
            $video_document->profiles = [];

            $video_profile = new stdClass;
            $video_profile->href = '/' . $cds_version . '/profiles/' . $video_info['npr_video_profile'];

            if ($video_info['npr_video_profile'] === 'youtube-video') {
                // add youtube-video profile (https://npr.github.io/content-distribution-service/profiles/youtube-video.html)
                $video_document->title = $entry->Channel->channel_title;
                $video_document->subheadline = $video['video_title'];
                $video_document->videoId = $video_info['video_id'];
                if (array_key_exists('startTime', $video_info)) {
                    $video_document->startTime = $video_info['startTime'];
                }
            }

            if ($video_info['npr_video_profile'] === 'player-video') {
                // add player-video profile (https://npr.github.io/content-distribution-service/profiles/player-video.html)
                $video_document->enclosures = [];
                $enclosure = new stdClass;
                $enclosure->href = $video_info['src'];
                $enclosure->rels = ['mp4-high'];
                $video_document->enclosures[] = $enclosure;

                $video_document->title = $video['video_title'];
                $video_document->isEmbeddable = true;
                $video_document->isRestrictedToAuthorizedOrgServiceIds = false;
            }

            if ($video_info['npr_video_profile'] === 'stream-player-video') {
                // add player-video profile (https://npr.github.io/content-distribution-service/profiles/stream-player-video.html)
                $video_document->enclosures = [];
                $enclosure = new stdClass;
                $enclosure->href = $video_info['src'];
                $enclosure->rels = ['hls'];
                $video_document->enclosures[] = $enclosure;

                $video_document->title = $video['video_title'];
                $video_document->isEmbeddable = true;
                $video_document->isRestrictedToAuthorizedOrgServiceIds = false;
            }
        }

        $video_document->profiles[] = $video_profile;
        $story->assets->{$video_asset_id} = $video_document;

        $json = json_encode($story);

        return $json;
    }

    public function create_story_id(ChannelEntry $entry): string
    {
        $prefix = $this->settings['document_prefix'];
        $cds_id = $prefix . '-' . $entry->entry_id;
        return $cds_id;
    }

    private function apply_shortcodes(string $text): string
    {
        /**
         * Known ee shortcodes
         * - {base_url}
         * - {C} - possible junk
         * - {file:123:url}
         * - {filedir_1}
         */
        $base_url = base_url();
        $text = preg_replace('/{base_url}/', $base_url, $text);

        $new_style_files = [];
        preg_match('/{file:\d+:url}/', $text, $new_style_files);
        if (count($new_style_files) > 0) {
            if (!is_array($new_style_files[0])) {
                $new_style_files[0] = [$new_style_files[0]];
            }

            foreach ($new_style_files[0] as $match) {
                $file_id = explode(':', $match)[1];
                $path = $this->get_media_url($file_id);
                $text = preg_replace("/$match/", $path, $text);
            }
        }

        $filedirs = [];
        preg_match('/{filedir_\d+}/', $text, $filedirs);
        if (count($filedirs) > 0) {
            if (!is_array($filedirs[0])) {
                $filedirs[0] = [$filedirs[0]];
            }

            foreach ($filedirs as $match) {
                $dir_id = str_replace(['{filedir_', '}'], '', $match);
                $dir = ee('Model')->get('FileSystemEntity')->filter('directory_id', $dir_id)->first();
                $path = $dir->getBaseUrl();
                $text = preg_replace("/$match/", $path, $text);
            }
        }

        return $text;
    }

    private function check_image_in_body(array $image, string $content): string
    {
        $image_attach_url = $this->get_filemanager_parts($image['file']);
        $image_url = parse_url($image_attach_url['filename']);
        $image_name_parts = pathinfo($image_url['path']);

        $image_regex = "/" . $image_name_parts['filename'] . "\-[a-zA-Z0-9]*" . $image_name_parts['extension'] . "/";
        $in_body = "";
        if (preg_match($image_regex, $content)) {
            if (str_contains($image_attach_url['filename'], '?')) {
                $in_body = "&origin=body";
            } else {
                $in_body = "?origin=body";
            }
        }

        return $in_body;
    }

    private function construct_canonical_url(string $base_url, string $url_title)
    {
        $url_segments = explode('/', $base_url);

        $last = $url_segments[array_key_last($url_segments)];
        if ($last === '{entry_id}' || $last === '{url_title}') {
            unset($url_segments[array_key_last($url_segments)]);
        }

        array_push($url_segments, $url_title);
        $url = rtrim(ee()->config->item('site_url'), '/') . '/' . ltrim(implode('/', $url_segments), '/');

        return $url;
    }

    private function create_image_crops(array $manipulations): array
    {
        $crops = array();
        foreach ($manipulations as $manipulation) {
            $crops[] = array(
                'tag' => 'crop',
                'attr' => array(
                    'type' => $manipulation['type'],
                    'src' => $manipulation['src'],
                    'height' => array_key_exists('height', $manipulation) ? intval($manipulation['height']) : null,
                    'width' => intval($manipulation['width']),
                ),
            );
        }

        return $crops;
    }

    private function get_audio_duration(string $absolute_path): int | float
    {
        $mp3 = new MP3File($absolute_path);
        $duration = $mp3->getDurationEstimate();
        return $duration;
    }

    private function get_bylines(ChannelEntry $entry): array
    {
        /*
         * If there is a custom byline configured, send that.
         *
         * If no cool things are going on, just send the display name for the post_author field.
         */
        $bylines = [];

        $byline_field = $this->field_utils->get_field_name('byline');
        if (!empty($entry->{$byline_field})) {
            $bylines = explode(', ', $entry->{$byline_field});

            $last = count($bylines) - 1;
            $bylines[$last] = ltrim($bylines[$last], '&');

            if (substr($bylines[$last], 0, strlen('and ') == 'and ')) {
                $bylines[$last] = substr($bylines[$last], strlen('and '));
            }
        }

        if (empty($bylines)) {
            $author_id = $entry->author_id;
            $member = ee('Model')->get('Member')->filter('member_id', $author_id)->first();

            $bylines[] = $member->screen_name;
        }

        return $bylines;
    }

    private function get_corrections(ChannelEntry $entry): array
    {
        $content_type = 'channel';
        ee()->load->model('grid_model');
        $media_field_id = $this->field_utils->get_field_id('corrections');

        // map column names
        $columns = ee()->grid_model->get_columns_for_field($media_field_id, $content_type);

        // get entry data
        $entry_data = ee()->grid_model->get_entry_rows($entry->entry_id, $media_field_id, $content_type, []);

        // loop entry data rows
        $corrections = [];
        foreach ($entry_data[$entry->entry_id] as $row) {
            $row_data = [];

            // map column data to column names
            foreach ($columns as $column_id => $column_details) {
                $column_name = $column_details['col_name'];
                $row_column = "col_id_$column_id";
                $row_col_data = $row[$row_column];
                $row_data[$column_name] = $row_col_data;
            }

            $date_info = explode('|', $row_data['correction_date']);
            $date = date('c', $date_info[0]);
            $row_data['correction_date'] = $date;

            $corrections[] = $row_data;
        }

        return $corrections;
    }

    private function get_file_id($file_src)
    {
        $filename = '';

        if (str_starts_with($file_src, '{')) {
            $parts = $this->get_filemanager_parts($file_src);
            $filename = $parts['filename'];
        } else {
            $image_url_data = parse_url($file_src);
            $image_path = ltrim($image_url_data['path'], '/');
            $image_path_elements = explode('/', $image_path);
            $filename = array_pop($image_path_elements);
        }

        $file_id = ee()->db->select('file_id')
            ->from('files')
            ->where(array(
                'file_name' => $filename,
            ))
            ->limit(1)
            ->get()
            ->row()
            ->file_id;

        return $file_id;
    }

    private function get_filemanager_parts(string $file_src): array
    {
        $parts = [
            'location' => '',
            'filename' => '',
        ];

        if ($this->file_manager_compatibility_mode === true) {
            $file_src = ltrim($file_src, '{');
            $file_src = str_replace('}', ':', $file_src);
            $split = explode(':', $file_src);
            $parts['location'] = $split[0];
            $parts['filename'] = $split[1];
        } else {
            $file_src = ltrim($file_src, '{');
            $file_src = rtrim($file_src, '}');
            $split = explode(':', $file_src);
            $parts['location'] = $split[1];
            $parts['filename'] = $split[2];
        }

        return $parts;
    }

    private function get_manipulations(array $image_data): array
    {
        $file = ee('Model')->get('File')->filter('file_id', $image_data['file_id'])->first();
        if ($file === null) {
            return array();
        }

        $destinations = $file->UploadDestination;
        $dimensions = $destinations->FileDimensions;

        $manipulations = array();
        foreach ($dimensions as $dimension) {
            $src = rtrim($destinations->url, '/') . "/_" . $dimension->short_name . "/" . $file->file_name;
            $manipulation = [
                'type' => $dimension->short_name,
                'src' => $src,
                'height' => $dimension->height,
                'width' => $dimension->width,
            ];

            $manipulations[] = $manipulation;
        }

        return $manipulations;
    }

    private function get_media(ChannelEntry $entry, string $field_name)
    {
        $content_type = 'channel';
        ee()->load->model('grid_model');
        $media_field_id = $this->field_utils->get_field_id($field_name);

        // map column names
        $columns = ee()->grid_model->get_columns_for_field($media_field_id, $content_type);

        // get entry data
        $entry_data = ee()->grid_model->get_entry_rows($entry->entry_id, $media_field_id, $content_type, []);

        // loop entry data rows
        $media = array();
        foreach ($entry_data[$entry->entry_id] as $row) {
            $row_data = array();

            // map column data to column names
            foreach ($columns as $column_id => $column_details) {
                $column_name = $column_details['col_name'];
                $row_column = "col_id_$column_id";
                $row_col_data = $row[$row_column];
                $row_data[$column_name] = $row_col_data;
            }

            // get filename from possible url
            $url_col = '';
            if ($field_name === 'audio_files') {
                $url_col = $row_data['audio_url'];
            } elseif ($field_name === 'npr_images') {
                $url_col = $row_data['crop_src'];
            }

            if (empty($url_col)) {
                $url_col = $row_data['file'];
            }

            $file_id = $this->get_file_id($url_col);
            $row_data['file_id'] = $file_id;

            $url = $this->get_media_url($file_id);
            $row_data['url'] = $url;

            $media[] = $row_data;
        }

        return $media;
    }

    private function get_media_url($file_id): string
    {
        $file = ee('Model')->get('FileSystemEntity')->filter('file_id', $file_id)->first();
        $base_url = rtrim(base_url(), '/');
        $path = rtrim($file->getBaseUrl(), '/');
        $filename = ltrim($file->file_name, '/');
        $url = $base_url . $path . '/' . $filename;

        return $url;
    }

    private function get_npr_cds_asset_profile($type, $cds_version = Npr_constants::NPR_CDS_VERSION): array
    {
        $profiles = [$type, 'document'];
        $output = [];
        foreach ($profiles as $p) {
            $new = new stdClass;
            $new->href = '/' . $cds_version . '/profiles/' . $p;
            if ($p == $type) {
                $new->rels = ['type'];
            }
            $output[] = $new;
        }

        return $output;
    }

    private function get_npr_cds_base_profiles($cds_version): array
    {
        $profiles = [
            'v1' => ['story', 'publishable', 'document', 'renderable', 'buildout'],
        ];

        $output = [];
        foreach ($profiles[$cds_version] as $p) {
            $new = new stdClass;
            $new->href = '/' . $cds_version . '/profiles/' . $p;
            if ($p !== 'document') {
                if ($p == 'story') {
                    $new->rels = ['type'];
                } else {
                    $new->rels = ['interface'];
                }
            }
            $output[] = $new;
        }

        return $output;
    }

    /**
     * Helper function to get the post expiry datetime
     *
     * The datetime is stored in post meta _nprone_expiry_8601
     * This assumes that the post has been published
     *
     * @param ChannelEntry $entry the ChannelEntry object
     *
     * @return DateTime the DateTime object created from the post expiry date
     * @see note on DATE_ATOM and DATE_ISO8601 https://secure.php.net/manual/en/class.datetime.php#datetime.constants.types
     * @uses npr_cds_get_datetimezone
     */
    private function get_post_expiry_datetime(ChannelEntry $entry): string
    {
        if (!empty($entry->expiration_date)) {
            return date('c', $entry->expiration_date);
        }

        $audio_runby_field = $this->field_utils->get_field_name('audio_runby_date');
        $audio_runby_date = $entry->{$audio_runby_field};

        if (!empty($audio_runby_date)) {
            return date('c', $audio_runby_date);
        }

        // return DateTime for the publish date plus seven days
        $future = date('c', $entry->edit_date);
        $future = date_add(date_create($future), new DateInterval('P7D'));

        return $future->format(\DateTime::ATOM);
    }

    private function get_text(ChannelEntry $entry, string $field_name, bool $strip_tags): string
    {
        $field = $this->field_utils->get_field_name($field_name);

        if (empty($entry->{$field})) {
            return '';
        }

        $text = $entry->{$field};

        if ($strip_tags) {
            $text = strip_tags($text);
        } else {
            $text = $this->apply_shortcodes($text);
        }

        return $text;
    }

    // todo: refactor against get_media()
    private function get_video_codes(ChannelEntry $entry, string $field_name): array
    {
        $content_type = 'channel';
        ee()->load->model('grid_model');
        $media_field_id = $this->field_utils->get_field_id($field_name);

        // map column names
        $columns = ee()->grid_model->get_columns_for_field($media_field_id, $content_type);

        // get entry data
        $entry_data = ee()->grid_model->get_entry_rows($entry->entry_id, $media_field_id, $content_type, []);

        // loop entry data rows
        $media = array();
        foreach ($entry_data[$entry->entry_id] as $row) {
            $row_data = array();

            // map column data to column names
            foreach ($columns as $column_id => $column_details) {
                $column_name = $column_details['col_name'];
                $row_column = "col_id_$column_id";
                $row_col_data = $row[$row_column];
                $row_data[$column_name] = $row_col_data;
            }

            $media[] = $row_data;
        }

        return $media;
    }

    private function get_video_id(string $url): string
    {
        $values = '';
        if (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $id)) {
            $values = $id[1];
        } else if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $id)) {
            $values = $id[1];
        } else if (preg_match('/youtube\.com\/v\/([^\&\?\/]+)/', $url, $id)) {
            $values = $id[1];
        } else if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $id)) {
            $values = $id[1];
        } else if (preg_match('/youtube\.com\/verify_age\?next_url=\/watch%3Fv%3D([^\&\?\/]+)/', $url, $id)) {
            $values = $id[1];
        } else {
            // not an youtube video
        }

        return $values;
    }

    private function process_image_credits(array $image_data): array
    {
        $credits = array();
        foreach ($image_data as $data) {
            if ($data['crop_primary'] === 1) {
                $components = explode("/", $data['crop_credit']);
                $credits['media_credit'] = $components[0];
                $credits['media_agency'] = count($components) > 1 ? $components[1] : null;
            }
        }

        return $credits;
    }

    private function process_video_info(array $data): array
    {
        // set default tag name and check embed code against other possibilities
        $tag_name = 'iframe';
        $possible_tags = ['iframe', 'video', 'embed'];
        foreach ($possible_tags as $possible) {
            if (strpos($data['embed_code'], "<$possible") !== false) {
                $tag_name = $possible;
                break;
            }
        }

        // load embed code as DOM document and parse video tag attributes
        $dom = new DOMDocument;
        $dom->loadHTML($data['embed_code']);

        $attributes = [];
        foreach ($dom->getElementsByTagName($tag_name) as $tag) {
            foreach ($tag->attributes as $attribute_name => $node_value) {
                $attributes[$attribute_name] = $tag->getAttribute($attribute_name);
            }
        }

        // parse url segments and query strings
        $parsed_url = parse_url($attributes['src']);
        $queries = [];
        if (array_key_exists('query', $parsed_url)) {
            parse_str($parsed_url['query'], $queries);
        }

        // guess npr profile from domain, allowing for variants like youtu.be
        $npr_video_profile = str_contains($parsed_url['host'], 'youtu') ? 'youtube-video' : 'stream-player-video';
        $attributes['video_id'] = $this->get_video_id($attributes['src']);

        // grab a path fragment for the asset ID
        $attributes['npr_video_profile'] = $npr_video_profile;

        if (array_key_exists('start', $queries)) {
            $attributes['startTime'] = $queries['start'];
        }

        return $attributes;
    }
}
