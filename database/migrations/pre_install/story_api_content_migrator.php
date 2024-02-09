<?php

namespace IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Story_api_content_migrator
{
    public function migrate()
    {
        $legacy_stories = ee()->db->get('npr_story_api_stories')->result_array();
        // $this->migrate_stories_table($legacy_stories);

        ee('CP/Alert')->makeBanner('story-api-story-migration')
            ->asIssue()
            ->withTitle('Legacy stories not migrated')
            ->addToBody('to do: build models and migrate legacy api stories')
            ->addToBody('found ' . count($legacy_stories) . ' stories')
            ->defer();
    }

    private function migrate_stories_table($legacy_stories): void
    {
        $stories = [];
        foreach ($legacy_stories as $story) {
            $new_story = array(
                'id' => $story['ee_id'],
                'npr_story_id' => $story['id'],
                'entry_id' => $story['entry_id'],
                'title' => $story['title'],
                'subtitle' => $story['subtitle'],
                'teaser' => $story['teaser'],
                'socialTitle' => $story['shortTitle'],
                'shortTeaser' => $story['miniTeaser'],
                'publishedDate' => $story['storyDate'],
                'editorialLastModifiedDate' => $story['pubDate'],
                'recommendUntilDateTime' => $story['audioRunByDate'],
                'keywords' => $story['keywords'],
                'priorityKeywords' => $story['priorityKeywords'],
                'pullQuote' => $story['pullQuote'],
            );

            $stories[] = $new_story;
        }

        ee()->db->insert_batch('exp_npr_cds_documents', $stories);
    }
}
