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
    public function get_service_name(string $service_id): string
    {
        $url = "https://organization.api.npr.org/v4/services/" . urlencode($service_id);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $raw = curl_exec($ch);

        //Did an error occur? If so, dump it out.
        if (curl_errno($ch)) {
            $msg = curl_error($ch);

            ee('CP/Alert')->makeInline('service-name-lookup-failure')
                ->asIssue()
                ->withTitle("Failed to look up service name")
                ->addToBody($msg)
                ->defer();

            return '';
        }

        curl_close($ch);

        $json = json_decode($raw);

        if (!property_exists($json, 'name')) {
            ee('CP/Alert')->makeInline('service-name-not-found')
                ->asIssue()
                ->withTitle("Service Name not found")
                ->addToBody("Contact NPR Station Services for assistance.")
                ->defer();

            return '';
        }

        $service_name = $json->name;

        return $service_name;
    }

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

        $body = json_decode($raw);
        $is_json = json_last_error() === JSON_ERROR_NONE;
        $body = $is_json ? $raw : substr($raw, $header_size);

        // parser expects an object, not json string.
        $response = null;
        if (curl_errno($ch) || !str_starts_with($http_status, 2)) {
            $response = $this->create_response($body, $request->request_url(), $http_status, curl_error($ch));
        } else {
            $response = $this->create_response($raw, $request->request_url(), $http_status, null);
        }

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

        if (!$json) {
            $message = str_starts_with($status, 2) ? "Story pushed successfully." : "Something went wrong. HTTP status code $status.";
            $response->messages = [$message];
            return $response;
        }

        if (property_exists($json, 'meta') && !empty($json->meta)) {
            $response->messages[] = $json->meta->messages[0];
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
                $body = $request->data;

                $put_headers = [
                    'Cache-Control: no-cache',
                    'Content-Type: application/json;charset=UTF-8',
                    'Content-Length: ' . strlen($body),
                    'Connection: Keep-Alive',
                    'Vary: Accept-Encoding',
                ];

                array_merge($headers, $put_headers);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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
