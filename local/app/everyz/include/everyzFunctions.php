<?php

/*
 * * Purpose: Miscelaneous functions
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

// Define and global variables
define("ZE_VER", "3.0");
define("EZ_TITLE", 'EveryZ - ');
define("ZE_COPY", ", ZE " . ZE_VER);
define("EVERYZBUILD", 8);

global $VG_DEBUG;
global $zeMessages, $zeLocale, $baseName, $requiredMissing, $zbxeLoadedJS;

$requiredMissing = false;
$zbxeLoadedJS = [];
$VG_DEBUG = (isset($_REQUEST['p_debug']) && $_REQUEST['p_debug'] == 'S' ? true : false );

// End of define and global variables
// Functions required ==========================================================
# Zabbix-Extras - Global Variables Start 
function zbxeFieldValue($p_query, $p_field) {
    $res = "";
    $result = prepareQuery($p_query);
    while ($row = DBfetch($result)) {
        $res = $row[$p_field];
    }
    return $res;
}

function descItem($itemName, $itemKey) {
    if (strpos($itemName, "$") !== false) {
        $tmp = explode("[", $itemKey);
        $tmp = explode(",", str_replace("]", "", $tmp[1]));
        for ($i = 0; $i < count($tmp); $i++) {
            $itemName = str_replace("$" . ($i + 1), $tmp[$i], $itemName);
        }
    }
    return $itemName;
}

/**
 * _zeT
 *
 * Translate strings using zbxe_translation. Need to be used on all modules.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $msg        text to translate
 * @param string  $moduleid   module identifier
 */
function _zeT($msg, $moduleid = "", $autoinsert = true) {
    global $VG_BANCO_OK;
    if (!$VG_BANCO_OK)
        return $msg;
    if (trim($msg) == "")
        return $msg;
    if ($moduleid == "") {
        global $moduleName;
        $moduleid = $moduleName;
    }
    $lang = quotestr(CWebUser::$data['lang']);
    $p_msg2 = quotestr($msg);
    $return = zbxeFieldValue('SELECT tx_new FROM zbxe_translation WHERE tx_original = '
            . $p_msg2 . ' AND lang = ' . $lang, 'tx_new');
    if ($return == "") {
        if ($autoinsert) {
            $sql = "INSERT INTO zbxe_translation (lang, tx_original, tx_new, module_id) VALUES (" . $lang . "," . $p_msg2 . "," . $p_msg2 . ", " . quotestr($moduleid) . ")";
            prepareQuery($sql);
        }
        $return = $msg;
    }
    return $return;
}

/**
 * zbxeConfigValue
 *
 * Get configuration value from zbxe_preferences.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $param   param to get current value
 * @param integer $id      get the value for specific user (default 0 = all users)
 * @param string  $default default value (used if dont exists configuration paramiter with $param name)
 */
function zbxeConfigValue($param, $id = 0, $default = "") {
    $query = 'select tx_value from zbxe_preferences WHERE userid = '
            . $id . " and tx_option = " . quotestr($param);
    $retorno = zbxeFieldValue($query, 'tx_value');
    return (strlen($retorno) < 1 ? $default : $retorno);
}

/**
 * zbxeUpdateConfigValue
 *
 * Update configuration values on zbxe_preferences.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string   $param   param to update value
 * @param string   $value   new value
 * @param integer  $id      userid (default 0 = all users)
 */
function zbxeUpdateConfigValue($param, $value, $id = 0) {
    $currentValue = zbxeConfigValue($param, $id);
    if ($currentValue == "") {
        if (zbxeFieldValue("select count(*) as total from zbxe_preferences WHERE tx_option = " . quotestr($param), "total") == 0) {
            $query = zbxeInsert("zbxe_preferences", ['userid', 'tx_option', 'tx_value', 'st_ativo'], [$id, $param, $value, '1']);
        }
    }
    if ((!isset($query)) && ($currentValue != $value)) {
        $query = zbxeUpdate("zbxe_preferences", ['userid', 'tx_option', 'tx_value', 'st_ativo'], [$id, $param, $value, '1']
                , ['tx_option'], [$param]);
        //echo "update [$currentValue] <br> [$query]";
    }
    //if ($param == "map_title_show") {
    if (isset($query)) {
        //echo "<br>ois [" . strlen($query) . "]";
        return prepareQuery($query);
    }
    //}
    //echo "[<br> $param - [$currentValue/$value]";
}

/**
 * zbxeInsert
 *
 * Return SQL query to insert records in a table.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string   $table       table name
 * @param array    $fields      fields to insert values
 * @param array    $values      new values
 */
function zbxeInsert($table, $fields, $values) {
    global $conn;
    $field_names = $field_values = "";
    for ($i = 0; $i < count($fields); $i++) {
        $field_names .= ($field_names == "" ? "" : ", " ) . ZE_DBFQ . $fields[$i] . ZE_DBFQ;
        /* when I found the support for paramiters query on Zabbix..
          $field_values .= ($field_values == "" ? "" : ", " ) . "?";
         */
        $field_values .= ($field_values == "" ? "" : ", " ) . quotestr($values[$i]);
    }
    $filter = "";
    $query = " INSERT INTO " . $table . " (" . $field_names . ") VALUES (" . $field_values . ") " . $filter;
    return zbxeStandardDML($query);
}

/**
 * zbxeUpdate 
 *
 * Return SQL query to update records in a table.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string   $table       table name
 * @param array    $fields      fields to update
 * @param array    $values      new values
 * @param array    $filternames fields filter definition to update SQL
 * @param array    $filternames values of fields to search
 */
function zbxeUpdate($table, $fields, $values, $filterNames, $filterValues) {
    global $conn;
    $updateFields = "";
    for ($i = 0; $i < count($fields); $i++) {
        $updateFields .= ($updateFields == "" ? "" : ", " ) . ZE_DBFQ . $fields[$i] . ZE_DBFQ . " = " . quotestr($values[$i]);
    }
    $filter = "";

    for ($i = 0; $i < count($filterNames); $i++) {
        /* when I found the support for paramiters query on Zabbix..
          $filter .= ($filter == "" ? "" : ", " ) . "" . $filterNames[$i] . " = ? ";
          $values[count($values)] = $filterValues[$i];
         */
        $filter .= ($filter == "" ? "" : " AND " ) . "" . $filterNames[$i] . " = " . quotestr($filterValues[$i]);
    }
    $query = " UPDATE " . $table . " SET " . $updateFields . ($filter != "" ? " WHERE " . $filter : "");
    return zbxeStandardDML($query);
}

/**
 * newComboFilter
 *
 * Return a combo with options from SQL values
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $query   SQL statement
 * @param string    $name    name of HTML element
 * @param string    $value   current value
 * @param boolean   $reload  true - submit form when value are changed
 */
function newComboFilter($query, $name, $value, $reload = true) {
    $cmbRange = new CComboBox($name, $value, ($reload ? 'javascript: submit();' : ''));
    $result = DBselect($query);
    $cmbRange->additem("0", "");
    while ($row_extra = DBfetch($result)) {
        $cmbRange->additem($row_extra['id'], $row_extra['description']);
    }
    return $cmbRange;
}

/**
 * newComboFilterArray
 *
 * Return a combo with options from a array of values
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $array   array of options (key and value)
 * @param string    $name    name of HTML element
 * @param string    $value   current value
 * @param boolean   $reload  true - submit form when value are changed
 */
function newComboFilterArray($array, $name, $value, $reload = true) {
    $cmbRange = new CComboBox($name, $value, ($reload ? 'javascript: submit();' : ''));
    $cmbRange->additem('', 'Selecione...');
    foreach ($array as $k => $v) {
        $cmbRange->additem($k, $v);
    }
    return $cmbRange;
}

/**
 * prepareQuery
 *
 * Execute a SQL code using native Zabbix Frontend Functions
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_query    SQL code to execute
 */
function prepareQuery($p_query) {
    $result = DBselect($p_query);
    if (!$result) {
        global $DB;
        die("Invalid query [$p_query]." . ( $DB['TYPE'] == ZBX_DB_POSTGRESQL ? "" : mysql_error()));
        return 0;
    } else {
        return $result;
    }
}

/**
 * getBetweenStrings
 *
 * Get text between two strings
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $start  Start identifier
 * @param string    $end    End identifier
 * @param string    $str    Full text
 */
function getBetweenStrings($start, $end, $str) {
    $matches = array();
    $regex = "/$start([a-zA-Z0-9_]*)$end/";
    preg_match_all($regex, $str, $matches);
    return $matches[1];
}

/**
 * debugInfo
 *
 * Generic function to show debug messages
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_text   Debug text message
 * @param boolean   $p_debug  True - Show message, False - Dont show
 * @param string    $p_color  Background color of message
 */
function debugInfo($p_text, $p_debug = false, $p_color = "") {
    global $VG_DEBUG;
    if ($p_debug == true || $VG_DEBUG == true) {
        echo (php_sapi_name() == 'cli' ? "\nDEBUG: " : '<div style="background-color:' . $p_color . ';"><pre>') . print_r($p_text, true) . (php_sapi_name() == 'cli' ? "\n" : "</pre></div>");
    }
}

function array_sort($array, $on, $order = SORT_ASC) {
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

/**
 * quotestr
 *
 * Generic function to quote strings
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_text   Text to quote
 */
function quotestr($p_text, $quote = true) {
    global $DB;
    zbxeStartDefinitions();
    if ($quote) {
        return ZE_QUOTECHAR . ($DB['TYPE'] == ZBX_DB_POSTGRESQL ?
                        pg_escape_string($p_text) :
                        addslashes($p_text)
                ) . ZE_QUOTECHAR;
    } else {
        return ($DB['TYPE'] == ZBX_DB_POSTGRESQL ?
                        pg_escape_string($p_text) :
                        addslashes($p_text)
                );
    }
}

/**
 * ezZabbixVersion
 *
 * Return Current Zabbix Version.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function ezZabbixVersion() {
    return str_replace(".", "", substr(ZABBIX_VERSION, 0, 5));
}

/**
 * checkAccessGroup
 *
 * Check if current user can access (read) hostgroup data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_groupid   ID of hostgroup
 */
function checkAccessGroup($p_groupid) {
    global $filter;
    $groupids = (isset($_REQUEST[$p_groupid]) ? $_REQUEST[$p_groupid] : $filter[$p_groupid]);
    if (getRequest($p_groupid) && !API::HostGroup()->isReadable($groupids)) {
        access_deny();
    }
    return $groupids;
}

/**
 * checkAccessTrigger
 *
 * Check if current user can access (read) trigger data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_triggerid   ID of trigger
 */
function checkAccessTrigger($p_triggerid) {
    global $filter;
    $triggerids = array(isset($_REQUEST[$p_triggerid]) ? $_REQUEST[$p_triggerid] : $filter[$p_triggerid]);
    if (getRequest($p_triggerid) && !API::Trigger()->isReadable($triggerids)) {
        access_deny();
    }
    return $triggerids;
}

/**
 * checkAccessHost
 *
 * Check if current user can access (read) host data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_hostid   ID of host
 */
function checkAccessHost($p_hostid) {
    global $filter;
    $hostids = (isset($_REQUEST[$p_hostid]) ? $_REQUEST[$p_hostid] : $filter[$p_hostid]);
    if (!is_array($hostids)) {
        $hostids = [$hostids];
    }
    if (getRequest($p_hostid) && !API::Host()->isReadable($hostids)) {
        access_deny();
    } else {
        if (count($hostids) > 0 && $hostids[0] == 0) {
            $hostids = array();
        }
    }
    return $hostids;
}

/**
 * getRequest2
 *
 * Get a parameter value with support to default value. Function created because Zabbix INC change this funcion name many times last years.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_name     Name of parameter
 * @param boolean   $p_message  Default value
 */
function getRequest2($p_name, $p_default = "") {
    if (isset($_REQUEST[$p_name])) {
        return $_REQUEST[$p_name];
    } else {
        return $p_default;
    }
}

/**
 * checkRequiredField
 *
 * Check if a mandatory field is empty.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_name     Name of parameter
 * @param boolean   $p_message  Custom error message
 */
function checkRequiredField($p_name, $p_message = "") {
    global $requiredMissing;
    $value = getRequest2($p_name);
    $requiredMissing = ($requiredMissing == true ? true : false);
    //var_dump($value);
    if (is_array($value) && $value == array(0)) {
        $requiredMissing = true;
        error(_zeT($p_message));
    } else if ($value == "") {
        $requiredMissing = true;
        error(_zeT($p_message));
    }
}

/**
 * addFilterActions
 *
 * Add the standard fields on $fields variable (common variable used for filter on standard zabbix pages)
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function addFilterActions() {
    global $filter, $fields, $moduleName;
    $fields["action"] = array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, NULL, null);
    $filter["action"] = $moduleName;
    $fields["filter_rst"] = array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, NULL, null);
    $filter["filter_rst"] = getRequest2("filter_rst", "");
    $fields["filter_set"] = array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, NULL, null);
    $filter["filter_set"] = getRequest2("filter_set", "");
    $fields['fullscreen'] = array(T_ZBX_INT, O_OPT, P_SYS, IN('0,1'), null);
    $filter["fullscreen"] = getRequest2("fullscreen", "0");
    $fields['hidetitle'] = array(T_ZBX_INT, O_OPT, P_SYS, IN('0,1'), null);
    $filter["hidetitle"] = getRequest2("hidetitle", "0");
}

/**
 * fullScreenIcon
 *
 * Return a object for control fullscreen mode
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function fullScreenIcon() {
    global $filter;
    return (new CList())->addItem(get_icon('fullscreen', ['fullscreen' => $filter['fullscreen']]));
}

/**
 * addFilterParameter
 *
 * Add filter fields with support to default values and profiles.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $p_name         Paramiter name
 * @param string  $p_type         Type of data
 * @param string  $p_default      Default value
 * @param boolean $p_array        The data will be stored as array values on Zabbix Database
 * @param boolean $p_unset_empty  Clear data on profile when empty
 * @param boolean $p_use_profile  Use Zabbix Profile system
 */
function addFilterParameter($p_name, $p_type, $p_default = "", $p_array = false
, $p_unset_empty = false, $p_use_profile = true) {
    global $baseProfile, $fields, $filter;
    $typeProfile = ($p_type == T_ZBX_INT ? PROFILE_TYPE_INT : PROFILE_TYPE_STR);
    // Algum problema com o tipo negativo... validar
    $fields[$p_name] = array($p_type, O_OPT, ($p_unset_empty ? P_UNSET_EMPTY : P_SYS), null, null);
//    $fields[$p_name] = array($p_type, O_OPT, ($p_unset_empty ? P_UNSET_EMPTY : P_SYS), DB_ID, null);
    if ($p_array) {
        $p_default = (is_array($p_default) ? $p_default : array($p_default));
        $filter[$p_name] = getRequest2($p_name, CProfile::getArray($baseProfile . "." . $p_name, $p_default));
        if ($p_use_profile) {
            CProfile::updateArray($baseProfile . "." . $p_name, $filter[$p_name], $typeProfile);
        }
    } else {
        $filter[$p_name] = getRequest2($p_name, CProfile::get($baseProfile . "." . $p_name, $p_default));
        if ($p_use_profile) {
            CProfile::update($baseProfile . "." . $p_name, $filter[$p_name], $typeProfile);
        }
    }
}

/**
 * resetProfile
 *
 * Clear profile data on Zabbix Database.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_name   Last part of profile name on Zabbix Database ($baseProfile$p_name)
 * @param boolean   $p_array  True - Filter variable will receive "blank value" | False - Filter variable will receive "null" value.
 */
function resetProfile($p_name, $p_array = false, $resetVariable = true) {
    global $baseProfile, $filter;
    CProfile::delete($baseProfile . "." . $p_name);
    if ($resetVariable) {
        if ($p_array) {
            $filter[$p_name] = null;
        } else {
            $filter[$p_name] = "";
        }
    }
}

/**
 * baseURL
 *
 * Return current URL of frontend. Used for create dynamic links (for example)
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function baseURL() {
    global $s, $_SERVER;
    return "http" . ((!empty($s['HTTPS']) && $s['HTTPS'] == 'on' ) ? "s" : "") . "://{$_SERVER['HTTP_HOST']}";
}

/**
 * getItem
 *
 * Return a standard object with item data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $itemid   Primary Key (unique identifier) of item on Zabbix Database
 */
function getItem($itemid) {
    $item = API::Item()->get([
        'itemids' => $itemid,
        'output' => ['itemid', 'name', 'status', 'key_', 'units', 'valuemapid', 'value_type', 'state']
    ]);
    $item = CMacrosResolverHelper::resolveItemKeys($item);
    $item = CMacrosResolverHelper::resolveItemNames($item);
    foreach ($item as $row) {
        return $row;
    }
}

/**
 * multiSelectHosts
 *
 * Return a standard object for select hosts.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $multiSelectHostData   Array with hosts from Zabbix
 */
function multiSelectHosts($multiSelectHostData) {
    return (new CMultiSelect([
        'name' => 'hostids[]',
        'objectName' => 'hosts',
        'data' => $multiSelectHostData,
        'popup' => [
            'parameters' => 'srctbl=hosts&dstfrm=zbx_filter&dstfld1=hostids_&srcfld1=hostid' .
            '&real_hosts=1&multiselect=1'
        ]]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH);
}

/**
 * multiSelectHostGroups
 *
 * Return a standard object for select host groups.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $multiSelectHostGroupData   Array with hostgroups from Zabbix
 */
function multiSelectHostGroups($multiSelectHostGroupData) {
    return (new CMultiSelect(
            [
        'name' => 'groupids[]',
        'objectName' => 'hostGroup',
        'data' => $multiSelectHostGroupData,
        'popup' => [
            'parameters' => 'srctbl=host_groups&dstfrm=zbx_filter&dstfld1=groupids_' .
            '&srcfld1=groupid&multiselect=1'
        ]
            ]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
    ;
}

/**
 * selectedHostGroups
 *
 * Return data from grouphosts.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $groupids   Array with groupid to get grouphost data
 */
function selectedHostGroups($groupids) {
    $multiSelectHostGroupData = NULL;
    if ($groupids !== [] && $groupids !== NULL && $groupids[0] !== NULL) {
        $filterGroups = API::HostGroup()->get([
            'output' => ['groupid', 'name'],
            'groupids' => $groupids
        ]);

        foreach ($filterGroups as $group) {
            $multiSelectHostGroupData[] = [
                'id' => $group['groupid'],
                'name' => $group['name']
            ];
        }
    }
//    var_dump($multiSelectHostGroupData);
    return $multiSelectHostGroupData;
}

/**
 * selectHostsByGroup
 *
 * Return Hosts from a list of groupids with inventory data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $groupids   Array with groupid to get hosts
 * @param integer   $inventoryFields   Array with name of inventory fields to return with host data
 */
function selectHostsByGroup($groupids, $inventoryFields = NULL) {
    $multiSelectHostData = [];
    if ($groupids !== [] && $groupids !== NULL && $groupids[0] !== NULL) {
        // Get hosts only with inventory enabled
        $filterHosts = API::Host()->get([
            'output' => ['hostid', 'name'],
            'selectInventory' => $inventoryFields,
            'withInventory' => true,
            'groupids' => $groupids
        ]);
        foreach ($filterHosts as $host) {
            $tmp = [
                'id' => $host['hostid'],
                'name' => $host['name']
            ];
            foreach ($inventoryFields as $Inv) {
                $tmp[$Inv] = $host['inventory'][$Inv];
            }
            $multiSelectHostData[] = $tmp;
        }
    }
    return $multiSelectHostData;
}

/**
 * selectEventsByGroup
 *
 * Return active events from a list of groupids.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $groupids   Array with groupid to search triggers and events
 * @param integer   $status     Status of trigger (1 - Problem, 0 - OK)
 * @param integer   $severity   Minimum severity of trigger
 */
function selectEventsByGroup($groupids, $status = 1, $severity = 0) {
    $events = [];
    if ($groupids !== [] && $groupids !== NULL && $groupids[0] !== NULL) {
        // Find active triggers from selected host groups
        $events = API::Trigger()->get([
            'output' => ['triggerid', 'description', 'priority'
            //, 'expression', 'flags', 'url'
            ],
            'selectHosts' => ['hostid'],
            'active' => true,
            'only_true' => true,
            'expandComment' => true,
            'expandDescription' => true,
            'groupids' => $groupids,
            'preservekeys' => true,
            'selectLastEvent' => true,
            'filter' => ["value" => $status],
            'min_severity' => $severity
        ]);
    }
    return $events;
}

/* Return Hosts form a list of hostids with inventory data */

function selectedHosts($hostids, $inventoryFields = NULL) {
    $multiSelectHostData = [];
    if ($hostids !== [] && $hostids !== NULL && $hostids[0] !== NULL) {
        $filterHosts = API::Host()->get([
            'output' => ['hostid', 'name'],
            'selectInventory' => [$inventoryFields],
            'hostids' => $hostids
        ]);

        foreach ($filterHosts as $host) {
            $multiSelectHostData[] = [
                'id' => $host['hostid'],
                'name' => $host['name']
            ];
        }
    }
    return $multiSelectHostData;
}

function hostMacroValue($hostid, $macroName, $default = 0) {
    // Recupera o valor das macros no host
    $array_host = API::Host()->get([
        'output' => ['name', 'hostid'],
        'hostids' => $hostid,
        'selectMacros' => ['hostmacroid', 'macro', 'value']
    ]);
    if (count($array_host) > 0) {
        foreach ($array_host[0]["macros"] as $row) {
            if ($row["macro"] == $macroName) {
                $macroValue = $row["value"];
                break;
            }
        }
    }

    if (!isset($macroValue) || $macroValue == NULL) {
        $macroValue = globalMacroValue($macroName);
    }
    // Recupera o valor global da macro caso ela não exista no host
    return $macroValue;
}

function globalMacroValue($macroName, $default = 0) {
    global $globalMacros;
    if (!isset($globalMacros)) {
        $globalMacros = API::UserMacro()->get([
            'output' => ['globalmacroid', 'macro', 'value'],
            'globalmacro' => true,
            'preservekeys' => true
        ]);
    }
    foreach ($globalMacros as $row) {
        if ($row["macro"] == $macroName) {
            $macroValue = $row["value"];
            break;
        }
    }
    return ( isset($macroValue) ? $macroValue : $default);
}

function hostName($hostid, $array_host = []) {
    if ($array_host == []) {
        $array_host = API::Host()->get([
            'output' => ['name', 'hostid'],
            'hostids' => $hostid
        ]);
    }
    if (!is_array($hostid)) {
        $hostid = [$hostid];
    }
    $retorno = "";
    foreach ($array_host as $rowData) {
        foreach ($hostid as $rowID) {
            if ($rowData['hostid'] == $rowID) {
                $retorno = ($retorno == "" ? "" : ", ") . $rowData['name'];
            }
        }
    }
    return $retorno;
}

function templateName($templateid, $array_template = []) {
    if ($array_template == []) {
        $array_template = API::Template()->get([
            'output' => ['name', 'hostid'],
            'templateids' => $templateid
        ]);
    }
    if (!is_array($templateid)) {
        $templateid = [$templateid];
    }
    $retorno = "";
    foreach ($array_template as $rowData) {
        foreach ($templateid as $rowID) {
            if ($rowData['templateid'] == $rowID) {
                $retorno = ($retorno == "" ? "" : ", ") . $rowData['name'];
            }
        }
    }
    return $retorno;
}

function zbxeDBConditionInt($p_field, $p_array) {
    if ($p_array !== [] && $p_array !== NULL && $p_array[0] !== NULL) {
        return dbConditionInt($p_field, $p_array);
    } else {
        return "";
    }
}

function zeDBConditionInt($fieldName, array $values, $notIn = false, $sort = true) {
    return (count($values) > 0 ? dbConditionInt($fieldName, $values) : "");
}

function buttonOptions($name, $value, $options, $values = []) {
    $radioOptions = (new CRadioButtonList($name, (int) $value));
    for ($i = 0; $i < count($options); $i++) {
        $radioOptions->addValue($options[$i], (count($values) > 0 ? $values[$i] : $i));
    }
    $radioOptions->setModern(true);
    return $radioOptions;
}

function buttonOutputFormat($name, $value) {
    return (new CRadioButtonList($name, (int) $value))->addValue('HTML', PAGE_TYPE_HTML)->addValue('CSV', PAGE_TYPE_CSV)->addValue('JSON', PAGE_TYPE_JSON)->setModern(true);
}

// Adiciona link para javascript externo
function zeAddJsFile($scripts) {
    if (!is_array($scripts)) {
        $scripts = array($scripts);
    }
    foreach ($scripts as $script) {
        echo "\n" . '<script src="' . $script . '"></script>';
    }
}

function zeCancelButton($url) {
    return (new CRedirectButton(_('Cancel'), $url, null))->setId('cancel');
}

function zbxeMapTitleColor() {
    return zbxeFieldValue("select tx_value from zbxe_preferences WHERE tx_option='map_title_color'", "tx_value");
}

function zbxeMapShowTitle() {
    return zbxeFieldValue("select tx_value from zbxe_preferences WHERE tx_option='map_title_show'", "tx_value");
}

function zbxeCompanyName() {
    return zbxeFieldValue("select tx_value from zbxe_preferences WHERE tx_option='company_name'", "tx_value") . " ";
}

function zbxeCompanyNameSize() {
    $empresa = zbxeCompanyName();
    $tamanho = (120 + (strlen($empresa) * 4));
    return $tamanho;
}

function zbxeImageId($name) {
    $query = "SELECT imageid FROM images WHERE name = '" . $name . "'";
    return zbxeFieldValue($query, 'imageid');
}

function zbxeImageName($id) {
    $query = "SELECT name FROM images WHERE imageid = '" . $id . "'";
    return zbxeFieldValue($query, 'name');
}

function zbxeJSONKey($name, $value) {
    $value = '"' . ltrim(rtrim($value, "'"), "'") . '"';
    return "\"" . $name . "\": " . $value;
}

function zbxeComboIconMap($p_name = 'iconmapid', $p_default = 0) {
    // icon maps
    $data = [];
    $data['iconMaps'] = API::IconMap()->get([
        'output' => ['iconmapid', 'name'],
        'preservekeys' => true
    ]);
    order_result($data['iconMaps'], 'name');
    // Append iconmapping to form list.
    $cmbIconMap = (new CComboBox($p_name, $p_default))->addItem(0, _('<manual>'));
    foreach ($data['iconMaps'] as $iconMap) {
        $cmbIconMap->addItem($iconMap['iconmapid'], $iconMap['name']);
    }
    return $cmbIconMap;
}

function zbxeInventoryField($inventoryId) {
    $inventoryFields = ["", "type", "type_full", "name", "alias", "os", "os_full"
        , "os_short", "serialno_a", "serialno_b", "tag", "asset_tag"
        , "macaddress_a", "macaddress_b", "hardware", "hardware_full"
        , "software", "software_full", "software_app_a"
        , "software_app_b", "software_app_c", "software_app_d"
        , "software_app_e", "contact", "location", "location_lat"
        , "location_lon", "notes", "chassis", "model", "hw_arch"
        , "vendor", "contract_number", "installer_name", "deployment_status"
        , "url_a", "url_b", "url_c", "host_networks", "host_netmask"
        , "host_router", "oob_ip", "oob_netmask", "oob_router"
        , "date_hw_purchase", "date_hw_install", "date_hw_expiry"
        , "date_hw_decomm", "site_address_a", "site_address_b"
        , "site_address_c", "site_city", "site_state"
        , "site_country", "site_zip", "site_rack"
        , "site_notes", "poc_1_name", "poc_1_email"
        , "poc_1_phone_a", "poc_1_phone_b", "poc_1_cell"
        , "poc_1_screen", "poc_1_notes", "poc_2_name"
        , "poc_2_email", "poc_2_phone_a", "poc_2_phone_b"
        , "poc_2_cell", "poc_2_screen", "poc_2_notes"];
    return $inventoryFields[$inventoryId];
}

/**
 * getRealPOST
 *
 * Get All parameters without Zabbix interference
 * @author http://php.net/manual/en/language.variables.external.php#94607
 * 
 */
function getRealPOST() {
    $pairs = explode("&", file_get_contents("php://input"));
    $vars = array();
    foreach ($pairs as $pair) {
        $nv = explode("=", $pair);
        $name = urldecode($nv[0]);
        $value = urldecode($nv[1]);
        $vars[$name] = $value;
    }
    return $vars;
}

/**
 * commonModuleHeader
 *
 * Add a standard module header
 * Uses 2 global variables: $dashboard, $form
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $module_id        module identifier
 * @param string  $title            Title of module
 * @param string  $allowFullScreen  Add a button to full screen view
 */
function commonModuleHeader($module_id, $title, $allowFullScreen = false, $method = 'POST', $customControls = null) {
    global $dashboard, $form, $table, $toggle_all;
    if (getRequest2('hidetitle') == 1) {
        $dashboard = (new CWidget());
        $table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);
    } else {
        $dashboard = (new CWidget())->setTitle(EZ_TITLE . _zeT($title));
        $table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);
        if ($allowFullScreen) {
            $dashboard->setControls(($customControls == null ? (new CList())->addItem(get_icon('fullscreen', ['fullscreen' => getRequest('fullscreen')])) : $customControls));
            $toggle_all = (new CColHeader(
                    (new CDiv())
                            ->addClass(ZBX_STYLE_TREEVIEW)
                            ->addClass('app-list-toggle-all')
                            ->addItem(new CSpan())
                    ))->addStyle('width: 18px');
        }
    }
    $form = (new CForm($method, 'everyz.php'))->setName($module_id);
    $form->addItem(new CInput('hidden', 'action', $module_id));
}

/**
 * zbxeSQLList
 *
 * Return a array with all records from a SQL code
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $query  SQL code to search dat
 */
function zbxeSQLList($query) {
    $result = prepareQuery($query);
    $tmp = [];
    while ($row = DBfetch($result)) {
        foreach ($row as $key => $value) {
            $tmp[$key] = $value;
        }
        $report[] = $tmp;
    }
    return (isset($report) ? $report : []);
}

/**
 * zbxeNeedFilter
 *
 * Universal error message. For page_type = HTML show error message using $table object, 
 * for another case show a text message only
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $message  error message
 */
function zbxeNeedFilter($message) {
    global $page, $table;
    if ($page['type'] == detect_page_type(PAGE_TYPE_HTML)) {
        if (!isset($table)) {
            $table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);
        }
        $table->setNoDataMessage($message);
    } else {
        echo $message;
    }
}

/**
 * newFilterWidget
 *
 * Standard filter form using zabbix native profile
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $name  name of object in HTML
 */
function newFilterWidget($name) {
    return (new CFilter('web.' . $name . '.filter.state'))->addVar('fullscreen', getRequest('fullscreen'));
}

/**
 * zbxeToCSV
 *
 * Standard CSV line of values
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $array  Array of values
 */
function zbxeToCSV($array) {
    $return = "";
    foreach ($array as $value) {
        $return .= quotestr($value, true) . ",";
    }
    return $return . "\n";
}

/**
 * zbxeJSLoad
 *
 * Add HTML tag for external JS file
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array  $scripts  Array of scripts
 * @param string $path     Path to scripts
 */
function zbxeJSLoad($scripts, $path = 'local/app/everyz/js') {
    global $zbxeLoadedJS;
    foreach ($scripts as $value) {
        if (!in_array($path . '/' . $value, $zbxeLoadedJS)) {
            echo '<script src="' . $path . '/' . $value . '" type="text/javascript"></script>';
            $zbxeLoadedJS[] = $path . '/' . $value;
        }
    }
}

/**
 * zbxeArraySearch
 *
 * Search for values in a named array
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array  $array  Array
 * @param string $key    Key to check
 * @param string $value  Required value
 */
function zbxeArraySearch($array, $key, $value) {
    foreach ($array as $k => $v) {
        if ($v[$key] == $value) {
            return $k;
        }
    }
    return null;
}

/**
 * zbxeUpdateTranslation
 *
 * Importa traduções do EveryZ e de seus módulos
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array  $json      JSON data converted to PHP array
 * @param boolean $resultOK  Variable with information about problems runing SQL commands
 * @param boolean $debug     If true the function will show debug messages instead run sql commands
 */
function zbxeUpdateTranslation($json, $resultOK, $debug = false) {
    if (isset($json["translation"])) {
        $insert = $update = 0;
        foreach ($json["translation"] as $row) {
            // Populate translations array
            if (!isset($translations[$row['lang']])) {
                $translations[$row['lang']] = zbxeSQLList(zbxeStandardDML('SELECT lang, tx_original, tx_new, module_id FROM `zbxe_translation` '
                                . ' WHERE lang = ' . quotestr($row['lang']) . ' ORDER BY tx_original'));
            }
            $translate = zbxeArraySearch($translations[$row['lang']], 'tx_original', $row['tx_original']);
            if (!isset($translations[$row['lang']][$translate])) {
                $sql = "INSERT INTO zbxe_translation (lang, tx_original, tx_new, module_id) VALUES ("
                        . quotestr($row['lang']) . "," . quotestr($row['tx_original'])
                        . "," . quotestr($row['tx_new']) . ", " . quotestr($row['module_id']) . ")";
                $insert++;
            } else {
                $translate = $translations[$row['lang']][$translate];
                //var_dump($translate);
                $sql = ($translate['tx_new'] == $row['tx_new'] ? '' :
                                'UPDATE zbxe_translation SET tx_new = ' . quotestr($row['tx_new'])
                                . ' WHERE lang = ' . quotestr($row['lang']) . ' and tx_original = '
                                . quotestr($row['tx_original']));
                $update++;
            }
            if (trim($sql) !== '') {
                $resultOK = DBexecute($sql);
                if (!$resultOK)
                    return false;
            }
        }
        if ($debug) {
            zbxeErrorLog(true, 'EveryZ - Translation update: [from file: ' . count($json["translation"])
                    . '| new values: ' . $insert . '| updated values: ' . $update . ']');
        }
        return count($json["translation"]);
    }
}

/**
 * zbxeUpdateConfigImages
 *
 * Importa as imagens padrões do everyz
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array   $json      JSON data converted to PHP array
 * @param boolean $resultOK  Variable with information about problems runing SQL commands
 * @param boolean $debug     If true the function will show debug messages instead run sql commands
 */
function zbxeUpdateConfigImages($json, $resultOK, $debug = false) {
    // aqui
    $extraCheck = (CWebUser::getType() > USER_TYPE_ZABBIX_ADMIN) && (zbxeConfigValue("zbxe_init_images", 0) == 0);
    if (isset($json["images"]) && $extraCheck) {
        $report['images'] = ['source' => count($json["images"]), 'insert' => 0, 'update' => 0];
        foreach ($json["images"] as $row) {
            $imageIDs = updateImage($row);
            $imageID = $imageIDs['imageids'][0];
            $report['images']['update'] ++;
            $newImageIDs[$row['imageid']] = $imageID;
            //if ($imageID !== $row['imageid']) {
            //zbxeConfigImageIDs();
            //}
        }
        //        var_dump($newImageIDs);
        // Updating config image references
        if (isset($json["imageReferences"])) {
            //var_dump(['new_image_id', $newImageIDs]);
            //var_dump(['old_references', $json["imageReferences"]]);
            foreach ($json["imageReferences"] as $row) {
                //var_dump($row['imageid']);
                //if ($newImageIDs[$row['imageid']] !== $row['imageid']) {
                //var_dump(['Imagem atualizada [' . $newImageIDs[$row['imageid']] . ' / ' . $row['imageid'] . ']']);
                zbxeUpdateConfigValue($row['tx_option'], $newImageIDs[$row['imageid']]);
                //}
            }
        }
        zbxeUpdateConfigValue("zbxe_init_images", 1);
        zbxeErrorLog(true, 'EveryZ - Images update: [from file: ' . $report['images']['source']
                . '| Updated: ' . $report['images']['update'] . ']');
    }
}

/**
 * zbxeUpdateConfig
 *
 * Importa configurações do EveryZ e de seus módulos
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array   $json      JSON data converted to PHP array
 * @param boolean $resultOK  Variable with information about problems runing SQL commands
 * @param boolean $debug     If true the function will show debug messages instead run sql commands
 */
function zbxeUpdateConfig($json, $resultOK, $debug = false) {
    $report = [];
    if (isset($json["config"])) {
        $report['config'] = ['source' => count($json["config"]), 'insert' => 0, 'update' => 0];
        $sql = zbxeStandardDML('SELECT * FROM `zbxe_preferences` ORDER BY userid, tx_option');
        $config = zbxeSQLList($sql);
        foreach ($json["config"] as $row) {
            $cIndex = zbxeArraySearch($config, 'tx_option', $row['tx_option']);
            if (!isset($config[$cIndex]['tx_option'])) {
                $sql = zbxeInsert("zbxe_preferences", ['userid', 'tx_option', 'tx_value', 'st_ativo']
                        , [$row['userid'], $row['tx_option'], $row['tx_value'], $row['st_ativo']]);
                $report['config']['insert'] ++;
            } else {
                $sql = ($config[$cIndex]['tx_value'] == $row['tx_value'] ? '' :
                                zbxeUpdate("zbxe_preferences", ['userid', 'tx_option', 'tx_value', 'st_ativo']
                                        , [$row['userid'], $row['tx_option'], $row['tx_value'], $row['st_ativo']]
                                        , ['tx_option'], [$row['tx_option']]));
                $report['config']['update'] ++;
            }
            if (trim($sql) !== '') {
                $resultOK = DBexecute($sql);
                if (!$resultOK)
                    return false;
            }
        }
        if ($debug) {
            zbxeErrorLog(true, 'EveryZ - Configuration update: [from file: ' . $report['config']['source']
                    . '| new values: ' . $report['config']['insert']
                    . '| updated values: ' . $report['config']['update'] . ']');
        }
    }
    // Import images
    zbxeUpdateConfigImages($json, $resultOK, $debug);
}

/**
 * zbxeStandardDML
 *
 * Normaliza comandos DML entre o MySQL e o PostgreSQL
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $query     SQL Statement
 */
function zbxeStandardDML($query) {
    global $DB;
    if ($DB['TYPE'] == ZBX_DB_POSTGRESQL) {
        $query = str_replace('varchar', 'character varying', $query);
        $query = str_replace('int', 'integer', $query);
        $query = str_replace("`", '', $query);
    }
    return $query;
}

/**
 * getImageId
 *
 * Return the imageid from a image name
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string $name     Name of image
 */
function getImageId($name) {
    return zbxeFieldValue("select imageid from images WHERE name = " . quotestr($name), "imageid");
}

/**
 * getImageName
 *
 * Return the imageid from a image name
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string $name     Name of image
 */
function getImageName($imageid) {
    return zbxeFieldValue("select name from images WHERE imageid = " . quotestr($imageid), "name");
}

function updateImage($image) {
    $imageid = getImageId($image['name']);
    if (intval($imageid) > 0) {
        $result = API::Image()->update([
            'imageid' => $imageid,
            'name' => $image['name'],
            'image' => $image['image']
        ]);
    } else {
        $result = API::Image()->create([
            'name' => $image['name'],
            'imagetype' => $image['imagetype'],
            'image' => $image['image']
        ]);
    }
    return $result;
}

function zbxeStartDefinitions() {
    global $DB;
    if (isset($DB) && !defined("ZE_DBFQ")) {
        if ($DB['TYPE'] == ZBX_DB_POSTGRESQL) {
            define("ZE_DBFQ", "");
            define("ZE_QUOTECHAR", "'");
        } else {
            define("ZE_DBFQ", "`");
            define("ZE_QUOTECHAR", '"');
        }
    }
}

function zbxeConfigImageIDs() {
    return 'tx_option in (' . quotestr("company_logo_login") . ',' . quotestr("company_logo_site") . ',' . quotestr("geo_default_poi") . ')'
            . " OR tx_option like " . quotestr('zbxe_default_image_%') . " OR tx_option like " . quotestr('%_poi')
            . " OR tx_option like " . quotestr('%_logo_%') . " AND tx_option not like " . quotestr('%_width%')
    ;
}

// Original: http://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php
// With changes by Adail Horst
function isJson($string) {
    $return = json_decode($string, true);
    return (json_last_error() == JSON_ERROR_NONE ? $return : false);
}

function optArrayValue($array, $value, $default = "") {
    return (isset($array[$value]) ? $array[$value] : $default);
}

function zbxeFullScreen() {
    if (zbxeConfigValue('custom_full_screen') == "1") {
        zbxeJSLoad(['everyzFunctions.js']);
    }
}

function zbxeResetConfiguration() {
    try {
        DBexecute(zbxeStandardDML("DROP TABLE `zbxe_preferences` "));
    } catch (Exception $e) {
        
    }
    try {
        DBexecute(zbxeStandardDML("DROP TABLE `zbxe_translation` "));
    } catch (Exception $e) {
        
    }
    $path = str_replace("/everyz/include", "/everyz", dirname(__FILE__));
    require_once $path . '/everyz.initdb.php';
}

function zbxeErrorLog($show, $message) {
    if ($show == true) {
        error_log($message, 0);
    }
}

function zbxeCustomMenu() {
    $logoSize = zbxeConfigValue("company_logo_width", 0, 120) . "px";
    $logoCompany = new CDiv(SPACE, '');
    $logoCompany->setAttribute('style', 'float: left; margin: 10px 0px 0 0; background: url("zbxe-logo.php") no-repeat; height: 25px; width: '
            . $logoSize . '; cursor: pointer;');
    $logoZE = new CDiv(SPACE, '');
    $logoZE->setAttribute('style', 'float: left; margin: 10px 0px 0 0; background: url("local/app/everyz/images/zbxe-logo.png") no-repeat; height: 25px; width: 30px; cursor: pointer;');
    $return = (new CLink($logoCompany, 'zabbix.php?action=dashboard.view'));
    if (zbxeConfigValue("show_everyz_logo", 0, 1) == 1) {
        $return->addItem(new CLink($logoZE, 'http://www.everyz.org'));
    }
    return $return;
}

function zbxeEveryZGlobal() {
    echo "\n" . '<script src="local/app/everyz/js/everyzFunctions.js"></script>';
    echo "\n" . '<link href="local/app/everyz/css/everyz.css" rel="stylesheet" type="text/css" id="skinSheet">';
}

function zbxeMenuUserType() {
    return zbxeConfigValue("everyz_userlevel", 0, USER_TYPE_SUPER_ADMIN);
}

// End Functions ===============================================================
// Enviroment configuration
try {
    global $VG_INSTALL;
    if (!isset($VG_INSTALL)) {
        global $VG_BANCO_OK;
        $VG_BANCO_OK = false;
        zbxeStartDefinitions();
        $ezCurrent = DBfetch(DBselect('select tx_value from zbxe_preferences WHERE tx_option = ' . quotestr("everyz_version")));
        if (empty($ezCurrent)) {
            zbxeErrorLog($VG_DEBUG, 'EveryZ - Fresh install [' . $ezCurrent . '] ...');
            $path = str_replace("/everyz/include", "/everyz", dirname(__FILE__));
            require_once $path . '/init/everyz.initdb.php';
        } else {
            $VG_BANCO_OK = true;
            $ezCurrent = $ezCurrent['tx_value'];
        }
        if ($VG_BANCO_OK && (int) $ezCurrent !== (int) EVERYZBUILD) {
            zbxeErrorLog($VG_DEBUG, 'EveryZ - Upgrade - [Current: ' . $ezCurrent . ' / New:' . EVERYZBUILD . '] ');
            $path = str_replace("/everyz/include", "/everyz", dirname(__FILE__));
            require_once $path . '/init/everyz.upgradedb.php';
        }
    }
    // Verificar se as imagens existem
    if (intval(zbxeFieldValue("select COUNT(*) as total from images where name like 'zbxe_%' ", "total"), 0) < 9) {
        //var_dump(zbxeFieldValue("select COUNT(*) as total from images where name like 'zbxe_%' ", "total"));
        $path = str_replace('/include', "", realpath(dirname(__FILE__))) . "/init";
        $json = json_decode(file_get_contents("$path/everyz_config.json"), true);
        zbxeUpdateConfigImages($json, true, false);
    }
} catch (Exception $e) {
    return FALSE;
}

