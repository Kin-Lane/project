<?php
$route = '/jobs/:project_id/organization/publish-organizations-working-html-listing-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$ReturnHTML = '<table width="100%" border="0" cellpadding="1" cellspacing="1" style="margin-left: 100px;">';

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

				$company_count = 0;
				$swagger_count = 0;
				$api_count = 0;

			if(count($Organizations) > 0)
				{
				$toggle = 0;
				foreach($Organizations as $Companys)
					{

					$organization_id = $Companys['organization_id'];
					$host = "organization.api.kinlane.com";
					$organization_id = prepareIdIn($organization_id,$host);
					if(is_numeric($organization_id))
						{
						$Name = $Companys['name'];
						$Details = $Companys['details'];
						$Screenshot_URL = $Companys['photo'];

						$url = $Companys['url'];
						$blog_url = $Companys['blog_url'];
						$blog_rss_url = $Companys['blog_rss_url'];
						$twitter_url = $Companys['twitter_url'];
						$github_url = $Companys['github_url'];
						$apisjson_url = $Companys['apisjson_url'];
						$sdksio_url = $Companys['sdksio_url'];
						$postman_url = $Companys['postman_url'];
						$portal_url = $Companys['portal_url'];

						$photo = $Companys['photo'];
						$photo_width = $Companys['photo_width'];

						$Provider_Tags = $Companys['tags'];

						$Details = strip_tags($Details);
						$Details = str_replace("&nbsp;", "", $Details);

						$Stack = array();

						if($toggle==1)
							{
	    					$row = '<tr style="background-color: #FFF;">' . chr(10);
							}
						else
							{
	    					$row = '<tr>' . chr(10);
							}

	    				//$row .= '<td align="center" width="50">' . chr(10);

	    				//$row .= '<a href="' . $url . '" id="home-logo-link-' . $organization_id . '"><img src="' . $photo . '" width="70" align="left" style="padding: 15px;" /></a>' . chr(10);

							//$row .= '</td>' . chr(10);

							$row .= '<td align="left">' . chr(10);

	    				$row .= '<a href="' . $url . '" id="home-name-link-' . $organization_id . '" style="color: #000;"><strong>' . $Name . '</strong></a>' . chr(10);

	    				$row .= '</td>' . chr(10);


	    				$row .= '<td width="50" align="center">';
	    				if($url!='')
	    					{
	    					$row .= '<a href="' . $url . '" target="_blank" title="Website" id="home-icon-' . $organization_id . '"><img id="home-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-home-icon.jpeg" width="18" align="center" /></a>' . chr(10);
								}
							$row .= '</td>' . chr(10);

	    				$row .= '<td width="50" align="center">';
	    				if($portal_url!='')
	    					{
	    					$row .= '<a href="' . $portal_url . '" target="_blank" title="Portal" id="portal-icon-' . $organization_id . '"><img id="portal-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-portal-developers.jpg" width="20" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);

						$row .= '<td width="50" align="center">';
						if($blog_url!='')
							{
	    					$row .= '<a href="' . $blog_url . '" target="_blank" title="Blog" id="blog-icon-' . $organization_id . '"><img id="blog-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-blog-icon.png" width="18" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center">';
						if($blog_rss_url!='')
							{
	    					$row .= '<a href="' . $blog_rss_url . '" target="_blank" title="Blog RSS" id="blogrss-icon-' . $organization_id . '"><img id="blogrss-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-rss-icon.png" width="18" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center">';
						if($twitter_url!='')
							{
	    					$row .= '<a href="' . $twitter_url . '" target="_blank" title="Twitter" id="twitter-icon-' . $organization_id . '"><img id="twitter-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-twitter-icon.png" width="23" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center">';
						if($github_url!='')
							{
	    					$row .= '<a href="' . $github_url . '" target="_blank" title="Github" id="github-icon-' . $organization_id . '"><img id="github-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-github-icon.png" width="25" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center">';
						if($apisjson_url!='')
							{
	    					$row .= '<a href="' . $apisjson_url . '" target="_blank" title="APIs.json" id="apisjson-icon-' . $organization_id . '"><img id="apisjson-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-a.png" width="25" align="center" /></a>' . chr(10);
							}

						//$row .= '(<a href="/ask-swagger-questions.html?apisjson_url=' . $apisjson_url . '" target="_blank" title="Ask Swagger Questions" id="ask-swagger-link-' . $organization_id . '" style="font-size: 10px;">swagger</a>)' . chr(10);
						//$row .= '(<a href="/ask-apis-json-questions.html?apisjson_url=' . $apisjson_url . '" target="_blank" title="Ask APIs.json Questions" id="ask-apisjson-link-' . $organization_id . '" style="font-size: 10px;">apis.json</a>)' . chr(10);

						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center">';
						if($sdksio_url!='')
							{
	    					$row .= '<a href="' . $sdksio_url . '" target="_blank" title="SDKs.io" id="sdksio-icon-' . $organization_id . '"><img id="sdksio-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/api-evangelist/sdks-io/sdks-io-icon.png" width="25" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);
						$row .= '<td width="50" align="center">';
						if($postman_url!='')
							{
	    					$row .= '<a href="' . $postman_url . '" target="_blank" title="Postman Collection" id="sdksio-icon-' . $organization_id . '"><img id="sdksio-icon-img-' . $organization_id . '" src="https://s3.amazonaws.com/kinlane-productions/building-blocks/x-postman.png" width="25" align="center" /></a>' . chr(10);
							}
						$row .= '</td>' . chr(10);
						$row .= '<td width="150" align="center" id="github-issue-' . $organization_id . '" style="font-size: 11px;">';
						$row .= '</td>' . chr(10);
	    				$row .= '</tr>' . chr(10);

						// APIs
						$Stack['apis'] = array();
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
							$APIQuery .= " WHERE cap.Company_ID = " . $organization_id . " AND t.Tag LIKE '%Stack'";
							}
						else
							{
							$APIQuery .= " WHERE cap.Company_ID = " . $organization_id . " AND t.Tag = '" . $thistag . "'";
							}

						$APIQuery .= " ORDER BY a.Name";

						//echo $APIQuery . "<br /><br />";
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
							$SDKsIO_URL = trim($APIRow['SDKsIO_URL']);

							if($toggle==1)
								{
		    					$row .= '<tr style="background-color: #FFF;">' . chr(10);
								}
							else
								{
		    					$row .= '<tr>' . chr(10);
								}

		    			//$row .= '<td align="center" width="50">' . chr(10);
							///$row .= '</td>' . chr(10);
							$row .= '<td align="left" style="font-size: 11px; padding-left: 20px;" width="35%">' . chr(10);
							$row .= '' . $API_Name . '';
							$row .= '</td>' . chr(10);

							//$row .= '<td width="50" align="center">' . chr(10);
							//if($Website_URL!=='')
							//	{
							//	$row .= '<a href="' . $Website_URL . '" target="_blank" title="Website"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-home-icon.jpeg" width="25" /></a>';
							//	}
							//$row .= '</td>' . chr(10);

							$row .= '<td width="50" align="center">' . chr(10);
							if($Documentation_URL!=='')
								{
								$row .= '<a href="' . $Documentation_URL . '" target="_blank" title="Documentation"><img src="http://kinlane-productions.s3.amazonaws.com/api-evangelist-site/building-blocks/bw-list.png" width="25" /></a>';
								}
							$row .= '</td>' . chr(10);

							$row .= '<td width="50" align="center">' . chr(10);
							if($Swagger_URL!=='')
								{
								$row .= '<a href="' . $Swagger_URL . '" target="_blank" title="Swagger"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-swagger-round.png" width="25" /></a>';
								//$row .= '<a href="/editor-swagger-json.html?swaggerurl=' . $Swagger_URL . '" target="_blank" title="Edit Swagger" style="font-size: 10px;">(edit)</a>';
								$swagger_count++;
								}
							$row .= '</td>' . chr(10);

							$row .= '<td width="50" align="center">' . chr(10);
							if($SDKsIO_URL!=='')
								{
								$row .= '<a href="' . $SDKsIO_URL . '" target="_blank" title="Swagger"><img src="https://s3.amazonaws.com/kinlane-productions/api-evangelist/sdks-io/sdks-io-icon.png" width="25" /></a>';
								$swagger_count++;
								}
							$row .= '</td>' . chr(10);
							$row .= '<td width="50" align="center">' . chr(10);
							$row .= '</td>' . chr(10);
							$row .= '<td width="50" align="center">' . chr(10);
							$row .= '</td>' . chr(10);
							$row .= '<td width="50" align="center">' . chr(10);
							$row .= '</td>' . chr(10);
							$row .= '<td width="50" align="center">' . chr(10);
							$row .= '</td>' . chr(10);
							$row .= '<td width="50" align="center">' . chr(10);
							$row .= '</td>' . chr(10);

							$row .= '</tr>' . chr(10);

							$api_count++;

							}

						$ReturnHTML .= $row;

						$F = array();
						$F['name'] = $Name;
						array_push($companies, $F);

						if($toggle==1){ $toggle = 0; } else { $toggle = 1; }

						$company_count++;
						}
					}
				}

			$ReturnHTML .= "</table>";

			$Page_Name = "Companies - Working";

			$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");
			$PageHeader = str_replace("[Name]",chr(39).htmlentities($Page_Name, ENT_QUOTES).chr(39),$PageHeader);

			$PageBody = "";
			//$PageBody = '<ul class="toolbar">';
			//$PageBody .= '<li>{"<a href="' . $project_subdomain . '/data/companies.json" title="JSON" target="_blank">JSON</a>"}</li>';
			//$PageBody .= '</ul>';
			//$PageBody = '<h1 class="title">' . $Page_Name . '</h1>';
			//$PageBody = '<p>These are the organizations I am tracking on as part of my API research in this area.</p>';
			$PageBody = '<p>Currently working with ' . $company_count . ' companies, in 223 areas, with ' . $api_count . ' APIs cataloged, and ' . $swagger_count . ' Swagger definitions.';

			$company_content = "";
			$company_content .= $PageHeader . chr(10);
		    $company_content .= $PageBody . chr(10);
		    $company_content .= $ReturnHTML . chr(10);

			}
		}

		$app->response()->header("Content-Type", "text/plain");
		echo $company_content;

	});
?>
