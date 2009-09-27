
/*
	Object:
		<Cowl>
	
	Contains anything and everything for a site in Cowl.
*/

var Cowl = {
	// Contains the libraries added
	libs: [],
	
	/*
		Method:
			<Cowl.Class>
		
		Method for adding classes to <Cowl.libs>.
		
		Parameters:
			name - The name(space) of the class.
			props - The new Class([ ... _methods_... });
	*/
	
	Class: function(name, props) {
		var klass = new Class(props);
		this.libs.push(name, klass);
	},
	
	/*
		Method:
			<Cowl.load>
		
		Loads a new instance of a class from <Cowl.libs>. If the klass does not exist, it will attempt to load it, else it will throw an LibraryError exception.
		
		Parameters:
			klass - The name of the klass that was Cowl.Class():d
	*/
	
	load: function(klass) {
		if ( this.libs[klass] ) {
			
		} else {
			
		}
	}
};
