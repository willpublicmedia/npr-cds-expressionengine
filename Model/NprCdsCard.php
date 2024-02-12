<?php

namespace IllinoisPublicMedia\NprCds\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * The card profile specified a free-form callout content item without any prescription on the content it contains.
 * Cards are most often used to render short representations of content on a page like the NPR homepage.
 *
 * @see https://npr.github.io/content-distribution-service/profiles/card.html
 *
 * @see audio-card
 * @see html-card
 * @see video-carousel-card
 */
class NprCdsCard extends Model
{
    protected static $_primary_key = 'ee_id';

    protected static $_table_name = 'npr_cds_cards';

    protected int $ee_id;

    /**
     * card profile type. currently: audio-card, html-card, video-card
     */
    protected string $cardType;

    /**
     * low, medium, or high
     */
    protected string $emphasisLevel;

    /**
     * The title to display when rendering this card
     */
    protected ?string $title;

    /**
     * An extra label displayed alongside the card; common values are BREAKING or EXCLUSIVE
     */
    protected ?string $attentionLabel;

    /**
     * A short description of the content represented by the card
     */
    protected ?string $teaser;

    /**
     * Flag representing whether the card should be visually highlighted for priority
     */
    protected bool $isHighPriority = false;

    /**
     * String representing a text eyebrow line for a card
     */
    protected ?string $eyebrowText;

    /**
     * Link object representing an external linking eyebrow line for a card. MUST have an href attribute that starts with http or https, and MUST NOT have rels
     */
    protected ?object $eyebrowLink;

    /**
     * Similar to the brandings array defined by the publishable profile, the card profile allows its content to be branded using a set of organization links.
     * The brandings array is subject to the following constraints:
     *   - If present, brandings must contain one or more links
     *   - Links in brandings must start with http or https
     *   - Links in brandings cannot have rels
     */
    protected ?array $brandings;

}
