<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Publishing;

require_once __DIR__ . '/../dto/http/api_response.php';
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_response;
use stdClass;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Cds_parser
{
    public function parse(Api_response $response)
    {
        $stories = [];
        $message = null;
        $json = $response->json;

        if (!empty($json->resources)) {
            foreach ($json->resources as $resource) {
                $story = $this->create_story($resource);
                $stories[] = $story;
            }
        }

        if (!empty($json->message)) {
            $message = $json->message;
        }

        dd($response);
        throw new \Exception('not implemented');
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
    public function add_paragraph_tag(string $p): string
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

    private function create_story(object $resource): object
    {
        // see: NPR_CDS_WP->update_posts_from_stories() line 165
        // to do: check story exists && should be updated

        $cats = [];
        if (empty($resource->body)) {
            $resource->body = null;
        }

        $npr_has_video = false;
        $npr_layout = $this->get_body_with_layout($resource);

        dd($npr_layout);
        throw new \Exception('not implemented');
    }

    private function extract_asset_id($href): bool | string
    {
        $href_xp = explode('/', $href);
        return end($href_xp);
    }

    public function extract_asset_profile($asset): bool | string
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

    /**
     * This function will format the body of the story with any provided assets inserted in the order they are in the layout
     * and return an array of the transformed body and flags for what sort of elements are returned
     *
     * @param stdClass $story Story object created during import
     * @return array with reconstructed body and flags describing returned elements
     */
    private function get_body_with_layout(stdClass $story): array
    {
        $returnary = ['has_image' => false, 'has_video' => false, 'has_external' => false, 'has_slideshow' => false, 'has_video_streaming' => false];
        $body_with_layout = "";

        // todo: add "use featured" setting
        // $use_npr_featured = !empty(get_option('npr_cds_query_use_featured'));
        $use_npr_featured = true;

        $profiles = $this->extract_profiles($story->profiles);

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
                        $body_with_layout .= '<figure class="wp-block-embed npr-promo-card ' . strtolower($asset_current->cardStyle) . '"><div class="wp-block-embed__wrapper">' . (!empty($asset_current->eyebrowText) ? '<h3>' . $asset_current->eyebrowText . '</h3>' : '') .
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
                                $body_with_layout .= '<p><iframe class="npr-embed-audio" style="width: 100%; height: 239px;" src="' . $asset_current->embeddedPlayerLink->href . '"></iframe></p>';
                            } elseif ($asset_current->isDownloadable) {
                                foreach ($asset_current->enclosures as $enclose) {
                                    if ($enclose->type == 'audio/mpeg' && !in_array('premium', $enclose->rels)) {
                                        $body_with_layout .= '[audio mp3="' . $enclose->href . '"][/audio]';
                                    }
                                }
                            }
                        }
                        break;
                    case 'pull-quote':
                        $body_with_layout .= '<blockquote class="npr-pull-quote"><h2>' . $asset_current->quote . '</h2>';
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
                        $body_with_layout .= '<figure class="wp-block-embed is-type-video"><div class="wp-block-embed__wrapper"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $asset_current->videoId . '" title="' . $asset_title . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></figure>';
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
                        $figclass = "wp-block-image size-large";
                        $image_href = $this->get_image_url($thisimg);
                        $fightml = '<img src="' . $image_href . '"';
                        if (in_array('image-vertical', $thisimg->rels)) {
                            $figclass .= ' alignright';
                            $fightml .= " width=200";
                        }
                        $thiscaption = (!empty(trim($asset_current->caption)) ? trim($asset_current->caption) : '');
                        $fightml .= (!empty($fightml) && !empty($thiscaption) ? ' alt="' . str_replace('"', '\'', strip_tags($thiscaption)) . '"' : '');
                        $fightml .= (!empty($fightml) ? '>' : '');
                        $thiscaption .= (!empty($cites) ? " <cite>" . $this->parse_credits($asset_current) . "</cite>" : '');
                        $figcaption = (!empty($fightml) && !empty($thiscaption) ? "<figcaption>$thiscaption</figcaption>" : '');
                        $fightml .= (!empty($fightml) && !empty($figcaption) ? $figcaption : '');
                        $body_with_layout .= (!empty($fightml) ? "<figure class=\"$figclass\">$fightml</figure>\n\n" : '');
                        break;
                    case 'image-gallery':
                        $fightml = '<figure class="wp-block-image"><div class="splide"><div class="splide__track"><ul class="splide__list">';
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
                                $video_asset = '[video mp4="' . $video_url . '"' . $poster . '][/video]';
                            } elseif ($asset_profile == 'stream-player-video') {
                                if (in_array('hls', $asset_current->enclosures[0]->rels)) {
                                    $returnary['has_video_streaming'] = true;
                                    $video_asset = '<video id="' . $asset_current->id . '" controls></video>' .
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
                            $body_with_layout .= '<figure class="wp-block-embed is-type-video"><div class="wp-block-embed__wrapper">' . $video_asset . '</div>' . $full_caption . '</figure>';
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
            $body_with_layout .= '<figure class="wp-block-embed npr-correction"><div class="wp-block-embed__wrapper"><h3>Corrections:</h3><ul>' . $correction_text . '</ul></div></figure>';
        }
        if (!empty($story->audio)) {
            $audio_file = '';
            foreach ($story->audio as $audio) {
                if (in_array('primary', $audio->rels) && !in_array('premium', $audio->rels)) {
                    $audio_id = $this->extract_asset_id($audio->href);
                    $audio_current = $story->assets->{$audio_id};
                    if ($audio_current->isAvailable) {
                        if ($audio_current->isEmbeddable) {
                            $audio_file = '<p><iframe class="npr-embed-audio" style="width: 100%; height: 235px;" src="' . $audio_current->embeddedPlayerLink->href . '"></iframe></p>';
                        } elseif ($audio_current->isDownloadable) {
                            foreach ($audio_current->enclosures as $enclose) {
                                if (!empty($enclose->rels) && $enclose->type == 'audio/mpeg' && !in_array('premium', $enclose->rels)) {
                                    $audio_file = '[audio mp3="' . $enclose->href . '"][/audio]';
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
        $returnary['body'] = htmlspecialchars($body_with_layout);
        return $returnary;
    }

    private function get_document($href): ?stdClass
    {
        // $url = NPR_CDS_PULL_URL . $href;
        // $options = $this->get_token_options();
        // $response = wp_remote_get($url, $options);
        // if (is_wp_error($response)) {
        //     /**
        //      * @var WP_Error $response
        //      */
        //     $code = $response->get_error_code();
        //     $message = $response->get_error_message();
        //     $message = sprintf('Error requesting document via CDS URL: %s (%s [%d])', $url, $message, $code);
        //     error_log($message);
        //     return $response;
        // }
        // $json = json_decode($response['body'], false);
        // return $json->resources[0];
        return new stdClass();
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
