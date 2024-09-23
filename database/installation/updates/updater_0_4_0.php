<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

require_once __DIR__ . '/../../migrations/2024_09_23_164238_createexthookafterchannelentrysaveforaddonnprcds.php';
use CreateExtHookAfterChannelEntrySaveForAddonNprCds;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Updater_0_4_0
{
    public function update(): void
    {
        $this->add_extensions();
    }

    private function add_extensions()
    {
        $migrator = new CreateExtHookAfterChannelEntrySaveForAddonNprCds();
        $migrator->up();
    }
}
