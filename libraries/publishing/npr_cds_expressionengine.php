<?php

namespace IllinoisPublicMedia\NprStoryApi\Libraries\Publishing;

use stdClass;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../configuration/npr_constants.php';

class Npr_cds_expressionengine
{
    public stdClass $request;

    public function __construct()
    {
        $this->request = new stdClass();
        $this->request->method = null;
        $this->request->params = null;
        $this->request->data = null;
        $this->request->path = null;
        $this->request->base = null;
        $this->request->request_url = null;
    }

    public function request($base_url, $params = [], $path = 'documents', $method = 'get')
    {
        $request_url = $this->build_request($params, $path, $base_url, $method);

        // $response = $this->query_by_url($request_url, $method);
        // $this->response = $response;
    }

    private function build_query_params($params)
    {
        $queries = array();
        foreach ($params as $k => $v) {
            $queries[] = "$k=$v";
            $param[$k] = $v;
        }

        return $queries;
    }

    private function build_request($base_url, $params, $path, $method)
    {
        $this->request->params = $params;
        $this->request->path = $path;
        $this->request->base = $base_url;

        $request_url = $this->request->base . '/' . $this->request->path;

        if ($method === 'post') {
            $this->request->postfields = $params['body'];
            unset($params['body']);
        }

        $queries = $this->build_query_params($params);
        $request_url = $request_url . '?' . implode('&', $queries);

        return $request_url;
    }
}
