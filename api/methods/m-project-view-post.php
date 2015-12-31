<?php
$route = '/project/view/';
$app->post($route, function () use ($app){

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();
	var_dump($_SERVER);
	$HTTP_REFERER = $_SERVER['HTTP_REFERER'];
	//echo $HTTP_REFERER . chr(10);
	$base_url_array = parse_url($HTTP_REFERER);
	$base_host = $base_url_array['host'];

	echo $base_host . chr(10);

	$hostquery = "SELECT ID FROM stack_network_kinlane_project.whitelist_host WHERE host = '" . $base_host . "'";
	$hostresults = mysql_query($hostquery) or die('Query failed: ' . mysql_error());
	if($hostresults && mysql_num_rows($hostresults))
		{
		$host = mysql_fetch_assoc($hostresults);
		$host_id = $host['ID'];

		$this_month = date('m');
		$this_year = date('Y');
		$table_name = "views_" . $this_year . "_" . $this_month;
		console.log($table_name);

		$checkLikeTableQuery = "show tables from `stack_network_kinlane_project` like " . chr(34) . $table_name . chr(34);
		$checkLikeTableResult = mysql_query($checkLikeTableQuery) or die('Query failed: ' . mysql_error());

		if($checkLikeTableResult && mysql_num_rows($checkLikeTableResult))
		  {
		  $checkLikeTableResult = mysql_fetch_assoc($checkLikeTableResult);
		  }
		else
		  {
		  $CreateTableQuery = "CREATE TABLE  `stack_network_kinlane_project`.`" . $table_name . "` (";
		  $CreateTableQuery .= "`id` int(10) unsigned NOT NULL AUTO_INCREMENT,";
			$CreateTableQuery .= "`host` varchar(100) DEFAULT NULL,";
		  $CreateTableQuery .= "`view_date` datetime NOT NULL,";
		  $CreateTableQuery .= "PRIMARY KEY (`track_id`)";
		  $CreateTableQuery .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;  ";
		  //echo "<br />" . $CreateTableQuery . "<br />";
		  mysql_query($CreateTableQuery) or die('Query failed: ' . mysql_error());
		  }

		$view_date = date('Y-m-d H:i:s');
		$query = "INSERT INTO " . $table_name . "(host,view_date) VALUES('" . mysql_real_escape_string($base_host) . "','" . mysql_real_escape_string($view_date) . "')";
		//echo $query . "<br />";
		mysql_query($query) or die('Query failed: ' . mysql_error());

		}

	//$app->response()->header("Content-Type", "application/json");
	//echo format_json(json_encode($ReturnObject));

	});
?>
