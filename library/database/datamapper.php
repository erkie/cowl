<?php

class MapperNotFoundException extends Exception {}
class MapperObjectNotFoundException extends Exception {}
class MapperNoTableException extends Exception {}

/*
	Abstract Class:
		DataMapper
	
	A base-class for mappers. Responsible for abstracting database logic from <DomainObjects>. 
*/

abstract class DataMapper
{
	/*
		Property: DataMapper::$table
		
		This property _must be_ overwritten in base classes. It should contain the name of the table for which the mapper maps.
	*/
	
	protected $table;
	
	/*
		Property: DataMapper::$primary_key
		
		Contains the name of the primary key for the table. The default value is "id". The <DataMapper::$table> and <DataMapper::$primary_key> are simply passed on to the <QueryBuilder>.
	*/
	
	protected $primary_key = 'id';
	
	// Property: DataMapper::$driver
	// Contains the database driver which this mapper uses.
	protected $driver = 'mysql';
	
	/*
		Property:
			DataMapper::$state
		
		Stores states for a query that is built in <DataMapper::find>.
	*/
	
	protected $state = array('order' => null, 'offset' => null, 'amount' => null, 'args' => null);
	
	// Property: DataMapper::$state_dirty
	// A bool value set to true if the <DataMapper::$state>-array has been changed.
	
	protected $state_dirty = false;
	
	// Property: DataMapper::$builder
	// An instance to a <QueryBuilder> pointed to this mapper.
	protected $builder;
	
	// Property: DataMapper::$instances
	// A simple registry that contains intances of mappers.
	private static $instances = array();
	
	// Property: DataMapper::$mappers_dir
	// The directory in which mappers are contained. Note that the filename of a mapper must follow the pattern "mapper.NAME.php"
	private static $mappers_dir = 'mappers/';
	
	// Property: DataMapper::$objects_dir
	// The directory in which objects are contained. The filename must follow the pattern "object.NAME.php". An object MUST have a corresponding mapper. The directory of the mappers and objects can be the same.
	private static $objects_dir = 'objects/';
	
	// Property: DataMapper::$object_name
	// Holds the the name of the DomainObject for which the current mapper maps.
	protected $object_name;
	
	/*
		Constructor:
			DataMapper::__construct
		
		Enforces the rule that a mapper must define a <DataMapper::$table>.
	*/
	
	public function __construct()
	{
		if ( is_null($this->table) || empty($this->table) )
		{
			throw new MapperNoTableException(get_class($this));
		}
		
		Database::loadDriver($this->driver);
		
		// Remove the word "Mapper" from classname
		$this->object_name = substr(get_class($this), 0, -6);
		$querybuilder = $this->driver . 'QueryBuilder';		
		$this->builder = new $querybuilder($this->table, $this->primary_key);
	}
	
	/*
		Method:
			DataMapper::setMappersDir
		
		Sets the <DataMapper::$mappers_dir>.
		
		Parameters:
			$dir - A directory in which the mappers lay.
	*/
	
	public static function setMappersDir($dir)
	{
		self::$mappers_dir = $dir;
	}
	
	/*
		Method:
			DataMapper::setObjectsDir
		
		Sets the <DataMapper::$objects_dir>.
		
		Parameters:
			$dir - A directory in which the objects lay.
	*/
	
	public static function setObjectsDir($dir)
	{
		self::$objects_dir = $dir;
	}
	
	/*
		Method:
			DataMapper::populate
		
		Populates a <DomainObject> based upon defined fields in the $object.
		If no entry is found the object will be set as erroneous.
		
		(begin code)
		
		// Using a field for population
		$post = new Post();
		$post->slug = $_GET['slug'];
		$mapper->populate($post);
		
		// $post corresponds to the database entry with the slug of $_GET['slug']
		
		// Short hand form if the $id is known
		$post = $mapper->populate(new Post($id));
		
		// $post now contains the fields of a database entry with id $id
		
		(end code)
		
		Parameters:
			DomainObject $object - The <DomainObject> to populate.
		
		Returns:
			The <DomainObject> $object passed originally.
	*/
	
	public function populate(DomainObject $object)
	{
		$db = Current::db($this->driver);
		
		Current::$plugins->hook('dbPopulate', $this, $object);
		
		$query = $this->builder->buildFindObject($object);
		
		$result = $db->execute($query);
		$this->populateFromDBResult($object, $result);
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
		
		return $object;
	}
	
	/*
		Method:
			DataMapper::find
		
		Executes a SELECT-statement after building the query with <QueryBuilder>. Will flush the <DataMapper::$state>.
		
		(begin code)
		
		// Fetch all posts with thread_id 10, order by id and limit 0, 10
		$posts = $postmapper->find(array('thread_id' => 10), 'id', 0, 10);
		
		// Same query as about, more intuitive code
		$posts = $postmapper->by('id')->limit(0, 10)->find(array('thread_id' => 10));
		
		// Fetch all posts
		$posts = $postmapper->find('all');
		
		// Fetch all posts limited to 0, 10
		$posts = $postmapper->limit(0, 10)->find('*');
		
		// Fetch all posts sorted by id
		$posts = $postmapper->by('time_created')->find('all');
		
		(end code)
		
		Parameters:
			$args - An array of args to filter the statement with. To find all records without filtering, the string 'all', or '*' _must_ be passed.
			$order - A string, or an array, of fields to sort the query by. Can also be set by <DataMapper::by>.
			$offset - The offset of the LIMIT. Can also be set by <DataMapper::limit>
			$amount - The amount of the LIMIT. Can also be set by <DataMapper::limit>
		
		Returns:
			A <DomainCollection> instance.
	*/
	
	public function find($args = null, $order = '', $offset = null, $amount = null)
	{
		$db = Current::db($this->driver);
		
		$func_args = func_get_args();
		Current::$plugins->hook('dbFind', $this, $func_args);
		
		if ( $this->state_dirty )
		{
			if ( is_null($args) )
			{
				$args = $this->state['args'];
			}
			
			$order = $this->state['order'];
			$offset = $this->state['offset'];
			$amount = $this->state['amount'];
			
			$this->state_dirty = false;
			$this->state = array('args' => array(), 'order' => null, 'offset' => null, 'amount' => null);
		}
		
		$query = $this->builder->buildFind($args, $order, $offset, $amount);
		$result = $db->execute($query);
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
		
		return new DomainCollection($result, $this);
	}
	
	// Alias: DataMapper::fetch
	// See <DataMapper::find>.
	
	public function fetch()
	{
		$args = func_get_args();
		return call_user_func_array(array($this, 'find'), $args);
	}
	
	/*
		Method:
			DataMapper::filter
				
		Sets the current args state of the querybuilder to SELECT $args. This method is able to chain mapper calls for a more intuitive API.
		
		(begin code)
		
		$posts = $postmapper->filter(array('id' => 10))->by('created')->limit(10, 10)->find();
		
		(end code)
	*/
	
	public function filter($args)
	{
		$this->state_dirty = true;
		
		$this->state['args'] = $args;
		return $this;
	}
	
	/*
		Method:
			DataMapper::by
		
		Set the current state of the querybuilder to ORDER BY $by. Used to chain mapper-calls for a more intuituve API. This method has to be called before <DataMapper::find>
		
		If the number of arguments is larger than one, the func_get_args array is added.
		
		(begin code)
		
		$mapper = DataMapper::get('post');
		$posts = $mapper->by('id')->find('all');
		
		foreach ( $posts as $post )
		{
			echo $post->header . PHP_EOL;
			echo $post->message . PHP_EOL;
		}
		
		// Other examples
		
		$mapper->by('modified DESC', 'name');
		$mapper->by(array('modfied DESC', 'name')); // Will yield the same result
		
		(end code)
		
		Parameters:
			$by - The string (or array) to build the values from.
		
		Returns:
			$this for chainability.
	*/
	
	public function by($by)
	{
		$this->state_dirty = true;
		
		$args = func_get_args();
		$this->state['order'] = (count($args) > 1) ? $args : $by;
		
		return $this;
	}
	
	/*
		Method:
			DataMapper::limit
		
		Sets the limit state to the passed $limit. Used to chain mapper calls for a more intuitive API. This method has to be called before <DataMapper::find>.
		
		(begin code)
		
		// SELECT  * FROM table ORDER BY id LIMIT 10, 10
		$objects = $mapper->by('id')->limit(10, 10)->find('all');
		
		// SELECT * FROM table LIMIT 10
		$objects = $mapper->limit(10, 10)->find('all');
		
		(end code)
		
		Parameters:
			$offset - The offset, or if $amount is left null, how many to limit.
			$amount - How many to limit.
		
		Returns:
			$this for chainability.
	*/
	
	public function limit($offset, $amount = null)
	{
		$this->state_dirty = true;
		
		$this->state['offset'] = $offset;
		$this->state['amount'] = $amount;		
		return $this;
	}
	
	/*
		Method:
			DataMapper::uptodate
		
		Keeps a <DomainObject> up-to date by either inserting or updating it, depedning on whether it has an ID. See <DataMapper::insert> or <DataMapper::update> for more details about the operations.
		
		(begin code)
		
		$post = $map->populate(new Post($id));
		$post->header = $post->header . ' updated!';
		$post->message = 'The message has been lost due to an example. Please direct your criticism to a brick wall.';
		$map->uptodate($post);
		
		// Updates the entry in the database
		
		$post = new Post();
		$post->set("header", "Posts keep disappearing");
		$post->set("message", "Why do my posts disappearing?! Rabble rabble rabble peas and carrots!");
		$map->uptodate($post);
		
		// Inserts the post into the database.
		
		(end code)
		
		Parameters:
			DomainObject $object - The <DomainObject> to update.
		
		Returns:
			The <DomainObject> $object originally passed.
	*/
	
	public function uptodate(DomainObject $object)
	{
		return ( ! $object->getID() ) ? $this->insert($object) : $this->update($object);
	}
	
	/*
		Method:
			DataMapper::insert
		
		Inserts the $object into the database. It uses the fields with a default value and values present to build the query, so you must properly populate the object before inserting.
		
		Parameters:
			DomainObject $object - The object to insert into the database.
		
		Returns:
			The passed <DomainObject> $object.
	*/
	
	public function insert(DomainObject $object)
	{
		// Ensure data integrity
		$object->ensure();
		
		$db = Current::db($this->driver);
		
		Current::$plugins->hook('dbInsert', $this, $object);
		
		$query = $this->builder->buildInsert($object);
		$result = $db->execute($query);
		
		$object->setID($result->getID());
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
		
		return $object;
	}
	
	/*
		Method:
			DataMapper::update
		
		Updates the $object. Sets new values for all present values in the $object. Be sure to set the ID property of the $object before updating.
		
		Parameters:
			DomainObject $object - The $object to update.
		
		Returns:
			The <DomainObject> to update.
	*/
	
	public function update(DomainObject $object)
	{
		// Ensure data integrity
		$object->ensure();
		
		$db = Current::db($this->driver);
		
		Current::$plugins->hook('dbUpdate', $this, $object);
		
		$query = $this->builder->buildUpdate($object);
		$result = $db->execute($query);
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
		
		return $object;
	}
	
	/*
		Method:
			DataMapper::remove
		
		Removes an entry from the table.
		
		(begin code)
		
		// With just an ID
		$map->remove($id);
		
		// With an object
		$post = new Post($id);
		$map->populate($post);
		
		$map->remove($post);
		
		(end code)
		
		Parameters:
			$id - The ID to remove. Can also be a <DomainObject>-instance with an <DomainObject::$id>.
	*/
	
	public function remove($id)
	{
		$db = Current::db($this->driver);
		
		Current::$plugins->hook('dbRemove', $this, $id);
		
		$query = $this->builder->buildDelete($id);
		$result = $db->execute($query);
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
	}
	
	/*
		Method:
			DataMapper::count
		
		Will count the number of rows sorted and offsetted using the current <DataMapper::$state>. This method will not flush the state-array.
		
		Examples:
			$total = $postmapper->filter('all')->count();
			echo $total; // 102 posts
		
		Parameters:
			See <DataMapper::find>
		
		Returns:
			The number of rows found.
	*/
	
	public function count($args = null, $order = '', $offset = null, $amount = null)
	{
		if ( $this->state_dirty )
		{
			if ( is_null($args) )
			{
				$args = $this->state['args'];
			}
			$order = $this->state['order'];
			$offset = $this->state['offset'];
			$amount = $this->state['amount'];
		}
		
		$db = Current::db($this->driver);
		
		$query = $this->builder->buildCount($args, $order, $offset, $amount);
		$ret = end($db->execute($query)->row());
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
		
		return $ret;
	}
	
	/*
		Method:
			DataMapper::query
		
		Run a databasecentric query returning the results in a <DomainCollection>
		
		Examples:
			$posts = $postmapper->query('
				SELECT * FROM %(table) AS %(prefix)
				
				ORDER BY %(field->primary_key) DESC
			');
			
			foreach ( $posts as $post )
				$post;
		
		Parameters:
			string $query - The query to be run on Current::db($this->driver);
			array $data - An associative array of data that will be sent to <QueryBuilder::format>
	*/
	
	public function query($query, $data = array())
	{
		$db = Current::db($this->driver);
		
		$query = $this->builder->format($query, $data);
		$result = $db->execute($query);
		
		Current::$plugins->hook('postDBQuery', $this, $query, $db);
		
		return new DomainCollection($result, $this);
	}
	
	/*
		Method:
			DataMapper::populateFromDBResult
		
		Loops through $result's first row and inserts every field into the passed <DomainObject>.
		Will set the object as erroneous if the row is empty.
		
		Parameters:
			DomainObject $object - The object to populate.
			DBResult $result - The DBResult with a row for the $object.
			
		Returns:
			Returns the passed $object if there was a row to populate.
	*/

	public function populateFromDBResult(DomainObject $object, DBResult $result)
	{
		$row = $result->row();
		return $row ? $this->populateFromRow($object, $row) : (bool)$object->setError();
	}
	
	/*
		Method:
			DataMapper::populateFromRow
		
		Loops through the $row's fields and populates them to a <DomainObject>-instance.
		
		Parameters:
			Domainobject $object - The object to populate.
			array $row - The row fetched from the database whos values will be added the the $object.
		
		Returns:
			The passed $object.
	*/
	
	public function populateFromRow(DomainObject $object, array $row)
	{
		foreach ( $row as $field => $value )
		{
			if ( $field == 'id' ) $object->setID($value);
			else $object->setRaw($field, $value);
		}
		$object->initialize();
		return $object;
	}
	
	/*
		Method:
			DataMapper::createObject
		
		Create a <DomainObject>-object that maps to the current mapper.
		
		Returns:
			The new instance.
	*/
	
	public function createObject()
	{
		return new $this->object_name;
	}
	
	/*
		Method:
			DataMapper::getTable
		
		Get the mapper's table name.
		
		Returns:
			Returns the <DataMapper::$table>
	*/
	
	public function getTable()
	{
		return $this->table;
	}
	
	/*
		Method:
			DataMapper::get
		
		Returns an instance to the passed $mapper. If the class of the passed name it will be included from the <DataMapper::$mappers_dir>. Corresponding <DomainObject>s will also be included.
		
		Parameters:
			$mapper - The name of the mapper (_without_ the Mapper-postfix).
		
		Returns:
			An instance of the $mapper.
	*/
	
	public static function get($mapper)
	{
		$key = strtolower($mapper);
		
		// Include corresponding mapper-class
		if ( ! isset($instances[$key]) )
		{
			$mappername = $mapper . 'Mapper';
			if ( ! class_exists($mappername) )
			{
				self::includeMapper($mapper);
			}
			self::$instances[$key] = new $mappername;
		}
		
		// Include DomainObject-class
		if ( ! class_exists($mapper) )
		{
			self::includeObject($mapper);
		}
		
		return self::$instances[$key];
	}
	
	/*
		Method:
			DataMapper::includeMapper
		
		Includes the $mapper from the <DataMapper::$mappers_dir>. Will throw a <MapperNotFoundException> if it is not found.
		
		Parameters:
			$mapper - The name of the mapper.
	*/
	
	private static function includeMapper($mapper)
	{
		$filename = self::$mappers_dir . sprintf('mapper.%s.php', strtolower($mapper));
		if ( file_exists($filename) )
		{
			require($filename);
		}
		else
		{
			throw new MapperNotFoundException($mapper);
		}
	}
	
	/*
		Method:
			DataMapper::includeObject
		
		Includes the $object from the <DataMapper::$objects_dir>. Will throw a <MapperObjectNotFoundException> if it is not found.
		
		Parameters:
			$object - The name of the object.
	*/
	
	private static function includeObject($object)
	{
		$filename = self::$objects_dir . sprintf('object.%s.php', strtolower($object));
		if ( file_exists($filename) )
		{
			require($filename);
		}
		else
		{
			throw new MapperObjectNotFoundException($object);
		}
	}
}

