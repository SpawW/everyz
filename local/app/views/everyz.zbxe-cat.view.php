<?php

/*
 * * Purpose: Report of capacity and trends
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
 * */


/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
$moduleName = "zbxe-cat";
$baseProfile .= $moduleName;
$moduleTitle = 'Capacity and Trends';

$intervalDesc = array('', _('Day'), _zeT('Week'), _zeT('Month'), _('Year'));
$intervalFactor = array(0, 1, 7, 30, 365);
$intervalFactor2 = array(0, '+1 days', '+1 week', '+1 months', '+1 years');
$sourceAgregator = array('hu.value_max', 'hu.value_min', 'hu.value_avg');
$intervalMask = array('', '%d/%m/%Y', '%U', '%m/%Y', '%Y');
$intervalMask2 = array('', 'd/m/Y', 'W (d/m/Y)', 'm/Y', 'Y');
$intervalMaskSort = array('', '%Y%m%d', '%Y%U', '%Y%m', '%Y');
// Common fields
addFilterParameter("format", T_ZBX_INT, 0, false, false, false);
addFilterActions();

// Specific fields

addFilterParameter("hostids", T_ZBX_INT, [], true, true);
//addFilterParameter("hostids", T_ZBX_INT, NULL, true, true);
addFilterParameter("agregation", T_ZBX_INT);
addFilterParameter("item", T_ZBX_STR);
addFilterParameter("filter_timesince", T_ZBX_STR);
addFilterParameter("filter_timetill", T_ZBX_STR);
addFilterParameter("timeshiftsource", T_ZBX_INT, 2);
addFilterParameter("timeshiftprojection", T_ZBX_INT, 2);

addFilterParameter("num_projection", T_ZBX_INT, 0);

check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
checkAccessHost('hostids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function week2date($year, $week, $weekday = 7) {
    global $filter;
    if ($filter["timeshiftsource"] == 2) {
        $time = mktime(0, 0, 0, 1, (4 + ($week - 1) * 7), $year);
        $this_weekday = intval(date('N', $time));
        $tmp = $weekday - $this_weekday;
        return " - " . date('d/m/y', mktime(0, 0, 0, 1, (4 + ($week - 1) * 7 + ($tmp)), $year));
    } else {
        return "";
    }
}

function buildQueryReport($itemid, $logTable) {
    global $DB, $filter;
    global $intervalMask, $sourceAgregator;

    $query = "\n"
            . "SELECT " . ($DB['TYPE'] == ZBX_DB_POSTGRESQL ? " DISTINCT ON(a.ano,a.mes,a.momento) " : "" )
            . "it.units, it.description, a.ano, a.mes, a.dia, a.momento, AVG(a.valor) AS valor
  FROM items it
 INNER JOIN
(SELECT hu.itemid,
DATE_FORMAT(FROM_UNIXTIME(hu.clock), '%Y') AS ano, DATE_FORMAT(FROM_UNIXTIME(hu.clock), '%m') AS mes, DATE_FORMAT(FROM_UNIXTIME(hu.clock), '%d') AS dia,
DATE_FORMAT(FROM_UNIXTIME(hu.clock), '" . $intervalMask[$filter["timeshiftsource"]] . "') AS momento, "
            . $sourceAgregator[$filter["agregation"]] . " AS valor
FROM " . $logTable . " hu
WHERE hu.clock between " . $filter["filter_timesince"] . " AND  "
            . $filter["filter_timetill"] . " AND hu.itemid = " . $itemid . "
) a
ON a.itemid = it.itemid
WHERE it.itemid = " . $itemid . "
GROUP BY it.units, a.ano, a.mes, a.dia, it.description, a.momento
ORDER BY a.ano, a.mes, a.momento
";
    if ($DB['TYPE'] == ZBX_DB_POSTGRESQL) {
        $query = str_replace('DATE_FORMAT', 'to_char', $query);
        $query = str_replace('FROM_UNIXTIME', 'to_timestamp', $query);
        $query = str_replace('%Y%m%d', 'YYYYMMDD', $query);
        $query = str_replace('%m', 'MM', $query);
        $query = str_replace('%d', 'DD', $query);
        $query = str_replace('%Y', 'YYYY', $query);
        $query = str_replace('%U', 'WW', $query);
//		$query = str_replace('group by','group by it.units, it.description, hu.clock, ',$query);
    }
    return $query;
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    resetProfile('hostids', true);
    $filter['filter_timesince'] = zbxDateToTime(date(TIMESTAMP_FORMAT_ZERO_TIME, time() - (SEC_PER_DAY * 30)));
    $filter['filter_timetill'] = zbxDateToTime(date(TIMESTAMP_FORMAT_ZERO_TIME, time()));
    $filter['item'] = "";
    $filter['agregation'] = 1;
    $filter['timeshiftsource'] = 1;
    $filter['timeshiftprojection'] = 2;
    $filter['num_projection'] = 7;
    $filter['filter_rst'] = NULL;
    $filter['filter_set'] = NULL;
} else { // Put the date in required format
    $filter['filter_timesince'] = zbxDateToTime($filter['filter_timesince'] ? $filter['filter_timesince'] : date(TIMESTAMP_FORMAT_ZERO_TIME, time() - (SEC_PER_DAY * 30)));
    $filter['filter_timetill'] = zbxDateToTime($filter['filter_timetill'] ? $filter['filter_timetill'] : date(TIMESTAMP_FORMAT_ZERO_TIME, time()));
}

$finalReport = Array();
// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {
    // Check if all required fields have values
    checkRequiredField("hostids", _zeT("You need to provide a least one host in filter!"));
    checkRequiredField("item", _zeT("You need to provide a item key for analisys!"));
    if ($requiredMissing == false) {
        $hostFilter = zeDBConditionInt('it.hostid', getRequest("hostids"));
        // Build a list of items with required key
        $query = "SELECT it.itemid, it.hostid FROM items it WHERE it.key_ LIKE " . quotestr($filter['item'] . "%")
                . ($hostFilter == "" ? "" : " AND ") . $hostFilter . "\n order by it.hostid, it.itemid";
        $result = DBselect($query);
        $itemids = "";
        while ($rowItem = DBfetch($result)) {
            $itemids .= ($itemids !== "" ? ", " : "") . $rowItem["itemid"];
            $itemType = zbxeFieldValue("select value_type from items it where it.itemid = " . $rowItem["itemid"], "value_type");
            $tabela_log = ($itemType == 0 ? "trends" : "trends_uint");
            $casasDecimais = ($itemType == 0 ? 4 : 0);

            $queryReport = buildQueryReport($rowItem["itemid"], $tabela_log);
            // Recover data from trends ========================================
            $resultReport = DBselect($queryReport);
            $report = Array();
            $cont = 0;
            while ($row = DBfetch($resultReport)) {
                if ($cont == 0) {
                    $maximo = $primeiro = $ultimo = $minimo = floatval($row['valor']);
                    $unidade = $row['units'];
                }
                $report[$cont]['itemid'] = $rowItem["itemid"];
                $report[$cont]['hostid'] = $rowItem["hostid"];
                $report[$cont]['momento'] = $row['momento'] . week2date($row['ano'], $row['momento']);
                $report[$cont]['valor'] = round(floatval($row['valor']), $casasDecimais);
                $report[$cont]['tipo'] = _zeT('Data from history');
                $dia = $row['dia'];
                $mes = $row['mes'];
                $ano = $row['ano'];
                $minimo = floatval(($minimo >= $report[$cont]['valor'] ? $report[$cont]['valor'] : $minimo));
                $maximo = floatval(($maximo <= $report[$cont]['valor'] ? $report[$cont]['valor'] : $maximo));
                $cont++;
            }
            // Adjust report to selected projection ============================
            if ($cont > 0) {

                $ultimoHistory = $ultimo = $report[($cont - 1)]['valor'];
                $tendencia = (($maximo - $minimo) / round($cont * 1)) * ($primeiro < $ultimo ? 1 : -1) / ($intervalFactor[$filter["timeshiftsource"]]);

                $dataAtual = mktime(0, 0, 0, $mes, $dia, $ano);
                if ($filter["timeshiftprojection"] > $filter["timeshiftsource"]) {
                    $dataAtual = strtotime($intervalFactor2[$filter["timeshiftsource"]], $dataAtual);
                }
                if ($filter["timeshiftprojection"] >= $filter["timeshiftsource"] || $filter["timeshiftsource"] == 2) {
                    $dataAtual = strtotime($intervalFactor2[$filter["timeshiftprojection"]], $dataAtual);
                }
                // Aplicando o fator de tendência tendência --------------------------
                for ($i = 0; $i < intval(getRequest('num_projection', 0)); $i++) {
                    $format = "d/m/Y";
                    $proximoDia = date($intervalMask2[$filter["timeshiftprojection"]], $dataAtual);
                    $dataAtual = strtotime($intervalFactor2[$filter["timeshiftprojection"]], $dataAtual);
                    $report[$cont]['momento'] = $proximoDia;
                    $report[$cont]['valor'] = round(floatval($ultimo) + $tendencia * ($intervalFactor[$filter["timeshiftprojection"]]), $casasDecimais);

                    $ultimo = $report[$cont]['valor'];
                    $report[$cont]['tipo'] = _zeT('Trend');
                    $cont++;
                }
                $finalReport[count($finalReport)] = [$report, $ultimoHistory, $report[($cont - 1)]['valor'], ($primeiro < $ultimo ? 1 : -1)];
            }
        }
    }
} else {
    zbxeNeedFilter(_('Specify some filter condition to see the values.'));
}

/* * ***************************************************************************
 * Display
 * ************************************************************************** */

switch ($filter["format"]) {
    case PAGE_TYPE_CSV:
        echo "hostid;name;moment;value;type;\n";
        break;
    case PAGE_TYPE_JSON:
        $jsonResult = [];
        break;
    default: // HTML
        require_once 'include/views/js/monitoring.latest.js.php';
        commonModuleHeader($moduleName, $moduleTitle, true);
// Get the multiselect hosts
        $multiSelectHostData = selectedHosts($filter['hostids']);
// Projection data
        $cmbTimeSource = (new CRadioButtonList('timeshiftsource', (int) $filter['timeshiftsource']))->setModern(true);
        $cmbTimeProjection = (new CRadioButtonList('timeshiftprojection', (int) $filter['timeshiftprojection']))->setModern(true);

        for ($i = 1; $i < count($intervalDesc); $i++) {
            $cmbTimeSource->addValue($intervalDesc[$i], $i);
            $cmbTimeProjection->addValue($intervalDesc[$i], $i);
        }
        $cmbAgregation = (new CRadioButtonList('agregation', (int) $filter['agregation']))->setModern(true);
        $cmbAgregation->addValue(_zeT('Max'), 0)->addValue(_zeT('Min'), 1)->addValue(_zeT('Avg'), 2);
//$widget = (new CFilter('web.latest.filter.state'));
        // Form to filter data
        $widget = newFilterWidget($moduleName);
// Source data filter
        $tmpColumn = new CFormList();
        $tmpColumn->addRow(
                        _('Hosts'), (new CMultiSelect([
                    'name' => 'hostids[]',
                    'objectName' => 'hosts',
                    'data' => $multiSelectHostData,
                    'popup' => [
                        'parameters' => 'srctbl=hosts&dstfrm=zbx_filter&dstfld1=hostids_&srcfld1=hostid' .
                        '&real_hosts=1&multiselect=1'
                    ]]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH))
                ->addRow(_('Key'), [ (new CTextBox('item', $filter['item']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH),
                    (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
                    (new CButton('item_name', _('Select')))
                    ->addClass(ZBX_STYLE_BTN_GREY)
                    ->onClick('return zbxePopUp("popup.php?srctbl=items&srcfld1=key_&real_hosts=1&dstfld1=item' .
                            '&with_items=1&dstfrm=zbx_filter");')
                ])
                ->addRow(_zeT('Analysis'), $cmbTimeSource)
                ->addRow(_zeT('Function'), $cmbAgregation)
        ;

        $tmpColumn->addItem(new CInput('hidden', 'action', $action));
        $widget->addColumn($tmpColumn);

        $tmpColumn = (new CFormList())
                ->addRow(_('From'), createDateSelector('filter_timesince', $filter['filter_timesince'], 'filter_timetill'))
                ->addRow(_('To'), createDateSelector('filter_timetill', $filter['filter_timetill'], 'filter_timesince'))
                ->addVar('filter_timesince', date(TIMESTAMP_FORMAT, $filter['filter_timesince']))
                ->addVar('filter_timetill', date(TIMESTAMP_FORMAT, $filter['filter_timetill']))
                ->addRow(_zeT('Projection'), $cmbTimeProjection)
                ->addRow(_zeT('Amount'), (new CNumericBox('num_projection', $filter['num_projection'], 2, false, false, false))->setWidth(ZBX_TEXTAREA_2DIGITS_WIDTH))
                ->addRow(_zeT('Output format'), buttonOutputFormat('format', (int) $filter['format']))
        ;
        $tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
        $widget->addColumn($tmpColumn);
        $dashboard->addItem($widget);
        // Build the report ------------------------------------------------------------
        $table->setHeader(array($toggle_all, (new CColHeader(_('Host')))->addStyle('width: 25%')
            , _zeT('Instant'), _zeT('Value'), _zeT('Type')));
        break;
}


$exportReport = [];
// Data
for ($iRep = 0; $iRep < count($finalReport); $iRep++) {
    $report = $finalReport[$iRep][0];

    $item = getItem($report[0]['itemid']);
    $state_css = ($item['state'] == ITEM_STATE_NOTSUPPORTED) ? ZBX_STYLE_GREY : null;
// Add the item group report
    if ($filter["format"] == PAGE_TYPE_HTML) {
        $hostName = (new CSpan([hostName([$report[0]["hostid"]]), " - ", bold($item['name_expanded'])]));
        $table->addRow([
                    (new CDiv())
                    ->addClass(ZBX_STYLE_TREEVIEW)
                    ->addClass('app-list-toggle')
                    ->setAttribute('data-app-id', $report[0]['itemid'])
                    ->setAttribute('data-open-state', 0)
                    ->addItem(new CSpan()),
            $hostName,
            (new CCol(['(' . _zeT("Analysis") . "/" . _zeT("Projection") . ') '])),
            (new CCol([ "(" . formatHistoryValue($finalReport[$iRep][1], $item, false)
        . "/" . formatHistoryValue($finalReport[$iRep][2], $item, false) . ')'
            ])),
            (new CCol([($finalReport[$iRep][3] == 1 ? _zeT("Upward trend") : _zeT("Downward trend"))]))
        ]);
    }

    $points = "";

    for ($i = 0; $i < count($report); $i++) {
        switch ($filter["format"]) {
            case PAGE_TYPE_CSV;
                echo zbxeToCSV([hostName($report[0]["hostid"]), $item['name_expanded']
                    , $report[$i]['momento'], $report[$i]['valor'], $report[$i]['tipo']]);
                break;
            case PAGE_TYPE_JSON;
                $jsonResult[count($jsonResult)] = ['host' => hostName($report[0]["hostid"])
                    , 'item' => $item['name_expanded'], 'moment' => $report[$i]['momento']
                    , 'value' => $report[$i]['valor'], 'type' => $report[$i]['tipo']];
                break;
            default;
                $momento = new CCol($report[$i]['momento'], 1);
                $valor = convert_units(array(
                    'value' => $report[$i]['valor'],
                    'units' => $unidade));

                $tipo = new CCol($report[$i]['tipo'], 1);
                $row = new CRow([
                    '', (new CCol($momento, 1))->addClass($state_css)
                    , (new CCol($valor, 1))->addClass($state_css)
                    , $tipo->addClass($state_css)
                ]);
                $row->setAttribute('parent_app_id', $report[0]['itemid']);

                $table->addRow($row);

                break;
        }
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
