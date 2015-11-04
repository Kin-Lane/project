<?php
$route = '/jobs/:project_id/buildingblock/publish-building-blocks-json-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$ReturnObject = array();

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

		$TagQuery = "SELECT t.tag_id, t.tag from tags t";
		$TagQuery .= " INNER JOIN project_tag_pivot ptp ON t.tag_id = ptp.tag_id";
		$TagQuery .= " WHERE ptp.Project_ID = " . $project_id;
		$TagQuery .= " ORDER BY t.tag DESC";
		$TagResult = mysql_query($TagQuery) or die('Query failed: ' . mysql_error());

		while ($Tag = mysql_fetch_assoc($TagResult))
			{

			$thistag = $Tag['tag'];
			//echo $thistag . "<br />";

			$url = "http://buildingblock.api.kinlane.com/buildingblocks/tags/" . urlencode($thistag) . "/?appid=" . $appid . "&appkey=" . $appkey;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			//echo $output;
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			$BuildingBlocks = json_decode($output,true);

			if(count($BuildingBlocks) > 0)
				{
				foreach($BuildingBlocks as $BuildingBlocks)
					{

					$building_block_id = $BuildingBlocks['building_block_id'];
					$building_block_category_id = $BuildingBlocks['building_block_category_id'];
					$name = $BuildingBlocks['name'];
					$about = strip_tags($BuildingBlocks['about']);
					$category_id = $BuildingBlocks['category_id'];
					$category = $BuildingBlocks['category'];
					$image = $BuildingBlocks['image'];
					$image_width = $BuildingBlocks['image_width'];
					$sort_order = $BuildingBlocks['sort_order'];

					// manipulation zone

					$building_block_id = prepareIdOut($building_block_id,$host);
					$building_block_category_id = prepareIdOut($building_block_category_id,$host);

					$F = array();
					$F['building_block_id'] = $building_block_id;
					$F['building_block_category_id'] = $building_block_category_id;
					$F['name'] = $name;
					$F['about'] = $about;
					$F['category_id'] = $category_id;
					$F['category'] = $category;
					$F['image'] = $image;
					$F['image_width'] = $image_width;
					$F['sort_order'] = $sort_order;

					array_push($ReturnObject, $F);

					}
				}

			$company_content = stripslashes(prettyPrint(json_encode($ReturnObject)));

			$data_store_file = "data/buildingblocks.json";

			// Github
			$GitHubClient = new GitHubClient();
			$GitHubClient->setCredentials($guser,$gpass);

			$owner = 'kinlane';
			$ref = "gh-pages";

			try
				{
				$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $data_store_file);

				$name = $CheckFile->getname();
				$content = base64_decode($CheckFile->getcontent());
				$sha = $CheckFile->getsha();

				$message = "Updating " . $data_store_file . " via Laneworks CMS Publish";
				$content = base64_encode($company_content);

				$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $data_store_file, $message, $content, $sha, $ref);
				}
			catch (Exception $e)
				{

				$message = "Adding " . $data_store_file . " via Laneworks CMS Publish";
				$content = base64_encode($company_content);

				$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $data_store_file, $message, $content, $ref);

				}

			}
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
