<?php

/*
 * * Purpose: Bookmark EveryZ URLs
 * * @author Adail Horst - http://spinola.net.br/blog
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
 * Variables for Everyz integration
 * ************************************************************************** */
// Configuration variables =====================================================
$moduleName = "zbxe-bookmark";
$baseProfile .= $moduleName;
$moduleTitle = 'Bookmark Manager';
//var_dump(zbxeNextValue('shorten', 1976));
/* * ***************************************************************************
 * Module Variables
 * ************************************************************************** */
$filterSQL = "";
$report = [];
$data = [];

addFilterActions();

// Specific fields =============================================================
addFilterParameter("descriptionFilter", T_ZBX_STR, '', false, false, false);
addFilterParameter("dml", T_ZBX_STR, '', false, true, false);
addFilterParameter("hashKey", T_ZBX_INT, -1, false, true, false);

addFilterParameter("url", T_ZBX_STR, '', false, false, false);
addFilterParameter("description", T_ZBX_STR, '', false, false, false);

// Mode of report ==============================================================
//addFilterParameter("mode", T_ZBX_STR, "", false, false, false);
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */


/* * ***************************************************************************
 * Set Filter
 * ************************************************************************** */
// Filtros =====================================================================
if (hasRequest('filter_rst')) {
    $filter['filter_rst'] = NULL;
    $filter['action'] = $moduleName;
    $filter['mode'] = "";
} elseif (hasRequest('filter_set')) {
    if ($filter['descriptionFilter'] !== "") {
        $filterSQL = " WHERE tx_desc like " . quotestr("%" . $filter['descriptionFilter'] . "%");
    }
}
/* * ***************************************************************************
 * DML actions
 * ************************************************************************** */

if (hasRequest('dml')) {
    // Sample Check if all required fields have values
    if ($filter['mode'] !== 'delete') {
        checkRequiredField("url", _zeT("You need to provide a URL!"));
        checkRequiredField("description", _zeT("You need to provide a description for this URL!"));
        switch ($filter['mode']) {
            case "edit":
            case "add":
                break;
            default :
                break;
        }
    }
    if ($requiredMissing) {
        show_messages(false, 'Bookmark not updated!');
    } else {
        switch ($filter['mode']) {
            case "edit":
                break;
            case "add":
                $sql = zbxeInsert("zbxe_shorten", [ 'tx_url', 'tx_desc', 'userid', 'id_url']
                        , [$filter["url"], $filter["description"], CWebUser::$data['userid'], zbxeNextValue('shorten', 1976)]);
                show_messages(prepareQuery($sql), _zeT('Bookmark added'));
                break;
            case "delete":
                $sql = 'delete from zbxe_shorten where id_url = ' . quotestr(trim($filter["hashKey"]));
                show_messages(prepareQuery($sql), _zeT('Bookmark deleted!'));
                break;
        }
        $filter['mode'] = '';
    }
}
// End DML

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */

if (in_array($filter['mode'], ["edit", ""])) {
    $query = 'SELECT id_url, tx_url, tx_desc, userid from zbxe_shorten ' . $filterSQL . ' order by tx_desc';
    $result = DBselect($query);
    while ($row = DBfetch($result)) {
        $report[] = [$row["id_url"], $row["tx_desc"], $row["tx_url"], $row["userid"]];
        $data[] = $row;
    }
}

/* * ***************************************************************************
 * Display
 * ************************************************************************** */

if (in_array($filter['mode'], ["edit", "add"])) {
    $createButton = null;
} else {
    $createButton = (new CList())->addItem(new CSubmit('form', _zeT('Create bookmark')))
            ->addItem(new CInput('hidden', 'mode', 'add'))
    ;
}

commonModuleHeader($moduleName, $moduleTitle, true);

$commonList->addItem($createButton);

if ($filter['mode'] == "") {
    $tmpColumn = new CFormList();
    $tmpColumn->addRow(_zeT('Description'), [ (new CTextBox('descriptionFilter', $filter['descriptionFilter']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)]);
    $tmpColumn->addItem(new CInput('hidden', 'action', $filter["action"]));
    $dashboard->addItem(newFilterTab($moduleName,[$tmpColumn]));
}


if ($filter['mode'] !== "") {
    switch ($filter['mode']) {
        case "add":
        case "edit":
            if ($filter['mode'] === "add") {
                $data[0] = ["tx_desc" => "", "tx_url" => ""];
            }
            $formWidget = (new CFormList())
                    ->addRow(_('Description')
                            , (new CTextBox('description', $data[0]["tx_desc"], false, 60))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                    )
                    ->addRow(_('URL')
                            , (new CTextBox('url', $data[0]["tx_url"], false, 1000))->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
                    )
                    // Hidden
                    //->addItem(new CInput('hidden', 'action', $filter["action"]))
                    ->addItem(new CInput('hidden', 'mode', $filter['mode']))
                    ->addItem(new CInput('hidden', 'dml', "Y"))
            ;
            $tab = (new CTabView())->addTab('bookmark', _zeT('Bookmark'), $formWidget);
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
        default:
            if ($filter["mode"] !== '') {
                error("Larga mão de preguiça... vai codificar que isso não vai ficar pronto sozinho!!! [" . $filter["mode"] . "]");
            }
            break;
    }
} else {
// Build the report header -----------------------------------------------------
    switch ($filter["format"]) {
        case 1;
            $table->setHeader(array(_zeT("Data")));
            break;
        case 0;
            $table->setHeader(array(zbxeColHeader("Short URL", 13)// (new CColHeader(_zeT("Short URL")))->addStyle('width: 5%')
                , _("Description"), zbxeColHeader("URL", 60), zbxeColHeader("Action", 15)));
            break;
    }

    if (count($report) > 0) {
        $linha = array();
        for ($i = 0; $i < count($report); $i++) {

            switch ($filter["format"]) {
                case 0;
                    //print_r([IdObfuscator::encode($report[$i][0]),IdObfuscator::decode(IdObfuscator::encode($report[$i][0]))]);
                    $hashKey = IdObfuscator::encode($report[$i][0]);
                    $linha[0] = new CCol($hashKey, 1);
                    $linha[1] = new CCol($report[$i][1], 1);
                    $linha[2] = new CCol($report[$i][2], 1);
                    //$linha[3] = new CCol($report[$i][3], 1);
                    $baseAction = '?action=' . $moduleName . '&hashKey=' . $report[$i][0];
                    $linha[3] = array((new CRedirectButton(_('Delete'), $baseAction . "&dml=Y&mode=delete", _('Delete record?')))->setId('delete')
                        , "&nbsp;"
                        , ((new CButton('copyURL', _('Copy URL')))->onClick("javascript:copyText("
                                . quotestr("everyz.php?shorturl=".$hashKey."&fullscreen=1&hidetitle=1") . ");"))
                    );
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

/* * ***************************************************************************
 * Display Footer
 * ************************************************************************** */

$form->addItem([ $table]);

$dashboard->addItem($form)->show();
?>
<script language="Javascript">
    function copyText(text) {
        var textArea = document.createElement('textarea');
        textArea.setAttribute('style', 'width:1px;border:0;opacity:0;');
        document.body.appendChild(textArea);
        textArea.value = text;
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Short URL copied! Paste where you need it.');
    }
</script>
<?php
