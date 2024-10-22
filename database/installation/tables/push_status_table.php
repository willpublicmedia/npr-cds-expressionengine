<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Tables;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/table.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table;

class push_status_table extends Table
{
    protected $_defaults = [];

    protected $_fields = [
        'id' => [
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'auto_increment' => true,
        ],
        'entry_id' => [
            'type' => 'int',
            'unsigned' => true,
            'null' => false,
        ],
        'last_push' => [
            'type' => 'int',
            'null' => true,
        ],
        'status_code' => [
            'type' => 'int',
            'unsigned' => true,
        ],
        'response' => [
            'type' => 'text',
        ],
    ];

    protected $_keys = [
        'primary' => 'id',
    ];

    protected $_table_name = 'npr_cds_push_status';
}
