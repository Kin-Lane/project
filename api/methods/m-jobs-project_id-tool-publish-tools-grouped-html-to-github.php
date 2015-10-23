<?php
$route = '/jobs/:project_id/tool/publish-tools-grouped-html-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$tools = array();

	$ReturnHTML = '<table width="100%" border="0">';

	//$ReturnHTML .= '<tr>' . chr(10);
	//$ReturnHTML .= '<td>' . chr(10);
	//$ReturnHTML .= '<hr>' . chr(10);
	//$ReturnHTML .= '</td>' . chr(10);
	//$ReturnHTML .= '</tr>' . chr(10);

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
		$project_tag_exclude = $Project['Tag_Exclude'];

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

			$url = "https://tool.api.kinlane.com:443/tool/tags/" . urlencode($thistag) . "/groupbytag/?appid=" . $appid . "&appkey=" . $appkey;
			//echo $url . "<br />";

			$http = curl_init();
			curl_setopt($http, CURLOPT_URL, $url);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);

			$output = curl_exec($http);
			//echo $output;
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			$info = curl_getinfo($http);

			$Tools = json_decode($output,true);

			if(count($Tools) > 0)
				{
				foreach($Tools as $key=>$value)
					{

					$tool_group = $key;

					if (strpos($project_tag_exclude,$tool_group) === false)
						{

		    			$row = '<tr>' . chr(10);
	    				$row .= '<td align="left" valign="top" style="padding-left:10px; margin-bottom:15px; background-color: #E0E0E0;">' . chr(10);

	    				$row .= '<h2>' . $tool_group . '</h2>' . chr(10);

	    				$row .= '</td>' . chr(10);
	    				$row .= '</tr>' . chr(10);

						$ReturnHTML .= $row;

						foreach($value as $Tool)
							{
							$tool_id = $Tool['tool_id'];
							$name = $Tool['name'];
							$user = $Tool['user'];
							$details = $Tool['details'];
							$post_date = $Tool['post_date'];
							$url = $Tool['url'];
							$logo = $Tool['logo'];
							$forks = $Tool['forks'];
							$followers = $Tool['followers'];
							$watchers = $Tool['watchers'];

							$Stack = array();

		    				$row = '<tr>' . chr(10);
		    				$row .= '<td align="left" valign="top">' . chr(10);

		    				$row .= '<a href="' . $url . '" id="home-logo-link-' . $tool_id . '"><img src="' . $logo . '" width="150" align="left" style="padding: 15px;" /></a>' . chr(10);
		    				$row .= '<a href="' . $url . '" id="home-name-link-' . $tool_id . '" style="color: #000;"><strong>' . $name . '</strong></a><p>' . $details . '</p>' . chr(10);

		    				$row .= '</td>' . chr(10);
		    				$row .= '</tr>' . chr(10);

							$row .= '<tr>' . chr(10);
							$row .= '<td>' . chr(10);
							$row .= '<hr>' . chr(10);
		    				$row .= '</td>' . chr(10);
		    				$row .= '</tr>' . chr(10);

							$ReturnHTML .= $row;

							$F = array();
							$F['name'] = $name;
							array_push($tools, $F);

							}
						}
					}
				}

			$ReturnHTML .= "</table>";

			$Page_Name = "Tools";

			$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");
			$PageHeader = str_replace("[Name]",chr(39).htmlentities($Page_Name, ENT_QUOTES).chr(39),$PageHeader);

			$PageBody = '<p>As I do my regular monitoring of the API space, these are the common " . $tag . " tools that I have identified so far. If there are tools or other resources that you think should be listed, let me know.';

			$company_content = $PageHeader . chr(10) . $PageBody . chr(10) . $ReturnHTML;

			$write_tool_file = "tools.html";

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
		$ReturnObject['tools'] = $tools;
		$ReturnObject['file'] = $write_tool_file;

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
