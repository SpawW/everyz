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
if ($easterMode) { // Clean the filter parameters
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
    echo "var easterEggMode=0;";
}
?>

vDiv = document.getElementById("mapid");
if (location.search.split('fullscreen=1')[1] !== undefined) {
    vDiv.style.height = (window.innerHeight - 10)+'px';
    if (location.search.split('hidetitle=1')[1] !== undefined) {
        vDiv.style.width = (window.innerWidth - 10)+'px';
        document.getElementsByTagName("body")[0].style.overflow = "hidden";
        document.getElementsByClassName("article")[0].style.padding = "0px 0px 0px 0px";
    } else {
        vDiv.style.width = (window.innerWidth - 50)+'px';
        vDiv.style.height = (window.innerHeight - 70)+'px';
    }
} else {
    vDiv.style.height = (window.innerHeight - 140)+'px';
    vDiv.style.width = (window.innerWidth - 50)+'px';
}
    //Define area for Map (setup this data in database ZabbixExtras)
    var ZabGeomap = L.map('mapid').setView([setViewLat, setViewLong], setViewZoom);
//.bindTooltip("My Label", {permanent: true, className: "my-label", offset: [0, 0] }).addTo(ZabGeomap);
    //,{drawControl: true}
    //Create layerGroup Circle
    var ZabGeocircle = new L.LayerGroup();
    //Create layerGroup Lines
    var ZabGeolines = new L.LayerGroup();
    //Create layerGroup Alert
    var ZabGeoalert = new L.LayerGroup();

    var mbToken = '<?php echo zbxeConfigValue('geo_token') ?>';
    var baseMaps = {};

// ------------------- OverLapping Layer ---------------------------------------
    var oms = new OverlappingMarkerSpiderfier(ZabGeomap);

    var popup = new L.Popup();
    oms.addListener('click', function (marker) {
      //alert('aqui');
        popup.setContent(marker.desc);
        popup.setLatLng(marker.getLatLng());
        ZabGeomap.openPopup(popup);
    });

    function onMapClick(e) {
        popup.setLatLng(e.latlng)
                .setContent("You selected here: " + e.latlng.toString())
                .openOn(ZabGeomap);
    }
// ------------------- OverLapping Layer end -----------------------------------

    ZabGeomap.on('contextmenu', onMapClick);


    function addMapTile(description, url, attribution, maxZoom) {
        baseMaps[description] = L.tileLayer(url, {maxZoom: maxZoom, attribution: '<a href="http://www.everyz.org">EveryZ</a> | ' + attribution});
    }
    if (mbToken !== "") {
        var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                'Imagery &copy <a href="http://mapbox.com">Mapbox</a>',
                mbUrl = (mbToken == "" ? 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png' : 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=' + mbToken);
        //Display Copyright
        L.tileLayer(mbUrl, {
            maxZoom: 18,
            attribution: mbAttr,
            id: 'mapbox.<?php
$mapBackgroud = [ "light", "streets", "dark", "outdoors", "satellite", "emerald"];
echo $mapBackgroud[$filter["map"]]; //"streets"
?>'
        }).addTo(ZabGeomap);
        grayscale = addTileLayer("light");
        streets = addTileLayer("streets");
        dark = addTileLayer("dark");
        outdoors = addTileLayer("outdoors");
        satellite = addTileLayer("satellite");
        emerald = addTileLayer("emerald");
        baseMaps["Grayscale"] = grayscale;
        baseMaps["Streets"] = streets;
        baseMaps["Dark"] = dark;
        baseMaps["Outdoors"] = outdoors;
        baseMaps["Satellite"] = satellite;
        baseMaps["Emerald"] = emerald;
    } else {
        addMapTile("OpenStreet_Base", 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>', 19);
        addMapTile("OpenStreet_Grayscale", 'http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png', '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>', 18);
        addMapTile("OpenTopo", 'http://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', 'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)', 17);
        addMapTile("Stamen_Terrain", 'http://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}.{ext}', 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>', 18);
        baseMaps["Stamen_Terrain"]["options"]["subdomains"] = ["a", "b", "c", "d"];
        baseMaps["Stamen_Terrain"]["options"]["ext"] = 'png';
        addMapTile("CartoDB_DarkMatter", 'http://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>', 19);
        addMapTile("Esri_WorldStreetMap", 'http://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012', 19);
        defaultMap = "<?php
$mapBackgroud = ["OpenStreet_Base", "OpenStreet_Grayscale", "OpenTopo", "Stamen_Terrain", "CartoDB_DarkMatter", "Esri_WorldStreetMap"];
echo $mapBackgroud[$filter["map"]];
?>";
        L.tileLayer(baseMaps[defaultMap]["_url"], baseMaps[defaultMap]["options"]).addTo(ZabGeomap);
    }
    // Adiciona o logotipo
    if (location.search.split('hidetitle=1')[1] !== undefined || easterEggMode === 1) {
        L.controlCredits({
            image: "zbxe-logo.php",
            link: "http://www.everyz.org/",
            text: '<div id="everyzTopMenuLogo"></div>',
            height: "25",
            width: "<?php echo zbxeConfigValue("company_logo_width", 0, 120); ?>"
        }).addTo(ZabGeomap).setPosition('topright');
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
                    . (strlen($value["description"]) > 30 ? " title='" . $value["description"] . "' " : "")
                    . "> "
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
        // Traduções
        //echo "------ adail2";    var_dump($hostData);
        if (file_exists("local/app/everyz/include/geolocation.buttons.change.php")) {
            require_once "local/app/everyz/include/geolocation.buttons.change.php"; ?>
             zbxeConsole('Load extra buttons.');
             <?php
        } else {
            function extraButtons($host)
            {
                return "";
            } ?>
             zbxeConsole('No extra buttons.');
             <?php
        }
        foreach ($hostData as $host) {
            if (array_key_exists("location_lat", $host)) {
                $bigPriority = bigSeverity($host);
                $hostEvents = showEvents($host);
                // Add host
                if ($bigPriority > 0) {
                    echo "\n addErrorHost(" . $host["location_lat"] . "," . $host["location_lon"] . ",errorImages[$bigPriority],"
                . 'popupHost(' . $host["id"] . ', ' . quotestr($host["name"])//.', '.(isset($host["events"]) && count($host["events"]))
                . ',' . $hostEvents . ', '. quotestr($host["conn"]).')'
                .','. $bigPriority. ");";
                }
                echo "\n addHost(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $host["iconid"] . ","
            . 'popupHost(' . $host["id"] . ', ' . quotestr($host["name"])
            . ',' . $hostEvents . ', '. quotestr($host["conn"]).",".extraButtons($host).')'
            . " );";

                // Add circles
                if (isset($host["circle"])) {
                    foreach ($host["circle"] as $circles) {
                        echo "addCircle(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $circles['size'] . ",'" . $circles['color'] . "'," . zbxeConfigValue('geo_circle_opacity', 0, 0.3) . ");";
                    }
                }
                // Add lines
                if (isset($host["line"])) {
                    foreach ($host["line"] as $lines) {
                        echo "\n addLine([" . $host["location_lat"] . "," . $host["location_lon"] . "], ["
                    . $lines['lat'] . "," . $lines['lon'] . "],'" . CMacrosResolverHelper::resolveMapLabelMacros($lines['popup']) . "','" . $lines['color'] . "'," . $lines['width'] . "," . zbxeConfigValue("geo_link_opacity", 0, 1) . ");";
                    }
                }
                // Add Multiline
                if (isset($host["multiline"])) {
                    $multilineCount = 1;
                    foreach ($host["multiline"] as $multilines) {
                        $linesPackage .= ($linesPackage == "" ? "" : ", ")
                            . "\n"
                            . '{"type": "Feature", "geometry": { "type": "MultiLineString", "coordinates": [['
                            . '[' . $host["location_lon"] . "," . $host["location_lat"] . '],' . $multilines[1]
                            . ']]}, "style": { "color":"red" } , "properties": {  "color":"red",  "popupContent": "' . $multilines[6] . '"},"id": '
                            . $multilineCount . '}';
                        $multilineCount++;
                        //echo "\n console.log('Multiline: [" . $host['location_lon'] . "],[" . $host['location_lat'] . "]," . $multilines[1] . " - " . $multilines[6] . "')";
                    }
                }

                // Add Polygon
                if (isset($host["polygon"])) {
                    $polygonCount = 1;
                    foreach ($host["polygon"] as $polygons) {
                        $linesPackage .= ($linesPackage == "" ? "" : ", ")
                            . "\n"
                            . '{"type": "Feature", "properties": { "popupContent": "' . $polygons[6] . '"}, "geometry": { "type": "Polygon", "coordinates": [['
                            . $polygons[1]
                            . ']]}, "className":{ "baseVal":"line2" },"id": '
                            . $polygonCount . '}';
                        $polygonCount++;
                        echo "\n console.log('Polygon: $polygons[1] - " . $polygons[6] . "')";
                    }
                }
            } else {
                // Hosts without coordenates
            }
        }
    }
?>

    //Add Scale in maps
    L.control.scale().addTo(ZabGeomap);
    function addTileLayer(name) {
        return L.tileLayer(mbUrl, {id: 'mapbox.' + name, attribution: mbAttr});
    }
    // Mapas disponíveis =======================================================
    var overlayMaps = {
        "Circles": ZabGeocircle,
        "Lines": ZabGeolines,
        "Alert": ZabGeoalert,
    };
    // Tiles for another maps

    layerControl = L.control.layers(baseMaps).addTo(ZabGeomap).setPosition('topleft');
    //If filter Circle Actived show Circles
    if (showCircles == 1) {
        ZabGeomap.addLayer(ZabGeocircle);
        layerControl.addOverlay(ZabGeocircle, "Circle");
    }

    //If filter Lines Actived show Lines
    if (showLines == 1) {
        ZabGeomap.addLayer(ZabGeolines);
        layerControl.addOverlay(ZabGeolines, "Lines");
    }

    //Active layer Alert
    ZabGeomap.addLayer(ZabGeoalert);

    //ZabGeoMap.fitBounds(oms.getBounds());
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
