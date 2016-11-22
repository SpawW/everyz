<?php
/*
 * * Purpose: Geolocalization of hosts
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
// Scripts e CSS adicionais
?>
<link rel="stylesheet" href="local/app/everyz/css/leaflet.css" />
<script src="local/app/everyz/js/leaflet.js"></script>
<?php
// Definitions -----------------------------------------------------------------
// Module Functions 
// Configuration variables =====================================================
$moduleName = "zbxe-geolocation";
$baseProfile .= $moduleName;

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("groupids", PROFILE_TYPE_STR, [], true, true);
addFilterParameter("iconmapid", T_ZBX_INT);

addFilterParameter("centerLat", T_ZBX_STR, "", false, false);
addFilterParameter("centerLong", T_ZBX_STR, "", false, false);
addFilterParameter("zoomLevel", T_ZBX_INT);
//addFilterParameter("showCircles", T_ZBX_INT);
//addFilterParameter("showLines", T_ZBX_INT);
addFilterParameter("layers", T_ZBX_INT);

check_fields($fields);

// Buscar o mapeamento de ícones ====================================================================
// Verificar a lista de campos do inventário que são utilizados =====================================
$inventoryFields = ["notes", "location_lat", "location_lon"];
$iconMapImageId = [];

$iconMapping = API::IconMap()->get([ 'iconmapids' => $filter["iconmapid"], 'output' => "extend", "selectMappings" => "extend"]);
$withIconMapping = (count($iconMapping) == 1);
if ($withIconMapping) {
    for ($i = 0; $i < count($iconMapping[0]["mappings"]); $i++) {
        $iconMapping[0]["mappings"][$i]["inventory_field"] = zbxeInventoryField($iconMapping[0]["mappings"][$i]["inventory_link"]);
        if (!in_array(zbxeInventoryField($iconMapping[0]["mappings"][$i]["inventory_link"]), $inventoryFields)) {
            $inventoryFields[count($inventoryFields)] = $iconMapping[0]["mappings"][$i]["inventory_field"];
        }
    }
}

$eventData = selectEventsByGroup($filter["groupids"], 1);
$hostData = selectHostsByGroup($filter["groupids"], $inventoryFields);
$cont = 0;
$imagesArray = [];
foreach ($hostData as $host) {
    // Popular dados de triggers no host
    // Descobrir a imagem do host
    $hostData[$cont]["maxPriority"] = -1;
    foreach ($eventData as $event) {
        $related = false;
        foreach ($event["hosts"] as $eventHost) {
            if ($eventHost["hostid"] == $host["id"]) {
                if (!isset($hostData[$cont]["events"])) {
                    $hostData[$cont]["events"] = [];
                }
                $related = true;
            }
        }
        if ($related) {
            //$duration = ((new DateTime())->getTimestamp()) - $event["lastEvent"]["clock"];
            //var_dump($event);
            $hostData[$cont]["events"][count($hostData[$cont]["events"])] = [
                $event["triggerid"], $event["description"], $event["priority"],
                zbx_date2age($event["lastEvent"]["clock"])
            ];
            if ($hostData[$cont]["maxPriority"] < $event["priority"]) {
                $hostData[$cont]["maxPriority"] = $event["priority"];
            }
        }
    }
    // Descobrir a imagem do host
    if ($withIconMapping) {
        foreach ($iconMapping[0]["mappings"] as $iMap) {
            if (array_key_exists($iMap["inventory_field"], $host)) {
                if ($host[$iMap["inventory_field"]] == $iMap["expression"]) {
                    $hostData[$cont]["iconid"] = $iMap["iconid"];
                    break;
                }
            }
        }

        if (!isset($hostData[$cont]["iconid"])) {
            $hostData[$cont]["iconid"] = $iconMapping[0]["default_iconid"];
        }
    } else {
        $hostData[$cont]["iconid"] = zbxeConfigValue('geo_default_poi', 0, "zbxe_default_icon");
    }
    /* if (!array_key_exists("imageid", $host)) {
      $hostData[$cont]["imageid"] = $iconMapping[0]["default_iconid"];
      } */
    // Varrer o notes e transferir o metadado para os arrays
    if (isset($host["notes"])) {
        $tmp2 = explode("\n", $host["notes"]);
        foreach ($tmp2 as $hostMetadata) {
            $tmp = explode(";", $hostMetadata);
            if (!isset($hostData[$cont][$tmp[0]])) {
                $hostData[$cont][$tmp[0]] = [];
            }
            $contType = count($hostData[$cont][$tmp[0]]);
            for ($noteInfo = 1; $noteInfo < count($tmp) - 1; $noteInfo++) {
                $hostData[$cont][$tmp[0]][$contType][$noteInfo] = $tmp[$noteInfo];
            }
        }
    }
    $cont++;
}
//var_dump($hostData);
/*
 * Display
 */

$dashboard = (new CWidget())
        ->setTitle('EveryZ - ' . _zeT('Geolocation'))
        ->setControls((new CList())
        ->addItem(get_icon('fullscreen', ['fullscreen' => getRequest('fullscreen')]))
);
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('GET', 'everyz.php'))->setName('geo');
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
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])))
        ->addRow(_('Automatic icon mapping'), [zbxeComboIconMap('iconmapid', $filter['iconmapid'])])
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
