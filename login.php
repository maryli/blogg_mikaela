<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
require_once("conn.php");	

$dbConn = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

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
//startar sessionen för att möjliggöra att spara information i sessionsvariabler	
session_start();
if($_SERVER["REQUEST_METHOD"] == "POST") 
{
	
	$username=addslashes($_POST['username']); 
	$password =  hash('sha256', $_POST['password'] );

	global $dbConn;
	$sql="SELECT id, username FROM user WHERE username='$username' and password='$password'"; 

	$result = mysqli_query($dbConn, $sql); //hämtar användarnamn och lösenord från databasen

	$row=mysqli_fetch_array($result); 
	
	$count=mysqli_num_rows($result); //räknar antal rader från databasen


	//om resultatet matchar lösenord och användarnamn så blir antal rader från databasen 1 och vi tillåts logga in
	if($count==1)
	{
		
		$_SESSION['login_user']=$row['username']; //sätter sessionsvariabeln loginuser till det användarnamn vi valde att logga in med

		header("location: bloggsystem.php"); //skickar användaren till bloggsystem.php vid lyckad inloggning
	}
}
	else 
	{
		$error="Your Login Name or Password is invalid"; //användaren blir inte inloggad, fel lösenord/användarnamn
	}

//skapa
if(isset($_POST['add']))
{	
	global $dbConn;	
	$username = $_POST['username'];
	$password =  hash('sha256', $_POST['password'] );
	$verfpassword =  hash('sha256', $_POST['verfpassword'] );

	$hash = mysqli_real_escape_string($dbConn, $password);
	$user = mysqli_real_escape_string($dbConn, $username);
	
	if ($password == $verfpassword) {
		$sql = "insert into user (username, password) VALUES('$user', '$password')";
		mysqli_query($dbConn, $sql);
		header("location: login.php");
	}
	else {
		header("location: login.php");
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
											
		echo "<li><a href='bloggsystem.php#$id'>$subtitle</a></li>";
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
		<div class='loginContent' style="padding-bottom: 70px;">
			<br />
			<div  id="loginform">
			<img src="login.png" alt="login" style="float: right;"/>
				<form action="login.php" method="post">
					<table border="0" cellspacing="6">
						<td>
							<span style="font-size: 20px;">LOGGA IN</span>	
						</td>
						<tr>
							<td>
								Användarnamn
							</td>
							<td>
								<input type="text" autocomplete="off" name="username"/>
							</td>
						</tr>
						<tr>
							<td>
								Lösenord
							</td>
							<td>
								<input type="password" name="password"/>
							</td>
						</tr>
						<tr>
							<td>					
							</td>
							<td>
								<input type="submit" class="button" value="Logga in"/>
							</td>
						</tr>				
					</table>	
				</form>
			</div>


		<div style="clear:left;"></div>
		
			<br />
			<div  id="loginform">
			<img src="member.png" alt="login" style="float: right;"/>
				<form action="login.php" method="post">
				<input type="hidden" name="add" value="true">
					<table border="0" cellspacing="6">
						<td>
						<span style="font-size: 20px;">NY MEDLEM</span>		
						</td>						
						<tr>
							<td>
								Användarnamn
							</td>
							<td>
								<input type="text" autocomplete="off" name="username"/>
							</td>
						</tr>
						<tr>
							<td>
								Lösenord
							</td>
							<td>
								<input type="password" name="password"/>
							</td>
						</tr>
						<tr>
							<td>
								Verifiera Lösenord
							</td>
							<td>
								<input type="password" name="verfpassword"/>
							</td>
						</tr>						
						<tr>
							<td>					
							</td>
							<td>
								<input type="submit" class="button" value="Skicka"/>
							</td>
						</tr>				
					</table>	
				</form>
			</div>

		
		<div style="clear:left;"></div>		
		</div>
		</div>
	</div>
</body>
</html>
