<?php

/*
 * * Purpose: Build ZabGeo JSON metadata
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

// script opcional
//require_once 'include/views/js/monitoring.latest.js.php';
// Definitions -----------------------------------------------------------------
// Module Functions 
// Configuration variables =====================================================
$moduleName = "zbxe-clean";
$baseProfile .= $moduleName;
$moduleTitle = 'Sample';

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
// Mode of report
addFilterParameter("mode", T_ZBX_STR, "", false, false, false);

check_fields($fields);

/*
 * Display
 */
$dashboard = (new CWidget())
        ->setTitle('EveryZ - ' . _zeT('Sample')) // Module title
        ->setControls((new CList())
        ->addItem(get_icon('fullscreen', ['fullscreen' => getRequest('fullscreen')]))
);
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('GET', 'everyz.php'))->setName('cat');
$table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);

// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    $filter['filter_rst'] = NULL;
} else { // Put the date in required format
}

$widget = (new CFilter('web.' . $moduleName . '.filter.state'));

// Source data filter
$tmpColumn = new CFormList();
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])))
;
$tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
$widget->addColumn($tmpColumn);

$dashboard->addItem($widget);

// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {
    // Sample Check if all required fields have values
//    checkRequiredField("hostids", "You need to provide a least one host in filter!");
    // $count variable for check if the report has results
    // $report for store the report data
    if ($requiredMissing == false) {
        // Build your report
    }
} else {
    $table->setNoDataMessage(_('Specify some filter condition to see the values.'));
}

// Build the report header -----------------------------------------------------
switch ($filter["format"]) {
    case 1;
        $table->setHeader(array(_zeT("Data")));
        break;
    case 0;
        $locale = localeconv();
        $currency = (strlen($locale['currency_symbol']) > 1 ? $locale['currency_symbol'] : '$');
        $table->setHeader(array(_("Column1"), _("Column2"), _("Column3")
        ));
        break;
}
// Building the report ---------------------------------------------------------
if (isset($cont)) {
    $linha = array();
    $cont2 = count($report[0]) - 1;
    for ($i = 0; $i < $cont; $i++) {
        switch ($filter["format"]) {
            case 0;
                for ($x = 0; $x <= $cont2; $x++) {
                    $linha[$x] = new CCol($report[$i][$x], 1);
                }
                // Calculo de UBM por host
                $table->addRow($linha);
                break;
            case 1;
                $linhaCSV = "";
                for ($x = 0; $x < $cont2; $x++) {
                    $linhaCSV .= quotestr($report[$i][$x]) . ";";
                }
                $table->addRow(array($linhaCSV));
                break;
            case 2;
                $table->addRow('Todo: Make a JSON');
                break;
        }
    }
}
$form->addItem([
    $table
        //Todo: Make export of data possible to select and to export to CSV, JSON using JavaScript
        //, new CActionButtonList('exportData', 'itemids', [ 0 => ['name' => _('Export as CSV')]])
]);

$dashboard->addItem($form)->show();
