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

addFilterParameter("centerLat", T_ZBX_STR, "", false, false);
addFilterParameter("centerLong", T_ZBX_STR, "", false, false);
addFilterParameter("zoomLevel", T_ZBX_INT);
addFilterParameter("map", T_ZBX_STR, "1", false, false);
addFilterParameter("layers", T_ZBX_INT);

check_fields($fields);


/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
checkAccessGroup('groupids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

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
    $filter['filter_rst'] = NULL;
} else { // Put the date in required format
    //var_dump($filter["groupids"]);
    //var_dump(selectHostsByGroup($filter["groupids"],['location_lat', 'location_lon', 'location']));
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
                        $hostData[$cont]['circle'][] = ['size' => $value['size'], 'color' => $value['color']];
                    }
                // Tratamento dos Lines
                if (isset($jsonArray['line']))
                    foreach ($jsonArray['line'] as $value) {
                        $hostData[$cont]['line'][] = ['lat' => $value['lat'], 'lon' => $value['lon'], 'popup' => optArrayValue($value, 'popup')];
                    }
                // Tratamento dos Links
                if (isset($jsonArray['link']))
                    foreach ($jsonArray['link'] as $value) {
                        $targetHost = hostIndex($value['hostid'], $hostData);
                        if ($targetHost > -1) {
                            $hostData[$cont]['line'][] = ['lat' => $hostData[$targetHost]['location_lat'], 'lon' => $hostData[$targetHost]['location_lon'], 'popup' => optArrayValue($value, 'popup')];
                        }
                    }
            }
            /* Configuração para CSV
              $tmp2 = explode("\n", $host["notes"]);

              // Tratar o JSON
              foreach ($tmp2 as $hostMetadata) {
              $tmp = explode(";", $hostMetadata);
              // Converter os links para lines através de consulta reversa aos hosts
              // aqui adail
              if ($tmp[0] == 'link') {
              //line;-3.70068;-38.65891;#303;2;Link3;
              //echo "\n console.log('$tmp[4]')";
              $targetHost = hostIndex($tmp[1], $hostData);
              $tmp = ['line', $hostData[$targetHost]['location_lat'], $hostData[$targetHost]['location_lon'], $tmp[2], $tmp[3], $tmp[4]];
              //echo "\n console.log('$tmp[5]')";
              }
              if (!isset($hostData[$cont][$tmp[0]])) {
              $hostData[$cont][$tmp[0]] = [];
              }
              $contType = count($hostData[$cont][$tmp[0]]);
              for ($noteInfo = 1; $noteInfo < count($tmp) - 1; $noteInfo++) {
              $hostData[$cont][$tmp[0]][$contType][$noteInfo] = $tmp[$noteInfo];
              }
              }

             */
        }
    }
    $cont++;
}


/* * ***************************************************************************
 * Display
 * ************************************************************************** */
?>
<link rel="stylesheet" href="local/app/everyz/css/leaflet.css" />
<script src="local/app/everyz/js/leaflet.js"></script>
<?php
commonModuleHeader($moduleName, $moduleTitle, true);
$widget = newFilterWidget($moduleName);

// Left collumn
$tmpColumn = new CFormList();
if ($filter['map'] == "") {
    $filter['map'] = 1;
}
if ($filter['centerLat'] == "") {
    $filter['centerLat'] = -12.8;
}
if ($filter['centerLong'] == "") {
    $filter['centerLong'] = -44.8;
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
for ($i = 5; $i < 14; $i++) {
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
    checkRequiredField("centerLat", _zeT("You need to entered center Latitude data!"));
    checkRequiredField("centerLong", _zeT("You need to entered center Longitude data!"));
    if ($requiredMissing) {
        error("check data required!");
        //checkRequiredField("centerLat", _zeT("You need to entered center Latitude data!"));  
    }
} else {
    zbxeNeedFilter(_zeT('Specify some filter condition to see the geolocation.'));
}

$table->addRow((new CDiv())
                ->setAttribute('id', "mapid")
                ->setAttribute('style', "width:100%; height: 700px;")
);
$form->addItem([ $table]);
$dashboard->addItem($form)->show();

require_once 'local/app/everyz/js/everyz-zbxe-geolocation.js.php';
?>
<script language="JavaScript">
    var filterButton = document.getElementById('filter-mode');
    var titleBar = document.getElementsByClassName("header-title");
    var filterDIV = document.getElementById('filter-space');
    for (i = 0; i <= titleBar[0].children.length - 1; i++) {
        if (titleBar[0].children[i].tagName.toLowerCase() == 'ul') {
            titleUL = titleBar[0].children[i];
            var newItem = document.createElement("LI");
            var textnode = document.createTextNode(" ")
            //newItem.appendChild(textnode);
            //             .titleUL.appendChild(newItem);
            titleUL.appendChild(filterButton);
            btnMin = document.getElementsByClassName("btn-min");
            if (btnMin.length > 0) {
                filterDIV.style = 'display: none;'
            }
        }
    }
</script>

<?php
