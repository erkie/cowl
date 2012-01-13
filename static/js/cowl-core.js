
/*
	Object:
		Cowl
	
	Contains anything and everything for a site in Cowl.
*/

var Cowl = {
	commands: {},
	instances: {},
	templateCallbacks: [],
	
	/*
		Method:
			Cowl.Command
	*/
	
	Command: function(name, props) {
		name = name.toLowerCase();
		Cowl.commands[name] = new Class(Object.merge({Extends: Cowl.CommandClass}, props));
		
		if ( /base$/.test(name) ) {			
			window.addEvent('domready', function() {
				Cowl.run(name, 'index');
			});
		}
	},
	
	/*
		Method:
			Cowl.fire
	*/
	
	fire: function(command, method) {
		var previousInstance = Cowl.previousInstance;
		
		if ( previousInstance ) {
			previousInstance.destroy();
		}
		
		if ( Cowl.hasCommand(command) ) {
			if ( ! Browser.loaded )
				window.addEvent('domready', function() {
					this.previousInstance = Cowl.run(command, method);
				}.bind(this));
			else
				this.previousInstance = Cowl.run(command, method);
		}
	},
	
	/*
		Method:
			Cowl.run
		
		Run a command
		
		Parameters:
			command - The name of the command, case insensitive
			method - The method to run
		
		Return:
			Returns the newly created instance
	*/
	
	run: function(command, method) {
		var cmd = Cowl.commands[command.toLowerCase()];
		var instance = new cmd();
		if ( instance[method] ) {
			instance[method]();
		}
		this.instances[command] = instance;
		return instance;
	},
	
	/*
		Method:
			Cowl.hasCommand
		
		Check wether a particular command exists
	*/
	
	hasCommand: function(command) {
		return !! Cowl.commands[command.toLowerCase()];
	},
	
	/*
		Method:
			Cowl.loadCommand
		
		Load a command.
		
		Parameters:
			command - The command
			callback - Callback for when the command is loaded
	*/
	
	loadCommand: function(command, callback) {
		if ( this.hasCommand(command) ) {
			callback();
			return;
		}
		
		var pieces = command.split('.');
		var last = pieces.pop();
		
		var url = Cowl.url('app', 'js', pieces.join('/') + '/page.' + last + '.js');
		var script = new Asset.javascript(url, {
			onLoad: function() {
				callback();
			}
		});
	},
	
	/*
		Method:
			Cowl.getInstance
		
		Get an active instance of a command.
		
		Parameters:
			command - The name of the command
	*/
	
	getInstance: function(command) {
		return Cowl.instances[command.toLowerCase()];
	},
	
	/*
		Method:
			Cowl.get
		
		Alias for <Cowl.getInstance>.
	*/
	
	get: function(command) {
		return Cowl.getInstance(command);
	},
	
	/*
		Method:
			Cowl.url
		
		Works exactly as PHP <Cowl::url>. Pass each piece as an argument and it will be joined with a '/'. The project <BASE_PATH> will be applied to the url.
		
		Parameters:
			(array) arr - Optional array to use
			mixed many pieces - If arr is not an array, the arguments-array is used.
		
		Returns:
			string - The url
	*/
	
	url: function(arr) {
		var data = typeOf(arr) == 'array' ? arr : arguments;
		return COWL_BASE + Array.from(data).join('/');
	},
	
	/*
		Method:
			Cowl.load
		
		Loads a URL and with the JSON from results searches for elements with the attribute tpl-name and replaces the contents of them with it.
		
		Parameters:
			(string) URL - The URL to load
			(function) callback - Optional callback that is called when everything is done
		
		Returns:
			Returns the Request.JSON object
	*/
	
	load: function(url, callback) {
		var req = new Request.JSON({
			onSuccess: function(data) {
				Cowl.templateReplace(data);
				if ( typeof callback == 'function' )
					callback(data, req);
			}
		});
		req.get(url);
		return req;
	},
	
	/*
		Method:
			Cowl.send
		
		Send a POST request using AJAX.
		
		Parameters:
			(string) URL - The URL to load
			(object) params - The parameters to send as POST variables
			(function) callback - A callback to be called when the request is done

		Returns:
			Returns the Request.JSON object
	*/
	
	send: function(url, params, callback) {
		var req = new Request.JSON({
			onSuccess: function(data) {
				Cowl.templateReplace(data);
				if ( typeof callback == 'function' )
					callback(data, req);
			}
		});
		req.post(params.toQueryString());
		return req;
	},
	
	/*
		Method:
			Cowl.templateReplace
		
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
		
		for ( var i = 0, callback; callback = this.templateCallbacks[i]; i++ )
			callback(data);
	},
	
	/*
		Method:
			Cowl.registerTemplateCallback
		
		Register a callback to be called when <Cowl.templateReplace> is called.
		
		Parameters:
			(function) callback - The callback to call. A data-parameter will be passed to the callback upon execution.
	*/
	
	registerTemplateCallback: function(callback) {
		this.templateCallbacks.push(callback);
	},
	
	/*
		Method:
			post
		
		Redirect the page in a POST request, instead of the normal GET request that simply
		changing document.location would do.
		
		Parameters:
			(string) url - The URL to send the POST request to
			(object) parameters - The parameters to be send in the POST request
			(object) formParameters - Extra parameters set on the new form, optional
	*/
	post: function(url, parameters, formParameters) {
		formParameters = formParameters || {};
		
	    // The rest of this code assumes you are not using a library.
	    // It can be made less wordy if you use one.
	    var form = new Element('form[method=post]', Object.merge(formParameters, {
			action: url
		}));

		Object.each(parameters, function(value, key) {
			var hiddenField = new Element('input[type=hidden]', {
				name: key,
				value: value
			}).inject(form);
	    });

		form.inject(document.body);
	    form.submit();
	}
};

Cowl.CommandClass = new Class({
	element: 'body',
	
	actions: {},
	
	initialize: function() {
		this._events = {};
		this._setElement();
		this._addDelegateEvents();
	},
	
	destroy: function() {
		Object.each(this._events, function(events, eventName) {
			events.each(function(ev) {
				this.element.removeEvent(eventName, ev);
			}, this);
		}, this);
	},
	
	_setElement: function() {
		this.element = document.getElement(this.element || 'body');
	},
	
	_addDelegateEvents: function() {
		Object.each(Object.clone(this.actions), function(method, ev) {
			var pieces = ev.split(' ');
			
			var eventName = pieces.shift();
			var selector = pieces.join(' ');
			
			var delegateEvent = this._makeDelegateEventFor(selector, method);
			
			if ( ! this._events[eventName] )
				this._events[eventName] = [];
			this._events[eventName].push(delegateEvent);
			
			this.element.addEvent(eventName, delegateEvent);
		}, this);
	},
	
	_makeDelegateEventFor: function(selector, method) {
		return function(e) {
			var target = e.target;
			if ( target.is(selector) ) {
				this[method].apply(this, arguments);
			}
		}.bind(this);
	}
});

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
			return !!(this.match(test) || this.getParents().filter(test).length);
		} else {
			if ( this === test ) return true;
			return this.getParents().contains(test);
		}
	},
	
	getClosest: function(selector) {
		return this.match(selector) ? this : this.getParents().filter(selector)[0];
	}
});
