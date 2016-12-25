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

/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
$VG_DEBUG = true;
$PATH = realpath(dirname(__FILE__));
//error_reporting(E_ALL);
//ini_set('display_errors', 1);


/* * ***************************************************************************
 * DML commands
 * ************************************************************************** */

try {
    if (!$VG_BANCO_OK) {
        $criar_tabelas = "S";
        $resultOK = true;
        debugInfo("EveryZ tables does not exists. Creating the configuration table:", true);
        $dmlPreferences = "CREATE TABLE zbxe_preferences (
      `userid` int NOT NULL,
      `tx_option` varchar(60) NOT NULL,
      `tx_value` varchar(255) NOT NULL,
      `st_ativo` int NOT NULL
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
    error($e->getMessage());
}

/* * ***************************************************************************
 * Update data
 * ****************************************************************************/
try { 
    if (zbxeFieldValue("select COUNT(*) as total from zbxe_preferences", "total") < 2) {
        $debug = false;
        $resultOK = true;
        DBstart();
        // Configuration
        $json = json_decode(file_get_contents("$PATH/everyz_config.json"), true);
        zbxeUpdateConfig($json, $resultOK, $debug);
        debugInfo("oi",true);
        // Translation
        $json = json_decode(file_get_contents("$PATH/everyz_lang_ALL.json"), true);
        zbxeUpdateTranslation($json, $resultOK, $debug);
        debugInfo("oi",true);

        DBend($resultOK);
    }
} catch (Exception $e) {
    if (zbxeFieldValue("select COUNT(*) as total from zbxe_preferences", "total") < 2)
        error($e->getMessage());
}

