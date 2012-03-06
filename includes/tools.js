//This function is used to display/hide the sideInfoWindow
//To use, make sure the object has an id set (ex: <div id="foo">)
function toggle_visibility(id) {
   var e = document.getElementById(id);
   if(e.style.display == 'block')
	  e.style.display = 'none';
   else
	  e.style.display = 'block';
}

// Initialize vector layers to "Layers" pane
function initLayer(url, id) {
	var layer = new esri.layers.ArcGISDynamicMapServiceLayer(url, {id:id, visible:false});
	map.addLayer(layer);
	return layer;
}



(function() {
	var __func__ = 'Omnibox';
	var construct = function(dom_node) {
		var self = {
			keyup: function() {
				
			},
			keydown: function(e) {
				
			},
		};
		
		/** dojo **/
		dojo.connect(dom_node, 'onkeyup', self.keyup);
		dojo.connect(dom_node, 'onkeydown', self.keydown);
		/** -/end/- **/
		
		var public = function() {
		};
		$.extend(public, {
			
		});
		return public;
	};
	var global = window[__func__] = function() {
		if(this !== window) {
			var instance = construct.apply(this, arguments);
			return instance;
		}
		else {
			
		}
	};
	$.extend(global, {
		toString: function() {
			return __func__+'()';
		},
	});
})();