<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Validation;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../configuration/npr_constants.php';

/**
 * Tools for validating NPR CDS form data.
 */
class Settings_validator
{
    private $validator;

    public function __construct($validator = null)
    {
        $this->validator = is_null($validator) ? ee('Validation')->make() : $validator;
    }

    /**
     * Default validation rules for NPR CDS settings.
     */
    public const API_SETTINGS_RULES = array(
        'cds_token' => 'required|maxLength[64]|regex[/[-\w]+/]',
        'document_prefix' => 'required|maxLength[64]|alphaNumeric',
        'service_id' => 'maxLength[10]|alphaNumeric',
        'service_name' => 'maxLength[128]',
        // 'api_key' => 'required|maxLength[64]|alphaNumeric',
        'pull_url' => 'url|maxLength[128]',
        'push_url' => 'url|maxLength[128]',
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
        $this->validator->setRules($rules);
        $results = $this->validator->validate($data);
        return $results;
    }
}
