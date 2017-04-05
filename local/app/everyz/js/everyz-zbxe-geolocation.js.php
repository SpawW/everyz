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
    var setViewLat = <?php echo $filter['centerLat'] ?>;
            var setViewLong = <?php echo $filter['centerLong'] ?>;
            var setViewZoom = <?php echo getRequest2("zoomLevel", 19) ?>;
            var showCircles = <?php echo ( in_array($filter["layers"], [3, 99]) ? 1 : 0); ?>; //(0=disable/1=enable)
            var showLines = <?php echo ( in_array($filter["layers"], [2, 99]) ? 1 : 0); ?>; //(0=disable/1=enable)

            vDiv = document.getElementById("mapid");
            if (location.search.split('fullscreen=1')[1] !== undefined) {
                vDiv.style.height = window.innerHeight - 10;
                if (location.search.split('hidetitle=1')[1] !== undefined) {
                    vDiv.style.width = window.innerWidth - 10;
                    document.getElementsByTagName("body")[0].style.overflow = "hidden";
                    document.getElementsByClassName("article")[0].style.padding = "0px 0px 0px 0px";
                } else {
                    vDiv.style.width = window.innerWidth - 50;
                    vDiv.style.height = window.innerHeight - 70;
                }
            } else {
                vDiv.style.height = window.innerHeight - 140;
                vDiv.style.width = window.innerWidth - 50;
            }
            /*window.innerWidth*/
            
            //Define area for Map (setup this data in database ZabbixExtras)
            var ZabGeomap = L.map('mapid').setView([setViewLat, setViewLong], setViewZoom);
            //Create layerGroup Circle
            var ZabGeocircle = new L.LayerGroup();
            //Create layerGroup Lines
            var ZabGeolines = new L.LayerGroup();
            //Create layerGroup Alert
            var ZabGeoalert = new L.LayerGroup();
            var mbToken = '<?php echo zbxeConfigValue('geo_token') ?>';
            var baseMaps = {};
            function addMapTile (description, url, attribution, maxZoom) {
            baseMaps[description] = L.tileLayer(url, {maxZoom: maxZoom, attribution: attribution});
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
echo $mapBackgroud[$filter["map"]]; //"streets"             
?>";
            L.tileLayer(baseMaps[defaultMap]["_url"], baseMaps[defaultMap]["options"]).addTo(ZabGeomap);
    }
    // Adiciona o logotipo
    if (location.search.split('hidetitle=1')[1] !== undefined) {
        var credctrl = L.controlCredits({
            image: "zbxe-logo.php",
            link: "http://www.everyz.org/",
            text: '<div id="everyzTopMenuLogo"></div>',
            height: "25",
            width: "<?php echo zbxeConfigValue("company_logo_width", 0, 120);?>"
        }).addTo(ZabGeomap).setPosition('topright');
    }
<?php

function showTitle($host) {
    $imgMetadata = ((CWebUser::getType() > USER_TYPE_ZABBIX_USER) ? "<img class=\"everyzEditIMG\" title=\"" . _zeT("Edit host metadata") . "\" src=\"local/app/everyz/images/zbxe-geometadata.png\" onclick=\'javascript:editHostMetadata(" . $host["id"] . ");\'/>" : "");
    $imgLatest = "<img class=\"everyzEditIMG\" title=\"" . _("Latest Data") . "\" src=\"local/app/everyz/images/zbxe-latest.png\" onclick=\'javascript:hostLatest(" . $host["id"] . ");\'/>";
    $imgIncident = "<img class=\"everyzEditIMG\" title=\"" . _("Status of triggers") . "\" src=\"local/app/everyz/images/zbxe-"
            . (isset($host["events"]) && count($host["events"]) > 0 ? "incident" : "ok") 
            .".png\" onclick=\'javascript:hostIncidents(" . $host["id"] . ");\'/>";

    return "'" . "Host: " . bold($host["name"]) . "<br>" . $imgMetadata . SPACE . $imgLatest . SPACE . $imgIncident;
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
            $eventList .= "<li style='background: #" . getSeverityColor($value["priority"], [$config])
                    . "; list-style:square;'><a class='everyzGEOLink' href='tr_events.php?triggerid="
                    . $value["triggerid"] . "&eventid=" . $value["eventid"] . "'"
                    . (strlen($value["description"]) > 30 ? " title='".$value["description"]."' " : "" )
                    . "> " 
                    . (strlen($value["description"]) > 30 ? substr($value["description"],0,30)."..." : $value["description"] ) 
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
    echo ($i == 0 ? "" : ","), zbxeImageId('zbxe_icon_error_' . $i);
}
echo "]; ";
// Cria os hosts no mapa
$linesPackage = "";

if (hasRequest('showteam')) { // Clean the filter parameters
?>
    // Easter Egg com os tradutores e desenvolvedores do EveryZ --------------------
     addHost(43.633175,-79.470457,"canada",easterEgg("translate","Shary Ann","<br>For your help translating to<br>french language!"));
     addHost(56.952304,24.111023,"riga",easterEgg("developer","Zabbix","<br>For create the Zabbix!"));
     addHost(27.43419,-28.125,"everyz",easterEgg("developer","EveryZ","<br>For increase the Zabbix<br>native functionalities!"
             +"<br><a href='http://www.everyz.org'>www.everyz.org</a>"));
     addLine([27.43419,-28.125], [-3.736878,-38.5334797],'','#770077',4,0.8);
     addLine([27.43419,-28.125], [-15.791246,-47.8932317],'','#FF6600',4,0.8);
     addLine([27.43419,-28.125], [45.066836045,7.63612707],'','#006600',4,0.8);
     addLine([27.43419,-28.125], [48.857482,2.2935243],'','#000077',4,0.8);
     addLine([27.43419,-28.125], [56.952304,24.111023],'','#660000',4,0.8);
     addLine([27.43419,-28.125], [43.633175,-79.470457],'','#CC0066',4,0.8);
     addHost(48.857482,2.2935243,"france",easterEgg("translate","Steve Destivelle","<br>For your help translating to<br>french language!"));
     addHost(-15.791246,-47.8932317,"brazil",  easterEgg("developer","Adail Horst","<br>For being crazy and have decided to develop!<br>Besides giving me a very charming name! ;-)") );
     addHost(-3.736878,-38.5334797,"ari",easterEgg("developer","Aristoteles","<br>For code together the Zab-Geo!") );
     addHost(45.066836045,7.63612707,"italy",easterEgg("translate","Dimitri Bellini","<br>For your help translating to<br>italian language!<br>http://quadrata.it/")); 
<?php     
} else {
    // Traduções
    foreach ($hostData as $host) {
        if (array_key_exists("location_lat", $host)) {
            $bigPriority = bigSeverity($host);
            $hostEvents = showEvents($host);
            // Add host
            if ($bigPriority > 0) {
                echo "\n addErrorHost(" . $host["location_lat"] . "," . $host["location_lon"] . ",errorImages[$bigPriority]," 
                    . 'popupHost('.$host["id"].', '.quotestr($host["name"])//.', '.(isset($host["events"]) && count($host["events"]))
                        .','.$hostEvents
                        .')'    
                    //. showTitle($host) 
                        //. showEvents($host) 
                        . ");";
            } 
            echo "\n addHost(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $host["iconid"] . "," 
                . 'popupHost('.$host["id"].', '.quotestr($host["name"])
                .','.$hostEvents.')'    
                //.', '                .(isset($host["events"]) && count($host["events"]) ? '0' : '1')
                //. showTitle($host)
                //. showEvents($host) 
                . " );";

            // Add circles
            if (isset($host["circle"])) {
                foreach ($host["circle"] as $circles) {
                    echo "addCircle(" . $host["location_lat"] . "," . $host["location_lon"] . "," . $circles['size'] . ",'" . $circles['color'] . "'," . zbxeConfigValue('geo_circle_opacity', 0, 0.3).");";
                }
            }
            // Add lines
            if (isset($host["line"])) {
                foreach ($host["line"] as $lines) {
                    echo "\n addLine([" . $host["location_lat"] . "," . $host["location_lon"] . "], ["
                    . $lines['lat'] . "," . $lines['lon'] . "],'" . $lines['popup'] . "','" . $lines['color'] . "'," . $lines['width'] . ",".zbxeConfigValue("geo_link_opacity", 0, 1).");";
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

