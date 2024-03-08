<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../configuration/npr_constants.php';
require_once __DIR__ . '/../utilities/field_utils.php';

use ExpressionEngine\Model\Channel\ChannelEntry;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;
use \stdClass;

class Cds_mapper
{
    private $field_utils;

    private $settings = [
        'document_prefix' => '',
        'pull_url' => '',
        'push_url' => '',
        'service_id' => null,
        // 'theme_uses_featured_image' => false,
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
        $this->settings = $this->load_settings();
    }

    public function create_json(ChannelEntry $entry, array $values, string $profile)
    {
        if ($profile !== 'document') {
            throw new \Exception('non-document profiles not supported');
        }

        $cds_version = Npr_constants::NPR_CDS_VERSION;
        $story = new stdClass;
        $prefix = $this->settings['document_prefix'];
        $cds_id = $prefix . '-' . $entry->entry_id;
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

        $story->title = $entry->title;

        // /*
        //  * If there is a custom byline configured, send that.
        //  *
        //  * If the site is using the coauthors plugin, and get_coauthors exists, send the display names
        //  * If no cool things are going on, just send the display name for the post_author field.
        //  */
        // $bylines = [];
        // $custom_byline_meta = get_option('npr_cds_mapping_byline');
        // // Custom field mapping byline
        // if (
        //     $use_custom
        //     && !empty($custom_byline_meta)
        //     && $custom_byline_meta != '#NONE#'
        //     && in_array($custom_content_meta, $post_metas)
        // ) {
        //     $bylines[] = get_post_meta($post->ID, $custom_byline_meta, true);
        // }

        // // Co-Authors Plus support overrides the NPR custom byline
        // if (function_exists('get_coauthors')) {
        //     $coauthors = get_coauthors($post->ID);
        //     if (!empty($coauthors)) {
        //         foreach ($coauthors as $i => $co) {
        //             $bylines[] = $co->display_name;
        //         }
        //     } else {
        //         npr_cds_error_log('we do not have co authors');
        //     }
        // } else {
        //     npr_cds_error_log('can not find get_coauthors');
        // }
        // if (empty($bylines)) {
        //     $bylines[] = get_the_author_meta('display_name', $post->post_author);
        // }
        // foreach ($bylines as $byline) {
        //     $byl = new stdClass;
        //     $byl_asset = new stdClass;
        //     $byline_id = $cds_id . '-' . $cds_count;
        //     $byl->id = $byline_id;
        //     $byl->name = $byline;
        //     $byl->profiles = npr_cds_asset_profile('byline');
        //     $story->assets->{$byline_id} = $byl;
        //     $byl_asset->href = '#/assets/' . $byline_id;
        //     $story->bylines[] = $byl_asset;
        //     $cds_count++;
        // }

        // /*
        //  * Send to NPR One
        //  *
        //  * If the box is checked, the value here is '1'
        //  * @see nprstory_save_send_to_one
        //  */
        // $nprapi = get_post_meta($post->ID, '_send_to_one', true); // 0 or 1
        // if (!empty($nprapi) && ('1' === $nprapi || 1 === $nprapi)) {
        //     $collect = new stdClass;
        //     $collect->rels = ['collection'];
        //     $collect->href = '/' . $cds_version . '/documents/319418027';
        //     $story->collections[] = $collect;
        // }

        // /*
        //  * This story should be featured in NPR One
        //  *
        //  * @see nprstory_save_nprone_featured
        //  */
        // $nprapi = get_post_meta($post->ID, '_nprone_featured', true); // 0 or 1
        // if (!empty($nprapi) && ('1' === $nprapi || 1 === $nprapi)) {
        //     $collect = new stdClass;
        //     $collect->rels = ['collection'];
        //     $collect->href = '/' . $cds_version . '/documents/500549367';
        //     $story->collections[] = $collect;
        // }

        // // NPR One audio run-by date
        // $datetime = npr_cds_get_post_expiry_datetime($post); // if expiry date is not set, returns publication date plus 7 days
        // $story->recommendUntilDateTime = date_format($datetime, 'c');

        // // Parse through the paragraphs, add references to layout array, and paragraph text to assets
        // $parts = array_filter(
        //     array_map('trim', preg_split("/<\/?p>/", $content))
        // );
        // foreach ($parts as $part) {
        //     $para = new stdClass;
        //     $para_asset = new stdClass;
        //     $para_id = $cds_id . '-' . $cds_count;
        //     $para->id = $para_id;

        //     $para_type = 'text';
        //     if (preg_match('/^<(figure|div)/', $part)) {
        //         $para_type = 'html';
        //     }
        //     if ($para_type == 'html') {
        //         $para->profiles = npr_cds_asset_profile($para_type . '-block');
        //     } else {
        //         $para->profiles = npr_cds_asset_profile($para_type);
        //     }
        //     $para->{$para_type} = $part;
        //     $story->assets->{$para_id} = $para;
        //     $para_asset->href = '#/assets/' . $para_id;
        //     $story->layout[] = $para_asset;
        //     $cds_count++;
        // }

        // $custom_media_credit = get_option('npr_cds_mapping_media_credit');
        // $custom_media_agency = get_option('npr_cds_mapping_media_agency');

        // /*
        //  * Attach images to the post
        //  */
        // $args = [
        //     'order' => 'DESC',
        //     'post_mime_type' => 'image',
        //     'post_parent' => $post->ID,
        //     'post_status' => null,
        //     'post_type' => 'attachment',
        // ];

        // $images = get_children($args);
        // $primary_image = get_post_thumbnail_id($post->ID);

        // if (!empty($images)) {
        //     $story->images = [];
        //     $image_profile = new stdClass;
        //     $image_profile->href = '/' . $cds_version . '/profiles/has-images';
        //     $image_profile->rels = ['interface'];
        //     $story->profiles[] = $image_profile;
        // }

        // foreach ($images as $image) {
        //     $custom_credit = '';
        //     $custom_agency = '';
        //     $image_metas = get_post_custom_keys($image->ID);
        //     if (
        //         $use_custom &&
        //         !empty($custom_media_credit) &&
        //         $custom_media_credit != '#NONE#' &&
        //         in_array($custom_media_credit, $image_metas)
        //     ) {
        //         $custom_credit = get_post_meta($image->ID, $custom_media_credit, true);
        //     }

        //     if (
        //         $use_custom &&
        //         !empty($custom_media_agency) &&
        //         $custom_media_agency != '#NONE#' &&
        //         in_array($custom_media_agency, $image_metas)
        //     ) {
        //         $custom_agency = get_post_meta($image->ID, $custom_media_agency, true);
        //     }

        //     // If the image field for distribute is set and polarity then send it.
        //     // All kinds of other math when polarity is negative or the field isn't set.
        //     $image_type = [];
        //     if ($image->ID == $primary_image) {
        //         $image_type = ['primary', 'promo-image-standard'];
        //     }

        //     // Is the image in the content?  If so, tell the API with a flag that CorePublisher knows.
        //     // WordPress may add something like "-150X150" to the end of the filename, before the extension.
        //     // Isn't that nice? Let's remove that.
        //     $image_attach_url = wp_get_attachment_url($image->ID);
        //     $image_url = parse_url($image_attach_url);
        //     $image_name_parts = pathinfo($image_url['path']);

        //     $image_regex = "/" . $image_name_parts['filename'] . "\-[a-zA-Z0-9]*" . $image_name_parts['extension'] . "/";
        //     $in_body = "";
        //     if (preg_match($image_regex, $content)) {
        //         if (str_contains($image_attach_url, '?')) {
        //             $in_body = "&origin=body";
        //         } else {
        //             $in_body = "?origin=body";
        //         }
        //     }

        //     $image_meta = wp_get_attachment_metadata($image->ID);

        //     $new_image = new stdClass;
        //     $image_asset = new stdClass;
        //     $image_asset_id = $prefix . '-' . $image->ID;
        //     $image_asset->id = $image_asset_id;
        //     $image_asset->profiles = npr_cds_asset_profile('image');
        //     $image_asset->title = $image->post_title;
        //     $image_asset->caption = $image->post_excerpt;
        //     $image_asset->producer = $custom_credit;
        //     $image_asset->provider = $custom_agency;
        //     $image_asset->enclosures = [];

        //     $image_enc = new stdClass;
        //     $image_enc->href = $image_attach_url . $in_body;
        //     $image_enc->rels = ['image-custom'];
        //     if (!empty($image_type)) {
        //         $image_enc->rels[] = 'primary';
        //         $new_image->rels = $image_type;
        //     }
        //     $image_enc->type = $image->post_mime_type;
        //     if (!empty($image_meta)) {
        //         $image_enc->width = $image_meta['width'];
        //         $image_enc->height = $image_meta['height'];
        //     }

        //     $image_asset->enclosures[] = $image_enc;
        //     $story->assets->{$image_asset_id} = $image_asset;

        //     $new_image->href = '#/assets/' . $image_asset_id;
        //     $story->images[] = $new_image;
        // }

        // /*
        //  * Attach audio to the post
        //  *
        //  * Should be able to do the same as image for audio, with post_mime_type = 'audio' or something.
        //  */
        // $args = [
        //     'order' => 'DESC',
        //     'post_mime_type' => 'audio',
        //     'post_parent' => $post->ID,
        //     'post_status' => null,
        //     'post_type' => 'attachment',
        // ];
        // $audios = get_children($args);
        // $audio_files = [];

        // if (!empty($audios)) {
        //     $story->audio = [];
        //     $audio_has = new stdClass;
        //     $audio_has->href = '/' . $cds_version . '/profiles/has-audio';
        //     $audio_has->rels = ['interface'];
        //     $story->profiles[] = $audio_has;
        //     $audio_listen = new stdClass;
        //     $audio_listen->href = '/' . $cds_version . '/profiles/listenable';
        //     $audio_listen->rels = ['interface'];
        //     $story->profiles[] = $audio_listen;
        // }

        // foreach ($audios as $audio) {
        //     $audio_meta = wp_get_attachment_metadata($audio->ID);
        //     $audio_guid = wp_get_attachment_url($audio->ID);
        //     $audio_files[] = $audio->ID;

        //     $new_audio = new stdClass;
        //     $audio_asset = new stdClass;
        //     $audio_asset_id = $prefix . '-' . $audio->ID;
        //     $audio_asset->id = $audio_asset_id;
        //     $audio_asset->profiles = npr_cds_asset_profile('audio');
        //     $audio_asset->title = $audio->post_title;
        //     $audio_asset->isAvailable = true;
        //     $audio_asset->isDownloadable = true;
        //     $audio_asset->isEmbeddable = false;
        //     $audio_asset->isStreamable = false;
        //     $audio_asset->duration = $audio_meta['length'];

        //     $audio_enc = new stdClass;
        //     $audio_enc->href = $audio_guid;
        //     $audio_enc->type = $audio->post_mime_type;

        //     $audio_asset->enclosures = [$audio_enc];
        //     $story->assets->{$audio_asset_id} = $audio_asset;

        //     $new_audio->href = '#/assets/' . $audio_asset_id;
        //     if (count($audio_files) == 1) {
        //         $new_audio->rels = ['headline', 'primary'];
        //     }

        //     $story->audio[] = $new_audio;
        // }

        // /*
        //  * Support for Powerpress enclosures
        //  *
        //  * This logic is specifically driven by enclosure metadata items that are
        //  * created by the PowerPress podcasting plug-in. It will likely have to be
        //  * re-worked if we need to accomodate other plug-ins that use enclosures.
        //  */
        // if ($enclosures = get_metadata('post', $post->ID, 'enclosure')) {
        //     foreach ($enclosures as $enclosure) {
        //         $pieces = explode("\n", $enclosure);

        //         $audio_guid = trim($pieces[0]);
        //         $attach_id = attachment_url_to_postid($audio_guid);
        //         if (!in_array($attach_id, $audio_files)) {
        //             $audio_files[] = $attach_id;

        //             $audio_meta = wp_get_attachment_metadata($attach_id);
        //             $duration = 0;
        //             if (!empty($audio_meta['length'])) {
        //                 $duration = $audio_meta['length'];
        //             } elseif (!empty($audio_meta['length_formatted'])) {
        //                 $duration = npr_cds_convert_duration_to_seconds($audio_meta['length_formatted']);
        //             } elseif (!empty($pieces[3])) {
        //                 $metadata = unserialize(trim($pieces[3]));
        //                 $duration = (!empty($metadata['duration'])) ? npr_cds_convert_duration_to_seconds($metadata['duration']) : 0;
        //             }
        //             $audio_type = 'audio/mpeg';
        //             if (!empty($audio_meta['mime_type'])) {
        //                 $audio_type = $audio_meta['mime_type'];
        //             }

        //             $new_audio = new stdClass;
        //             $audio_asset = new stdClass;
        //             $audio_asset_id = $prefix . '-' . $attach_id;
        //             $audio_asset->id = $audio_asset_id;
        //             $audio_asset->profiles = npr_cds_asset_profile('audio');
        //             $audio_asset->isAvailable = true;
        //             $audio_asset->isDownloadable = true;
        //             $audio_asset->isEmbeddable = false;
        //             $audio_asset->isStreamable = false;
        //             $audio_asset->duration = $duration;

        //             $audio_enc = new stdClass;
        //             $audio_enc->href = wp_get_attachment_url($attach_id);
        //             $audio_enc->type = $audio_type;

        //             $audio_asset->enclosures = [$audio_enc];
        //             $story->assets->{$attach_id} = $audio_asset;

        //             $new_audio->href = '#/assets/' . $audio_asset_id;
        //             if (count($audio_files) == 1) {
        //                 $new_audio->rels = ['headline', 'primary'];
        //             }

        //             $story->audio[] = $new_audio;
        //         }
        //     }
        // }

        /*
         * The story has been assembled; now we shall return it
         */
        return json_encode($story);
    }

    private function apply_shortcodes(string $text): string
    {
        // /*
        //  * Clean up the content by applying shortcodes and then stripping any remaining shortcodes.
        //  */
        // // Let's see if there are any plugins that need to fix their shortcodes before we run do_shortcode
        // if (has_filter('npr_cds_shortcode_filter')) {
        //     $content = apply_filters('npr_cds_shortcode_filter', $content);
        // }

        // // Since we don't have a standard way to handle galleries across installs, let's just nuke them
        // // Also, NPR is still trying to figure out how to handle galleries in CDS, so we can circle back when they do
        // $content = preg_replace('/\[gallery(.*)\]/U', '', $content);

        // // The [embed] shortcode also gets kinda hinky, along with the Twitter/YouTube oEmbed stuff
        // // In lieu of removing them, let's just convert them into links
        // $content = preg_replace('/\[embed\](.*)\[\/embed\]/', '<a href="$1">$1</a>', $content);
        // $content = preg_replace('/<p>(https?:\/\/.+)<\/p>/U', '<p><a href="$1">$1</a></p>', $content);

        // // Apply the usual filters from 'the_content', which should resolve any remaining shortcodes
        // $content = apply_filters('the_content', $content);

        // // for any remaining short codes, nuke 'em
        // $content = strip_shortcodes($content);
        return $text;
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

        return $settings;
    }
}
