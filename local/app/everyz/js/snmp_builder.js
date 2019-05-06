/* Used for inicial development: snmp_builder.js from GiapNguyen
** Objective: SNMPBuilder Plugin with some improvements to easy install with Zabbix-Extras
** Copyright 2014 - Adail Horst - http://spinola.net.br/blog
** Original info is:
*/
// Copyright (c) 2009 GiapNguyen
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
// 
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
// LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
// OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
//
// This is distributed under Free-BSD licence.

// On click a node on the oid tree
// Make an ajax call to retrieve information and value of oid
// Show result in oidview table
// viewtype: 	0 : auto detect oid is table or not (assume if its name's end with string "Table", it is a table)
//		1 : it's a table.

function clickTree(oid, idx, viewtype, column_names)
{
	if (!oid) return;
	if (!viewtype || viewtype == 0){
		viewtype = 0;
		$('viewtype').checked = false; //sync with viewtype checkbox
	}

	var server_ip = $F($('server_ip'));
	var snmp_version = $F($('snmp_version'));
	var community = $F($('community'));
	var mib = $F($('mib'));
	var get_oid_url = 'zbxe-snmp-builder.php?select=1&output=json';

	var server_port = server_ip.match(/\d+$/); // FIXME?
	if (!server_port) server_port = 161;
	
	oidview._oid = oid;
	oidview._idx = idx;
	new Ajax.Request(get_oid_url, {
		method: 'post',
		parameters: {mib: mib, server_ip: server_ip, server_port: server_port, snmp_version: snmp_version, 
			    community: community, oid: oid, idx: idx, viewtype : viewtype},
		onSuccess: function(transport) {
			var json = transport.responseText.evalJSON();
			if (json.error)
			{
				alert(json.error);
				return;
			}
			
			$('oidinfo').update(json.info);
			switch (json.value.ret)
			{
				case 0: //full information
					// oidview.update(['Oid/Name','Type','Value'],[json.value.row],{tr: onClickOid});
					oidview.update(column_names, [json.value.row], {tr: onClickOid});
					break;
				case 1: // table
					oidview.update(json.value.headers,json.value.rows,{td: onClickCell, th: onClickHeader});
					break;
			}
		}
	});
}

//Make a simple convert from snmp type to a zabbix item
//Support INTEGER , Couter32, Timeticks, STRING.
function convertOid(oid, type)
{
	if (!type) return null; //no type?
	var row;

	var octets = false;
	var p_type = '';
	var p_subtype = '';
	var units = '';
	var delta = '';
	var multiplyer = '';
	var delta = '';
	var descr = oid;

	if (oid.match(/if(In|Out)Octets/i) || oid.match(/if(HCIn|HCOut)Octets/i) ||
	    oid.match(/^\.1\.3\.6\.1\.2\.1\.2\.2\.1\.(10|16)/) ||
	    oid.match(/^\.1\.3\.6\.1\.2\.1\.31\.1\.1\.1\.(6|10)/)
	) {
		octets = true;
	}

	switch (type)
	{
		case 'INTEGER':
		case 'INTEGER32':
		case 'Unsigned32':
		case 'Gauge32':
			p_type = 'Numeric (integer 64bit)';
			p_subtype = 'Decimal';
			break;
		case 'Counter32':
			if (octets) {
				p_type = 'Numeric (float)';
				p_subtype = 'Decimal';
				unuts = 'Bps';
				delta = 'Yes';
			} else {
				p_type = 'Numeric (integer 64bit)';
			}
			break;
		case 'Counter64':
			if (octets) {
				p_type = 'Numeric (float)';
				unuts = 'Bps';
				delta = 'Yes';
			} else {
				p_type = 'Numeric (float)';
				p_subtype = 'Decimal';
			}
			break;		
		case 'Timeticks':
			p_type = 'Numeric (integer 64bit)';
			p_subtype = 'Decimal';
			units = 's';
			multiplyer = '0.01';
			break;
		case 'STRING':
			p_type = 'Text';
			break;
		default:
			p_type = 'Text';
			break;
	}
	// row[1] is a placeholder for item description, filled with oid
	row = [oid, oid ,p_type, p_subtype, units, multiplyer, delta];
	return row;
}

//On click a oid on oidview
//Convert then insert it into itemlist
function onClickOid(e)
{
	var row = this.data[0];
	var item = convertOid(row[0],row[1]);
	if (item)
	{
		itemlist.appendData(item);
		Event.element(e).setStyle('background-color: #ACCEDE');
	}
}


//On click a cell in tableview
//Make an ajax call to retrieve full information of the oid + its index
//Convert then insert it into itemlist
function onClickCell(e)
{
	var server_ip = $F($('server_ip'));
	var snmp_version = $F($('snmp_version'));
	var community = $F($('community'));
	var mib = $F($('mib'));

	var server_port = server_ip.match(/\d+$/); // FIXME?
	if (!server_port) server_port = 161;
	
	var x = Event.element(e).table_x;
	var y = Event.element(e).table_y;
	var descr;

	console.log("ClickSell:", x, "-", y);

	if (x > 0)
	{
		oid = this.headers[x];
		idx = this.data[y][0];
		for (i=1; i < this.headers.length; i++)
		{
			if(this.headers[i].match(/Descr$/))
			{
				descr = this.data[y][i];
				break;
			}
		}
		var get_oid_url = 'zbxe-snmp-builder.php?select=1&output=json';
		new Ajax.Request(get_oid_url, {
			method: 'post',
			parameters: {mib: mib, server_ip: server_ip, server_port: server_port, snmp_version: snmp_version, 
				    community: community, oid: oid, idx: idx},
			onSuccess: function(transport) {
				var json = transport.responseText.evalJSON();
				if (json.error)
				{
					alert(json.error);
					return;
				}
				
				$('oidinfo').update(json.info);
				switch (json.value.ret)
				{
					case 0: //full information
						var item = convertOid(json.value.row[0], json.value.row[1]);
						if (item)
						{
							if (descr)
								item[1] += '(' + descr +')';
							itemlist.appendData(item);
						}
						break;
				}
			}
		});
		Event.element(e).setStyle('background-color: #ACCEDE');
	}
	
}

//On click a header in tableview
//Same with click a cell but we only make an ajax call to retrieve full information of first index,
//Convert then insert it into itemlist then clone the row for rest.
function onClickHeader(e)
{
	var server_ip = $F($('server_ip'));
	var snmp_version = $F($('snmp_version'));
	var community = $F($('community'));
	var mib = $F($('mib'));

	var server_port = server_ip.match(/\d+$/); // FIXME?
	if (!server_port) server_port = 161;
	
	var col_idx = Event.element(e).table_x;
	var get_oid_url = 'zbxe-snmp-builder.php?select=1&output=json';
	var descr_idx;

	console.log ("ClickHeader", col_idx);	// DEBUG

	if (col_idx>0) // first column is index, do nothing
	{
		oid = this.headers[col_idx];
		idx = this.data.first()[0]; 
		var s_idx = new String(idx);
				
		var server_ip = $F($('server_ip'));
		var snmp_version = $F($('snmp_version'));
		var community = $F($('community'));
		var mib = $F($('mib'));
		var get_oid_url = 'zbxe-snmp-builder.php?select=1&output=json';

		var server_port = server_ip.match(/\d+$/);
		if (!server_port) server_port = 161;
		for (i=1; i < this.headers.length; i++) // from 1, 0 is index
		{
			if(this.headers[i].match(/Descr$/))
			{
				descr_idx = i;
				console.log ("descr: ", this.headers[i]); // DEBUG
				break;
			}
		}

		new Ajax.Request(get_oid_url, {
			method: 'post',
			parameters: {mib: mib, server_ip: server_ip, server_port: server_port, snmp_version: snmp_version, 
				    community: community, oid: oid, idx: idx},
			onSuccess: function(transport) {
				var json = transport.responseText.evalJSON();
				if (json.error)
				{
					alert(json.error);
					return;
				}
				
				$('oidinfo').update(json.info);
				switch (json.value.ret)
				{
					case 0: //full information
						var item = convertOid(json.value.row[0],json.value.row[1]);
						if (item)
						{
							var tbody = this.tbody;
							var i = 1; // row 0 is headers FIXME
							this.data.each(function (row){
								item1 = item.clone();
								s_oid1 = item1[0].substr(0,item1[0].length - s_idx.length);
								item1[0] = s_oid1 + row[0];
								if (descr_idx > 0) 
									item1[1] = item1[0] + '(' + row[descr_idx] + ')';
								itemlist.appendData(item1);
								var cell = tbody.getElementsByTagName('TR')[i].getElementsByTagName('TD')[col_idx];
								cell.setStyle('background-color: #ACCEDE');
								i++;
							});
							// Event.element(e).setStyle('background-color: #ACCEDE'); //FIXME do we need it?
						}
						break;
				}
			}.bind(this) // oidView table
		});
	}
	
}

// On click save button
// Send itemlist data and update results 
function onSaveItems(e)
{
	var server_ip = $F($('server_ip'));
	var templateid = $F($('templateid'));
	var snmp_version = $F($('snmp_version'));
	var community = $F($('community'));
	var history = $F($('history'));
	var trends = $F($('trends'));
	var delay = $F($('delay'));
	var graph_create = $F($('graph_create'));
	var graph_name = $F($('graph_name'));
	var graph_width = $F($('graph_width'));
	var graph_height = $F($('graph_height'));
	var graph_type = $F($('graph_type'));
	var graph_func = $F($('graph_func'));
	var draw_type = $F($('draw_type'));
	var yaxisside = $F($('yaxisside'));

	var server_port = server_ip.match(/\d+$/); //FIXME?
	if (!server_port) server_port = 161;
	
	if (itemlist.data.size() === 0)
		return;
	json = itemlist.data.toJSON();
	var get_oid_url = 'zbxe-snmp-builder.php?save=1&output=json';
	new Ajax.Request(get_oid_url, {
		method: 'post',
		parameters: {server_ip: server_ip, server_port: server_port, templateid: templateid, 
			    snmp_version: snmp_version, community: community, oids: json, history: history, 
			    trends: trends, delay: delay,
			    graph_create: graph_create, graph_name: graph_name, graph_width: graph_width, 
			    graph_height: graph_height, graph_type: graph_type, graph_func: graph_func, 
			    draw_type: draw_type, yaxisside: yaxisside},
		onSuccess: function(transport) {
			if (json.error)
			{
				alert(json.error);
				return;
			}
			$('message').update(transport.responseText);
		}	
	});
}

//On click Clear button
//Clear itemlist data
function onClearItems(e)
{
	itemlist.clear();

	// Clear cell highlits
//	document.getElementById("dyntable-oidview").getElementsByTagName("tr")[3].childNodes[6].setStyle('background-color:#BEBEBE')
	var table = document.getElementById('dyntable-oidview').getElementsByTagName("tr"); //('oidview');
	console.log (table); // DEBUG
	if (table) {
	    var numRows = table.length;
	    if (numRows) {
		for (var i=0; i<numRows; i++) {
		    var row = table[i].childNodes;
		    var numCells = row.length;
		    for (var j=0; j<numCells; j++) {
			var cell = row[j];
			if (cell) {
			    if (i == 0) {
				cell.setStyle('background-color:#CDCECD');
			    }
			    else if ((i % 2) == 0) {
				cell.setStyle('background-color:#DEDEDE');
			    } else {
				cell.setStyle('background-color:#EEEEEE');
			    }
			}
		    }
		}
	    }
	}
}

//On click a row of itemlist table
//Remove the row
function onClickItem(e)
{
	var y = Event.element(e).table_y;
	var value = this.data[y];
	this.update(null,this.data.without(value),null);
	// clear cell highlite in oidview
	var oid = value[0];
	var table = oidview.data;
	var tbody = oidview.tbody.getElementsByTagName('TR');
	if (tbody) {
	    if (oidview.observer.tr) { // full information FIXME or === ?check it!!!
		var datarow = tbody[1].getElementsByTagName('TD');  // row 0 is headers FIXME
		console.log ("not table view (full info)");
		if (datarow[0].innerHTML == oid)
		    for (var i = 0; i < datarow.length; i++) {
			datarow[i].setStyle('background-color:#DEDEDE');
		    }
	    } else { // table view
		console.log ("table view");
		var splitOid = oid.match(/:(\w+)\.(\d+)$/); // FIXME check it!!!
		var baseOid = splitOid[1];
		var oid_idx = splitOid[2];		// FIXME only for int index???
	        var numRows = table.length;
		console.log ("baseOid:", baseOid); // DEBUG
		for (var col = 0; col < numRows; col++) {
		    if (oidview.headers[col] == baseOid) // FIXME or ===?
			break;
		}
		if (col) {
		    for (var i = 0; i < numRows; i++) {
			if (oidview.data[i][0] == oid_idx) {
			    var tab_idx = i + 1;				// FIXME row 0 - headers!!!
			    var cell = tbody[tab_idx].getElementsByTagName('TD')[col];
			    if (tab_idx == 0) {
				cell.setStyle('background-color:#CDCECD');
			    } else if ((tab_idx % 2) == 0) {
				cell.setStyle('background-color:#DEDEDE');
			    } else {
				cell.setStyle('background-color:#EEEEEE');
			    }
			    break;
			}
		    }
		}
	    }
	}
}

//On click the viewtype checkbox
function onViewType(e)
{
	var viewtype = $F($('viewtype')); // 0: autodetect, 1:table;
	clickTree(oidview._oid, oidview._idx, viewtype, column_names);
}
