<?php
$route = '/jobs/:project_id/organization/pull-apisio-apisjson/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

  $host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

 	$request = $app->request();
 	$params = $request->params();

	if(isset($params['skip'])){ $skip = $params['skip']; } else { $skip = 0; }

	$ReturnObject = array();

	$ReturnHTML = '<table width="100%" border="0">';

	$ReturnHTML .= '<tr>' . chr(10);
	$ReturnHTML .= '<td>' . chr(10);
	$ReturnHTML .= '<hr>' . chr(10);
	$ReturnHTML .= '</td>' . chr(10);
	$ReturnHTML .= '</tr>' . chr(10);

 	$request = $app->request();
 	$params = $request->params();

	//echo $project_id;

	$ProjectQuery = "SELECT * FROM project WHERE Project_ID = " . $project_id;
	//echo $ProjectQuery;
	$ProjectResults = mysql_query($ProjectQuery) or die('Query failed: ' . mysql_error());
	if($ProjectResults && mysql_num_rows($ProjectResults))
		{
		$Project = mysql_fetch_assoc($ProjectResults);
		$project_title = $Project['Title'];
		$project_summary = $Project['Summary'];
		$project_github_repo = $Project['Github_Repo'];
		$project_subdomain = $Project['Subdomain'];
		$project_type = $Project['Type'];

		$project_github_url = "https://github.com/kinlane/" . $project_github_repo;
		$project_github_path = '/var/www/html/repos/' . $project_github_repo;

		$next = "";
		$continue = 1;
		$count = 0;
		while($continue == 1)
			{

			if($next=='')
				{
				$url = "http://apis.io/api/apis?sort=createAt";
				}
			else
				{
				$url = $next;
				}
			echo $url . "<br /><hr />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			//var_dump($output);
			$APIs = json_decode($output,true);

			$next = "";
			foreach($APIs as $key => $value)
				{
				if(trim($key)=='paging')
					{
					$next = $value['next'];
					}
				}

			$status = $APIs['status'];
			//echo $status . "<br />";
			//$paging = $APIs['paging'];
			//var_dump($paging);

			foreach($APIs['data'] as $APIs)
				{
				//var_dump($APIs);
				$apisjson_url = $APIs['apiFileUrl'];
				$exist = 0;
				foreach($ReturnObject as $Object)
					{
					if($Object['apisjson_url']==$apisjson_url)
						{
						$exist = 1;
						}
					}
				if($exist==0)
					{
					if (strpos($apisjson_url,'theapistack.com') !== false)
						{
						}
					else
						{
						$F = array();
						$F['apisjson_url'] = $apisjson_url;
						array_push($ReturnObject, $F);
						}
					}
				}

			if($status=='success')
				{
				//echo $next;
				}
			else
				{
				$continue = 0;
				}
			$count++;

			if($count > 125)
				{
				$continue = 0;
				}

			}
		}

		echo "Count: " . count($ReturnObject) . "<br />";

		foreach($ReturnObject as $Object)
			{
			$apisjson_url = $Object['apisjson_url'];
			$apisjson_url = str_replace("api.json","apis.json",$apisjson_url);
			//echo $apisjson_url . "<br />";

			$url = "http://organization.api.kinlane.com/organization/definitions/import/apisjson/.14/?appid=" . $appid . "&appkey=" . $appkey . "&apisjson_url=" . urlencode($apisjson_url);
			echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			//echo $output;
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			$Organizations = json_decode($output,true);

			}

		//$app->response()->header("Content-Type", "application/json");
		echo "<hr />";
		echo format_json(stripslashes(json_encode($ReturnObject)));

	});
?>
