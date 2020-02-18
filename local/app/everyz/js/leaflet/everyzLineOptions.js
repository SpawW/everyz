L.everyzLineOptions = LeafletToolbar.ToolbarAction.extend({
	options: {
		toolbarIcon: { className: 'fa fa-bars' }
	},

	initialize: function (map, shape, options) {
		this._map = map;
		this._shape = shape;
		this._shape.options.editing = this._shape.options.editing || {};
		LeafletToolbar.ToolbarAction.prototype.initialize.call(this, map, options);
	},

	enable: function () {
		var map = this._map,
		shape = this._shape;
		console.log("everyzLineOptionsExtendedEdit"); console.log(shape);
		everyzObj.currentElement = shape;
		everyzObj.map.removeLayer(this.toolbar);
		everyzObj.dialog.options.size = [420,220];
		everyzObj.dialog.setContent(templatePopUp['polyline']);
		updateLineOptions(shape);
		map.on('click', function () {
			shape.editing.disable();
			everyzObj.dialog.close();
		});
	}
});
