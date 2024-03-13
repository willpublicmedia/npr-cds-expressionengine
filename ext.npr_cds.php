<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Extension;
use IllinoisPublicMedia\NprCds\Extensions\BeforeChannelEntrySave;

class Npr_cds_ext extends Extension
{
    protected $addon_name = 'npr_cds';

    public function autofill_media_fields($entry, $values)
    {
        $runner = new BeforeChannelEntrySave();
        $runner->autofill_media_fields($entry, $values);
    }

    public function pull_story_via_entry_save($entry, $values)
    {
        $runner = new BeforeChannelEntrySave();
        $runner->pull_story_via_entry_save($entry, $values);
    }

    public function push_story_via_entry_save($entry, $values)
    {
        $runner = new BeforeChannelEntrySave();
        $runner->push_story_via_entry_save($entry, $values);
    }
}
