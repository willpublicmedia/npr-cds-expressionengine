<?php

namespace IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Story_api_content_migrator
{
    public function migrate()
    {
        ee('CP/Alert')->makeBanner('story-api-story-migration')
            ->asIssue()
            ->withTitle('Legacy stories not migrated')
            ->addToBody('to do: build models and migrate legacy api stories')
            ->defer();
    }
}
