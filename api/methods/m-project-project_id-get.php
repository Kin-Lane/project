<?php
$route = '/project/:project_id/';
$app->get($route, function ($project_id)  use ($app){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$ReturnObject = array();

	$Query = "SELECT * FROM project WHERE Project_ID = " . $project_id;

	$DatabaseResult = mysql_query($Query) or die('Query failed: ' . mysql_error());

	while ($Database = mysql_fetch_assoc($DatabaseResult))
		{

		$project_id = $Database['Project_ID'];
		$title = $Database['Title'];
		$summary = $Database['Summary'];
		$github_repo = $Database['Github_Repo'];
		$subdomain = $Database['Subdomain'];
		$type = $Database['Type'];
		$image = $Database['Image'];
		$image_width = $Database['Image_Width'];

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

		// manipulation zone
		$project_id = prepareIdOut($project_id,$host);

		$F = array();
		$F['project_id'] = $project_id;
		$F['title'] = $title;
		$F['summary'] = $summary;
		$F['github_repo'] = $github_repo;
		$F['subdomain'] = $subdomain;
		$F['type'] = $type;
		$F['image'] = $image;
		$F['image_width'] = $image_width;

		$ReturnObject = $F;
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));
	});
?>
