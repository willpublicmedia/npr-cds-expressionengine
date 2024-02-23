<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Mapping;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../utilities/channel_entry_builder.php';
use IllinoisPublicMedia\NprCds\Libraries\Utilities\Channel_entry_builder;

class Publish_form_mapper
{
    public function map($entry, $values, $story)
    {
        /**
         * @see https://npr.github.io/content-distribution-service/getting-started/story-api-migration-guide/table-of-fields.html
         * [x] title
         * [x] subtitle
         * [x] teaser
         * shortTitle -> socialTitle
         * miniTeaser -> shortTeaser
         * shortTeaser
         * socialTitle
         * contributorText -> shortTeaser
         * thumbnail -> see image
         * slug -> see slug
         * id
         * partnerId -> deprecated
         * link type=api -> deprecated
         * link type=html -> see webpages link
         * storyDate -> publishedDate
         * pubDate -> editorialLastModifiedDate
         * publishedDate -> editorialLastModifiedDate
         * lastModifiedDate -> deprecated
         * audioRunByDate -> recommendUntilDateTime
         * keywords -> deprecated
         * priorityKeywords
         * organization -> see organization
         * parent ->  see parent
         * byline -> see byline
         * text
         * textWithHtml -> text
         * layout -> see layout
         * relatedLink -> see relatedLink
         * htmlAsset -> see htmlAsset
         * multimedia -> see multimedia
         * show -> see show
         * correction -> see correction
         * product -> deprecated
         * promoArt -> deprecated
         * staticGraphic -> deprecated
         * performance -> deprecated
         * fullStory -> deprecated
         * fullText -> text
         * listText -> deprecated
         * message -> deprecated
         * bookEdition deprecated
         * book -> deprecated
         * trait -> deprecated
         * author -> deprecated
         * externalAsset -> deprecated
         * calendarEvent -> deprecated
         * audio -> see audio
         * pullQuote
         * album -> see album
         * artist -> see album
         * transcript -> see transcript
         * story -> see document
         * image -> see image
         */
        $url_title = $this->generate_url_title($entry, $story->title);
        $data = [
            'teaser' => $story->teaser,
            'title' => $story->title,
            'url_title' => $url_title,
        ];

        $entry_builder = new Channel_entry_builder();
        $objects = $entry_builder->assign_data_to_entry($data, $entry, $values);
        $objects['story'] = $story;

        return $objects;
    }

    private function generate_url_title($entry, $story_title)
    {
        $url_title = $entry->isNew() ?
        (string) ee('Format')->make('Text', $story_title)->urlSlug() :
        $entry->url_title;

        if (empty($url_title)) {
            $url_title = $entry->url_title;
        }

        return $url_title;
    }
}
