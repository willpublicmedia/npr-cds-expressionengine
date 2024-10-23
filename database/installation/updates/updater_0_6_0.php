<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../tables/table_loader.php';
require_once __DIR__ . '/../tables/itable.php';
require_once __DIR__ . '/../tables/config_settings_table.php';
require_once __DIR__ . '/../../../libraries/installation/table_installer.php';

use IllinoisPublicMedia\NprCds\Database\Installation\Tables\config_settings_table;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\ITable;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table_loader;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Table_installer;

class Updater_0_6_0
{
    public function update(): bool
    {
        $results = [];
        $results[] = $this->add_push_status_config('npr_cds_settings', 'log_last_push_response');
        $results[] = $this->create_push_status_table('push_status');

        $success = !in_array('false', $results, true);
        if ($success) {
            $this->log_message();
        }

        return $success;
    }

    private function add_push_status_config(string $table_name, string $col_name): bool
    {
        $query_result = ee()->db->query("SHOW COLUMNS FROM `exp_" . $table_name . "` LIKE '" . $col_name . "'")->result_array();
        $column_exists = count($query_result) > 0;

        if ($column_exists) {
            return true;
        }

        $config = new config_settings_table();
        $fields = $config->fields();
        $column_def = [$col_name => $fields[$col_name]];

        ee()->load->dbforge();
        ee()->dbforge->add_column($table_name, $column_def);

        return true;
    }

    private function check_table_exists(string $table_name): bool
    {
        ee()->load->dbutil();
        return ee()->db->table_exists($table_name);
    }

    private function create_push_status_table(string $table_name): bool
    {
        $table_exists = $this->check_table_exists($table_name);

        if ($table_exists) {
            return true;
        }

        $table_data['table_name'] = $this->load_table_config($table_name);

        $installer = new Table_installer();
        $installer->install($table_data);

        return true;
    }

    private function load_table_config(string $table_name): ITable
    {
        $loader = new Table_loader();
        $data = $loader->load($table_name);

        return $data;
    }

    private function log_message()
    {
        ee('CP/Alert')->makeInline('npr-table-create')
            ->asAttention()
            ->withTitle("NPR Data Tables Updated")
            ->addToBody("Added push status table.")
            ->defer();
    }
}
