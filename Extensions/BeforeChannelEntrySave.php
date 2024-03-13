<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

require_once __DIR__ . '/../database/installation/fields/field_installer.php';
require_once __DIR__ . '/../libraries/publishing/npr_cds_expressionengine.php';
require_once __DIR__ . '/../libraries/utilities/field_utils.php';
require_once __DIR__ . '/../libraries/mapping/publish_form_mapper.php';
require_once __DIR__ . '/../libraries/mapping/cds_mapper.php';

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_installer;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_request;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_response;
use IllinoisPublicMedia\NprCds\Libraries\Mapping\Cds_mapper;
use IllinoisPublicMedia\NprCds\Libraries\Mapping\Publish_form_mapper;
use IllinoisPublicMedia\NprCds\Libraries\Publishing\Npr_cds_expressionengine;
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Field_utils;

class BeforeChannelEntrySave extends AbstractRoute
{
    private $fields = array(
        'audio_files' => null,
        'channel_entry_source' => null,
        'npr_images' => null,
        'npr_story_id' => null,
        'overwrite_local_values' => null,
        'publish_to_npr' => null,
    );

    private $settings = [
        'document_prefix' => '',
        'pull_url' => '',
        'push_url' => '',
        'service_id' => null,
        // 'theme_uses_featured_image' => false,
        // 'max_image_width' => 1200,
        // 'image_quality' => 75,
        // 'image_format' => 'jpeg',
        'mapped_channels' => '',
        // 'npr_permissions' => '',
        'npr_image_destination' => '',
    ];

    public function __construct()
    {
        $this->settings = $this->load_settings();
        $this->map_model_fields(array_keys($this->fields));
    }

    public function pull_story_via_entry_save($entry, $values)
    {
        $source_field = $this->fields['channel_entry_source'];
        $is_external_story = array_key_exists($source_field, $values) ? $this->check_external_story_source($values[$source_field]) : false;
        $overwrite_field = $this->fields['overwrite_local_values'];
        $overwrite = array_key_exists($overwrite_field, $values) ? $values[$overwrite_field] : false;

        // WARNING: check for push stories!
        if (!$is_external_story || !$overwrite) {
            return;
        }

        $abort = false;

        $is_mapped_channel = $this->check_mapped_channel($entry->channel_id);
        if ($is_mapped_channel === false) {
            $abort = true;
        }

        $has_required_fields = $this->check_required_fields($entry->Channel->FieldGroups);
        if ($has_required_fields === false) {
            $abort = true;
        }

        if ($abort === true) {
            return;
        }

        $id_field = $this->fields['npr_story_id'];
        $npr_story_id = $values[$id_field];

        $result = $this->validate_story_id($entry, $values);
        if ($result instanceof ValidationResult) {
            if ($result->isNotValid()) {
                return $this->display_error($result);
            }
        }

        $response = $this->pull_npr_story($npr_story_id);
        if (is_null($response) || isset($response->messages)) {
            return;
        }

        $stories = $response->json->resources;
        if (count($stories) > 1) {
            $error = [
                'npr_story_id' => [
                    'Story ID represents a document collection. Select a single document ID and try again.',
                ],
            ];
            $this->display_error($error);
            return;
        }

        $objects = $this->map_story_values($entry, $values, $stories[0]);

        $story = $objects['story'];
        $values = $objects['values'];
        $entry = $objects['entry'];

        // Flip overwrite value
        $values[$overwrite_field] = false;
        $entry->{$overwrite_field} = false;

        // currently no cds model in this plugin
        // $story->ChannelEntry = $entry;
        // $story->save();
    }

    public function push_story_via_entry_save($entry, $values)
    {
        $push_field = $this->fields['publish_to_npr'];
        $push_story = array_key_exists($push_field, $values) ? $values[$push_field] : false;

        if (!$push_story) {
            return;
        }

        $abort = false;

        $is_mapped_channel = $this->check_mapped_channel($entry->channel_id);
        if ($is_mapped_channel === false) {
            $abort = true;
        }

        $has_required_fields = $this->check_required_fields($entry->Channel->FieldGroups);
        if ($has_required_fields === false) {
            $abort = true;
        }

        // move api key check to api query setup
        // $api_key = isset($this->settings['api_key']) ? $this->settings['api_key'] : '';
        // if ($api_key === '') {
        //     $abort = true;
        //     ee('CP/Alert')->makeInline('story-push-api-key')
        //         ->asIssue()
        //         ->withTitle('NPR Stories')
        //         ->addToBody("No API key set. Can't push story.")
        //         ->defer();
        // }

        $push_url = isset($this->settings['push_url']) ? $this->settings['push_url'] : null;
        if ($push_url === null) {
            $abort = true;
            ee('CP/Alert')->makeInline('story-push-push-url')
                ->asIssue()
                ->withTitle('NPR Stories')
                ->addToBody("No push url set. Can't push story.")
                ->defer();
        }

        if ($abort) {
            return;
        }

        $json = $this->create_json($entry, $values);

        if ($json === false) {
            ee('CP/Alert')->makeInline('story-json-encode')
                ->asError()
                ->withTitle('NPR Stories')
                ->addToBody("A JSON error occurred while preparing the entry for distribution.")
                ->defer();

            return;
        }

        $response = $this->push_story($json);
        if (is_null($response)) {
            ee('CP/Alert')->makeInline('story-push')
                ->asIssue()
                ->withTitle('NPR CDS')
                ->addToBody("Error pushing to NPR")
                ->defer();
            return;
        }

        if (!empty($response->messages)) {
            $message = $response->messages[0];
            ee('CP/Alert')->makeInline('story-push')
                ->asIssue()
                ->withTitle('NPR CDS')
                ->addToBody($message)
                ->defer();
        }

        // assign story id if not present
        if ($entry->{$this->fields['npr_story_id']} === '') {
            $response_items = $this->process_push_response($entry);
            $npr_story_id = $response_items['npr_story_id'];
            $entry->{$this->fields['npr_story_id']} = $npr_story_id;
        }

        ee('CP/Alert')->makeInline('story-push')
            ->asSuccess()
            ->withTitle('NPR Stories')
            ->addToBody("Story pushed to NPR.")
            ->defer();
    }

    private function check_required_fields($field_groups, $display_error = true)
    {
        foreach ($field_groups as $group) {
            if ($group->group_name === Field_installer::DEFAULT_FIELD_GROUP['group_name']) {
                return true;
            }

            if ($group->group_name === Field_installer::LEGACY_FIELD_GROUP['group_name']) {
                ee('CP/Alert')->makeInline('legacy-field-group')
                    ->asWarning()
                    ->withTitle('NPR CDS Mapping Issue')
                    ->addToBody('Legacy Story API fields detected.')
                    ->addToBody('Channel should use the ' . Field_installer::DEFAULT_FIELD_GROUP['group_name'] . ' field group for accurate content mapping.')
                    ->defer();

                return true;
            }
        }

        if ($display_error) {
            ee('CP/Alert')->makeInline('story-push-missing-fields')
                ->asIssue()
                ->withTitle('NPR Stories Mapping Error')
                ->addToBody('Channel must use the ' . Field_installer::DEFAULT_FIELD_GROUP['group_name'] . ' field group.')
                ->defer();
        }

        return false;
    }

    private function check_external_story_source($story_source)
    {
        if (is_null($story_source) || $story_source == 'local') {
            return false;
        }

        return true;
    }

    private function check_mapped_channel($channel_id, $display_error = true)
    {
        $results = ee()->db->
            select('mapped_channels')->
            from('npr_story_api_settings')->
            get()->
            result_array();

        $mapped_channels = (array_pop($results))['mapped_channels'];
        $mapped_channels = explode("|", $mapped_channels);

        $is_mapped = in_array($channel_id, $mapped_channels);

        if (!$is_mapped && $display_error) {
            ee('CP/Alert')->makeInline('story-push-not-mapped')
                ->asIssue()
                ->withTitle('NPR CDS Mapping Error')
                ->addToBody('Channel not mapped to CDS data. See NPR CDS addon settings in control panel.')
                ->defer();
        }

        return $is_mapped;
    }

    private function create_json(ChannelEntry $entry, array $values)
    {
        $parser = new Cds_mapper();
        $json = $parser->create_json($entry, $values, 'document');

        return $json;
    }

    private function display_error($errors)
    {
        foreach ($errors->getAllErrors() as $field => $results) {
            $alert = ee('CP/Alert')->makeInline('entries-form')
                ->asIssue()
                ->withTitle('NPR Story save error.');

            foreach ($results as $message) {
                $alert->addToBody($message);
            }

            $alert->defer();
        }
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

    private function map_story_values($entry, $values, $story): array
    {
        $mapper = new Publish_form_mapper();
        $objects = $mapper->map($entry, $values, $story);

        return $objects;
    }

    private function process_push_response(ChannelEntry $entry): array
    {
        $items = [];

        // this is overkill, but ensures that entry and outgoing json get the same ID.
        $mapper = new Cds_mapper();
        $items['npr_story_id'] = $mapper->create_story_id($entry);

        return [];
    }

    private function pull_npr_story($npr_story_id): ?Api_response
    {
        $params = array(
            'id' => $npr_story_id,
        );

        $pull_url = isset($this->settings['pull_url']) ? $this->settings['pull_url'] : null;

        $request = new Api_request();
        $request->base_url = $pull_url;
        $request->params = $params;
        $request->path = 'documents';
        $request->method = 'get';

        $api_service = new Npr_cds_expressionengine();
        $response = $api_service->request($request);

        return $response;
    }

    private function push_story(string $json): ?Api_response
    {
        $params = [
            'json' => $json,
        ];

        $push_url = isset($this->settings['push_url']) ? $this->settings['push_url'] : null;

        $request = new Api_request();
        $request->base_url = $push_url;
        $request->params = $params;
        $request->path = 'documents';
        $request->method = 'put';

        $api_service = new Npr_cds_expressionengine();
        $response = $api_service->request($request);

        return $response;
    }

    private function validate_story_id($entry, $values)
    {

        $validator = ee('Validation')->make();
        $validator->defineRule('uniqueStoryId', function ($key, $value, $parameters) use ($entry) {
            $id_field = $this->fields['npr_story_id'];

            $query = ee('Model')->get('ChannelEntry')->filter($id_field, $value);
            $count = $query->count();

            if ($count === 0) {
                return true;
            }

            $owner_entry = $query->first()->entry_id;

            if ($owner_entry === $entry->entry_id) {
                return true;
            }

            return "An NPR story with ID $value has already been created. Content rejected.";
        });

        $validator->setRules(array(
            $this->fields['npr_story_id'] => 'uniqueStoryId',
        ));

        $result = $validator->validate($values);
        return $result;
    }
}
