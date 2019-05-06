/*
* * Purpose: Javascript library for edit Geolocalization metadata of hosts
* * Adail Horst
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
      vReport += "\n" + msgValidJSONBotton + " " + vCont;
      alert(msgValidJSONTop + vReport);
    } else {
      alert(msgValidJSONNoElements);
    }
    //alert('VALID json!');
    return true;
  }
  catch (e) {
    alert(msgValidateInvalidJson);
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
    alert(msgValidateInvalidJson);
    return false;
  }
}

function formatJSON() {
  return JSON.stringify(json, null, 2).replace(/\\"/g, "\"").replace(/"\[/g, "[").replace(/\]"/g, "]");
}

function validColor(color) {
  if (!/(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test('#'+color)) {
    alert(msgInvalidColor); return false;
  }
  return true;
}
function validNumberRange(size, min, max, msg) {
  if (size < min || size > max) {
    alert(msg); return false;
  }
  return true;
}
function validPosition(pos) {
  var lngVal = /^-?((1?[0-7]?|[0-9]?)[0-9]|180)\.[0-9]{1,6}$/;
  return lngVal.test(pos);
}

function htmlPopUpControl(saveOnClick, deleteOnClick, newOnClick) {
  var deleteOnClick = typeof deleteOnClick !== 'undefined' ? deleteOnClick : 'deleteLayer()';
  return '<table><tr><td>'
  + '<input class="zbxe_button" type="button" id="updateBtn" value="'+zbxeTranslation['Update']+'" onclick="'+saveOnClick+';"/>'
  + '</td><tr></table>';
}

function linkPopUpOptions (e) {
  return {'popupDescription':description,'currentColor' :  color,
  'weight' : weight, 'dasharray' : dasharray};
}

function htmlLineParams() {
  return '<span><b>'+zbxeTranslation['Description']+'<b/></span><br/>'+
  '<textarea id="line_description" cols="48" rows="2"><popupDescription></textarea><br/>' +
  '<table class="list-table"><thead><tr><th><b>'+zbxeTranslation['Color']
  +'</b></th><th><b>'+zbxeTranslation['Width']
  +'</b></th><th><b>'+zbxeTranslation['Decorator']
  +'</b></th><th><b>'+zbxeTranslation['Opacity']
  +'</b></th><th></th></tr></thead>'+
  //'<currentColor>'
  '<tbody><tr><td>'+divColorPicker ('#580f0f',85, 'line_color')+'</td>'+
  '<td>'+zbxeEditInteger('line_weight', '<weight>', 35, {'maxlength': 2})+'</td>'+
  '<td>'+zbxeSelect('line_dasharray','<dasharray>',{'': 'Solid','20,15': 'Dashed'})+'</td>'+
  '<td>'+zbxeSelect('line_opacity','<opacity>',
  {'1': 'Solid','0.9': '90%','0.8': '80%','0.7': '70%','0.6': '60%',
  '0.5': '50%','0.4': '40%','0.3': '30%','0.2': '20%','0.1': '10%'})+
  '</td></tr></tbody></table>';
}

function htmlLinkParams() {
  return "<table><tr><td><b>Host</b></td><td></td></tr><tr><td><b>Coordenates</b></td><td></td></tr></table>" + htmlLineParams();
}
function htmlAddHostLink() {
  return '<table class="no-class" style="width: 100%;">'
  + '  <tr>'
  + '    <td style="width: 20%; text-align: right; vertical-align: middle;"><label>'
  + 'Host'
  + '    </label></td>'
  + '    <td style="width: 80%;">'
  + '      <input type="text" id="hostname" placeholder="Host" onkeyup="loadHosts(this);" style="width:100%;"/>'
  + '      <input type="hidden" id="hostid"> <input type="hidden" id="host_location_lat"> <input type="hidden" id="host_location_lon">'
  + '      <div id="hostid-detail" style="height: 20px; overflow-y: scroll; display: none;">oiiiiii</div>'
  + '      <div id="hostid-list" style="max-height: 100px; overflow-y: scroll; display: none;"></div>'
  + '    </td>'
  + '  </tr>'
  + '  <tr><td colspan="2"><input class="zbxe_button" type="button" id="addBtn" value="'+zbxeTranslation['Add']+'" onclick="addNewHost();"/></td></tr>'
  + '</table>'
  ;
}

function htmlButton(id, caption, onclick, className) {
  return '<input class="'
  + (typeof className !== 'undefined' ? className : 'zbxe_button')
  + '" type="button" id="'+id+'" value="'+caption+'" onclick="'+ onclick+';"/>';
}

function customPopUp(e, html,values) {
  options = {
    minWidth: 400,
    autoPan: true,
    closeButton: true,
    offset: new L.Point(0, 2),
    autoPanPadding: new L.Point(5, 5),
    className: ''
  };
  e.bindPopup(parse_html(html,values),options);
}

function htmlLineValues (layer) {
  return {'popupDescription':layer.zbxe.description,'currentColor' :  layer.zbxe.color.substring(1, 7),
  'weight' : layer.zbxe.weight,
  'dasharray' : layer.zbxe.dasharray, 'opacity' : layer.zbxe.opacity};
}

// From Zabbix source with adaptations
function show_color_picker(id, event) {
  if (!color_picker) {
    return;
  }

  curr_txt = document.getElementById(id);
  if (curr_txt.hasAttribute('disabled')) {
    return;
  }
  curr_lbl = document.getElementById('lbl_' + id);
  var pos = getPosition(curr_lbl);
  mapdiv = document.getElementById('mapid');
  color_picker.x = (pos.left < 0 ? getPosition(mapdiv).left : pos.left);
  color_picker.y = (pos.top < 0 ? getPosition(mapdiv).top : pos.top);

  color_picker.style.left = (color_picker.x + 20) + 'px';
  color_picker.style.top = color_picker.y + 'px';
  color_picker.style.display = 'block';

  addToOverlaysStack('color_picker', event.target, 'color_picker');
  overlayDialogueOnLoad(true, color_picker);
}

function addMarker(iconid, lat, lon, popup) {
  var marker =  L.marker([lat,lon ]  ,{ icon: zbxImage(iconid)  }).addTo(ZabGeomap);
  if (typeof popup !== 'undefined') {
    marker.bindPopup(popup);
  }
  return marker;
}

function deleteLayer(){
  ZabGeomap.removeLayer(currentElement);
  ZabGeomap.closePopup();
}

function addMarkerClick(iconid, lat, lon, popup) {
  var marker =  L.marker([lat,lon ]  ,{ icon: zbxImage(iconid)  }).addTo(everyzObj.map);
  if (typeof popup !== 'undefined') {
    marker.bindTooltip(popup);
  }
  return marker;
}

// ========================== Line functions ===================================

function updateLineOptions(currentElement){
  //console.log(currentElement.zbxe);
  document.getElementById('line_color').value = currentElement.zbxe.color;
  document.getElementById('line_description').value = currentElement.zbxe.popup;
  document.getElementById('line_weight').value = currentElement.zbxe.weight;
  document.getElementById('line_dasharray').value = currentElement.zbxe.dasharray;
  document.getElementById('line_opacity').value= currentElement.zbxe.opacity;
  everyzObj.dialog.open();
}

// ================================ Link functions =============================
function loadHosts(ele) {
  //console.log(ele);
  $.ajax({
    type: "POST",
    url: "jsrpc.php?type=11&method=multiselect.get&objectName=hosts&real_hosts=1&limit=21&search=",
    data:'search='+ele.value,
    beforeSend: function(){
      $("#hostname").css("background","#FFF url(local/app/everyz/images/zbxeLoaderIcon.gif) no-repeat");
      $("#hostname").css("background-position","right");
      $("#hostid-detail").hide();
    },
    success: function(obj) {
      everyzObj.dialog.open();
      $("#hostid-list").show();
      let options = "";
      Object.entries(JSON.parse(obj).result).forEach(([key, value]) => {
        if (value.id !== everyzObj.main.hostid) {
          options += '<li style="hover {background-color: yellow;}" onClick="selectHost('+value.id+',\''+value.name+'\');">'+value.name+'</li>';
        }
      });
      $("#hostid-list").html('<ul id="hostid-lists" style="cursor: pointer;">'+options+'</ul>');
      $("#hostname").css("background","#FFF");
    }
  });
}

function hostInventory (hostid) {
  $.ajax({
    type: "POST",
    url: "local/app/views/everyzjsrpc.php?type=11&method=host.inventory.get&real_hosts=1&limit=1",
    data:'hostid='+hostid,
    beforeSend: function(){
      $("#hostname").css("background","#FFF url(local/app/everyz/images/zbxeLoaderIcon.gif) no-repeat");
      $("#hostname").css("background-position","right");
      $("#hostid-detail").hide();
    },
    success: function(obj) {
      let JSONObj = JSON.parse(obj).result[0];
      $("#hostid-detail").html('<b>('+JSONObj.inventory.location_lat+','+JSONObj.inventory.location_lon+')</b>');
      $('#host_location_lat').val(JSONObj.inventory.location_lat);
      $('#host_location_lon').val(JSONObj.inventory.location_lon);
      $("#hostid-detail").show();
    }
  });
}

function selectHost (id, name) {
  $('#hostid').val(id);
  $('#hostname').val(name);
  $("#hostid-list").hide();
  hostInventory (id);
  $("#hostname").css("background","#FFF url(local/app/everyz/images/zbxe-ok.png) no-repeat");
  $("#hostname").css("background-position","right");
}

function addLinkHost(iconid, lat, lon, hostname, hostid, uid) {
  let hostMaker = addMarkerClick(iconid,lat,lon,hostname);
  hostMaker.zbxe = {type: 'marker', uid: uid, hostid: hostid};
  return hostMaker;
}

function addNewHost() {
  let hostMaker = addLinkHost (iconid, $('#host_location_lat').val(),$('#host_location_lon').val(), $('#hostname').val(), $('#hostid').val(), generateUID());
  let hostLink = addLinkToHost([new L.LatLng(everyzObj.main.lat, everyzObj.main.lon), new L.LatLng($('#host_location_lat').val(),$('#host_location_lon').val())],hostMaker,true);
  everyzObj.map.fitBounds(hostLink.getBounds());
  everyzObj.dialog.close();
}

// ============================ JSON Functions =================================
function buildJSON(){
  var layers = [];
  everyzObj.json = { everyzVersion: '2' };
  everyzObj.map.eachLayer(function(layer) {
    if (typeof layer.zbxe !== 'undefined' && layer.zbxe.type !== 'marker') {
      if (typeof everyzObj.json[layer.zbxe.type] == 'undefined') {
        everyzObj.json[layer.zbxe.type] = [];
      }
      let element = {coordenates: layer._latlngs || layer._latlng, width: layer.zbxe.weight, color: layer.zbxe.color
        , popup: layer.zbxe.description, uid: layer.zbxe.uid, dasharray: layer.zbxe.dasharray, opacity: layer.zbxe.opacity
      };
      switch (layer.zbxe.type) {
        case 'link':
        element.hostid = layer.zbxe.hostid;
        break;
        case 'circle':
        element.size = parseFloat(layer._mRadius).toFixed(6);
        console.clear; console.log(element); console.log(layer);
        default:
      }
      everyzObj.json[layer.zbxe.type].push(element);
      layers.push(layer);
    }
  });
  $('#newJSON').val(JSON.stringify(everyzObj.json, null, 2));
  metadataCount('polyline','#lineCount',everyzObj.json);
  metadataCount('link','#linkCount',everyzObj.json);
  metadataCount('circle','#circleCount',everyzObj.json);
  metadataCount('polygon','#polygonCount',everyzObj.json);
}


function metadataCount(type,element, json) {
  if (typeof json[type] !== 'undefined') {
    $(element).text(json[type].length);
  } else {
    $(element).text(0);
  }
}

function updateHostInventory() {
  console.log(hostData);
  $.ajax({
    type: "POST",
    url: "local/app/views/everyzjsrpc.php?type=11&method=host.inventory.update",
    data: { hostid : hostData['hostid'] , 'inventory.notes': $('#newJSON').val() },
    beforeSend: function(){
      $("#controlJSON").css("background","#FFF url(local/app/everyz/images/zbxeLoaderIcon.gif) no-repeat");
      $("#controlJSON").css("background-position","right");
      //      $("#hostid-detail").hide();
    },
    success: function(obj) {
      let JSONObj = JSON.parse(obj).result[0];
      $("#controlJSON").css("background","#FFF");
      $("#controlJSON").append(
        //  zbxeTranslation['Host updated']
        '<output class="msg-good" role="contentinfo" aria-label="Success message"><span>Host updated</span><button type="button" class="overlay-close-btn" '
        +'onclick="jQuery(this).closest(\'.msg-good\').remove();" title="Close"></button></output>'
      );
      //      $("#hostid-detail").show();
    }
  });
}
