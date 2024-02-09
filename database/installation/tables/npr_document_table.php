<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Tables;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/table.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table;

/**
 * See https://content.api.npr.org/v1/profiles/document
 */
class npr_document_table extends Table
{
    protected $_defaults = array();

    protected $_fields = array(
        'ee_id' => array(
            'type' => 'int',
            'constraint' => 64,
            'unsigned' => true,
            'auto_increment' => true,
        ),
        'npr_id' => array(
            'type' => 'varchar',
            'constraint' => 48,
        ),
        'entry_id' => array(
            'type' => 'int',
            'constraint' => 10,
        ),
        'title' => array(
            'type' => 'varchar',
            'constraint' => 512,
        ),
        'subtitle' => array(
            'type' => 'varchar',
            'constraint' => 1024,
        ),
        'teaser' => array(
            'type' => 'varchar',
            'constraint' => '4096',
        ),
        'shortTeaser' => array(
            'type' => 'varchar',
            'constraint' => 2048,
        ),
        'socialTitle' => array(
            'type' => 'varchar',
            'constraint' => 256,
        ),
        'publishedDate' => array(
            'type' => 'datetime',
        ),
        'editorialLastModifiedDate' => array(
            'type' => 'datetime',
        ),
        'lastModifiedDate' => array(
            'type' => 'datetime',
        ),
        'recommendUntilDateTime' => array(
            'type' => 'datetime',
        ),
        'keywords' => array(
            'type' => 'varchar',
            'constraint' => 4096,
        ),
        'priorityKeywords' => array(
            'type' => 'varchar',
            'constraint' => 4096,
        ),
        'pullQuote' => array(
            'type' => 'varchar',
            'constraint' => 4096,
        ),
    );

    protected $_keys = array(
        'primary' => 'id',
        // 'foreign' => 'organization_id',
    );

    protected $_table_name = 'npr_cds_documents';
}
