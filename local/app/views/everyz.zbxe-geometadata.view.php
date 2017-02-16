<?php

/*
 * * Purpose: Generate ZabGeo Metadata
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
// Configuration variables =====================================================
$moduleName = "zbxe-geometadata";
$baseProfile .= $moduleName;
$moduleTitle = 'ZabGeo - Metadata';
// Common fields
addFilterActions();
// Specific fields
addFilterParameter("format", T_ZBX_INT, 0, false, false, false);
addFilterParameter("hostids", T_ZBX_INT, [], true, true);
// Field validation
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
checkAccessHost('hostids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */

/* * ***************************************************************************
 * Display
 * ************************************************************************** */
?>
<script language="JavaScript">
    jq2 = jQuery.noConflict();
    var json, txJson = '';
    jq2(function ($) {
        // Code using $ as usual goes here; the actual jQuery object is jq2
        //
        //json = JSON.parse('{"circle":[{"size":5000,"color":"#e6e600"},{"size":3000,"color":"#e6e600"}],"line":[{"lat":-3.73082,"lon":-38.53935},{"lat":-3.77091,"lon":-38.56922,"popup":"Link 1"}],"link":[{"hostid":10781},{"hostid":11781}]}');
        json = JSON.parse('{}');
        txJson = document.getElementById('jsonResult');
        txJson.value = formatJSON();
        //alert(txJson);
    });

    function formatJSON() {
        return JSON.stringify(json, null, 2).replace(/\\"/g, "\"").replace(/"\[/g, "[").replace(/\]"/g, "]");
    }

    function addCircle() {
        vSize = document.getElementById('circle_size').value;
        vColor = document.getElementById('circle_color').value;
        if (!json.hasOwnProperty("circle")) {
            json.circle = [];
        }
        json["circle"].push({"size": vSize, "color": vColor});
        txJson.value = formatJSON();
    }
    function addLine() {
        vLat = document.getElementById('line_lat').value;
        vLon = document.getElementById('line_lon').value;
        vPopUp = document.getElementById('line_popup').value;
        // Validation
        if (!validPosition(vLat)) {
            alert('Invalid latitude!');
            return false;
        }
        if (!validPosition(vLon)) {
            alert('Invalid longitude!');
            return false;
        }
        if (!json.hasOwnProperty("line")) {
            json.line = [];
        }
        json["line"].push({"lat": vLat, "lon": vLon, "popup": vPopUp});
        txJson.value = formatJSON();
    }

    function validPosition(pos) {
        var lngVal = /^-?((1?[0-7]?|[0-9]?)[0-9]|180)\.[0-9]{1,6}$/;
        return lngVal.test(pos);
    }

    function addLink() {
        var hostIDs = document.getElementsByName('hostids[]');
        vPopUp = document.getElementById('link_popup').value;
        if (hostIDs.length === 0) {
            alert('Please select at least one host!');
            return false;
        }

        if (!json.hasOwnProperty("link")) {
            json.link = [];
        }
        for (i = 0; i < hostIDs.length; i++) {
            json["link"].push({"hostid": hostIDs[i].value, "popup": vPopUp});
        }
        txJson.value = formatJSON();
    }
    function validateJSON() {
        try {
            //var txJson = document.getElementById('jsonResult');
            json = JSON.parse(txJson.value);
            txJson.value = formatJSON();
            if (json.hasOwnProperty("line")) {
                vReport = "\n - Lines: " + json.line.length;
                vCont = json.line.length;
            } else {
                vReport = "";
                vCont = 0;
            }
            if (json.hasOwnProperty("circle")) {
                vReport += "\n - Circles: " + json.circle.length;
                vCont += json.circle.length;
            }
            if (json.hasOwnProperty("link")) {
                vReport += "\n - Links: " + json.link.length;
                vCont += json.link.length;
            }
            if (vCont > 0) {
                vReport += "\nTotal of elements: " + vCont;
                alert('JSON code is VALID and have: ' + vReport);
            } else {
                alert('Dont found any element!');
            }
            //alert('VALID json!');
            return true;
        }
        catch (e) {
            alert('Invalid json');
            return false;
        }
    }
    function resetJSON() {
        try {
            var txJson = document.getElementById('jsonResult');
            txJson.value = '{}';
            json = JSON.parse(txJson.value);
            return true;
        }
        catch (e) {
            alert('Invalid json');
            return false;
        }
    }

</script>

<?php

commonModuleHeader($moduleName, $moduleTitle, true);

insert_show_color_picker_javascript();

//$subt = (new CTableInfo());

$leftCol = new CFormList();
$rightCol = new CFormList();
$addButton = (new CButton('btnAddCircle', _('Add')))->onClick('javascript:addCircle();');


$subTable = (new CTableInfo())->setHeader([ _zeT('Color'), _zeT('Size'), '']);
$subTable->addRow([ new CColor('circle_color', '6666FF', false)
    , (new CNumericBox('circle_size', 3000))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
    , $addButton
]);
$leftCol->addRow(_('Circle'), $subTable);

// Link
$addButton->onClick('javascript:addLine();');
$multiSelectHostData = selectedHosts($filter['hostids']);
$hostSelect = (new CMultiSelect([
    'name' => 'hostids[]', 'objectName' => 'hosts', 'data' => $multiSelectHostData,
    'popup' => [ 'parameters' => 'srctbl=hosts&dstfrm=zbx_filter&dstfld1=hostids_&srcfld1=hostid' . '&real_hosts=1&multiselect=1'
    ]]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH);

$addButton->onClick('javascript:addLink();');

//$leftCol->addRow(_('Link'), $subTable);
$leftCol->addRow(_('Link'), [$hostSelect]);
$leftCol->addRow(_('Link description'), [(new CTextBox('link_popup', 'Link Description')), SPACE, $addButton]);

// Right side ==================================================================
// Line
$subTable = (new CTableInfo())->setHeader(['Latitude', 'Longitude', 'Popup Description', '']);
$addButton->onClick('javascript:addLine();');
$subTable->addRow([
    (new CTextBox('line_lat', -15.77972))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
    , (new CTextBox('line_lon', -47.92972))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
    , (new CTextBox('line_popup', 'Link Description'))
    , $addButton
]);
$leftCol->addRow(_('Line'), $subTable);
// Multiline

$subTable2 = (new CTableInfo())->setHeader(['New configuration', '']);
$subTable2->addRow([ (new CTextArea('jsonResult', ''))->setWidth(ZBX_TEXTAREA_BIG_WIDTH)]);
$subTable2->addRow([ (new CButton('btnvalidate', _('Validate')))->onClick('javascript:validateJSON();'), (new CButton('btnReset', _('Reset')))->onClick('javascript:resetJSON();')]);

$rightCol->addRow($subTable2);

$table->addRow([$leftCol, $rightCol]);

/* * ***************************************************************************
 * Display Footer 
 * ************************************************************************** */
$form->addItem([ $table]);
$dashboard->addItem($form)->show();

