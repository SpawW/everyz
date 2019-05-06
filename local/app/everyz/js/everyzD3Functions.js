/*
 * * Purpose: Functions to use d3.js
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

function newD3Pie(container, data, tooltipStandard, showLabels, canvasWidth, canvasHeight) {
    showLabels = (showLabels === undefined ? true : showLabels);
    canvasWidth = (canvasWidth === undefined ? 350 : canvasWidth);
    canvasHeight = (canvasHeight === undefined ? 200 : canvasHeight);
    if (showLabels) {
        outerLabel = {"pieDistance": 12};
    } else {
        outerLabel = {format: "none"};
    }
    var pie = new d3pie(container, {
        "footer": {
            "color": "#999999",
            "fontSize": 10,
            "font": "open sans",
            "location": "bottom-left"
        },
        "size": {
            "canvasWidth": canvasWidth,
            "canvasHeight": canvasHeight,
            "pieOuterRadius": "90%"
        },
        "data": {
            "sortOrder": "value-desc",
            "content": data
        },
        "labels": {
            "outer": outerLabel,
            "inner": {
                "hideWhenLessThanPercentage": 3
            },
            "mainLabel": {
                "fontSize": 11
            },
            "percentage": {
                "color": "#ffffff",
                "decimalPlaces": 0
            },
            "value": {
                "color": "#adadad",
                "fontSize": 11
            },
            "lines": {
                "enabled": true
            },
            "truncation": {
                "enabled": true
            }
        },
        "tooltips": {
            "enabled": true,
            "type": "placeholder",
            "string": tooltipStandard
        },
        "effects": {
            "pullOutSegmentOnClick": {
                "effect": "linear",
                "speed": 400,
                "size": 8
            }
        },
        "misc": {
            "gradient": {
                "enabled": true,
                "percentage": 100
            }
        }
    });
}

var gauges = [];
function createGauge(name, label, value, min, max) {
    var config =
            {
                size: 120,
                label: label,
                min: undefined != min ? min : 0,
                max: undefined != max ? max : 100,
                minorTicks: 5
            }

    var range = config.max - config.min;
    config.yellowZones = [{from: config.min + range * 0.75, to: config.min + range * 0.9}];
    config.redZones = [{from: config.min + range * 0.9, to: config.max}];

    gauges[name] = new Gauge(name + "Gauge", config);
    gauges[name].render();
    gauges[name].redraw(value);
}
