
/*
	Object:
		<Cowl>
	
	Contains anything and everything for a site in Cowl.
*/

var Cowl = {
	commands: {},
	instances: {},
	
	Command: function(name, props) {
		Cowl.commands[name.toLowerCase()] = new Class(props);
	},
	
	fire: function(command, method) {
		command = command.toLowerCase();
		if ( Cowl.commands[command] ) {
			window.addEvent('domready', function() {
				Cowl.run(command, method);
			});
		}
	},
	
	run: function(command, method) {
		var instance = new Cowl.commands[command]();
		if ( instance[method] ) {
			instance[method]();
		}
		this.instances[command] = instance;
	},
	
	url: function() {
		return COWL_BASE + $A(arguments).join('/');
	},
	
	/*
		Method:
			<Cowl.loadToPage>
		
		Loads a URL and with the JSON from results searches for elements with the attribute tpl-name and replaces the contents of them with it.
		
		Parameters:
			(string) URL - The URL to load
			(function) callback - Optional callback that is called when everything is done
	*/
	
	load: function(url, callback) {
		var req = new Request.JSON({
			onSuccess: function(data) {
				Cowl.templateReplace(data);
				if ( typeof callback == 'function' )
					callback(data);
			}
		});
		req.get(url);
	},
	
	/*
		Method:
			<Cowl.templateReplace>
		
		Replaces the contents of elements on the current page with the attribute tpl-name with their respective entry in data.
		
		Parameters:
			(object) data - The data to find and replace
	*/
	
	templateReplace: function(data) {
		// Get elements with tpl-name properties
		var elements = $$('*:tpl-name').each(function(element) {
			var keys = element.get('tpl-name').split('.');
			
			var value = data[keys[0]], i = 1;
			while ( typeof value != 'undefined' && keys[i] )
				value = value[keys[i++]];
			
			if ( value )
				element.set(element.get('tpl-attribute') || 'text', value);
		});
	}
};

Element.implement({
	isVisible: function() {
		try {
			if (this.offsetWidth === 0 || this.offsetHeight === 0)
				return false;
			var height = document.documentElement.clientHeight,
				rects = this.getClientRects(),
				on_top = function(r) {
					var leftDistance = Math.floor((r.right - r.left) / 10);
					var topDistance = Math.floor((r.bottom - r.top) / 10);
					for (var x = Math.floor(r.left), x_max = Math.ceil(r.right); x <= x_max; x += leftDistance )
						for (var y = Math.floor(r.top), y_max = Math.ceil(r.bottom); y <= y_max; y += topDistance ) {
							var el = document.elementFromPoint(x, y);
							if ( el && el.is(this) )
								return true;
						}
					return false;
			};
			for (var i = 0, l = rects.length; i < l; i++) {
				var r = rects[i],
					in_viewport = r.top > 0 ? r.top <= height : (r.bottom > 0 && r.bottom <= height);
				if (in_viewport && on_top(r)) return true;
			}
			return false;
		} catch ( e ) {
			return false;
		}
	},
	
	is: function(test) {
		if ( typeof test == "string" ) {
			return !!(this.match(test) || this.getParent(test));
		} else {
			if ( this === test ) return true;
			var top = test;
			while ( top.getParent && top !== this ) {
				top = top.getParent();
			}
			return !!top;
		}
	}
});
