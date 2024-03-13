<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Publishing;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../configuration/npr_constants.php';
require_once __DIR__ . '/../dto/http/api_response.php';
require_once __DIR__ . '/../dto/http/api_request.php';
require_once __DIR__ . '/cds_parser.php';

use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_request;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_response;

class Npr_cds_expressionengine
{
    public function request(Api_request $request): Api_response
    {
        $response = $this->connect_as_curl($request);
        return $response;
    }

    private function connect_as_curl(Api_request $request)
    {
        $ch = $this->prepare_curl_handle($request);

        $raw = curl_exec($ch);

        //Did an error occur? If so, dump it out.
        if (curl_errno($ch)) {
            $msg = curl_error($ch);

            ee('CP/Alert')->makeInline('entries-form')
                ->asIssue()
                ->withTitle("Unable to connect to NPR Story API")
                ->addToBody($msg)
                ->defer();
        }

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        // may not be necessary
        // $body = json_decode($raw);
        // $is_json = json_last_error() === JSON_ERROR_NONE;
        // $body = $is_json ? $raw : substr($raw, $header_size);

        // parser expects an object, not json string.
        $response = curl_errno($ch) ?
        $this->create_response($raw, $request->request_url(), $http_status, curl_error($ch)) : $this->create_response($raw, $request->request_url(), $http_status, null);

        curl_close($ch);

        if ($http_status != Npr_constants::NPR_CDS_STATUS_OK || $response->code != Npr_constants::NPR_CDS_STATUS_OK) {
            $code = property_exists($response, 'code') ? $response->code : $http_status;
            $message = "Error updating " . $request->request_url();
            if (property_exists($response, 'messages') && !is_null($response->messages)) {
                if (is_string($response->messages)) {
                    $message = $response->messages;
                } elseif (is_array($response->messages) && sizeof($response->messages) > 0) {
                    $message = array_key_exists('message', $response->messages) ? $response->messages[0]['message'] : $response->messages[0];
                }
            }

            ee('CP/Alert')->makeInline('entries-form')
                ->asIssue()
                ->withTitle("NPR API response error: $code")
                ->addToBody($message)
                ->defer();
        }

        return $response;
    }

    private function create_response($raw, $url, $status, $message)
    {
        $response = new Api_response();
        $response->code = $status;
        $response->url = $url;
        $response->raw = $raw;

        if ($message) {
            $response->messages = [$message];
        }

        $json = json_decode($raw);

        if (property_exists($json, 'Message') && !empty($json->Message)) {
            $response->messages[] = $json->Message;
        } else {
            $response->json = $json;
        }

        return $response;
    }

    private function prepare_curl_handle(Api_request $request): \CurlHandle  | false
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $request->request_url());

        $headers = [];

        switch ($request->method) {
            case ($request->method === 'get'):
                break; // empty case for most common/default method.
            case ($request->method === 'put'):
                $put_headers = [
                    'Cache-Control: no-cache',
                    'Content-Type: application/json;charset=UTF-8',
                    'Connection: Keep-Alive',
                    'Vary: Accept-Encoding',
                ];

                array_merge($headers, $put_headers);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $field_count = count($request->params);
                curl_setopt($ch, CURLOPT_PUT, $field_count);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request->postfields));
                break;
            case ($request->method === 'post'):
                $post_headers = [
                    'Content-Type: application/json;charset=UTF-8',
                    'Connection: Keep-Alive',
                    'Vary: Accept-Encoding',
                ];
                array_merge($headers, $post_headers);
                curl_setopt($ch, CURLOPT_HEADER, true);
                $field_count = count($request->params);
                curl_setopt($ch, CURLOPT_POST, $field_count);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $request->postfields);
                break;
            case ($request->method === 'delete'):
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $cds_token = $this->request_auth_token();
        $headers[] = 'Authorization: Bearer ' . $cds_token;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        return $ch;
    }

    private function request_auth_token(): string
    {
        $cds_token = ee()->db->select('cds_token')
            ->limit('1')
            ->get('npr_cds_settings')
            ->result_array();

        if (isset($cds_token[0])) {
            $cds_token = $cds_token[0];
        }

        return $cds_token['cds_token'];
    }
}
