<?php
$route = '/project/view/';
$app->post($route, function () use ($app){

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();

	$HTTP_REFERER = $_SERVER['HTTP_REFERER'];
	echo $HTTP_REFERER . chr(10);
	$base_url_array = parse_url($HTTP_REFERER);
	$base_host = $base_url_array['host'];

	echo $base_host . chr(10);

	//$app->response()->header("Content-Type", "application/json");
	//echo format_json(json_encode($ReturnObject));

	});
?>
