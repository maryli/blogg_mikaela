<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
include('phone.php'); //vidarebefodrar till bloggsystem_phone.php
require_once("conn.php");	

$dbConn = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);
session_start();
// Skriver ut felmeddelande om anslutningen till databasen misslyckas
if (mysqli_connect_errno()) 
	{
		printf("Connect failed: %s\n", mysqli_connect_error());		
		exit();
	}

// Gör om input-data till Sträng för att skydda mot eventuell kod i input		
function safeInsert($string)														//funktion för "htmlentities" som gör om inskriven kod från användaren till vanlig text. Annars kan det bli stora problem i databasen.
{
	global $dbConn;																	//anger att funktionen ska använda sig av den globala $dbConn
	
	$string = htmlentities($string);
	$string = mysqli_real_escape_string($dbConn, $string);							//"mysqli_real_escape_string" gör om input-data till strängar, om de skulle innehålla kod
	return $string;

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
// Visar alla blogginlägg
function displayAllPosts() {

	global $dbConn;
	$sql = "SELECT id, category_id, subtitle, content, user, image, datetime FROM blogpost ORDER BY datetime DESC";
	mysqli_set_charset($dbConn, 'utf8');

	$result = mysqli_query($dbConn, $sql);
	
	while ($row = mysqli_fetch_assoc($result)) 
	{
		$id = $row['id'];
		$category_id = $row['category_id'];
		$subtitle = $row['subtitle'];
		$content = html_entity_decode($row['content']);
		$user = $row['user'];
		$datetime = $row['datetime'];
		$imageName = $row['image'];	
		
		if (strlen($imageName)>0) {
			$image = "<img src='$imageName' alt='image' title='$imageName' style='float:right; margin-left:10px;'/>";
		}
		else {
			$image = null;
		}
//skriver ut blogginlägg		
		echo "<a href='#$id' name='$id' style='text-decoration:none;'><div class='blogposts'>			
		$image
		<span class='headline'>$subtitle</span><br />
		<span class='contentText'>$content</span> 		
		<div style='clear:right;'></div>
		</div>
		<div class='blogbottom'>Postat av: $user, $datetime</div></a>";
	}
}

// Dropdownmeny
function populateDropdown() {

	global $dbConn;															//gör databasen tillgänglig
	$sql = "SELECT id, name FROM category";									//fråga till databasen
	
	$result = mysqli_query($dbConn, $sql);									//stoppar in frågan i $result
	$option = "";
	while ($row = mysqli_fetch_assoc($result))								//loopar igenom alla rader
	{
		$id = $row['id'];
		
		$name = $row['name'];
				
																			//Added en option( kategori ) till variabeln option för varje varv i loopen
		$option = "$option<option value='$id'>$name</option>";
	}
	return $option;															//När loopen är klar så returnerar vi option variablen
}
// Tar emot formulärdata och sparar ner i databasen 		
if($_SERVER["REQUEST_METHOD"] == "POST") 									//om något är postas kör koden, annars inte
{
	$content = safeInsert ($_POST['content']);								//kallar på funktionen "safeInsert" som är till för att skydda mot skadlig kod
	$posterName = safeInsert ($_POST['posterName']);	
	$categoryId = (int) $_POST['Kategori'];									//hämtar upp $categoryId från formuläret. sätter som heltal
	$subtitle = safeInsert ($_POST['subtitle']);
	$imageName = "";														//används om det inte laddas upp en bild
	$thumbName = "";

																																//skickar in till databasen och visar ut
	//ladda upp bild
	IF (!empty($_FILES["filen"]["name"])) //Kontrollerar om det finns e fil med i uppladdningen. Om inte så körs inte koden nedan.
	{
		$imageName = $_FILES['filen']['name']; //hämtar namnet på filen jag laddar upp från formuläret	
		$watermark =  $_FILES['filen']['tmp_name'] ;
		$thumbName = createImages($watermark); //fångar upp det createThumb returnerar, i detta fallet filens namn
							
	}	

	$sql = "INSERT INTO blogpost (image, thumbnail, category_id, content, subtitle, user, datetime) VALUES ('$imageName', '$thumbName', $categoryId, '$content', '$subtitle', '$posterName', NOW())";		//NOW() --> sql-sats som genererar datum och tid just nu
																																								//glöm inte fnuttar kring värdena i VALUES, de är text! är det siffra behöver inga fnuttar
	mysqli_query($dbConn, $sql);
	
}
//funktion som skapar tumnagelbilder
FUNCTION createImages($watermark)										
{
	global $dbConn;
	$image = imagecreatefromjpeg($_FILES['filen']['tmp_name']);		//hämtar tmp-filen
	$orgWidth = imagesx($image);							//räknar ut bildens bredd
	$orgHeight = imagesy($image);							//räknar ut bildens höjd
	
	if($watermark)
	{
	//Lägg till copyrighttext
	$textcolor =  imagecolorallocate($image, 255,0,255);
	imagettftext($image, 20, 0 ,10, $orgHeight - 25 , $textcolor, "mathilde.ttf","Mikaelas blogg"); 
	}	

	//create thumbnail
	$thumbWidth = CEIL(($orgWidth / $orgHeight) * 150);		//ger proportionerna i bildstorleken och gångar med 150 som är den nya höjden. CEIL avrundar

	$thumb = imagecreatetruecolor($thumbWidth, 150);		//sätter storleken på tumnagelbilden - höjden är alltid 150 px

	imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, 150, $orgWidth, $orgHeight);

	$thumbname = "thumb_" . $_FILES['filen']['name'];		//sparar bildens namn i $thumbname, samt lägger till prefixet thumb_
	imagejpeg($thumb, $thumbname, 100);						//vilken bild, namn på bilden, kvalitet på bilden
	
	//create image
	$imageWidth = CEIL(($orgWidth / $orgHeight) * 350);		//ger proportionerna i bildstorleken och gångar med 150 som är den nya höjden. CEIL avrundar

	$newImage = imagecreatetruecolor($imageWidth, 350);		//sätter storleken på tumnagelbilden - höjden är alltid 150 px

	imagecopyresampled($newImage, $image, 0, 0, 0, 0, $imageWidth, 350, $orgWidth, $orgHeight);

	$imageName = $_FILES['filen']['name'];		//sparar bildens namn i $thumbname, samt lägger till prefixet thumb_
	imagejpeg($newImage, $imageName, 100);
	
	imagedestroy($thumb);										//imagedestroy tar bort bilderna ur webbserverns minne efter ett tag
	imagedestroy($image);
	imagedestroy($newImage);

	RETURN $thumbname;
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

<script type="text/javascript" src="tinymce\jscripts\tiny_mce\tiny_mce.js"></script>

<script type="text/javascript">

tinyMCE.init({
	// General options
	mode : "textareas",
	theme : "simple",
});

</script>

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
				<ul>
					<li><img src='about.png' alt='image' style='margin-left: 30px;'></li>
				</ul>
				<span class="about">Nunc dignissim purus eu erat imperdiet eget faucibus nibh volutpat. Donec leo ante, consequat vel gravida eu, tincidunt ac erat. Aliquam erat volutpat. Mauris rhoncus magna ut libero euismod</span> 
				
			</div>		
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
					<li><a href='imageGallery.php' style="font-size: 20px; color: #3a3c3d;">Bildgalleri</a></li>
				</ul>
			</div>
		</div>
		<div id="content">
		
			<?php
			if(isset($_SESSION['login_user'])) //visar formuläret om användaren är inloggad
			{
				$dropDown = populateDropdown(); //kör funktionen som returnerar strängen med val och sparar i dropdown
				
				echo"<form class='box' action='bloggsystem.php' method='post' enctype='multipart/form-data'>
					<fieldset>
					<legend>Posta blogginlägg</legend>
					Namn<br />
					<input type='text' autocomplete='off' name='posterName'/><br />
					Rubrik<br />
					<input type='text' autocomplete='off' name='subtitle'/><br />
					Brödtext<br />
					<textarea name='content'></textarea><br />								
					Välj kategori<br />
					<select name='Kategori'>	
					$dropDown
					</select>
					<br /><br />					
					<input TYPE='file' name='filen' />
					<br /><br />
					<input type='submit' class='button' value='Posta'/>
					</fieldset>				
				</form>";
			}
			
			?>
			
			<?php displayAllPosts(); 	//visar alla poster från alla kategorier
			
			?>
		</div>	
		<div style="clear:left;"></div>	
	</div>
	

</body>
</html>
