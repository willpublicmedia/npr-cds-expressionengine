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
        $this->request->version = null;
        $this->request->request_url = null;
    }

    public function request($base_url, $version = 'v1', $params = [], $path = 'documents', $method = 'get')
    {
        $request_url = $this->build_request($base_url, $version, $params, $path, $method);

        $response = $this->query_by_url($request_url, $method);
        // $this->response = $response;
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

    private function build_request($base_url, $version, $params, $path, $method): string
    {
        $this->request->params = $params;
        $this->request->path = $path;
        $this->request->base = $base_url;
        $this->request->version = $version;

        $request_url = $this->request->base . '/'
        . $this->request->version . '/'
        . $this->request->path;

        if (array_key_exists('id', $params)) {
            $request_url = $request_url . '/' . $params['id'];
        }

        if ($method === 'post') {
            $this->request->postfields = $params['body'];
            unset($params['body']);
        }

        $queries = $this->build_query_params($params);
        $request_url = count($queries) > 0 ?
        $request_url . '?' . implode('&', $queries) :
        $request_url;

        $this->request->request_url = $request_url;

        return $request_url;
    }

    private function connect_as_curl($url, $method)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $headers = [];

        if ($method === 'post') {
            $post_headers = [
                'Content-Type: application/json;charset=UTF-8',
                'Connection: Keep-Alive',
                'Vary: Accept-Encoding',
            ];
            array_merge($headers, $post_headers);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $field_count = count($this->request->params);
            curl_setopt($ch, CURLOPT_POST, $field_count);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request->postfields);
        }

        if ($method === 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $cds_token = $this->request_auth_token();
        $headers[] = 'Authorization: Bearer ' . $cds_token;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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

        $is_json = json_validate($raw);
        $body = $is_json ? $raw : substr($raw, $header_size);

        // // parser expects an object, not xml string.
        // $response = curl_errno($ch) ? $this->create_error_response(curl_error($ch), $url) : $this->convert_response($body, $url);

        // curl_close($ch);

        // if ($http_status != self::NPRAPI_STATUS_OK || $response->code != self::NPRAPI_STATUS_OK) {
        //     $code = property_exists($response, 'code') ? $response->code : $http_status;
        //     $message = "Error updating $url";
        //     if (property_exists($response, 'messages')) {
        //         if (is_string($response->messages)) {
        //             $message = $response->messages;
        //         } elseif (is_array($response->messages) && sizeof($response->messages) > 0) {
        //             $message = array_key_exists('message', $response->messages) ? $response->messages[0]['message'] : $response->messages[0];
        //         }
        //     }

        //     ee('CP/Alert')->makeInline('entries-form')
        //         ->asIssue()
        //         ->withTitle("NPR API response error: $code")
        //         ->addToBody($message)
        //         ->defer();
        // }

        // return $response;
    }

    private function query_by_url($url, $method): void
    {
        /** Begin wp function */
        // //fill out the $this->request->param array so we can know what params were sent
        // $parsed_url = parse_url( $url );
        // if ( !empty( $parsed_url['query'] ) ) {
        //     $params = explode( '&', $parsed_url['query'] );
        //     if ( !empty( $params ) ) {
        //         foreach ( $params as $p ){
        //             $attrs = explode( '=', $p );
        //             $this->request->param[ $attrs[0] ] = $attrs[1];
        //         }
        //     }
        // }
        // $options = $this->get_token_options();
        // $response = wp_remote_get( $url, $options );
        // if ( !is_wp_error( $response ) ) {
        //     $this->response = $response;
        //     if ( $response['response']['code'] == self::NPR_CDS_STATUS_OK ) {
        //         if ( $response['body'] ) {
        //             $this->json = $response['body'];
        //         } else {
        //             $this->notice[] = __( 'No data available.', 'npr-content-distribution-service' );
        //         }
        //     } else {
        //         npr_cds_show_message( 'An error occurred pulling your story from the NPR CDS.  The CDS responded with message = ' . $response['response']['message'], TRUE );
        //     }
        // } else {
        //     $error_text = '';
        //     if ( !empty( $response->errors['http_request_failed'][0] ) ) {
        //         $error_text = '<br> HTTP Error response =  ' . $response->errors['http_request_failed'][0];
        //     }
        //     npr_cds_show_message( 'Error pulling story for url=' . $url . $error_text, TRUE );
        //     npr_cds_error_log( 'Error retrieving story for url=' . $url );
        // }
        /** end wp function */

        $this->request->request_url = $url;

        $response = $this->connect_as_curl($url, $method);
        // if (isset($response->messages)) {
        //     return;
        // }

        // if ($response->body) {
        //     $this->xml = $response->body;
        // } else {
        //     $this->notice[] = 'No data available.';
        // }

        // return $response;
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
