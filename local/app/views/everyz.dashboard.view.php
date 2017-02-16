<?php

/*
 * * Purpose: Create widgets for everyz dashboard
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


$dashboard = (new CWidget())->setTitle(_('EveryZ Dashboard'));
$loadScripts = [];

function newWidget($p_id, $p_title, $p_content, $p_expanded = true, $p_icon = []) {
    global $loadScripts;
    $tmp = explode("|", $p_title);
    if (isset($tmp[4]) && $tmp[4] == 2) {
        $loadScripts[] = $tmp[2];
        return (new CUiWidget($p_id, (new CDiv(''))->setName('body-' . $tmp[2])->setId('body-' . $tmp[2])))->setHeader(_zeT($tmp[3]), [$p_icon], true);
    } else {
        return (new CCollapsibleUiWidget($p_id, (new CDiv($p_content))->setName('body-' . $tmp[2])->setId('body-' . $tmp[2])))
                        ->setExpanded($p_expanded)
                        ->setHeader(_zeT($tmp[3]), [$p_icon], true);
    }
}

function linkList($p_filter) {
    $table = (new CTableInfo());
    $res = DBselect('SELECT userid, tx_option, tx_value from zbxe_preferences zpre '
            . ' WHERE userid in (0,' . CWebUser::$data['userid'] . ') and st_ativo = 1 '
            . ' and tx_option like ' . quotestr($p_filter)
            . ' order by userid, tx_option');
    while ($row = DBfetch($res)) {
        $tmp = explode("|", $row['tx_value']);
        $name = $tmp[1];
        $tag = $tmp[0];
        $table->addRow([
            [(new CImg('local/app/everyz/images/' . $tag . '.png', 'no image'))->setAttribute('style', 'vertical-align:middle;')
                , (new CLink("&nbsp;" . _zeT($name), 'everyz.php?action=' . $tag))
        ]]);
    }
    return $table;
}

//Todo: Descobrir o maior número de linha
//Todo: separar os widgets em linhas
// Quantidade de linhas
$totRows = zbxeFieldValue("SELECT max(tx_value) as ultimo FROM zbxe_preferences WHERE tx_option LIKE 'widget_%' and tx_value like '%|%|%' ", 'ultimo');
$totRows = explode("|", $totRows)[0];

for ($rowNum = 0; $rowNum <= $totRows; $rowNum++) {
// Recuperando a lista de widgets
    //   var_dump($rowNum);
    $query = 'SELECT tx_option, tx_value FROM zbxe_preferences '
            . ' where tx_option like ' . quotestr("widget_%") . ' and tx_option not like ' . quotestr("%link%") . ' and st_ativo = 1 '
            . ' and tx_value like ' . quotestr($rowNum . '|%')
            . ' order by userid, tx_value';
    $res = DBselect($query);
    //var_dump($query);

    $dashboardGrid = [];
    $cont = 0;
    while ($row = DBfetch($res)) {
        //var_dump($row['tx_option']);
        $dashboardGrid[$cont][0] = newWidget($row['tx_option'], $row['tx_value'], linkList($row['tx_option'] . '_link_%'));
        $cont++;
    }
    $dashboardRow = [];
    for ($row = 0; $row < count($dashboardGrid); $row++) {
        for ($col = 0; $col < count($dashboardGrid[$row]); $col++) {
            $dashboardRow[$row] = (new CDiv($dashboardGrid[$row]))->addClass('cell')->addClass('row');
        }
    }
    if ($dashboardRow !== []) {
        $dashboardTable = (new CDiv($dashboardRow))
                ->addClass('table')
                ->addClass('widget-placeholder');
        $dashboard->addItem($dashboardTable);
    }
}


$dashboard->show();

for ($i = 0; $i < count($loadScripts); $i++) {
    require_once 'local/app/everyz/js/everyz.widget.' . $loadScripts[$i] . '.js.php';
}
