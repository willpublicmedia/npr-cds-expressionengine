<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../utilities/config_utils.php';
require_once __DIR__ . '/../utilities/channel_entry_builder.php';
require_once __DIR__ . '/../publishing/npr_cds_expressionengine.php';
require_once __DIR__ . '/story_api_compatibility_mapper.php';

use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_request;
use IllinoisPublicMedia\NprCds\Libraries\Mapping\Story_api_compatibility_mapper;
use IllinoisPublicMedia\NprCds\Libraries\Publishing\Npr_cds_expressionengine;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Channel_entry_builder;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Config_utils;
use \stdClass;

class Publish_form_mapper
{
    private $settings = [
        'pull_url' => '',
        'service_id' => '',
        'theme_uses_featured_image' => false,
    ];

    public function __construct()
    {
        $this->settings = Config_utils::load_settings(array_keys($this->settings));
    }

    public function map($entry, $values, $story)
    {
        $profiles = $this->extract_profiles($story->profiles);
        $collections = $this->get_collections($story);
        $corrections = $this->get_corrections($story);
        $audio = in_array('has-audio', $profiles) || property_exists($story, 'audio') ? $this->get_audio($story) : null;
        $images = in_array('has-images', $profiles) || property_exists($story, 'images') ? $this->get_images($story) : null;
        $videos = in_array('has-videos', $profiles) || property_exists($story, 'videos') ? $this->get_videos($story) : null;
        $bylines = property_exists($story, 'bylines') ? $this->get_bylines($story) : null;

        $npr_layout = $this->get_body_with_layout($story, $profiles);
        $text = array_key_exists('body', $npr_layout) ? $npr_layout['body'] : '';

        /**
         * @see https://npr.github.io/content-distribution-service/getting-started/story-api-migration-guide/table-of-fields.html
         */
        $url_title = $this->generate_url_title($entry, $story->title);
        $data = [
            'audio' => $audio,
            'bylines' => $bylines,
            'collections' => $collections,
            'corrections' => $corrections,
            'last_modified_date' => !empty($story->editorialLastModifiedDateTime) ? strtotime($story->editorialLastModifiedDateTime) : null,
            'images' => $images,
            'pub_date' => !empty($story->publishDateTime) ? strtotime($story->publishDateTime) : null,
            'recommendUntilDateTime' => !empty($story->recommendUntilDateTime) ? strtotime($story->recommendUntilDateTime) : null,
            'socialTitle' => property_exists($story, 'socialTitle') ? $story->socialTitle : null,
            'shortTeaser' => property_exists($story, 'shortTeaser') ? $story->socialTitle : null,
            'teaser' => $story->teaser,
            'text' => $text,
            'title' => $story->title,
            'url_title' => $url_title,
            'videos' => $videos,
        ];

        $api_compat = new Story_api_compatibility_mapper();
        $data = $api_compat->map_cds_to_story($data);

        $entry_builder = new Channel_entry_builder();
        $objects = $entry_builder->assign_data_to_entry($data, $entry, $values);
        $objects['story'] = $story;

        return $objects;
    }

    /**
     * Format and return a paragraph of text from an associated NPR API article
     * This function checks if the text is already wrapped in an HTML element (e.g. <h3>, <div>, etc.)
     * If not, the return text will be wrapped in a <p> tag
     *
     * @param string $p
     *   A string of text
     *
     * @return string
     *   A formatted string of text
     */
    private function add_paragraph_tag(string $p): string
    {
        if (preg_match('/^<[a-zA-Z0-9 \="\-_\']+>.+<[a-zA-Z0-9\/]+>$/', $p)) {
            if (preg_match('/^<(a href|em|strong)/', $p)) {
                $output = '<p>' . $p . '</p>';
            } else {
                $output = $p;
            }
        } else {
            if (str_contains($p, '<div class="fullattribution">')) {
                $output = '<p>' . str_replace('<div class="fullattribution">', '</p><div class="fullattribution">', $p);
            } else {
                $output = '<p>' . $p . '</p>';
            }
        }
        return $output;
    }

    private function extract_asset_id($href): bool | string
    {
        $href_xp = explode('/', $href);
        return end($href_xp);
    }

    private function extract_asset_profile($asset): bool | string
    {
        $output = '';
        foreach ($asset->profiles as $profile) {
            if (!empty($profile->rels) && in_array('type', $profile->rels)) {
                $output = $this->extract_asset_id($profile->href);
            }
        }
        return $output;
    }

    private function extract_profiles($story): array
    {
        $output = [];
        foreach ($story as $p) {
            $p_xp = explode('/', $p->href);
            $output[] = end($p_xp);
        }
        return $output;
    }

    private function generate_url_title($entry, $story_title)
    {
        $url_title = $entry->isNew() ?
        (string) ee('Format')->make('Text', $story_title)->urlSlug() :
        $entry->url_title;

        if (empty($url_title)) {
            $url_title = $entry->url_title;
        }

        return $url_title;
    }

    private function get_audio(stdClass $story): array
    {
        $audio_refs = $story->audio;
        $audios = [];
        foreach ($audio_refs as $ref) {
            $asset_id = $this->extract_asset_id($ref->href);
            $asset = $story->assets->{$asset_id};
            $asset_profile = $this->extract_asset_profile($asset);

            if (!$asset->isAvailable) {
                continue;
            }

            $enclosures = [];
            foreach ($asset->enclosures as $enclosure) {
                $data = [
                    'url' => $enclosure->href,
                    'rels' => !empty($enclosure->rels) ? $enclosure->rels : null,
                    'type' => !empty($enclosure->type) ? $enclosure->type : null,
                    'filesize' => !empty($enclosure->fileSize) ? $enclosure->fileSize : null,
                ];
                $enclosures[] = $data;
            }

            $audio = [
                'available' => $asset->isAvailable,
                'streamable' => $asset->isStreamable,
                'downloadable' => $asset->isDownloadable,
                'embeddable' => $asset->isEmbeddable,
                'title' => !empty($asset->headline) ? $asset->headline : null,
                'enclosures' => $enclosures,
                'duration' => !empty($asset->duration) ? $asset->duration : null,
                'availabilityMessage' => !empty($asset->availabilityMessage) ? $asset->availabilityMessage : null,
                'songTitle' => !empty($asset->songTitle) ? $asset->songTitle : null,
                'songArtist' => !empty($asset->songArtist) ? $asset->songArtist : null,
                'songTrackNumber' => !empty($asset->songTrackNumber) ? $asset->songTrackNumber : null,
                'albumTitle' => !empty($asset->albumTitle) ? $asset->albumTitle : null,
                'albumArtist' => !empty($asset->albumArtist) ? $asset->albumArtist : null,
                'expiration' => !empty($asset->streamExpirationDateTime) ? $asset->streamExpirationDateTime : null,
                'embeddedPlayerLink' => !empty($asset->embeddedPlayerLink) ? $asset->embeddedPlayerLink->href : null,
            ];

            if (!empty($asset->transcriptLink)) {
                $transcriptLink = $asset->transcriptLink->href;
                $transcriptDoc = $this->get_document($transcriptLink);
                $audio['transcript'] = $transcriptDoc->text;
            }

            $audios[$asset_id] = $audio;
        }

        return $audios;
    }

    /**
     * This function will format the body of the story with any provided assets inserted in the order they are in the layout
     * and return an array of the transformed body and flags for what sort of elements are returned
     *
     * @param stdClass $story Story object created during import
     * @param array $profiles Array of profile types extracted from story object.
     * @return array with reconstructed body and flags describing returned elements
     */
    private function get_body_with_layout(stdClass $story, array $profiles = [])
    {
        $returnary = ['has_image' => false, 'has_video' => false, 'has_external' => false, 'has_slideshow' => false, 'has_video_streaming' => false];
        $body_with_layout = "";

        $use_npr_featured = $this->settings['theme_uses_featured_image'];

        $profiles = count($profiles) > 0 ? $profiles : $this->extract_profiles($story->profiles);

        if (in_array('buildout', $profiles) && !empty($story->layout)) {
            foreach ($story->layout as $layout) {
                $asset_id = $this->extract_asset_id($layout->href);
                $asset_current = $story->assets->{$asset_id};
                $asset_profile = $this->extract_asset_profile($asset_current);
                switch ($asset_profile) {
                    case 'text':
                        if (!empty($asset_current->text)) {
                            $body_with_layout .= $this->add_paragraph_tag($asset_current->text) . "\n";
                        }
                        break;
                    case 'promo-card':
                        $promo_card = $this->get_document($asset_current->documentLink->href);
                        $promo_card_url = '';
                        if (!is_null($promo_card)) {
                            foreach ($promo_card->webPages as $web) {
                                if (in_array('canonical', $web->rels)) {
                                    $promo_card_url = $web->href;
                                }
                            }
                        }
                        $body_with_layout .= '<figure class="figure wp-block-embed npr-promo-card ' . strtolower($asset_current->cardStyle) . '"><div class="wp-block-embed__wrapper">' . (!empty($asset_current->eyebrowText) ? '<h3>' . $asset_current->eyebrowText . '</h3>' : '') .
                        '<p><a href="' . $promo_card_url . '">' . $asset_current->linkText . '</a></p></div></figure>';
                        break;
                    case 'html-block':
                        if (!empty($asset_current->html)) {
                            $body_with_layout .= $asset_current->html;
                        }
                        $returnary['has_external'] = true;
                        if (strpos($asset_current->html, 'jwplayer.com')) {
                            $returnary['has_video'] = true;
                        }
                        break;
                    case 'audio':
                        if ($asset_current->isAvailable) {
                            if ($asset_current->isEmbeddable) {
                                $body_with_layout .= '<div class=""><iframe class="npr-embed-audio" style="width: 100%; height: 290px;" src="' . $asset_current->embeddedPlayerLink->href . '"></iframe></div>';
                            } elseif ($asset_current->isDownloadable) {
                                foreach ($asset_current->enclosures as $enclose) {
                                    if ($enclose->type == 'audio/mpeg' && !in_array('premium', $enclose->rels)) {
                                        $body_with_layout .= '<audio controls src="' . $enclose->href . '" preload="metadata">';
                                    }
                                }
                            }
                        }
                        break;
                    case 'pull-quote':
                        $body_with_layout .= '<blockquote class="npr-pull-quote">' . $asset_current->quote;
                        if (!empty($asset_current->attributionParty)) {
                            $body_with_layout .= '<p>' . $asset_current->attributionParty;
                            if (!empty($asset_current->attributionContext)) {
                                $body_with_layout .= ', ' . $asset_current->attributionContext;
                            }
                            $body_with_layout .= '</p>';
                        }
                        $body_with_layout .= '</blockquote>';
                        break;
                    case 'youtube-video':
                        $asset_title = 'YouTube video player';
                        if (!empty($asset_current->headline)) {
                            $asset_title = $asset_current->headline;
                        }
                        $returnary['has_video'] = true;
                        $body_with_layout .= '<figure class="figure wp-block-embed is-type-video"><div class="ratio ratio-16x9 wp-block-embed__wrapper"><iframe src="https://www.youtube.com/embed/' . $asset_current->videoId . '" title="' . $asset_title . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></figure>';
                        break;
                    case 'internal-link':
                        $link_url = '';
                        $link_asset = $this->get_document($asset_current->documentLink->href);
                        if (!empty($link_asset->webPages)) {
                            foreach ($link_asset->webPages as $web) {
                                if (in_array('canonical', $web->rels)) {
                                    $link_url = $web->href;
                                }
                            }
                        }
                        if (!empty($link_url)) {
                            $body_with_layout .= '<p><a href="' . $link_url . '">' . $asset_current->linkText . '</a></p>';
                        }
                        break;
                    case 'external-link':
                        if (!empty($asset_current->externalLink->href)) {
                            $body_with_layout .= '<p><a href="' . $asset_current->externalLink->href . '">' . $asset_current->linkText . '</a></p>';
                        }
                        break;
                    case 'image':
                        $thisimg_rels = [];
                        foreach ($story->images as $images) {
                            if ($images->href == '#/assets/' . $asset_id && !empty($images->rels)) {
                                $thisimg_rels = $images->rels;
                            }
                        }
                        if (in_array('primary', $thisimg_rels) && $use_npr_featured) {
                            break;
                        }
                        $thisimg = $asset_current->enclosures[0];
                        foreach ($asset_current->enclosures as $img_enclose) {
                            if (!empty($img_enclose->rels) && in_array('primary', $img_enclose->rels)) {
                                $thisimg = $img_enclose;
                            }
                        }
                        $figclass = "figure wp-block-image size-large";
                        $image_href = $this->get_image_url($thisimg);
                        $fightml = '<img src="' . $image_href . '"';
                        if (in_array('image-vertical', $thisimg->rels)) {
                            $figclass .= ' alignright';
                            $fightml .= " width=200";
                        }
                        $thiscaption = (!empty(trim($asset_current->caption)) ? trim($asset_current->caption) : '');
                        $fightml .= (!empty($fightml) && !empty($thiscaption) ? ' alt="' . str_replace('"', '\'', strip_tags($thiscaption)) . '"' : '');
                        $fightml .= (!empty($fightml) ? '>' : '');
                        $thiscaption .= (!empty($cites) ? " <cite class=\"photocredit\">" . $this->parse_credits($asset_current) . "</cite>" : '');
                        $figcaption = (!empty($fightml) && !empty($thiscaption) ? "<figcaption class=\"caption\">$thiscaption</figcaption>" : '');
                        $fightml .= (!empty($fightml) && !empty($figcaption) ? $figcaption : '');
                        $body_with_layout .= (!empty($fightml) ? "<figure class=\"$figclass\">$fightml</figure>\n\n" : '');
                        break;
                    case 'image-gallery':
                        $fightml = '<figure class="figure wp-block-image"><div class="splide"><div class="splide__track"><ul class="splide__list">';
                        $returnary['has_slideshow'] = true;
                        foreach ($asset_current->layout as $ig_layout) {
                            $ig_asset_id = $this->extract_asset_id($ig_layout->href);
                            $ig_asset_current = $story->assets->{$ig_asset_id};
                            $thisimg = $ig_asset_current->enclosures[0];
                            foreach ($ig_asset_current->enclosures as $ig_img_enclose) {
                                if (!empty($ig_img_enclose->rels) && in_array('primary', $ig_img_enclose->rels)) {
                                    $thisimg = $ig_img_enclose;
                                }
                            }
                            $image_href = $this->get_image_url($thisimg);
                            $full_credits = $this->parse_credits($ig_asset_current);

                            $link_text = str_replace('"', "'", $ig_asset_current->title . $full_credits);
                            $fightml .= '<li class="splide__slide"><a href="' . urlencode($thisimg->href) . '" target="_blank"><img data-splide-lazy="' . urlencode($image_href) . '" alt="' . ee('Format')->make('Text', $link_text)->attributeEscape() . '"></a><div>' . htmlspecialchars($link_text) . '</div></li>';
                        }
                        $fightml .= '</div></div></ul></figure>';
                        $body_with_layout .= $fightml;
                        break;
                    case str_contains($asset_profile, 'player-video'):
                        if ($asset_current->isRestrictedToAuthorizedOrgServiceIds !== true) {
                            $asset_caption = [];
                            $full_caption = '';
                            if (!empty($asset_current->title)) {
                                $asset_caption[] = $asset_current->title;
                            }
                            if (!empty($asset_current->caption)) {
                                $asset_caption[] = $asset_current->caption;
                            }
                            $credits = $this->parse_credits($asset_current);
                            if (!empty($credits)) {
                                $asset_caption[] = '(' . $credits . ')';
                            }
                            if (!empty($asset_caption)) {
                                $full_caption = '<figcaption>' . implode(' ', $asset_caption) . '</figcaption>';
                            }
                            $returnary['has_video'] = true;
                            $video_asset = '';
                            if ($asset_profile == 'player-video') {
                                $poster = '';
                                $video_url = $asset_current->enclosures[0]->href;
                                if (!empty($asset_current->images)) {
                                    foreach ($asset_current->images as $v_image) {
                                        if (in_array('thumbnail', $v_image->rels)) {
                                            $v_image_id = $this->extract_asset_id($v_image->href);
                                            $v_image_asset = $story->assets->{$v_image_id};
                                            foreach ($v_image_asset->enclosures as $vma) {
                                                $poster = ' poster="' . $this->get_image_url($vma) . '"';
                                            }
                                        }
                                    }
                                }
                                foreach ($asset_current->enclosures as $v_enclose) {
                                    if (in_array('mp4-hd', $v_enclose->rels)) {
                                        $video_url = $v_enclose->href;
                                    } elseif (in_array('mp4-high', $v_enclose->rels)) {
                                        $video_url = $v_enclose->href;
                                    }
                                }
                                $video_asset = '<video controls poster="' . $poster . '" width="640" height="360"><source src="' . $video_url . '"</source></video>';
                            } elseif ($asset_profile == 'stream-player-video') {
                                if (in_array('hls', $asset_current->enclosures[0]->rels)) {
                                    $returnary['has_video_streaming'] = true;
                                    $video_asset = '<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>' .
                                    '<video id="' . $asset_current->id . '" controls></video>' .
                                    '<script>' .
                                    'let video = document.getElementById("' . $asset_current->id . '");' .
                                    'if (Hls.isSupported()) {' .
                                    'let hls = new Hls();' .
                                    'hls.attachMedia(video);' .
                                    'hls.on(Hls.Events.MEDIA_ATTACHED, () => {' .
                                    'hls.loadSource("' . $asset_current->enclosures[0]->href . '");' .
                                        'hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {' .
                                        'console.log("manifest loaded, found " + data.levels.length + " quality level");' .
                                        '});' .
                                        '});' .
                                        '}' .
                                        '</script>';
                                }
                            }

                            $video_asset = '<figure class="figure wp-block-embed is-type-video"><div class="wp-block-embed__wrapper">' . $video_asset . '</div>' . $full_caption . '</figure>';

                            $is_first_video = true;
                            if (!empty($story->videos)) {
                                $is_first_video = $layout->href === $story->videos[0]->href;
                            }

                            if (!$this->settings['theme_uses_featured_image'] || !$is_first_video) {
                                $body_with_layout .= $video_asset;
                            }
                        }
                        break;
                    default:
                        // Do nothing???
                        break;
                }

            }

        }
        if (!empty($story->corrections)) {
            $correction_text = '';
            foreach ($story->corrections as $correction) {
                $correct_id = $this->extract_asset_id($correction->href);
                $correct_current = $story->assets->{$correct_id};
                $correction_text .= '<li><strong><em>' .
                date(ee()->config->get('date_format'), strtotime($correct_current->dateTime)) .
                '</em></strong><br />' . strip_tags($correct_current->text) . '</li>';
            }
            $body_with_layout .= '<div class="wp-block-embed__wrapper" id="corrections-' . $story->npr_story_id . '"><span class="h3">Corrections:</span><ul>' . $correction_text . '</ul></div>';
        }
        if (!empty($story->audio)) {
            $audio_file = '';
            foreach ($story->audio as $audio) {
                if (in_array('primary', $audio->rels) && !in_array('premium', $audio->rels)) {
                    $audio_id = $this->extract_asset_id($audio->href);
                    $audio_current = $story->assets->{$audio_id};
                    if ($audio_current->isAvailable) {
                        if ($audio_current->isEmbeddable) {
                            $audio_file = '<div class=""><iframe class="npr-embed-audio" style="width: 100%; height: 290px;" src="' . $audio_current->embeddedPlayerLink->href . '"></iframe></div>';
                        } elseif ($audio_current->isDownloadable) {
                            foreach ($audio_current->enclosures as $enclose) {
                                if (!empty($enclose->rels) && $enclose->type == 'audio/mpeg' && !in_array('premium', $enclose->rels)) {
                                    $audio_file = '<audio src="' . $enclose->href . '" controls preload="metadata">';
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($audio_file)) {
                $body_with_layout = $audio_file . "\n" . $body_with_layout;
            }
        }
        $returnary['body'] = $body_with_layout;
        return $returnary;
    }

    private function get_bylines(stdClass $story): ?array
    {
        $byline_refs = $story->bylines;

        $bylines = [];
        foreach ($byline_refs as $ref) {
            $id = $this->extract_asset_id($ref->href);
            $asset = $story->assets->{$id};

            if (property_exists($asset, 'name')) {
                $bylines[] = $asset->name;
            } elseif (property_exists($asset, 'bylineDocuments')) {
                $bio_href = ltrim($asset->bylineDocuments[0]->href, '#');
                $bio = $this->get_document($bio_href);
                $bylines[] = $bio->title;
            }
        }

        return $bylines;
    }

    private function get_collections(stdClass $story): ?array
    {
        if (empty($story->collections)) {
            return null;
        }

        $collections = [];
        foreach ($story->collections as $item) {
            $collect_id = $this->extract_asset_id($item->href);
            $doc = $this->get_document($item->href);

            $collections[] = [
                'id' => $collect_id,
                'href' => $item->href,
                'rels' => $item->rels,
                'title' => $doc->title,
                'authorized' => in_array($this->settings['service_id'], $doc->authorizedOrgServiceIds),
            ];
        }

        return $collections;
    }

    private function get_corrections(stdClass $story): ?array
    {
        if (empty($story->corrections)) {
            return null;
        }

        $corrections = [];
        foreach ($story->corrections as $correction) {
            $correct_id = $this->extract_asset_id($correction->href);
            $correct_current = $story->assets->{$correct_id};
            $corrections[$correct_id] = [
                'date' => date(ee()->config->get('date_format'), strtotime($correct_current->dateTime)),
                'text' => strip_tags($correct_current->text),
            ];
        }

        return $corrections;
    }

    private function get_document($href): ?stdClass
    {
        $pull_url = $this->settings['pull_url'];

        $request = new Api_request();
        $request->base_url = $pull_url;
        $request->params = [];
        $request->path = $href;
        $request->version = '';
        $request->method = 'get';

        $api_service = new Npr_cds_expressionengine();
        $response = $api_service->request($request);

        $json = json_decode($response->raw, false);
        return $json->resources[0];
    }

    private function get_image_url($image)
    {
        if (empty($image->hrefTemplate)) {
            return $image->href;
        }
        // $format = get_option('npr_cds_image_format', 'webp');
        // $quality = get_option('npr_cds_image_quality', 75);
        // $width = get_option('npr_cds_image_width', 1200);
        $format = 'jpeg';
        $quality = 75;
        $width = 1200;
        $parse = parse_url($image->hrefTemplate);
        parse_str($parse['query'], $output);
        foreach ($output as $k => $v) {
            if ($v == '{width}') {
                $output[$k] = $width;
            } elseif ($v == '{format}') {
                $output[$k] = $format;
            } elseif ($v == '{quality}') {
                $output[$k] = $quality;
            }
        }
        return $parse['scheme'] . '://' . $parse['host'] . $parse['path'] . '?' . http_build_query($output);
    }

    private function get_images($story): array
    {
        $images = [];

        foreach ($story->images as $image_ref) {
            $rels = [];

            $asset_id = $this->extract_asset_id($image_ref->href);
            if (!empty($image_ref->rels)) {
                $rels = $image_ref->rels;
            }

            $asset_current = $story->assets->{$asset_id};

            $enclosures = [];
            foreach ($asset_current->enclosures as $enclosure) {
                $data = [
                    'height' => $enclosure->height,
                    'width' => $enclosure->width,
                    'href' => $enclosure->href,
                    'rels' => $enclosure->rels,
                ];

                if (property_exists($enclosure, 'hrefTemplate')) {
                    $data['hrefTemplate'] = $enclosure->hrefTemplate;
                }

                $enclosures[] = $data;
            }

            $images[$asset_id] = [
                'rels' => $rels,
                'caption' => property_exists($asset_current, 'caption') ? $asset_current->caption : '',
                'copyright' => property_exists($asset_current, 'copyright') ? $asset_current->copyright : null,
                'displaySize' => property_exists($asset_current, 'displaySize') ? $asset_current->displaySize : '',
                'title' => property_exists($asset_current, 'title') ? $asset_current->title : '',
                'provider' => property_exists($asset_current, 'provider') ? $asset_current->provider : '',
                'producer' => property_exists($asset_current, 'producer') ? $asset_current->producer : '',
                'providerLink' => property_exists($asset_current, 'providerLink') ? $asset_current->providerLink : '',
                'enclosures' => $enclosures,
            ];
        }

        return $images;
    }

    private function get_video_streaming($asset, $profile): ?array
    {
        if ($asset->isRestrictedToAuthorizedOrgServiceIds === true) {
            return null;
        }

        if ($asset->isEmbeddable === false) {
            return null;
        }

        $closedCaptions = null;
        if (!empty($asset->closedCaptions)) {
            $closedCaptions = [];
            foreach ($asset->closedCaptions as $caption) {
                $closedCaptions[] = $caption->href;
            }
        }

        $video = [
            'title' => !empty($asset->title) ? $asset->title : "Video {$asset->id}",
            'caption' => !empty($asset->caption) ? $asset->caption : null,
            'producer' => !empty($asset->producer) ? $asset->producer : null,
            'provider' => !empty($asset->provider) ? $asset->provider : null,
            'copyright' => !empty($asset->copyright) ? $asset->copyright : null,
            'displaySize' => !empty($asset->displaySize) ? $asset->displaySize : null,
            'duration' => !empty($asset->duration) ? $asset->duration : 1,
            'closedCaptions' => $closedCaptions,
        ];

        if ($profile === 'player-video') {
            $enclosures = [];
            foreach ($asset->enclosures as $enclosure) {
                $data = [
                    'url' => $enclosure->href,
                    'rels' => $enclosure->rels,
                    'type' => $enclosure->type,
                ];

                $enclosures[] = $data;
            }

            $video['enclosures'] = $enclosures;

            if (!empty($asset->images)) {
                foreach ($asset->images as $v_image) {
                    if (in_array('thumbnail', $v_image->rels)) {
                        $v_image_id = $this->extract_asset_id($v_image->href);
                        $video['thumbnail'] = $v_image;
                    }
                }
            }
        } elseif ($profile === 'stream-player-video') {
            if (in_array('hls', $asset->enclosures[0]->rels)) {
                $embed_code = '<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>' .
                '<video id="' . $asset->id . '" controls></video>' .
                '<script>' .
                'let video = document.getElementById("' . $asset->id . '");' .
                'if (Hls.isSupported()) {' .
                'let hls = new Hls();' .
                'hls.attachMedia(video);' .
                'hls.on(Hls.Events.MEDIA_ATTACHED, () => {' .
                'hls.loadSource("' . $asset->enclosures[0]->href . '");' .
                    'hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {' .
                    'console.log("manifest loaded, found " + data.levels.length + " quality level");' .
                    '});' .
                    '});' .
                    '}' .
                    '</script>';

                $video['embed_code'] = $embed_code;
            }
        }

        return $video;
    }

    private function get_video_youtube($asset): array
    {
        $video = [
            'title' => !empty($asset->headline) ? $asset->headline : "YouTube Video Player ($asset->videoId)",
            'startTime' => property_exists($asset, 'startTime') ? $asset->startTime : 0,
            'displaySize' => property_exists($asset, 'displaySize') ? $asset->displaySize : null,
            'videoId' => $asset->videoId,
            'subheadline' => property_exists($asset, 'subheadline') ? $asset->subheadline : null,
        ];

        $video['embed_code'] = '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/' . $asset->videoId . '" title="' . $video['title'] . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';

        return $video;
    }

    private function get_videos($story): array
    {
        $video_refs = $story->videos;
        $videos = [];
        foreach ($video_refs as $ref) {
            $asset_id = $this->extract_asset_id($ref->href);
            $asset_current = $story->assets->{$asset_id};
            $asset_profile = $this->extract_asset_profile($asset_current);

            $video = [];
            switch ($asset_profile) {
                case 'youtube-video':
                    $video = $this->get_video_youtube($asset_current);
                    break;
                case str_contains($asset_profile, 'player-video');
                    $video = $this->get_video_streaming($asset_current, $asset_profile);
                    break;
                default:
                    // no code
                    break;
            }

            $video['profile'] = $asset_profile;
            $videos[$asset_id] = $video;
        }

        return $videos;
    }

    private function parse_credits($asset): string
    {
        $credits = [];
        foreach (['producer', 'provider', 'copyright'] as $item) {
            if (!empty($asset->{$item})) {
                $credits[] = $asset->{$item};
            }
        }
        if (!empty($credits)) {
            return ' (' . implode(' | ', $credits) . ')';
        }
        return '';
    }
}
