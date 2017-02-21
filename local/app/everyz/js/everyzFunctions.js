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
    titleUL = titleBar[0].children[1].children[0];
} else {
    titleUL = titleBar[0].children[1];
}

var newItem = document.createElement("LI");
var textnode = document.createTextNode(" ")
titleUL.appendChild(filterButton);
btnMin = document.getElementsByClassName("btn-min");
if (btnMin.length > 0) {
    filterDIV.style = 'display: none;'
}
