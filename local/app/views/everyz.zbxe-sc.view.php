<?php

/*
 * * Purpose: Report of Monitoration costs
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
 * Agrupamento lógico: item, host, grupo de hosts, Template
 * Agrupamento temporal: dia, semana, mês, ano
 * Filtro: Agora, Periodo, Grupo, Host, Template, Aplicação, item
 * WebService para salvar retratos em tabela específica
 * Método para limpeza de dados
 * Como calcular o VPS em coleta agendada e em intervalo flexível
 * */

require_once 'include/views/js/monitoring.latest.js.php';

// Definitions -----------------------------------------------------------------
define("HISTORYSIZE", 50);
define("TRENDSIZE", 128);

function initiateGlobalMacro($macroName, $value) {
    $currentValue = globalMacroValue($macroName, "--EVERYZ--");
    if ($currentValue == "--EVERYZ--") {
        var_dump(
                API::UserMacro()->createGlobal([ "macro" => $macroName, "value" => $value])
        );
        return $value;
    } else {
        return globalMacroValue($macroName, "--EVERYZ--");
    }
}

function checkGlobalMacros() {
    global $globalMacros;
    if (!isset($globalMacros)) {
        $globalMacros = API::UserMacro()->get([
            'output' => ['globalmacroid', 'macro', 'value'],
            'globalmacro' => true,
            'preservekeys' => true
        ]);
    }
    // Macro global do valor da UBM
    define("UBM_VALUE", initiateGlobalMacro('{$EVERYZ_UBM_VALUE}', '1'));
    // Macro global do referencial em megas do armazenamento
    define("REFERENCE_GB", initiateGlobalMacro('{$EVERYZ_UBM_REF_GB}', '0.2'));
    // Macro global do referencial em VPS do processamento
    define("REFERENCE_VPS", initiateGlobalMacro('{$EVERYZ_UBM_REF_VPS}', '0.35'));
}

// Check if the basic macros have values
checkGlobalMacros();

// Function
function totalUBM($vps, $gb) {
    $refVPS = REFERENCE_VPS;
    $refGB = REFERENCE_GB;
    $fatorVPS = 0.69;
    $fatorGB = 0.19;
    $fatorHost = 0.12;
    // Transformando de bytes para GB
    $gb = round($gb / 1024 / 1024 / 1024, 6);
    $totalUBM = round(
            (
            (
            ($vps * 100 / $refVPS) / 100 * $fatorVPS
            ) // Processing custs
            + (
            ($gb * 100 / $refGB) / 100 * $fatorGB
            ) // Storage Custs
            + ($fatorHost) // Administration custs
            ) / 10
            , 6)
    ;
    /*    var_dump('<br>vps ' . $vps . " gb " . ($gb)
      . " refvps " . $refVPS . " refgb " . $refGB
      . " vpsrelativo " . ($vps * 100 / $refVPS) . " gbrelativo " . ($gb * 100 / $refGB)
      . " total ubm " . $totalUBM);
     */
    return $totalUBM;
}

function valorUBM($totalUBM, $cotacao) {
    return $totalUBM * $cotacao;
}

// Configuration variables =====================================================
$moduleName = "zbxe-sc";
$baseProfile .= $moduleName;

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("hostids", PROFILE_TYPE_STR, [], true, true);
addFilterParameter("groupids", PROFILE_TYPE_STR, [], true, true);

// Mode of report
addFilterParameter("mode", T_ZBX_STR, "", false, false, false);
addFilterParameter("groupView", T_ZBX_INT);
addFilterParameter("groupTime", T_ZBX_INT);
addFilterParameter("notMonitored", T_ZBX_INT);

// Time filter
addFilterParameter("filter_timesince", T_ZBX_STR);
addFilterParameter("filter_timetill", T_ZBX_STR);
addFilterParameter("timeshiftsource", T_ZBX_INT, 2);
addFilterParameter("timeshiftprojection", T_ZBX_INT, 2);

addFilterParameter("format", T_ZBX_INT);

check_fields($fields);

/*
 * Display
 */
$dashboard = (new CWidget())
        ->setTitle('EveryZ - ' . _zeT('Costs'))
        ->setControls(fullScreenIcon());
//$dashboard->addVar('fullscreen', $filter['fullscreen']);
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('GET', 'everyz.php'))->setName('cat');
$table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);

$groupOptions = array(_('Item'), _('Host'), _('Host Groups'), _('Template'));
$groupMoment = array(_('Day'), _('Week'), _('Year'));

// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    $filter['hostids'] = [];
    $filter['filter_timesince'] = zbxDateToTime(date(TIMESTAMP_FORMAT_ZERO_TIME, time() - (SEC_PER_DAY * 30)));
    $filter['filter_timetill'] = zbxDateToTime(date(TIMESTAMP_FORMAT_ZERO_TIME, time()));
    $filter['groupView'] = 1;
    $filter['groupTime'] = 1;
    $filter['timeshiftsource'] = 1;
    $filter['timeshiftprojection'] = 2;
    $filter['num_projection'] = 7;
    $filter['filter_rst'] = NULL;
} else { // Put the date in required format
    $filter['filter_timesince'] = zbxDateToTime($filter['filter_timesince'] ? $filter['filter_timesince'] : date(TIMESTAMP_FORMAT_ZERO_TIME, time() - (SEC_PER_DAY * 30)));
    $filter['filter_timetill'] = zbxDateToTime($filter['filter_timetill'] ? $filter['filter_timetill'] : date(TIMESTAMP_FORMAT_ZERO_TIME, time()));
}

$widget = (new CFilter('web.latest.filter.state'));

// Source data filter
$tmpColumn = new CFormList();
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups(selectedHostGroups($filter['groupids'])))
        ->addRow(_zeT('Not monitored items'), buttonOptions('notMonitored', $filter['notMonitored'], array(_("Hide"), _("Show"))))
//        ->addRow(_zeT('Group by'), buttonOptions('groupView', $filter['groupView'], $groupOptions))
//        ->addRow(_zeT('Time group by'), buttonOptions('groupTime', $filter['groupTime'], $groupMoment))
;
$tmpColumn->addItem(new CInput('hidden', 'action', $action));
$widget->addColumn($tmpColumn);

$tmpColumn = (new CFormList())
        ->addRow(_('Hosts'), multiSelectHosts(selectedHosts($filter['hostids'])))
//        ->addRow(_('From'), createDateSelector('filter_timesince', $filter['filter_timesince'], 'filter_timetill'))
//        ->addRow(_('To'), createDateSelector('filter_timetill', $filter['filter_timetill'], 'filter_timesince'))
//        ->addVar('filter_timesince', date(TIMESTAMP_FORMAT, $filter['filter_timesince']))
//        ->addVar('filter_timetill', date(TIMESTAMP_FORMAT, $filter['filter_timetill']))
        ->addRow(_zeT('Output format'), buttonOutputFormat('format', $filter['format']))

;
$tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
$widget->addColumn($tmpColumn);


$dashboard->addItem($widget);
$finalReport = Array();

// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {
    // Check if all required fields have values
//    checkRequiredField("hostids", "You need to provide a least one host in filter!");

    if ($requiredMissing == false) {
        $hostFilter = zeDBConditionInt('it.hostid', getRequest("hostids"));
        $hostGroupFilter = zbxeDBConditionInt('hg.groupid', $filter["groupids"]);
        if ($hostGroupFilter !== "") {
            $hostGroupFilter = "\n inner join hosts_groups hg \n on (hg.hostid = it.hostid) AND " . $hostGroupFilter;
        }

        // Recover data from items =============================================
        $query = "SELECT hos.name as host_name, it.hostid, it.name as item_name, it.key_ as item_key, it.delay, "
                . "\n     it.history, it.trends, it.status, 86400 / it.delay * it.history AS history_costs, it.trends * 24 AS trends_costs, it.itemid as itemid "
                . "\n FROM items it "
                . "\n INNER JOIN hosts hos ON hos.hostid = it.hostid "
                . ($hostFilter == "" ? "" : " AND ") . $hostFilter
                . $hostGroupFilter
                . " WHERE it.flags <> 2 and it.type not in (2,17)"
                . ($filter["notMonitored"] == 0 ? " AND it.status = 0 " : "")
                . "\n Order by hos.name, it.key_ ";
        $report = Array();
        $hostsReport = Array();
        $lastItemID = $cont = $historyTotal = $trendTotal = $storageTotal = $vpsTotal = $ubmTotal = (float) 0;
        // Ids importantes no relatorio
        $indexVPS = 7;
        $indexUBM = 8;
        $indexTotal = 9;
        // Recupera o valor de UBM Global na macro {$EVERYZ_UBM_VALUE}
        $result = DBselect($query);
        while ($row = DBfetch($result)) {
            if ($lastItemID !== $row['itemid']) {
                $report[$cont][-1] = $row['hostid'];
                $report[$cont][0] = $row['host_name'];
                $report[$cont][1] = $row['item_name'];
                $report[$cont][2] = $row['item_key'];
                $report[$cont][3] = $row['delay'] . " / " . $row['history'] . " / " . $row['trends'];
                $report[$cont][4] = ($row['status'] == 1 ? _('Not monitored') : _('Active'));
                $historyRows = round(floatval($row['history_costs']), 0); // antigo 7
                $historyTotal += $historyRows;
                $trendRows = round(floatval($row['trends_costs']), 0);
                $report[$cont][5] = "[" . $historyRows . " / " . $trendRows . "] " . _zeT('rows'); // antigo 8
                $trendTotal += $trendRows; // Antigo 8
                // Total Rows
                $totalSize = ($historyRows * HISTORYSIZE) + ($trendRows * TRENDSIZE);
                $storageTotal += $totalSize;
                $report[$cont][6] = convert_units(array('value' => $totalSize, 'units' => 'B'));
                $report[$cont][$indexVPS] = round(1 / floatval($row['delay']), 4);
                $vpsTotal += (float) $report[$cont][$indexVPS];
                $report[$cont][$indexUBM] = totalUBM($report[$cont][$indexVPS], $totalSize);
                $report[$cont][$indexTotal] = valorUBM($report[$cont][$indexUBM], hostMacroValue($row["hostid"], '{$EVERYZ_UBM_VALUE}'));
                $ubmTotal += (float) $report[$cont][$indexUBM];
                if (!isset($hostsReport[$row["hostid"]])) {
                    //Qtd Itens / Sum [Delay / History / Trends] / Sum [History / Trends] / Sum [Storage / VPS / BMU / $]
                    $hostsReport[$row["hostid"]] = ["item" => 0, "delay" => 0, "history" => 0
                        , "trends" => 0, "rowHistory" => 0, "rowTrend" => 0, "storage" => 0
                        , "vps" => 0, "ubm" => 0, "money" => 0];
                }
                $hostsReport[$row["hostid"]]["item"] += 1;
                $hostsReport[$row["hostid"]]["delay"] += $row["delay"];
                $hostsReport[$row["hostid"]]["history"] += $row["history"];
                $hostsReport[$row["hostid"]]["trends"] += $row["trends"];
                $hostsReport[$row["hostid"]]["rowHistory"] += $historyRows;
                $hostsReport[$row["hostid"]]["rowTrend"] += $trendRows;
                $hostsReport[$row["hostid"]]["storage"] += $totalSize;
                $hostsReport[$row["hostid"]]["vps"] += $report[$cont][$indexVPS];
                $hostsReport[$row["hostid"]]["ubm"] += $report[$cont][$indexUBM];
                $hostsReport[$row["hostid"]]["money"] += $report[$cont][$indexTotal];

                $cont++;
            }
            $lastItemID = $row['itemid'];
        }
    }
    //var_dump($hostsReport);
} else {
    $table->setNoDataMessage(_('Specify some filter condition to see the values.'));
}

// Build the report header -----------------------------------------------------

$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');

$locale = localeconv();
$currency = (strlen($locale['currency_symbol']) > 1 ? $locale['currency_symbol'] : '$');
switch ($filter["format"]) {
    case 1;
        $table->setHeader(array(_zeT("Data")));
        $fieldsExport = array("Host", "Item", "Key"
            , "Delay Average", "History Average", "Trends Average"
            , "Status"
            , "History rows", "Trends rows"
            , "Storage", "VPS", "BMU", "HostID"
            , _zeT($currency)
        );
        break;
    case 2;
        $fieldsExport = array("Host", "Item", "Key"
            , ["Delay", "History", "Trends"]
            , "Status"
            #, "Rows [
            , ["HistoryRows", "TrendsRows"]
            , "Storage", "VPS", "BMU"
            , _zeT($currency)
        );
        break;
    case 0;
        $table->setHeader(array($toggle_all, _("Host"), _("Item"), _("Key")
            , (new CColHeader(_("Delay") . " / " . _("History") . " / " . _("Trends")))->addStyle('width: 15%')
            , (new CColHeader(_("Status")))->addStyle('width: 5%')
            , (new CColHeader(_("History") . " / " . _("Trends")))->addStyle('width: 12%')
            , _zeT("Storage"), _zeT("VPS"), _zeT("BMU")
            , _zeT($currency)
        ));
        break;
}
// Building the report ---------------------------------------------------------
if (isset($cont)) {
    $linha = array('', '');
    $linhasDesc = " " . _zeT("rows");
    $cont2 = count($report[0]) - 2;
    $currHostID = -1;
    $exportData = "";
    for ($i = 0; $i < $cont; $i++) {
        switch ($filter["format"]) {
            case 0; // HTML
                for ($x = 1; $x <= $cont2; $x++) {
                    $linha[$x + 1] = new CCol($report[$i][$x], 1);
                }
                // UBM por host
                if ($currHostID !== $report[$i][-1]) {
                    $hostSum = $hostsReport[$report[$i][-1]];
                    $table->addRow([
                                (new CDiv())
                                ->addClass(ZBX_STYLE_TREEVIEW)
                                ->addClass('app-list-toggle')
                                ->setAttribute('data-app-id', $report[$i][-1])
                                ->setAttribute('data-open-state', 0)
                                ->addItem(new CSpan())
                        , $report[$i][0] // Hostname
                        , $hostSum["item"] . ' item' . ($hostSum["item"] > 1 ? 's' : '')
                        , ''
                        , _('Average') . " [ " . round($hostSum['delay'] / $hostSum["item"])
                        . " / " . round($hostSum['history'] / $hostSum["item"])
                        . " / " . round($hostSum['trends'] / $hostSum["item"]) . " ]"
                        , ''
                        , "[ " . $hostSum['rowHistory'] . " / " . $hostSum['rowTrend'] . " ] rows"
                        , convert_units(array('value' => $hostSum["storage"], 'units' => 'B'))
                        , $hostSum["vps"]
                        , $hostSum["ubm"]
                        , round($hostSum["money"], 2)
                    ]);
                }
                $currHostID = $report[$i][-1];
                // UBM por item
                $row = new CRow($linha);
                $row->setAttribute('parent_app_id', $report[$i][-1]);
                $table->addRow($row);
                break;
            case 1; // CSV
                $linhaExport = "";
                for ($x = 0; $x <= $cont2; $x++) {
                    switch ($x) {
                        case 3:
                            $fieldValue = explode("/", str_replace(" ", "", $report[$i][$x]));
                            $fieldValue = quotestr($fieldValue[0]) . ";" . quotestr($fieldValue[1]) . ";" . quotestr($fieldValue[2]);
                            break;
                        case 5:
                            $fieldValue = explode("/", str_replace(" ", "", str_replace("rows", "", str_replace("[", "", str_replace("]", "", $report[$i][$x])))));
                            $fieldValue = quotestr($fieldValue[0]) . ";" . quotestr($fieldValue[1]);
                            break;
                        default:
                            $fieldValue = quotestr($report[$i][$x]);
                            break;
                    }
                    $linhaExport .= $fieldValue . ";";
                }
                $exportData .= $linhaExport . $report[$i][-1] . ";" . "\n";
                break;
            case 2; // JSON
                $linhaExport = "";
                for ($x = 0; $x <= $cont2; $x++) {
                    $fieldTitle = $fieldsExport[$x];
                    switch ($x) {
                        case 3:
                            $fieldValue = explode("/", str_replace(" ", "", $report[$i][$x]));
                            //$fieldValue = quotestr($fieldValue[0]) . ";" . quotestr($fieldValue[1]) . ";" . quotestr($fieldValue[2]);
                            $linhaExport .= ", " . zbxeJSONKey($fieldTitle[0], $fieldValue[0])
                                    . ", " . zbxeJSONKey($fieldTitle[1], $fieldValue[1])
                                    . ", " . zbxeJSONKey($fieldTitle[2], $fieldValue[2])
                            ;

                            break;
                        case 5:
                            $fieldValue = explode("/", str_replace(" ", "", str_replace("rows", "", str_replace("[", "", str_replace("]", "", $report[$i][$x])))));
//                            $fieldValue = quotestr($fieldValue[0]) . ";" . quotestr($fieldValue[1]);
                            $linhaExport .= ", " . zbxeJSONKey($fieldTitle[0], $fieldValue[0])
                                    . ", " . zbxeJSONKey($fieldTitle[1], $fieldValue[1])
                            ;
                            break;
                        default:
                            $fieldValue = quotestr($report[$i][$x]);
                            $linhaExport .= ($x == 0 ? "" : ", ") . zbxeJSONKey($fieldTitle, $fieldValue);
                            break;
                    }
                    //. "\"" . $fieldTitle . "\": \"" . $fieldValue . "\"";
                }
                $exportData .= ($exportData === "" ? "" : ",") . "{" . $linhaExport
                        . ", " . zbxeJSONKey("hostid", $report[$i][-1]) . "}\n";
                break;
        }
    }
    if ($filter["format"] > 0) {
        switch ($filter["format"]) {
            case 1;
                $titleCSV = "";
                for ($x = 0; $x < count($fieldsExport); $x++) {
                    $titleCSV .= quotestr($fieldsExport[$x]) . ";";
                }
                $textArea = (new CTextArea('exportData', $titleCSV . "\n" . $exportData));
                break;
            case 2;
                $textArea = (new CTextArea('exportData', '{"data":[' . $exportData . ']}'));
                break;
        }
        $textArea->setWidth(800);
        $textArea->setRows(10);
        $table->addRow($textArea);
    }
}
$form->addItem([
    $table
        //Todo: Make export of data possible to select and to export to CSV, JSON using JavaScript
        //, new CActionButtonList('exportData', 'itemids', [ 0 => ['name' => _('Export as CSV')]])
]);

$dashboard->addItem($form)->show();
