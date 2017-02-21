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

    var setViewLat = <?php echo $filter['centerLat'] ?>;
            var setViewLong = <?php echo $filter['centerLong'] ?>;
            var setViewZoom = <?php echo getRequest2("zoomLevel", 19) ?>;
            var showCircles = <?php echo ( in_array($filter["layers"], [3, 99]) ? 1 : 0); ?>; //(0=disable/1=enable)
            var showLines = <?php echo ( in_array($filter["layers"], [2, 99]) ? 1 : 0); ?>; //(0=disable/1=enable)

            vDiv = document.getElementById("mapid");
            vDiv.style.width = screen.availWidth;
            vDiv.style.height = screen.availHeight;
            //Define area for Map (setup this data in database ZabbixExtras)
            var ZabGeomap = L.map('mapid').setView([setViewLat, setViewLong], setViewZoom);
            //Create layerGroup Circle
            var ZabGeocircle = new L.LayerGroup();
            //Create layerGroup Lines
            var ZabGeolines = new L.LayerGroup();
            //Create layerGroup Alert
            var ZabGeoalert = new L.LayerGroup();
            //User will need change this token for the theirs token, acquire in https://www.mapbox.com/studio/account/
            //(setup this data in database ZabbixExtras)
            //pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpandmbXliNDBjZWd2M2x6bDk3c2ZtOTkifQ._QA7i5Mpkd_m30IGElHziw
            var mbToken = '<?php echo zbxeConfigValue('geo_token') ?>';
            console.log("Tamanho do Token MapBox: " + mbToken.length);
            if (mbToken.length <= 1) {
    window.alert("Alert! \n Please check EveryZ - Customization. Token is required for ZabGeo!");
<?php //error("Alert! Please check EveryZ - Customization. Token is required for ZabGeo!");               ?>
    }
    var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
            '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
            'Imagery &copy <a href="http://mapbox.com">Mapbox</a>',
            mbUrl = 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=' + mbToken;
            //Display Copyright 
            L.tileLayer(mbUrl, {
            maxZoom: 18,
                    attribution: mbAttr,
                    id: 'mapbox.<?php
// array com os nomes dos mapas
$mapBackgroud = [ "light", "streets", "dark", "outdoors", "satellite", "emerald"];
echo $mapBackgroud[$filter["map"]]; //"streets"             
?>'
            }).addTo(ZabGeomap);
            // Cria dinamicamente a referencia para o icone do host 
                    function zbxImage(p_iconid, width = 32, height = 32) {
                    return L.icon({
                    iconUrl: 'imgstore.php?iconid=' + p_iconid,
                            iconSize: [width, height],
                            iconAnchor: [Math.round(width / 2), height],
                            popupAnchor: [2, - 38],
                    });
                    }
            function addCircle (lat, lon, radiusSize, fillColor = '#303', borderColor = '', opacity = 0.2){
            L.circle([lat, lon], {color: borderColor, fillColor: fillColor, fillOpacity: opacity, radius: radiusSize}).addTo(ZabGeocircle).bindPopup(radiusSize + 'm');
            }
            function addHost(lat, lon, hostid, name, description) {
            L.marker([lat, lon], {icon: zbxImage(hostid)}).addTo(ZabGeomap).bindPopup(name + description);
            }
            function addErrorHost(lat, lon, hostid, name, description) {
            L.marker([lat, lon], {icon: zbxImage(hostid, 40, 40)}).addTo(ZabGeomap).bindPopup(name + description);
            }

            function addAlert (lat, lon, radiusSize, fillColor = '#303', borderColor = '', opacity = 0.2, title = ''){
            L.circle([lat, lon], {color: borderColor, fillColor: fillColor, fillOpacity: opacity, radius: radiusSize}).addTo(ZabGeoalert).bindPopup(title);
            }

            //
            //Change for repeat to read JSON and add Markers if lat and long exist
            //Put marker in Map
<?php

function showTitle($host) {
    return "'<a href=\'hosts.php?form=update&hostid=" . $host["id"] . "\'>" . $host["name"]
            . "</a>(" . $host["location_lat"] . "," . $host["location_lon"] . ")','"
    ;
}

function bigSeverity($host) {
    $bigPriority = 0;
    if (isset($host["events"])) {
        $eventList = "";
        foreach ($host["events"] as $key => $value) {
            $bigPriority = ($bigPriority > $value["priority"] ? $bigPriority : $value["priority"]);
        }
    }
    return $bigPriority;
}

function showEvents($host) {
    global $config;
    if (isset($host["events"])) {
        $eventList = "";
        foreach ($host["events"] as $key => $value) {
            $eventList .= "<li style=\'background: #" . getSeverityColor($value["priority"], [$config])
                    . "; list-style:square;\'><a href=\'tr_events.php?triggerid="
                    . $value["triggerid"] . "&eventid=" . $value["eventid"] . "\'> " . $value["description"] . "</a></li>";
        }
        return "<hr width=\'99%\' color=\'gray\'><ul>" . $eventList . "</ul>";
    } else {
        return "";
    }
}

// Array com os índices de imagens de erro
echo "\n errorImages = [";
for ($i = 0; $i < 6; $i++) {
    echo ($i == 0 ? "" : ","), zbxeImageId('zbxe_icon_error_' . $i);
}
echo "]; ";
// Cria os hosts no mapa
$linesPackage = "";
foreach ($hostData as $host) {
    if (array_key_exists("location_lat", $host)) {
        $bigPriority = bigSeverity($host);
        // Add host
        if ($bigPriority > 0) {
            echo "\n addErrorHost(" . $host["location_lat"] . "," . $host["location_lon"] . ",errorImages[$bigPriority]," . showTitle($host) . showEvents($host) . "' );";
        }
        echo "\n addHost(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $host["iconid"] . "," . showTitle($host) . showEvents($host) . "' );";
        // Add circles
        if (isset($host["circle"])) {
            foreach ($host["circle"] as $circles) {
                echo "addCircle(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $circles['size'] . ",'" . $circles['color'] . "');";
//                echo "addCircle(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $circles[1] . ",'" . $circles[2] . "');";
            }
        }
        // Add lines
        if (isset($host["line"])) {
            $lineCount = 1;
            foreach ($host["line"] as $lines) {
                $linesPackage .= ($linesPackage == "" ? "" : ", ")
                        . "\n"
                        . '{"type": "Feature", "geometry": { "type": "LineString", "coordinates": [['
                        . $host["location_lon"] . ", " . $host["location_lat"]
                        . '],[' . $lines['lon'] . ', ' . $lines['lat'] . ']]} ' . (
                        $lines['popup'] == "" ? '' : ', "properties": { "popupContent": "' . $lines['popup'] . '"}' ) . ',"id": '
                        . $lineCount . '}';
                $lineCount++;
                //echo "\n console.log('$lines[4]')";
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
                echo "\n console.log('Multiline: [" . $host['location_lon'] . "],[" . $host['location_lat'] . "]," . $multilines[1] . " - " . $multilines[6] . "')";
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
    }
}
?>
            //Change for repeat to read JSON and add Circle inf note exist
            //If radius exist add Circle [use lat and long more radius]
            //Capture point in map using double click 

            var popup = L.popup();
                    function onMapClick(e) {
                    popup
                            .setLatLng(e.latlng)
                            .setContent("You selected here: " + e.latlng.toString())
                            .openOn(ZabGeomap);
                    }

            ZabGeomap.on('contextmenu', onMapClick);
                    //Add Scale in maps
                    L.control.scale().addTo(ZabGeomap);
                    function addTileLayer(name) {
                    return L.tileLayer(mbUrl, {id: 'mapbox.' + name, attribution: mbAttr});
                    }
            // Mapas disponíveis =======================================================
            grayscale = addTileLayer("light");
                    streets = addTileLayer("streets");
                    dark = addTileLayer("dark");
                    outdoors = addTileLayer("outdoors");
                    satellite = addTileLayer("satellite");
                    emerald = addTileLayer("emerald");
                    var baseMaps = {
                    "Grayscale": grayscale
                            , "Streets": streets
                            , "Dark": dark
                            , "Outdoors": outdoors
                            , "Satellite": satellite
                            , "Emerald": emerald
                    };
                    var overlayMaps = {
                    "Circles": ZabGeocircle,
                            "Lines": ZabGeolines,
                            "Alert": ZabGeoalert,
                    };
                    //layerControl = L.control.layers(baseMaps).addTo(ZabGeomap);
                    layerControl = L.control.layers(baseMaps).addTo(ZabGeomap).setPosition('topleft');
                    //If filter Circle Actived show Circles 
                    if (showCircles == 1) {
            ZabGeomap.addLayer(ZabGeocircle);
            }

            //If filter Lines Actived show Lines 
            if (showLines == 1) {
            ZabGeomap.addLayer(ZabGeolines);
            }

            //Active layer Alert
            ZabGeomap.addLayer(ZabGeoalert);
                    layerControl.addOverlay(ZabGeocircle, "Circle");
                    layerControl.addOverlay(ZabGeolines, "Lines");
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

