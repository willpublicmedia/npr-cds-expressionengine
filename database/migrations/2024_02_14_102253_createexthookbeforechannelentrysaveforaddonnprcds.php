<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateExtHookBeforeChannelEntrySaveForAddonNprCds extends Migration
{
    private $required_extensions = [
        'autofill_media_fields' => [
            'hook' => 'before_channel_entry_save',
            'priority' => 5,
        ],
        'delete_from_cds' => [
            'hook' => 'before_channel_entry_delete',
            'priority' => 10,
        ],
        'pull_story_via_entry_save' => [
            'hook' => 'before_channel_entry_save',
            'priority' => 10,
        ],
        'push_story_via_entry_save' => [
            'hook' => 'before_channel_entry_save',
            'priority' => 15,
        ],
    ];

    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        $addon = ee('Addon')->get('npr_cds');

        foreach ($this->required_extensions as $method => $config) {
            $ext_class = $addon->getExtensionClass();

            $installed = ee('Model')->get('Extension')
                ->filter('class', $ext_class)
                ->filter('method', $method)
                ->filter('hook', $config['hook'])
                ->count() > 0;

            if ($installed) {
                continue;
            }

            $data = [
                'class' => $ext_class,
                'method' => $method,
                'hook' => $config['hook'],
                'settings' => serialize([]),
                'priority' => $config['priority'],
                'version' => $addon->getVersion(),
                'enabled' => 'y'
            ];

            ee('Model')->make('Extension', $data)->save();
        }
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        $addon = ee('Addon')->get('npr_cds');

        ee('Model')->get('Extension')
            ->filter('class', $addon->getExtensionClass())
            ->delete();
    }
}
