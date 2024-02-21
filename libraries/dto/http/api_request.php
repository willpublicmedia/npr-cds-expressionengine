<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Dto\Http;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../configuration/npr_constants.php';
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;

class Api_request
{
    public ?string $base_url;

    public ?string $data;

    public string $method;

    public array $params;

    public array $postfields;

    public string $path;

    public function request_url(): string
    {
        $request_url = $this->build_request_url();
        return $request_url;
    }

    public string $version = Npr_constants::NPR_CDS_VERSION;

    public function __construct()
    {
        $this->method = 'get';
        $this->params = [];
        $this->path = 'documents';
        $this->postfields = [];
    }

    private function build_request_url(): string
    {
        $request_url = $this->base_url . '/'
        . $this->version . '/'
        . $this->path;

        if (array_key_exists('id', $this->params)) {
            $request_url = $request_url . '/' . $this->params['id'];
        }

        if ($this->method === 'post') {
            $this->postfields = $this->params['body'];
            unset($this->params['body']);
        }

        $queries = $this->build_query_params($this->params);
        $request_url = count($queries) > 0 ?
        $request_url . '?' . implode('&', $queries) :
        $request_url;

        return $request_url;
    }

    private function build_query_params($params)
    {
        $queries = array();
        foreach ($params as $k => $v) {
            if ($k === 'id') {
                continue;
            }
            $queries[] = "$k=$v";
            $param[$k] = $v;
        }

        return $queries;
    }
}
