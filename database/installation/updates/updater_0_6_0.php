<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Updates;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../tables/table_loader.php';
require_once __DIR__ . '/../tables/itable.php';
require_once __DIR__ . '/../../../libraries/installation/table_installer.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\ITable;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table_loader;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Table_installer;

class Updater_0_6_0
{
    private $table_name = 'push_status';

    public function update(): bool
    {
        $table_exists = $this->check_table_exists($this->table_name);

        if ($table_exists) {
            return true;
        }

        $success = $this->create_push_status_table($this->table_name);
        if ($success) {
            $this->log_message();
        }

        return $success;
    }

    private function check_table_exists(string $table_name): bool
    {
        ee()->load->dbutil();
        return ee()->db->table_exists($table_name);
    }

    private function create_push_status_table(string $table_name): bool
    {
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
