<?php

//Accesso validato al GisClient
if(defined('USER_SCHEMA') && USER_SCHEMA == "admin"){ //Utenti PLONE
	require_once ROOT_PATH."config/users/user.Plone.class.php";
	$usrObj=Array("user"=>"username","pwd"=>"enc_password","auth"=>"authstring");
} else {
	if(!defined('USER_SCHEMA')) define('USER_SCHEMA',DB_SCHEMA);
	require_once ROOT_PATH."lib/user.GisClient.class.php";
	$usrObj=Array("user"=>"username","pwd"=>"enc_password");
}

$usr=new userApps($usrObj);

//Accesso all'Author da superutente
if (!defined('SUPER_PWD')){
	define('SUPER_PWD','');
}

if ((SUPER_PWD=='') ||
    (substr(SUPER_PWD, 0, 4) == 'md5:' && isset($_POST["username"]) && $_POST["username"]==SUPER_USER && $_POST["enc_password"]==substr(SUPER_PWD, 4)) ||
    (isset($_POST["username"]) && $_POST["username"]==SUPER_USER && $_POST["enc_password"]==md5(SUPER_PWD))||
    (isset($_SESSION["USERNAME"]) && $_SESSION["USERNAME"]==SUPER_USER && empty($_REQUEST["logout"]))){
	$_SESSION["USERNAME"]=SUPER_USER;
	$usr->status=true;
}
else{
	$usr->context=(dirname($_SERVER["SCRIPT_FILENAME"])."/"==ADMIN_PATH)?('author'):('gisclient');	
	if(!empty($usr->data["logout"])) $usr->logout();
	if(!$usr->checkUser())
		$message=$usr->error->message;

}

?>
