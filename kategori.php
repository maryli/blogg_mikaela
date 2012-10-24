<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
include('phone.php');
require_once("conn.php");	

$dbConn = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);
session_start();
// Skriver ut felmeddelande om anslutningen till databasen misslyckas
if (mysqli_connect_errno()) 
	{
		printf("Connect failed: %s\n", mysqli_connect_error());		
		exit();
	}


// Visar kategorier	
function displayCategories() {

	global $dbConn;																	//gör databasen tillgänglig
	$sql = "SELECT id, name, description FROM category";							//fråga till databasen
	
	$result = mysqli_query($dbConn, $sql);											//stoppar in svaret från databasen i result
	$count = mysqli_num_rows($result);
	
	while ($row = mysqli_fetch_assoc($result))										//loopar igenom alla rader och skriver ut infon från databasen
	{	
		$id = $row['id'];		
		$name = $row['name'];	
	
		$sql = "SELECT id description FROM blogpost WHERE category_id = $id";       //SQL sats som selekterar blogposter som tillhör respektive kategori
		$blogPosts = mysqli_query($dbConn, $sql);                                   //Hämtar blogposter som tillhör respektive kategori
		$blogPostCount = mysqli_num_rows($blogPosts);                               //Räknar raderna i dom blogposter vi hämtat		
											
		echo "<li><a href='kategori.php?id=$id'>$name ($blogPostCount)</a></li>";
	}
		
}
//visar alla poster från den kategori som är vald
function displayCategoryPosts() {

	global $dbConn;
	$categoryId = (int) $_GET['id']; //hämtar och sparar GET variabeln id i categoryId
	$sql = "SELECT id, category_id, subtitle, content, user, image, datetime FROM blogpost WHERE category_id = $categoryId ORDER BY datetime DESC";
	
	
	$result = mysqli_query($dbConn, $sql);
	
	
	while ($row = mysqli_fetch_assoc($result)) 
	{
		$id = $row['id'];
		$category_id = $row['category_id'];
		$subtitle = $row['subtitle'];
		$content = html_entity_decode($row['content']);          //funktionen html_entity_decode kodar om html taggarna från text till html
		$user = $row['user'];
		$datetime = $row['datetime'];
		$imageName = $row['image'];
		
		if (strlen($imageName)>0) {
			$image = "<img src='$imageName' alt='image' title='$imageName' style='float:right; margin-left:10px;'/>";
		}
		else {
			$image = null;
		}		
		
		echo "<a href='#$id' name='$id' style='text-decoration:none;'><div class='blogposts'>			
		$image
		<span class='headline'>$subtitle</span><br />
		<span class='contentText'>$content</span> 		
		<div style='clear:right;'></div>
		</div>
		<div class='blogbottom'>Postat av: $user, $datetime</div></a>";
		
	}	
}

//senaste inläggen
function recentPosts() {

	global $dbConn;																	//gör databasen tillgänglig
	$sql = "SELECT id, subtitle FROM blogpost ORDER BY datetime DESC LIMIT 5";							//fråga till databasen
	
	$result = mysqli_query($dbConn, $sql);											//stoppar in svaret från databasen i result
	
	while ($row = mysqli_fetch_assoc($result))										//loopar igenom alla rader och skriver ut infon från databasen
	{
		$id = $row['id'];
		$subtitle = $row['subtitle'];
											
		echo "<li><a href='#$id'>$subtitle</a></li>";
	}
		
}
		
 ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href='stilmall.css' rel='stylesheet' type='text/css' /> 
<title>Mikaelas blogg</title>
</head>
 
<body>
	
	<div id="header">
		<div id="headercontent"><img src='headline.jpg' alt='image'/>
		<?php 
			if(isset($_SESSION['login_user']))	//skriver ut login om användaren inte är inloggad annars logout
			{
				echo "<a href='logout.php' class='loginbutton'>Logga ut</a>";
			}
			else
			{
				echo "<a href='login.php' class='loginbutton'>Logga in</a>";	
			}
		?>
		</div>
	</div>
	<div id="contentwrapper">
		<div id="sidebar">
			<div class="sidebarWidget">
				<span class='submenuHeadlines'>Kategorier</span>
				<ul>
					<li><a href='bloggsystem.php'>Hem</a></li>
					<?php 
						displayCategories(); //visar kategorierna i sidmenyn
					?>
					
			
				</ul>
			</div>								
			<div class="sidebarWidget">
				<span class='submenuHeadlines'>Senaste inläggen</span>
				<ul>
				<?php					
					recentPosts();
				?>
				</ul>
			</div>
			<div class="sidebarWidget">
				<ul>
					<li><a href='imageGallery.php'>Bildgalleri</a></li>
				</ul>
			</div>
		</div>		
		<div id="content">				
			<?php displayCategoryPosts(); ?> <!--visar alla poster för vald kategori-->						
		</div>
		<div style="clear:left;"></div>

	</div>
		
</body>
</html>
