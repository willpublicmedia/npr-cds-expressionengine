<?php

namespace IllinoisPublicMedia\NprStoryApi\Libraries\Publishing;

require_once __DIR__ . '/../dto/http/api_response.php';
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_response;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Cds_parser
{
    public function parse(Api_response $response)
    {
        dd($response);
        throw new \Exception('not implemented');
    }
}
