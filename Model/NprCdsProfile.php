<?php

namespace IllinoisPublicMedia\NprCds\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * see:
 *   - https://npr.github.io/content-distribution-service/profiles/
 */
class NprCdsProfile extends Model
{
    protected static $_primary_key = 'ee_id';

    protected static $_table_name = 'npr_cds_document_profiles';

    protected static $_relationships = array(
        'NprCdsDocument' => array(
            'type' => 'BelongsTo',
        ),
    );

    protected int $ee_id;

    protected string $npr_id;

    protected string $href;

    protected string $rels;
}
