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

var filterButton = document.getElementById('filter-mode');
var titleBar = document.getElementsByClassName("header-title");
var filterDIV = document.getElementById('filter-space');
if (titleBar[0].children[0].tagName.toLowerCase() == 'div') {
    ZBX_VER="3.2";
    titleUL = titleBar[0].children[1].children[0];
} else {
    ZBX_VER="3.0";
    titleUL = titleBar[0].children[1];
}
if (titleBar[0].children.length > 1) {
    var newItem = document.createElement("LI");
    var textnode = document.createTextNode(" ")
    titleUL.appendChild(filterButton);
    btnMin = document.getElementsByClassName("btn-min");
    if (btnMin.length > 0) {
        filterDIV.style = 'display: none;'
    }
}

function zbxeSearch(mode) {
    inputSearch = document.getElementById("search");
    switch (mode) {
        case "share":
            PopUp("https://share.zabbix.com/search?searchword="+inputSearch.value+"&search_cat=1");
            break;
        case "doc":
            PopUp("https://www.zabbix.com/documentation/"+ZBX_VER+"/start?do=search&id="+inputSearch.value);
            break;
    }
}

/*
// Search bar customization
var inputValue = document.getElementById("search");
var onFocus = function () {
    //this.classList.remove("search");
    this.classList.add("input-expand");
};
var onBlur = function () {
    if (!this.value)
        this.classList.remove("input-expand");
};
inputValue.addEventListener('focus', onFocus, false);
inputValue.addEventListener('blur', onBlur, false);
inputValue.classList.remove("search");
inputValue.classList.add("input-value");

*/