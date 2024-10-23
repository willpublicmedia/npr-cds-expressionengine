<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

require_once __DIR__ . '/../libraries/utilities/config_utils.php';
require_once __DIR__ . '/../libraries/dto/http/api_request.php';
require_once __DIR__ . '/../libraries/publishing/npr_cds_expressionengine.php';
require_once __DIR__ . '/../libraries/utilities/field_utils.php';
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Npr_constants;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_request;
use IllinoisPublicMedia\NprCds\Libraries\Publishing\Npr_cds_expressionengine;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Config_utils;
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
        $this->settings = Config_utils::load_settings(array_keys($this->settings));
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

        $story_id_field = $this->fields['npr_story_id'];
        $document_id = $entry->{$story_id_field};

        Config_utils::log_push_results('entry', $entry->entry_id, $document_id, $response);

        $alert = ee('CP/Alert')->makeInline('npr-delete');
        if ($response->code === Npr_constants::NPR_CDS_DELETE_OK) {
            $alert = $alert->asSuccess()
                ->withTitle('NPR CDS')
                ->addToBody('Document ' . $document_id . ' successfully deleted from CDS.');
        } else {
            $alert = $alert->asIssue()
                ->withTitle('NPR CDS')
                ->addToBody('Document ' . $document_id . ' not deleted from CDS.');
        }

        $alert->defer();

        return;
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
