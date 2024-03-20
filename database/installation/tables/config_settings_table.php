<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Tables;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/table.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table;

class config_settings_table extends Table
{
    protected $_defaults = array(
        'cds_token' => '',
        'document_prefix' => '',
        // 'pull_url' => '',
        // 'push_url' => ''
        'service_id' => null,
        // 'document_prefix' => '',
        'theme_uses_featured_image' => true,
        // 'max_image_width' => 1200,
        // 'image_quality' => 75,
        // 'image_format' => 'jpeg',
        'mapped_channels' => '',
        // 'npr_permissions' => '',
        'npr_image_destination' => '',
    );

    protected $_fields = array(
        'id' => array(
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'auto_increment' => true,
        ),
        'cds_token' => array(
            'type' => 'varchar',
            'constraint' => 64,
        ),
        'document_prefix' => array(
            'type' => 'text',
            'constraint' => 64,
        ),
        'mapped_channels' => array(
            'type' => 'text',
        ),
        'npr_image_destination' => array(
            'type' => 'varchar',
            'constraint' => 64,
        ),
        'pull_url' => array(
            'type' => 'text',
            'constraint' => 128,
        ),
        'push_url' => array(
            'type' => 'text',
            'constraint' => 128,
        ),
        'service_id' => array(
            'type' => 'varchar',
            'null' => true,
            'constraint' => 24,
        ),
        'service_name' => array(
            'type' => 'varchar',
            'null' => true,
            'constraint' => 128,
        ),
        'theme_uses_featured_image' => array(
            'type' => 'tinyint',
        ),
    );

    protected $_keys = array(
        'primary' => 'id',
    );

    protected $_table_name = 'npr_cds_settings';
}
