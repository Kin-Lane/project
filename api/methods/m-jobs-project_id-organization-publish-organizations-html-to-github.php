<?php
$route = '/jobs/:project_id/organization/publish-organizations-html-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$ReturnHTML = '<table width="100%" border="0" cellpadding="3" cellspacing="2">';

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
	//	echo $TagQuery;
		$TagResult = mysql_query($TagQuery) or die('Query failed: ' . mysql_error());

		while ($Tag = mysql_fetch_assoc($TagResult))
			{

			$companies = array();
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

			$toggle = 0;
			$company_count = 0;
			$swagger_count = 0;
			$api_count = 0;

			if(count($Organizations) > 0)
				{
				foreach($Organizations as $Companys)
					{

					$host = "organization.api.kinlane.com";
					$Company_ID = $Companys['organization_id'];
					$Company_ID = prepareIdIn($Company_ID,$host);

					$Name = $Companys['name'];
					$Details = $Companys['details'];
					$Screenshot_URL = $Companys['photo'];

					$url = $Companys['url'];
					$blog_url = $Companys['blog_url'];
					$blog_rss_url = $Companys['blog_rss_url'];
					$twitter_url = $Companys['twitter_url'];
					$github_url = $Companys['github_url'];
					$apisjson_url = $Companys['apisjson_url'];

					$photo = $Companys['photo'];
					$photo_width = $Companys['photo_width'];

					$tag_url = $Companys['tag_url'];
					$tag_description = $Companys['tag_description'];

					$Details = strip_tags($Details);
					$Details = str_replace("&nbsp;", "", $Details);

					if($tag_description!=''){ $Details = $tag_description; }
					if($tag_url!=''){ $url = $tag_url; }

					$Stack = array();

					$row = '<tr>' . chr(10);

    				$row .= '<td align="left" width="80%" style="border-top: 1px solid #CCC; padding-top:18px;">' . chr(10);

    				$row .= '<a href="' . $url . '" id="home-logo-link-' . $Company_ID . '"><img src="' . $photo . '" width="200" align="left" style="padding: 15px;" /></a>' . chr(10);

					//$row .= '</td>' . chr(10);
					//$row .= '<td align="left" width="35%" style="border-top: 1px solid #CCC;">' . chr(10);

    				$row .= '<a href="' . $url . '" id="home-name-link-' . $Company_ID . '" style="color: #000;"><strong>' . $Name . '</strong></a>' . chr(10);

    				$row .= $Details . chr(10);

    				$row .= '</td>' . chr(10);

    				$row .= '<td width="50" align="center" style="border-top: 1px solid #CCC;">';
    				if($url!='')
    					{
    					$row .= '<a href="' . $url . '" target="_blank" title="Website" id="home-icon-' . $Company_ID . '"><img id="home-icon-img-' . $Company_ID . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-home-icon.jpeg" width="28" align="center" /></a>' . chr(10);
						}
					$row .= '</td>' . chr(10);
					$row .= '<td width="50" align="center" style="border-top: 1px solid #CCC;">';
					if($blog_url!='')
						{
    					$row .= '<a href="' . $blog_url . '" target="_blank" title="Blog" id="blog-icon-' . $Company_ID . '"><img id="blog-icon-img-' . $Company_ID . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-blog-icon.png" width="28" align="center" /></a>' . chr(10);
						}
					$row .= '</td>' . chr(10);
					$row .= '<td width="50" align="center" style="border-top: 1px solid #CCC;">';
					if($blog_rss_url!='')
						{
    					$row .= '<a href="' . $blog_rss_url . '" target="_blank" title="Blog RSS" id="blogrss-icon-' . $Company_ID . '"><img id="blogrss-icon-img-' . $Company_ID . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-rss-icon.png" width="28" align="center" /></a>' . chr(10);
						}
					$row .= '</td>' . chr(10);
					$row .= '<td width="50" align="center" style="border-top: 1px solid #CCC;">';
					if($twitter_url!='')
						{
    					$row .= '<a href="' . $twitter_url . '" target="_blank" title="Twitter" id="twitter-icon-' . $Company_ID . '"><img id="twitter-icon-img-' . $Company_ID . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-twitter-icon.png" width="33" align="center" /></a>' . chr(10);
						}
					$row .= '</td>' . chr(10);
					$row .= '<td width="50" align="center" style="border-top: 1px solid #CCC;">';
					if($github_url!='')
						{
    					$row .= '<a href="' . $github_url . '" target="_blank" title="Github" id="github-icon-' . $Company_ID . '"><img id="github-icon-img-' . $Company_ID . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-github-icon.png" width="35" align="center" /></a>' . chr(10);
						}
					$row .= '</td>' . chr(10);
					$row .= '<td width="50" align="center" style="border-top: 1px solid #CCC;">';
					if($apisjson_url!='')
						{
    					$row .= '<a href="' . $apisjson_url . '" target="_blank" title="APIs.json" id="apisjson-icon-' . $Company_ID . '"><img id="apisjson-icon-img-' . $Company_ID . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-a.png" width="35" align="center" /></a>' . chr(10);
						}
					$row .= '</td>' . chr(10);
					$row .= '<td width="150" align="center" id="github-issue-' . $Company_ID . '" style="font-size: 11px;" style="border-top: 1px solid #CCC;">';
					$row .= '</td>' . chr(10);
    				$row .= '</tr>' . chr(10);

					// APIs
					$Stack['apis'] = array();
					//$APIQuery = "SELECT a.API_ID, a.Name, (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Website' LIMIT 1) AS Website_URL, (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Swagger' LIMIT 1) AS Swagger_URL, (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Documentation' LIMIT 1) AS Documentation_URL FROM api a JOIN company_api_pivot cap ON a.API_ID = cap.API_ID WHERE cap.Company_ID = " . $Company_ID;

					$APIQuery = "SELECT DISTINCT a.API_ID, a.Name,";
					$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Website' LIMIT 1) AS Website_URL,";
					$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Swagger' LIMIT 1) AS Swagger_URL,";
					$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Documentation' LIMIT 1) AS Documentation_URL,";
					$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'SDKs.io' LIMIT 1) AS SDKsIO_URL";
					$APIQuery .= " FROM api a";
					$APIQuery .= " JOIN company_api_pivot cap ON a.API_ID = cap.API_ID";
					$APIQuery .= " JOIN api_tag_pivot atp ON a.API_ID = atp.API_ID";
					$APIQuery .= " JOIN tags t ON atp.Tag_ID = t.Tag_ID";

					//echo $APIQuery . "<br /><br />";

					if($thistag=='API-Stack')
						{
						$APIQuery .= " WHERE cap.Company_ID = " . $Company_ID . " AND t.Tag LIKE '%Stack'";
						}
					else
						{
						$APIQuery .= " WHERE cap.Company_ID = " . $Company_ID . " AND t.Tag = '" . $thistag . "'";
						}

					$APIQuery .= " ORDER BY a.Name";

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

						$row .= '<tr>' . chr(10);

	    				//$row .= '<td align="center" width="50" style="padding-bottom: 10px;">' . chr(10);
						//$row .= '</td>' . chr(10);
						$row .= '<td align="left" style="padding-bottom: 5px;padding-left: 55px; font-weight: bold;">' . chr(10);
						$row .= '<a href="' . $Website_URL . '" target="_blank" title="Documentation">' . $API_Name . '</a>';
						$row .= '</td>' . chr(10);

						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						if($Website_URL!=='')
							{
							$row .= '<a href="' . $Website_URL . '" target="_blank" title="Documentation"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-home-icon.jpeg" width="35" /></a>';
							}
						$row .= '</td>' . chr(10);

						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						if($Documentation_URL!=='')
							{
							$row .= '<a href="' . $Documentation_URL . '" target="_blank" title="Documentation"><img src="http://kinlane-productions.s3.amazonaws.com/api-evangelist-site/building-blocks/bw-list.png" width="35" /></a>';
							}
						$row .= '</td>' . chr(10);

						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						if($Swagger_URL!=='')
							{
							$row .= '<a href="' . $Swagger_URL . '" target="_blank" title="Swagger"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-swagger-round.png" width="35" /></a>';
							$swagger_count++;
							}
						$row .= '</td>' . chr(10);

						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center" style="padding-bottom: 10px;">' . chr(10);
						$row .= '</td>' . chr(10);

						$row .= '</tr>' . chr(10);

						$api_count++;

						}

					$row .= '<tr><td style="height: 18px; border: 1px solid #FFF;" colspan="8"></td></tr>';

					$ReturnHTML .= $row;

					$F = array();
					$F['name'] = $Name;
					$F['details'] = $Details;
					$F['photo'] = $photo;
					$F['url'] = $url;
					array_push($companies, $F);

					if($toggle==1){ $toggle = 0; } else { $toggle = 1; }

					$company_count++;

					}
				}
			$close = '<tr><td style="height: 18px; border-bottom: 1px solid #CCC;" colspan="8"></td></tr>';
			$ReturnHTML .= $close . "</table>";

			$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");

			$PageBody = "";

			$company_content = "";
			$company_content .= $PageHeader . chr(10);
		    $company_content .= $PageBody . chr(10);
		    $company_content .= $ReturnHTML . chr(10);

			$write_company_file = "companies.html";

			// Github
			$GitHubClient = new GitHubClient();
			$GitHubClient->setCredentials($guser,$gpass);

			$owner = $project_github_user;
			$ref = "gh-pages";

			try
				{
				$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $write_company_file);

				$name = $CheckFile->getname();
				$content = base64_decode($CheckFile->getcontent());
				$sha = $CheckFile->getsha();

				$message = "Updating " . $write_company_file . " via Laneworks CMS Publish";
				$content = base64_encode($company_content);

				$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $write_company_file, $message, $content, $sha, $ref);
				}
			catch (Exception $e)
				{

				$message = "Adding " . $write_company_file . " via Laneworks CMS Publish";
				$content = base64_encode($company_content);

				$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $write_company_file, $message, $content, $ref);

				}

			}
		}

		$ReturnObject = array();
		$ReturnObject['companies'] = $companies;
		$ReturnObject['file'] = $write_company_file;
		$ReturnObject['company_count'] = $company_count;
		$ReturnObject['api_count'] = $api_count;
		$ReturnObject['swagger_count'] = $swagger_count;

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
