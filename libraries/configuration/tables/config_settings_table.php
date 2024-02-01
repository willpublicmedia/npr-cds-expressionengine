<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Configuration\Tables;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/table.php';
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Tables\Table;

class config_settings_table extends Table
{
    protected $_defaults = array(
        // 'cds_token' => '',
        // 'pull_url' => '',
        // 'push_url' => ''
        'org_id' => null,
        // 'document_prefix' => '',
        // 'theme_uses_featured_image' => false,
        // 'max_image_width' => 1200,
        // 'image_quality' => 75,
        // 'image_format' => 'jpeg',
        'mapped_channels' => '',
        // 'npr_permissions' => '',
    );

    protected $_fields = array(
        'id' => array(
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'auto_increment' => true,
        ),
        // 'api_key' => array(
        //     'type' => 'varchar',
        //     'constraint' => 64
        // ),
        'mapped_channels' => array(
            'type' => 'text',
        ),
        // 'npr_permissions' => array(
        //     'type' => 'varchar',
        //     'constraint' => 256
        // ),
        // 'npr_image_destination' => array(
        //     'type' => 'varchar',
        //     'constraint' => 64
        // ),
        'org_id' => array(
            'type' => 'varchar',
            'null' => TRUE,
            'constraint' => 24
        ),
        // 'pull_url' => array(
        //     'type' => 'varchar',
        //     'constraint' => 64,
        // ),
        // 'push_url' => array(
        //     'type' => 'varchar',
        //     'constraint' => 64
        // )
    );

    protected $_keys = array(
        'primary' => 'id',
    );

    protected $_table_name = 'npr_cds_settings';
}
