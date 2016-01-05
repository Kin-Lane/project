<?php
$route = '/jobs/:project_id/organization/publish-organizations-json-to-github/';
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

			$url = "http://organization.api.kinlane.com/organization/tags/" . urlencode($thistag) . "/?appid=" . $appid . "&appkey=" . $appkey;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			//echo $output;
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			$Organizations = json_decode($output,true);

			if(count($Organizations) > 0)
				{
				foreach($Organizations as $Companys)
					{

					$company_id = $Companys['organization_id'];
					$Name = $Companys['name'];
					$Details = $Companys['details'];
					$Screenshot_URL = $Companys['photo'];

					$url = $Companys['url'];
					$blog_url = $Companys['blog_url'];
					$blog_rss_url = $Companys['blog_rss_url'];
					$twitter_url = $Companys['twitter_url'];
					$github_url = $Companys['github_url'];

					$photo = $Companys['photo'];
					$photo_width = $Companys['photo_width'];

					$Provider_Tags = $Companys['tags'];

					$tag_url = $Companys['tag_url'];
					$tag_description = $Companys['tag_description'];

					$Details = strip_tags($Details);
					$Details = str_replace("&nbsp;", "", $Details);

					if($tag_description!=''){ $Details = $tag_description; }
					if($tag_url!=''){ $url = $tag_url; }

					$Stack = array();

					$company_id = prepareIdOut($company_id,$host);

					$Stack['id'] = $company_id;
					$Stack['name'] = $Name;
					$Stack['summary'] = substr($Details,0,400);
					$Stack['details'] = $Details;
					$Stack['website'] = $url;
					$Stack['twitter'] = $twitter_url;
					$Stack['github'] = $github_url;
					$Stack['blog'] = $blog_url;
					$Stack['blogrss'] = $blog_rss_url;
					$Stack['logo'] = $photo;
					$Stack['logo_width'] = $photo_width;
					$Stack['screenshot'] = $Screenshot_URL;
					$Stack['tags'] = $Provider_Tags;

					array_push($ReturnObject, $Stack);

					}
				}

			$company_content = stripslashes(prettyPrint(json_encode($ReturnObject)));

			$data_store_file = "data/companies.json";

			// Github
			$GitHubClient = new GitHubClient();
			$GitHubClient->setCredentials($guser,$gpass);

			$owner = $project_github_user;
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
