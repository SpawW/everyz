<?php

/*
* * Purpose: Show a graphical representation of a network equipemment
* * Adail Horst - http://spinola.net.br/blog
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
* TodoS: ===========================================================================
* */

/* * ***************************************************************************
* Module Variables
* ************************************************************************** */
// Configuration variables =====================================================
$moduleName = "zbxe-netports";
$baseProfile .= $moduleName;
$moduleTitle = 'NetPorts';
// Common fields
addFilterActions();
// Specific fields
addFilterParameter("format", T_ZBX_INT, 0, false, false, false);
// Field validation
check_fields($fields);

/* * ***************************************************************************
* Access Control
* ************************************************************************** */
//checkAccessHost('hostids');

/* * ***************************************************************************
* Module Functions
* ************************************************************************** */

/* * ***************************************************************************
* Get Data
* ************************************************************************** */
$items = API::Item()->get([
	'output' => ['itemid', 'lastvalue'],
	'selectHosts' => ['hostid', 'status', 'hostid', 'lastvalue'],
	'search' => [ 'key_' => 'some.value' ],
	'editable' => true
]);

var_dump($items);

$myItemData = [];

foreach ($items as $key => $value) {
	$myItemData[] = ['y' => $value['lastvalue'], 'legendText' => 'test-'.$value['hosts'][0]['hostid'],'label' => 'test'];
}
$myItemData[] = ['y' => 1, 'legendText' => 'test','label' => 'test'];
$myItemData[] = ['y' => 2, 'legendText' => 'test','label' => 'test'];
var_dump($myItemData);
//{  y: 23.24, legendText:"Google", label: "Google" }

//var_dump($items);

/* * ***************************************************************************
* Display
* ************************************************************************** */

?>
<script src="local/app/everyz/js/d3/d3.v4.min.js" charset="utf-8"></script>
<script type="text/javascript">
window.onload = function () {
	var chart = new CanvasJS.Chart("chartContainer",
	{
		title:{
			text: "Desktop Search Engine Market Share, Dec-2012"
		},
		animationEnabled: true,
		legend:{
			verticalAlign: "center",
			horizontalAlign: "left",
			fontSize: 20,
			fontFamily: "Helvetica"
		},
		theme: "theme2",
		data: [
			{
				type: "pie",
				indexLabelFontFamily: "Garamond",
				indexLabelFontSize: 20,
				indexLabel: "{label} {y}%",
				startAngle:-20,
				showInLegend: true,
				toolTipContent:"{legendText} {y}%",
				dataPoints: [
					{  y: 83.24, legendText:"Google", label: "Google" },
					{  y: 8.16, legendText:"Yahoo!", label: "Yahoo!" },
					{  y: 4.67, legendText:"Bing", label: "Bing" },
					{  y: 1.67, legendText:"Baidu" , label: "Baidu"},
					{  y: 0.98, legendText:"Others" , label: "Others"}
				]
			}
		]
	});
	chart.render();

	var chart2 = new CanvasJS.Chart("chartContainer2",
	{
		title:{
			text: "My custom chart from custom data"
		},
		animationEnabled: true,
		legend:{
			verticalAlign: "center",
			horizontalAlign: "left",
			fontSize: 20,
			fontFamily: "Helvetica"
		},
		theme: "theme2",
		data: [
			{
				type: "pie",
				indexLabelFontFamily: "Garamond",
				indexLabelFontSize: 20,
				indexLabel: "{label} {y}%",
				startAngle:-20,
				showInLegend: true,
				toolTipContent:"{legendText} {y}%",
				dataPoints: <?php
 				echo json_encode($myItemData);
				?>
			}
		]
	});
	chart2.render();

}
</script>
<!--<script type="text/javascript" src="http://canvasjs.com/assets/script/canvasjs.min.js"></script>-->
<script type="text/javascript" src="local/app/everyz/js/canvasjs/canvasjs.min.js"></script>
<?php

commonModuleHeader($moduleName, $moduleTitle, true);

$table->addRow((new CDiv())
->setAttribute('id', "chartContainer")
->setAttribute('style', "height: 300px; width: 100%;")
);
$table->addRow((new CDiv())
->setAttribute('id', "chartContainer2")
->setAttribute('style', "height: 300px; width: 100%;")
);
$table->addRow((new CDiv())
->setAttribute('id', "chart")
->setAttribute('style', "height: 300px; width: 100%;")
);

/* * ***************************************************************************
* Display Footer
* ************************************************************************** */
$form->addItem([ $table]);
$dashboard->addItem($form)->show();
?>
<script type="text/javascript" src="local/app/everyz/js/canvasjs/main.js"></script>
<link rel="stylesheet" type="text/css" href="local/app/everyz/css/canvasjs/style.css"/>
<!--<script type="text/javascript" src="https://bl.ocks.org/arpitnarechania/raw/027e163073864ef2ac4ceb5c2c0bf616/main.js"></script>-->
<!--<link rel="stylesheet" type="text/css" href="https://bl.ocks.org/arpitnarechania/raw/027e163073864ef2ac4ceb5c2c0bf616/style.css"/>-->
<?php
