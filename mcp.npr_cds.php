<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/libraries/configuration/config_form_builder.php';
require_once __DIR__ . '/libraries/validation/settings_validator.php';
require_once __DIR__ . '/libraries/configuration/npr_constants.php';
require_once __DIR__ . '/libraries/publishing/npr_cds_expressionengine.php';

use ExpressionEngine\Service\Addon\Mcp;
use IllinoisPublicMedia\NprCds\Constants;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Config_form_builder;
use IllinoisPublicMedia\NprCds\Libraries\Publishing\Npr_cds_expressionengine;
use IllinoisPublicMedia\NprCds\Libraries\Validation\Settings_validator;

/**
 * NPR CDS control panel.
 */
class Npr_cds_mcp extends Mcp
{
    protected $addon_name = Constants::MODULE_NAME;

    private $settings = array();

    private $base_url;

    /**
     * NPR CDS control panel constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $has_permission = ee('Permission')->isSuperAdmin() || ee('Permission')->has('can_admin_addons');
        if (!$has_permission) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->load_settings();
        $this->base_url = ee('CP/URL')->make('addons/settings/npr_cds');
        ee()->load->helper('form');
    }

    /**
     * NPR Story API settings index.
     *
     * @return void
     */
    public function index()
    {
        $validation_results = null;
        if (!empty($_POST)) {
            $validation_results = $this->process_form_data($_POST);

            if ($validation_results->isValid()) {
                $settings = $this->save_settings($_POST, 'npr_cds_settings');
                $this->settings = $settings;
            }
        }

        $builder = new Config_form_builder();
        $form_fields = $builder->build_api_settings_form($this->settings);
        $data = array(
            'base_url' => $this->base_url,
            'cp_page_title' => 'NPR CDS Settings',
            'errors' => $validation_results,
            'save_btn_text' => 'Save Settings',
            'save_btn_text_working' => 'Saving...',
            'sections' => $form_fields,
        );

        return ee('View')->make('ee:_shared/form')->render($data);
    }

    private function get_service_name(string $service_id): string
    {
        $api_service = new Npr_cds_expressionengine();
        $service_name = $api_service->get_service_name($service_id);

        return $service_name;
    }

    private function load_settings()
    {
        // note: this is a different settings load than provided by Config_utils::load_settings.
        $results = ee()->db->get('npr_cds_settings')->result_array();

        $raw = array_pop($results);
        $raw['mapped_channels'] = explode("|", $raw['mapped_channels']);
        $settings = $raw;

        $this->settings = $settings;
    }

    private function process_form_data($form_data)
    {
        $validator = new Settings_validator();
        $rules = Settings_validator::API_SETTINGS_RULES;
        $result = $validator->validate($form_data, $rules);
        return $result;
    }

    private function require_npr_channel($channel_array)
    {
        $npr_channel_id = ee('Model')->get('Channel')
            ->filter('channel_name', 'npr_stories')
            ->fields('channel_id')
            ->first()
            ->channel_id;

        if (!in_array($npr_channel_id, array_values($channel_array))) {
            $channel_array[] = "$npr_channel_id";
        }

        return $channel_array;
    }

    private function save_settings($form_data, $table_name): array
    {
        $changed = false;

        $mapped = $form_data['mapped_channels'];
        $mapped = $this->require_npr_channel($mapped);

        // keep empty values from creeping in
        $mapped = array_filter($mapped);
        $mapped = implode('|', array_values($form_data['mapped_channels']));
        $form_data['mapped_channels'] = $mapped;

        foreach ($form_data as $key => $value) {
            if ($this->settings[$key] != $value) {
                $changed = true;
                break;
            }
        }

        if ($changed == false) {
            return $form_data;
        }

        $service_name = $this->get_service_name($form_data['service_id']);
        $form_data['service_name'] = $service_name;

        $query = ee()->db->
            get($table_name)->
            result_array();
        $old_settings = array_pop($query);

        ee()->db->update($table_name, $form_data, array('id' => $old_settings['id']));

        return $form_data;
    }
}
