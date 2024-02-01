<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Validation;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * Tools for validating NPR CDS form data.
 */
class Settings_validator
{
    /**
     * Default validation rules for NPR CDS settings.
     */
    public const API_SETTINGS_RULES = array(
        'cds_token' => 'required|maxLength[64]|alphaNumeric',
        // 'api_key' => 'required|maxLength[64]|alphaNumeric',
        // 'pull_url' => 'url|maxLength[64]',
        // 'push_url' => 'url|maxLength[64]',
        'org_id' => 'maxLength[10]|numeric',
        // 'npr_permissions' => 'maxLength[256]|alphaNumeric',
    );

    /**
     * Validate form values.
     *
     * @param  mixed $data Form data.
     * @param  mixed $rules Validation rules.
     *
     * @return mixed Validation object.
     */
    public function validate($data, $rules)
    {
        $results = ee('Validation')->make($rules)->validate($data);
        return $results;
    }
}
