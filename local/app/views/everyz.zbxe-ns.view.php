<?php

/*
 * * Purpose: Report of Not Supported Items
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
 */
/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */

$moduleName = "zbxe-ns";
$baseProfile .= $moduleName;
$moduleTitle = 'Not Supported Items';

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
//addFilterParameter("hostids", T_ZBX_INT, [], true, false);
//addFilterParameter("groupids", T_ZBX_INT, [], true, false);
addIdFilterParameter("hostids");
addIdFilterParameter("groupids");
addFilterParameter("item", T_ZBX_STR);
addFilterParameter("inactiveHosts", T_ZBX_INT, 1);

check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */

checkAccessHost('hostids');
checkAccessGroup('groupids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
if (hasRequest('filter_rst')) {
    resetProfile('hostids', true);
    resetProfile('groupids', true);
    resetProfile('item');
    //resetProfile('application');
    $filter['filter_rst'] = NULL;
    $filter['filter_set'] = NULL;
}


$finalReport = Array();
switch ($filter["format"]) {
    case PAGE_TYPE_CSV:
        break;
    case PAGE_TYPE_JSON:
        $jsonResult = [];
        break;
    default:
        break;
}

$report = Array();
$hostCont = Array();

// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {

    $hostFilter = zbxeDBConditionInt('hos.hostid', $filter["hostids"]);
    $hostGroupFilter = zbxeDBConditionInt('hg.groupid', $filter["groupids"]);
    if ($hostGroupFilter !== "") {
        $hostGroupFilter = "\n inner join hosts_groups hg \n on (hg.hostid = ite.hostid) AND " . $hostGroupFilter;
    }
    $query = 'select hos.host, hos.name as visible_name, ite.name, ite.itemid, hos.hostid, itrt.error, ite.key_ ' .
            '  from items ite ' .
            '  inner join item_rtdata itrt' .
            '     on (itrt.itemid = ite.itemid) ' . 
            '  inner join hosts hos ' .
            '     on (hos.hostid = ite.hostid) '
            . ($hostFilter == "" ? "" : " AND ") . $hostFilter
            . $hostGroupFilter
            . ($filter['inactiveHosts'] == 1 ? "and hos.status = 0" : "")
            . ' where itrt.state = 1 AND ite.status = 0 '
            . ($filter["item"] == "" ? "" : ' AND ite.key_ like ' . quotestr($filter["item"] . "%"))
            . ' order by hos.host, ite.name'
    ;
    // Build a list of items with required key ---------------------------------
    $result = DBselect($query);
    $cont = 0;
    while ($rowItem = DBfetch($result)) {
        $report[$cont]['host_name'] = ($rowItem["visible_name"] !== "" ? $rowItem["visible_name"] : $rowItem["host"]);
        $report[$cont]['itemid'] = $rowItem["itemid"];
        $report[$cont]['hostid'] = $rowItem["hostid"];
        $report[$cont]['error'] = $rowItem["error"];
        $report[$cont]['key_'] = $rowItem["key_"];
        $cont++;
    }
} else {
    zbxeNeedFilter(_('Specify some filter condition to see the values.'));
}

/* * ***************************************************************************
 * Display
 * ************************************************************************** */
switch ($filter["format"]) {
    case PAGE_TYPE_CSV:
        echo zbxeToCSV(["Host", "Item", "Key", "Error Message"]);
        break;
    case PAGE_TYPE_JSON:
        $jsonResult = [];
        break;
    default:
        require_once 'include/views/js/monitoring.latest.js.php';
        commonModuleHeader($moduleName, $moduleTitle, true);
        $multiSelectHostData = selectedHosts($filter['hostids']);
        $multiSelectHostGroupData = selectedHostGroups($filter['groupids']);

        $leftFilter = (new CFormList())
          ->addRow(_('Host Groups'), multiSelectHostGroups($multiSelectHostGroupData))
          ->addRow(_('Key'), [ (new CTextBox('item', $filter['item']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)])
        ;
        $leftFilter->addItem(new CInput('hidden', 'action', $filter["action"]));

        $rightFilter = (new CFormList())
                ->addRow(_('Hosts'), multiSelectHosts($multiSelectHostData))
                ->addRow(_zeT('Output format'), buttonOutputFormat('format', (int) $filter['format']))
                ->addRow(bold(_zeT('Inactive hosts')), buttonOptions("inactiveHosts", (int) $filter["inactiveHosts"], [_('Show'), _('Hide')]))
        ;

        $dashboard->addItem(newFilterTab($moduleName,[$leftFilter, $rightFilter]));

        $table->setHeader(array($toggle_all, (new CColHeader(_('Host')))
            , _('Item'), _('Key'), _('Error'), _('Actions')));

        break;
}

$lastHostID = -1;
foreach ($report as $row) {
    $item = getItem($row['itemid']);
    $state_css = ($item['state'] == ITEM_STATE_NOTSUPPORTED) ? ZBX_STYLE_GREY : null;
    if ($filter["format"] == PAGE_TYPE_HTML && $row['hostid'] !== $lastHostID) {
        $resumo = zbxeFieldValue("select count(*) as total from items ite " 
                . '  inner join item_rtdata itrt'
                . '     on (itrt.itemid = ite.itemid) ' 
                . "where itrt.state = 1 and ite.status = 0 and ite.hostid = " . $row['hostid']
                . ($filter["item"] == "" ? "" : ' AND ite.name like ' . quotestr($filter["item"] . "%"))
                , 'total'
        );
        $table->addRow([
                    (new CDiv())
                    ->addClass(ZBX_STYLE_TREEVIEW)
                    ->addClass('app-list-toggle')
                    ->setAttribute('data-app-id', $row['hostid'])
                    ->setAttribute('data-open-state', 1)
                    ->addItem(new CSpan())
            , [(new CLink($row["host_name"], ""))->onClick("javascript: return redirect('hosts.php?form=update&hostid=" . $row['hostid']
                        . "&groupid=0', 'post', 'sid', true);"
                )]
            , (new CCol("(" . _n('%1$s Item', '%1$s Items', $resumo) . ")"))->setColSpan(4)
        ]);
    }
    $lastHostID = $row["hostid"];
    switch ($filter["format"]) {
        case PAGE_TYPE_CSV;
            echo zbxeToCSV([$row["host_name"], $item['name_expanded']
                , $item['key_'], $row['error']]);
            break;
        case PAGE_TYPE_JSON;
            $jsonResult[count($jsonResult)] = ['host' => $row["host_name"]
                , 'item' => $item['name_expanded'], 'key_' => $item['key_']
                , 'error' => $row['error']];
            break;
        default;
            $tableRow = new CRow([
                '', ''
                , (new CCol($item["name_expanded"], 1))->addClass($state_css)
                , (new CCol($item["key_"], 1))->addClass($state_css)
                , (new CCol($row["error"], 1))->addClass($state_css)
                , [(new CLink(_('Disable'), ""))->onClick("javascript: return redirect('items.php?group_itemid[]=" . $row['itemid']
                            . "&hostid=" . $row['hostid']
                            . "&action=item.massdisable&sid=180b233e4008f20e', 'post', 'sid', true);"
                    )]
            ]);
            $tableRow->setAttribute('parent_app_id', $row['hostid']);
            $table->addRow($tableRow);
            break;
    }
}
/* * ***************************************************************************
 * Display Footer
 * ************************************************************************** */
switch ($filter["format"]) {
    case PAGE_TYPE_CSV;
        break;
    case PAGE_TYPE_JSON;
        echo json_encode($jsonResult, JSON_UNESCAPED_UNICODE);
        break;
    default;
        $form->addItem([ $table]);
        $dashboard->addItem($form)->show();
        break;
}
