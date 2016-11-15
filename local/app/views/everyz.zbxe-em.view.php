<?php

/*
 * * Purpose: Event screen with extra filter options 
 * *          and correlate information (temporal analysis of possible causes and effects)
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

require_once 'include/views/js/monitoring.latest.js.php';


// Configuration variables =====================================================
$moduleName = "zbxe-em";
$baseProfile .= $moduleName;
$momento = -1;
$resumo = $ultimoMomento = 0;
// Ajustar no everyz.php o suporte oficial a CSV/JSON export
$CSV_EXPORT = false;

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("hostids", PROFILE_TYPE_STR, [], true, true);
addFilterParameter("groupids", PROFILE_TYPE_STR, [], true, true);
addFilterParameter("mode", T_ZBX_STR, "", false, false, false);
addFilterParameter("item", T_ZBX_STR);
addFilterParameter("trigger", T_ZBX_STR);
addFilterParameter("p_triggerid", T_ZBX_INT);
addFilterParameter("p_min_events", T_ZBX_INT, 6);
addFilterParameter("groupmode", T_ZBX_INT, 3600);
addFilterParameter("p_check_range", T_ZBX_INT, 30);
addFilterParameter("p_stime", T_ZBX_INT);

//Timebar
addFilterParameter("stime", T_ZBX_STR, null);
addFilterParameter("period", T_ZBX_INT, null);
addFilterParameter("from", T_ZBX_INT, zbxDateToTime($filter["stime"]));
addFilterParameter("till", T_ZBX_INT);
//addFilterParameter("stime", T_ZBX_STR, null);
//addFilterParameter("stime", T_ZBX_STR, null);


check_fields($fields);

$range = $filter["p_check_range"] * 60;

$reportMode = $filter["mode"] == "report";

$filter["period"] = navigation_bar_calc();
$filter["till"] = $filter["from"] + $filter["period"];

/*
 * Functions
 */

function iniciaEvento($desc = '', $menores = 0, $maiores = 0, $menor_desc = '', $maior_desc = '') {
    return array(
        "momento" => $desc
        , "menores" => $menores
        , "maiores" => $maiores
        , "menor_desc" => $menor_desc
        , "maior_desc" => $maior_desc
    );
}

function imagem($tipo, $hint, $qtd) {
    if ($qtd > 0) {
        if ($tipo === "down") {
            $msg = "Possible cause.";
            $dep_type = "DEP_DOWN";
        } else {
            $msg = "Possible consequence.";
            $dep_type = "DEP_UP";
        }
        $img = new CImg('images/general/arrow_' . $tipo . '.png', $dep_type);
        $img->setAttribute('style', 'vertical-align: top; border: 0px;');
        $img->setHint(_zeT($msg) . "\n" . _zeT('Related incidents') . ": " . $qtd . "\n" . $hint);
        return $img;
    } else {
        return "";
    }
}

function exibeEvento($chave, $linha) {
    if (array_key_exists($chave, $linha)) {
        return array(imagem('down', $linha[$chave]['maior_desc'], $linha[$chave]['maiores'])
            , imagem('up', $linha[$chave]['menor_desc'], $linha[$chave]['menores'])
        );
    } else {
        return "";
    }
}

/*
 * Security
 */
if (getRequest('groupids') && !API::HostGroup()->isReadable(getRequest('groupids'))) {
    access_deny();
}
if (getRequest('hostids') && !API::Host()->isReadable(getRequest('hostids'))) {
    access_deny();
}
if (getRequest('triggerid') && !API::Trigger()->isReadable([getRequest('triggerid')])) {
    access_deny();
}
/*
 * Display
 */
if ($reportMode) {
    $dashboard = (new CWidget())
            ->setTitle('EveryZ - ' . _zeT('Event Management') . " - " . _zet("correlation"))
            ->setControls((new CList())
                    ->addItem(array(
                        SPACE . _zeT('Number of Incidents') . " >=" . SPACE,
                        (new CNumericBox('p_min_events', $filter["p_min_events"], 6))->addStyle('width: 25px')
            )))
            ->addItem(_zeT('Report generated on') . SPACE . '[' . zbx_date2str(_('d M Y H:i:s')) . ']')
    ;
} else {
    $dashboard = (new CWidget())
            ->setTitle('EveryZ - ' . _zeT('Event Management'))
            ->setControls(fullScreenIcon())

    ;
}
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('GET', 'everyz.php'))
        ->setName('ns')
        ->addVar('stime', $filter["stime"], 'stime_csv')
        ->addVar('period', $filter["period"], 'period_csv')
        ->addVar('page', getPageNumber(), 'page_csv')
        ->addVar('fullscreen', getRequest('fullscreen'))
;

;
$table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);

// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    resetProfile('hostids', true);
    resetProfile('groupids', true);
    resetProfile('item');
//resetProfile('application');
    $filter['filter_rst'] = NULL;
    $filter['filter_set'] = NULL;
}
// Get the multiselect hosts
$multiSelectHostData = selectedHosts($filter['hostids']);
// Get the multiselect hosts
$multiSelectHostGroupData = selectedHostGroups($filter['groupids']);
//error("adail");

$widget = (new CFilter('web.events.filter.state'));

// Source data filter
$tmpColumn = new CFormList();
$tmpColumn->addRow(_('Host Groups'), multiSelectHostGroups($multiSelectHostGroupData))
        ->addRow(_('Key'), [(new CTextBox('item', $filter['item']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH),
            (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
            (new CButton('item_name', _('Select')))
            ->addClass(ZBX_STYLE_BTN_GREY)
            ->onClick('return PopUp("popup.php?srctbl=items&srcfld1=key_&real_hosts=1&dstfld1=item' .
                    '&with_items=1&dstfrm=zbx_filter");')
        ])
        ->addRow(_zeT('Grouping mode'), (new CRadioButtonList('groupmode', (int) $filter['groupmode']))
                ->addValue(_('Hour'), 3600)
                ->addValue('30 ' . _('Minutes'), 1800)
                ->addValue('10 ' . _('Minutes'), 600)
                ->addValue(_('Minute'), 60)
                ->setModern(true)
        )
;

$tmpColumn->addItem(new CInput('hidden', 'action', $action));
$widget->addColumn($tmpColumn);

$tmpColumn = (new CFormList())
        ->addRow(_('Hosts'), multiSelectHosts($multiSelectHostData))
        /* ->addRow(_('Trigger'), [
          (new CTextBox('trigger', $filter["trigger"], true))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH),
          (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
          (new CButton('btn1', _('Select')))
          ->addClass(ZBX_STYLE_BTN_GREY)
          ->onClick('return PopUp("popup.php?dstfrm=zbx_filter&dstfld1=triggerid' .
          '&dstfld2=trigger&srctbl=triggers&srcfld1=triggerid&srcfld2=description' .
          '&real_hosts=1&monitored_hosts=1&with_monitored_triggers=1' .
          '");'
          )
          ]) */
        ->addRow(_zeT('Output format'), (new CRadioButtonList('format', (int) $filter['format']))->addValue('HTML', 0)->addValue('CSV', 1)->setModern(true))
;
$widget->addColumn($tmpColumn)
        ->addVar('stime', $filter["stime"])
        ->addVar('period', $filter["period"])
        ->addNavigator()
;

// Dont show the filter bar for report mode
if ($reportMode) {
    $trigOpt['monitored'] = true;
    $triggers = API::Trigger()->get($trigOpt);
    //$options['triggerids'] = zbx_objectValues($triggers, 'triggerid');

    $options = array(
        'source' => EVENT_SOURCE_TRIGGERS,
        'output' => API_OUTPUT_EXTEND, //API_OUTPUT_SHORTEN,
        'sortfield' => 'eventid',
        'sortorder' => ZBX_SORT_DOWN,
        'limit' => (10)
    );
    $report = array();
    $eventTitles = array();
    $count = 1;
    $tmp = $options;
    $tmp['objectids'] = $filter["p_triggerid"];
//    $tmp['triggerids'] = $reference;
    $tmp['time_till'] = $filter["stime"] + ($range);
    ;
    $tmp['sortfield'] = 'eventid';
    $tmp['sortorder'] = "DESC";
    $events = API::Event()->get($tmp);
    $listaTriggers = " ";
    $possui = false;
    foreach ($events as $enum => $event) { // Varrer as últimas X ocorrências do evento originado por aquela trigger;
        $tmp2 = $options;
        $from = $event['clock'] - ($range);
        $till = $event['clock'] + ($range);
        $tmp2['time_from'] = $from;
        $tmp2['time_till'] = $till;
        $events2 = API::Event()->get($tmp2);
        $eventTitles[$count] = zbx_date2str(_('d M H:i'), $event['clock']);
        foreach ($events2 as $enum => $event2) { // Varrer outros eventos relacionados dentro do range
            if ($event2['objectid'] != $event['objectid'] AND $event2['value'] == $event['value']) {
                //var_dump($event["eventid"] . "<br>");
                //var_dump($event2); //AQUI
                if (strpos($listaTriggers, $event2['objectid']) !== false) {
                    $report[$event2['objectid']]['count'] ++;
                } else {
                    $triggerInfo = API::Trigger()->get(array(
                        'output' => array('triggerid', 'description'),
                        'triggerids' => $event2['objectid'],
                        //'output' => API_OUTPUT_EXTEND,//API_OUTPUT_SHORTEN,
                        'selectHosts' => array('hostid', 'host'),
                        'expandDescription' => true
                            //, 'host' => true
                            //, (versaoZabbix() < 24 ? 'expandData' => true : null)
                    ));
                    $primeiroHostTrigger = hostName([$triggerInfo[0]["hosts"][0]["hostid"]]);
                    //var_dump($primeiroHostTrigger);
                    if (count($triggerInfo) > 0) {
                        $listaTriggers .= "," . $event2['objectid'];
                        $report[$event2['objectid']] = array("count" => intval(1)
                            , "description" => $triggerInfo[0]['description']
                            , "host" => $primeiroHostTrigger
                            //. $event2['objectid'] 
                            , "event1" => iniciaEvento("")
                            , "event2" => iniciaEvento("")
                            , "event3" => iniciaEvento("")
                            , "event4" => iniciaEvento("")
                            , "event5" => iniciaEvento("")
                            , "event6" => iniciaEvento("")
                            , "event7" => iniciaEvento("")
                            , "event8" => iniciaEvento("")
                            , "event9" => iniciaEvento("")
                            , "event10" => iniciaEvento("")
                        );
                        $possui = true;
                    }
                }
                if (count($triggerInfo) > 0) {
                    if ($event2['clock'] >= $event['clock']) {
                        $report[$event2['objectid']]["event" . $count]["menores"] += 1;
                        $report[$event2['objectid']]["event" . $count]["menor_desc"] .=
                                ($report[$event2['objectid']]["event" . $count]["menores"] > 1 ? "\n" : "")
                                . zbx_date2str(_('Y.m.d H:i:s'), $event2['clock']);
                    } else {
                        $report[$event2['objectid']]["event" . $count]["maiores"] += 1;
                        $report[$event2['objectid']]["event" . $count]["maior_desc"] .=
                                ($report[$event2['objectid']]["event" . $count]["maiores"] > 1 ? "\n" : "")
                                . zbx_date2str(_('Y.m.d H:i:s'), $event2['clock']);
                    }
                }
            }
        }
        // Somar quantas vezes o evento correlato ocorreu
        $count++;
    }
    // Verificação de segurança pois podem existir menos de 10 eventos para a trigger de origem.
    $tmp = count($eventTitles);
    if ($tmp < 10) {
        for ($i = $tmp; $i < 10; $i++) {
            $eventTitles[$i + 1] = _('Not Found');
            //echo "$i<br>";
        }
    }

    $table->setHeader(array(
        (new CColHeader(_zeT('Amount')))->addStyle('width: 5%'),
        (new CColHeader(_('Host')))->addStyle('width: 15%'),
        (new CColHeader(_('Trigger')))->addStyle('width: 25%'),
        $eventTitles[1],
        $eventTitles[2],
        $eventTitles[3],
        $eventTitles[4],
        $eventTitles[5],
        $eventTitles[6],
        $eventTitles[7],
        $eventTitles[8],
        $eventTitles[9],
        $eventTitles[10]
    ));
    if ($possui === false) { // Avisa que não foram encontrados eventos no período
        $tmp = new CCol(array(_zeT("Sem eventos para correlacionar com os parâmetros informados")), 'center');
        $tmp->setColSpan(13);
        $table->addRow($tmp);
    } else {
        // Ordenando o array
        $report = array_sort($report, 'count', SORT_DESC);
        foreach ($report as $enum => $linha) {
            if ($linha['count'] >= $filter["p_min_events"]) {
                $table->addRow(array(
                    $linha['count'],
                    $linha['host'],
                    $linha['description'],
                    exibeEvento('event1', $linha),
                    exibeEvento('event2', $linha),
                    exibeEvento('event3', $linha),
                    exibeEvento('event4', $linha),
                    exibeEvento('event5', $linha),
                    exibeEvento('event6', $linha),
                    exibeEvento('event7', $linha),
                    exibeEvento('event8', $linha),
                    exibeEvento('event9', $linha),
                    exibeEvento('event10', $linha)
                ));
            }

            if ($CSV_EXPORT) {
                $csvRows[] = array(
                    zbx_date2str($formato_data, $event['clock']),
                    $_REQUEST['hostid'] == 0 ? $host['name'] : null,
                    $description,
                    trigger_value2str($event['value']),
                    getSeverityCaption($trigger['priority']),
                    $event['duration'],
                    ($config['event_ack_enable']) ? ($event['acknowledges'] ? _('Yes') : _('No')) : null,
                    // ($config['event_ack_enable'])? $ack :NULL,
                    strip_tags((string) $actions)
                );
            }
        }
    }
} else {
    $dashboard->addItem($widget);
    $report = Array();

// Get data for report ---------------------------------------------------------
    if (hasRequest('filter_set')) {
        if ($reportMode) { // Custom event report for show only events related
            $trigOpt['monitored'] = true;
            $triggers = API::Trigger()->get($trigOpt);
            $options = array(
                'source' => EVENT_SOURCE_TRIGGERS,
                'output' => API_OUTPUT_EXTEND, //API_OUTPUT_SHORTEN,
                'sortfield' => 'eventid',
                'sortorder' => ZBX_SORT_DOWN,
                'limit' => (100)
            );
// query event with short data
            $report = $eventTitles = array();
            $count = 1;
            $tmp = $options;
            $tmp['objectids'] = $filter["p_triggerid"];
            $tmp['time_till'] = $filter["p_stime"] + ($range);
            $tmp['sortfield'] = 'eventid';
            $tmp['sortorder'] = "DESC";
            $events = API::Event()->get($tmp);
            $listaTriggers = " ";
            $possui = false;
        } else {
            $table->setHeader(array(
                _('Time'),
                _('Host'),
                _('Description'),
                _('Status'),
                _('Severity'),
                _('Duration'),
                ($config['event_ack_enable']) ? _('Ack') : null,
                _('Actions')
            ));

            if ($CSV_EXPORT) {
                $csvRows[] = array(
                    _('Time'),
                    _('Host'),
                    _('Description'),
                    _('Status'),
                    _('Severity'),
                    _('Duration'),
                    ($config['event_ack_enable']) ? _('Ack') : null,
                    _('Actions')
                );
            }
            // Filters using trigger data ------------------------------------------
            $triggerOptions = [
                'output' => ['triggerid', 'description', 'expression', 'priority', 'flags', 'url'],
                'selectHosts' => ['hostid', 'name', 'status'],
                'selectItems' => ['itemid', 'hostid', 'name', 'key_', 'value_type'],
                'preservekeys' => true,
                'monitored' => true
            ];
            if (isset($_REQUEST['triggerid']) && ($_REQUEST['triggerid'] > 0)) {
                $triggerOptions['triggerids'] = $_REQUEST['triggerid'];
            }
            if ($filter["groupids"] !== []) {
                $triggerOptions['groupids'] = $filter["groupids"];
            }
            if ($filter["hostids"] !== []) {
                $triggerOptions['hostids'] = $filter["hostids"];
            }
            if ($filter["item"] !== "") {
                $hostFilter = zeDBConditionInt('it.hostid', $filter["hostids"]);
                $query = "SELECT it.itemid, it.hostid FROM items it WHERE it.key_ LIKE " . quotestr($filter['item'] . "%")
                        . ($hostFilter == "" ? "" : " AND ") . $hostFilter
                        . "\n order by it.hostid, it.itemid";
                $result = DBselect($query);
                $itemids = [];
                while ($rowItem = DBfetch($result)) {
                    $itemids[count($itemids)] = $rowItem["itemid"];
                }
                $triggerOptions['itemids'] = $itemids;
            }
            //Todo: Filtro por trigger (com suporte a expressão regular)

            $triggers = API::Trigger()->get($triggerOptions);
            // End filters

            $events = API::Event()->get([
                'source' => EVENT_SOURCE_TRIGGERS,
                'object' => EVENT_OBJECT_TRIGGER,
                'objectids' => zbx_objectValues($triggers, 'triggerid'),
                'output' => API_OUTPUT_EXTEND,
                'select_acknowledges' => API_OUTPUT_COUNT,
                'sortfield' => ['clock', 'eventid'],
                'sortorder' => ZBX_SORT_DOWN,
                'nopermissions' => true
            ]);

            $csv_disabled = zbx_empty($events);

// Sumarizando dados
            $instants = [];
            $uniqueHosts = [];
            $uniqueItems = [];
            foreach ($events as $enum => $event) {
                $momento = (int) (floor($event['clock'] / (int) $filter["groupmode"]) * (int) $filter["groupmode"]);
                if ($momento !== $ultimoMomento) {
                    $event["desc_instant"] = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $momento);
                    $event["desc_instant"] = substr($event["desc_instant"], 0, strlen($event["desc_instant"]) -
                                    ($filter["groupmode"] == 3600 ? 5 : ($filter["groupmode"] == 1800 || $filter["groupmode"] == 600 ? 4 : 2 ) )
                            ) . "*";
                    $ultimoMomento = $momento;
                    $instants[$momento]["events"] = 0;
                    $instants[$momento]["hosts"] = 0;
                    $uniqueHosts = [];
                    $uniqueItems = [];
//                }
                    $cont = 0;
                }
                $instants[$momento]["events"] ++;
                $cont++;
                // Resolve item and host data
                //Todo: verificar se o array das triggers já tem a informação de hosts, se tiver não precisa buscar nem host nem itens
// Sumarizar quantidade de hosts
                $hostids = zbx_objectValues($triggers[$event['objectid']]["hosts"], 'hostid');
                $uniqueHosts = array_unique(array_merge($hostids, $uniqueHosts));

                $triggers[$event['objectid']]["hosts_names"] = hostName($hostids);
                $instants[$momento]["hosts"] = count($uniqueHosts);
// Sumarizar quantidade de itens
                //var_dump($triggers[$event['objectid']]['items']);
                $itemids = $triggers[$event['objectid']]['items'];
                $uniqueItems = array_unique(array_merge(zbx_objectValues($itemids, 'itemid'), $uniqueItems));

                $triggers[$event['objectid']]["items_names"] = CMacrosResolverHelper::resolveItemNames($itemids);
                $instants[$momento]["items"] = count($uniqueItems);
// Sumarizar quantidade de eventos anteriores
// Sumarizar quantidade de triggers
            }

            foreach ($events as $enum => $event) {
                $trigger = $triggers[$event['objectid']];

                foreach ($trigger['items'] as $item) {
                    $i = array();
                    $i['itemid'] = $item['itemid'];
                    $i['value_type'] = $item['value_type'];
                    $i['action'] = str_in_array($item['value_type'], array(
                                ITEM_VALUE_TYPE_FLOAT,
                                ITEM_VALUE_TYPE_UINT64
                            )) ? 'showgraph' : 'showvalues';
                    $i['name'] = $item['name']; //itemName($item);
                    $items[] = $i;
                }

// Actions ---------------------------------------------------------
                $actions = (new CButton("btnEM", _zeT('Correlate'), 'T'))->onClick("return PopUp('everyz.php?action=zbxe-em&mode=report&"
                        . "p_triggerid=" . $event['objectid']
                        . "&p_check_range=" . $filter["p_check_range"]
                        . "&fullscreen=1&form_refresh=0&p_stime=" . $event['clock']
                        . "');");
//get_event_actions_status($event['eventid']);

                $ack = getEventAckState($event, true);
                $description = CMacrosResolverHelper::resolveEventDescription(zbx_array_merge($trigger, array(
                            'clock' => $event['clock'],
                            'ns' => $event['ns']
                )));

                $tr_desc = new CSpan($description, 'pointer');
                $tr_desc->onClick("create_mon_trigger_menu(event, " .
                        " [{'triggerid': '" . $trigger['triggerid'] . "', 'lastchange': '" . $event['clock'] . "'}]," .
                        zbx_jsvalue($items, true) . ");");

// duration
                $event['duration'] = zbx_date2age($event['clock']);

                $statusSpan = new CSpan(trigger_value2str($event['value']));
// add colors and blinking to span depending on configuration and trigger parameters
                addTriggerValueStyle(
                        $statusSpan, $event['value'], $event['clock'], $event['acknowledged']
                );

// host JS menu link
                //var_dump($triggers);
                $hostSpan = new CSpan($triggers[$event['objectid']]["hosts_names"], 'link_menu menu-host');
                $scripts = API::Script()->getScriptsByHosts($triggers[$event['objectid']]["hosts"][0]);
                $momento = floor($event['clock'] / (int) $filter["groupmode"]) * (int) $filter["groupmode"];
                if ($momento !== $ultimoMomento) {
                    $descMomento = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $momento);
                    $descMomento = substr($descMomento, 0, strlen($descMomento) - ($filter["groupmode"] == 3600 ? 5 :
                                            ($filter["groupmode"] == 1800 || $filter["groupmode"] == 600 ? 4 : 2 ) )
                            ) . "*";
                    $table->addRow([
                                (new CDiv())
                                ->addClass(ZBX_STYLE_TREEVIEW)
                                ->addClass('app-list-toggle')
                                ->setAttribute('data-app-id', $momento)
                                ->setAttribute('data-open-state', 0)
                                ->addItem(new CSpan())
                        , $descMomento
                        , ''
                        , (new CCol("(" . _n('%1$s Event', '%1$s Events', $instants[$momento]["events"])
                        . ", " . _n('%1$s Host', '%1$s Hosts', $instants[$momento]["hosts"])
                        . ")"))->setColSpan(6)
                    ]);
                    $ultimoMomento = $momento;
                }

                $tableRow = new CRow(array('',
                    new CLink(zbx_date2str(DATE_TIME_FORMAT_SECONDS, $event['clock'])
                            , 'tr_events.php?triggerid=' . $event['objectid'] . '&eventid=' . $event['eventid'], 'action'),
                    $hostSpan,
                    new CSpan($tr_desc, 'link_menu'),
                    $statusSpan,
                    getSeverityCell($trigger['priority'], $config, null, !$event['value']),
                    $event['duration'],
                    ($config['event_ack_enable']) ? $ack : null,
                    $actions
                ));
                $tableRow->setAttribute('parent_app_id', $momento);
                $table->addRow($tableRow);

                if ($CSV_EXPORT) {
                    $csvRows[] = array(
                        zbx_date2str($formato_data, $event['clock']),
                        $_REQUEST['hostid'] == 0 ? $host['name'] : null,
                        $description,
                        trigger_value2str($event['value']),
                        getSeverityCaption($trigger['priority']),
                        $event['duration'],
                        ($config['event_ack_enable']) ? ($event['acknowledges'] ? _('Yes') : _('No')) : null,
                        // ($config['event_ack_enable'])? $ack :NULL,
                        strip_tags((string) $actions)
                    );
                }
            }
        }
    } else {
        $table->setNoDataMessage(_('Specify some filter condition to see the values.'));
    }

    // Build the report ------------------------------------------------------------
    switch ($filter["format"]) {
        case 1;
            $table->setHeader(array(_zeT("Data")));
            break;
        case 0;
            $table->setHeader(array($toggle_all,
                _('Time'),
                _('Host'),
                (new CColHeader(_('Description')))->addStyle('width: 35%'),
                (new CColHeader(_('Status')))->addStyle('width: 5%'),
                (new CColHeader(_('Severity')))->addStyle('width: 5%'),
                (new CColHeader(_('Duration')))->addStyle('width: 5%'),
                (new CColHeader(($config['event_ack_enable']) ? _('Ack') : null))->addStyle('width: 3%'),
                (new CColHeader(_('Actions')))->addStyle('width: 7%')
            ));
            break;
    }
    $lastHostID = -1;

    foreach ($report as $row) {
        $item = getItem($row['itemid']);
        $state_css = ($item['state'] == ITEM_STATE_NOTSUPPORTED) ? ZBX_STYLE_GREY : null;
        if ($filter["format"] == 0 && $row['hostid'] !== $lastHostID) {
            $resumo = zbxeFieldValue("select count(*) as total from items ite "
                    . "where ite.state = 1 and ite.status = 0 and ite.hostid = " . $row['hostid']
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
                , $row["host_name"]
                , "(" . _n('%1$s Item', '%1$s Items', $resumo) . ")"
            ]);
        }
        $lastHostID = $row["hostid"];
        switch ($filter["format"]) {
            case 1;
                $table->addRow(quotestr($row["host_name"])
                        . ";" . quotestr($item['name_expanded'])
                        . ";" . quotestr($item['key_'])
                        . ";" . quotestr($row['error'])
                )
                ;
                break;
            case 0;
                $tableRow = new CRow([
                    '', ''
                    , (new CCol($item["name_expanded"], 1))->addClass($state_css)
                    , (new CCol($item["key_"], 1))->addClass($state_css)
                    , (new CCol($row["error"], 1))->addClass($state_css)
                    , [new CLink(_('Disable'), 'items.php?group_itemid=' . $row['itemid'] . '&hostid=' . $row['hostid'] . '&action=item.massdisable')]
                ]);
                $tableRow->setAttribute('parent_app_id', $row['hostid']);
                $table->addRow($tableRow);
                break;
        }
    }
}

$form->addItem([$table]);

// Scripts for timeline
$firstEvent = API::Event()->get([
    'source' => EVENT_SOURCE_TRIGGERS,
    'object' => EVENT_OBJECT_TRIGGER,
    'output' => API_OUTPUT_EXTEND,
    //'objectids' => $triggerId ? $triggerId : null,
    'sortfield' => ['clock'],
    'sortorder' => ZBX_SORT_UP,
    'limit' => 1
        ]);
$firstEvent = reset($firstEvent);
$starttime = $firstEvent['clock'];
$timeline = [
    'period' => $filter["period"],
    'starttime' => date(TIMESTAMP_FORMAT, $starttime),
    'usertime' => date(TIMESTAMP_FORMAT, (int) $filter["till"])
];

$objData = [
    'id' => 'timeline_1',
    'loadSBox' => 0,
    'loadImage' => 0,
    'loadScroll' => 1,
    'dynamic' => 0,
    'mainObject' => 1,
    'periodFixed' => CProfile::get('web.events.timelinefixed', 1),
    'sliderMaximumTimePeriod' => ZBX_MAX_PERIOD
];

zbx_add_post_js('jqBlink.blink();');
zbx_add_post_js('timeControl.addObject("scroll_events_id", '
        . zbx_jsvalue($timeline) . ', ' . zbx_jsvalue($objData) . ');');
zbx_add_post_js('timeControl.processObjects();');

$dashboard->addItem($form)->show();
