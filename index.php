<?php

	function pr($data){
		echo '<pre>';
		if(!$data){
			var_dump($data);
		}
		else{
			print_r($data);
		}
		echo '</pre>';
	}

	//INSTALL THE FRAMEWORK
	class zSpark_Installer{

		//INITIALIZE INSTALLER
		public function __construct(){

			//CORE PATHS
			define('APP_PATH', 			dirname(__FILE__).'/');			//SET THE PATH FOR THE MAIN APPLICATION
			define('SYSTEM_PATH',		APP_PATH.'system/');			//SET THE SYSTEM PATH
			define('MODEL_PATH', 		APP_PATH.'model/');				//SET THE PATH FOR MODELS
			define('CONTROLLER_PATH', 	APP_PATH.'controller/');		//SET THE PATH FOR CONTROLLERS
			define('PLUGIN_PATH', 		APP_PATH.'plugin/'); 			//SET THE PATH FOR PLUGINS				
			define('APP_NAME', 			basename(dirname(__FILE__))); 	//SET THE NAME OF THE APPLICATION - TAKE FROM THE FOLDER THE FRAMEWORK IS INSTALLED IN
			define('VIEW_PATH', 		APP_PATH.'view/');				//SET THE PATH FOR VIEWS

			$this->show_installer();
			
		}

		//BUILD FRAMEWORK FOLDER SCAFFOLDING
		private function scaffold(){

			//DEFINE FRAMEWORK SCAFFOLD DIRECTORIES
			$directories = array(
				MODEL_PATH,
				CONTROLLER_PATH,
				VIEW_PATH,
				PLUGIN_PATH,
				SYSTEM_PATH,
			);

			//CREATE THE DIRECTORIES
			foreach($directories as $directory){
				mkdir($directory, 0777, true);
			}

			$this->build_config_file();
			$this->build_zSpark();
			$this->build_controller_base();
			$this->build_model_base();
			$this->build_orm_wrapper();
			$this->build_database();

			//CREATE THE HTACCESS FILE
			$this->htaccess();
		
			//CREATE THE HOME CONTROLLER
			$this->home_controller();

			//CREATE THE HOME TEMPLATE
			$this->home_template();

			//CREATE THE DEFAULT HEADER	
			$this->header();

			//CREATE HTE DEFAULT FOOTER
			$this->footer();

			//CREATE THE LOGIN CONTROLLER
			$this->login_controller();

			//CREATE THE LOGIN TEMPLATE
			$this->login_template();

			//CREATE SYSTEM TEMPLATE
			$this->system_template();

			//CREATE THE DEFAULT CSS
			$this->css();	

			//CREATE THE DEFAULT JAVASCRIPT
			$this->javascript();

			//CREATE THE ROUTE FILE
			$this->build_router();


			header('Location: ./');

		}

		private function build_router(){
			$content = "<?php
	
	//LOAD zSpark
	require_once 'system/zSpark.php';

	//START zSpark AND TELL IT TO ROUTE THE REQUESTS
	new zSpark(true);
";
			$this->create_file(APP_PATH.'index.php', $content);
		}

		private function show_installer(){
			if(empty($_POST)){
				$this->installer_template_welcome();
			}
			elseif($_POST['start_install']){
				$this->validate_install();
			}

			//$this->scaffold();
		}

		private function validate_install(){
			$data = $_POST;
			if($_POST['use_db'] == 'on'){
				$link =  mysql_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_password']);
				if(!$link){
					$data['errors'][] = 'Unable to connect to database with provided credentials'; 
				}
				else{
					$select = 	mysql_select_db($_POST['db_name'], $link);
					if(!$link){
						$data['errors'][] = 'Connected to server but could not find database by the name of '.$_POST['db_name']; 
					}
				}
			}

			if($data['errors']){
				$this->installer_template_welcome($data);
				exit;
			}
			else{
				
				$this->scaffold();
				

				

			}
		
		}

		private function build_zSpark(){

			$content = '<?php
	
	session_start();

	function pr($data){
		echo \'<pre>\';
		if(!$data){
			var_dump($data);
		}
		else{
			print_r($data);
		}
		echo \'</pre>\';
	}

	//PRIMARY FRAMEWORK CLASS
	class zSpark {

		//BEGIN PROGRAM
		public function __construct($route = false){

			//SET CONFIG
			$this->config();

			//SET REQUEST
			$this->request();

			//WAS ROUT SPECIFICALLY REQUESTED
			if($route){

				//ROUTE BASED ON THE URL REQUEST
				$this->route();
			}
			
		}

		//SET CONFIG
		public function config(){

			if(!$this->config){
				require_once dirname(__FILE__).\'/config.php\';
				require_once dirname(__FILE__).\'/database.php\';
				$this->config = new zSpark_Config;
				require_once dirname(__FILE__).\'/model_base.php\';
				require_once dirname(__FILE__).\'/orm_wrapper.php\';
				require_once dirname(__FILE__).\'/controller_base.php\';				
			}
			return $this->config;
		}

		//LOAD THE DATABASE
		public function Db($dbname = false){
	
			if(!$this->Db){
				$Db 		= new zSpark_Database;
				$dbconf 		= $this->config()->database();
				if($dbname){
					$dbconf 		= $this->config()->database($dbname);
				}
				$Db->dbname = $dbconf[\'db_name\'];				
				$Db->conn = mysql_pconnect($dbconf[\'db_host\'], $dbconf[\'db_username\'], $dbconf[\'db_password\']) or trigger_error(mysql_error(),E_USER_ERROR);
				mysql_select_db($dbconf[\'db_name\']);
			}

			//DB IS ALREADY CONNECTED
			else{
				if($this->Db->dbname !== $dbname){
					$dbconf 		= $this->config()->database($dbname);
					$Db->dbname 	= $dbconf[\'db_name\'];				
					$Db->conn 		= mysql_pconnect($dbconf[\'db_host\'], $dbconf[\'db_username\'], $dbconf[\'db_password\']) or trigger_error(mysql_error(),E_USER_ERROR);
					mysql_select_db($dbconf[\'db_name\']);
				}
			}

			if(strpos(get_class($this), \'_Model\')){
				return $Db;
			}
			$this->Db = $Db;
			return $this->Db;
		}

		//GET REQUEST VARIABLES
		public function request(){

			//ONLY SET VARS IF THEY NEED TO BE SET
			if(!$this->request){

				//INIT VARS
				$request_parts 		= explode(\'/\', $_SERVER[\'REQUEST_URI\']);
				$filtered_parts 	= array();

				//FILTER OUT EMPTY VALUES
				foreach($request_parts as $part){
					if($part !== \'\'){
						$filtered_parts[] = $part;
					}
				}

				$request_parts 		= $filtered_parts;
				$beginning_found 	= false;
				$request 			= new stdClass;
				$request->raw 		= $request_parts;
				
				if(!in_array(APP_NAME, $request_parts)){
					foreach($request_parts as $part){
						//SET CONTROLLER
						if(!isset($request->controller)){											
							$request->controller = $part;
							continue;
						}

						//SET METHOD/TEMPLATE
						if(!isset($request->method)){
							$request->method = $part;
							continue;
						}
						
						//SET GET VARS YOU CAN USE variable:value IN A PATH LIKE A $_GET VARIABLE
						if(strpos($part, \':\')){
							$sub_parts = explode(\':\', $part);
							$request->vars[$sub_parts[0]] = $sub_parts[1];
						}
						else{
							$request->vars[] = $part;
						}
					}
				}
				else{
					//CYCLE THE REQUEST PARTS
					foreach($request_parts as $part){
						
						//FIND THE APPLICATION IN THE PATH AND SKIP TO THE NEXT PART
						if($part == APP_NAME){
							$beginning_found = true;
							continue;
						}

						//SKIP THE PART IF THE BEGINNING HASNT BEEN FOUND
						if(!$beginning_found){
							continue;
						}

						//SET CONTROLLER
						if(!isset($request->controller)){											
							$request->controller = $part;
							continue;
						}

						//SET METHOD/TEMPLATE
						if(!isset($request->method)){
							$request->method = $part;
							continue;
						}
						
						//SET GET VARS YOU CAN USE variable:value IN A PATH LIKE A $_GET VARIABLE
						if(strpos($part, \':\')){
							$sub_parts = explode(\':\', $part);
							$request->vars[$sub_parts[0]] = $sub_parts[1];
						}
						else{
							$request->vars[] = $part;
						}				
					}
				}
				
				//DEFAULT TO HOME CONTROLLER
				if(!file_exists(CONTROLLER_PATH.$request->controller.\'_Controller.php\' && file_exists(VIEW_PATH.\'Home/\'.$request->controller.\'.php\'))){

					//CHECK FOR VIEW PATH BEFORE SETTING CONTROLLER
					if(!file_exists(VIEW_PATH.$request->controller)){
						$request->method = $request->controller;
						$request->controller = \'Home\';
					}
				}

				$this->request = $request;
			}

			return $this->request;
		}

		//ROUTE REQUESTS
		public function route(){

			//DEFAULT TO HOME CONTROLLER
			$controller_name 	= \'Home\';
			$method 			= \'index\';	
			
			//IF A CONTROLLER NAME EXISTS
			if($this->request()->controller){
				$controller_name = $this->request()->controller;
			}
			
			//LOAD THE CONTROLLER
			$controller = $this->load_controller($controller_name);

			//METHOD OVERRIDE
			if($this->request()->method){
				$method = $this->request()->method;
			}

			//FORWARD TO METHOD IF EXISTS
			if(method_exists($controller, $method)){
				$controller->$method();
			}

			//LOAD TEMPLATE
			$controller->load_template();
		}

		public function load_controller($controller_name){

			$this->request->controller = $controller_name;

			//LOAD A CONTROLLER
			$class_name = $controller_name.\'_Controller\';
			
			if(file_exists(CONTROLLER_PATH.$class_name.\'.php\')){
				require_once CONTROLLER_PATH.$class_name.\'.php\';
				return new $class_name;
			}

			//LOAD A FILE
			else{

				if($this->request->method){
					$file_path = VIEW_PATH.$controller_name.\'/\'.$this->get_path_after($controller_name);

					if(file_exists($file_path)){
						ob_start();
						include $file_path;
						exit;
					}					
				}
			}

			//NO CONTROLLER OR FILE WAS LOADED TO BOMB OUT 404
			$this->not_found();
		}

		public function load_model($name, $force_new = false){

			/*if(class_exists($name) && $force_new == false){
				return $this->$name;
			}*/
			$model_name = $name.\'_Model\';
			$model_path = MODEL_PATH.$model_name.\'.php\';

			require_once $model_path;
			$this->$name = new $model_name;
			return $this->$name;
		}

		public function load_plugin($name){



			$plugin_path 	= PLUGIN_PATH.$name.\'/index.php\';
			$class_name 	= $name.\'_Plugin\';

			if(file_exists($plugin_path)){
				require_once $plugin_path;				
				return new $class_name;
			}
			$this->pr(\'Unable to load plugin \'. $name);
			exit;
		}

		public function debug($data){
			echo \'<pre>\';
			if(!$data){
				var_dump($data);
			}
			else{
				print_r($data);
			}
			echo \'</pre>\';
		}

		public function load_lib($name){

			$filepath = LIB_PATH.$name.\'.php\';
			if(file_exists($filepath)){
				require_once $filepath;
			}

		}

		public function get_path_after($name){

			//GET ALL THE URL PARTS
			$request_parts = $this->request()->raw;			

			//INIT PATH ARRAY
			$path = array();

			$found = false;

			//CYCLE THE URL PARTS
			foreach($request_parts as $part){

				//THE PATH PART WAS FOUND
				if($name == $part){
					$found = true;
					continue;
				}

				//PATH PART WAS ALREADY FOUND SO ADD THIS TO THE ARRAY
				if($found){
					$path[] = $part;
				}
			}			
			return implode(\'/\', $path);
		}

		public function not_found(){

			//SET THE HEADER AS 404
			header("HTTP/1.0 404 Not Found");

			//SET THE FILE PATH
			$file_path = VIEW_PATH.\'System/404.php\';

			//LOAD THE HEADER
			$this->load_header();

			//CHECK FOR 404 TEMPLATE
			if(file_exists($file_path)){
				include $file_path;
			}

			//NOT TEMPLATE FOUND JUST PRINT DEFAULT TEXT
			else{
				echo \'Error: 404. The page you are looking for was not found\';
			}

			//LOAD THE FOOTER
			$this->load_footer();

			//END THE SCRIPT
			exit;
		}
	}
';
		$this->create_file(SYSTEM_PATH.'zSpark.php', $content);

		}

		private function build_database(){
			$content = '<?php

	class zSpark_Database{

		public $conn;
		public $q;
		public $num_rows;
		public $inserted_columns;
	
		function add_error($arr)
		{
			print_r($arr);
		}
		
		function connect()
		{
			// Put the connection into $this->conn
			$this->conn = mysql_connect($this->credentials[\'db_host\'], $this->credentials[\'db_username\'], $this->credentials[\'db_password\']);
			mysql_select_db($this->credentials[\'db_name\'], $this->conn);
		}

		function query($query)
		{
			$this->q = NULL;
			$this->num_rows = NULL;
			$this->q = mysql_query($query, $this->conn);
			if($this->q)
			{
				//$this->add_flash($query);
				$ret = TRUE;
			}else{
				$this->add_error(array(0 => $query, 1 => mysql_error()));
				$ret = FALSE;
			}
			return $ret;
		}
		
		function get_row($query)
		{
			
			// Perform the query
			if(!$this->query($query))
			{
				return FALSE;
			}
			// Get results from query
			if(mysql_num_rows($this->q) == 0)
			{
				return FALSE;
			}else{
				return $this->stripslashes_deep(mysql_fetch_assoc($this->q));
			}
		}

		
		function get_rows($query)
		{
			// Perform the query
			if(!$this->query($query))
			{
				return FALSE;
			}
			// Get results from query
			if(mysql_num_rows($this->q) == 0)
			{
				return FALSE;
			}else{
				while($r = mysql_fetch_assoc($this->q))
				{
					$ret[] = $this->stripslashes_deep($r);
				}
				return $ret;
			}
		}


		
		
		
		function insert($table, $data)
		{
			$this->inserted_columns = array();
			// Perform the query
			if(!$this->query("SHOW COLUMNS FROM `{$table}`"))
			{
				return FALSE;
			}
			// Get results from query
			if(mysql_num_rows($this->q) == 0)
			{
				return FALSE;
			}else{
				while($r = mysql_fetch_assoc($this->q))
				{
					$fields[$r[\'Field\']] = array(
						\'type\' => $r[\'Type\'],
						\'key\' => $r[\'Key\'],
					);
				}
				$data = $this->stripslashes_deep($data);
				foreach($data as $k => $v)
				{
					if(is_array($fields[$k]))
					{
						$this->inserted_columns[$k] = $k;
						$v = $this->escape($v);
						$query .= " `{$k}` = \'{$v}\', ";
					}
				}
				$query = trim($query, \' ,\');
				$complete = "INSERT INTO `{$table}` SET {$query}";
				if($this->query($complete))
				{
					return mysql_insert_id($this->conn);
				}else{
					return false;
				}
			}
		}

	
		function update($table, $data, $where)
		{
			if(!strstr(\' \'.$where, \'=\'))
			{
				$this->add_error(\'No where clause specified. Exiting update.\');
				return FALSE;
			}
			// Perform the query
			if(!$this->query("SHOW COLUMNS FROM `{$table}`"))
			{
				return FALSE;
			}
			// Get results from query
			if(mysql_num_rows($this->q) == 0)
			{
				return FALSE;
			}else{
				while($r = mysql_fetch_assoc($this->q))
				{
					$fields[$r[\'Field\']] = array(
						\'type\' => $r[\'Type\'],
						\'key\' => $r[\'Key\'],
					);
				}
				$data = $this->stripslashes_deep($data);
				foreach($data as $k => $v)
				{
					if(is_array($fields[$k]) && $fields[$k][\'key\'] != \'PRI\')
					{
						$v = $this->escape($v);
						$query .= " `{$k}` = \'{$v}\', ";
					}
				}
				$query = trim($query, \' ,\');
				$complete = "UPDATE `{$table}` SET {$query} WHERE {$where}";
				return $this->query($complete);
			}
		}

		function num_rows()
		{
			return mysql_num_rows($this->q);
		}



		/**
		 * Escape a string
		 *
		 * @param string $value
		 * @return string results
		 */
		function escape($value)
		{
			if(is_array($value))
			{
				return mysql_real_escape_string(serialize($value), $this->conn);
			}
			return mysql_real_escape_string($value, $this->conn);
		}

		
		/**
		 * Recursively remove slash characters from an array or string
		 *
		 * @param array/string $value
		 * @return array results
		 */
		function escape_deep($value)
		{
			if(is_array($value))
			{
				foreach($value as $k=>$v)
				{
					$value[$k] = $this->escape_deep($v);
				}
				return $value;
			}else{
				return mysql_real_escape_string($value, $this->conn);
			}
		}


		/**
		 * Recursively remove slash characters from an array or string
		 *
		 * @param array/string $value
		 * @return array results
		 */
		function stripslashes_deep($value)
		{
			if(is_object($value)){
				return $value;
			}
			if(is_array($value))
			{
				foreach($value as $k=>$v)
				{
					$value[$k] = $this->stripslashes_deep($v);
				}
				return $value;
			}else{			
				return stripslashes($value);
			}
		}



		/**
		 * Will pull a single result from table, joining
		 * in any table with the appropriate naming prefix.
		 * If there is a field in table named address_id, it
		 * will look for a table named address, and then
		 * join in the row with the value stored in that field.
		 *
		 * @param string $table
		 * @param string $where
		 * @return array results
		 */
		function fetch_complete($table, $where)
		{
			// Make sure there is a where clause
			if(!strstr(\' \'.$where, \'=\'))
			{
				$this->add_error(\'No where clause specified. Exiting fetch_complete.\');
				return FALSE;
			}
			// Fetch table names for later
			if(!$this->get_table_names())
			{
				return FALSE;
			}
			// Perform the query
			if(!$this->query("SHOW COLUMNS FROM `{$table}`"))
			{
				return FALSE;
			}
			// Get results from query
			if(mysql_num_rows($this->q) == 0)
			{
				return FALSE;
			}else{
				while($r = mysql_fetch_assoc($this->q))
				{
					$fields[$r[\'Field\']] = array(
						\'type\' => $r[\'Type\'],
						\'key\' => $r[\'Key\'],
					);
				}

				$complete = "SELECT * FROM {$table} WHERE {$where}";
				$return = $this->get_row($complete);				

				if($return !== FALSE)
				{
					$final_return = $return;
					foreach($return as $key => $val)
					{
						if(preg_match(\'/([a-z_0-9]+)_id/i\', $key, $matches))
						{
							if($matches[1] != $table && array_search($matches[1], $this->tables) !== FALSE)
							{
								$get_join = $this->get_row("SELECT * FROM {$matches[1]} WHERE {$key} = \'{$val}\' ");
								if($get_join !== FALSE)
								{
									$final_return = array_merge($final_return, $get_join);
								}
							}
						}
					}
				}
				return $final_return;

			}
		}



		/**
		 * Get all of the table names for the current database,
		 * store the resulting array in $this->tables
		 *
		 * @return boolean success
		 */
		function get_table_names()
		{
			// Get all of the table names
			if(!$this->query("SHOW TABLES"))
			{
				$this->add_error("Could not get table names.");
				return FALSE;
			}
			// Get results from query
			if(mysql_num_rows($this->q) == 0)
			{
				$this->add_error("Could not get table names.");
				return FALSE;
			}else{
				while($r = mysql_fetch_array($this->q))
				{
					$this->tables[] = $r[0];
				}
				return TRUE;
			}
		}


		// Get valid enum values from a column
		// Borrowed from php.net
		function get_enum($table, $field, $ucfirst_values = TRUE)
		{
			$result = $this->query("show columns from {$table}");
			$types = array();
			while($tuple=mysql_fetch_assoc($this->q))
			{
				if($tuple[\'Field\'] == $field)
				{
					$types=$tuple[\'Type\'];
					$beginStr=strpos($types,"(")+1;
					$endStr=strpos($types,")");
					$types=substr($types,$beginStr,$endStr-$beginStr);
					$types=str_replace("\'","",$types);
					$types=split(\',\',$types);
					if($sorted)
					{
						sort($types);
					}
				}
			}
			foreach($types as $v)
			{
				if($ucfirst_values)
				{
					$ret[$v] = ucfirst($v);
				}else{
					$ret[$v] = $v;	
				}
			}
			return $ret;
		}
	}';

			$this->create_file(SYSTEM_PATH.'database.php', $content);
		}

		private function build_orm_wrapper(){
			$content = '<?php
	//ORM WRAPPER BASE CLASS
	class ORM_Wrapper extends zSpark_Model{

		public function __construct(){
			unset($this->request);
		}

		public function first(){
			foreach($this as $el){
				return $el;
			}
		}

		public function count(){
			$count = 0;
			foreach($this as $el){
				$count++;
			}
			return $count;
		}

		public function last(){
			$count = $this->count()-1;
			return $this->$count;
		}
	}
';
			$this->create_file(SYSTEM_PATH.'orm_wrapper.php', $content);
		}

		private function build_model_base(){
			$content = '<?php 
	
	class zSpark_Model extends zSpark {

		private $_has_many 	= array();
		private $_has_one 	= array();
		private $_where 	= array();
		private $_order 	= array();
		private $_limit;

		public function __construct(){

			//SET THE TABLE NAME
			if(!$this->table_name){
				$this->table_name = strtolower($this->model_name());
			}
		}

		//FORWARD UNFOUND METHODS
		public function __call($name, $value){

			$class_name = str_replace(\'_Model\', \'\', get_class($this));
			$check_var = strtolower($class_name).\'_\'.$name;
			if(isset($this->$check_var)){
				return $this->$check_var;
			}
			
			//CHECK IF THIS MODEL HAS MANY
			if(count($this->_has_many)){
				foreach($this->_has_many as $get){
					$get[\'table\'] = $this->get_table_name($get[\'model\']);
					if(strtolower($name) == strtolower($get[\'table\']) || strtolower($name) == strtolower($get[\'model\'])){

						$field_value 	= $this->$get[\'local_field\'];
						$field_name 	= $get[\'remote_field\'];
						$model 			= $this->load_model($get[\'model\'])->where("{$field_name} = \'{$field_value}\'");
						if(!empty($value)){
							$model->where($value[0]);
						}

						if(!empty($get[\'where\'])){
							foreach($get[\'where\'] as $field_name => $field_value){
								$model->where("{$field_name} = \'{$field_value}\'");
							}
						}

						return $model->orm_load();
					}
				}
			}

			//CHECK IF THIS MODEL HAS ONE
			if(count($this->_has_one)){
				foreach($this->_has_one as $get){				
					$get[\'table\'] = $this->get_table_name($get[\'model\']);
					if(strtolower($name) == strtolower($get[\'table\']) || strtolower($name) == strtolower($get[\'model\'])){
						
						$field_value 	= $this->$get[\'local_field\'];
						$field_name 	= $get[\'remote_field\'];						
				
						$model 			= $this->load_model($get[\'model\'])->where("{$field_name} = \'{$field_value}\'")->limit(1);
						if(!empty($value)){
							$model->where($value[0]);
						}

						if(!empty($get[\'where\'])){
							foreach($get[\'where\'] as $field_name => $field_value){
								$model->where("{$field_name} = \'{$field_value}\'");
							}
						}

						$model = $model->orm_load();
						//if($model->count()){
						if(count($model)){
							$model = $model[0];
						}
						return $model;
					}
				}
			}			
		}

		public function where($query){
			$this->_where[] = $query;			
			return $this;
		}

		public function limit($number){
			$this->_limit = $number;
			return $this;
		}

		public function get_table_name($model_name){
			$model = $this->load_model($model_name);

			if($model->table_name){
				$table_name = $model->table_name;
			}
			else{
				$table_name = strtolower($model_name);
			}

			return $table_name;
		}		

		public function has_one($model_name, $local_field = null, $remote_field = null, $where = array()){

			$default = strtolower($model_name).\'_id\';

			//SET FIELDS IF NEEDED
			if($local_field == false){
				$local_field = $default;
			}
			if($remote_field == false){
				$remote_field = $default;
			}
			
			$this->_has_one[] = array(\'model\' => $model_name, \'local_field\' => $local_field, \'remote_field\' => $remote_field, \'where\' => $where);
		}

		//SET MODEL RELATIONSHIP TO MANY
		public function has_many($model_name, $local_field = false, $remote_field = false, $where = array()){

			$default = strtolower($model_name).\'_id\';			

			//SET FIELDS IF NEEDED
			if($local_field == false){
				$local_field = $this->get_primary_field(strtolower($this->model_name()));
			}

			if($remote_field == false){
				$remote_field = $this->get_primary_field(strtolower($this->model_name()));
			}

			$this->_has_many[] = array(\'model\' => $model_name, \'local_field\' => $local_field, \'remote_field\' => $remote_field, \'where\' => $where);
		}

		//GET THE NAME OF THIS MODEL
		public function model_name(){

			return str_replace(\'_Model\', \'\', get_class($this));
		}

		//GET THE PRIMARY KEY OF A TABLE
		public function get_primary_field($table_name){

			$field = $this->Db()->get_row("SHOW COLUMNS FROM `{$table_name}`");			
			$field_name = $field[\'Field\'];		
			return $field_name;
		}

		//LOAD A MODEL
		public function orm_load($id = false){

			if(!$this->table_name){
				$this->table_name = strtolower($this->model_name());
			}
			
			$field_name = $this->get_primary_field($this->table_name);
			
			
			$where = " WHERE 1=1";
			if($id){
				$where .= "
				AND ({$field_name} = {$id})";
			}
			if(count($this->_where)){
				

				foreach($this->_where as $q){
					$where .= "
						AND ({$q}) ";
				}
			}

			$limit = "";
			if($this->_limit){
				$limit = " LIMIT {$this->_limit}";
			}

			$order = "ORDER BY {$field_name} ASC";
			if($this->_order){
				$order = $this->_order;
			}


			$sql = "SELECT * FROM `{$this->table_name}` {$where} {$order} {$limit} ";


			
			if($id === false){

				$res = $this->Db()->get_rows($sql);
				$ret = new Orm_Wrapper;
				//$ret = array();
				if($res){
					foreach($res as $key=>$record){
						$ret->$key = $this->orm_load($record[$field_name]);
						//$ret[$key] = $this->orm_load($record[$field_name]);
					}
				}
				return $ret;
				
			}
			else{	
				$model_name = $this->model_name().\'_Model\'; 
				$model = new $model_name;			
				$res = $model->Db()->get_row($sql);
				$model->orm_set($res);
				return $model;
			}
		}

		public function orm_set($data = array()){
			
			foreach($data as $key=>$value){
				$this->$key = $value;
			}			
			return $this;
		}

		public function orm_save(){
			
			//EXTRACT DATA
			$data = $this->expose_data();
			unset($data[\'table_name\']);
			unset($data[\'_has_many\']);
			unset($data[\'_has_one\']);
			
			//SET THE TABLE NAME
			if(!$this->table_name){
				$this->table_name = strtolower($this->model_name());
			}

			//GET THE PRIMARY FIELD
			$primary_field = $this->get_primary_field($this->table_name);

			//INSERT A NEW RECORD
			if(!$data[$primary_field]){
				$id = $this->Db()->insert($this->table_name, $data);
			}


			//UPDATE THE RECORD BECAUSE AN ID WAS PROVIDED
			else{
				
				//CHECK THAT THE RECORD EXISTS
				if($this->Db()->get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = \'{$data[$primary_field]}\'")){
					$this->Db()->update($this->table_name, $data, "{$primary_field} = \'{$data[$primary_field]}\'");
					$id = $data[$primary_field];
				}
				
				//CREATE A NEW RECORD
				else{
					$id = $this->Db()->insert($this->table_name, $data);
				}
			}
		
			return $this->orm_load($id);

		}

		//DELETE A RECORD
		public function orm_delete(){

			//EXTRACT DATA
			$data = $this->expose_data();

			//GET THE PRIMARY FIELD
			$primary_field = $this->get_primary_field($this->table_name);

			//DELETE THE RECORD
			return $this->Db()->query("DELETE FROM `{$this->table_name}` WHERE {$primary_field} = \'{$data[$primary_field]}\'");

		}

		public function expose_data(){
			return get_object_vars($this); 
		}
	}
';
			$this->create_file(SYSTEM_PATH.'model_base.php', $content);
		}

		private function build_controller_base(){
			$content = '<?php
	
	class zSpark_Controller extends zSpark{

		private $_disable_headers = array();

		//FIND THE URL PATH AFTER A URL PART
		public function Db($name = false){
			return $this->Db($name);
		}
		
		public function require_login(){			
			if(!isset($_SESSION[\'Login\'])){
				$_SESSION[\'login_redirect\'] = $this->request->raw;
				$this->load_controller(\'Login\')->load_template();
				exit;
			}
		}

		public function disable_headers($data){

			if(is_array($data)){
				foreach($data as $header){
					$this->_disable_headers[] = $header;
				}
			}
			else{
				$this->_disable_headers[] = $data;
			}
		}

		public function js_tag($name){			
			echo \'<script type="text/javascript" src="\'.$this->config()->path->web_path.\'js/\'.$name.\'.js"></script>\';
		}

		public function css_tag($name){			
			echo \'<link rel="stylesheet" href="\'.$this->config()->path->web_path.\'css/\'.$name.\'.css">\';
		}

		public function load_template($template = false, $load_controller = false){

			

			if(strpos(get_class($this), \'_Controller\')){
				$controller = str_replace(\'_Controller\', \'\', get_class($this));
			}
			else{
				$controller = \'Home\';
				if($this->request()->controller){
					$controller = $this->request()->controller;
				}
			}
			

			//NO TEMPLATE WAS SPECIFIED
			if($template == false){
				
				//DEFAULT TO INDEX TEMPLATE
				$template_path = VIEW_PATH.$controller.\'/index.php\';
				
				//IF THERE WAS A METHOD IN THE REQUEST SWITCH TO THAT TEMPLATE
				if($this->request()->method){
					$template_path = VIEW_PATH.$controller.\'/\'.$this->request()->method.\'.php\';
				}
			}

			//A TEMPLATE WAS SPECIFIED
			else{
				$template_path = VIEW_PATH.$controller.\'/\'.$template.\'.php\';
			}

			//CHECK THAT THE TEMPLATE EXISTS
			if(file_exists($template_path)){
				
				//GET THE HEADER
				$this->load_header();

				//GET THE TEMPLATE
				include $template_path;

				//GET THE FOOTER
				$this->load_footer();
				exit;
			}

			//BOMB OUT ERROR
			else{
				echo \'404 not found\';
				exit;
			}
			exit;
		}

		public function load_header(){

			//SET HEADER LOCATIONS
			$main_header 	= VIEW_PATH.\'/header.php\';
			$local_header 	= VIEW_PATH.$this->request()->controller.\'/header.php\';

			//DEFAULT TO LOCAL HEADER
			if(file_exists($local_header)){
				include $local_header;
			}

			//LOAD MAIN HEADER
			elseif(file_exists($main_header)){
				include $main_header;
			}
		}

		public function load_footer(){

			$main_footer 	= VIEW_PATH.\'/footer.php\';
			$local_footer 	= VIEW_PATH.$this->request()->controller.\'/footer.php\';

			//DEFAULT TO LOCAL FOOTER
			if(file_exists($local_footer)){
				include $local_footer;
			}

			//LOAD MAIN FOOTER
			elseif(file_exists($main_footer)){
				include $main_footer;
			}
		}

		public function load_partial($name){

			$main_partial 	= VIEW_PATH.\'partial/\'.$name.\'.php\';
			$local_partial 	= VIEW_PATH.$this->request()->controller.\'/partial/\'.$name.\'.php\';

			//DEFAULT TO LOCAL FOOTER
			if(file_exists($local_partial)){
				include $local_partial;
			}

			//LOAD MAIN FOOTER
			elseif(file_exists($main_partial)){
				include $main_partial;
			}
		}
	}
			';

			$this->create_file(SYSTEM_PATH.'controller_base.php', $content);
		}

		private function build_config_file(){

			$credentials = '
$credentials = array(
	\'db_name\' => array(
		\'db_host\'		=> \'localhost\',				//do not touch unless you know how to connect to outside databases
		\'db_username\' 	=> \'root\',				//your database username. If using wamp/mamp/lamp username is root
		\'db_password\' 	=> \'\',				//your database password. If using wamp/mamp/lamp password is empty
	),				
);
';
		if($_POST['use_db'] == 'on'){
			$credentials = '
$credentials = array(
	\''.$_POST['db_name'].'\' => array(
		\'db_host\'		=> \''.$_POST['db_host'].'\',				//do not touch unless you know how to connect to outside databases
		\'db_username\' 	=> \''.$_POST['db_user'].'\',				//your database username. If using wamp/mamp/lamp username is root
		\'db_password\' 	=> \''.$_POST['db_password'].'\',				//your database password. If using wamp/mamp/lamp password is empty
	),				
);
';			
		}

			
			$content = '
<?php
class zSpark_Config extends zSpark{

	public function __construct(){

		$this->define();			
		$this->path();
	}

	//MAIN CONFIG
	public function path(){

		if(!$this->path){

		//DEFINE GLOBAL VAR HERE, NEST MULTIDIMENSIONAL ARRAYS TO CREATE OBJECT ORIENTED STRUCTURE
		$path = array(
			\'web_path\'	=> \'//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'\', 	//your website name
			\'app_name\'	=> \'\', 		//example: dashboard
			\'pathname\' 	=> \'/your/path/example/\',		//define a custom path with a custom name. Best practice is to begin with APP_PATH then follow with the folders to ensure accuracy
			\'varname\'	=> array(
				\'subvar\' => \'subvar_value\'				//define array(s) to use as $this->path->varname->subvar_value to establish custom global vars. You can nest as many vars as you want
			),
		);

		//CONVERTS ARRAY TO OBJECT
			$this->path = json_decode(json_encode($path));
		}
		return $this->path;
	}

	public function database($db_name = false){

		//YOUR DATABASE CREDENTIALS. $this->Db() WILL DEFAULT TO THE FIRST SET OF CREDENTIALS
		//TO SWITCH DATABASES: USE $this->Db(db_name) 
		'.$credentials.'

			//SWITCH DB CRED IF NEEDED
			if($db_name !== false){
				return $credentials[$db_name];
			}

			//DEFAULT TO FIRST CREDENTIAL SET
			foreach($credentials as $db_name=>$cred){
				$cred[\'db_name\'] = $db_name;
				return $cred;
			}
		}

		//DEFINE FRAMEWORK VARIABLES
		public function define(){
			
			//IF GLOBAL DEFINITIONS ARE NOT DEFINED
			if(!defined(\'APP_PATH\')){
				
				//CORE PATHS
				define(\'APP_PATH\', 			\''.APP_PATH.'\');			//SET THE PATH FOR THE MAIN APPLICATION
				define(\'MODEL_PATH\', 		APP_PATH.\'model/\');				//SET THE PATH FOR MODELS
				define(\'CONTROLLER_PATH\', 	APP_PATH.\'controller/\');			//SET THE PATH FOR CONTROLLERS
				define(\'PLUGIN_PATH\', 		APP_PATH.\'plugin/\');				//SET THE PATH FOR PLUGINS				
				define(\'APP_NAME\', 			\''.basename(APP_PATH).'\'); 	//SET THE NAME OF THE APPLICATION - TAKE FROM THE FOLDER THE FRAMEWORK IS INSTALLED IN
				define(\'VIEW_PATH\', 		APP_PATH.\'view/\');				//SET THE PATH FOR VIEWS
			}
		}
	}';
			$this->create_file(SYSTEM_PATH.'config.php', $content);
		}

		

		private function installer_template_welcome($data = array()){
			?>
<!DOCTYPE html>
<html lang=\"en\">
  	<head>
	    <meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <title>zSpark Installer</title>

	    <!-- BOOTSTRAP CSS -->
	    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
	   	
	   	<!-- JQUERY -->
	   	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	    
	    <!-- BOOTSTRAP JS -->
	    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

	    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	    <!--[if lt IE 9]>
	      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	    <![endif]-->
  	</head>
  	<body style="background:#ededed">
	  	<div class="container">
	  		<div style="background:#fff; box-shadow:rgba(0,0,0,0.6) 0 0 5px; padding:20px;">
	  			<div class="page-header"><h1>zSpark Installer</h1></div>
	  			<p>Please Configure Your zSpark Install. If you do not wish to use a database, simply uncheck the "Use Database" box.</p>
	  			<? if($data['errors']): ?>
	  				<? foreach($data['errors'] as $error): ?>
	  					<div class="alert alert-warning" role="alert"><strong>Warning: </strong><?=$error?></div>
	  				<? endforeach; ?>
	  			<? endif?>
	  			<form class="form-horizontal" method="post">	  				
					<input type="hidden" name="app_name" class="form-control" id="app_name" placeholder="Framework Directory" value="<?=APP_NAME?>">			  	
					<div class="panel panel-default">
						<div class="panel-heading"><div class="pull-right"><input type="checkbox" name="use_db" id="use_db" checked> <label for="use_db">Use Database</label></div><h3 class="panel-title">Database</h3> </div>
						<div class="panel-body">

							<div class="form-group">
						    	<label for="db_host" class="col-sm-2 control-label">Database Host</label>
						    	<div class="col-sm-10">
						    		<input type="text" name="db_host" value="<?=$data['db_host'] ? $data['db_host'] : 'localhost'; ?>"  class="form-control" id="db_host" placeholder="Database Host" value="localhost">
						    		<span id="helpBlock" class="help-block">Only change this if you have set up remote db access on the server.</span>
						    	</div>
						  	</div>

							<div class="form-group">
						    	<label for="db_name" class="col-sm-2 control-label">Database Name</label>
						    	<div class="col-sm-10">
						    		<input type="text" name="db_name" class="form-control" id="db_name" value="<?=$data['db_name']?>"  placeholder="Database Name">
						    		
						    	</div>
						  	</div>

						  	<div class="form-group">
						    	<label for="db_user" class="col-sm-2 control-label">Database User</label>
						    	<div class="col-sm-10">
						    		<input type="text" name="db_user" class="form-control" id="db_user" value="<?=$data['db_user']?>"  placeholder="Database User">
						    		
						    	</div>
						  	</div>

						  	<div class="form-group">
						    	<label for="db_password" class="col-sm-2 control-label">Database Password</label>
						    	<div class="col-sm-10">
						    		<input type="text" name="db_password" class="form-control" id="db_password" value="<?=$data['db_password']?>" placeholder="Database Password">
						    		
						    	</div>
						  	</div>
						</div>
					</div>
				  	<div class="form-group">
				    	<div class="col-sm-offset-2 col-sm-10">
				      		<button type="submit" name="start_install" value="true" class="btn btn-default">Start Install</button>
				    	</div>
				  	</div>
				</form>
	  		</div>
	  	</div>
  	</body>
</html>
			<?
		}

		//CREATE THE HTACCESS FILE: /.htaccess
		private function htaccess(){


			$this->create_file(APP_PATH.'.htaccess', "
RewriteEngine On

RewriteBase /

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^(.*)$ index.php/$1 [L] ");
			
		}

		private function login_controller(){
			$this->create_file(CONTROLLER_PATH.'Login_Controller.php', "
<?php
	class Login_Controller extends zSpark_Controller {

		public function __construct(){
			//DO STUFF HERE WHEN THE CONTROLLER IS LOADED
		}

		public function index(){
			//DO STUFF HERE WHEN THE DEFAULT VIEW IS LOADED
		}
	}
?>");			
		}

		private function login_template(){
			
			//CREATE THE LOGIN TEMPLATE FOLDER
			mkdir(VIEW_PATH.'Login', 0777, true);
			
			$content = "<div class=\"container\">
	<div class=\"page-header\">Please Login</div>
	<form>
		<div class=\"form-group\">
	    	<label for=\"email\">Email address</label>
	    	<input type=\"email\" class=\"form-control\" id=\"email\" name=\"email\" placeholder=\"Email\">
	  	</div>
		<div class=\"form-group\">
			<label for=\"password\">Password</label>
			<input type=\"password\" class=\"form-control\" id=\"password\" name=\"password\" placeholder=\"Password\">
		</div>
	  <button type=\"submit\" class=\"btn btn-default\">Submit</button>
	</form>
</div>
";
			//MAKE THE DEFAULT VIEW
			$this->create_file(VIEW_PATH.'Login/index.php', $content);
		}

		private function home_controller(){

			$this->create_file(CONTROLLER_PATH.'Home_Controller.php', "
<?php
	class Home_Controller extends zSpark_Controller {

		public function __construct(){
			//DO STUFF HERE WHEN THE CONTROLLER IS LOADED
		}

		public function index(){
			//DO STUFF HERE WHEN THE DEFAULT VIEW IS LOADED
		}
	}
?>");			
		}

		//DEFAULT HOME TEMPLATE: /view/Home/index.php
		private function home_template(){

			//CREATE THE HOME TEMPLATE FOLDER
			mkdir(VIEW_PATH.'Home', 0777, true);
			$content = "
    	<h1>Welcome to zSpark</h1>
";
			//MAKE THE DEFAULT VIEW
			$this->create_file(VIEW_PATH.'Home/index.php', $content);
		}

		//DEFAULT HEADER: /view/header.php
		private function header(){
			$content = "
<!DOCTYPE html>
<html lang=\"en\">
  	<head>
	    <meta charset=\"utf-8\">
	    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
	    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
	    <title>".'<?=$this->page_name?>'."</title>

	    <!-- BOOTSTRAP CSS -->
	    <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css\" integrity=\"sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7\" crossorigin=\"anonymous\">
	    <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css\" integrity=\"sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r\" crossorigin=\"anonymous\">
	   	
	   	<!-- JQUERY -->
	   	<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js\"></script>
	    
	    <!-- BOOTSTRAP JS -->
	    <script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js\" integrity=\"sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS\" crossorigin=\"anonymous\"></script>

	    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	    <!--[if lt IE 9]>
	      <script src=\"https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js\"></script>
	      <script src=\"https://oss.maxcdn.com/respond/1.4.2/respond.min.js\"></script>
	    <![endif]-->
  	</head>
  	<body>";

  			$this->create_file(VIEW_PATH.'header.php', $content);
		}

		//DEFAULT FOOTER: /view/footer.php
		private function footer(){
			$content = "
	</body>
</html>";
			$this->create_file(VIEW_PATH.'footer.php', $content);
		}

		private function system_template(){
			mkdir(VIEW_PATH.'System', 0777, true);
			$this->create_file(VIEW_PATH.'System/404.php', 'Error: 404. The file you are looking for was not found.');
		}

		private function css(){
			mkdir(VIEW_PATH.'css', 0777, true);
			$this->create_file(VIEW_PATH.'css/style.css', '');
		}

		private function javascript(){
			mkdir(VIEW_PATH.'js', 0777, true);
			$this->create_file(VIEW_PATH.'js/script.js', '');
		}

		//CREATE A FILE
		private function create_file($filename, $content){
			$handle = fopen($filename, "w+");
			fwrite($handle, $content);
			fclose($handle);
		}
	}
	
	

	//RUN THE APP
	new zSpark_Installer();

	
?>
