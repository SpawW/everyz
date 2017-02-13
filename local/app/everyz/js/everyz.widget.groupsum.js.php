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
$divName = "body-$moduleName";
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
    return '[]';
}

/* * ***************************************************************************
 * Get Data
 * ************************************************************************** */
?>
<style>

    /*
    .chart rect {
      fill: steelblue;
    }
    */
    .chart .legend {
        fill: black;
        font: 14px sans-serif;
        text-anchor: start;
        font-size: 12px;
    }

    .chart text {
        fill: white;
        font: 10px sans-serif;
        text-anchor: end;
    }

    .chart .label {
        fill: black;
        font: 14px sans-serif;
        text-anchor: end;
    }

    .bar:hover {
        fill: brown;
    }

    .axis path,
    .axis line {
        fill: none;
        stroke: #000;
        shape-rendering: crispEdges;
    }


</style>

<script src="http://d3js.org/d3.v3.min.js"></script>
<script>
    vDiv = document.getElementById("<?php echo $divName;?>");
    vDiv.innerHTML = '<svg class="chart"></svg>';
    var data = {
        labels: [
            'resilience', 'maintainability', 'accessibility',
            'uptime', 'functionality', 'impact'
        ],
        series: [
            {
                label: '2012',
                values: [4, 8, 15, 16, 23, 42]
            },
            {
                label: '2013',
                values: [12, 43, 22, 11, 73, 25]
            },
            {
                label: '2014',
                values: [31, 28, 14, 8, 15, 21]
            }, ]
    };

    var chartWidth = 300,
            barHeight = 20,
            groupHeight = barHeight * data.series.length,
            gapBetweenGroups = 10,
            spaceForLabels = 150,
            spaceForLegend = 150;

// Zip the series data together (first values, second values, etc.)
    var zippedData = [];
    for (var i = 0; i < data.labels.length; i++) {
        for (var j = 0; j < data.series.length; j++) {
            zippedData.push(data.series[j].values[i]);
        }
    }

// Color scale
    var color = d3.scale.category20();
    var chartHeight = barHeight * zippedData.length + gapBetweenGroups * data.labels.length;

    var x = d3.scale.linear()
            .domain([0, d3.max(zippedData)])
            .range([0, chartWidth]);

    var y = d3.scale.linear()
            .range([chartHeight + gapBetweenGroups, 0]);

    var yAxis = d3.svg.axis()
            .scale(y)
            .tickFormat('')
            .tickSize(0)
            .orient("left");

// Specify the chart area and dimensions
    var chart = d3.select(".chart")
            .attr("width", spaceForLabels + chartWidth + spaceForLegend)
            .attr("height", chartHeight);

// Create bars
    var bar = chart.selectAll("g")
            .data(zippedData)
            .enter().append("g")
            .attr("transform", function (d, i) {
                return "translate(" + spaceForLabels + "," + (i * barHeight + gapBetweenGroups * (0.5 + Math.floor(i / data.series.length))) + ")";
            });

// Create rectangles of the correct width
    bar.append("rect")
            .attr("fill", function (d, i) {
                return color(i % data.series.length);
            })
            .attr("class", "bar")
            .attr("width", x)
            .attr("height", barHeight - 1);

// Add text label in bar
    bar.append("text")
            .attr("x", function (d) {
                return x(d) - 3;
            })
            .attr("y", barHeight / 2)
            .attr("fill", "red")
            .attr("dy", ".35em")
            .text(function (d) {
                return d;
            });

// Draw labels
    bar.append("text")
            .attr("class", "label")
            .attr("x", function (d) {
                return -10;
            })
            .attr("y", groupHeight / 2)
            .attr("dy", ".35em")
            .text(function (d, i) {
                if (i % data.series.length === 0)
                    return data.labels[Math.floor(i / data.series.length)];
                else
                    return ""
            });

    chart.append("g")
            .attr("class", "y axis")
            .attr("transform", "translate(" + spaceForLabels + ", " + -gapBetweenGroups / 2 + ")")
            .call(yAxis);

// Draw legend
    var legendRectSize = 18,
            legendSpacing = 4;

    var legend = chart.selectAll('.legend')
            .data(data.series)
            .enter()
            .append('g')
            .attr('transform', function (d, i) {
                var height = legendRectSize + legendSpacing;
                var offset = -gapBetweenGroups / 2;
                var horz = spaceForLabels + chartWidth + 40 - legendRectSize;
                var vert = i * height - offset;
                return 'translate(' + horz + ',' + vert + ')';
            });

    legend.append('rect')
            .attr('width', legendRectSize)
            .attr('height', legendRectSize)
            .style('fill', function (d, i) {
                return color(i);
            })
            .style('stroke', function (d, i) {
                return color(i);
            });

    legend.append('text')
            .attr('class', 'legend')
            .attr('x', legendRectSize + legendSpacing)
            .attr('y', legendRectSize - legendSpacing)
            .text(function (d) {
                return d.label;
            });

</script>

<?php


