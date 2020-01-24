<?php
/*
* * Purpose: Generate ZabGeo Metadata
* * Adail Horst - http://www.everyz.org
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
/* ****************************************************************************
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
addFilterParameter("updateHost", T_ZBX_INT);
addFilterParameter("sourceHostID", T_ZBX_INT, 0, false, false, false);
addFilterParameter("jsonResult", T_ZBX_STR, "", false, false, false);
addIdFilterParameter("hostids");
// Field validation
check_fields($fields);
/* * ***************************************************************************
* Access Control
* ************************************************************************** */
zbxeCheckUserLevel(USER_TYPE_ZABBIX_ADMIN);
checkAccessHost('hostids');
checkAccessHost('sourceHostID', true);


/* * ***************************************************************************
* Module Functions
* ************************************************************************** */
zbxeJSLoad( [
  'everyzD3Functions.js',  'everyz-zbxe-geolocation.static.js', 'everyz-zbxe-geometadata.static.js',
  'leaflet/leaflet.js',
  'leaflet/leaflet.lineextremities.js', 'leaflet/leaflet-control-credits.js',
  'leaflet/leaflet-control-credits-src.js', 'leaflet/leaflet.oms.min.js', 'leaflet/leaflet.draw.js',
  'leaflet/leaflet-sidebar.js','leaflet/leaflet.toolbar-src.js', 'leaflet/leaflet.draw-toolbar.js',
  'leaflet/ColorPicker.js', 'leaflet/everyzLineOptions.js'
  , 'leaflet/leaflet.Control.Custom.js', 'leaflet/Leaflet-Dialog.js'
]);
zbxeCSSLoad(['font-awesome.min.css', 'leaflet.css','leaflet-control-credits.css', 'L.Icon.Pulse.css','leaflet-draw.css'
, 'leaflet-sidebar.css', 'leaflet-toolbar.css', 'leaflet-draw-toolbar.css', 'Leaflet.Dialog.css', 'zbxe-geometadata.css'
]);

function addTab($key, $desc)
{
  global $dataTab, $leftCol;
  $dataTab->addTab($key, _zeT($desc), $leftCol);
  $leftCol = new CFormList();
}

function sampleHostSelect(){
  global $multiSelectHostData, $hostSelect, $leftCol, $table, $dataTab, $filter;
  $dataTab = new CTabView();
  $multiSelectHostData = selectedHosts($filter['hostids']);
  $hostSelect = multiSelectHosts( $multiSelectHostData);
  $leftCol = new CFormList();
  $leftCol->addRow(_('Host to link'), [$hostSelect]);
  $table->addRow([$leftCol]);
  $table->setFooter(makeFormFooter(new CSubmit('update', _('Update'))));
}

/* * ***************************************************************************
* Get Data
* ************************************************************************** */
$inventoryFields = ["notes", "location_lat", "location_lon"];
$hostData = API::Host()->get([
  'output' => ['hostid', 'name'],
  'selectInventory' => $inventoryFields,
  'withInventory' => true,
  'hostids' => $filter["sourceHostID"]
]);
$hostData = $hostData[0];


/* * ***************************************************************************
* Display
* ************************************************************************** */
?>
<script language="JavaScript">

<?php
echo jsTranslations(['Update','Description','Width','Decorator','Color','Delete','Host','Opacity',
"It is not possible link host to yourself!", 'Please select at least one host!',
'Invalid latitude!', 'Invalid longitude!','JSON code is VALID and have\:',
'Total of elements:', 'Do not found any element!', 'Invalid color!',
'Invalid size!', 'Invalid width!','Invalid json!',
"No matches found","More matches found...","type here to search","new","Select"
, "New host link", "Configuration", "Add", "Revert","Host updated"
]);
?>

currentHost = <?php echo $filter["sourceHostID"];?>;

function validateHostJSON() {
  try {
    // If JSON configuration is invalid removeIT
    json = JSON.parse(<?php echo json_encode(($hostData['inventory']['notes'] == "" ? "{}" : $hostData['inventory']['notes'])); ?>);
    txJson = document.getElementById('jsonResult');
    txJson.value = formatJSON();
  }
  catch (e) {
    alert(zbxeTranslation['Invalid json!']);
    json = JSON.parse('{}');
    return false;
  }
}

window.name = "everyz_popup";

function addCircle() {
  var vColor = document.getElementById('circle_color').value;
  var vSize = parseInt(document.getElementById('circle_size').value);
  if (!validColor(vColor)) { return false; }
  if (!validNumberRange(vSize,1,100000,zbxeTranslation['Invalid size!'])) { return false; }

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
  vWidth = parseInt(document.getElementById('line_width').value);
  vColor = document.getElementById('line_color').value;
  if (!validColor(vColor)) { return false; }
  if (!validNumberRange(vWidth,1,10,zbxeTranslation['Invalid width!'])) { return false; }

  // Validation
  if (!validPosition(vLat)) {
    alert(zbxeTranslation['Invalid latitude!']);
    return false;
  }
  if (!validPosition(vLon)) {
    alert(zbxeTranslation['Invalid longitude!']);
    return false;
  }
  if (!json.hasOwnProperty("line")) {
    json.line = [];
  }
  json["line"].push({"lat": vLat, "lon": vLon, "width": vWidth, "color": vColor, "popup": vPopUp});
  txJson.value = formatJSON();
}

function addLink() {
  var hostIDs = document.getElementsByName('hostids[]');
  vPopUp = document.getElementById('link_popup').value;
  vWidth = document.getElementById('link_width').value;
  vColor = document.getElementById('link_color').value;
  if (!validColor(vColor)) { return false; }
  if (!validNumberRange(vWidth,1,10,zbxeTranslation['Invalid width!'])) { return false; }

  if (hostIDs.length === 0) {
    alert(zbxeTranslation['Please select at least one host!']);
    return false;
  } else {
    for (i = 0; i < hostIDs.length; i++) {
      //alert("["+hostIDs[i].value + "][" + currentHost + "]"+msgSelfLink);
      if (hostIDs[i].value == currentHost) {
        alert(zbxeTranslation["It is not possible link host to yourself!"]);
        return false;
      }
    }
  }

  if (!json.hasOwnProperty("link")) {
    json.link = [];
  }
  for (i = 0; i < hostIDs.length; i++) {
    json["link"].push({"hostid": hostIDs[i].value, "width": vWidth, "color": vColor, "popup": vPopUp});
  }
  txJson.value = formatJSON();
}


</script>

<?php

commonModuleHeader($moduleName, $moduleTitle, true);

$table->addRow([(new CDiv())
->setAttribute('id', "mapid")
// ->setAttribute('style', "width: 100%; height: 100%; min-height: 730px;")
]);

/* * ***************************************************************************
* Display Footer
* ************************************************************************** */
$form->addItem([$table]);
$dashboard->addItem($form)->show();
?>
<script language="JavaScript">

/*

function onPolyLineClick(e) {
var layer = e.target;
var popup = layer.getPopup();
var html = parse_html(templatePopUp['polyline'],  htmlLineValues (layer));
popup.setContent(html);
updateLineOptions(layer);
}


function newPolyLine() {
new L.Draw.Polyline(everyzObj.map, everyzObj.drawControl.options.polyline).enable();
}

function addEditToolBar(){
var sidebar = L.control.sidebar().addTo(everyzObj.map);

sidebar.addPanel({
id:   'settings',
tab:  '<i class="fa fa-gear"></i>',
title: 'Settings',
pane: templatePopUp['mainhost']
});
sidebar.addPanel({
id: 'cnf-link',
tab: '<i class="fa fa-link icon-green"></i>',
title: 'Link',
pane: templatePopUp['marker']
});
}
*/


function onLinkClick(e) {
  var layer = e.target;
  var html = parse_html(templatePopUp['marker'], htmlLineValues (layer));
  popup.setContent( html );
  zbxeInitHostSelect('hostids');
  updateLineOptions(layer);
}

<?php
$iconDefault = zbxeConfigValue('geo_default_poi', 0, "zbxe_default_icon");
echo "\niconid=".$iconDefault.";";
echo "\nicontarget=".(zbxeImageId('zbxe_default_icon_ok') > 0 ? zbxeImageId('zbxe_default_icon_ok') : $iconDefault).";";
echo "\nhostData=".json_encode($hostData).";";
?>

// Popup Templates =============================================================
var templatePopUp = [];

function drawCreated(e) {
  everyzObj.currentElement = e.layer;
  var type = e.layerType;
  zbxeConsole('New item: '+type);
  everyzObj.featureGroup.addLayer(e.layer);
  switch(type) {
    case 'polyline':
    case 'circle':
    case 'polygon':
    initCurrentElement(e.layer);
    addEditPopUp(e.layer);
    break;
    case 'marker':
    initCurrentElement(currentElement);
    parseValues = htmlLineValues(currentElement);
    customPopUp(currentElement, parse_html(templatePopUp['newMarker'],parseValues));
    currentElement.on('click', onLinkClick);
    updateLineOptions(currentElement);
    zbxeInitHostSelect('hostids');
    break;
    default:
    zbxeConsole('Unknow type: '+type);
  };
  everyzObj.currentElement.zbxe.type = type;
};

/*
*/


function init() {
  $ = jQuery;
  $("<style type='text/css'> li { background-color: black;} li:nth-child(odd) { background-color: #f1f1f1;} li:nth-child(even) { background-color: white;} </style>").appendTo("head");

  // $("<style type='text/css'>body {  min-width: 730px;}</style>").appendTo("head");

  addDefaultMapTiles();

  everyzObj.map = L.map('mapid', everyzObj.layerList).setView([hostData['inventory']['location_lat'],hostData['inventory']['location_lon']], 15,
  editActions = [
    LeafletToolbar.EditAction.Popup.Edit,
    LeafletToolbar.EditAction.Popup.Delete
    , L.everyzLineOptions
  ]);
  L.control.layers(everyzObj.baseMaps).addTo(everyzObj.map);
  everyzObj.layerList[0].addTo(everyzObj.map);

  everyzObj.drawControl = new L.Control.Draw({    draw : {
    position : 'topleft',
    polygon : true,
    polyline : true,
    rectangle : false,
    circle : true,
    circlemarker: false,
    marker: false
  },
  edit : false}).addTo(everyzObj.map);

  everyzObj.map.addControl(everyzObj.drawControl);
  everyzObj.map.on('draw:created', drawCreated);
  everyzObj.featureGroup = L.featureGroup().addTo(everyzObj.map);

  L.control.custom({
    position: 'topleft',
    content :
    addToolButton ('zbxeNewLink','fa-link',zbxeTranslation['New host link'])+"<br>"+
    addToolButton('zbxeConfig','fa-gear',zbxeTranslation['Configuration']),
    classes : 'btn-group-vertical btn-group-sm leaflet-touch leaflet-bar',
    style   :
    {
      padding: '0px 0 0 0',
      cursor: 'pointer',
      width: '30px',
      height: '60px',
      'background-clip': 'padding-box'
      //'background-color': 'white'
    },
    datas   :
    {
      'foo': 'bar',
    },
    events:
    {
      click: function(data)
      {
        //console.log(['aqui',data,data.srcElement.id]);
        //let sourceElement = data.explicitOriginalTarget.id;
        let sourceElement = data.srcElement.id;
        switch(sourceElement) {
          case 'zbxeNewLink':
          everyzObj.dialog.options.size = [420,220];
          everyzObj.dialog.setContent(templatePopUp['newMarker']);
          $("#hostname").focus();
          break;
          case 'zbxeConfig':
          everyzObj.dialog.options.size = [420,360];
          everyzObj.dialog.setContent(templatePopUp['mainhost']);
          buildJSON();
          //$("#hostname").focus();
          break;
          default:
          zbxeConsole('Unknow source: '+sourceElement);
        };
      }
    }
  })
  .addTo(everyzObj.map);
  $(document).keypress(
    function(event){
      if (event.which == '13') {
        event.preventDefault();
      }
    }
  );

  everyzObj.dialog = L.control.dialog({enableMove: false}).setContent(templatePopUp['mainhost']).addTo(everyzObj.map);
  everyzObj.dialog.setLocation([ 2, 40 ]);
  everyzObj.main = { lat: hostData['inventory']['location_lat'], lon: hostData['inventory']['location_lon'], hostid: hostData['hostid'] ,hostname: hostData['name'] };
  everyzObj.layerGroup = L.layerGroup();
  everyzObj.layerGroup.addTo(everyzObj.map);

  var hostMaker = addMarkerClick(iconid,hostData['inventory']['location_lat'],hostData['inventory']['location_lon'],hostData['name'] + ' - Main');
  everyzObj.map.setView(hostMaker.getLatLng(),10);

  // Load current objects ======================================================
  if (isValidJSON(hostData.inventory.notes)) {
    everyzObj.json = JSON.parse(hostData.inventory.notes);
    if (typeof everyzObj.json['polygon'] !== 'undefined') {
      everyzObj.json['polygon'].forEach(function(item) {
        let element = L.polygon(item.coordenates);
        element.addTo(everyzObj.map);
        addEditPopUp(element);
        initCurrentElement(element,'polygon',itemOptions (item));
      });
    }
    if (typeof everyzObj.json['circle'] !== 'undefined') {
      everyzObj.json['circle'].forEach(function(item) {
        let element = L.circle([item.coordenates.lat,item.coordenates.lng],{color: item.color, dasharray: item.dasharray, popup: item.popup, radius: item.size});
        element.addTo(everyzObj.map);
        addEditPopUp(element);
        initCurrentElement(element,'circle',itemOptions (item));
      });
    }
    if (typeof everyzObj.json['polyline'] !== 'undefined') {
      everyzObj.json['polyline'].forEach(function(item) {
        let element = L.polyline(item.coordenates);
        element.addTo(everyzObj.map);
        addEditPopUp(element);
        initCurrentElement(element,'polyline',itemOptions (item));
      });
    }
    if (typeof everyzObj.json['link'] !== 'undefined') {
      everyzObj.json['link'].forEach(function(item) {
        // Todo: Falta os nomes de host
        let lastCoordenate = item.coordenates[item.coordenates.length-1];
        let hostMaker = addLinkHost (iconid, lastCoordenate.lat, lastCoordenate.lng, "", item.hostid, item.uid);
        let hostLink = addLinkToHost(item.coordenates,hostMaker,true);
        updateLinkConfig(hostLink,itemOptions (item));
      });
    }
  }

  jQuery(window).ready(function() {
    initMessages({});
  });
  templatePopUp['mainhost'] = '<?php echo zbxeLoadTemplate('templates/geolocation.hostCurrent.htm',$hostData) ?>'
  + '<table width="100%"><tr><td>'+htmlButton('updateJSON', zbxeTranslation['Update'], "updateHostInventory()","zbxe_button")+'</td><td id="controlJSON"></td></tr></table>'
  //+ htmlButton('revertJSON', zbxeTranslation['Revert'], "revertJSON()","zbxe_button_warning")
  ;
  templatePopUp['polyline'] = htmlLineParams()+htmlPopUpControl('savePolyLine()');
  templatePopUp['marker'] = htmlLinkParams()+htmlPopUpControl('saveLink()');
  templatePopUp['newMarker'] = htmlAddHostLink();
}

function savePolyLine() {
  let ele = everyzObj.currentElement;
  console.log('init - save polyline ['+ele.zbxe.uid+']');
  ele.zbxe.color = document.getElementById('line_color').value;
  ele.zbxe.description = document.getElementById('line_description').value;
  ele.zbxe.weight = document.getElementById('line_weight').value;
  ele.zbxe.dasharray = document.getElementById('line_dasharray').value;
  ele.zbxe.opacity = document.getElementById('line_opacity').value;
  ele.setStyle({
    color: ele.zbxe.color,
    weight: ele.zbxe.weight,
    dashArray: ele.zbxe.dasharray,
    opacity: ele.zbxe.opacity
  });
  everyzObj.dialog.close();
}

jQuery(document).ready(setTimeout(init(),2000));
</script>
<?php
//
