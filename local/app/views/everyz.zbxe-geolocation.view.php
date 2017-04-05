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

/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
// Configuration variables =====================================================
$moduleName = "zbxe-geolocation";
$baseProfile .= $moduleName;
$moduleTitle = 'Geolocation';

// Zabbix configuration
$config = select_config();

// Common fields
//addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("groupids", PROFILE_TYPE_STR, [], true, true);
addFilterParameter("iconmapid", T_ZBX_INT);

addFilterParameter("centerLat", T_ZBX_STR, "-12.70894", false, false);
addFilterParameter("centerLong", T_ZBX_STR, "-47.19727", false, false);
addFilterParameter("showteam", T_ZBX_STR, "", false, false);
addFilterParameter("zoomLevel", T_ZBX_INT, 5);
addFilterParameter("map", T_ZBX_STR, "0");
addFilterParameter("layers", T_ZBX_STR, "99");

check_fields($fields);


/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
checkAccessGroup('groupids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function cleanPosition($position) {
    $position = str_replace('°', '', $position);
    return $position;
}

function hostIndex($hostid, $hostArray) {
    foreach ($hostArray as $k => $v) {
        if ($v['id'] == $hostid) {
            return $k;
        }
    }
    return -1;
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    resetProfile('groupids', true);
    resetProfile('iconmapid', true);
    resetProfile('centerLat', true);
    resetProfile('centerLon', true);
    resetProfile('zoomLevel', true);
    resetProfile('layers', true);
    resetProfile('map', true);
    $filter['filter_rst'] = NULL;
    $filter['layers'] = intval($filter['layers'], 99);
    $filter['map'] = intval($filter['map'], 0);
} else { // Put the date in required format
    $filter['layers'] = intval((int) $filter['layers'], 99);
    $filter['map'] = intval($filter['map'], 0);
}

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

foreach ($hostData as $key => $host) {
    $hostData[$key]['location_lat'] = cleanPosition($host['location_lat']);
    $hostData[$key]['location_lon'] = cleanPosition($host['location_lon']);
    // Check if host have a minimum usable data
    if ($host['location_lat'] == "" || $host['location_lon'] == "") {
//        echo "host faltando dados".$host['id'];
        unset($hostData[$key]);
    } else {
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
                $hostData[$cont]["events"][count($hostData[$cont]["events"])] = [
                    "triggerid" => $event["triggerid"], "description" => $event["description"]
                    , "priority" => $event["priority"], "moment" => zbx_date2age($event["lastEvent"]["clock"])
                    , "eventid" => $event["lastEvent"]["eventid"]
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
        // Varrer o notes e transferir o metadado para os arrays
        if (isset($host["notes"])) {
            $jsonArray = isJson($host["notes"]);
            if (!$jsonArray == false) {
                // Tratamento dos Circles
                if (isset($jsonArray['circle']))
                    foreach ($jsonArray['circle'] as $value) {
                        $hostData[$cont]['circle'][] = ['size' => $value['size'], 'color' => (!strpos($value['color'], "#") ? "#" : "") . $value['color']];
                    }
                $defaultColor = "000088";
                $defaultWidth = 4;
                // Tratamento dos Lines
                if (isset($jsonArray['line']))
                    foreach ($jsonArray['line'] as $value) {
                        $hostData[$cont]['line'][] = ['lat' => cleanPosition($value['lat']), 'lon' => cleanPosition($value['lon'])
                            , 'popup' => optArrayValue($value, 'popup')
                            , 'color' => "#" . optArrayValue($value, 'color', $defaultColor), 'width' => optArrayValue($value, 'width', $defaultWidth)
                        ];
                    }
                // Tratamento dos Links
                if (isset($jsonArray['link']))
                    foreach ($jsonArray['link'] as $value) {
                        $targetHost = hostIndex($value['hostid'], $hostData);
                        if ($targetHost > -1) {
                            $hostData[$cont]['line'][] = ['lat' => cleanPosition($hostData[$targetHost]['location_lat'])
                                , 'lon' => cleanPosition($hostData[$targetHost]['location_lon']), 'popup' => optArrayValue($value, 'popup', 'test')
                                , 'color' => "#" . optArrayValue($value, 'color', $defaultColor), 'width' => optArrayValue($value, 'width', $defaultWidth)
                            ];
                        }
                    }
            }
        }
    }
    $cont++;
}

// Ordenar hosts por existência de evento
$tmp = [];
foreach ($hostData as $key => $host) {
    if ($host['maxPriority'] > -1) {
        array_push($tmp, $host);
    } else {
        array_unshift($tmp, $host);
    }
    //$hostData
}
$hostData = $tmp;

/* * ***************************************************************************
 * Display
  <script src="local/app/everyz/js/leaflet.js"></script>
 * ************************************************************************** */
zbxeJSLoad(['everyzD3Functions.js','everyz-zbxe-geolocation.static.js',
    'leaflet.js', 'leaflet/leaflet.lineextremities.js', 'leaflet/leaflet-control-credits.js', 'leaflet/leaflet-control-credits-src.js']
);
?>
<link rel="stylesheet" href="local/app/everyz/css/leaflet.css" />
<link rel="stylesheet" href="local/app/everyz/css/leaflet-control-credits.css" />
<?php
commonModuleHeader($moduleName, $moduleTitle, true);
$widget = newFilterWidget($moduleName);

// Left collumn
$tmpColumn = new CFormList();
if ($filter['map'] == "") {
    $filter['map'] = 0;
}
if ($filter['centerLat'] == "") {
    $filter['centerLat'] = -12.70894;
}
if ($filter['centerLong'] == "") {
    $filter['centerLong'] = -47.19727;
}
if ($filter['zoomLevel'] == "") {
    $filter['zoomLevel'] = 5;
}
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])))
        ->addRow(_zeT('Automatic icon mapping'), [zbxeComboIconMap('iconmapid', $filter['iconmapid'])])
;
if (zbxeConfigValue("geo_token", 0, '') !== "") {
    $tmpColumn->addRow(_zeT('Default tile'), [newComboFilterArray(
                ["Grayscale", "Streets", "Dark", "Outdoors", "Satellite", "Emerald"]
                , "map", $filter['map'], false, false)]);
} else {
    $tmpColumn->addRow(_zeT('Default tile'), [newComboFilterArray(
                ["OpenStreet_Base", "OpenStreet_Grayscale", "OpenTopo", "Stamen_Terrain", "CartoDB_DarkMatter", "Esri_WorldStreetMap"]
                , "map", $filter['map'], false, false)]);
}

$tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
$widget->addColumn($tmpColumn);
// Left collumn
$tmpColumn = new CFormList();
$radioZoom = new CComboBox('zoomLevel', (int) $filter['zoomLevel']);
for ($i = 1; $i < 18; $i++) {
    $radioZoom->additem($i, $i);
}

$tmpColumn->addRow(_('Center'), [
            _zeT('Latitude'), SPACE, (new CTextBox('centerLat', $filter['centerLat']))->setWidth(ZBX_TEXTAREA_TINY_WIDTH), SPACE,
            _zeT('Longitude '), SPACE, (new CTextBox('centerLong', $filter['centerLong']))->setWidth(ZBX_TEXTAREA_TINY_WIDTH)])
        ->addRow(_zeT('Default layers'), [
            (new CRadioButtonList('layers', (int) $filter['layers']))->setModern(true)
            ->addValue(_zeT('none'), 1)->addValue(_zeT('Lines'), 2)->addValue(_zeT('Circles'), 3)->addValue(_zeT('All'), 99)
        ])
        ->addRow(_zeT('Default zoom level'), [$radioZoom])

;
$widget->addColumn($tmpColumn);
$dashboard->addItem($widget);

// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {
    // Sample Check if all required fields have values
    checkRequiredField("centerLat", _zeT("You need to entered center Latitude data!"));
    checkRequiredField("centerLong", _zeT("You need to entered center Longitude data!"));
    checkRequiredField("groupids", _zeT("You need to select one or more host groups!"));
} else {
    zbxeNeedFilter(_zeT('Specify some filter condition to see the geolocation.'));
}

if (hasRequest("filter_set") && !$requiredMissing) {
    $table->addRow((new CDiv())
                    ->setAttribute('id', "mapid")
                    ->setAttribute('style', "width:100%; height: 100%;")
    );
}
$form->addItem([$table]);
$dashboard->addItem($form)->show();

if (hasRequest("filter_set") && !$requiredMissing) {
    require_once 'local/app/everyz/js/everyz-zbxe-geolocation.js.php';
    //zbxeJSLoad(['everyz-zbxe-geolocation.js.php']);
}

