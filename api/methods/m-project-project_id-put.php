<?php
$route = '/project/:project_id/';
$app->put($route, function ($project_id) use ($app){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

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

  	$Query = "SELECT * FROM project WHERE Project_ID = " . $project_id;
	//echo $Query . "<br />";
	$Database = mysql_query($Query) or die('Query failed: ' . mysql_error());

	if($Database && mysql_num_rows($Database))
		{
		$query = "UPDATE project SET";

		$query .= " Opening = 'nothing'";

		if($post_date!='') { $query .= ", Title = '" . $title . "',"; }
		if($author!='') { $query .= ", Summary = '" . $summary . "',"; }
		if($summary!='') { $query .= ", Github_Repo = '" . $github_repo . "',"; }
		if($body!='') { $query .= ", Subdomain = '" . $subdomain . "',"; }
		if($footer!='') { $query .= ", Type = '" . $type . "',"; }
		if($curated_id!='') { $query .= ", Image = '" . $image . "',"; }
		if($curated_id!='') { $query .= ", Image_Width = '" . $image_width . "',"; }

		$query .= " Closing = 'nothing'";

		$query .= " WHERE project_id = '" . $project_id . "'";

		//echo $query . "<br />";
		mysql_query($query) or die('Query failed: ' . mysql_error());
		}

		$F['tags'] = array();

		$TagQuery = "SELECT t.tag_id, t.tag from tags t";
		$TagQuery .= " INNER JOIN project_tag_pivot ptp ON t.tag_id = ptp.tag_id";
		$TagQuery .= " WHERE ptp.Project_ID = " . $project_id;
		$TagQuery .= " ORDER BY t.tag DESC";
		$TagResult = mysql_query($TagQuery) or die('Query failed: ' . mysql_error());

		while ($Tag = mysql_fetch_assoc($TagResult))
			{
			$thistag = $Tag['tag'];

			$T = array();
			$T = $thistag;
			array_push($F['tags'], $T);
			//echo $thistag . "<br />";
			if($thistag=='Archive')
				{
				$archive = 1;
				}
			}

	$project_id = prepareIdOut($project_id,$host);					

	$F = array();
	$F['project_id'] = $project_id;
	$F['post_date'] = $post_date;
	$F['title'] = $title;
	$F['author'] = $author;
	$F['summary'] = $summary;
	$F['body'] = $body;
	$F['footer'] = $footer;
	$F['curated_id'] = $curated_id;

	array_push($ReturnObject, $F);

	$app->response()->header("Content-Type", "application/json");
	echo stripslashes(format_json(json_encode($ReturnObject)));

	});
?>
