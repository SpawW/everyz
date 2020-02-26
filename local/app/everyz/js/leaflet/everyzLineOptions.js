L.everyzLineOptions = LeafletToolbar.ToolbarAction.extend({
  options: {
    toolbarIcon: { className: "fa fa-bars" }
  },

  initialize: function(map, shape, options) {
    this._map = map;
    this._shape = shape;
    this._shape.options.editing = this._shape.options.editing || {};
    LeafletToolbar.ToolbarAction.prototype.initialize.call(this, map, options);
  },

  enable: function() {
    var map = this._map,
      shape = this._shape;
    console.log("everyzLineOptions.ExtendedEdit");
    // console.log(shape);

    everyzObj.currentElement = shape;
    everyzObj.map.removeLayer(this.toolbar);
    everyzObj.dialog.options.size = [450, 320];
    everyzObj.dialog.setContent(templatePopUp["polyline"]);
    function waitForElementToDisplay(selector, time, loadTriggers) {
      objSelector = document.querySelector(selector);
      if (objSelector != null) {
        console.log(`Found selector ${selector}`);
        loadTriggers(objSelector);
        return;
      } else {
        setTimeout(function() {
          console.log(`Waiting for selector ${selector}`);
          waitForElementToDisplay(selector, time);
        }, time);
      }
    }
    waitForElementToDisplay("#linkTrigger", 300, function(selector) {
      jQuery.ajax({
        type: "POST",
        url: "everyzjsrpc.php?type=11&method=host.triggers.get",
        data: "hostid=" + hostData["hostid"],
        beforeSend: function() {},
        success: function(obj) {
          // console.clear();
          let JSONObj = JSON.parse(obj);
          let newOption = [];
					console.log(everyzObj.currentElement.zbxe);

					selector.options[0] = new Option("", "");
          JSONObj.result.forEach(el => {
            // console.log(el);
            newOption = new Option(el.description, el.triggerid);
            newOption.setAttribute("style", `color: ${severityColors[el.priority]};`);
						selector.options[selector.options.length] = newOption;						
					});
					selector.value = everyzObj.currentElement.zbxe.trigger;
        }
      });
    });
    updateLineOptions(shape);
    map.on("click", function() {
      shape.editing.disable();
      everyzObj.dialog.close();
    });
  }
});
