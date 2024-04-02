<?php

namespace IllinoisPublicMedia\NprCds\Database\Migrations\StoryApi;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Story_api_settings_migrator
{
    public function migrate()
    {
        $legacy_settings = ee()->db->get('npr_story_api_settings')->result_array();

        if (count($legacy_settings) <= 0) {
            return;
        }

        $legacy_settings = $legacy_settings[0];
        $service = $this->find_service_id($legacy_settings['org_id']);

        $data = array(
            'mapped_channels' => $legacy_settings['mapped_channels'],
            'npr_image_destination' => $legacy_settings['npr_image_destination'],
            'service_id' => $service['service_id'],
            'service_name' => $service['service_name'],
        );

        ee()->db->where('id', 1);
        ee()->db->update('npr_cds_settings', $data);

        return;
    }

    private function find_service_id($org_id)
    {
        $url = "https://organization.api.npr.org/v4/legacy/organizations/" . urlencode($org_id);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $raw = curl_exec($ch);

        //Did an error occur? If so, dump it out.
        if (curl_errno($ch)) {
            $msg = curl_error($ch);

            ee('CP/Alert')->makeInline('org-id-conversion')
                ->asIssue()
                ->withTitle("Unable to convert Org ID")
                ->addToBody($msg)
                ->defer();
        }

        curl_close($ch);

        $json = json_decode($raw);

        if (!property_exists($json, 'services')) {
            ee('CP/Alert')->makeInline('org-id-conversion')
                ->asIssue()
                ->withTitle("Service ID not found")
                ->addToBody("Contact NPR Station Services for assistance.")
                ->defer();

            $settings = [
                'service_id' => $org_id,
                'service_name' => null,
            ];

            return $settings;
        }

        $service = $json->services[0];

        $settings = [
            'service_id' => property_exists($service, 'id') ? $service->id : '',
            'service_name' => property_exists($service, 'name') ? $service->name : '',
        ];

        return $settings;
    }
}
