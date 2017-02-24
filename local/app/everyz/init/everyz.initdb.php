<?php

/*
 * * Purpose: Initialize EveryZ DB
 * * Adail Horst - http://spinola.net.br/blog | adail@spinola.net.br
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
if (php_sapi_name() == "cli") {
    // In cli-mode
    $path = str_replace('/local/app/everyz/init', "", dirname(__FILE__));
    require_once $path . '/include/config.inc.php';
    global $VG_INSTALL;
    $VG_INSTALL = true;
    require_once $path . '/local/app/everyz/include/everyzFunctions.php';
    $VG_BANCO_OK = false;
}

/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
$VG_DEBUG = true;
$PATH = realpath(dirname(__FILE__));

/* * ***************************************************************************
 * DML commands
 * ************************************************************************** */

try {
    if (!$VG_BANCO_OK) {
        // se tabelas na versão anterior do zabbix extras existirem, remover....
        $oldZE = DBfetch(DBselect('select tx_value from zbxe_preferences WHERE tx_option = ' . quotestr("logo_company")));
        DBexecute(zbxeStandardDML("DROP TABLE `zbxe_preferences` "));
        DBexecute(zbxeStandardDML("DROP TABLE `zbxe_translation` "));
        DBexecute(zbxeStandardDML("delete from images where name like 'logo_%' or name like 'zbxe_%' "));

        zbxeErrorLog(true, 'EveryZ - Old tables verification [' . (int) $oldZE . ']');
        if (!intval($oldZE) > 0) {
            zbxeErrorLog(true, 'EveryZ - Dropping old tables');
            DBexecute(zbxeStandardDML("DROP TABLE `zbxe_preferences` "));
            DBexecute(zbxeStandardDML("DROP TABLE `zbxe_translation` "));
        }
        $resultOK = true;
        zbxeErrorLog($VG_DEBUG, 'EveryZ - Creating tables');
        $dmlPreferences = "CREATE TABLE zbxe_preferences (
      `userid` int NOT NULL,
      `tx_option` varchar(60) NOT NULL,
      `tx_value` varchar(255) NOT NULL,
      `st_ativo` int NOT NULL,
      `module_id` varchar(20)
    )";
        $dmlTranslation = "CREATE TABLE zbxe_translation (
      `lang` varchar(255) NOT NULL,
      `tx_original` varchar(255) NOT NULL,
      `tx_new` varchar(255) NOT NULL,
      `module_id` varchar(20)
    ) ";
        DBexecute(zbxeStandardDML($dmlPreferences));
        DBexecute(zbxeStandardDML($dmlTranslation));
    }
} catch (Exception $e) {
    zbxeErrorLog(true, 'EveryZ - Creating tables fail');
    error($e->getMessage());
}
/* * ***************************************************************************
 * Update data
 * *************************************************************************** */
try {
    if (zbxeFieldValue("select COUNT(*) as total from zbxe_preferences", "total") < 2) {
        $debug = true;
        $resultOK = true;
#        DBstart();
        zbxeErrorLog($debug, 'EveryZ - Insert data on preferences');
        // Configuration
        $json = json_decode(file_get_contents("$PATH/everyz_config.json"), true);
        zbxeUpdateConfig($json, $resultOK, $debug);
        // Translation
        zbxeErrorLog($debug, 'EveryZ - Insert data on translation');
        $json = json_decode(file_get_contents("$PATH/everyz_lang_ALL.json"), true);
        zbxeUpdateTranslation($json, $resultOK, $debug);
#       DBend($resultOK);
    } else {
        // Verificar se as imagens existem
        if (zbxeFieldValue("select COUNT(*) as total from images where name like \"zbxe_%\" ", "total") !== 8) {
            $json = json_decode(file_get_contents("$PATH/everyz_config.json"), true);
            zbxeUpdateConfigImages($json, $resultOK, $debug);
        }
    }
} catch (Exception $e) {
    if (zbxeFieldValue("select COUNT(*) as total from zbxe_preferences", "total") < 2)
        error($e->getMessage());
}

