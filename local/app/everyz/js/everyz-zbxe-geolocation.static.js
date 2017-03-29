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

function addMapTile(description, url, attribution, maxZoom) {
    baseMaps[description] = L.tileLayer(url, {maxZoom: maxZoom, attribution: attribution});
}

function dynamicPopUp(element) {
//marker.bindPopup("Popup content");
    element.on('mouseover', function (e) {
        this.openPopup();
    });
    element.on('mouseout', function (e) {
        this.closePopup();
    });
}

function addHost(lat, lon, hostid, name, description) {
//marker = 
    L.marker([lat, lon], {icon: zbxImage(hostid)}).addTo(ZabGeomap).bindPopup(name);
}

function addErrorHost(lat, lon, hostid, name, description) {
    L.marker([lat, lon], {icon: zbxImage(hostid, 40, 40)}).addTo(ZabGeomap).bindPopup(name);
}

function editHostMetadata(hostid) {
    PopUp("everyz.php?action=zbxe-geometadata&fullscreen=1&hidetitle=1&sourceHostID=" + hostid);
}

function hostLatest(hostid) {
    PopUp("latest.php?fullscreen=0&hostids[]=" + hostid + "&application=&select=&show_without_data=1&fullscreen=1&filter_set=Filter");
}

function hostIncidents(hostid) {
    PopUp("tr_status.php?fullscreen=1&groupid=0&hostid=" + hostid + "&show_triggers=1&ack_status=1&show_events=1&show_severity=0&filter_set=Filter");
}

function addTileLayer(name) {
    return L.tileLayer(mbUrl, {id: 'mapbox.' + name, attribution: mbAttr});
}

function onMapClick(e) {
    popup.setLatLng(e.latlng)
            .setContent("You selected here: " + e.latlng.toString())
            .openOn(ZabGeomap);
}

function onEachFeature(feature, layer) {
    var popupContent = "";
    if (feature.properties && feature.properties.popupContent) {
        popupContent += feature.properties.popupContent;
    }
    layer.bindPopup(popupContent);
}

// Cria dinamicamente a referencia para o icone do host 
function zbxImage(p_iconid, width, height) {
    width = typeof width !== 'undefined' ? width : 32;
    height = typeof height !== 'undefined' ? height : 32;
    return L.icon({
        iconUrl: 'imgstore.php?iconid=' + p_iconid,
        iconSize: [width, height],
        iconAnchor: [Math.round(width / 2), height],
        popupAnchor: [2, -38],
    });
}

function addCircle(lat, lon, radiusSize, fillColor, borderColor, opacity) {
    var fillColor = typeof fillColor !== 'undefined' ? fillColor : '#303';
    var borderColor = typeof borderColor !== 'undefined' ? borderColor : '';
    var opacity = typeof opacity !== 'undefined' ? opacity : 0.3;
    if (showCircles == 1) {
        L.circle([lat, lon], {color: borderColor, fillColor: fillColor, fillOpacity: opacity, radius: radiusSize}).addTo(ZabGeocircle).bindPopup(radiusSize + 'm');
    }
}

function addAlert(lat, lon, radiusSize, fillColor, borderColor, opacity, title) {
    var fillColor = typeof fillColor !== 'undefined' ? fillColor : '#303';
    var borderColor = typeof borderColor !== 'undefined' ? borderColor : '';
    var opacity = typeof opacity !== 'undefined' ? opacity : 0.2;
    var title = typeof title !== 'undefined' ? title : '';
    L.circle([lat, lon], {color: borderColor, fillColor: fillColor, fillOpacity: opacity, radius: radiusSize}).addTo(ZabGeoalert).bindPopup(title);
}

function addLine(from, to, popup, fillColor, weight, opacity) {
    var popup = typeof popup !== 'undefined' ? popup : '#303';
    var fillColor = typeof fillColor !== 'undefined' ? fillColor : '#000088';
    var weight = typeof weight !== 'undefined' ? weight : 6;
    var opacity = typeof opacity !== 'undefined' ? opacity : 1;
    if (showLines == 1) {
        tmp = new L.polyline([new L.LatLng(from[0], from[1]), new L.LatLng(to[0], to[1])], {color: fillColor, weight: weight, opacity: opacity});
        if (popup !== "") {
            tmp.bindPopup(popup);
            dynamicPopUp(tmp);
        }
        ZabGeomap.addLayer(tmp);
        //            tmp.showExtremities('arrowM'); nao esta funcionando ainda
    }
}
