<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Dto\Http;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../configuration/npr_constants.php';
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;

class Api_request
{
    public ?string $base;

    public ?string $data;

    public string $method;

    public array $params;

    public array $postfields;

    public string $path;

    public ?string $request_url;

    public string $version = Npr_constants::NPR_CDS_VERSION;

    public function __construct()
    {
        $this->method = 'get';
        $this->params = [];
        $this->path = 'documents';
        $this->postfields = [];
    }
}
