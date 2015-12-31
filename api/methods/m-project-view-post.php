<?php
$route = '/project/view/';
$app->post($route, function () use ($app){

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();

	//if(isset($params['title'])){ $title = mysql_real_escape_string($params['title']); } else { $title = 'No Title'; }

	var_dump($_SERVER);

	//$app->response()->header("Content-Type", "application/json");
	//echo format_json(json_encode($ReturnObject));

	});
?>
