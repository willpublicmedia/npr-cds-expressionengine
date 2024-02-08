<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Validation;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../configuration/npr_constants.php';
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;

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
        'org_id' => 'maxLength[10]|matchOrgIdEnvironment',
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
        $this->defineOrgIdRule($data);
        $this->validator->setRules($rules);
        $results = $this->validator->validate($data);
        return $results;
    }

    private function defineOrgIdRule($data)
    {
        $this->validator->defineRule('matchOrgIdEnvironment', function ($key, $value, $parameters, $rule) use ($data) {
            $is_valid = false;

            if ($data['pull_url'] === Npr_constants::NPR_STAGING_URL || $data['push_url'] === Npr_constants::NPR_STAGING_URL) {
                $is_valid = preg_match("/^s\d+$/", $data['org_id']) === 1 ? true : "Org ID should begin with 's' when used in staging environments.";
            } else {
                $is_valid = preg_match("/\d+/", $data['org_id']) === 1 ? true : "Org ID should be numerical.";
            }

            return $is_valid;
        });
    }
}
