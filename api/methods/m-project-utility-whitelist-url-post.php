<?php
$route = '/project/view/';
$app->post($route, function () use ($app){

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();

	// Add URL

	//$app->response()->header("Content-Type", "application/json");
	//echo format_json(json_encode($ReturnObject));

	});
?>
