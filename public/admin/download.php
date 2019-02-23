<?php
include "login.php";
$fName=$_POST["file"];
$action=$_POST["azione"];
$type=$_POST["type"];
//print_r($_POST);
//if($fName && $action && $type){
	if (file_exists($fName)){
		$f=fopen($fName,'r');
		$str=fread($f,filesize($fName));
		fclose($f);
		$name=basename($fName);
		switch($type){
			case "excel":
				break;
			case "zip":
				break;
			case "gif":
				break;
			case "png":
				break;
			case "text":
				Header ("Content-type: text");
				header('Content-Disposition: attachment; filename="'.$name.'"'); 
				break;
			default:
				die("<p><b>Tipo di File errato</b></p>");
				break;
		}	
		echo $str;
	}
	else
		echo "<p><b>Il File non esiste</b></p>";
//}
?>