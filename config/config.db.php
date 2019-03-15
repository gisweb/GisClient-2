<?php

$domain=explode(".",$_SERVER["HTTP_HOST"]);
$userIp=$_SERVER['REMOTE_ADDR'];
$charSet='UTF-8';
$port='5434';
$dbUser='gwAdmin';
$dbPwd='!{!dpQ3!Hg7kdCA9';


if (isset($_REQUEST["db"])) $_SESSION["gisclient_database"]=$_REQUEST["db"];
if (isset($_SESSION["gisclient_database"])){
    $dbName=$_SESSION["gisclient_database"];
    $charSet='UTF-8';
    $dbSchema='gisclient_22';
    $dbport='5434';
    $userSchema=$dbSchema;
}
else{
	$dbSchema='gisclient_22';
	$userSchema=$dbSchema;

	if (in_array('sanremo',$domain)){
		$dbName='gw_sanremo';
	}
	if (in_array('pieveligure',$domain)){
		$dbName='gw_pieveligure';
	}
	if (in_array('bolano',$domain)){
		$dbName='gw_bolano';      
	}
	if (in_array('recco',$domain)){
		$dbName='gw_recco';
	}
	if (in_array('taggia',$domain)){
		$dbName='gw_taggia';
	}
	if (in_array('imperia',$domain)){
			$dbName='gw_imperia';
	}
	if (in_array('andora',$domain)){
			$dbName='gw_andora';
	}
	if (in_array('noli',$domain)){
			$dbName='gw_noli';
	}
	if (in_array('camogli',$domain)){
		$dbName='gw_camogli';
	}
	if (in_array('fivizzano',$domain)){
		$dbName='gw_fivizzano';
	}	
	if (in_array('magliolo',$domain)){
		$dbName='gw_magliolo';
	}	
	if (in_array('dianosanpietro',$domain)){
		$dbName='gw_dianosanpietro';
	}	
	if (in_array('dego',$domain)){
		$dbName='gw_dego';
	}	
	if (in_array('vernazza',$domain)){
		$dbName='gw_vernazza';
	}	
	if (in_array('lavagna',$domain)){
		$dbName='gw_lavagna';
	}	
	if (in_array('sestrilevante',$domain)){
		$dbName='sit';
	}	
	if (in_array('sori',$domain)){
		$dbName='gw_sori';
	}	
	if (in_array('savignone',$domain)){
		$dbName='gw_savignone';
	}	
	if (in_array('leivi',$domain)){
		$dbName='gw_leivi';
	}		
	if (in_array('pianacrixia',$domain)){
		$dbName='gw_pianacrixia';
	}		
	if (in_array('ceriale',$domain)){
		$dbName='gw_ceriale';
	}		
	if (in_array('sori',$domain)){
		$dbName='gw_sori';
	}		
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	if (in_array('vezzanoligure',$domain) || in_array('vezzano',$domain)){
		$dbName='gw_vezzano';
	}
	
	
	////NUOVI
    if (in_array('moneglia',$domain)){
			$dbName='gw_moneglia';
	}
	
if (in_array('gisclient',$domain)){
			$dbName='gw_sanremo';
	}

}


//Impostazioni database Postgresql
define('DB_NAME', $dbName);
define('DB_SCHEMA', $dbSchema);
define('USER_SCHEMA', $userSchema);
define('CHAR_SET', 'UTF-8');
define('DB_HOST', '195.88.6.158');
define('DB_PORT', $port);
define('DB_USER', $dbUser); //Superutente
define('DB_PWD', $dbPwd);

define('MAP_USER','mapserver');
define('MAP_PWD','mapserver');
	
//Superutente per l'accesso ad Author
define('SUPER_USER','claudio');
	
?>
