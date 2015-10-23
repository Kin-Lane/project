<?php
$route = '/jobs/:project_id/news/publish-jekyll-html-to-github/';
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

			$page = 0;
			$count = 0;
			$url = "http://news.api.kinlane.com/news/tags/" . urlencode($thistag) . "/build/?appid=" . $appid . "&appkey=" . $appkey;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			echo $output;

			$NewsPosts = json_decode($output,true);

			foreach($NewsPosts as $NewsPost)
				{

				$News_ID = $NewsPost['news_id'];
				$News_Title = $NewsPost['title'];
				$Body = $NewsPost['body'];
				$Footer = $NewsPost['footer'];
				$Post_Body = $Body . $Footer;
				$Feature_Image = $NewsPost['image'];

				$Github_Build = $NewsPost['github_build'];

				$Post_Date = $NewsPost['post_date'];

				$Post_Year = date('Y',strtotime($Post_Date));
				if(strlen($Post_Year)==1){$Post_Year="0".$Post_Year;}
				$Post_Month = date('m',strtotime($Post_Date));
				if(strlen($Post_Month)==1){$Post_Month="0".$Post_Month;}
				$Post_Day = date('d',strtotime($Post_Date));
				if(strlen($Post_Day)==1){$Post_Day="0".$Post_Day;}

				$Prep_Title = str_replace(chr(39),"&#39;",$News_Title);
				$Prep_Title = PrepareFileName($News_Title);

				$Post_Link = $project_subdomain . "/" . $Post_Year . "/" . $Post_Month . "/" . $Post_Day . "/" . $Prep_Title . "/";

				$Host = parse_url($Post_Link);
				$Domain = $Host['host'];

				$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/blog3.html");

				$PageHeader = str_replace("[Title]",chr(39).htmlentities($News_Title, ENT_QUOTES).chr(39),$PageHeader);
				$PageHeader = str_replace("[Image]",$Feature_Image,$PageHeader);
				$PageHeader = str_replace("[URL]",$Post_Link,$PageHeader);
				$PageHeader = str_replace("[Source]",$Post_Link,$PageHeader);
				$PageHeader = str_replace("[Domain]",$Domain,$PageHeader);

				$NewsFileName = $Post_Year . "-" . $Post_Month . "-" . $Post_Day . "-" . $Prep_Title . ".html";

				$WriteNewsContent = $PageHeader . $Post_Body;
				$WriteNewsContent = utf8_encode($WriteNewsContent);
				$news_store_file = "_posts/" . $NewsFileName;

				// Github
				$GitHubClient = new GitHubClient();
				$GitHubClient->setCredentials($guser,$gpass);

				$owner = 'kinlane';
				$ref = "gh-pages";

				$ReturnObject['title'] = $News_Title;
				$ReturnObject['url'] = $Post_Link;
				$ReturnObject['domain'] = $Domain;
				$ReturnObject['image'] = $Feature_Image;
				$ReturnObject['file'] = $news_store_file;

				try
					{
					$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $news_store_file);

					$name = $CheckFile->getname();
					$content = base64_decode($CheckFile->getcontent());
					$sha = $CheckFile->getsha();

					$message = "Updating " . $news_store_file . " via Laneworks Publish";
					$content = base64_encode($WriteNewsContent);
					$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $news_store_file, $message, $content, $sha, $ref);
					}
				catch (Exception $e)
					{
					$message = "Adding " . $news_store_file . " via Laneworks Publish";
					$content = base64_encode($WriteNewsContent);

					$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $news_store_file, $message, $content, $ref);

					}
				}
			}
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
