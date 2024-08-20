<?php
namespace IllinoisPublicMedia\NprCds\Libraries\Utilities;

/**
 * Contains common utilities for CDS property manipulation.
 */
class Cds_utils
{
    // load image preferences or use hardcoded
    private $image_preferences = [
        'format' => 'jpeg',
        'quality' => 75,
        'width' => 1200,
    ];

    /**
     * Based on @openpublicmedia/npr-cds-wordpress
     * NPR_CDS_WP->get_image_url()
     *
     * Handles embedded URLs in image hrefs from NPR's CDN.
     */
    public function get_image_url(array | \stdClass $image, $download = false): array
    {
        $image_url = is_array($image) ?
        $this->get_image_url_from_array($image, $download) :
        $this->get_image_url_from_object($image, $download);
        return $image_url;
    }

    private function get_image_url_from_array(array $image, bool $download): array
    {
        if (empty($image['hrefTemplate'])) {
            return $image['href'];
        }

        $parse = parse_url($image['hrefTemplate']);
        if (!empty($parse['query'])) {
            parse_str($parse['query'], $output);
            if (!empty($output['url'])) {
                $parse = parse_url(urldecode($output['url']));
            }
        }

        $path = pathinfo($parse['path']);
        $out = [
            'url' => $image['href'],
            'filename' => $path['filename'] . '.' . $path['extension'],
        ];

        if (empty($image['hrefTemplate'])) {
            return $out;
        }

        $format = $this->image_preferences['format'];
        $quality = $this->image_preferences['quality'];
        $width = $this->image_preferences['width'];

        if ($download) {
            $width = $image['width'];
        }

        $out['url'] = str_replace(['{width}', '{format}', '{quality}'], [$width, $format, $quality], $image['hrefTemplate']);
        if ($format !== $path['extension']) {
            $out['filename'] = $path['filename'] . '.' . $format;
        }

        return $out;
    }

    private function get_image_url_from_object(\stdClass $image, bool $download): array
    {
        if (empty($image->hrefTemplate)) {
            return $image->href;
        }

        $parse = parse_url($image->hrefTemplate);
        if (!empty($parse['query'])) {
            parse_str($parse['query'], $output);
            if (!empty($output['url'])) {
                $parse = parse_url(urldecode($output['url']));
            }
        }

        $path = pathinfo($parse['path']);
        $out = [
            'url' => $image->href,
            'filename' => $path['filename'] . '.' . $path['extension'],
        ];

        if (empty($image->hrefTemplate)) {
            return $out;
        }

        $format = $this->image_preferences['format'];
        $quality = $this->image_preferences['quality'];
        $width = $this->image_preferences['width'];

        if ($download) {
            $width = $image->width;
        }

        $out['url'] = str_replace(['{width}', '{format}', '{quality}'], [$width, $format, $quality], $image->hrefTemplate);
        if ($format !== $path['extension']) {
            $out['filename'] = $path['filename'] . '.' . $format;
        }

        return $out;
    }
}
