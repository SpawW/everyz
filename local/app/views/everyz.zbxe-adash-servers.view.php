<?php
/*
 * * Purpose: Automatic Dashboard for servers
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
$moduleName = "zbxe-adash-servers";
$baseProfile .= $moduleName;
$moduleTitle = 'Automatic Dashboard - Server';

// Zabbix configuration
$config = select_config();

// Common fields
//addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("groupids", PROFILE_TYPE_STR, [], true, true);

check_fields($fields);


/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
checkAccessGroup('groupids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function newWidget($id, $content) {
    return [(new CSpan($content))->setAttribute('id', "cpu" . $id . "Gauge")
        , (new CSpan($content))->setAttribute('id', "mem" . $id . "Gauge")
        , (new CSpan($content))->setAttribute('id', "fs" . $id . "Gauge")];
    ;
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    resetProfile('groupids', true);
    $filter['filter_rst'] = NULL;
} else { // Put the date in required format
    //var_dump($filter["groupids"]);
    //var_dump(selectHostsByGroup($filter["groupids"],['location_lat', 'location_lon', 'location']));
}


/* * ***************************************************************************
 * Display
 * ************************************************************************** */
zbxeJSLoad(['d3/d3.min.js', 'everyzD3Functions.js.php', 'd3/gauge.js']);

commonModuleHeader($moduleName, $moduleTitle, true);
$widget = newFilterWidget($moduleName);

// Left collumn
$tmpColumn = new CFormList();
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])));
$tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
$widget->addColumn($tmpColumn);
// Left collumn

$dashboard->addItem($widget);

// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {
    // Sample Check if all required fields have values
    if ($requiredMissing) {
        error("check data required!");
        //checkRequiredField("centerLat", _zeT("You need to entered center Latitude data!"));  
    }
} else {
    zbxeNeedFilter(_zeT('Specify some filter condition to see the geolocation.'));
}

function getItems($groupids, $itemType, $key_) {
    global $itemids, $reportData;
    $result = API::Item()->get([
        'output' => ['itemid', 'hostid'],
        'groupids' => $groupids,
        'search' => ['key_' => $key_]
    ]);
    foreach ($result as $value) {
        $itemids[] = (int) $value['itemid'];
        $reportData[$value['hostid']][$itemType] = ['itemid' => $value['itemid'], 'value' => 0];
    }
    return $result;
}

$allHosts = API::Host()->get([
    'output' => ['hostid', 'name'],
    'groupids' => $filter["groupids"]
        ]);

$reportData = [];

foreach ($allHosts as $value) {
    $reportData[$value['hostid']]['name'] = $value['name'];
}

// ----- Get ItemIds -----------------------------------------------------------
$cpuItens = getItems($filter["groupids"], 'cpu', 'system.cpu.load[percpu,avg1]');
$memoryItens = getItems($filter["groupids"], 'memory', 'vm.memory.size[pavailable]');
$fsItens = getItems($filter["groupids"], 'fs', 'vfs.fs.size[/,pfree]');
// ----- Get Trends ------------------------------------------------------------
$trendProjection = 'value_max';
$itemValues = API::Trend()->get([
    'output' => ['itemid', 'clock', 'num', $trendProjection], //"value_min", "value_avg", "value_max"],
    'itemids' => $itemids
        ]);
// Varrer os hosts e coletar seus itens e valores
// Varrer o array e organizar como "item" "valor"
foreach ($itemValues as $itemValue) {
    foreach ($reportData as $key => $host) {
        if ($host['cpu']['itemid'] == $itemValue['itemid']) {
            $reportData[$key]['cpu']['value'] = $itemValue[$trendProjection];
        } elseif ($host['memory']['itemid'] == $itemValue['itemid']) {
            $reportData[$key]['memory']['value'] = $itemValue[$trendProjection];
        } elseif ($host['fs']['itemid'] == $itemValue['itemid']) {
            $reportData[$key]['fs']['value'] = $itemValue[$trendProjection];
        }
    }
}
//var_dump($reportData);
$hostNames = $hostData = [];

$hostCount = 20;
$cont = 0;
//for ($i = 0; $i < count($allHosts); $i++) {
foreach ($reportData as $key => $host) {
    if (isset($host['name'])) {
        $tmp = [
            "hostid" => $key,
            "name" => $host['name'], 
            "cpu" => $host['cpu']['value'],
            "memory" => $host['memory']['value'],
            "fs" => $host['fs']['value']
        ];
        $cont++;
        $hostData[] = $tmp;
        $gauges[] = newWidget($tmp['hostid'], "");
        $hostNames[] = $tmp["name"];
        if ($cont > 2) {
            $table->addRow($hostNames);
            $table->addRow($gauges);
            $hostNames = $gauges = [];
            $cont = 0;
        }
    }
}

if ($cont > 0) {
    $table->addRow($hostNames);
    $table->addRow($gauges);
}
$form->addItem([$table]);
$dashboard->addItem($form)->show();
?>
<script language="JavaScript">
    var gauges = [];
    function getRandomValue(gauge) {
        var overflow = 0; //10;
        return gauge.config.min - overflow + (gauge.config.max - gauge.config.min + overflow * 2) * Math.random();
    }
    function addGauges(hostid, cpu, mem, fs) {
        createGauge("cpu" + hostid, "CPU", cpu);
        createGauge("mem" + hostid, "Memory", mem);
        createGauge("fs" + hostid, "FileSystem", fs);
    }
    hostData = <?php echo json_encode($hostData, JSON_UNESCAPED_UNICODE); ?>;
    for (i = 0; i < hostData.length; i++) {
        addGauges(hostData[i]['hostid'], hostData[i]['cpu'], hostData[i]['memory'], hostData[i]['fs']);
    }
    /*for (var key in gauges) {
     var value = getRandomValue(gauges[key])
     gauges[key].redraw(value);
     }*/
// Full Screen
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
