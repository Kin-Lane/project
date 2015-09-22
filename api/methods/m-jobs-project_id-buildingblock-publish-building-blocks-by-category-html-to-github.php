<?php
$route = '/jobs/:project_id/buildingblock/publish-building-blocks-by-category-html-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$buildingblocks = array();
	$ReturnHTML = '<table width="100%" border="0">';

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

			$url = "http://buildingblock.api.kinlane.com/buildingblocks/bytype/" . urlencode(str_replace("API ","",$thistag)) . "/grouped/?appid=" . $appid . "&appkey=" . $appkey;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			//echo $output;
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			$BuildingBlockCategories = json_decode($output,true);

			if(count($BuildingBlockCategories) > 0)
				{
				foreach($BuildingBlockCategories as $Key => $Value)
					{
					$BuildingBlockCategoryName = $Key;
					$BuildingBlockCategory = $Value;

	    			$row = '<tr>' . chr(10);
    				$row .= '<td align="left" valign="top" style="padding-left:10px; padding-bottom:5px; background-color: #E0E0E0;">' . chr(10);

    				$row .= '<h2>' . $BuildingBlockCategoryName . '</h2>' . chr(10);

    				$row .= '</td>' . chr(10);
    				$row .= '</tr>' . chr(10);

					$F = array();
					$F[$BuildingBlockCategoryName] = array();

					if(count($BuildingBlockCategory) > 0)
						{

						$row .= '<tr>' . chr(10);
						$row .= '<td align="left" valign="top">' . chr(10);
						$row .= '<ul>' . chr(10);

						foreach($BuildingBlockCategory as $BuildingBlocks)
							{

							$building_block_id = $BuildingBlocks['building_block_id'];
							$building_block_category_id = $BuildingBlocks['building_block_category_id'];
							$name = $BuildingBlocks['name'];
							$about = $BuildingBlocks['about'];
							$category_id = $BuildingBlocks['category_id'];
							$category = $BuildingBlocks['category'];
							$image = $BuildingBlocks['image'];
							$image_width = $BuildingBlocks['image_width'];
							if($image_width=='' || $image_width==0){ $image_width = 100; }
							$sort_order = $BuildingBlocks['sort_order'];

							$Stack = array();

		    				//$row .= '<tr>' . chr(10);
		    				//$row .= '<td align="left" valign="top">' . chr(10);

								//if($image!='')
									//{
		    					//$row .= '<img src="' . $image . '" width="100" align="left" style="padding: 15px;" />' . chr(10);
									//}
								//else
//									{
									//$row .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-empty.png" width="100" align="left" style="padding: 15px;" />' . chr(10);
									//}

		    				$row .= '<li><strong>' . $name . '</strong> - ' . $about . '</li>' . chr(10);

		    			//	$row .= '</td>' . chr(10);
		    				//$row .= '</tr>' . chr(10);

							$N = array();
							$N['name'] = $name;
							array_push($F[$BuildingBlockCategoryName], $N);
							}

							$row .= '</ul>' . chr(10);
							$row .= '</td>' . chr(10);
		    			$row .= '</tr>' . chr(10);

						}

					$ReturnHTML .= $row;
					array_push($buildingblocks, $F);

					}
				}

			$ReturnHTML .= "</table>";

			$Page_Name = "Building Blocks";

			$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");
			$PageHeader = str_replace("[Name]",chr(39).htmlentities($Page_Name, ENT_QUOTES).chr(39),$PageHeader);

			$PageBody = '<p>These are the common building blocks I have pullled from my research so far.';

			$company_content = $PageHeader . chr(10) . $PageBody . chr(10) . $ReturnHTML;

			$write_tool_file = "building-blocks.html";

			// Github
			$GitHubClient = new GitHubClient();
			$GitHubClient->setCredentials($guser,$gpass);

			$owner = 'kinlane';
			$ref = "gh-pages";

			try
				{
				$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $write_tool_file);

				$name = $CheckFile->getname();
				$content = base64_decode($CheckFile->getcontent());
				$sha = $CheckFile->getsha();

				$message = "Updating " . $write_tool_file . " via Laneworks CMS Publish";
				$content = base64_encode($company_content);

				$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $write_tool_file, $message, $content, $sha, $ref);
				}
			catch (Exception $e)
				{

				$message = "Adding " . $write_tool_file . " via Laneworks CMS Publish";
				$content = base64_encode($company_content);

				$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $write_tool_file, $message, $content, $ref);

				}

			}
		}

		$ReturnObject = array();
		$ReturnObject['building_blocks_by_category'] = $buildingblocks;
		$ReturnObject['file'] = $write_tool_file;

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
