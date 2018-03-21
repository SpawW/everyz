<?php

/*
 * * Purpose: Import hosts from CSV data
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
$moduleName = "zbxe-csvhost";
$baseProfile .= $moduleName;
$moduleTitle = 'Host import';
$requiredFields = ['host.host', 'interface.type', 'interface.ip', 'group.1', 'template.1'];
$requiredIndex = [];
$groupCount = $templateCount = 1;
$interfaceTypeIndex = ['AGENT' => 1, 'SNMP' => 2, 'IPMI' => 3, 'JMX' => 4];
$interfaceDefaultPort = ['AGENT' => 10050, 'SNMP' => 161, 'IPMI' => 623, 'JMX' => 12345];

$useip = false;
// Common fields
addFilterActions();

// Specific fields
addFilterParameter("format", T_ZBX_INT);
addFilterParameter("dataFields", T_ZBX_STR, implode(",", $requiredFields), true, false, false);
addFilterParameter("hostData", T_ZBX_STR, "Past your data here!", true, false, false);
// Field validation
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
if (CWebUser::getType() < USER_TYPE_SUPER_ADMIN) {
    error(_('You need to be a Zabbix Super Admin to access this module!'));
    access_deny(ACCESS_DENY_PAGE);
}

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function newInterface($type, $address, $port = null, $default = false, $useip = true) {
    global $interfaceTypeIndex, $interfaceDefaultPort;
//aqui
    if (!in_array($type, $interfaceTypeIndex)) {
        $possibleInterfacesType = "";
        foreach ($interfaceTypeIndex as $key => $value) {
            $possibleInterfacesType .= ($possibleInterfacesType === "" ? "" : ", ") . $key ;
        }
        error(_zeT('Wrong interface type. Possible values: ') . ' - ' . $possibleInterfacesType);
        return "";
    }
    if ($port == null) {
        $port = $interfaceDefaultPort[$type];
    }
    return ['type' => $interfaceTypeIndex[$type], 'port' => $port, 'useip' => ($useip ? 1 : 0), 'useip' => ($useip ? 1 : 0)
        , ($useip ? 'ip' : 'dns') => $address
        , 'main' => 1
        , 'locked' => '', 'dns' => '', 'items' => ''
    ];
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
if (hasRequest('hostData')) {
    $dataFields = str_getcsv($filter['dataFields'], ';');
    if (count($dataFields) < 5) {
        $dataFields = str_getcsv($filter['dataFields'], ',');
        if (count($dataFields) < count($requiredFields)) {
            error("Definição inválida de fields!");
            $invalido = true;
        }
    }
    if (!isset($invalido)) {
        foreach ($dataFields as $key => $value) {
            $tmp = array_search($value, $requiredFields);
            if ($tmp > -1) {
                $requiredIndex[$requiredFields[$tmp]] = $key;
            }
            switch ($value) {
                case 'interface.ip' :
                    $requiredIndex['address'] = $key;
                    $useip = true;
                    break;
                case 'interface.type' :
                    $requiredIndex['address'] = $key;
                    $useip = true;
                    break;
                case 'interface.port' :
                    $requiredIndex['port'] = $key;
                    break;
                default:
                    if (strpos($value, 'group.') > -1)
                        $groupIndex[] = $key;
                    if (strpos($value, 'template.') > -1)
                        $templateIndex[] = $key;
                    if (strpos($value, 'inventory.') > -1)
                        $inventoryIndex[] = [$key, str_replace('inventory.', '', $value)];
                    $extraFields[$value] = $key;
                    break;
            }
        }
        // Get all hostgroups
        $tmp = API::HostGroup()->get(['output' => ['groupid', 'name']]);
        foreach ($tmp as $value) {
            $groupArray[$value['name']] = $value['groupid'];
        }
        // Get all templates
        $tmp = API::Template()->get(['output' => ['templateid', 'host']]);
        foreach ($tmp as $value) {
            $templateArray[$value['host']] = $value['templateid'];
        }
        $data = explode("\n", $filter['hostData']);
        foreach ($data as $key => $value) {
            $linhaCSV = str_getcsv($value, ',');
            if (count($linhaCSV) == 1) {
                $linhaCSV = str_getcsv($value, ';');
            }
            if (count($linhaCSV) > 3) {
                // Buscando os itens de inventário
                $inventory = [];
                for ($i = 0; $i < count($inventoryIndex); $i++) {
                    $inventory[$inventoryIndex[$i][1]] = $linhaCSV[$inventoryIndex[$i][0]];
                }
                // Buscando todos os grupos
                $groups = [];
                for ($i = 0; $i < count($groupIndex); $i++) {
                    if (!isset($groupArray[$linhaCSV[$groupIndex[$i]]])) {
                        show_message(_('Group added') . ' - ' . $linhaCSV[$groupIndex[$i]]);
                        $tmp = API::HostGroup()->create(['name' => $linhaCSV[$groupIndex[$i]]]);
                        $groupArray[$linhaCSV[$groupIndex[$i]]] = $tmp;
                    }
                    $groups[] = $groupArray[$linhaCSV[$groupIndex[$i]]];
//                    $groups[] = ['groupid' => $groupArray[$linhaCSV[$groupIndex[$i]]]];
                }
                // Buscando todos os grupos
                $templates = [];
                for ($i = 0; $i < count($templateIndex); $i++) {
                    if (!isset($templateArray[$linhaCSV[$templateIndex[$i]]])) {
                        error(_('Template missing') . ' - ' . $linhaCSV[$templateIndex[$i]]);
                    } else {
                        $templates[] = ['templateid' => $templateArray[$linhaCSV[$templateIndex[$i]]]];
                    }
                }
                if (count($linhaCSV) == count($dataFields)) {
                    $host = [
                        'name' => '',
                        'status' => HOST_STATUS_MONITORED,
                        'description' => '',
                        'proxy_hostid' => 0,
                        'ipmi_authtype' => -1,
                        'ipmi_privilege' => 0,
                        'ipmi_username' => '',
                        'ipmi_password' => '',
                        'tls_connect' => HOST_ENCRYPTION_NONE,
                        'tls_accept' => HOST_ENCRYPTION_NONE,
                        'tls_issuer' => '',
                        'tls_subject' => '',
                        'tls_psk_identity' => '',
                        'tls_psk' => '',
                        'groups' => zbx_toObject($groups, 'groupid'),
                        'templates' => $templates,
                        'interfaces' =>
                        [ 1 => newInterface($linhaCSV[$requiredIndex['interface.type']], $linhaCSV[$requiredIndex['address']]
                                    , (isset($requiredIndex['interface.port']) ? $linhaCSV[$requiredIndex['interface.port']] : null), true, $useip)],
                        'macros' => [],
                        'inventory_mode' => 1,
                        'inventory' => $inventory
                    ];
                    //  print_r($host); exit;
                    foreach ($extraFields as $key => $value) {
                        if (strpos($key, 'host.') > -1 && !isset($requiredFields[$key])) {
                            $host[str_replace('host.', '', $key)] = $linhaCSV[$value];
                        }
                    }
                    $hostIds = API::Host()->create($host);
                    if ($hostIds) {
                        $messages[] = _('Host added') . ' - ' . $host['host'];
                    } else {
                        show_error_message(_('Cannot add host') . ' - ' . $host['host']);
                        $error = true;
                    }
                } else {
                    error("Dados inválidos na linha " . $key . " (" . $host["host"] . "). Esperado: " . count($dataFields)
                            . " campos. Encontrado: " . count($linhaCSV) . " campos.");
                }
            }
        }
        if ($messages) {
            show_message('Total de hosts cadastrados: ' . count($messages));
        }
    } // Fim dados validos
}

$groups = API::HostGroup()->get([ 'output' => 'extend', 'sortfield' => 'name']);

/* * ***************************************************************************
 * Display
 * ************************************************************************** */
commonModuleHeader($moduleName, $moduleTitle, true);

$tmpColumn = new CFormList();
$tmpColumn->addRow(_zeT('Data fields'), (new CTextArea('dataFields', $filter['dataFields']))->setWidth(ZBX_TEXTAREA_BIG_WIDTH));
$tmpColumn->addRow(_zeT('Host Data'), (new CTextArea('hostData', $filter['hostData']))->setWidth(ZBX_TEXTAREA_BIG_WIDTH));
$tmpColumn->addRow(new CSubmit('form', _zeT('Create hosts')));

$table->addRow([$tmpColumn]);


/* * ***************************************************************************
 * Display Footer 
 * ************************************************************************** */
$form->addItem([ $table]);
$dashboard->addItem($form)->show();
