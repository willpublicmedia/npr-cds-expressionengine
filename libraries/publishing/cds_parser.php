<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Publishing;

require_once __DIR__ . '/../dto/http/api_response.php';
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_response;

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

    private function create_story(object $json): object
    {
        // see: NPR_CDS_WP->update_posts_from_stories() line 165
        // to do: check story exists && should be updated

        dd($json);
        throw new \Exception('not implemented');
    }
}
