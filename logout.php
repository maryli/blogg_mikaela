<?php
session_start();
if(session_destroy()) //stänger sessioner och vidarbefodrar den utloggade användaren till bloggsystem.php
{
header("Location: bloggsystem.php");
}
?>