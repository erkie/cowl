
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
	
	/*
		Method:
			<Cowl.loadToPage>
		
		Loads a URL and with the JSON from results searches for elements with the attribute tpl-name and replaces the contents of them with it.
		
		Parameters:
			(string) URL - The URL to load
			(function) callback - Optional callback that is called when everything is done
	*/
	
	loadToPage: function(url, callback) {
		var req = new Request.JSON({
			onSuccess: function(data) {
				Cowl.templateReplace(data);
				if ( typeof callback == 'function' )
					callback();
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
