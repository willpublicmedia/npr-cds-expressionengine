<?php

namespace IllinoisPublicMedia\NprCds\Model;

use DateTime;
use ExpressionEngine\Service\Model\Model;

/**
 * An audio document represents an audio resource. Audio resources contain 0 or more links to audio files themselves, as well as metadata about the audio and its intended use.
 * The audio profile is intended to be used as “type” profile.
 *
 * @see https://npr.github.io/content-distribution-service/profiles/audio.html
 */
class NprCdsAudio extends Model
{
    protected static $_primary_key = 'ee_id';

    protected static $_table_name = 'npr_cds_audio';

    protected static $_relationships = array(
        'NprCdsDocument' => array(
            'type' => 'BelongsTo',
        ),
    );

    protected int $ee_id;

    /**
     * An array of links to the audio files themselves. See the “Enclosures” section below.
     */
    protected array $enclosures;

    /**
     * Is this audio available? This will be false for audio that has not been fully processed yet.
     */
    protected bool $isAvailable;

    /**
     * Should this audio be available for download?
     */
    protected bool $isDownloadable;

    /**
     * Should this audio be available for rendering in an embedded player?
     */
    protected bool $isEmbeddable;

    /**
     * Should this audio be available for streaming?
     */
    protected bool $isStreamable;

    /**
     * optional: The title of the audio
     */
    protected ?string $title;

    /**
     * optional: The duration of this audio in seconds (if known); this value must be >= 1.
     */
    protected ?int $duration;

    /**
     * optional: What message should be displayed based on this audio’s availability? (ex: “Audio will become available later today”)
     */
    protected ?string $availabilityMessage;

    /**
     * optional: If this audio contains a song, the title of the song
     */
    protected ?string $songTitle;

    /**
     * optional: If this audio contains a song, the artist of the song
     */
    protected ?string $songArtist;

    /**
     * optional: If this audio contains a song, the number of the track in the collection this song appears in
     */
    protected ?int $songTrackNumber;

    /**
     * optional: If this audio contains a song, the title of the album it appears on
     */
    protected ?string $albumTitle;

    /**
     * optional: If this audio contains a song, the artist of the album it appears on
     */
    protected ?string $albumArtist;

    /**
     * optional: The date and time at which this stream should no longer be served
     */
    protected ?DateTime $streamExpirationDateTime;

    /**
     * optional:  A link to a CDS document representing the transcript of this audio; this link is not guaranteed valid. Clients should follow the link to confirm the document’s existence before rendering. This link must contain an extension rel, and can optionally have an always-display rel. These strings are to be contained in the rels property array.
     */
    protected ?string $transcriptLink;

    /**
     * optional: A link to a web page displaying the transcript of this audio. This link cannot have rels.
     */
    protected ?string $transcriptWebPageLink;

    /**
     * optional: A link to a web page displaying an embedded audio player for this audio. This link cannot have rels.
     */
    protected ?string $embeddedPlayerLink;
}
