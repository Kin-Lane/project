<?php
$route = '/jobs/:project_id/blog/publish-jekyll-html-to-local-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$htmlurl = "http://repos.laneworks.net/rebuild-blog?project_id=" . $project_id;

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();
	
	$http = curl_init();  
	curl_setopt($http, CURLOPT_URL, $htmlurl);  
	curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);   
	
	$output = curl_exec($http);
	$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
	$info = curl_getinfo($http);
	//var_dump($info);

	echo $output;
	
	//$app->response()->header("Content-Type", "application/json");
	//echo format_json(json_encode($ReturnObject));

	});
?>
