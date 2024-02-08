<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation\Tables;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/itable.php';
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\ITable;

class Table implements ITable
{
    protected $_defaults;

    protected $_fields;

    protected $_keys;

    protected $_table_name;

    public function table_name()
    {
        return $this->_table_name;
    }

    public function defaults()
    {
        return $this->_defaults;
    }

    public function fields()
    {
        return $this->_fields;
    }

    public function keys()
    {
        return $this->_keys;
    }
}
