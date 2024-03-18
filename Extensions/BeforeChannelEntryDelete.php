<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

require_once __DIR__ . '/../libraries/dto/http/api_request.php';
require_once __DIR__ . '/../libraries/publishing/npr_cds_expressionengine.php';
require_once __DIR__ . '/../libraries/utilities/field_utils.php';
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_request;
use IllinoisPublicMedia\NprCds\Libraries\Publishing\Npr_cds_expressionengine;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class BeforeChannelEntryDelete extends AbstractRoute
{
    private $fields = array(
        'channel_entry_source' => null,
        'npr_story_id' => null,
    );

    private $settings = [
        'push_url' => '',
    ];

    public function __construct()
    {
        $this->settings = $this->load_settings();
        $this->map_model_fields(array_keys($this->fields));
    }

    public function delete_from_cds($entry, $values)
    {
        if ($entry->{$this->fields['channel_entry_source']} !== 'local') {
            return;
        }

        $npr_story_id = $entry->{$this->fields['npr_story_id']};

        if (empty($npr_story_id)) {
            return;
        }

        $request = new Api_request();
        $push_url = isset($this->settings['push_url']) ? $this->settings['push_url'] : null;

        $request = new Api_request();
        $request->base_url = $push_url;
        $request->id = $npr_story_id;
        $request->params = [];
        $request->path = 'documents';
        $request->method = 'delete';

        $api_service = new Npr_cds_expressionengine();
        $response = $api_service->request($request);

        return;
    }

    private function load_settings()
    {
        $fields = array_keys($this->settings);

        $settings = ee()->db->select(implode(',', $fields))
            ->limit(1)
            ->get('npr_cds_settings')
            ->result_array();

        if (isset($settings[0])) {
            $settings = $settings[0];
        }

        return $settings;
    }

    private function map_model_fields($field_array)
    {
        $field_utils = new Field_utils();
        $field_names = array();
        foreach ($field_array as $model_field) {
            $field_names[$model_field] = $field_utils->get_field_name($model_field);
        }

        $this->fields = $field_names;
    }
}
