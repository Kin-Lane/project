<?php
$route = '/project/';
$app->post($route, function () use ($app){

	$Add = 1;
	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();

	if(isset($params['title'])){ $title = mysql_real_escape_string($params['title']); } else { $title = 'No Title'; }
	if(isset($params['summary'])){ $summary = mysql_real_escape_string($params['summary']); } else { $summary = ''; }
	if(isset($params['github_repo'])){ $github_repo = mysql_real_escape_string($params['github_repo']); } else { $github_repo = ''; }
	if(isset($params['subdomain'])){ $subdomain = mysql_real_escape_string($params['$subdomain']); } else { $subdomain = ''; }
	if(isset($params['type'])){ $type = mysql_real_escape_string($params['type']); } else { $type = ''; }
	if(isset($params['image'])){ $image = mysql_real_escape_string($params['image']); } else { $image = ''; }
	if(isset($params['image_width'])){ $image_width = mysql_real_escape_string($params['image_width']); } else { $image_width = ''; }

	$Query = "SELECT * FROM project WHERE Title = '" . $title . "'";
	//echo $Query . "<br />";
	$Database = mysql_query($Query) or die('Query failed: ' . mysql_error());

	if($Database && mysql_num_rows($Database))
		{
		$ThisProject = mysql_fetch_assoc($Database);
		$project_id = $ThisProject['Project_ID'];
		}
	else
		{

		$Query = "INSERT INTO project(Title,Summary,Github_Repo,Subdomain,Type,Image,Image_Width)";
		$Query .= " VALUES(";
		$Query .= "'" . mysql_real_escape_string($title) . "',";
		$Query .= "'" . mysql_real_escape_string($summary) . "',";
		$Query .= "'" . mysql_real_escape_string($github_repo) . "',";
		$Query .= "'" . mysql_real_escape_string($subdomain) . "',";
		$Query .= "'" . mysql_real_escape_string($type) . "',";
		$Query .= "'" . mysql_real_escape_string($image) . "',";
		$Query .= mysql_real_escape_string($image_width);
		$Query .= ")";
		//echo $Query . "<br />";
		mysql_query($Query) or die('Query failed: ' . mysql_error());
		$project_id = mysql_insert_id();
		}

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdOut($project_id,$host);

	$ReturnObject['project_id'] = $project_id;

	$app->response()->header("Content-Type", "application/json");
	echo format_json(json_encode($ReturnObject));

	});
?>
