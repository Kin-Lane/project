<?php
$route = '/jobs/:project_id/curated/publish-curated-html-to-github/';
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
		$project_github_user = $Project['Github_User'];
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
			$page = 0;
			$count = 0;
			$url = "http://curated.api.kinlane.com/curated/tags/" . urlencode($thistag) . "/build/?appid=" . $appid . "&appkey=" . $appkey;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			$CuratedPosts = json_decode($output,true);

			foreach($CuratedPosts as $CuratedPost)
				{

				$curated_id = $CuratedPost['curated_id'];
				$curated_title = $CuratedPost['title'];
				$curated_link = $CuratedPost['link'];
				$curated_image = $CuratedPost['screenshot_url'];
				$github_build = $CuratedPost['github_build'];
				$post_date = $CuratedPost['item_date'];

				$Post_Year = date('Y',strtotime($post_date));
				if(strlen($Post_Year)==1){$Post_Year="0".$Post_Year;}
				$Post_Month = date('m',strtotime($post_date));
				if(strlen($Post_Month)==1){$Post_Month="0".$Post_Month;}
				$Post_Day = date('d',strtotime($post_date));
				if(strlen($Post_Day)==1){$Post_Day="0".$Post_Day;}

				$Host = parse_url($curated_link);
				if(isset($Host['host']))
					{
					$domain = $Host['host'];
					}
				else
					{
					$domain = "";
					}

				$Prep_Title = str_replace(chr(39),"&#39;",$curated_title);
				$Prep_Title = PrepareFileName($Prep_Title);

				$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/blog3.html");

				$PageHeader = str_replace("[Title]",chr(39).htmlentities($curated_title, ENT_QUOTES).chr(39),$PageHeader);
				$PageHeader = str_replace("[Image]",$curated_image,$PageHeader);
				$PageHeader = str_replace("[URL]",$curated_link,$PageHeader);
				$PageHeader = str_replace("[Source]",$curated_link,$PageHeader);
				$PageHeader = str_replace("[Domain]",$domain,$PageHeader);

				$curated_file_name = $Post_Year . "-" . $Post_Month . "-" . $Post_Day . "-" . $Prep_Title . ".html";

				$curated_write_content = $PageHeader;
				$curated_write_content = utf8_encode($curated_write_content);
				$curated_store_file = "_posts/" . $curated_file_name;

				// Github
				$GitHubClient = new GitHubClient();
				$GitHubClient->setCredentials($guser,$gpass);

				$owner = $project_github_user;
				$ref = "gh-pages";

				$curated_id = prepareIdOut($curated_id,$host);

				$F = array();
				$F['curated_id'] = $curated_id;
				$F['title'] = $curated_title;
				$F['url'] = $curated_link;
				$F['domain'] = $domain;
				$F['image'] = $curated_image;
				$F['file'] = $curated_store_file;
				array_push($ReturnObject, $F);

				try
					{
					$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $curated_store_file);

					$name = $CheckFile->getname();
					$content = base64_decode($CheckFile->getcontent());
					$sha = $CheckFile->getsha();

					$message = "Updating " . $curated_store_file . " via Laneworks Publish";
					$content = base64_encode($curated_write_content);
					$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $curated_store_file, $message, $content, $sha, $ref);
					}
				catch (Exception $e)
					{
					$message = "Adding " . $curated_store_file . " via Laneworks Publish";
					$content = base64_encode($curated_write_content);

					$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $curated_store_file, $message, $content, $ref);

					}
				}
			}
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
