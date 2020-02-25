<?php
/*
* * Purpose: Geolocalization of hosts - Javascript
* * Adail Horst / Aristoteles Araujo.
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
?>
<script type="text/javascript">
geoHostWord = "<?php echo _('Host'); ?>";
geoTitleMetadata = "<?php echo _('Edit host metadata'); ?>";
geoTitleLatest = "<?php echo _('Latest Data'); ?>";
geoTitleTriggers = "<?php echo _('Status of triggers'); ?>";
geoPopUpTemplate = "<?php echo "$popupHost"; ?>";
severityColors = [<?php
for ($i = 0; $i < 6; $i++) {
  echo($i == 0 ? "" : ","). "'#".getSeverityColor($i, [$config])."'";
}
?>];

<?php
$easterMode = hasRequest('showteam') || count($hostData) == 0;
echo "var configEveryz = {};\n";
if ($easterMode) { 
  echo "var setViewLat = 24.43419;";
  echo "var setViewLong = -28.125;";
  echo "var setViewZoom = 3;";
  echo "var showCircles = 1;";
  echo "var showLines = 1;";
  echo "var easterEggMode=1;";
  echo "var easterEggInfo='"
  . 'Você está vendo os créditos aos principais tradutores e desenvolvedores do EveryZ.<br>'
  . '<b>Clique aqui</b> e conheça um pouco mais sobre o EveryZ e como utiliza-lo melhor!'
  . "';";
  echo "var eeCont = 0;";
} else {
  echo "var setViewLat = " . $filter['centerLat'] . ";";
  echo "var setViewLong = " . $filter['centerLong'] . ";";
  echo "var setViewZoom = " . getRequest2("zoomLevel", 19) . ";";
  echo "var showCircles = " . (in_array($filter["layers"], [3, 99]) ? 1 : 0) . ";";
  echo "var showLines = " . (in_array($filter["layers"], [2, 99]) ? 1 : 0) . ";";
  // echo "console.log(getUrlVars()['refresh']);";
  echo "configEveryz.refresh = parseInt(getUrlVars()['refresh']) || 0; ";
  // echo "configEveryz.refresh = " . (array_key_exists("refresh",$filter) ? $filter["refresh"] : 0) . ";";
  echo "console.info(`EveryZ - Automatic Refresh Interval: \${configEveryz.refresh}. `);";
  echo "var easterEggMode=0;";
}
?>


//Define area for Map (setup this data in database ZabbixExtras)
zbxeConfigureDivMap('mapid');

addDefaultMapTiles();
everyzObj.map = L.map('mapid', everyzObj.layerList).setView([setViewLat, setViewLong], setViewZoom);
L.control.layers(everyzObj.baseMaps).addTo(everyzObj.map);
everyzObj.layerList[<?php echo $filter['map'];?>].addTo(everyzObj.map);

// Auto-center map when show popup
everyzObj.map.on('popupopen', function(e) {
    var px = everyzObj.map.project(e.target._popup._latlng); 
    px.y -= e.target._popup._container.clientHeight/2; 
    everyzObj.map.panTo(everyzObj.map.unproject(px),{animate: true}); 
});

//Create layerGroup Circle
var ZabGeocircle = new L.LayerGroup();
//Create layerGroup Lines
var ZabGeolines = new L.LayerGroup();
//Create layerGroup Alert
var ZabGeoalert = new L.LayerGroup();

var mbToken = '<?php echo zbxeConfigValue('geo_token') ?>';
//var baseMaps = {};
var hostsData = <?php echo json_encode($hostData); ?>;
var invalidHosts = <?php echo json_encode($invalidHosts); ?>;

//console.log(['Host Data',hostsData]);
// ------------------- OverLapping Layer ---------------------------------------
var oms = new OverlappingMarkerSpiderfier(everyzObj.map);

var popup = new L.Popup();
oms.addListener('click', function (marker) {
  popup.setContent(marker.desc);
  popup.setLatLng(marker.getLatLng());
  everyzObj.map.openPopup(popup);
});

// ------------------- OverLapping Layer end -----------------------------------

everyzObj.map.on('contextmenu', onMapClick);

// DIV for invalid hosts in groups
if (invalidHosts.length > 0) {
  invalidHosts.forEach(function(host) {
    console.warn(`EveryZ - Host with problem for geolocation ${host.name} / ${host.error}`)
  });
}

// Adiciona o logotipo
if (location.search.split('hidetitle=1')[1] !== undefined || easterEggMode === 1) {
  L.controlCredits({
    image: "zbxe-logo.php",
    link: "http://www.everyz.org/",
    text: '<div id="everyzTopMenuLogo"></div>',
    height: "25",
    width: "<?php echo zbxeConfigValue("company_logo_width", 0, 120); ?>"
  }).addTo(everyzObj.map).setPosition('topright');
}
<?php

function bigSeverity($host)
{
  $bigPriority = 0;
  if (isset($host["events"])) {
    $eventList = "";
    foreach ($host["events"] as $key => $value) {
      $bigPriority = ($bigPriority > $value["priority"] ? $bigPriority : $value["priority"]);
    }
  }
  return $bigPriority;
}

function showEvents($host)
{
  global $config;
  if (isset($host["events"])) {
    $eventList = "";
    foreach ($host["events"] as $key => $value) {
      $eventList .= "<li style='background: #" . getSeverityColor($value["priority"], [$config])
      . "; list-style:square;'><a class='everyzGEOLink' href='tr_events.php?triggerid="
      . $value["triggerid"] . "&eventid=" . $value["eventid"] . "'"
      . (strlen($value["description"]) > 30 ? " title='" . $value["description"] . "' " : "") . "> "
      . (strlen($value["description"]) > 30 ? substr($value["description"], 0, 30) . "..." : $value["description"])
      . "</a></li>";
    }
    return quotestr('<hr width="99%" color="gray"><ul>' . $eventList . '</ul>');
  } else {
    return "''";
  }
}

// Array com os índices de imagens de erro
echo "\n errorImages = [";
for ($i = 0; $i < 6; $i++) {
  echo($i == 0 ? "" : ","), zbxeImageId('zbxe_icon_error_' . $i);
}
echo "]; ";
// Cria os hosts no mapa
$linesPackage = "";

if ($easterMode) { // Clean the filter parameters
  ?>
  showEasterEgg();
  <?php
} else {
  if (file_exists("local/app/everyz/include/geolocation.buttons.change.js.php")) {
    require_once "local/app/everyz/include/geolocation.buttons.change.js.php"; ?>
    zbxeConsole('Extra buttons loaded.');
    <?php
  } else {?>
    function extraButtons(host) {
      return "";
    }
    zbxeConsole('No extra buttons.');
    <?php
  }
}
?>
// Sort array
function sortByKey(array, key) {
  return array.sort(function(a, b) {
    var x = a[key]; var y = b[key];
    return ((x < y) ? -1 : ((x > y) ? 1 : 0));
  });
};
function reverseSortByKey(array, key) {
  return array.sort(function(a, b) {
    var x = a[key]; var y = b[key];
    return ((x < y) ? -1 : ((x > y) ? 0 : 1));
  });
};
sortByKey(hostsData,'maxPriority');
//reverseSortByKey(hostsData,'notes');

function addLiveElement (host, element) {
  if (element.zbxe.hasOwnProperty('trigger') && parseInt(element.zbxe.trigger) || 0 > 0) {
    liveHosts.count++;
    zbxeConsole(`Live Link ${host.name} - TriggerID: ${element.zbxe.trigger}`);
    element.zbxe.originalColor = element.zbxe.color;
    // console.log(element.zbxe);
    liveHosts.hosts.push(element);
    liveHosts.triggerIDs.push(element.zbxe.trigger);
  }
}

// Hosts with "live elements"
var liveHosts = {count: 0, hosts: [], triggerIDs: []};
hostsData.forEach(function(host) {
  if (!isNaN(parseFloat(host.location_lat)) && !isNaN(parseFloat(host.location_lon))) {
    // @todo Check if coordenates are in DMS and convert to DD
    // // https://stackoverflow.com/questions/1140189/converting-latitude-and-longitude-to-decimal-values
    // @todo Check if exist hosts without coordenates
    let validJSON = isValidJSON(host['notes']);
    if (validJSON) {
      let notesJSON = JSON.parse(host['notes']);
      if (typeof notesJSON['polygon'] !== 'undefined') {
        notesJSON['polygon'].forEach(function(item) {
          var element = L.polygon(item.coordenates);
          element.addTo(everyzObj.map);
          initCurrentElement(element,'polygon',itemOptions (item));
        });
      }
      if (typeof notesJSON['circle'] !== 'undefined') {
        notesJSON['circle'].forEach(function(item) {
          var element = L.circle([item.coordenates.lat,item.coordenates.lng],{color: item.color, dasharray: item.dasharray, popup: item.popup, radius: item.size});
          element.addTo(everyzObj.map);
          initCurrentElement(element,'circle',itemOptions (item));
        });
      }
      if (typeof notesJSON['polyline'] !== 'undefined') {
        notesJSON['polyline'].forEach(function(item) {
          var element = L.polyline(item.coordenates);
          element.addTo(everyzObj.map);
          initCurrentElement(element,'polyline',itemOptions (item));
          addLiveElement(host,element);
        });
      }
      if (typeof notesJSON['link'] !== 'undefined') {
        notesJSON['link'].forEach(function(item) {
          let lastCoordenate = item.coordenates[item.coordenates.length-1];
          let element = addLinkToHost(item.coordenates,{zbxe: {hostid: host.id}}, false);
          updateLinkConfig(element,itemOptions (item));
          addInfoPopup(element);
          addLiveElement(host,element);
          // console.log(item);

        });
      }
    } else if (host.notes != "") {
      console.warn(`EveryZ - Host with inventory note field invalid: "${host.name}". Current value: ${host.notes}`);
    }
    let hostevents = "";
    host['events'].forEach(function(item) {
      hostevents += "<li style='background: " + severityColors[item.priority] + "; list-style:square;'><a class='everyzGEOLink' href='tr_events.php?triggerid="
      + item.triggerid + "&eventid=" + item.itemid+ "'"
      + (item.description.length > 30 ? " title='" + item.description + "' " : "") + "> "
      + (item.description.length > 30 ? item.description.substring(0, 30) + "..." : item.description)
      + "</a></li>";
    });
    if (hostevents !== "") {
      hostevents = '<hr width="99%" color="gray"><ul>' +hostevents+ '</ul>';
    }
    // @todo - Add hosts with events by last
    if (host.maxPriority > 0) {
      // Add host with incident
      addErrorHost(
        host.location_lat,host.location_lon,errorImages[host.maxPriority]
        ,popupHost(host.id, host.name, hostevents, host.conn,extraButtons(host))
        , host.maxPriority
      );
      //      console.log(host);
    } else {
      // Add host without incident
      var realTimeHost = addHost(
        host.location_lat,host.location_lon,host.iconid
        ,popupHost(host.id, host.name, hostevents, host.conn,extraButtons(host))
      );
      
    }
  } else {
    // Host com coordenadas invalidas ou sem coordenadas
    console.log (['Host with invalid coordenates',host.id, host.name, host.location_lat,host.location_lon]);
  }
});

function loadLiveLinks() {
  jQuery.ajax({
    type: "POST",
    url: "everyzjsrpc.php?type=11&method=geoevents.get",
    data: {triggerids: liveHosts.triggerIDs},
    success: function(obj) {
      let JSONObj = JSON.parse(obj).result;
      let tmpHost = 0;
      let newColor = 0;
      JSONObj.forEach(function(item) {
        for(let i = 0; i < liveHosts.hosts.length; i++){
          tmpHost = liveHosts.hosts[i];
          if (parseInt(tmpHost.zbxe.trigger) == item.triggerid) {
            newColor = (item.value == 1 ? severityColors[item.priority] : tmpHost.zbxe.originalColor)
            tmpHost.zbxe.color = newColor;
            tmpHost.setStyle({ color: newColor, });
          }
        }
      })
    }
  });
}

// Enable live check in selected hosts
zbxeConsole(liveHosts.count)
if (liveHosts.count > 0 && configEveryz.refresh > 0) {
  // console.log(['Automatic refresh enabled!',liveHosts]);
  console.info(`Automatic refresh every ${configEveryz.refresh} seconds.`);
  // refreshData = 
  loadLiveLinks();
  setInterval(function() { loadLiveLinks(); },  configEveryz.refresh*1000);
}

//Add Scale in maps
L.control.scale().addTo(everyzObj.map);
function addTileLayer(name) {
  return L.tileLayer(mbUrl, {id: 'mapbox.' + name, attribution: mbAttr});
}

//Active layer Alert
everyzObj.map.addLayer(ZabGeoalert);

//everyzObj.map.fitBounds(oms.getBounds());
//Add lines between hosts
var lineHosts = {
  "type": "FeatureCollection",
  "features": [
    <?php echo $linesPackage; ?>
  ]
};
var myStyle = {
  "color": "#ff7800",
  "weight": 2,
  "opacity": 0.5
};
function onEachFeature(feature, layer) {
  var popupContent = "";
  if (feature.properties && feature.properties.popupContent) {
    popupContent += feature.properties.popupContent;
  }
  layer.bindPopup(popupContent);
}


L.geoJSON(lineHosts, {
  //style: myStyle,
  onEachFeature: onEachFeature
}).addTo(ZabGeolines);


</script>
