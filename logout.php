<?php
session_start();
if(session_destroy()) //st�nger sessioner och vidarbefodrar den utloggade anv�ndaren till bloggsystem.php
{
header("Location: bloggsystem.php");
}
?>