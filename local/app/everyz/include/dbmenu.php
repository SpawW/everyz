<?php

/*
 * * Purpose: dinamic add menu item on zabbix interface
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

require_once 'local/app/everyz/include/everyzFunctions.php';
if ($VG_BANCO_OK) {
    $ZBXE_MENU = array(
        'label' => _('Extras'),
        'user_type' => USER_TYPE_ZABBIX_ADMIN,
        'node_perm' => PERM_READ,
        'default_page_id' => 0,
        'pages' => array('')
    );
    $ZBXE_VAR = $ZBXE_SUBMENU = array();
// Get data from DBExtras menu structure
    $res = DBselect('SELECT userid, tx_option, tx_value from zbxe_preferences zpre '
            . ' WHERE userid in (0,' . CWebUser::$data['userid'] . ') and st_ativo = 1 '
            . ' order by userid, tx_option');
    $i = 0;
    while ($row = DBfetch($res)) {
        if (strpos($row['tx_option'], 'menu_') !== false) {
            if (strpos($row['tx_option'], 'submenu_') !== false) {
                $ZBXE_SUBMENU[count($ZBXE_SUBMENU)] = $row['tx_value'];
            } else {
                $tmp = explode("|", $row['tx_value']);
                $ZBXE_MENU['pages'][$i] = array('url' => $tmp[0] . '.php', 'label' => _zeT($tmp[1]));
                $i += 1;
            }
        } else {
            $ZBXE_VAR[$row['tx_option']] = $row['tx_value'];
        }
    }
    $zbx_menu['extras'] = $ZBXE_MENU;
} 



