<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once __DIR__ . '/constants.php';

use ExpressionEngine\Service\Addon\Module;
use IllinoisPublicMedia\NprCds\Constants;

class Npr_cds extends Module
{
    public $return_data;

    protected $addon_name = Constants::MODULE_NAME;

    public function __construct()
    {
        parent::__construct();
    }
}
