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
addFilterParameter("gaugeKey1", T_ZBX_STR, 'system.cpu.load[percpu,avg1]');
addFilterParameter("gaugeKey2", T_ZBX_STR, 'vm.memory.size[pavailable]');
addFilterParameter("gaugeKey3", T_ZBX_STR, 'vfs.fs.size[/,pfree]');
addFilterParameter("titleKey1", T_ZBX_STR, 'CPU');
addFilterParameter("titleKey2", T_ZBX_STR, 'MEM');
addFilterParameter("titleKey3", T_ZBX_STR, 'FS');
addFilterParameter("colCount", T_ZBX_INT, 4);

check_fields($fields);


/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
checkAccessGroup('groupids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function getItems($groupids, $itemType, $key_) {
    global $itemids, $reportData, $countGauges;
    if (trim($key_) == "") {
        $result = [];
    } else {
        if (!is_array($groupids)) {
            $groupids = [$groupids];
        }
        $result = API::Item()->get([
            'output' => ['itemid', 'hostid', 'lastvalue'],
            //'output' => 'extend',
            'groupids' => $groupids,
            'search' => ['key_' => $key_]
        ]);
        foreach ($result as $value) {
            $itemids[] = (int) $value['itemid'];
            //$itemids[(int) $value['itemid']]['value'] = $value['lastValue'];
            $reportData[$value['hostid']][$itemType] = ['itemid' => $value['itemid'], 'value' => $value['lastvalue']];
        }
        //var_dump([$reportData, $groupids, $itemType, $key_]);
    }
    return $result;
}

function newWidget($id, $content) {
    return [(new CSpan($content))->setAttribute('id', "key1" . $id . "Gauge")
        , (new CSpan($content))->setAttribute('id', "key2" . $id . "Gauge")
        , (new CSpan($content))->setAttribute('id', "key3" . $id . "Gauge")];
    ;
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
if (hasRequest('filter_rst')) { // Clean the filter parameters
    resetProfile('groupids', true);
    resetProfile('gaugeKey1', true);
    resetProfile('gaugeKey2', true);
    resetProfile('gaugeKey3', true);

    $filter["gaugeKey1"] = 'system.cpu.load[percpu,avg1]';
    $filter["gaugeKey2"] = 'vm.memory.size[pavailable]';
    $filter["gaugeKey3"] = 'vfs.fs.size[/,pfree]';
    $filter["titleKey1"] = 'CPU';
    $filter["titleKey2"] = 'MEM';
    $filter["titleKey3"] = 'FS';

    $filter['filter_set'] = NULL;
    $filter['filter_rst'] = NULL;
} else { // Put the date in required format
    //var_dump($filter["groupids"]);
    //var_dump(selectHostsByGroup($filter["groupids"],['location_lat', 'location_lon', 'location']));
}
$allHosts = API::Host()->get([
    'output' => ['hostid', 'name'],
    'groupids' => $filter["groupids"]
        ]);

$reportData = [];
$totalHosts = $gaugeKey1 = $gaugeKey2 = $gaugeKey3 = 0;

foreach ($allHosts as $value) {
    $reportData[$value['hostid']]['name'] = $value['name'];
    $totalHosts++;
}

// ----- Get ItemIds -----------------------------------------------------------
if (count($filter['groupids']) > 0) {
    $countGauges = ['gaugeKey1' => 0, 'gaugeKey2' => 0, 'gaugeKey3' => 0];
    $gaugeKey1Itens = getItems($filter["groupids"], 'gaugeKey1', $filter['gaugeKey1']);
    $gaugeKey2Itens = getItems($filter["groupids"], 'gaugeKey2', $filter['gaugeKey2']);
    $gaugeKey3Itens = getItems($filter["groupids"], 'gaugeKey3', $filter['gaugeKey3']);

    $countGauges = ['gaugeKey1' => count($gaugeKey1Itens), 'gaugeKey2' => count($gaugeKey2Itens), 'gaugeKey3' => count($gaugeKey3Itens)];
}
//var_dump(["Total hosts - $totalHosts", "Total Key1: " . $countGauges['gaugeKey1']    , "Total Key2: " . $countGauges['gaugeKey1'], "Total Key3: " . $countGauges['gaugeKey1']]);

$hostNames = $hostData = [];

/* * ***************************************************************************
 * Display - Report
 * ************************************************************************** */

zbxeJSLoad(['d3/d3.min.js', 'everyzD3Functions.js', 'd3/gauge.js']);

commonModuleHeader($moduleName, $moduleTitle, true);

$widget = newFilterWidget($moduleName);

// Left collumn
$leftColumn = new CFormList();

$leftColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])));
$radioCol = (new CRadioButtonList('colCount', (int) $filter['colCount']))->setModern(true);
for ($i = 1; $i < 6; $i++) {
    //Caption | value
    $radioCol->addValue($i, $i-1);
}
$leftColumn->addRow(_('Columns'), [$radioCol]);

$rightColumn = new CFormList();
$rightColumn->addRow(_('Key 1'), [ (new CTextBox('gaugeKey1', $filter['gaugeKey1']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
    , (new CTextBox('titleKey1', $filter['titleKey1']))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)]);
$rightColumn->addRow(_('Key 2'), [ (new CTextBox('gaugeKey2', $filter['gaugeKey2']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
    , (new CTextBox('titleKey2', $filter['titleKey2']))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)]);
$rightColumn->addRow(_('Key 3'), [ (new CTextBox('gaugeKey3', $filter['gaugeKey3']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
    , (new CTextBox('titleKey3', $filter['titleKey3']))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)]);

$leftColumn->addItem(new CInput('hidden', 'action', $filter["action"]));

// Colluns configuration

$widget->addColumn($leftColumn);
$widget->addColumn($rightColumn);
// Left collumn

$dashboard->addItem($widget);
if (hasRequest('filter_set') ) { // Clean the filter parameters
    checkRequiredField("groupids", _zeT("You need to provide a least one host group in filter!"));
    if (!$requiredMissing) {
        $cont = 0;
        //var_dump($reportData);
        foreach ($reportData as $key => $host) {
            if (isset($host['name'])) {
                $tmp = [
                    "hostid" => $key,
                    "name" => $host['name'],
                    "gaugeKey1" => (isset($host['gaugeKey1']) ? $host['gaugeKey1']['value'] : -111),
                    "gaugeKey2" => (isset($host['gaugeKey2']) ? $host['gaugeKey2']['value'] : -111),
                    "gaugeKey3" => (isset($host['gaugeKey3']) ? $host['gaugeKey3']['value'] : -111)
                ];
                $cont++;
                $hostData[] = $tmp;
                $gauges[] = newWidget($tmp['hostid'], "");
                $hostNames[] = $tmp["name"];
                if ($cont > $filter['colCount']) {
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
    }
}
$form->addItem([$table]);
$dashboard->addItem($form)->show();
?>
<script language="JavaScript">
    var gauges = [];
    function addGauges(hostid, key1, key2, key3) {
        if (key1 > -1) {
            createGauge("key1" + hostid, "<?php echo $filter["titleKey1"]; ?>", key1);
        }
        if (key2 > -1) {
            createGauge("key2" + hostid, "<?php echo $filter["titleKey2"]; ?>", key2);
        }
        if (key3 > -1) {
            createGauge("key3" + hostid, "<?php echo $filter["titleKey3"]; ?>", key3);
        }
    }
    hostData = <?php echo json_encode($hostData, JSON_UNESCAPED_UNICODE); ?>;
    for (i = 0; i < hostData.length; i++) {
        addGauges(hostData[i]['hostid'], hostData[i]['gaugeKey1'], hostData[i]['gaugeKey2'], hostData[i]['gaugeKey3']);
    }
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
