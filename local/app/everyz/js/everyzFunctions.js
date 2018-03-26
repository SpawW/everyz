/*
 * * Purpose: Generic JS functions
 * * Adail Horst.
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

var titleBar = document.getElementsByClassName("header-title");
var filterDIV = document.getElementById('filter-space');
var filterButton = document.getElementById('filter-mode');

if (titleBar[0] == undefined) {
    if (filterButton !== null) {
        filterDIV.style = 'display: none;';
        filterButton.style = 'display: none;';
    }
} else {
    var filterButton = document.getElementById('filter-mode');
    var btnMax = document.getElementsByClassName("btn-max");
    if (titleBar[0].children[0].tagName.toLowerCase() == 'div') {
        ZBX_VER = "3.2";
        if (titleBar[0].children[1] !== undefined) {
            titleUL = titleBar[0].children[1].children[0];
            if (titleUL.tagName.toLowerCase() !== 'ul') {
                tmp = titleUL.getElementsByTagName("UL");

                if (tmp[0] === undefined) {
                    titleUL.insertAdjacentHTML("beforeEnd", "<ul></ul>");
                }
                titleUL = tmp[0];
            }
        }
    } else {
        ZBX_VER = "3.0";
        titleUL = titleBar[0].children[1];
        if (titleUL.tagName.toLowerCase() !== 'ul') {
            tmp = titleUL.getElementsByTagName("UL");
            titleUL = tmp[0];
        }
    }
    if (titleBar[0].children.length > 1) {
        if (filterButton !== null) {
            var newItem = document.createElement("LI");
            newItem.appendChild(filterButton);
            titleUL.appendChild(newItem);
        }
        var btnMin = document.getElementsByClassName("btn-min");
        if (btnMin.length > 0) {
            filterDIV.style = 'display: none;';
        }
    }
}

function zbxeSearch(mode) {
    inputSearch = document.getElementById("search");
    switch (mode) {
        case "share":
            zbxePopUp("https://share.zabbix.com/search?searchword=" + inputSearch.value + "&search_cat=1");
            break;
        case "doc":
            zbxePopUp("https://www.zabbix.com/documentation/" + ZBX_VER + "/start?do=search&id=" + inputSearch.value);
            break;
    }
}

// Original code from js/common.js  file from Zabbix 3.4
function zbxePopUp(url, width, height, form_name) {
        if (!width) {
                width = 1024;
        }
        if (!height) {
                height = 768;
        }
        if (!form_name) {
                form_name = 'zbx_popup';
        }

        var left = (screen.width - (width + 150)) / 2;
        var top = (screen.height - (height + 150)) / 2;

        var popup = window.open(url, form_name, 'width=' + width + ', height=' + height + ', top=' + top + ', left=' + left + ', resizable=yes, scrollbars=yes, location=no, menubar=no');
        popup.focus();

        return false;
}
