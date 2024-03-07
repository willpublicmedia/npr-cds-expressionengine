<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateExtHookBeforeChannelEntrySaveForAddonNprCds extends Migration
{
    private $required_extensions = array(
        'pull_story_via_entry_save' => array(
            'hook' => 'before_channel_entry_save',
            'priority' => 10,
        ),
        'push_story_via_entry_save' => array(
            'hook' => 'before_channel_entry_save',
            'priority' => 15,
        ),
    );

    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        $addon = ee('Addon')->get('npr_cds');

        foreach ($this->required_extensions as $method => $config) {
            $data = [
                'class' => $addon->getExtensionClass(),
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
