<?php
$route = '/jobs/:project_id/apisjson/publish-all-organizations-apisjson-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();





  $app->response()->header("Content-Type", "application/json");
  echo format_json(json_encode($ReturnObject));

});
?>
