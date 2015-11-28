<?php
$route = '/jobs/:project_id/organization/publish-organizations-with-apis-json-to-github/';
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
			//var_dump($Organizations);
			if(count($Organizations) > 0)
				{
				foreach($Organizations as $Companys)
					{

					$Company_ID = $Companys['organization_id'];
					$host = "organization.api.kinlane.com";
					$Company_ID = prepareIdIn($Company_ID,$host);

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

					$Provider_Tags = "";
					$TagQuery = "SELECT t.Tag_ID, t.Tag FROM tags t INNER JOIN company_tag_pivot sptp ON t.Tag_ID = sptp.Tag_ID WHERE sptp.Company_ID = " . $Company_ID . " ORDER BY Tag";
					echo $TagQuery . "<br />";
					$TagResult = mysql_query($TagQuery) or die('Query failed: ' . mysql_error());
					$First = 1;
					while ($ThisTag = mysql_fetch_assoc($TagResult))
						{
						$Tag = $ThisTag['Tag'];
						if($First==1){
							$First=2;
							$Provider_Tags .= $Tag;
							}
						else {
							$Provider_Tags .= "," . $Tag;
							}
						}

					$Details = strip_tags($Details);
					$Details = str_replace("&nbsp;", "", $Details);

					// APIs
					$Stack['apis'] = array();
					$APIQuery = "SELECT a.API_ID, a.Name, (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Website' LIMIT 1) AS Website_URL, (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Swagger' LIMIT 1) AS Swagger_URL, (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Documentation' LIMIT 1) AS Documentation_URL FROM api a WHERE a.Company_ID = " . $Company_ID;
					echo $APIQuery . "<br />";
					$APIResult = mysql_query($APIQuery) or die('Query failed: ' . mysql_error());
					while ($APIRow = mysql_fetch_assoc($APIResult))
						{

						$API_ID = $APIRow['API_ID'];

						$API_Name = $APIRow['Name'];

						$API_Name_Slug = PrepareFileName($API_Name);

						//echo " -- API-Name " . $API_Name . "<br />";

						$Website_URL = trim($APIRow['Website_URL']);
						$Swagger_URL = trim($APIRow['Swagger_URL']);
						$Documentation_URL = trim($APIRow['Documentation_URL']);

						$APIStack = array();

						$APIStack['id'] = $API_ID;
						$APIStack['name'] = $API_Name;
						$APIStack['website-url'] = $Website_URL;
						$APIStack['swagger-url'] = $Swagger_URL;
						$APIStack['documentation-url'] = $Documentation_URL;

						array_push($Stack['apis'], $APIStack);
						}

						$host = $_SERVER['HTTP_HOST'];
						$Company_ID = prepareIdOut($Company_ID,$host);

						$Stack = array();

						$Stack['id'] = $Company_ID;
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

			if($project_github_repo!='api-stack')
				{
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
		}

		$app->response()->header("Content-Type", "application/json");
		echo $company_content;

	});
?>
