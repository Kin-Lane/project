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
			echo $url . "<br />";

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

      $ReturnObject = array();

			if(count($Organizations) > 0)
				{

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

      			$API_JSON_URL = $project_subdomain . "/data/" . $Company_Name_Slug . "/apis.json";

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

      			$APIJSON['tags'] = $Tags;

      			$APIJSON['created'] = date('Y-m-d');
      			$APIJSON['modified'] = date('Y-m-d');

      			$APIJSON['url'] = $API_JSON_URL;
      			$APIJSON['specificationVersion'] = "0.14";

      			$APIJSON['apis'] = array();
            $APIJSON['x-common'] = array();

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
      					array_push($APIJSON['x-common'], $Link);

      					}
      				}

              //
              // APIs
              //
              $API_Count = 0;
  						$APIQuery = "SELECT DISTINCT a.API_ID, a.Name, a.About,";
  						$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Website' LIMIT 1) AS Website_URL,";
  						$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Swagger' LIMIT 1) AS Swagger_URL,";
  						$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Documentation' LIMIT 1) AS Documentation_URL,";
  						$APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'SDKs.io' LIMIT 1) AS SDKsIO_URL,";
              $APIQuery .= " (SELECT URL from api_url WHERE API_ID = a.API_ID AND Type = 'Base URL' LIMIT 1) AS Base_URL";
  						$APIQuery .= " FROM api a";
  						$APIQuery .= " JOIN company_api_pivot cap ON a.API_ID = cap.API_ID";
  						$APIQuery .= " JOIN api_tag_pivot atp ON a.API_ID = atp.API_ID";
  						$APIQuery .= " JOIN tags t ON atp.Tag_ID = t.Tag_ID";
  						$APIQuery .= " WHERE cap.Company_ID = " . $organization_id . " AND t.Tag = '" . $thistag . "'";
  						$APIQuery .= " ORDER BY a.Name";

  						//echo $APIQuery . "<br /><br />";
  						$APIResult = mysql_query($APIQuery) or die('Query failed: ' . mysql_error());
  						while ($APIRow = mysql_fetch_assoc($APIResult))
  							{

  							$API_ID = $APIRow['API_ID'];
  							$API_Name = $APIRow['Name'];
                $API_About = $APIRow['About'];
                if($API_About == '')
                  {
                  $API_About = $Body;
                  }

  							$API_Name_Slug = PrepareFileName($API_Name);
  							//echo " -- API-Name " . $API_Name . "<br />";

  							$Website_URL = trim($APIRow['Website_URL']);
                if($Website_URL == '')
                  {
                  $Website_URL = $url;
                  }
  							$Swagger_URL = trim($APIRow['Swagger_URL']);
  							$Documentation_URL = trim($APIRow['Documentation_URL']);
  							$SDKsIO_URL = trim($APIRow['SDKsIO_URL']);
                $Base_URL = trim($APIRow['Base_URL']);

                $API = array();
          			$API['name'] = $API_Name;
          			$API['description'] = $API_About;
          			$API['image'] = trim($photo);

          			$API['humanURL'] = trim($Website_URL);

          			if($Base_URL!='')
          				{
          				$API['baseURL'] = trim($Base_URL);
          				}
          			else
          				{
          				$API['baseURL'] = trim($Website_URL);
          				}

          			$API['tags'] = $Tags;

          			$API['properties'] = array();

                if($Documentation_URL!='')
                  {
                  $Link = array();
        					$Link['type'] = "x-documentation";
        					$Link['url'] = trim($Documentation_URL);
        					array_push($API['properties'], $Link);
                  }

                if($Swagger_URL!='')
                  {
                  $Link = array();
        					$Link['type'] = "x-oadf";
        					$Link['url'] = trim($Swagger_URL);
        					array_push($API['properties'], $Link);
                  }

                if($SDKsIO_URL!='')
                  {
                  $Link = array();
        					$Link['type'] = "x-sdks";
        					$Link['url'] = trim($SDKsIO_URL);
        					array_push($API['properties'], $Link);
                  }

                array_push($APIJSON['apis'], $API);
                $API_Count ++ ;
                }

      			$APIJSON['include'] = array();

    				$APIJSON['maintainers'] = array();

    				$Maintainer = array();
    				$Maintainer['FN'] = "Kin";
    				$Maintainer['X-twitter'] = "apievangelist";
    				$Maintainer['email'] = "kin@email.com";

    				array_push($APIJSON['maintainers'], $Maintainer);

            if($API_Count>0)
              {
              array_push($ReturnObject,$APIJSON);
              }
      			}
      		}
        }
      }
    }

  $app->response()->header("Content-Type", "application/json");
  echo stripslashes(format_json(json_encode($ReturnObject)));
  });

?>
