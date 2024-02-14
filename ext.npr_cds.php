<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Extension;
use IllinoisPublicMedia\NprCds\Extensions\BeforeChannelEntrySave;

class Npr_cds_ext extends Extension
{
    protected $addon_name = 'npr_cds';

    public function query_cds($entry, $values)
    {
        $runner = new BeforeChannelEntrySave();
        $runner->query_cds($entry, $values);
    }
}
