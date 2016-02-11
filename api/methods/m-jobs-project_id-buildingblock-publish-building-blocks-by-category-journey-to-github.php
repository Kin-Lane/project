<?php
$route = '/jobs/:project_id/buildingblock/publish-building-blocks-by-category-journey-to-github/';
$app->get($route, function ($project_id)  use ($app,$appid,$appkey,$guser,$gpass){

	$host = $_SERVER['HTTP_HOST'];
	$project_id = prepareIdIn($project_id,$host);

	$buildingblocks = array();
	$ReturnHTML = '<table width="100%" border="0">';

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

			$thistag = $Tag['tag'];
			//echo $thistag . "<br />";
			
			for ($x = 0; $x <= 4; $x++) 
				{
				//Run the core four
				if($x==0){ $zone='Essentials'; }
				elseif($x==1){ $zone='Technology'; }
				elseif($x==2){ $zone='Business'; }
				elseif($x==3){ $zone='Politics'; }
				elseif($x==4){ $zone=''; }	
				
				//echo "ZONE: " . $x . ")" . $zone . "<br />";
				$url = "http://buildingblock.api.kinlane.com/buildingblocks/bytype/" . urlencode(str_replace("API ","",$thistag)) . "/grouped/?appid=" . $appid . "&appkey=" . $appkey;
				if($zone!=''){ $url .= "&zone=" . $zone; }
				//echo $url . "<br />";
	
				$http = curl_init();
				curl_setopt($http, CURLOPT_URL, $url);
				curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
	
				curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
	
				$output = curl_exec($http);
				//echo $output;
				$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
				$info = curl_getinfo($http);
	
				$BuildingBlockCategories = json_decode($output,true);
				$CountBuildingBlockCategories = json_decode($output,true);
	
				if(count($BuildingBlockCategories) > 0)
					{
						
					$Previous_Page = "index.html";
					$Next_Page = "";					
					
					$Category_Count = 0;
					$Category_Key = array();
					
					foreach($CountBuildingBlockCategories as $Key => $Value)
						{
						$K = array();
						$K = $Key;
						//echo "Count: " . count($CountBuildingBlockCategories[$Key]) . "<br />";
						if(count($CountBuildingBlockCategories[$Key]) > 0)
							{
							array_push($Category_Key,$K);
							}
						}				
					
					foreach($BuildingBlockCategories as $Key => $Value)
						{
							
						$First_Building_Block = 1;
						$This_Count = 0;
							
						$BuildingBlockCategoryName = $Key;
						//echo "Processing: " . $BuildingBlockCategoryName . "(" . $First_Building_Block . ")<br />";
						$BuildingBlockCategoryNameSlug = str_replace("/","-",$BuildingBlockCategoryName);
						$BuildingBlockCategoryNameSlug = str_replace(" ","-",$BuildingBlockCategoryNameSlug);
						$BuildingBlockCategoryNameSlug = strtolower($BuildingBlockCategoryNameSlug);					
						
						$BuildingBlockCategory = $Value;
							
						$F = array();
						$F[$BuildingBlockCategoryName] = array();
	
						if(count($BuildingBlockCategory) > 0)
							{
							
							$Stop_Count = count($BuildingBlockCategory);						
							
							foreach($BuildingBlockCategory as $BuildingBlocks)
								{
															
								$building_block_id = $BuildingBlocks['building_block_id'];
								$building_block_category_id = $BuildingBlocks['building_block_category_id'];
								
								$name = $BuildingBlocks['name'];
								
								$nameslug = str_replace("/","-",$name);
								$nameslug = str_replace(" ","-",$nameslug);
								$nameslug = strtolower($nameslug);																										
									
								$about = strip_tags($BuildingBlocks['about']);
								$category_id = $BuildingBlocks['category_id'];
								$category = $BuildingBlocks['category'];
								$category_about = $BuildingBlocks['category_about'];
								$image = $BuildingBlocks['image'];
								$image_width = $BuildingBlocks['image_width'];
								if($image_width=='' || $image_width==0){ $image_width = 100; }
								$sort_order = $BuildingBlocks['sort_order'];
								$category_image = $BuildingBlocks['category_image'];
								$category_hex = $BuildingBlocks['category_hex'];
								
								$organizations = $BuildingBlocks['organizations'];
								$apis = $BuildingBlocks['apis'];
								$links = $BuildingBlocks['links'];
								$tools = $BuildingBlocks['tools'];
								$questions = $BuildingBlocks['questions'];
	
								$Stack = array();
	
								// For the first building blocks
								if($First_Building_Block==1)
									{
														
									$nextname = $name;
									$nextnameslug = str_replace("/","-",$nextname);
									$nextnameslug = str_replace(" ","-",$nextnameslug);
									$nextnameslug = strtolower($nextnameslug);	
									$Next_Page = $BuildingBlockCategoryNameSlug . '-' . $nextnameslug . ".html";															
					
									$Page_Name = $BuildingBlockCategoryName;
									
									$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");
									$PageHeader = str_replace("[Name]",chr(39).htmlentities($Page_Name, ENT_QUOTES).chr(39),$PageHeader);
				
									$PageBody = '';
									
									if($zone=='Essentials')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Just the Essentials</p>' . chr(10); 	
										}
									elseif($zone=='Technology')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Technology</p>' . chr(10); 	
										}	
									elseif($zone=='Business')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Business</p>' . chr(10); 	
										}
									elseif($zone=='Politics')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Politics</p>' . chr(10); 	
										}																												
									else
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Complete</p>' . chr(10); 	
										}										

									$PageBody .= '<table cellpadding="0" cellspacing="0" align="center" border="0" width="100%">' . chr(10);
									$PageBody .= '<tr>' . chr(10);					
									$PageBody .= '<td align="center">' . chr(10);		
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-subway-circle.png" style="position: relative; z-index: 100; float: left; display: inline; margin-top:3px;" align="left" id="circle-one" width="40" />' . chr(10);		
									$PageBody .= '<div style="width: 95%; max-width: 890px; height: 25px; margin-left: 8px; margin-top:10px; background-color: ' . $category_hex . '; position: absolute; z-index: 50;" id="line-hex"></div>' . chr(10);
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-round-black-circle-red-center.png" width="50" style="position: relative; z-index: 100; float: center; display: inline;" id="stop-circle" />' . chr(10);										
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-subway-circle.png" style="position: relative; z-index: 100; float: right; display: inline margin-top:3px;;" align="right" id="circle-two" width="40" />' . chr(10);										
									$PageBody .= '</td>' . chr(10);							
									$PageBody .= '</tr>' . chr(10);	
									$PageBody .= '</table>' . chr(10);			
									$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">{"' . $BuildingBlockCategoryName . '"}</p>' . chr(10); 
									$PageBody .= '<p>' . $category_about . '</p>' . chr(10);								
									$PageBody .= '<!-- Pagination links -->' . chr(10);
									$PageBody .= '<div class="pagination">' . chr(10);
									if($Previous_Page!='')
										{
										$PageBody .= '<a href="' . $Previous_Page . '" class="older"><< Prev</a>' . chr(10);
										}
									else
										{
										$PageBody .= '<a href="#" class="older"> </a>' . chr(10);
										}
																			
									if($Next_Page!='')
										{
										$PageBody .= '<a href="' . $Next_Page . '" class="newer" tabindex="0">Next >></a>' . chr(10);
										}
									else
										{
										$PageBody .= '<a href="#" class="newer" tabindex="0"> </a>' . chr(10);
										}	
									$PageBody .= '</div>' . chr(10);
									
										
				
									$page_content = $PageHeader . chr(10) . $PageBody . chr(10) . $ReturnHTML;
				
									if($zone=='Essentials'){ $write_page_folder = "journey/essentials/"; }	
									elseif($zone=='Technology'){ $write_page_folder = "journey/technology/"; }
									elseif($zone=='Business'){ $write_page_folder = "journey/business/"; }
									elseif($zone=='Politics'){ $write_page_folder = "journey/politics/"; }
									else{ $write_page_folder = "journey/complete/"; }				
									
									$write_page_file = $BuildingBlockCategoryNameSlug . ".html";					
									$write_page_folder_file = $write_page_folder . $write_page_file;
									
									$Previous_Page = $write_page_file;	
									//echo $write_page_folder . "<br />";
									
									// Github
									$GitHubClient = new GitHubClient();
									$GitHubClient->setCredentials($guser,$gpass);
				
									$owner = $project_github_user;
									$ref = "gh-pages";
				
									try
										{
										$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $write_page_folder_file);
				
										//$name = $CheckFile->getname();
										$content = base64_decode($CheckFile->getcontent());
										$sha = $CheckFile->getsha();
				
										$message = "Updating " . $write_page_folder_file . " via Laneworks CMS Publish";
										$content = base64_encode($page_content);
				
										$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $write_page_folder_file, $message, $content, $sha, $ref);
										}
									catch (Exception $e)
										{
				
										$message = "Adding " . $write_page_folder_file . " via Laneworks CMS Publish";
										$content = base64_encode($page_content);
				
										$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $write_page_folder_file, $message, $content, $ref);
				
										}	
										
									$N = array();
									$N['name'] = $name;
									$N['filename'] = $write_page_file;
									array_push($F[$BuildingBlockCategoryName], $N);	
									
									$First_Building_Block = 0;	
									
									//// End the Category
									
									if(isset($BuildingBlockCategory[$This_Count+1]))
										{
										$nextname = $BuildingBlockCategory[$This_Count+1]['name'];
										$nextnameslug = str_replace("/","-",$nextname);
										$nextnameslug = str_replace(" ","-",$nextnameslug);
										$nextnameslug = strtolower($nextnameslug);	
										$Next_Page = $BuildingBlockCategoryNameSlug . '-' . $nextnameslug . ".html";							
										}
									else
										{																						
										$nextname = "";	
										$nextnameslug = "";
										$Next_Page = "";
										}										
	
					    			//echo "Processing: " . $name . "(" . $First_Building_Block . ")<br />";
									//echo "prev/next: " . $Previous_Page . "  /  " . $Next_Page . "<br />";
		
									$Page_Name = $name;
		
									$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");
									$PageHeader = str_replace("[Name]",chr(39).htmlentities($name, ENT_QUOTES).chr(39),$PageHeader);
				
									$PageBody = '';
									
									if($zone=='Essentials')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Just the Essentials</p>' . chr(10); 	
										}
									elseif($zone=='Technology')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Technology</p>' . chr(10); 	
										}	
									elseif($zone=='Business')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Business</p>' . chr(10); 	
										}
									elseif($zone=='Politics')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Politics</p>' . chr(10); 	
										}																												
									else
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Complete</p>' . chr(10); 	
										}										
									
									$PageBody .= '<table cellpadding="0" cellspacing="0" align="center" border="0" width="100%">' . chr(10);
									$PageBody .= '<tr>' . chr(10);					
									$PageBody .= '<td align="center">' . chr(10);		
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-subway-circle.png" style="position: relative; z-index: 100; float: left; display: inline; margin-top:3px;" align="left" id="circle-one" width="40" />' . chr(10);		
									$PageBody .= '<div style="width: 95%; max-width: 890px; height: 25px; margin-left: 8px; margin-top:10px; background-color: ' . $category_hex . '; position: absolute; z-index: 50;" id="line-hex"></div>' . chr(10);
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-round-black-circle-red-center.png" width="50" style="position: relative; z-index: 100; float: center; display: inline;" id="stop-circle" />' . chr(10);										
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-subway-circle.png" style="position: relative; z-index: 100; float: right; display: inline margin-top:3px;;" align="right" id="circle-two" width="40" />' . chr(10);										
									$PageBody .= '</td>' . chr(10);							
									$PageBody .= '</tr>' . chr(10);	
									$PageBody .= '</table>' . chr(10);			
									$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">{"' . $BuildingBlockCategoryName . '":"' . $name . '"}</p>' . chr(10); 
									$PageBody .= '<p>' . $about . '</p>' . chr(10);
									$PageBody .= '<div style="padding: 5px 25px 5px 25px;">' . chr(10);
									
									// Organizations
									if(count($organizations) > 0)
										{
										$OrgBody = '<p><strong>Organizations</strong></p>' . chr(10);
										$OrgBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);
										$anyorgs = 0;																	
										foreach($organizations as $org)
											{
											$org_name = $org['name'];
											$org_url = $org['url'];	
											$org_text = $org['text'];	
											if($api_url!='' && $api_url!=0)
												{													
												$OrgBody .= '<li><a href="' . $org_url . '">' . $org_name . '</a>';
												if($org_text!=''){ $OrgBody .= ' - ' . $org_text; } 
												$OrgBody .= '</li>' . chr(10);
												$anyorgs = 1;
												}
											}
										if($anyorgs==1)
											{
											$PageBody .= $OrgBody . '</ul>' . chr(10);
											}											
										}
									
									// APIs
									if(count($apis) > 0)
										{
										$APIBody = '<p><strong>APIs</strong></p>' . chr(10);
										$APIBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);
										$anyapis = 0;																	
										foreach($apis as $api)
											{
											$api_name = $api['name'];
											$api_url = $api['url'];	
											$api_text = $api['text'];
											if($api_url!='' && $api_url!=0)
												{			
												$APIBody .= '<li><a href="' . $api_url . '">' . $api_name . '</a>';
												if($api_text!=''){ $APIBody .= ' - ' . $api_text; } 
												$APIBody .= '</li>' . chr(10);
												$anyapis = 1;
												}
											}
										if($anyapis==1)
											{
											$PageBody .= $APIBody . '</ul>' . chr(10);		
											}
										}			
									
									// Links
									if(count($links) > 0)
										{
										$PageBody .= '<p><strong>Links</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($links as $link)
											{
											$link_name = $link['name'];
											$link_url = $link['url'];	
											$link_text = $link['text'];			
											$PageBody .= '<li><a href="' . $link_url . '">' . $link_name . '</a>';
											if($link_text!=''){ $PageBody .= ' - ' . $link_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);	
									
									// Tools
									if(count($tools) > 0)
										{
										$PageBody .= '<p><strong>Links</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($tools as $tool)
											{
											$tool_name = $tool['name'];
											$tool_url = $tool['url'];	
											$tool_text = $tool['text'];			
											$PageBody .= '<li><a href="' . $tool_url . '">' . $tool_name . '</a>';
											if($tool_text!=''){ $PageBody .= ' - ' . $tool_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);	
									
									// Questions
									if(count($questions) > 0)
										{
										$PageBody .= '<p><strong>Links</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($questions as $question)
											{
											$question_name = $question['name'];
											$question_url = $question['url'];	
											$question_text = $question['text'];			
											$PageBody .= '<li>' . $question_name;
											if($question_text!=''){ $PageBody .= ' - ' . $question_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);	
									$PageBody .= '</div>' . chr(10);
									
									$PageBody .= '<!-- Pagination links -->' . chr(10);
									$PageBody .= '<div class="pagination">' . chr(10);
									if($Previous_Page!='')
										{
										$PageBody .= '<a href="' . $Previous_Page . '" class="older"><< Prev</a>' . chr(10);
										}
									else
										{
										$PageBody .= '<a href="#" class="older"> </a>' . chr(10);
										}		
									//echo $This_Count . " == " . $Stop_Count . "<br />";
									if(($This_Count == $Stop_Count - 1) && isset($Category_Key[$Category_Count+1]))
										{
										$nextname = $Category_Key[$Category_Count+1];	
										$nextnameslug = str_replace("/","-",$nextname);
										$nextnameslug = str_replace(" ","-",$nextnameslug);
										$nextnameslug = strtolower($nextnameslug);	
										$Next_Page = $nextnameslug . ".html";							
										}								
																
									if($Next_Page!='')
										{
										$PageBody .= '<a href="' . $Next_Page . '" class="newer" tabindex="0">Next >></a>' . chr(10);
										}
									else 
										{
										$PageBody .= '<a href="#" class="newer" tabindex="0"> </a>' . chr(10);
										}
										
									$PageBody .= '</div>' . chr(10);						
				
									
				
									$page_content = $PageHeader . chr(10) . $PageBody . chr(10) . $ReturnHTML;
				
									if($zone=='Essentials'){ $write_page_folder = "journey/essentials/"; }	
									elseif($zone=='Technology'){ $write_page_folder = "journey/technology/"; }
									elseif($zone=='Business'){ $write_page_folder = "journey/business/"; }
									elseif($zone=='Politics'){ $write_page_folder = "journey/politics/"; }
									else{ $write_page_folder = "journey/complete/"; }	
									
									$write_page_file = $BuildingBlockCategoryNameSlug . '-' . $nameslug . ".html";
									$Previous_Page = $write_page_file;	
									//echo $write_page_folder . "<br />";			
									$write_page_folder_file = $write_page_folder . $write_page_file;		    				
		    									
									// Github
									$GitHubClient = new GitHubClient();
									$GitHubClient->setCredentials($guser,$gpass);
				
									$owner = $project_github_user;
									$ref = "gh-pages";
				
									try
										{
										$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $write_page_folder_file);
				
										//$name = $CheckFile->getname();
										$content = base64_decode($CheckFile->getcontent());
										$sha = $CheckFile->getsha();
				
										$message = "Updating " . $write_page_folder_file . " via Laneworks CMS Publish";
										$content = base64_encode($page_content);
				
										$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $write_page_folder_file, $message, $content, $sha, $ref);
										}
									catch (Exception $e)
										{
				
										$message = "Adding " . $write_page_folder_file . " via Laneworks CMS Publish";
										$content = base64_encode($page_content);
				
										$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $write_page_folder_file, $message, $content, $ref);
				
										}		    				
		    				
									$N = array();
									$N['name'] = $name;
									$N['filename'] = $write_page_file;
									array_push($F[$BuildingBlockCategoryName], $N);
									
									$This_Count++;																						
									
									}
								else 
									{
										
									//echo $This_Count . "<br />";
									//echo $This_Count + 1 . "<br />";
									//var_dump($BuildingBlockCategory[$This_Count+1]);
									if(isset($BuildingBlockCategory[$This_Count+1]))
										{
										$nextname = $BuildingBlockCategory[$This_Count+1]['name'];
										$nextnameslug = str_replace("/","-",$nextname);
										$nextnameslug = str_replace(" ","-",$nextnameslug);
										$nextnameslug = strtolower($nextnameslug);	
										$Next_Page = $BuildingBlockCategoryNameSlug . '-' . $nextnameslug . ".html";							
										}
									else
										{																						
										$nextname = $thistag . " Building Blocks";	
										$nextnameslug = "building-blocks";
										$Next_Page = "/building-blocks.html";
										}										
	
					    			//echo "Processing: " . $name . "(" . $First_Building_Block . ")<br />";
									//echo "prev/next: " . $Previous_Page . "  /  " . $Next_Page . "<br />";
		
									$Page_Name = $name;
		
									$PageHeader = file_get_contents("http://control.laneworks.net/admin/project/templates/page.html");
									$PageHeader = str_replace("[Name]",chr(39).htmlentities($name, ENT_QUOTES).chr(39),$PageHeader);
				
									$PageBody = '';
									
									if($zone=='Essentials')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Just the Essentials</p>' . chr(10); 	
										}
									elseif($zone=='Technology')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Technology</p>' . chr(10); 	
										}	
									elseif($zone=='Business')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Business</p>' . chr(10); 	
										}
									elseif($zone=='Politics')
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Politics</p>' . chr(10); 	
										}																												
									else
										{
										$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">Complete</p>' . chr(10); 	
										}										
									
									$PageBody .= '<table cellpadding="0" cellspacing="0" align="center" border="0" width="100%">' . chr(10);
									$PageBody .= '<tr>' . chr(10);					
									$PageBody .= '<td align="center">' . chr(10);		
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-subway-circle.png" style="position: relative; z-index: 100; float: left; display: inline; margin-top:3px;" align="left" id="circle-one" width="40" />' . chr(10);		
									$PageBody .= '<div style="width: 95%; max-width: 890px; height: 25px; margin-left: 8px; margin-top:10px; background-color: ' . $category_hex . '; position: absolute; z-index: 50;" id="line-hex"></div>' . chr(10);
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-round-black-circle-red-center.png" width="50" style="position: relative; z-index: 100; float: center; display: inline;" id="stop-circle" />' . chr(10);										
									$PageBody .= '<img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-api-subway-circle.png" style="position: relative; z-index: 100; float: right; display: inline margin-top:3px;;" align="right" id="circle-two" width="40" />' . chr(10);										
									$PageBody .= '</td>' . chr(10);							
									$PageBody .= '</tr>' . chr(10);	
									$PageBody .= '</table>' . chr(10);			
									$PageBody .= '<p style="font-size: 22px; font-weight: bold;" align="center">{"' . $BuildingBlockCategoryName . '":"' . $name . '"}</p>' . chr(10); 
									$PageBody .= '<p>' . $about . '</p>' . chr(10);
									$PageBody .= '<div style="padding: 5px 25px 5px 25px;">' . chr(10);
									
									// Organizations
									if(count($organizations) > 0)
										{
										$PageBody .= '<p><strong>Organizations</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($organizations as $org)
											{
											$org_name = $org['name'];
											$org_url = $org['url'];	
											$org_text = $org['text'];			
											$PageBody .= '<li><a href="' . $org_url . '">' . $org_name . '</a>';
											if($org_text!=''){ $PageBody .= ' - ' . $org_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);
									
									// APIs
									if(count($apis) > 0)
										{
										$PageBody .= '<p><strong>APIs</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($apis as $api)
											{
											$api_name = $api['name'];
											$api_url = $api['url'];	
											$api_text = $api['text'];			
											$PageBody .= '<li><a href="' . $api_url . '">' . $api_name . '</a>';
											if($api_text!=''){ $PageBody .= ' - ' . $api_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);			
									
									// Links
									if(count($links) > 0)
										{
										$PageBody .= '<p><strong>Links</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($links as $link)
											{
											$link_name = $link['name'];
											$link_url = $link['url'];	
											$link_text = $link['text'];			
											$PageBody .= '<li><a href="' . $link_url . '">' . $link_name . '</a>';
											if($link_text!=''){ $PageBody .= ' - ' . $link_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);	
									
									// Tools
									if(count($tools) > 0)
										{
										$PageBody .= '<p><strong>Links</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($tools as $tool)
											{
											$tool_name = $tool['name'];
											$tool_url = $tool['url'];	
											$tool_text = $tool['text'];			
											$PageBody .= '<li><a href="' . $tool_url . '">' . $tool_name . '</a>';
											if($tool_text!=''){ $PageBody .= ' - ' . $tool_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);	
									
									// Questions
									if(count($questions) > 0)
										{
										$PageBody .= '<p><strong>Links</strong></p>' . chr(10);
										$PageBody .= '<ul style="padding: 1px 0px 1px 25px;">' . chr(10);																	
										foreach($questions as $question)
											{
											$question_name = $question['name'];
											$question_url = $question['url'];	
											$question_text = $question['text'];			
											$PageBody .= '<li>' . $question_name;
											if($question_text!=''){ $PageBody .= ' - ' . $question_text; } 
											$PageBody .= '</li>' . chr(10);
											}
										}
									$PageBody .= '</ul>' . chr(10);	
									$PageBody .= '</div>' . chr(10);
									
									$PageBody .= '<!-- Pagination links -->' . chr(10);
									$PageBody .= '<div class="pagination">' . chr(10);
									if($Previous_Page!='')
										{
										$PageBody .= '<a href="' . $Previous_Page . '" class="older"><< Prev</a>' . chr(10);
										}
									else
										{
										$PageBody .= '<a href="#" class="older"> </a>' . chr(10);
										}		
									//echo $This_Count . " == " . $Stop_Count . "<br />";
									if(($This_Count == $Stop_Count - 1) && isset($Category_Key[$Category_Count+1]))
										{
										$nextname = $Category_Key[$Category_Count+1];	
										$nextnameslug = str_replace("/","-",$nextname);
										$nextnameslug = str_replace(" ","-",$nextnameslug);
										$nextnameslug = strtolower($nextnameslug);	
										$Next_Page = $nextnameslug . ".html";							
										}								
																
									if($Next_Page!='')
										{
										$PageBody .= '<a href="' . $Next_Page . '" class="newer" tabindex="0">Next >></a>' . chr(10);
										}
									else 
										{
										$PageBody .= '<a href="#" class="newer" tabindex="0"> </a>' . chr(10);
										}
										
									$PageBody .= '</div>' . chr(10);						

									$page_content = $PageHeader . chr(10) . $PageBody . chr(10) . $ReturnHTML;
				
									if($zone=='Essentials'){ $write_page_folder = "journey/essentials/"; }	
									elseif($zone=='Technology'){ $write_page_folder = "journey/technology/"; }
									elseif($zone=='Business'){ $write_page_folder = "journey/business/"; }
									elseif($zone=='Politics'){ $write_page_folder = "journey/politics/"; }
									else{ $write_page_folder = "journey/complete/"; }	
									
									$write_page_file = $BuildingBlockCategoryNameSlug . '-' . $nameslug . ".html";
									$Previous_Page = $write_page_file;	
									//echo $write_page_folder . "<br />";			
									$write_page_folder_file = $write_page_folder . $write_page_file;		    				
		    									
									// Github
									$GitHubClient = new GitHubClient();
									$GitHubClient->setCredentials($guser,$gpass);
				
									$owner = $project_github_user;
									$ref = "gh-pages";
				
									try
										{
										$CheckFile = $GitHubClient->repos->contents->getContents($owner, $project_github_repo, $ref, $write_page_folder_file);
				
										//$name = $CheckFile->getname();
										$content = base64_decode($CheckFile->getcontent());
										$sha = $CheckFile->getsha();
				
										$message = "Updating " . $write_page_folder_file . " via Laneworks CMS Publish";
										$content = base64_encode($page_content);
				
										$updateFile = $GitHubClient->repos->contents->updateFile($owner, $project_github_repo, $write_page_folder_file, $message, $content, $sha, $ref);
										}
									catch (Exception $e)
										{
				
										$message = "Adding " . $write_page_folder_file . " via Laneworks CMS Publish";
										$content = base64_encode($page_content);
				
										$updateFile = $GitHubClient->repos->contents->createFile($owner, $project_github_repo, $write_page_folder_file, $message, $content, $ref);
				
										}		    				
		    				
									$N = array();
									$N['name'] = $name;
									$N['filename'] = $write_page_file;
									array_push($F[$BuildingBlockCategoryName], $N);
									
									$This_Count++;
																												
									}							
								
								}
	
							}
	
						// End Each Category
						array_push($buildingblocks, $F);					
						$Category_Count ++;
						}
					}
				}
					// End Project

			}
		}

		$ReturnObject = array();
		$ReturnObject['building_blocks_by_category'] = $buildingblocks;		

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

	});
?>
