<?php
/*
 * * Purpose: Chart with group information
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
$moduleName = "groupsum";
$baseProfile .= $moduleName;
$moduleTitle = 'Group Sum';
// Common fields
addFilterActions();
// Specific fields
addFilterParameter("format", T_ZBX_INT, 0, false, false, false);
addFilterParameter("hostids", T_ZBX_INT, 0, false, false, false);
// Field validation
check_fields($fields);

/* * ***************************************************************************
 * Access Control
 * ************************************************************************** */
$hosts = checkAccessHost('hostids');

/* * ***************************************************************************
 * Module Functions
 * ************************************************************************** */

function data() {
    return '[
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
		]';
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("body-groupsum",
                {
                    title: {
                        text: "Group Statistics"
                    },
                    animationEnabled: true,
                    legend: {
                        verticalAlign: "center",
                        horizontalAlign: "left",
                        fontSize: 20,
                        fontFamily: "Helvetica"
                    },
                    theme: "theme2",
                    data: <?php echo data(); ?>
                });
        chart.render();
    }
</script>
<script type="text/javascript" src="local/app/everyz/js/canvasjs.min.js"></script> 

<?php


