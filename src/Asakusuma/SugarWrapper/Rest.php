<?php

namespace Asakusuma\SugarWrapper;

use \Alexsoft\Curl;

/**
 * SugarCRM REST API Class
 *
 * @package     SugarCRM
 * @category    Libraries
 * @author  Asa Kusuma
 * @license MIT License
 * @link    http://github.com/asakusuma/SugarCRM-REST-API-Wrapper-Class/
 */
class Rest
{
    /**
     * The URL of the SugarCRM REST API
     * Example: http://mydomain.com/sugarcrm/service/v2/rest.php
     *
     * @var string
     */
    private $rest_url;

    /**
     * A SugarCRM Username. It's recommended that you create a seperate SugarCRM
     * User account to make REST calls.
     *
     * @var string
     */
    private $username;

    /**
     * The password for the $username SugarCRM account
     *
     * @var string
     */
    private $password;

    /**
     * The session ID for REST calls
     *
     * @var string
     */
    private $session;

    /**
     * Boolean flag for login status
     *
     * @var boolean
     */
    private $logged_in = FALSE;

    /**
     * logined user information
     */
    private $userinfo = null;

    /**
     * The latest error
     *
     * @var false|array
     */
    private $error = FALSE;

    /**
     * The curl object we use to talk to the API
     *
     * @var \Alexsoft\Curl
     */
    private $request;

    /**
     * Set the url
     *
     * @param string $url
     * @return \Asakusuma\SugarWrapper\Rest
     */
    public function setUrl($url = null)
    {
        $this->rest_url = $url;

        return $this;
    }

    /**
     * Set the username
     *
     * @param string $username
     * @return \Asakusuma\SugarWrapper\Rest
     */
    public function setUsername($username = null)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the password
     *
     * @param string $password
     * @return \Asakusuma\SugarWrapper\Rest
     */
    public function setPassword($password = null)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get logged-in user information
     */
    public function loggedInUserInfo()
    {
        return $this->userinfo;
    }

    /**
     * Connects to the API and logs in
     *
     * @param string $rest_url
     * @param string $username
     * @param string $password
     * @param string $md5_password
     * @return boolean
     */
    function connect($rest_url = null, $username = null, $password = null, $md5_password = true)
    {
        if (!is_null($rest_url)) {
            $this->rest_url = $rest_url;
        }

        if (!is_null($username)) {
            $this->username = $username;
        }

        if (!is_null($password)) {
            $this->password = $password;
        }

        $this->logged_in = FALSE;

        if ($this->login($md5_password)) {
            $this->logged_in = TRUE;
            $data['session'] = $this->session;
        }

        return $this->logged_in;
    }

    /**
     * Gets the current error. The current error is sent whenever an API call
     * returns an error. When the function is called, it returns and clears the
     * current error.
     *
     * @return boolean|array Returns the error array in the form:
     * <pre>
     * array(
     *     'name' => [value],
     *     'number' => [value],
     *     'description'
     * )
     * </pre>
     *       If there is no error, returns FALSE.
     *       If the error array is corrupted, but there is still an
     *       error, returns TRUE.
     */
    public function get_error()
    {
        $error = TRUE;

        if (isset($this->error['name'])) {
            $error = $this->error;
            $this->error = FALSE;
        } else if (is_bool($this->error)) {
            $error = $this->error;
            $this->error = FALSE;
        }

        return $error;
    }

    /**
     * Generate simple array from name_value_list of result returned by
     * API.
     *
     * @param array $nvlist name_value_list of API result
     * @return array
     */
    protected function adjustNameValueList($nvlist)
    {
        $result = array();

        foreach ($nvlist as $field) {
            $result[$field['name']] = $field['value'];
        }
        return $result;
    }

    /**
     * Makes a 'login' API call which authenticates based on the $username
     * and $password class variables. If the login call succeeds, sets the
     * $session class variable as the session ID. If it fails, sets the current
     * error.
     *
     * @param boolean $md5_password
     * @return boolean
     */
    private function login($md5_password = true)
    {
        // run md5 on password if needed
        $password = ($md5_password ? md5($this->password) : $this->password);

        $result = $this->rest_request(
            'login',
            array(
                'user_auth' => array(
                    'user_name' => $this->username,
                    'password' => $password
                ),
                'name_value_list' => array(
                    array(
                        'name' => 'notifyonsave',
                        'value' => 'true'
                    )
                )
            )
        );

        if (isset($result['id'])) {
            $this->session = $result['id'];
            $this->userinfo = $this->adjustNameValueList(
                $result['name_value_list']);

            return TRUE;
        }

        $this->error = $result;

        if (!(isset($this->error['name']) &&
            isset($this->error['number']) &&
            isset($this->error['description']))) {
            $this->error['name'] = "Unknown Error";
            $this->error['number'] = -1;
            $this->error['description'] = "We are having technical difficulties. We apologize for the inconvenience.";
        }

        return FALSE;
    }

    /**
     * Set a curl object, mainly used for testing
     *
     * @param \Alexsoft\Curl $curl
     * @return \Asakusuma\SugarWrapper\Rest
     */
    public function setCurl(\Alexsoft\Curl $curl)
    {
        $this->request = $curl;

        return $this;
    }

    /**
     * Returns the curl object, or creates one
     *
     * @return \Alexsoft\Curl
     */
    public function getCurl()
    {
        if ($this->request === null) {
            $this->request = new Curl($this->rest_url);
        }

        return $this->request;
    }

    /**
     * Makes an API call given a call name and arguments checkout
     * http://developers.sugarcrm.com/documentation.php for documentation on the
     * specific API calls
     *
     * @param string $call_name the API call name
     * @param array $call_arguments the arguments for the API call
     * @return array
     */
    private function rest_request($call_name, $call_arguments)
    {
        $request = $this->getCurl();
        $request->addData(
            array(
                'method' => $call_name,
                'input_type' => 'JSON',
                'response_type' => 'JSON',
                'rest_data' => json_encode($call_arguments)
            )
        );
        if($call_name == 'set_entry') {
            $request->addHeaders(array('Expect'=>' '));
        }

        $output = $request->post();
        $response_data = json_decode(html_entity_decode($output['body']), true);

        return $response_data;
    }

    /**
     * Checks to see if the given string is in the valid format for a SugarCRM
     * record ID. This is for input data sanitation, does not actually check to
     * see if there is a record with the given ID.
     *
     * @param string $id the SugarCRM record ID
     * @return boolean
     */
    public function is_valid_id($id)
    {
        if (!is_string($id)) {
            return FALSE;
        }

        return preg_match("/[0-9a-z\-]+/", $id);
    }

    /**
     *
     * @param string $module
     * @param string $query
     * @return boolean
     */
    public function count_records($module, $query)
    {
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

        if (isset($result['result_count'])) {
            return $result['result_count'];
        }

        return FALSE;
    }

    /**
     * Retrieves Sugar Bean records. Essentially returns the result of a
     * SELECT SQL statement, given a base module, any number of related of modules,
     * and respective fields for each module. Each row returned represents a
     * single record of the base module. Each row may have multiple records from.
     * related modules.
     *
     * @param string $module the SugarCRM module name. Usually first letter
     *                       capitalized. This is the name of the base module.
     *                       In other words, any other modules involved in the
     *                       query will be related to the given base module.
     * @param array $fields  the fields you want to retrieve, based on the
     *                       module:
     * <pre>
     *                       array(
     *                           'Cases' => array(
     *                               'field_name',
     *                               'some_other_field_name'
     *                           ),
     *                           'Accounts' => array(
     *                               'field_name',
     *                               'some_other_field_name'
     *                           )
     *                       )
     * </pre>
     * @param array $options Lets you set options for the query
     * <pre>
     * $options['limit'] = Limit how many records returned
     * $options['offset'] = Query offset
     * $options['where'] = WHERE clause for an SQL statement
     * $options['order_by'] = ORDER BY clause for an SQL statement
     * </pre>
     * @return array
     */
    public function get_with_related($module, $fields, $options = array())
    {
        if (sizeof($fields) < 1) {
            return FALSE;
        }
        if (!isset($fields[$module])) {
            return FALSE;
        }

        //Set the defaults for the options
        $options = array_merge(
            array(
                'limit'    => 20,
                'offset'   => 0,
                'where'    => null,
                'order_by' => null,
            ),
            $options
        );

        $base_fields = $fields[$module];
        unset($fields[$module]);

        $relationships = array();

        foreach ($fields as $related_module => $fields_list) {
            $relationships[] = array(
                'name' => strtolower($related_module),
                'value' => $fields_list
            );
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
            'deleted' => false
        );

        $result = $this->rest_request(
            'get_entry_list',
            $call_arguments
        );

        return $result;
    }

    /**
     * Retrieves Sugar Bean records. Essentially returns the result of a
     * SELECT SQL statement.
     *
     * @param string $module the SugarCRM module name. Usually first letter
     *                       capitalized. This is the name of the base module.
     *                       In other words, any other modules involved in the
     *                       query will be related to the given base module.
     * @param array $fields  the fields you want to retrieve, based on the
     *                       module:
     * <pre>
     *                       array(
     *                           'field_name',
     *                           'some_other_field_name'
     *                       )
     * </pre>
     * @param array $options Lets you set options for the query
     * <pre>
     * $options['limit'] = Limit how many records returned
     * $options['offset'] = Query offset
     * $options['where'] = WHERE clause for an SQL statement
     * $options['order_by'] = ORDER BY clause for an SQL statement
     * </pre>
     * @return array
     */
    public function get($module, $fields, $options = array())
    {
        $results = $this->get_with_related(
            $module,
            array($module => $fields),
            $options
        );

        $records = array();

        if ($results) {
            foreach ($results['entry_list'] as $entry) {
                $records[] = $this->adjustNameValueList(
                    $entry['name_value_list']);
            }
        }

        return $records;
    }

    /**
     * Saves or creates a SugarCRM record, depending on whether or not the 'id'
     * field in the $values parameter is set.
     *
     * @param string $module the SugarCRM module name. Usually first letter capitalized.
     * @param array $values the data of the record to be set in the form:
     * <pre>
     * array(
     *     'id' => 'some value',
     *     'field_name' => 'some other value'
     * )
     * </pre>
     * @return array
     */
    public function set($module, $values)
    {
        $call_arguments = array(
            'session' => $this->session,
            'module_name' => $module,
            'name_value_list' => $values,
        );

        $result = $this->rest_request(
            'set_entry', $call_arguments
        );

        return $result;
    }

    /**
     * Prints the results of an API call in a nice div layout
     * @param array $results
     * @codeCoverageIgnore
     */
    public function print_results($results)
    {
        if (isset($results['entry_list'][0]['module_name'])) {
            $module_name = $results['entry_list'][0]['module_name'];

            echo "<h1>" . $module_name . "</h1>";

            foreach ($results['entry_list'] as $i => $entry) {
                echo "<div class='first'>";

                foreach ($entry['name_value_list'] as $field) {
                    echo "<div class='second'>" . $field['name'] . " = " . $field['value'] . "</div>";
                }

                if (isset($results['relationship_list'][$i])) {
                    foreach ($results['relationship_list'][$i] as $module) {
                        echo "<div class='second'><b>related " . $module['name'] . "</b><br/>";

                        foreach ($module['records'] as $x => $record) {
                            echo "<div class='third'>";

                            foreach ($record as $field) {
                                echo "<div class='fourth'>" . $field['name'] . " = " . $field['value'] . "</div>";
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
     * Setup the relationship between modules
     *
     * @param string $module_name
     * @param string $module_id
     * @param string $link_field_name
     * @param string $related_ids
     * @return array
     */
    public function set_relationship(
        $module_name,
        $module_id,
        $link_field_name,
        $related_ids,
        $delete = 0
    ) {
        $call_arguments = array(
            'session' => $this->session,
            'module_name' => $module_name,
            'module_id' => $module_id,
            'link_field_name' => $link_field_name,
            'related_ids' => array($related_ids),
            'name_value_list' => array(),
            'delete' => $delete,
        );

        $result = $this->rest_request(
            'set_relationship', $call_arguments
        );

        return $result;
    }

    /**
     * Gets the attachment of a note given an id. See README for an example on
     * use.
     *
     * @param type $note_id the SugarCRM record ID
     * @return false|array Attachment data in an array on success. Actual file
     * data will be in binary format. Otherwise FALSE.
     */
    public function get_note_attachment($note_id)
    {
        if ($this->is_valid_id($note_id)) {
            $call_arguments = array(
                'session' => $this->session,
                'id' => $note_id
            );

            $result = $this->rest_request(
                'get_note_attachment', $call_arguments
            );

            return $result;
        }

        return FALSE;
    }

    /**
     * Sets the attachment for a note. Will replace the old note if one already
     * exists. See README for example on use
     *
     * @param string $note_id the SugarCRM record ID
     * @param string $file the file in binary format
     * @param string $filename the name of the file
     * @return array
     */
    public function set_note_attachment($note_id, $file, $filename)
    {

        $call_arguments = array(
            'session' => $this->session,
            'note' => array(
                'id' => $note_id,
                'file' => $file,
                'filename' => $filename,
                'related_module_name' => 'Cases'
            )
        );

        $result = $this->rest_request(
                'set_note_attachment', $call_arguments
        );

        return $result;
    }

    /**
     * Retrieve the list of available modules on the system available to the
     * currently logged in user.
     *
     * @return array
     */
    public function get_available_modules()
    {
        $call_arguments = array(
            'session' => $this->session
        );

        $result = $this->rest_request(
                'get_available_modules', $call_arguments
        );

        return $result;
    }

    /**
     * Given a list of modules to search and a search string, return the id,
     * module_name, along with the fields.  We will support Accounts, Bug Tracker,
     * Cases, Contacts, Leads, Opportunities, Project, ProjectTask, and Quotes.
     *
     * @param string $search_string The name of the string to search
     * @param array $modules The array of modules to query
     * @param int $offset A specified offset in the query
     * @param int $max_results Max number of records to return
     * @return array
     */
    public function search_by_module($search_string, $modules, $offset, $max_results)
    {
        $call_arguments = array(
            'session' => $this->session,
            'search_string' => $search_string,
            'modules' => $modules,
            'offset' => $offset,
            'max_results' => $max_results,
        );

        $result = $this->rest_request(
                'search_by_module', $call_arguments
        );

        return $result;
    }

    /**
     * Simple getter for logged_in private variable
     *
     * @return boolean
     */
    function is_logged_in()
    {
        return $this->logged_in;
    }

    /**
     * Closes the API connection when the PHP class object is destroyed
     * @codeCoverageIgnore
     */
    function __destruct()
    {
        if ($this->logged_in) {
            $l = $this->rest_request(
                'logout', array(
                    'session' => $this->session
                )
            );
        }
    }
}
