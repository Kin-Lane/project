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

	$CheckTagQuery = "SELECT ID FROM stack_network_kinlane_project.whitelist_host WHERE host = '" . $base_host . "'";
	$CheckTagResults = mysql_query($CheckTagQuery) or die('Query failed: ' . mysql_error());
	if($CheckTagResults && mysql_num_rows($CheckTagResults))
		{
		$Tag = mysql_fetch_assoc($CheckTagResults);
		$tag_id = $Tag['Tag_ID'];

		$this_month = date('m');
		$table_name = "views_" . $this_month;
		console.log($table_name);


		}

	//$app->response()->header("Content-Type", "application/json");
	//echo format_json(json_encode($ReturnObject));

	});
?>
