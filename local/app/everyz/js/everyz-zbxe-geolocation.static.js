/*
 * * Purpose: Javascript library for Geolocalization of hosts
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

// Default values for new objects ============================================
var everyzObj = {
  default: { polyline: { color: "#FFCC00" } },
  json: { everyzVersion: "2" },
  currentElement: [],
  baseMaps: {},
  layerList: []
};

function zbxeConsole(msg) {
  console.log("EveryZ - " + msg);
}

// function drawControler() {
//   var toolbar = L.Toolbar();
//   toolbar.addToolbar(map);
// }
function addDefaultMapTiles() {
  addMapTile(
    "OSM",
    "http://{s}.tile.osm.org/{z}/{x}/{y}{r}.png",
    "© OpenStreetMap contributors"
  );
  addMapTile(
    "OpenTopo",
    "http://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",
    'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
    17
  );

  addMapTile(
    "Esri.WorldGrayCanvas",
    "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}",
    "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ"
  );

  addMapTile(
    "OpenStreet_Grayscale",
    "http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png",
    '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    18
  );
  addMapTile(
    "Stamen.Toner",
    "https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}{r}.{ext}",
    'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    20,
    "png"
  );

  addMapTile(
    "CartoDB_DarkMatter",
    "http://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png",
    '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
    19
  );

  addMapTile(
    "Esri.WorldTopo",
    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",
    "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community"
  );
  addMapTile(
    "Esri.WorldImagery",
    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
    "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community"
  );

  // Google URL provided by Roberto almeida - thanks !
  addMapTile(
    "Google.Street",
    "http://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&s=Ga",
    "Google - Street"
  );
  addMapTile(
    "Google.Satelite",
    "http://mt0.google.com/vt/lyrs=s&hl=en&x={x}&y={y}&z={z}&s=Ga",
    "Google - Satelite"
  );
  addMapTile(
    "Google.Mix",
    "http://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}&s=Ga",
    "Google - Mix"
  );
}
/**
 * Add a tile to a zabgeo
 * @param  {string} description
 * @param  {string} url
 * @param  {string} attribution
 * @param  {integer} maxZoom
 * @param  {string} ext
 */
function addMapTile(description, url, attribution, maxZoom, ext) {
  maxZoom = maxZoom || 20;
  ext = ext || "";
  everyzObj.layerList.push(
    L.tileLayer(url, {
      attribution: attribution + " | EveryZ 2",
      maxZoom: maxZoom,
      ext: ext
    })
  );
  everyzObj.baseMaps[description] =
    everyzObj.layerList[everyzObj.layerList.length - 1];
}

function dynamicPopUp(e) {
  e.on("mouseover", function(e) {
    this.openPopup();
  });
  e.on("mouseout", function(e) {
    this.closePopup();
  });
}

function addHost(lat, lon, hostid, popUpContent) {
  let marker = L.marker([lat, lon], {
    icon: zbxImage(hostid)
  })
    .addTo(everyzObj.map)
    .bindTooltip(popUpContent, {
      className: "zbxe-label",
      offset: [0, 0]
    });
  marker.desc = popUpContent;
  oms.addMarker(marker);
  return marker;
}

function addErrorHost(lat, lon, hostid, popUpContent, severityLevel) {
  var fillColor = severityColors[severityLevel];
  var pulsingIcon = L.icon.pulse({
    iconSize: [14, 14],
    color: fillColor,
    fillColor: fillColor
  });
  let marker = L.marker([lat, lon], {
    icon: pulsingIcon
  })
    .addTo(everyzObj.map)
    .bindPopup(popUpContent, {
      className: "zbxe-label",
      offset: [0, 0],
      maxWidth: 500
    });
  marker.desc = popUpContent;
  oms.addMarker(marker);
}

/**
 * Open metadata editor
 * @param  {integer} hostid
 */
function editHostMetadata(hostid) {
  zbxePopUp(
    `everyz.php?action=zbxe-geometadata&hidetitle=1&sourceHostID=${hostid}`
  );
}

/**
 * Show latest data for selected host
 * @param  {integer} hostid
 */
function hostLatest(hostid) {
  zbxePopUp(
    `latest.php?fullscreen=0&hostids[]=${hostid}&application=&select=&show_without_data=1&fullscreen=1&filter_set=Filter`
  );
}

/**
 * Show recent problems for selected host
 * @param  {integer} hostid
 */
function hostIncidents(hostid) {
  zbxePopUp(
    `zabbix.php?action=problem.view&fullscreen=1&page=1&filter_show=1&filter_hostids[]=${hostid}&filter_application=&filter_name=&filter_severity=0&filter_inventory[0][field]=type&filter_inventory[0][value]=&filter_evaltype=0&filter_maintenance=1&filter_set=1&kioskmode=1`
  );
}
/**
 * Show availability report for selected host
 * @param  {integer} hostid
 */
function hostAvailability(hostid) {
  zbxePopUp(
    `report2.php?config=0&from=now-1h&to=now&filter_groupid=0&filter_hostid=10318&filter_set=1${hostid}&kioskmode=1`
  );
  // zbxePopUp("report2.php?config=0&from=now-1M%2FM&to=now-1M%2FM&filter_groupid=20&filter_hostid="+hostid);
}

function addTileLayer(name) {
  return L.tileLayer(mbUrl, {
    id: `mapbox.${name}`,
    attribution: mbAttr
  });
}

function copyToClipboard(coordenates) {
  zbxeConsole(`Coordenates: ${coordenates}`);
  const el = document.createElement("textarea");
  el.value = coordenates;
  el.setAttribute("readonly", "");
  el.style.position = "absolute";
  el.style.left = "-9999px";
  document.body.appendChild(el);
  el.select();
  document.execCommand("copy");
  document.body.removeChild(el);
}

/**
 * Configure map onclick in unused areas to show coordenates
 * @param  {object} e
 */
function onMapClick(e) {
  let img = `<img style="cursor: pointer;" src="local/app/everyz/images/zbxe-copy-icon.png" onclick="copyToClipboard('${e.latlng.toString()}');" title="Copy coordenates"></img>`;
  popup
    .setLatLng(e.latlng)
    .setContent(`You selected here: ${e.latlng.toString()} ${img}`)
    .openOn(everyzObj.map);
}

function onEachFeature(feature, layer) {
  var popupContent = "";
  if (feature.properties && feature.properties.popupContent) {
    popupContent += feature.properties.popupContent;
  }
  layer.bindPopup(popupContent, {
    permanent: false,
    className: "zbxe-label",
    offset: [0, 0],
    maxWidth: 500
  });
}

// Cria dinamicamente a referencia para o icone do host
function zbxImage(iconid, width, height) {
  width = width || 32;
  height = height || 32;
  iconURL =
    iconid == parseInt(iconid, 0)
      ? `imgstore.php?iconid=${iconid}`
      : `local/app/everyz/images/zpoi_${iconid}.png`;
  return L.icon({
    iconUrl: iconURL,
    iconSize: [width, height],
    iconAnchor: [Math.round(width / 2), height],
    popupAnchor: [2, -38]
  });
}

function addCircle(lat, lon, radiusSize, fillColor, borderColor, opacity) {
  var fillColor = typeof fillColor !== "undefined" ? fillColor : "#303";
  var borderColor = typeof borderColor !== "undefined" ? borderColor : "";
  var opacity = typeof opacity !== "undefined" ? opacity : 0.3;
  if (showCircles == 1) {
    L.circle([lat, lon], {
      color: borderColor,
      fillColor: fillColor,
      fillOpacity: opacity,
      radius: radiusSize
    })
      .addTo(ZabGeocircle)
      .bindPopup(radiusSize + "m", {
        className: "zbxe-label",
        offset: [0, 0],
        maxWidth: 500
      });
  }
}

function addAlert(
  lat,
  lon,
  radiusSize,
  fillColor,
  borderColor,
  opacity,
  title
) {
  var fillColor = typeof fillColor !== "undefined" ? fillColor : "#303";
  var borderColor = typeof borderColor !== "undefined" ? borderColor : "";
  var opacity = typeof opacity !== "undefined" ? opacity : 0.2;
  var title = typeof title !== "undefined" ? title : "";

  L.circle([lat, lon], {
    color: borderColor,
    fillColor: fillColor,
    fillOpacity: opacity,
    radius: radiusSize
  })
    .addTo(ZabGeoalert)
    .bindPopup(title, {
      className: "zbxe-label",
      offset: [0, 0],
      maxWidth: 500
    });
}

function addLine(from, to, popup, fillColor, weight, opacity, text) {
  var popup = typeof popup !== "undefined" ? popup : "#303";
  var fillColor = typeof fillColor !== "undefined" ? fillColor : "#000088";
  var weight = typeof weight !== "undefined" ? weight : 6;
  var opacity = typeof opacity !== "undefined" ? opacity : 1;
  if (showLines == 1) {
    tmp = new L.polyline(
      [new L.LatLng(from[0], from[1]), new L.LatLng(to[0], to[1])],
      {
        color: fillColor,
        weight: weight,
        opacity: opacity
      }
    );

    if (popup !== "") {
      tmp
        .bindTooltip(popup, {
          permanent: false,
          className: "zbxe-label",
          offset: [0, 0]
        })
        .bindPopup(popup, { maxWidth: 500 });
    }
    everyzObj.map.addLayer(tmp);

    tmp.on("click", function() {
      everyzObj.map.removeLayer(tmp);
    });
  }
}

function parse_html(html, args) {
  for (var key in args) {
    var re = new RegExp(`<${key}>`, "g");
    html = html.replace(re, args[key]);
  }
  return html;
}

function actionButton(title, img, onclick, className) {
  if (className === undefined) {
    className = "everyzShortcutIMG";
  }
  return `<img class="${className}" hspace=10 vspace=10  title="${title}" src="local/app/everyz/images/${img}" onclick='${onclick}'/>&nbsp;`;
}

function popupHost(hostid, hostname, events, hostConn, extraButtons) {
  var hasEvent = events.length > 0;
  var buttonList =
    actionButton(
      geoTitleLatest,
      "zbxe-latest.png",
      `javascript:hostLatest(${hostid});`
    ) +
    actionButton(
      geoTitleTriggers,
      `zbxe-${hasEvent ? "incident" : "ok"}.png`,
      `javascript:hostIncidents(${hostid});`
    );
  var extraButtons = typeof extraButtons !== "undefined" ? extraButtons : "";

  extraButtons = parse_html(extraButtons, {
    host: hostname,
    conn: hostConn,
    hostid: hostid
  });
  return parse_html(geoPopUpTemplate, {
    hostIdentify: geoHostWord,
    host: hostname,
    conn: hostConn,
    shortcuts: buttonList + extraButtons,
    editbutton: actionButton(
      geoTitleMetadata,
      "zbxe-geometadata.png",
      `javascript:editHostMetadata(${hostid});`,
      "everyzEditIMG"
    ),
    eventsList: events
  });
}

function easterEgg(type, name, extra) {
  return (
    `<div class="popUpEgg"><table><tr><td><img width="60px" height="60px" src="local/app/everyz/images/icon_${type}.png"/></td>` +
    `<td class="tdEgg"><b>${name}</b>, very thank you!${extra}</td></tr></table></div>`
  );
}

function easterEggAnimated() {
  // Easter Egg com os tradutores e desenvolvedores do EveryZ --------------------
  switch (eeCont) {
    case 1:
      addHost(
        -15.791246,
        -47.8932317,
        "adail",
        easterEgg(
          "developer",
          "Adail Horst",
          "<br>For being crazy and decide to create me,<br> besides giving me a very charming name!  ;-)"
        )
      );
      addHost(
        -3.736878,
        -38.5334797,
        "ari",
        easterEgg(
          "developer",
          "Aristoteles",
          "<br>For code together the Zab-Geo!"
        )
      );
      addLine(
        [27.43419, -28.125],
        [-3.736878, -38.5334797],
        "",
        "#770077",
        4,
        0.8
      );
      addLine(
        [27.43419, -28.125],
        [-15.791246, -47.8932317],
        "",
        "#FF6600",
        4,
        0.8
      );
      break;
    case 2:
      addHost(
        45.066836045,
        7.63612707,
        "italy",
        easterEgg(
          "translate",
          "Dimitri Bellini",
          "<br>For your help translating to<br>italian language!<br>http://quadrata.it/"
        )
      );
      addLine(
        [27.43419, -28.125],
        [45.066836045, 7.63612707],
        "",
        "#006600",
        4,
        0.8
      );
      break;
    case 3:
      addHost(
        43.633175,
        -79.470457,
        "canada",
        easterEgg(
          "translate",
          "Shary Ann",
          "<br>For your help translating to<br>french language!"
        )
      );
      addHost(
        56.952304,
        24.111023,
        "riga",
        easterEgg("developer", "Zabbix", "<br>For create the Zabbix!")
      );
      addLine(
        [27.43419, -28.125],
        [43.633175, -79.470457],
        "",
        "#CC0066",
        4,
        0.8
      );
      addLine(
        [27.43419, -28.125],
        [56.952304, 24.111023],
        "",
        "#660000",
        4,
        0.8
      );
      break;
    case 4:
      addHost(
        48.857482,
        2.2935243,
        "france",
        easterEgg(
          "translate",
          "Steve Destivelle",
          "<br>For your help translating to<br>french language!"
        )
      );
      addHost(
        -25.526181,
        -54.537174,
        "latinoware",
        easterEgg(
          "latinoware",
          "Latinoware",
          "<br>For your contribution in use of <br> Free Software on Latin America!" +
            "<br>This is the best free software event<br> in the Americas, join US!!!<br><a href='http://latinoware.org/'>http://latinoware.org/</a>"
        )
      );
      addLine(
        [27.43419, -28.125],
        [48.857482, 2.2935243],
        "",
        "#000077",
        4,
        0.8
      );
      addLine(
        [27.43419, -28.125],
        [-25.526181, -54.537174],
        "",
        "#000077",
        4,
        1
      );
      break;
    case 5:
      L.controlCredits({
        image: "local/app/everyz/images/zpoi_whynotwork.png",
        link: "http://www.everyz.org/docs",
        text: `<div id="everyzTopMenuInfo">${easterEggInfo}</div>`,
        height: "64",
        width: "103"
      })
        .addTo(ZabGeomap)
        .setPosition("topright");
      break;
    default:
      addHost(
        27.43419,
        -28.125,
        "everyz",
        easterEgg(
          "developer",
          "EveryZ",
          "<br>For increase the Zabbix<br>native functionalities!" +
            "<br><a href='http://www.everyz.org'>www.everyz.org</a>"
        )
      );
  }
  eeCont = eeCont + 1;
  if (eeCont < 6) {
    setTimeout(easterEggAnimated, 1000);
  }
}

function showEasterEgg() {
  setTimeout(easterEggAnimated, 1000);
}

// from: https://stackoverflow.com/questions/24018630/how-to-save-a-completed-polygon-points-leaflet-draw-to-mysql-table
// with adaptations
function getShapes(drawnItems) {
  var shapes = [];
  shapes["polyline"] = [];
  shapes["circle"] = [];
  shapes["marker"] = [];

  drawnItems.eachLayer(function(layer) {
    if (layer instanceof L.Polyline) {
      shapes["polyline"].push(layer.getLatLngs());
    }

    if (layer instanceof L.Circle) {
      shapes["circle"].push([layer.getLatLng()]);
    }

    if (layer instanceof L.Marker) {
      shapes["marker"].push([layer.getLatLng()], layer.getRadius());
    }
  });

  return shapes;
}

//https://stackoverflow.com/questions/5623838/rgb-to-hex-and-hex-to-rgb
function hexToRgb(hex) {
  var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result
    ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
      }
    : null;
}

function htmlExtra(args) {
  var extra = "";
  for (var key in args) {
    extra += " " + key + '="' + args[key] + '"';
  }
  return extra;
}

/**
 * Create a input element with numeric (integer) validation
 * @param  {string}  id    id/name of element
 * @param  {string}  value default value
 * @param  {integer} width width of input
 * @param  {array}   args  extra html args
 * @return {string}        html code for input
 */
function zbxeEditInteger(id, value, width, args) {
  var value = typeof value !== "undefined" ? value : "";
  var width = typeof width !== "undefined" ? width : "80";
  var extra = htmlExtra(args);
  position = typeof position !== "undefined" ? position : "topleft";
  return (
    `<input id="${id}" name="${id}" value="${value}" style="text-align: right;width: ${width}px;"` +
    ` onchange="validateNumericBox(this, false, true);" type="text" ${extra}>"`
  );
}

/**
 * Create a select element from array
 * @param  {string} id      id/name of element
 * @param  {string} value   default value
 * @param  {array}  options array with possible options (options[key] = value)
 * @param  {array}  args    extra html args
 * @return {string}         html code for select input
 */
function zbxeSelect(id, value, options, args) {
  var extra = htmlExtra(args);
  var select_options = "";
  for (var key in options) {
    select_options += ` <option value="${key}" ${
      value === key ? "selected" : ""
    }>${options[key]}</option>`;
  }
  return `<select id="${id}" name="${id}" ${extra}>${select_options}</select>"`;
}

/**
 * Function to configure a host input select
 * @param  {string} id   [description]
 * @param  {array} data [description]
 * @param  {array} args [description]
 * @return none
 */
function zbxeInitHostSelect(id, data, args, zbxeTranslation) {
  var data = typeof data !== "undefined" ? [data] : {};
  if (typeof zbxeTranslation === "undefined") {
    var translation = {
      "No matches found": "No matches found",
      "More matches found...": "More matches found...",
      "type here to search": "type here to search",
      new: "new",
      Select: "Select"
    };
  } else {
    var translation = {
      "No matches found": zbxeTranslation["No matches found"],
      "More matches found...": zbxeTranslation["More matches found..."],
      "type here to search": zbxeTranslation["type here to search"],
      new: zbxeTranslation["new"],
      Select: zbxeTranslation["Select"]
    };
  }
  var params = {
    srctbl: "hosts",
    dstfrm: "zbx_filter",
    dstfld1: "hostids_",
    srcfld1: "hostid",
    real_hosts: "1",
    multiselect: "1"
  };
  for (var key in args) {
    params[key] = args[key];
  }
}

/**
 * Configure a map for normal or fullscreen mode
 * @param  {string} mapid ID of DIV used to show a map
 * @return {[type]}       None
 */
function zbxeConfigureDivMap(mapid) {
  var mapid = typeof mapid !== "undefined" ? mapid : "mapid";
  vDiv = document.getElementById(mapid);
  if (location.search.split("fullscreen=1")[1] !== undefined) {
    vDiv.style.height = window.innerHeight - 10 + "px";
    if (location.search.split("hidetitle=1")[1] !== undefined) {
      vDiv.style.width = window.innerWidth - 10 + "px";
      document.getElementsByTagName("body")[0].style.overflow = "hidden";
      document.getElementsByTagName("main")[0].style.padding =
        "0px 0px 0px 0px";
    } else {
      vDiv.style.width = window.innerWidth - 50 + "px";
      vDiv.style.height = window.innerHeight - 70 + "px";
    }
  } else {
    vDiv.style.height = window.innerHeight - 140 + "px";
    vDiv.style.width = window.innerWidth - 50 + "px";
  }
}

function divColorPicker(value, width, id) {
  var id = typeof id !== "undefined" ? id : "font_color";
  var width = typeof width !== "undefined" ? width : "85";
  var value = typeof value !== "undefined" ? value : "#6666FF";
  var hexVal = value;
  if (value.length === 6) {
    value = "#" + value;
  }
  if (value.length === 7) {
    var tmp = hexToRgb(value);
    hexVal = value.substring(1, 7);
    value = `rgb(${tmp.r}, ${tmp.g}, ${tmp.b}); background: rgb(${tmp.r}, ${tmp.g}, ${tmp.b})`;
  }
  var type = width > 0 ? "text" : "hidden";
  return `<input type="color" id="${id}" name="${id}" value="${value}" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$"> `;
}

function addColorPicker(map, position) {
  position = typeof position !== "undefined" ? position : "topleft";
  var legend = L.control({ position: position });
  legend.onAdd = function(map) {
    return newLeafletLeftBar(
      "everyzLeftBar",
      divColorPicker("000000", 0) + divColorPicker(map, 0, "lbl2")
    );
  };
  legend.addTo(map);
  return legend;
}

function newLeafletLeftBar(id, content) {
  var div = L.DomUtil.create("div", "leaflet-bar leaflet-draw-toolbar-top");
  div.innerHTML = `<a class="everyz-color-picker" title="Select default color">${content}</a>`;
  div.id = id;
  div.firstChild.onmousedown = div.firstChild.ondblclick = div.firstChild.onpointerdown =
    L.DomEvent.stopPropagation;
  return div;
}

function addToolButton(id, customClass, title, onClick) {
  if (typeof onClick !== "undefined") {
    onClick = ` onclick="${onClick}"`;
  } else {
    onClick = "";
  }
  return (
    `<button id="${id}_btn" type="button" class="btn btn-info" style="width: 30px; height: 30px;">` +
    `<a id="${id}" class="fa ${customClass} icon-green" style="width: 28px; height: 28px;" title="${title}" ${onClick}></a>` +
    `</button>`
  );
}

function isValidJSON(str) {
  try {
    JSON.parse(str);
    return true;
  } catch (e) {
    return false;
  }
}

function addEditPopUp(element) {
  element.on("click", function(event) {
    new LeafletToolbar.EditToolbar.Popup(event.latlng, {
      actions: editActions,
      maxWidth: "auto"
    }).addTo(everyzObj.map, element);
  });
  return element;
}
/**
 * Init properties for a new element in map
 * @param  {} currentElement
 * @param  {} type
 * @param  {} options
 */
function initCurrentElement(currentElement, type, options) {
  options = options || [];
  currentElement.zbxe = {
    uid: options.uid || generateUID(),
    color: options.color || everyzObj.default.polyline.color,
    popup: options.popup || "",
    weight: options.weight || 5,
    dasharray: options.dasharray || "",
    opacity: options.opacity || 0.8,
    size: options.size || 500,
    trigger: options.trigger || ""
  };
  if (typeof type !== "undefined") {
    currentElement.zbxe.type = type;
  }
  currentElement.setStyle({
    color: currentElement.zbxe.color,
    weight: currentElement.zbxe.weight,
    dashArray: currentElement.zbxe.dasharray,
    opacity: currentElement.zbxe.opacity,
    radius: currentElement.zbxe.size
  });
}

/**
 * Set element properties from zabbix inventory / JSON
 * @param  {array} item - Array with metadata
 */
function itemOptions(item) {
  return {
    color: item.color,
    dasharray: item.dasharray,
    popup: item.popup || "",
    opacity: item.opacity,
    weight: item.width,
    radius: item.size,
    trigger: item.trigger || ""
  };
}
/**
 * Create a unique UID for element in map
 */
function generateUID() {
  var firstPart = (Math.random() * 46656) | 0;
  var secondPart = (Math.random() * 46656) | 0;
  firstPart = ("000" + firstPart.toString(36)).slice(-3);
  secondPart = ("000" + secondPart.toString(36)).slice(-3);
  return firstPart + secondPart;
}

function addLinkToHost(route, hostMaker, editMode) {
  let hostLink = new L.polyline(route);
  everyzObj.currentElement = hostLink;
  initCurrentElement(everyzObj.currentElement);
  hostLink.zbxe.main = hostMaker;
  hostLink.zbxe.hostid = hostMaker.zbxe.hostid;
  hostLink.zbxe.type = "link";
  if (editMode == true) {
    addEditPopUp(hostLink);
  }
  hostLink.addTo(everyzObj.map);
  return hostLink;
}

function updateLinkConfig(hostLink, options) {
  hostLink.zbxe.color = options.color;
  hostLink.zbxe.width = options.width || 5;
  hostLink.zbxe.dasharray = options.dasharray || [];
  hostLink.zbxe.opacity = options.opacity || 0.9;
  hostLink.zbxe.popup = options.popup || "";
  hostLink.setStyle({
    color: hostLink.zbxe.color,
    weight: hostLink.zbxe.width,
    dashArray: hostLink.zbxe.dasharray,
    opacity: hostLink.zbxe.opacity
  });
  return hostLink;
}

function addInfoPopup(element) {
  //  console.log([element.zbxe.uid,element.zbxe.popup,element.zbxe]);
  if (element.zbxe.popup !== "") {
    //console.log([element.zbxe]);
    element.bindPopup(element.zbxe.popup, { maxWidth: 500 });
  }
}
