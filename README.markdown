SugarCRM REST API Wrapper Class
===============================
by Asa Kusuma

http://www.asakusuma.com/

License: MIT


Contents
--------
1. About
2. Installation
3. Usage Example
4. Notes
5. get_note_attachment() Example
6. set_note_attachment() Example


1.About
-------
- PHP wrapper class for interacting with a SugarCRM REST API
- Creating, reading, and updating capability
- More info on SugarCRM: http://www.sugarcrm.com/
- API docs: http://developers.sugarcrm.com/
- Designed to work with SugarCRM v.6

2. Installation via Composer
----------------------------
Edit composer.json

	{
		"repositories": [
    			{
    				"type": "vcs",
        			"url": "https://github.com/Daursu/curl"
        		}
    		],
		"require": {
			"asakusuma/sugarcrm-wrapper": "dev-master"
		},
		"minimum-stability": "dev"
	}

Then install with composer

	$ composer install

3.Usage Example
---------------
	$sugar = new \Asakusuma\SugarWrapper\Rest;

	$sugar->setUrl('https://sugarcrm/service/v2/rest.php');
	$sugar->setUsername('user');
	$sugar->setPassword('password');

	$sugar->connect();

	$results = $sugar->get("Accounts",array('id','name'));

	print_r($results);

See example.php for another example.


4.Notes
-------
- The `is_valid_id()` function may need to modify for different versions
of SugarCRM.
- Different versions of SugarCRM have different ID formats.


5.get_note_attachment() Example
-------------------------------
>This example outputs the contents of a note's attachment, given the
>note ID. Assumes $note_id contains the ID of the note you wish to modify.

	$sugar = new \Asakusuma\SugarWrapper\Rest;

	$sugar->setUrl('https://sugarcrm/service/v2/rest.php');
	$sugar->setUsername('user');
	$sugar->setPassword('password');

	$sugar->connect();

	$result = $sugar->get_note_attachment($note_id);
	$filename = $result['note_attachment']['filename'];
	$file = $result['note_attachment']['file'];

	$file = base64_decode($file);
	header("Cache-Control: no-cache private");
	header("Content-Description: File Transfer");
	header('Content-disposition: attachment; filename='.$filename);
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Transfer-Encoding: binary");
	header('Content-Length: '. strlen($file));
	echo $file;
	exit;


6.set_note_attachment() Example
-------------------------------
>This example illustrates how to set a note's attachment from an html form.
>Assumes $note_id contains the ID of the note you wish to modify.

### HTML Code
	<form method="post" action="example.php" enctype="multipart/form-data">
    	<input name="note_file" type="file" />
  		<input type="submit" value="Go" />
	</form>

### PHP Code (example.php)
	$sugar = new \Asakusuma\SugarWrapper\Rest;

	$sugar->setUrl('https://sugarcrm/service/v2/rest.php');
	$sugar->setUsername('user');
	$sugar->setPassword('password');

	$sugar->connect();

	if ($_FILES["note_file"]["error"] > 0) {
    	// Error: $_FILES["file"]["error"]
	} else if(isset($_FILES['note_file']['tmp_name']) && $_FILES['note_file']['tmp_name']!="") {
		$handle = fopen($_FILES['note_file']['tmp_name'], "rb");
		$filename = $_FILES['note_file']['name'];
		$contents = fread($handle, filesize($_FILES['note_file']['tmp_name']));
		$binary = base64_encode($contents);
		$file_results = $sugar->set_note_attachment($note_id,$binary,$filename);
	}

7.get_available_modules() Example
-------------------------------
>This example illustrates how to get the available modules in Sugar.  All of them.
>This is a handy function to use when building future proof sugarcrm plugins.
>

### PHP Code (example.php)
	$sugar = new \Asakusuma\SugarWrapper\Rest;
	$modules = $sugar->get_available_modules();
>BAM! Now loop through the array that was returned and stored in $modules.  You could use this
>to display a dropdown in the admin panel that displays all modules a user would want to connect your
>sugarcrm plugin to.
