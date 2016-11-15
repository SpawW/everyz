<?php

/*
 * * Purpose: Configure widgets / items
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

// Definitions -----------------------------------------------------------------
//include('local/app/everyz/js/zbxe-widgets.edit.js.php');
// Module Functions 
function getWidgetItemData() {
    $query = 'SELECT tx_value, tx_option from zbxe_preferences where '
            . (hasRequest("link") ? ' tx_option = ' . quotestr(getRequest('link')) : ' tx_option like ' . quotestr(getRequest('widget') . '_link_%'))
            . ' order by tx_value';
    //var_dump($query);
    $result = DBselect($query);
    $cont = 0;
    $report = [];
    while ($row = DBfetch($result)) {
        $tmp = explode("|", $row['tx_value']);
        $report[$cont]['name'] = $tmp[0];
        $report[$cont]['title'] = $tmp[1];
        $report[$cont]['linkid'] = $row["tx_option"];
        $cont++;
    }
    //var_dump($report);
    return $report;
}

// Configuration variables =====================================================
$moduleName = "zbxe-widgets";
$baseProfile .= $moduleName;

// Common fields
addFilterParameter("format", T_ZBX_INT);
addFilterActions();

// Specific fields
addFilterParameter("nameFilter", T_ZBX_STR);
addFilterParameter("name", T_ZBX_STR);
addFilterParameter("title", T_ZBX_STR);
addFilterParameter("row", T_ZBX_INT);
addFilterParameter("order", T_ZBX_INT);
addFilterParameter("widget", T_ZBX_STR);
addFilterParameter("widgettype", T_ZBX_STR);
addFilterParameter("dml", T_ZBX_STR);
addFilterParameter("link", T_ZBX_STR);
// Mode of report
addFilterParameter("mode", T_ZBX_STR, "", false, false, false);

check_fields($fields);

// DML actions
if (hasRequest('dml')) {
    switch ($filter['mode']) {
        case "widget.edit":
        case "widget.add":
            $filter['mode'] = '';
            $name = str_replace("|", "", getRequest('name'));
            $title = getRequest('row') . "|" . getRequest('order') . "|" . $name . "|" . str_replace("|", "", getRequest('title'));
            if ($filter["mode"] == "widget.add") {
                $last = explode("_", zbxeFieldValue('select MAX(tx_option) as ultimo from zbxe_preferences zp where zp.tx_option like "widget_%"', "ultimo"));
                $last = intval($last[1]) + 1;
                $name = "widget_" . $last; //. "_" . $name;
// Insert
                $sql = "insert into zbxe_preferences values (0," . quotestr($name) . "," . quotestr($title) . ",1)";
            } else {
// Update
                $sql = "update zbxe_preferences set tx_value = " . quotestr($title) . " where tx_option = " . quotestr(getRequest2("widget"));
            }
            prepareQuery($sql);
            //var_dump($sql);
            break;
        case "widget.item.edit":
        case "widget.item.add":
            $filter['mode'] = '';
            $name = str_replace("|", "", getRequest('name'));
            $title = $name . "|" . str_replace("|", "", getRequest('title'));
            if ($filter["mode"] == "widget.item.add") {
                $last = explode("_", zbxeFieldValue('select MAX(tx_option) as ultimo from zbxe_preferences zp where zp.tx_option like "widget_%_link_%"', "ultimo"));
                $last = intval($last[1]) + 1;
                $name = "widget_" . $last; //. "_" . $name;
// Insert
                $sql = "insert into zbxe_preferences values (0," . quotestr($name) . "," . quotestr($title) . ",1)";
            } else {
// Update
                $sql = "update zbxe_preferences set tx_value = " . quotestr($title) . " where tx_option = " . quotestr(getRequest2("link"));
            }
            prepareQuery($sql);
            //var_dump($sql);
            break;
// Delete
        default:
            /* if ($filter["mode"] == "") {
              $filter["filter_set"] = "Filter";
              } */
            break;
    }
}
// End DML


/*
 * Display
 */
if (strpos(getRequest2('mode'), "edit") > 0 || strpos(getRequest2('mode'), "add") > 0) {
    $createButton = null;
} else {
    $createButton = (new CList())->addItem(new CSubmit('form', _zeT('Create ' . (strpos(getRequest2('mode'), "item") > 0 ? 'item' : 'widget'))));
}
$dashboard = (new CWidget())
        ->setTitle('EveryZ - ' . _zeT('Widgets configuration').(strpos(getRequest2('mode'), "item") > 0 ? " - Items":"")) // Module title
        ->setControls((new CForm('get'))
        ->cleanItems()
        ->addItem(new CInput('hidden', 'action', $filter["action"]))
        ->addItem(new CInput('hidden', 'mode', 'widget.add'))
        ->addItem($createButton))
;
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('GET', 'everyz.php'))->setName('cat');
$table = (new CTableInfo())->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS);

// Filtros =====================================================================
if (hasRequest('filter_rst')) { // Clean the filter parameters
    $filter['filter_rst'] = NULL;
    $filter['mode'] = NULL;
    $filter['nameFilter'] = "";
}


$widget = (new CFilter('web.' . $moduleName . '.filter.state'));

// Source data filter
$tmpColumn = new CFormList();
$tmpColumn->addRow(_('Name'), [ (new CTextBox('nameFilter', $filter['nameFilter']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)]);
$tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
$widget->addColumn($tmpColumn);

if ($filter['mode'] == "") {
    $dashboard->addItem($widget);
}

$filterSQL = "";
// Get data for report ---------------------------------------------------------
if (hasRequest('filter_set')) {
    // Sample Check if all required fields have values
//    checkRequiredField("hostids", "You need to provide a least one host in filter!");
    // $count variable for check if the report has results
    // $report for store the report data
    if ($filter['nameFilter'] !== "") {
        $filterSQL = " and tx_value like " . quotestr($filter['nameFilter'] . "%");
    }
    if ($requiredMissing == false) {
        // Get data for report
    }
} else {
    $table->setNoDataMessage(_('Specify some filter condition to see the values.'));
}
if (hasRequest('mode')) {
    switch ($filter['mode']) {
        case "widget.add":
        case "widget.edit":
            $data = ["name" => "", "title" => "", "row" => "", "order" => ""];
            if ($filter['mode'] == "widget.edit") { // Recover widget data
                $query = 'SELECT tx_value, tx_option from zbxe_preferences where tx_option = "'
                        . getRequest('widget') . '" order by tx_value';
                $result = DBselect($query);
                while ($row = DBfetch($result)) {
                    $tmp = explode("|", $row['tx_value']);
                    $data['name'] = $tmp[2];
                    $data['title'] = $tmp[3];
                    $data['row'] = $tmp[0];
                    $data['order'] = $tmp[1];
                }
            }
            $formWidget = (new CFormList())
                    ->addRow(_('Name')
                            , (new CTextBox('name', $data["name"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow(_('Title')
                            , (new CTextBox('title', $data["title"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow('Position', [
                        _('Row'), '&nbsp;', (new CNumericBox('row', $data['row'], 2))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
                        , '&nbsp;', _('Order'), '&nbsp;', (new CNumericBox('order', $data['order'], 2))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)])
                    ->addRow(bold('Type of widget'), [(new CRadioButtonList('widgettype', (int) $filter['widgettype']))->setModern(true)
                        ->addValue(_zeT('Links'), 0)->addValue(_zeT('jQuery'), 1)->addValue(_zeT('Custom DIV'), 2)])
                    ->addItem(new CInput('hidden', 'action', $filter["action"]))
                    ->addItem(new CInput('hidden', 'mode', $filter['mode']))
                    ->addItem(new CInput('hidden', 'widget', getRequest2("widget")))
                    ->addItem(new CInput('hidden', 'dml', "Y"))

            ;
            $tab = (new CTabView())->addTab('widget', _('Widget'), $formWidget);
            $cancelAction = '?action=' . $moduleName;
            if (hasRequest('widget') && getRequest('widget') !== "") {
                $tab->setFooter(makeFormFooter(new CSubmit('update', _('Update')), [
                    zeCancelButton($cancelAction)
//                    new CButtonCancel()
                ]));
            } else {
                $tab->setFooter(makeFormFooter(new CSubmit('add', _('Add')), [zeCancelButton($cancelAction)]));
            }
            $table->addRow($tab);
            break;
        case "widget.item.add":
        case "widget.item.edit":
            $data = getWidgetItemData();
            $formWidget = (new CFormList())
                    ->addRow(_('Name')
                            , (new CTextBox('name', $data[0]["name"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow(_('Title')
                            , (new CTextBox('title', $data[0]["title"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addItem(new CInput('hidden', 'action', $filter["action"]))
                    ->addItem(new CInput('hidden', 'mode', $filter['mode']))
                    ->addItem(new CInput('hidden', 'widget', getRequest2("widget")))
                    ->addItem(new CInput('hidden', 'link', getRequest2("link")))
                    ->addItem(new CInput('hidden', 'dml', "Y"))

            ;
            $tab = (new CTabView())->addTab('widget', _('Widget'), $formWidget);
            $cancelAction = '?action=' . $moduleName . '&widget=' . getRequest2("widget") . "&mode=widget.items";
            if (hasRequest('widget') && getRequest('widget') !== "") {
                $tab->setFooter(makeFormFooter(new CSubmit('update', _('Update')), [
                    //new CButtonCancel()
                    //(new CRedirectButton(_('Cancel'), $cancelAction, null))->setId('cancel')
                    zeCancelButton($cancelAction)
                ]));
            } else {
                $tab->setFooter(makeFormFooter(new CSubmit('add', _('Add')), [zeCancelButton($cancelAction)]));
            }
            $table->addRow($tab);
            break;
        case "widget.items":
            $report = getWidgetItemData();
            $cont = count($report);
            if ($cont > 0) {
                $table->setHeader(array(_("Name"), _("Title"), _("Actions")));
                $linha = array();
                for ($i = 0; $i < $cont; $i++) {
                    $linha[0] = new CCol($report[$i]['name'], 1);
                    $linha[1] = new CCol($report[$i]['title'], 1);
                    $baseAction = '?action=' . $moduleName . '&widget=' . getRequest2("widget") . '&link=' . $report[$i]['linkid'];
                    $linha[2] = array(
                        (new CRedirectButton(_('Edit'), $baseAction . "&mode=widget.item.edit"
                        , null))->setId('edit')
                        , "&nbsp;"
                        , (new CRedirectButton(_('Delete'), $baseAction . "&mode=widget.delete", _('Delete record?')))->setId('delete')
                    );
                    $table->addRow($linha);
                }
            }
            break;
        default:
            if ($filter["mode"] !== '') {
                error("Larga mão de preguiça... vai codificar que isso não vai ficar pronto sozinho!!! [" . getRequest2("mode") . "]");
            }
            break;
    }
} else { // Report
    $query = 'SELECT tx_option, tx_value FROM `zbxe_preferences` '
            . ' where tx_option like "widget%" '
            . ' and tx_option not like "%link%" and st_ativo = 1 ' . $filterSQL
            . ' order by userid, tx_option';
    $res = DBselect($query);
    $report = [];
    $cont = 0;
    while ($row = DBfetch($res)) {
        $tmp = explode("|", $row['tx_value']);
        $name = $row['tx_option'];
        $title = $tmp[3];
        $report[count($report)] = [$name, $title];
        $cont++;
    }

// Build the report header -----------------------------------------------------
    switch ($filter["format"]) {
        case 1;
            $table->setHeader(array(_zeT("Data")));
            break;
        case 0;
            $locale = localeconv();
            $currency = (strlen($locale['currency_symbol']) > 1 ? $locale['currency_symbol'] : '$');
            $table->setHeader(array(_("Name"), _("Title"), _("Actions")));
            break;
    }
// Building the report ---------------------------------------------------------
    if ($cont > 0) {
        $linha = array();
        $cont2 = count($report[0]) - 1;
        for ($i = 0; $i < $cont; $i++) {
            switch ($filter["format"]) {
                case 0;
                    $linha[0] = new CCol($report[$i][0], 1);
                    $linha[1] = new CCol($report[$i][1], 1);
                    $baseAction = '?action=' . $moduleName . '&widget=' . $report[$i][0];
                    $linha[2] = array(
                        (new CRedirectButton(_('Edit'), $baseAction . "&mode=widget.edit"
                        , null))->setId('edit')
                        , "&nbsp;"
                        , (new CRedirectButton(_('Items'), $baseAction . "&mode=widget.items", null))->setId('items')
                        , "&nbsp;"
                        , (new CRedirectButton(_('Delete'), $baseAction . "&mode=widget.delete", _('Delete record?')))->setId('delete')
                    );

                    // Calculo de UBM por host
                    $table->addRow($linha);
                    break;
                case 1;
                    $linhaCSV = "";
                    for ($x = 0; $x < $cont2; $x++) {
                        $linhaCSV .= quotestr($report[$i][$x]) . ";";
                    }
                    $table->addRow(array($linhaCSV));
                    break;
                case 2;
                    $table->addRow('Todo: Make a JSON');
                    break;
            }
        }
    }
}
$form->addItem([ $table]);

$dashboard->addItem($form)->show();
