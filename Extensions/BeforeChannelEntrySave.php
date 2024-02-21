<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

require_once __DIR__ . '/../database/installation/fields/field_installer.php';
require_once __DIR__ . '/../libraries/publishing/npr_cds_expressionengine.php';
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_installer;
use IllinoisPublicMedia\NprCds\Libraries\Dto\Http\Api_request;
use IllinoisPublicMedia\NprCds\Libraries\Publishing\Npr_cds_expressionengine;

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

    public function query_cds($entry, $values)
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

        // WARNING: story pull executes loop. Story may be an array.
        $story = $this->pull_npr_story($npr_story_id);
        if (!$story) {
            return;
        }

        // if (isset($story[0])) {
        //     $story = $story[0];
        // }

        // $objects = $this->map_story_values($entry, $values, $story);
        // $story = $objects['story'];
        // $values = $objects['values'];
        // $entry = $objects['entry'];

        // Flip overwrite value
        $values[$overwrite_field] = false;
        $entry->{$overwrite_field} = false;

        // $story->ChannelEntry = $entry;
        // $story->save();
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
        $field_names = array();
        foreach ($field_array as $model_field) {
            $field = ee('Model')->get('ChannelField')
                ->filter('field_name', $model_field)
                ->first();

            if ($field === null) {
                continue;
            }

            $field_id = $field->field_id;
            $field_names[$model_field] = "field_id_{$field_id}";
        }

        $this->fields = $field_names;
    }

    private function pull_npr_story($npr_story_id)
    {
        $params = array(
            'id' => $npr_story_id,
            // 'dateType' => 'story',
            // 'output' => 'json',
        );

        $pull_url = isset($this->settings['pull_url']) ? $this->settings['pull_url'] : null;

        $request = new Api_request();
        $request->base_url = $pull_url;
        $request->params = $params;
        $request->path = 'documents';
        $request->method = 'get';

        $api_service = new Npr_cds_expressionengine();
        $response = $api_service->request($request);

        if ($response === null || isset($response->messages)) {
            return;
        }

        $api_service->parse($response);

        $stories = array();
        // foreach ($api_service->stories as $story) {
        //     $stories[] = $api_service->save_clean_response($story);
        // }

        return $stories;
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
