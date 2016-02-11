<?php
$route = '/jobs/:project_id/blog/publish-jekyll-html-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();
	
	if(isset($_REQUEST['override'])){ $override = $params['override']; } else { $override = 0; }
	if(isset($_REQUEST['start'])){ $start = $params['start']; } else { $start = 0; }
	if(isset($_REQUEST['finish'])){ $finish = $params['finish']; } else { $finish = 25; }

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

			$page = 0;
			$count = 0;
			$url = "http://blog.api.kinlane.com/blog/tags/" . urlencode($thistag) . "/build/?appid=" . $appid . "&appkey=" . $appkey . "&override=" . $override . "&start=" . $start . "&finish=" . $finish;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			//echo $output;

			$BlogPosts = json_decode($output,true);

			foreach($BlogPosts as $BlogPost)
				{

				$Blog_ID = $BlogPost['blog_id'];
				$Blog_Title = $BlogPost['title'];
				$Body = $BlogPost['body'];
				$Footer = $BlogPost['footer'];
				$Post_Body = $Body . $Footer;
				$Feature_Image = $BlogPost['image'];

				$Github_Build = $BlogPost['github_build'];

				$Post_Date = $BlogPost['post_date'];

				$Post_Year = date('Y',strtotime($Post_Date));
				if(strlen($Post_Year)==1){$Post_Year="0".$Post_Year;}
				$Post_Month = date('m',strtotime($Post_Date));
				if(strlen($Post_Month)==1){$Post_Month="0".$Post_Month;}
				$Post_Day = date('d',strtotime($Post_Date));
				if(strlen($Post_Day)==1){$Post_Day="0".$Post_Day;}

				$Prep_Title = str_replace(chr(39),"&#39;",$Blog_Title);
				$Prep_Title = PrepareFileName($Blog_Title);

				$Post_Link = $project_subdomain . "/" . $Post_Year . "/" . $Post_Month . "/" . $Post_Day . "/" . $Prep_Title . "/";

				$Host = parse_url($Post_Link);
				$Domain = $Host['host'];

				$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/blog6.html");

				$PageHeader = str_replace("[Title]",htmlentities($Blog_Title, ENT_QUOTES),$PageHeader);
				$PageHeader = str_replace("[Image]",$Feature_Image,$PageHeader);
				$PageHeader = str_replace("[Source]",$Post_Link,$PageHeader);
				$PageHeader = str_replace("[Domain]",$Domain,$PageHeader);

				$BlogFileName = $Post_Year . "-" . $Post_Month . "-" . $Post_Day . "-" . $Prep_Title . ".html";

				$WriteBlogContent = $PageHeader . $Post_Body;
				$WriteBlogContent = utf8_encode($WriteBlogContent);
				$blog_store_file = "_posts/" . $BlogFileName;

				// Github
				$GitHubClient = new GitHubClient();
				$GitHubClient->setCredentials($guser,$gpass);

				$owner = $project_github_user;
				$ref = "gh-pages";

				$B = array();
				$B['title'] = $Blog_Title;
				$B['url'] = $Post_Link;
				$B['domain'] = $Domain;
				$B['image'] = $Feature_Image;
				$B['file'] = $blog_store_file;
				array_push($ReturnObject,$B);
				
				try
					{
					$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $blog_store_file);

					$name = $CheckFile->getname();
					$content = base64_decode($CheckFile->getcontent());
					$sha = $CheckFile->getsha();

					$message = "Updating " . $blog_store_file . " via Laneworks Publish";
					//echo $message . "<br />";
					$content = base64_encode($WriteBlogContent);
					$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $blog_store_file, $message, $content, $sha, $ref);
					}
				catch (Exception $e)
					{
					$message = "Adding " . $blog_store_file . " via Laneworks Publish";
					$content = base64_encode($WriteBlogContent);
					//echo $message . "<br />";
					$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $blog_store_file, $message, $content, $ref);

					}
					
				}
			}
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
