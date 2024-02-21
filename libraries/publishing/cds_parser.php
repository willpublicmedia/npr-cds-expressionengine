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
            foreach ($json->resources as $story) {
                $stories[] = $story;
            }
        }

        if (!empty($json->message)) {
            $message = $json->message;
        }

        dd($response);
        throw new \Exception('not implemented');
    }
}
