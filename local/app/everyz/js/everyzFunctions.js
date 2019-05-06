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

function zbxeCheckZabbixVersion(str) {
  const regex = /Zabbix (.*). ©/g;
  let m;
  let vReturn = 0;
  while ((m = regex.exec(str)) !== null) {
    if (m.index === regex.lastIndex) {
      regex.lastIndex++;
    }
    m.forEach((match, groupIndex) => {
      //console.log(`Current Zabbix Version, group ${groupIndex}: ${match}`);
      vReturn = match;
    });
  }
  return vReturn;
}

function zbxeMoveFilterButton() {
  let filterButton = document.getElementsByClassName('filter-btn-container')[0];
  if (typeof filterButton != 'undefined') {
    filterButton.children[0].children[0].innerText = "";
    translations = [];
    translations[ 'filter'] = "filllterr";
    filterButton.setAttribute('Title', translations['filter']);
    //alert(filterButton.children[0].children[0].innerText);
    let titleBar = document.getElementsByClassName("header-title")[0];

    filterDIV = document.getElementById('filter-space');
    if (titleBar !== undefined) {
      titleBar.appendChild(filterButton);
      zbxeConsole('Filter button moved option !');
    }
    if (window.location.pathname.indexOf('everyz.php')) {
      var style = '<style type="text/css">' + " {"
      + " .ui-tabs-nav .filter-trigger li a:hover { text-decoration: none; color: #000000; background-color: #33B5E5; padding: 1px 34px 7px 10px; }"
      + " .filter-btn-container{ position: relative; text-align: left; width: 22px; margin-left: -10px; } "
      + " #ui-id-1{ padding: 6px 35px 0px 0px; } "
      +"} " + "</style>";
    }
  } else {
    zbxeConsole('Screen without filter option!');
  }
}

function zbxeSetContentInfo() {
  let contentInfo = document.getElementsByTagName("footer")[0];
  if (typeof contentInfo != 'undefined') {
    let title = (ZABBIXVERSIONS.includes(zbxeCheckZabbixVersion(contentInfo.innerHTML)) ? "" : 'title="Not tested for this zabbix version."');
    contentInfo.innerHTML += ' | <a class="grey link-alt" target="_blank" '+title+' href="http://www.everyz.org/">EveryZ '+EVERYZVERSION+'</a>';
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
  return false;
}

// Original code from js/common.js  file from Zabbix 3.4
function zbxePopUp(url, width, height, form_name) {
  if (!width) {
    width = 1024;
  }
  if (!height) {
    height = 768;
    let contentInfo = document.getElementsByTagName("footer")[0];
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

function zbxeConsole(msg) {
  console.log('EveryZ - ' + msg);
}

window.onload = function () {
  zbxeSetContentInfo();
  zbxeConsole('Loaded');
  //zbxeMoveFilterButton();
}
