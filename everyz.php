<?php

/*
 * * Purpose: Add support for external modules to extend Zabbix native functions
 * * Adail Horst - http://spinola.net.br/blog
 * *
 * * This program is free software; you can redistribute it and/or modify
 * * it under the terms of the GNU General Public License as published by
 * * the Free Software Foundation; either version 2 of the License, or
 * * (at your option) any later version.
 * *
 * * This program is distributed in the hope that it will be useful,
 * * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * * GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License
 * * along with this program; if not, write to the Free Software
 * * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * */


// Global definitions ==========================================================
require_once dirname(__FILE__) . '/include/config.inc.php';

//define('ZBX_PAGE_DO_REFRESH', 1);
/**
 * Base path to profiles on Zabbix Database
 */
$baseProfile = "everyz.";

$page['title'] = _('EveryZ');
$page['file'] = 'everyz.php';

switch (getRequest('format')) {
    case PAGE_TYPE_CSV:
        $page['file'] = 'everyz_export.csv';
        $page['type'] = detect_page_type(PAGE_TYPE_CSV);
        break;
    case PAGE_TYPE_JSON:
        $page['file'] = 'everyz_export.json';
        $page['type'] = detect_page_type(PAGE_TYPE_CSV);
        break;
    default:
        $page['type'] = detect_page_type(PAGE_TYPE_HTML);
        ?>
<link href="local/app/everyz/css/everyz.css" rel="stylesheet" type="text/css" id="skinSheet">
        <?php
        break;
}
$page['scripts'] = array('class.calendar.js', 'multiselect.js', 'gtlc.js');

$filter = $fields = [];

require_once dirname(__FILE__) . '/include/page_header.php';

addFilterParameter("action", T_ZBX_STR, "dashboard");

/* =============================================================================
 * Permissions
  ============================================================================== */

$config = select_config();
$action = getRequest2("action");
$module = "dashboard";
$res = DBselect('SELECT userid, tx_option, tx_value from zbxe_preferences zpre '
        . ' WHERE userid in (0,' . CWebUser::$data['userid'] . ') and st_ativo = 1 '
        . ' and tx_value like "' . $action . '|%" '
        . ' order by userid, tx_option');
while ($row = DBfetch($res)) {
    $tmp = explode("|", $row['tx_value']);
    $module = $tmp[0];
}
if ($module == "dashboard") {
    include_once dirname(__FILE__) . "/local/app/views/everyz.dashboard.view.php";
} else {
    $file = dirname(__FILE__) . "/local/app/views/everyz." . $module . ".view.php";
    if (file_exists($file)) {
        include_once $file;
    } else {
        echo "nao existe o arquivo do modulo (" . $module . ")";
    }
}

require_once dirname(__FILE__) . '/include/page_footer.php';
