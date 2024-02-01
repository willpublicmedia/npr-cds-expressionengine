<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Security;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../constants.php';

/**
 * Tools for checking NPR Story API permissions.
 */
class Permissions_checker
{
    /**
     * Makes sure users can access a given method.
     *
     * @access    private
     * @return    void
     */
    public static function can_admin_addons()
    {
        // super admins always have access
        $can_access = ee('Permission')->isSuperAdmin() || ee('Permission')->has('can_admin_addons');

        if (!$can_access) {
            show_error(lang('unauthorized_access'), 403);
        }
    }
}
