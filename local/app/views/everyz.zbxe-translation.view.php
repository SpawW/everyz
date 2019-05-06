<?php

/*
 * * Purpose: Strings translation
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
$moduleName = "zbxe-translation";
$baseProfile .= $moduleName;
$moduleTitle = 'Translation';
$otherGroup = 'Other';

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
$filter['sourceTranslation'] = [T_ZBX_STR, O_OPT, P_SYS, null, null];
$filter['stringTranslation'] = [T_ZBX_STR, O_OPT, P_SYS, null, null];

check_fields($fields);

$dataTab = new CTabView();
$lang = CWebUser::$data['lang'];

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function addTab($key, $value, $dataTab) {
    global $lang;
    $tmp = explode("|", zbxeFieldValue("SELECT tx_value FROM zbxe_preferences where tx_option like 'widget_%_link_%' and tx_value like "
                    . quotestr($key . "|%"), "tx_value"));
    $desc = (count($tmp) == 2 ? $tmp[1] : $key);
    $tabContent = new CFormList();
    $tabContent->addRow(bold(_zeT("Source")), bold(_zeT("Translation")));
    foreach ($value as $tmp2) {
        $tabContent->addRow($tmp2[0], ( $lang == "en_GB" ? $tmp2[1] :
                        (new CTextBox('stringTranslation[]', $tmp2[1]))->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
                                ->setAttribute('style', ($tmp2[0] == $tmp2[1] ? 'background-color: #f7e360;' : '') . ' width: 400px')
                )
        );
        $tabContent->addItem(new CInput('hidden', 'sourceTranslation[]', $tmp2[0]));
    }
    $dataTab->addTab($key, $desc, $tabContent);
}

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
if (CWebUser::getType() < USER_TYPE_SUPER_ADMIN) {
//    access_deny(ACCESS_DENY_PAGE);
}

/* * ***************************************************************************
 * Change Data
 * ************************************************************************** */
/* Update user translations if needed */
parse_str(file_get_contents('php://input'), $httpParams);
$updated = false;
$dml = false;
$sql = $dmlReport = "";
if (isset($httpParams['stringTranslation'])) {
    foreach ($httpParams['stringTranslation'] as $key => $value) {
        $sourceString = $httpParams['sourceTranslation'][$key];
        if ($sourceString !== $value) {
            $current = zbxeFieldValue("select tx_new from zbxe_translation where tx_original = "
                    . quotestr($sourceString) . " and lang=" . quotestr($lang), "tx_new");
            if ($current !== $value) {
                $sql = zbxeUpdate('zbxe_translation', ['tx_new'], [$value], ['tx_original', 'lang'], [$sourceString, $lang]);
                $dml = true;
                $dmlReport .= 'Update translation for string "' . $sourceString;
                prepareQuery($sql);
            }
        }
    }
}
if ($dml) {
    show_message(_zeT('Translation strings updated!')); 
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */

$query = 'SELECT tx_original, module_id FROM zbxe_translation zet where lang='
        . quotestr("en_GB") . ' and tx_original <> '
        . quotestr("Everyz") . ' order by module_id';
$result = prepareQuery($query);
$strings = [];

while ($row = DBfetch($result)) {
    $translate = _zeT($row['tx_original'], $row['module_id']);
    $next = (isset($strings[$row['module_id']]) ? count($strings[$row['module_id']]) : 0);
    $strings[$row['module_id']][$next] = [$row['tx_original'], ($translate == "" ? $row['tx_original'] : $translate)];
}

// Agrupando strings originais
$report = [];
foreach ($strings as $key => $value) {
    $key2 = ($key == "" || count($strings[$key]) < 10 ? $otherGroup : $key);
    foreach ($value as $tmp2) {
        $next = (isset($report[$key2]) ? count($report[$key2]) : 0);
        $report[$key2][$next] = [$tmp2[0], $tmp2[1], $key];
    }
}



/* * ***************************************************************************
 * Display
 * ************************************************************************** */
commonModuleHeader($moduleName, $moduleTitle, true);

foreach ($report as $key => $value) {
    if ($key !== $otherGroup) {
        addTab($key, $value, $dataTab);
    }
}
addTab($otherGroup, $report[$otherGroup], $dataTab);
if ($lang !== "en_GB") {
    $dataTab->setFooter(makeFormFooter(new CSubmit('update', _('Update'))));
}

/* * ***************************************************************************
 * Display Footer
 * ************************************************************************** */

$form->addItem([$dataTab]);
$dashboard->addItem($form)->show();
