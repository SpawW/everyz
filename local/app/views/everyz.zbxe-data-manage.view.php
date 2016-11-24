<?php
/*
 * * Purpose: Translation, export and import data
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

// Scripts e CSS adicionais
?>
<?php
// Definitions -----------------------------------------------------------------
// Module Functions 
// Configuration variables =====================================================
$moduleName = "data-manage";
$baseProfile .= $moduleName;

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("source", PROFILE_TYPE_STR, [], true, true);
addFilterParameter("translation", PROFILE_TYPE_STR, [], true, true);

check_fields($fields);

/*
 * Get Data
 */

/*
 * Display
 */

$dashboard = (new CWidget())
        ->setTitle(EZ_TITLE . _zeT('Translation and data management'))
        ->setControls((new CList())
        ->addItem(get_icon('fullscreen', ['fullscreen' => getRequest('fullscreen')]))
);
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('GET', 'everyz.php'))->setName($moduleName);
$table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);

// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    $filter['filter_rst'] = NULL;
} else { // Put the date in required format
    //var_dump($filter["groupids"]);
    //var_dump(selectHostsByGroup($filter["groupids"],['location_lat', 'location_lon', 'location']));
}

$widget = (new CFilter('web.latest.filter.state'));

// Left collumn
$tmpColumn = new CFormList();
if ($filter['map'] == "") {
    $filter['map'] = 1;
}
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])))
        ->addRow(_zeT('Automatic icon mapping'), [zbxeComboIconMap('iconmapid', $filter['iconmapid'])])
        ->addRow(_zeT('Default tile'), [newComboFilterArray(
                    [ "Grayscale", "Streets", "Dark", "Outdoors", "Satellite", "Emerald"]
                    , "map", $filter['map'], false, false)])
;
$tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
$widget->addColumn($tmpColumn);
// Left collumn
$tmpColumn = new CFormList();
$radioZoom = (new CRadioButtonList('zoomLevel', (int) $filter['zoomLevel']))->setModern(true);
for ($i = 11; $i < 19; $i++) {
    $radioZoom->addValue(_($i), $i);
}
$tmpColumn->addRow(_('Center'), [
            _zeT('Latitude'), SPACE, (new CTextBox('centerLat', $filter['centerLat']))->setWidth(ZBX_TEXTAREA_TINY_WIDTH), SPACE,
            _zeT('Longitude '), SPACE, (new CTextBox('centerLong', $filter['centerLong']))->setWidth(ZBX_TEXTAREA_TINY_WIDTH)])
        ->addRow(_zeT('Default layers'), [
            (new CRadioButtonList('layers', (int) $filter['layers']))->setModern(true)
            ->addValue(_('none'), 1)->addValue(_('Lines'), 2)->addValue(_('Circles'), 3)->addValue(_('All'), 99)
        ])
        ->addRow(_zeT('Default zoom level'), [$radioZoom])

;
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
    $table->setNoDataMessage(_zeT('Specify some filter condition to see the geolocation.'));
}

$table->addRow((new CDiv())
                ->setAttribute('id', "mapid")
                ->setAttribute('style', "width:100%; height: 600px;")
        //->addClass("smallmap")
        //->addItem("oi")
);
$form->addItem([
    $table
        //Todo: Make export of data possible to select and to export to CSV, JSON using JavaScript
        //, new CActionButtonList('exportData', 'itemids', [ 0 => ['name' => _('Export as CSV')]])
]);

$dashboard->addItem($form)->show();

require_once 'local/app/everyz/js/everyz-zbxe-geolocation.js.php';
