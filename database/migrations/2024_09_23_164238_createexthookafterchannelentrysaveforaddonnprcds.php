<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateExtHookAfterChannelEntrySaveForAddonNprCds extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        $addon = ee('Addon')->get('npr_cds');

        $ext = [
            'class' => $addon->getExtensionClass(),
            'method' => 'after_channel_entry_save',
            'hook' => 'after_channel_entry_save',
            'settings' => serialize([]),
            'priority' => 10,
            'version' => $addon->getVersion(),
            'enabled' => 'y'
        ];

        // If we didnt find a matching Extension, lets just insert it
        ee('Model')->make('Extension', $ext)->save();
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
