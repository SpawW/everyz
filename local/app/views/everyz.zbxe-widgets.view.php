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
// Module Functions 
function getWidgetItemData() {
    global $filter;
    $query = 'SELECT tx_value, tx_option from zbxe_preferences where '
            . ($filter["item"] !== "" ? ' tx_option = ' . quotestr($filter["item"]) : ' tx_option like ' . quotestr($filter['widget'] . '_link_%'))
            . ' order by tx_value';
    $result = DBselect($query);
    $cont = 0;
    $report = [["name" => "", "title" => "", "row" => "", "order" => ""]];
    while ($row = DBfetch($result)) {
        $tmp = explode("|", $row['tx_value']);
        $report[$cont]['name'] = $tmp[0];
        $report[$cont]['title'] = $tmp[1];
        $report[$cont]['itemid'] = $row["tx_option"];
        $cont++;
    }
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
addFilterParameter("widgettype", T_ZBX_STR);
addFilterParameter("dml", T_ZBX_STR);
addFilterParameter("widget", T_ZBX_STR, "", false, true, false);
addFilterParameter("item", T_ZBX_STR, "", false, true, false);

// Mode of report
addFilterParameter("mode", T_ZBX_STR, "", false, false, false);

check_fields($fields);

// DML actions
if (hasRequest('dml')) {
    switch ($filter['mode']) {
        case "widget.edit":
        case "widget.add":
            var_dump("sQL - " . $sql);
            $name = str_replace("|", "", $filter['name']);
            $title = $filter['row'] . "|" . $filter['order'] . "|" . $name . "|" . str_replace("|", "", $filter['title']);
            if ($filter["mode"] == "widget.add") {
                $last = explode("_", zbxeFieldValue('select MAX(tx_option) as ultimo from zbxe_preferences zp where zp.tx_option like "widget_%"', "ultimo"));
                $last = intval($last[1]) + 1;
                $name = "widget_" . $last; //. "_" . $name;
// Insert
                $sql = "insert into zbxe_preferences values (0," . quotestr($name) . "," . quotestr($title) . ",1)";
            } else {
// Update
                $sql = "update zbxe_preferences set tx_value = " . quotestr($title) . " where tx_option = " . quotestr($filter["widget"]);
            }
            show_messages(prepareQuery($sql), _zeT('Widget ' . ($filter["mode"] == "widget.add" ? "added" : "updated")));
            $filter['mode'] = '';
            $filter["widget"] = '';
            break;
        case "widget.item.edit":
        case "widget.item.add":
            $name = str_replace("|", "", $filter['name']);
            $title = $name . "|" . str_replace("|", "", $filter['title']);
            if ($filter["mode"] == "widget.item.add") {
                $last = explode("_", zbxeFieldValue('select MAX(tx_option) as ultimo from zbxe_preferences zp where zp.tx_option like ' .
                                quotestr($filter["widget"] . '_link_%'), "ultimo"));
                if (count($last) > 2) {
                    $last = intval($last[3]) + 1;
                } else {
                    $last = 1;
                }
                $name = $filter["widget"] . '_link_' . $last; //. "_" . $name;
// Insert
                $sql = "insert into zbxe_preferences values (0," . quotestr($name) . "," . quotestr($title) . ",1)";
            } else {
// Update
                $sql = "update zbxe_preferences set tx_value = " . quotestr($title) . " where tx_option = " . quotestr($filter["item"]);
            }

            show_messages(prepareQuery($sql), _zeT('Widget item ' . ($filter["mode"] == "widget.add" ? "added" : "updated")));
            $filter['mode'] = 'widget.items';
            $filter["item"] = '';
            break;
// Delete
        case "widget.item.delete":
            if (trim($filter["item"]) == "") {
                error(_zeT("Invalid parameters!"));
                break;
            }
            $sql = 'delete from zbxe_preferences where tx_option = ' . quotestr(trim($filter["item"]));
            show_messages(prepareQuery($sql), _zeT('Widget item ' . ($filter["mode"] == "widget.add" ? "added" : "deleted")));
            $filter['mode'] = 'widget.items';
            $filter['item'] = '';
            break;
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
if (strpos($filter['mode'], "edit") > 0 || strpos($filter['mode'], "add") > 0) {
    $createButton = null;
} else {
    $createButton = (new CList())->addItem(new CSubmit('form', _zeT('Create ' . (strpos($filter['mode'], "item") > 0 ? 'item' : 'widget'))));
}
if (in_array($filter['mode'], ["widget.edit", "widget.items", "widget.item.edit", "widget.item.add"])) { // Recover widget data
    $query = 'SELECT tx_value, tx_option from zbxe_preferences where tx_option = "' . $filter['widget'] . '" order by tx_value';
    $result = DBselect($query);
    while ($row = DBfetch($result)) {
        $tmp = explode("|", $row['tx_value']);
        $data['name'] = $tmp[2];
        $data['title'] = $tmp[3];
        $data['row'] = $tmp[0];
        $data['order'] = $tmp[1];
    }
    $extraTitle = " - [" . $data['title'] . "]";
} else {
    $extraTitle = "";
    $data = [];
}

$dashboard = (new CWidget())
        ->setTitle(EZ_TITLE . _zeT('Widgets configuration') . (strpos($filter['mode'], "item") > 0 ? " - Items" . $extraTitle : ""))
        ->setControls((new CForm('POST'))
        ->cleanItems()
        ->addItem(new CInput('hidden', 'action', $filter["action"]))
        ->addItem(new CInput('hidden', 'mode', 'widget.' . (strpos($filter["mode"], 'item') ? "item." : "") . 'add'))
        ->addItem(new CInput('hidden', 'widget', $filter["widget"]))
        ->addItem($createButton))
;
$toggle_all = (new CColHeader(
        (new CDiv())
                ->addClass(ZBX_STYLE_TREEVIEW)
                ->addClass('app-list-toggle-all')
                ->addItem(new CSpan())
        ))->addStyle('width: 18px');
$form = (new CForm('POST', 'everyz.php'))->setName($moduleName);
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
}
if ($filter['mode'] !== "") {
    switch ($filter['mode']) {
        case "widget.add":
        case "widget.edit":
            if ($filter['mode'] == "widget.add") { // Recover widget data aqui2
                $data = ["name" => "", "title" => "", "row" => "", "order" => ""];
            }
            $formWidget = (new CFormList())
                    ->addRow(_('Name')
                            , (new CTextBox('name', $data["name"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow(_('Title')
                            , (new CTextBox('title', $data["title"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow(_zeT('Position'), [
                        _('Row'), '&nbsp;', (new CNumericBox('row', $data['row'], 2))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
                        , '&nbsp;', _('Order'), '&nbsp;', (new CNumericBox('order', $data['order'], 2))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)])
                    ->addRow(bold('Type of widget'), [(new CRadioButtonList('widgettype', (int) $filter['widgettype']))->setModern(true)
                        ->addValue(_zeT('Links'), 0)->addValue(_zeT('jQuery'), 1)->addValue(_zeT('Custom DIV'), 2)])
                    ->addItem(new CInput('hidden', 'action', $filter["action"]))
                    ->addItem(new CInput('hidden', 'mode', $filter['mode']))
                    ->addItem(new CInput('hidden', 'widget', $filter["widget"]))
                    ->addItem(new CInput('hidden', 'dml', "Y"))

            ;
            $tab = (new CTabView())->addTab('widget', _zeT('Widget'), $formWidget);
            $cancelAction = '?action=' . $moduleName;
            if (hasRequest('widget') && $filter['widget'] !== "") {
                $tab->setFooter(makeFormFooter(new CSubmit('update', _('Update')), [
                    zeCancelButton($cancelAction)
                ]));
            } else {
                $tab->setFooter(makeFormFooter(new CSubmit('add', _('Add')), [zeCancelButton($cancelAction)]));
            }
            $table->addRow($tab);
            break;
        case "widget.item.add":
        case "widget.item.edit":
            if ($filter['mode'] == "widget.item.add") { // Recover widget data aqui2
                $data = [["name" => "", "title" => "", "row" => "", "order" => ""]];
            } else {
                $data = getWidgetItemData();
            }
            $formWidget = (new CFormList())
                    ->addRow(_('Name')
                            , (new CTextBox('name', $data[0]["name"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow(_('Title')
                            , (new CTextBox('title', $data[0]["title"], false, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addItem(new CInput('hidden', 'action', $filter["action"]))
                    ->addItem(new CInput('hidden', 'mode', $filter['mode']))
                    ->addItem(new CInput('hidden', 'widget', $filter["widget"]))
                    ->addItem(new CInput('hidden', 'item', $filter["item"]))
                    ->addItem(new CInput('hidden', 'dml', "Y"))

            ;
            $tab = (new CTabView())->addTab('widget', _('Widget'), $formWidget);
            $cancelAction = '?action=' . $moduleName . '&widget=' . $filter["widget"] . "&mode=widget.items";
            if (hasRequest('widget') && $filter['widget'] !== "") {
                $tab->setFooter(makeFormFooter(new CSubmit('update', _(
                                        (strpos($filter['mode'], "add") > 0 ? 'Add' : 'Update')
                                )), [
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
            if ($report[0]["name"] == "") {
                $table->setNoDataMessage(_zeT('Please add some item to this widget.'));
            } else {
                $table->setHeader(array(_("Name"), _zeT("Title"), _("Actions")));
                $linha = array();
                for ($i = 0; $i < $cont; $i++) {
                    $linha[0] = new CCol($report[$i]['name'], 1);
                    $linha[1] = new CCol($report[$i]['title'], 1);
                    $baseAction = '?action=' . $moduleName . '&widget=' . $filter["widget"] . '&item=' . $report[$i]['itemid'];
                    $linha[2] = array(
                        (new CRedirectButton(_('Edit'), $baseAction . "&mode=widget.item.edit"
                        , null))->setId('edit')
                        , "&nbsp;"
                        , (new CRedirectButton(_('Delete'), $baseAction . "&dml=Y&mode=widget.item.delete", _('Delete record?')))->setId('delete')
                    );
                    $table->addRow($linha);
                }
            }
            break;
        default:
            if ($filter["mode"] !== '') {
                error("Larga mão de preguiça... vai codificar que isso não vai ficar pronto sozinho!!! [" . $filter["mode"] . "]");
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
