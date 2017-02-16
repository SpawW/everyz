/*
 * 
 */
function newD3Pie(container, data, tooltipStandard, showLabels, canvasWidth, canvasHeight) {
    showLabels = showLabels || true;
    canvasWidth = canvasWidth || 350;
    canvasHeight = canvasHeight || 200;
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
