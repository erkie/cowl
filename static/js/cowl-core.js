
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
	}
};
