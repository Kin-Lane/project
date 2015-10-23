<?php
$route = '/project/groupbytype/';
$app->get($route, function ()  use ($app){

	$ReturnObject = array();

	$TypeQuery = "SELECT DISTINCT Type FROM project ORDER BY Type";

	$DatabaseTypeResult = mysql_query($TypeQuery) or die('Query failed: ' . mysql_error());

	while ($Type = mysql_fetch_assoc($DatabaseTypeResult))
		{
		$Project_Type = $Type['Type'];

		$ReturnObject[$Project_Type] = array();

		}

	$TypeQuery = "SELECT DISTINCT Type FROM project ORDER BY Type";

	$DatabaseTypeResult = mysql_query($TypeQuery) or die('Query failed: ' . mysql_error());

	while ($Type = mysql_fetch_assoc($DatabaseTypeResult))
		{

		$Project_Type = $Type['Type'];

		$Query = "SELECT * FROM project WHERE Type = '" . $Project_Type . "' ORDER BY Title";

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
			$host = $_SERVER['HTTP_HOST'];
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

			array_push($ReturnObject[$Project_Type], $F);

			}
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));
	});
?>
