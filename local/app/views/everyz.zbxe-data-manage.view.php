<?php

/*
 * * Purpose: Export and import data from EveryZ database
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
 * TodoS: ===========================================================================
 * */

/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
// Definitions -----------------------------------------------------------------
// Module Functions 
// Configuration variables =====================================================
$moduleName = "zbxe-data-manage";
$baseProfile .= $moduleName;
$moduleTitle = 'Data manager';
$report = [];

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields

check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */

if (hasRequest('filter_rst')) { 
    //resetProfile('hostids', true);
    //resetProfile('groupids', true);
    $filter['filter_rst'] = NULL;
    $filter['filter_set'] = NULL;
} else { 
}

$report['translation'] = zbxeSQLList('SELECT * FROM `zbxe_translation` order by lang, tx_original');
$report['preferences'] = zbxeSQLList('SELECT * FROM `zbxe_preferences` order by userid, tx_option');

?>
<?php

/* * ***************************************************************************
 * Display
 * ************************************************************************** */
commonModuleHeader($moduleName, $moduleTitle, true);

show_message(json_encode($report, JSON_UNESCAPED_UNICODE));


/* * ***************************************************************************
 * Display Footer 
 * ************************************************************************** */
switch ($filter["format"]) {
    case PAGE_TYPE_CSV;
        echo zbxeToCSV($csvRows);
        break;
    case PAGE_TYPE_JSON;
        echo "[" . $jsonResult . "]";
        break;
    default;
        $form->addItem([ $table]);
        $dashboard->addItem($form)->show();
        break;
}
