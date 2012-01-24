<?php 

/**
 * SugarCRM REST API Class
 *
 * @package   	SugarCRM
 * @category  	Libraries
 * @author	Asa Kusuma
 * @license	MIT License
 * @link	http://github.com/asakusuma/SugarCRM-REST-API-Wrapper-Class/
 */


class Sugar_REST {
	
	////////////////////////////////////////
	/// Settings Variables
	/// (Edit to configure)
	////////////////////////////////////////
	
	/**
	* Variable:	$rest_url
	* Description:	The URL of the SugarCRM REST API
	* Example:	http://mydomain.com/sugarcrm/service/v2/rest.php
	*/
	private $rest_url = "https://example.com/service/v2/rest.php";
	
	/**
	* Variable:	$username
	* Description:	A SugarCRM Username. It's recommended that
	*		you create a seperate SugarCRM User account
	*		to make REST calls.
	*/
	private $username = "";
	
	/**
	* Variable:	$password
	* Description:	The password for the $username SugarCRM account
	*/
	private $password = "";
	
	
	////////////////////////////////////////
	/// Other Variables
	/// (Don't edit)
	////////////////////////////////////////
	
	/**
	* Variable:	$session
	* Description:	The session ID for REST calls
	*/
	private $session;
	
	/**
	* Variable:	$logged_in
	* Description:	Boolean flag for login status
	*/
	private $logged_in;
	
	/**
	* Variable:	$error
	* Description:	The latest error
	*/
	private $error = FALSE;
	
	/**
	* Function:	Sugar_REST()
	* Parameters: 	none	
	* Description:	Class constructor
	* Returns:	TRUE on login success, otherwise FALSE
	*/
	function Sugar_REST($rest_url=null,$username=null,$password=null,$md5_password=true) 
	{
	    if (!is_null($rest_url))
	    {
	        $this->rest_url = $rest_url;
	    }
	    
	    if (!is_null($username))
	    {
            $this->username = $username;
	    }

        if (!is_null($password))
        {
            $this->password = $password;
        }
        
		if($this->login($md5_password)) {
			$this->logged_in = TRUE;
			$data['session'] = $this->session;
		} else {
			$this->logged_in = FALSE;
		}
	}
	
	/**
	* Function:	get_error()
	* Parameters: 	none	
	* Description:	Gets the current error. The current error is sent whenever
	*		an API call returns an error. When the function is called,
	*		it returns and clears the current error.
	* Returns:	Returns the error array in the form:
	*			array(
	*				'name' => [value],
	*				'number' => [value],
	*				'description'
	*			)
	*		If there is no error, returns FALSE.
	*		If the error array is corrupted, but there is still an
	*		error, returns TRUE.
	*/
	public function get_error() {
		if(isset($this->error['name'])) {
			$error = $this->error;
			$this->error = FALSE;
			return $error;
		} else if(is_bool($this->error)) {
			$error = $this->error;
			$this->error = FALSE;
			return $error;
		} else {
			return TRUE;
		}
	}
	
	/**
	* Function:	login()
	* Parameters: 	none	
	* Description:	Makes a 'login' API call which authenticates based on the $username
	*		and $password class variables. If the login call succeeds, sets
	*		the $session class variable as the session ID. If it fails, sets
	*		the current error.
	* Returns:	Returns TRUE on success, otherwise FALSE
	*/
	private function login($md5_password=true) {
	    
	    // run md5 on password if needed
	    $password = $this->password;
	    if ($md5_password)
	    {
	        $password = md5($this->password);
	    }
	    
		$result = $this->rest_request(
			'login',
			array(
				'user_auth' => array('user_name'=>$this->username,'password'=>$password),
				'name_value_list' => array(array('name' => 'notifyonsave', 'value' => 'true'))
			)
		);
		if(isset($result['id'])) {
			$this->session = $result['id'];
			return TRUE;
		} else {
			$this->error = $result;
			if(isset($this->error['name']) && isset($this->error['number']) && isset($this->error['description'])) {
				$this->error = $result;
			} else {
				$this->error['name'] = "Unknown Error";
				$this->error['number'] = -1;
				$this->error['description'] = "We are having technical difficulties. We apologize for the inconvenience.";
			}
			return FALSE;
		}
	}
	
	/**
	* Function:	rest_request()
	* Parameters: 	$call_name	= (string) the API call name
	*		$call_arguments	= (array) the arguments for the API call
	* Description:	Makes an API call given a call name and arguments
	*		checkout http://developers.sugarcrm.com/docs for documentation
	*		on the specific API calls
	* Returns:	An array with the API call response data
	*/
	private function rest_request($call_name, $call_arguments) {

		$ch = curl_init(); 
		
		$post_data = 'method='.$call_name.'&input_type=JSON&response_type=JSON';
		$jsonEncodedData = json_encode($call_arguments);
		$post_data = $post_data . "&rest_data=" . $jsonEncodedData;
		
        	curl_setopt($ch, CURLOPT_URL, $this->rest_url); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        	$output = curl_exec($ch); 
		
		$response_data = json_decode($output,true);
		
		return $response_data;
	}

	/**
	* Function:	is_valid_id($id)
	* Parameters: 	$id	= (string) the SugarCRM record ID
	* Description:	Checks to see if the given string is in the valid
	*		format for a SugarCRM record ID. This is for input
	*		data sanitation, does not actually check to see if
	*		if there is a record with the given ID.
	* Returns:	TRUE if valid format, otherwise FALSE
	*/
	public function is_valid_id($id) {
		if(!is_string($id)) return FALSE;
		return preg_match("/[0-9a-z\-]+/",$id);
	}
	
	public function count_records($module, $query) {
		$call_arguments = array(
			'session' => $this->session,
			'module_name' => $module,
			'query' => $query,
			'deleted' => 0
		);
		
		$result = $this->rest_request(
			'get_entries_count',
			$call_arguments
		);
		
		if(isset($result['result_count'])) {
			return $result['result_count'];	
		} else {
			return FALSE;
		}
	}
	
	/**
	* Function:	get_with_related($module, $fields, $options)
	* Parameters: 	$module	= (string) the SugarCRM module name. Usually first
	*			letter capitalized. This is the name of the base
	*			module. In other words, any other modules involved
	*			in the query will be related to the given base
	*			module.
	*		$fields		= (array) the fields you want to retrieve, based on
	*				the module:
	*				array(
	*					'Cases' => array(
	*						'field_name',
	*						'some_other_field_name'
	*					),
	*					'Acounts' => array(
	*						'field_name',
	*						'some_other_field_name'
	*					)
	*				)
	*		$options 	= (array)[optional] Lets you set options for the query:
	*				$options['limit'] = Limit how many records returned
	*				$options['offset'] = Query offset
	*				$options['where'] = WHERE clause for an SQL statement
	*				$options['order_by'] = ORDER BY clause for an SQL statement
	* Description:	Retrieves Sugar Bean records. Essentially returns the result of a
	*		SELECT SQL statement, given a base module, any number of related of modules,
	*		and respective fields for each module. Each row returned represents a 
	*		single record of the base module. Each row may have multiple records from.
	*		related modules.
	* Returns:	Result of API call in an array.
	*/
	public function get_with_related($module,$fields,$options=null) {
		
		if(sizeof($fields) < 1) {
			return FALSE;
		}
				
		//Set the defaults for the options
		if(!isset($options['limit'])) {
			$options['limit'] = 20;
		}
		if(!isset($options['offset'])) {
			$options['offset'] = 0;
		}
		if(!isset($options['where'])) {
			$options['where'] = null;
		}
		if(!isset($options['order_by'])) {
			$options['order_by'] = null;
		}
		if(!isset($fields[$module])) {
			return FALSE;
		}
		
		$base_fields = $fields[$module];
		unset($fields[$module]);
		
		$relationships = array();
		foreach($fields as $related_module => $fields_list) {
			$relationships[] = array('name' => strtolower($related_module), 'value' => $fields_list);
		}

		$call_arguments = array(
			'session' => $this->session,
			'module_name' => $module,
			'query' => $options['where'],
			'order_by' => $options['order_by'],
			'offset' => $options['offset'],
			'select_fields' => $base_fields,
			'link_name_to_fields_array' => $relationships,
			'max_results' => $options['limit'],
			'deleted' => "FALSE"
		);

		$result = $this->rest_request(
			'get_entry_list',
			$call_arguments
		);
		
		return $result;
	}
	
	/**
	* Function:	get($module, $fields, $options)
	* Parameters: 	$module	= (string) the SugarCRM module name. Usually first
	*			letter capitalized. This is the name of the base
	*			module. In other words, any other modules involved
	*			in the query will be related to the given base
	*			module.
	*		$fields		= (array) the fields you want to retrieve: 
	*				array(
	*					'field_name',
	*					'some_other_field_name'
	*				)
	*		$options	= (array)[optional] Lets you set options for the query:
	*				$options['limit'] = Limit how many records returned
	*				$options['offset'] = Query offset
	*				$options['where'] = WHERE clause for an SQL statement
	*				$options['order_by'] = ORDER BY clause for an SQL statement
	* Description:	Retrieves Sugar Bean records. Essentially returns the result of a
	*		SELECT SQL statement. 
	* Returns:	A 2-D array, first dimension is records, second is fields. For instance, the
	*		'name' field in the first record would be accessed in $result[0]['name].
	*/
	public function get($module,$fields,$options=null) {
		$results = $this->get_with_related($module,array($module => $fields),$options);
		$records = array();
		if ($records)
		{
    		foreach($results['entry_list'] as $entry) {
    			$record = array();
    			foreach($entry['name_value_list'] as $field) {
    				$record[$field['name']] = $field['value'];
    			}
    			$records[] = $record;
    		}
    	}
    	return $records;
	}
	
	/**
	* Function:	set($module, $values)
	* Parameters: 	$module	= (string) the SugarCRM module name. Usually first
	*			letter capitalized.
	*		$values	= (array) the data of the record to be set in
	*		the form:
	*			array(
	*				'id' => 'some value',
	*				'field_name' => 'some other value'
	*			)
	*						
	* Description:	Saves or creates a SugarCRM record, depending on whether	
	*		or not the 'id' field in the $values parameter is set.
	* Returns:	Result of API call in an array.
	*/
	public function set($module,$values) {
		$call_arguments = array(
			'session' => $this->session,
			'module_name' => $module,
			'name_value_list' => $values,
		);
		
		$result = $this->rest_request(
			'set_entry',
			$call_arguments
		);

		return $result;
	}

	public function print_results($results) {
		if(isset($results['entry_list'][0]['module_name'])) {
			$module_name = $results['entry_list'][0]['module_name'];
			echo "<h1>".$module_name."</h1>";
			foreach($results['entry_list'] as $i => $entry) {
				echo "<div class='first'>";
				foreach($entry['name_value_list'] as $field) {
					echo "<div class='second'>".$field['name']." = ".$field['value']."</div>";
				}
				if(isset($results['relationship_list'][$i])) {
					foreach($results['relationship_list'][$i] as $module) {
						echo "<div class='second'><b>related ".$module['name']."</b><br/>";
						foreach($module['records'] as $x=>$record) {
							echo "<div class='third'>";
							foreach($record as $field) {
								echo "<div class='fourth'>".$field['name']." = ".$field['value']."</div>";
							}
							echo "</div>";
						}
						echo "</div>";
					}
				}
				echo "</div>";
			}
		}
	}
	
	/**
	* Function:	get_note_attachment($note_id)
	* Parameters: 	$note_id	= (string) the SugarCRM record ID
	* Description:	Gets the attachment of a note given an id. See
	*		README for an example on use
	* Returns:	Attachment data in an array on success. Actual file
	*		data will be in binary format. Otherwise FALSE.
	*/
	public function get_note_attachment($note_id) {
		if($this->is_valid_id($note_id)) {
			$call_arguments = array(
				'session' => $this->session,
				'id' => $note_id
			);

			$result = $this->rest_request(
				'get_note_attachment',
				$call_arguments
			);
			return $result;
		}
		return FALSE;
	}
	
	/**
	* Function:	set_note_attachment($note_id, $file, $filename)
	* Parameters: 	$note_id	= (string) the SugarCRM record ID
	*		$file		= (string) the file in binary format
	*		$filename	= (string) the name of the file
	* Description:	Sets the attachment for a note. Will replace the old
	*				note if one already exists. See README for example on use
	* Returns:		Result of API call in an array.
	*/
	public function set_note_attachment($note_id,$file,$filename) {
		
		$call_arguments = array(
			'session' => $this->session,
			'note' => array(
				'id'=>$note_id,
				'file' => $file,
				'filename' => $filename,
				'related_module_name' => 'Cases'
			)
		);
		
		$result = $this->rest_request(
			'set_note_attachment',
			$call_arguments
		);
		
		return $result;
	}
	
	/**
	* Function:	is_logged_in()
	* Parameters: 	none
	* Description:	Simple getter for logged_in private variable
	* Returns:	boolean
	*/
	function is_logged_in()
	{
	    return $this->logged_in;
	}

	/**
	* Function:	__destruct()
	* Parameters: 	none
	* Description:	Closes the API connection when the PHP class
	*		object is destroyed
	* Returns:	nothing
	*/
	function __destruct() {
		if($this->logged_in) {
			$l = $this->rest_request(
				'logout',
				array(
					'session' => $this->session
				)
			);
		}
	}
}

?>