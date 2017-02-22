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

if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
$VG_DEBUG = true;
$PATH = realpath(dirname(__FILE__));

/* * ***************************************************************************
 * Update data
 * ************************************************************************** */
try {
    /*     * *******************************************************************
     * DML Updates
     * ********************************************************************** */
    if ($ezCurrent < 4) {
        DBexecute(zbxeStandardDML("ALTER TABLE `zbxe_preferences` ADD `module_id` VARCHAR(20) NULL"));
        DBexecute(zbxeStandardDML("UPDATE `zbxe_preferences` SET `module_id` = 'everyz4' "));
    }
    if ($ezCurrent < 6) {
        try {
            DBexecute(zbxeStandardDML("DROP TABLE `zbxe_preferences` "));
        } catch (Exception $e) {
            
        }
        try {
            DBexecute(zbxeStandardDML("DROP TABLE `zbxe_translation` "));
        } catch (Exception $e) {
            
        }
        $path = str_replace("/everyz/include", "/everyz", dirname(__FILE__));
        require_once $path . '/everyz.initdb.php';
    }

    /*     * *******************************************************************
     * Data Update
     * ********************************************************************** */
    for ($i = 2; $i <= EVERYZBUILD; $i++) {
        if ($i > $ezCurrent) {
            $debug = false;
            $resultOK = true;
            DBstart();
            if (file_exists("$PATH/everyz_upgrade.$i.php")) {
                require_once "$PATH/everyz_upgrade.$i.php";
            } 
            // Update Configuration
            if (file_exists("$PATH/everyz_config.$i.json")) {
                $json = json_decode(file_get_contents("$PATH/everyz_config.$i.json"), true);
                zbxeUpdateConfig($json, $resultOK, $debug);
            }
            // Update Translation
            if (file_exists("$PATH/everyz_lang.$i.json")) {
                $json = json_decode(file_get_contents("$PATH/everyz_lang.$i.json"), true);
                zbxeUpdateTranslation($json, $resultOK, $debug);
            }
            zbxeUpdateConfig("everyz_version", $i);
        }
    }
    if (isset($resultOK)) {
        DBend($resultOK);
        show_messages(true, _s(_zeT('EveryZ - Configuration update to %1$s version!'), EVERYZBUILD));
    }
} catch (Exception $e) {
    if (zbxeFieldValue("select COUNT(*) as total from zbxe_preferences", "total") < 2)
        error($e->getMessage());
}

