<!doctype html>  

	<head>
  		<meta charset="utf-8">
  		<title>SugarCRM REST API Wrapper Class Example</title>
		<style>
			body {
				color:#333;
				font-family:verdana,arial;
				font-size:14px;
				float:none;
				clear:both;
			}
			
			div {
				padding:4px;
				margin:4px;
			}
			
			div.first {
				border:1px solid #8F8F8F;
				margin-bottom:20px;
			}
		
			div.second {
				border:1px solid #9F9F9F;
			}
			
			div.third {
				border:1px solid #CCC;
			}
			
			div.fourth{
				border:1px solid #CFCFCF;
			}
		</style>
	</head>

<body>

<?php
require_once("sugar_rest.php");
$sugar = new Sugar_REST();
$error = $sugar->get_error();
if($error !== FALSE) {
	echo $error['name'];
}
$results = $sugar->get_with_related("Accounts", array("Accounts" => array('id','name'), "Cases" => array('id','status')));
$sugar->print_results($results);
?>