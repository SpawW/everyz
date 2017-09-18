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
$filter = $fields = [];

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
require_once dirname(__FILE__) . '/include/page_header.php';

addFilterParameter("action", T_ZBX_STR, "dashboard", false, false, false);
addFilterParameter("shorturl", T_ZBX_STR, '', false, false, false);

zbxeTranslateURL();

/* =============================================================================
 * Permissions
  ============================================================================== */

$config = select_config();
//var_dump([$action, $module, $filter["shorturl"]]);
$action = getRequest2("action");
//var_dump([$action, $module, $filter["shorturl"]]);
$module = "dashboard";

if (hasRequest('zbxe_reset_all') && getRequest2('zbxe_reset_all') == "EveryZ ReseT" && $action == "zbxe-config") {
    // Remover tabelas do EveryZ
    // Remover profiles
    try {
        show_message(_zeT('EveryZ configuration back to default factory values! Please click on "EveryZ" menu!'));
        DBexecute(zbxeStandardDML("DROP TABLE `zbxe_preferences` "));
        DBexecute(zbxeStandardDML("DROP TABLE `zbxe_translation` "));
        DBexecute(zbxeStandardDML("DELETE FROM `profiles` where idx like 'everyz%' "));
        $path = str_replace("local/app/views", "local/app/everyz/init", dirname(__FILE__));
        if (!file_exists($path . '/everyz.initdb.php')) {
            $path = dirname(__FILE__) . '/local/app/everyz/init';
        }
        require_once $path . '/everyz.initdb.php';

        exit;
    } catch (Exception $e) {
        
    }
}

$res = DBselect('SELECT userid, tx_option, tx_value from zbxe_preferences zpre '
        . ' WHERE userid in (0,' . CWebUser::$data['userid'] . ') '
        . ' and tx_value like ' . quotestr($action . '|%')
        . ' order by userid, tx_option');
while ($row = DBfetch($res)) {
    $tmp = explode("|", $row['tx_value']);
    $module = $tmp[0];
}
/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
if ($module == "dashboard") {
    zbxeCheckUserLevel(zbxeMenuUserType());
    include_once dirname(__FILE__) . "/local/app/views/everyz.dashboard.view.php";
} else {
    zbxeCheckUserLevel((count($tmp) > 2 ? (int) $tmp[2] : 3));
    $file = dirname(__FILE__) . "/local/app/views/everyz." . $module . ".view.php";
    if (file_exists($file)) {
        include_once $file;
    } else {
        echo "Não existe o arquivo do modulo (" . $module . ")";
    }
}

echo "<!-- Everyz Version - " . EVERYZVERSION . " -->\n";

zbxeFullScreen();
require_once dirname(__FILE__) . '/include/page_footer.php';
