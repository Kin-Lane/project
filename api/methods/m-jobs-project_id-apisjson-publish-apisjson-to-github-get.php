<?php
$route = '/jobs/:project_id/apisjson/publish-apisjson-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

 	$request = $app->request();
 	$params = $request->params();

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

						$Company_Name = $Companys['name'];
						$Details = $Companys['details'];
						$Screenshot_URL = $Companys['photo'];

						$url = $Companys['url'];
            $Email_Address = $Companys['email'];
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

      			$Company_Name_Slug = PrepareFileName($Company_Name);

      			$API_JSON_URL = $project_subdomain . $Company_Name_Slug . "/apis.json";

      			$Body = $Details;

      			$Body = str_replace(chr(34),"",$Body);
      			$Body = str_replace(chr(39),"",$Body);
      			$Body = strip_tags($Body);
      			$Body = mysql_real_escape_string($Body);

      			// Add Company As Include in Master APIs.json
      			$APIJSON_Include = array();
      			$APIJSON_Include['name'] = $Company_Name;
      			$APIJSON_Include['url'] = $API_JSON_URL;

      			// Base URL
      			$Base_URL = "";
      			$query = "SELECT Company_URL_ID,Type,URL FROM company_url WHERE Company_ID = " . $organization_id . " AND Type = 'BaseURL'";
      			$linkResult = mysql_query($query) or die('Query failed: ' . mysql_error());
      			if($linkResult && mysql_num_rows($linkResult))
      				{
      				while ($link = mysql_fetch_assoc($linkResult))
      					{
      					$Base_URL = $link['URL'];
      					}
      				}

      			// Begin Individual APIs.json
      			$APIJSON = array();
      			$APIJSON['name'] = trim($Company_Name);
      			$APIJSON['description'] = trim($Body);
      			$APIJSON['image'] = trim($photo);

      			// Maange the API.json Tags
      			$Tags = array();
      			$Tag =  array('api');
      			array_push($Tags, $Tag);

      			$Tag =  array('application programming interfaces');
      			array_push($Tags, $Tag);

      			$Tags = array();
      			$TagQuery = "SELECT DISTINCT t.Tag FROM tags t JOIN company_tag_pivot ctp ON t.Tag_ID = ctp.Tag_ID WHERE ctp.Company_ID = " . $organization_id . " AND t.Tag NOT LIKE '%-Stack' ORDER BY t.Tag";
      			//echo $TagQuery;
      			$TagResult = mysql_query($TagQuery) or die('Query failed: ' . mysql_error());
      			$rowcount = 1;
      			while ($ThisTag = mysql_fetch_assoc($TagResult))
      				{
      				$Tag = strtolower($ThisTag['Tag']);
      				array_push($Tags, $Tag);
      				}

      			$APIJSON['tags'] = $Tags;

      			$APIJSON['created'] = date('Y-m-d');
      			$APIJSON['modified'] = date('Y-m-d');

      			$APIJSON['url'] = $API_JSON_URL;
      			$APIJSON['specificationVersion'] = "0.14";

      			$APIJSON['apis'] = array();

      			$API = array();
      			$API['name'] = $Company_Name;
      			$API['description'] = $Body;
      			$API['image'] = trim($photo);

      			$API['humanURL'] = trim($url);

      			if($Base_URL!='')
      				{
      				$API['baseURL'] = trim($Base_URL);
      				}
      			else
      				{
      				$API['baseURL'] = trim($url);
      				}

      			$API['tags'] = $Tags;

      			$API['properties'] = array();

      			$CompanyURLQuery = "SELECT * FROM company_url WHERE Company_ID = " . $organization_id . " ORDER BY Name, Type";
      			//echo $CompanyURLQuery . "<br />";
      			$CompanyURLResult = mysql_query($CompanyURLQuery) or die('Query failed: ' . mysql_error());

      			while ($CompanyURL = mysql_fetch_assoc($CompanyURLResult))
      				{
      				$Company_URL_ID = $CompanyURL['Company_URL_ID'];

      				$API_URL = $CompanyURL['URL'];
      				$API_URL_Name = $CompanyURL['Name'];
      				$API_URL_Type = $CompanyURL['Type'];

      				$API_Building_Block_ID = $CompanyURL['Building_Block_ID'];

      				$API_Building_Block_Name = "";
      				$API_Building_Block_Description = "";
      				$API_Building_Block_Icon = "";

      				if($API_Building_Block_ID>0)
      					{

      					$Building_Block_Query = "SELECT Building_Block_ID, bb.Name AS Building_Block_Name, bb.About, bbc.Name AS Building_Block_Category_Name, bbc.Type as Type FROM building_block bb JOIN building_block_category bbc ON bb.Building_Block_Category_ID = bbc.BuildingBlockCategory_ID WHERE Building_Block_ID = " . $API_Building_Block_ID;
      					//echo $Building_Block_Query . "<br />";
      					$Building_Block_Result = mysql_query($Building_Block_Query) or die('Query failed: ' . mysql_error());
      					if($Building_Block_Result && mysql_num_rows($Building_Block_Result))
      						{
      						$HaveBuildingBlock = 1;
      						$Building_Block = mysql_fetch_assoc($Building_Block_Result);

      						$Building_Block_Image_Query = "SELECT Image_Name,Image_Path FROM building_block_image WHERE Image_Path <> '' AND Building_Block_ID = " . $API_Building_Block_ID . " ORDER BY Building_Block_Image_ID DESC";
      						$Building_Block_Image_Result = mysql_query($Building_Block_Image_Query) or die('Query failed: ' . mysql_error());
      						while ($Building_Block_Image = mysql_fetch_assoc($Building_Block_Image_Result))
      							{
      							$API_Building_Block_Icon = $Building_Block_Image['Image_Path'];
      							}

      						$API_Building_Block_Name = $Building_Block['Building_Block_Name'];
      						//echo "Building Block Name: " . $API_Building_Block_Name . "<br />";
      						$API_Building_Block_Description = $Building_Block['About'];

      						}

      					$API_URL_Type_Slug = PrepareFileName($API_Building_Block_Name);

      					$Link = array();
      					$Link['type'] = "X-" . $API_URL_Type_Slug;
      					$Link['url'] = trim($API_URL);
      					array_push($API['properties'], $Link);

      					}
      				}

      			array_push($APIJSON['apis'], $API);

      			$APIJSON['include'] = array();

      			// Begin APIs
      			$APIQuery = "SELECT * FROM api WHERE Company_ID = " . $organization_id . " ORDER BY Name";
      			//echo $TagQuery;
      			$APIResult = mysql_query($APIQuery) or die('Query failed: ' . mysql_error());
      			$rowcount = 1;

      			if($APIResult && mysql_num_rows($APIResult))
      				{
      				while ($API = mysql_fetch_assoc($APIResult))
      					{

      					$API_ID = $API['API_ID'];
      					$API_Name = $API['Name'];
      					$API_About = $API['About'];

      					$API_About = str_replace(chr(34),"",$API_About);
      					$API_About = str_replace(chr(39),"",$API_About);
      					$API_About = strip_tags($API_About);
      					$API_About = mysql_real_escape_string($API_About);

      					// Website
      					$API_Website_URL = "";
      					$query = "SELECT URL FROM api_url WHERE API_ID = " . $API_ID . " AND Type = 'Website'";
      					$linkResult = mysql_query($query) or die('Query failed: ' . mysql_error());
      					if($linkResult && mysql_num_rows($linkResult))
      						{
      						while ($link = mysql_fetch_assoc($linkResult))
      							{
      							$API_Website_URL = $link['URL'];
      							}
      						}

      					$Include = array();
      					$Include['name'] = $API_Name;
      					$Include['url'] = $API_Website_URL;
      					array_push($APIJSON['include'], $Include);

      					}

      				$APIJSON['maintainers'] = array();

      				$Maintainer = array();
      				$Maintainer['FN'] = "Kin";
      				$Maintainer['X-twitter'] = "apievangelist";
      				$Maintainer['email'] = "kin@email.com";

      				array_push($APIJSON['maintainers'], $Maintainer);

      				$ReturnEachAPIJSON = stripslashes(format_json(json_encode($APIJSON)));

      				$API['contact'] = array();
      				$Contact = array();
      				$Contact['FN'] = $Company_Name;
      				if($Email_Address!='')
      					{
      					$Contact['email'] = trim(str_replace("mailto:","",$Email_Address));
      					}

      				if($twitter_url!='')
      					{
      					$Contact['X-twitter'] = $twitter_url;
      					}
      				array_push($API['contact'], $Contact);

      				array_push($APIJSON['apis'], $API);

      				$APIJSON['maintainers'] = array();

      				$Maintainer = array();
      				$Maintainer['FN'] = "Kin";
      				$Maintainer['X-twitter'] = "apievangelist";
      				$Maintainer['email'] = "kin@email.com";

      				array_push($APIJSON['maintainers'], $Maintainer);

      				}
      			}
      		}
        }
      }
    }

  $ReturnObject = $APIJSON;

  $app->response()->header("Content-Type", "application/json");
  echo format_json(json_encode($ReturnObject));
  });

?>
