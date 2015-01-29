
//$.ajax({   url: 'path.php',   type: 'PUT',   success: function(data) {     //play with data }});

$WorkingResponse = "";
$blogcount = 0;
$textEditors = "";

function BlogShowme($row)
	{
	$thisrow = $row.id;			
	$thisslug = $thisrow.replace("-icon","");
	
	$thisrow = document.getElementById($thisslug).style.display;

	if($thisrow=='none')
		{
		document.getElementById($thisslug).style.display = '';	
		}
	else
		{
		document.getElementById($thisslug).style.display = 'none';	
		}			
	}	
	
function addBlogPost(tinyInstance)
	{
	
	$blog_name = document.getElementById("add-blog-name").value;
	
	$blog_description = tinyMCE.get('add-blog-description').getContent()

	$blog_url = document.getElementById("add-blog-url").value;
	$blog_tags = document.getElementById("add-blog-tags").value;
	$blog_slug = document.getElementById("add-blog-slug").value;	
	
	$postData = {};
	  
	$postData['appid'] = '5ed48098';
	$postData['appkey'] = 'b6c8c8cba92815a6cdfe6e780bb0d2f5';

	$postData['name'] = $blog_name;
	$postData['description'] = $blog_description;
	$postData['url'] = $blog_url;
	$postData['tags'] = $blog_tags;
	$postData['slug'] = $blog_slug
		
	$hosturl = 'http://blog.api.kinlane.com';
	$baseurl = '/';
	
	$resource = 'blog/';

	//$query = '?appid=5ed48098';
	//$query = $query + '&appkey=b6c8c8cba92815a6cdfe6e780bb0d2f5';
	
	$apiurl = $hosturl + $baseurl + $resource;

	$.ajax({
		url: $apiurl,   
		type: 'POST', 
		data: $postData,
		success: function(data) {
			
			$WorkingResponse = data;
				
			$blogcount = 0;
			
			$.each(data, function(blogKey, blogValue) {
				
				$blog_name = blogValue['name'];
				$blog_description = blogValue['description'];
				$blog_url = blogValue['url'];
				$blog_tags = blogValue['tags'];
				$blog_slug = blogValue['slug'];
				
				$html = getBlogListing($blog_name,$blog_description,$blog_url,$blog_tags,$blog_slug);
				$('#jsonBlogEditorTable').append($html); 
				
				$blogcount++;
				
				});

			
			}
		});		
	
	}	
	
function getAddBlogPost()
	{		
		
	html = '<tr id="add-blog-post" style="display: none;"><td align="center" style="font-size: 12px; background-color:#CCC; padding:5px;">';

	html = html + '<span style="font-size: 18px;"><strong>Add New Blog</span></strong>';
    html = html + '<table border="0" width="90%" cellpadding="3" cellspacing="2" id="blog-post-table">';
    
    html = html + '<tr>';
    html = html + '<td align="right" width="5%"><strong>name:</strong></td>';
    html = html + '<td align="left"><input type="text" id="add-blog-name" value="" style="width:95%;" /></td>';
    html = html + '</tr>';
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>description:</strong></td>';
    html = html + '<td align="left"><textarea id="add-blog-description" name="Page_Body" rows="20" cols="50" class="mceEditor"></textarea></td>';
    html = html + '</tr>';
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>url:</strong></td>';
    html = html + '<td align="left"><input type="text" id="add-blog-url" value="" style="width:95%;" /></td>';
    html = html + '</tr>';
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>tags:</strong></td>';
    html = html + '<td align="left"><input type="text" id="add-blog-tags" value="" style="width:95%;" /></td>';
    html = html + '</tr>'  
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>slug:</strong></td>';
    html = html + '<td align="left"><input type="text" id="add-blog-slug" value="" style="width:95%;" /></td>';
    html = html + '</tr>'      
    
    html = html + '<tr>';
    html = html + '<td align="center" style="background-color:#FFF;" colspan="2"><input type="button" name="addAPIButton" value="Add" onclick="addBlogPost();" /></td>';
    html = html + '</tr>'         
     
    html = html + '</table>';
    
    html = html + '<br /></td></tr>';  
    	
	return html; 			
	}
	
function ConfirmDelete($blogcount)
	{
	if(confirm("Are you Sure?"))
		{
		deleteBlogPost($blogcount);
		}
	else{
		
		}	
	}		
	
function deleteBlogPost($blogcount)
	{
	;
	$blog_slug = document.getElementById("edit-blog-slug-" + $blogcount).value;	
	
	$hosturl = 'http://blog.api.kinlane.com';
	$baseurl = '/';
	
	$resource = 'blog/';

	$query = '?appid=5ed48098';
	$query = $query + '&appkey=b6c8c8cba92815a6cdfe6e780bb0d2f5';
	
	$apiurl = $hosturl + $baseurl + $resource + $blog_slug + '/' + $query;

	$.ajax({
		url: $apiurl,   
		type: 'DELETE', 
		success: function(data) {
			
	       	$('#blog-post-' + $blogcount).remove();
	       	$('#add-blog-post-' + $blogcount).remove();
	       	$('#edit-blog-post-' + $blogcount).remove();
	        										
			}
		});		
	
	}	
	
function editBlogPost($blogcount)
	{
	
	$blog_name = document.getElementById("edit-blog-name-" + $blogcount).value;	
	$blog_description = tinyMCE.get("edit-blog-description-" + $blogcount).getContent()
	$blog_url = document.getElementById("edit-blog-url-" + $blogcount).value;
	$blog_tags = document.getElementById("edit-blog-tags-" + $blogcount).value;
	$blog_slug = document.getElementById("edit-blog-slug-" + $blogcount).value;	
	
	$postData = {};
	  
	$postData['appid'] = '5ed48098';
	$postData['appkey'] = 'b6c8c8cba92815a6cdfe6e780bb0d2f5';

	$postData['name'] = $blog_name;
	$postData['description'] = $blog_description;
	$postData['url'] = $blog_url;
	$postData['tags'] = $blog_tags;
		
	$hosturl = 'http://blog.api.kinlane.com';
	$baseurl = '/';
	
	$resource = 'blog/';

	$apiurl = $hosturl + $baseurl + $resource + $blog_slug + '/';
	
	$.ajax({
		url: $apiurl,   
		type: 'put', 
		data: $postData,
		success: function(data) {
			
			$WorkingResponse = data;
				
			$blogcount = 0;
			
			$.each(data, function(blogKey, blogValue) {
				
				$blog_name = blogValue['name'];
				$blog_description = blogValue['description'];
				$blog_url = blogValue['url'];
				$blog_tags = blogValue['tags'];
				$blog_slug = blogValue['slug'];

				});
							
			}
		});		
	
	}		
	
function getEditBlogPost($blog_name,$blog_description,$blog_url,$blog_tags,$blog_slug,$blogcount)
	{				
		
	html = '<tr id="edit-blog-post-' + $blogcount + '" style="display: none;"><td align="center" style="font-size: 12px; background-color:#CCC; padding:5px;">';	

	html = html + '<span style="font-size: 18px;"><strong>Edit Blog</span></strong>';
    html = html + '<table border="0" width="90%" cellpadding="3" cellspacing="2" id="blog-post-table">';
    
    html = html + '<tr>';
    html = html + '<td align="right" width="5%"><strong>name:</strong></td>';
    html = html + '<td align="left"><input type="text" id="edit-blog-name-' + $blogcount + '" value="' + $blog_name + '" style="width:95%;" /></td>';
    html = html + '</tr>';
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>description:</strong></td>';
    html = html + '<td align="left"><textarea id="edit-blog-description-' + $blogcount + '" name="Page_Body" rows="20" cols="50" class="mceEditor">' + $blog_description + '</textarea></td>';
    html = html + '</tr>';    
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>url:</strong></td>';
    html = html + '<td align="left"><input type="text" id="edit-blog-url-' + $blogcount + '" value="' + $blog_url + '" style="width:95%;" /></td>';
    html = html + '</tr>';
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>tags:</strong></td>';
    html = html + '<td align="left"><input type="text" id="edit-blog-tags-' + $blogcount + '" value="' + $blog_tags + '" style="width:95%;" /></td>';
    html = html + '</tr>'  
    
    html = html + '<tr>';
    html = html + '<td align="right"><strong>slug:</strong></td>';
    html = html + '<td align="left"><input type="text" id="edit-blog-slug-' + $blogcount + '" value="' + $blog_slug + '" style="width:95%;" readonly /></td>';
    html = html + '</tr>'      
    
    html = html + '<tr>';
    html = html + '<td align="center" colspan="2"><input type="button" name="editAPIButton" value="Save" onclick="editBlogPost(' + $blogcount + ')" /></td>';
    html = html + '</tr>'         
     
    html = html + '</table>';
    
    html = html + '<br /></td></tr>';  
    	
	return html; 			
	}		
	
function getBlogListing($blog_name,$blog_description,$blog_url,$blog_tags,$blog_slug,$blogcount)
	{
		
	html = '<tr id="blog-post-' + $blogcount + '">';
	html = html + '<td style="padding-top: 5px; padding-bottom: 5px;">';	

	html = html + '<a href="#" onclick="ConfirmDelete(' + $blogcount + '); return false;" id="delete-blog-post-' + $blogcount + '-icon" title="Delete Blog Post"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-delete-circle.png" width="35" align="right"  /></a>';		
    html = html + '<a href="#" onclick="BlogShowme(this); return false;" id="edit-blog-post-' + $blogcount + '-icon" title="Edit Blog Post"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-edit-circle.png" width="35" align="right"  /></a>';	
	
	html = html + '<span style="font-size:20px;">';
	html = html + '<strong>' + $blog_name + '</strong>';
	html = html + '</span>';
	
	html = html + '</td>';
	html = html + '</tr>';
	
	return html; 
				
	}	

function loadBlogEditor()
    {

	$response = "";
	
	$html = getAddBlogPost();
	$('#jsonBlogEditorTable').append($html); 
	$textEditors = "add-blog-post";
	
	$hosturl = 'http://blog.api.kinlane.com';
	$baseurl = '/';
	
	$resource = 'blog/';

	$query = '?appid=5ed48098';
	$query = $query + '&appkey=b6c8c8cba92815a6cdfe6e780bb0d2f5';
	
	$apiurl = $hosturl + $baseurl + $resource + $query;
	
	$.ajax({
		url: $apiurl,   
		type: 'GET',   
		success: function(data) {
			
			$WorkingResponse = data;
			
			$.each(data, function(blogKey, blogValue) {
				
				$blog_name = blogValue['name'];
				$blog_description = blogValue['description'];
				$blog_url = blogValue['url'];
				$blog_tags = blogValue['tags'];
				$blog_slug = blogValue['slug'];
				
				$html = getBlogListing($blog_name,$blog_description,$blog_url,$blog_tags,$blog_slug,$blogcount);
				$('#jsonBlogEditorTable').append($html); 
				$html = getEditBlogPost($blog_name,$blog_description,$blog_url,$blog_tags,$blog_slug,$blogcount)
				$('#jsonBlogEditorTable').append($html);
				$textEditors = $textEditors + ",edit-blog-post-" + $blogcount;
				
				$blogcount++;
				
				});

			tinyMCE.init({
      			mode : "textareas",		
				theme : "advanced",
				plugins : "spellchecker,pagebreak,layer,table,advhr,advimage,autosave,advlist,advlink,inlinepopups,insertdatetime,preview,media,contextmenu,paste,nonbreaking",
				theme_advanced_buttons1 : "save,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,code,|,hr,|,spellchecker",
				theme_advanced_buttons2 : "",
				theme_advanced_buttons3 : "",
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				theme_advanced_statusbar_location : "bottom",
				extended_valid_elements : "iframe[src|width|height|name|align]",
				width : "550px",
				height : "300px"
			});	

			
			}
		});		

	}	