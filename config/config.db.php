<?php

	$domain=explode(".",$_SERVER["HTTP_HOST"]); 
	$userIp=$_SERVER['REMOTE_ADDR'];
	$dbName='gisclient_demo';
	$dbSchema='gisclient_22';
	$userSchema=$dbSchema;
	$charSet='UTF-8';
	
//$domain = array('camogli');


	if(in_array('portalesit',$domain)){
		$dbName='gisclient';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}	

	if(in_array('imperia',$domain)){
		$dbName='gw_imperia';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

	if(in_array('lavagna',$domain)){
		$dbName='gw_lavagna';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}	

	if(in_array('carasco',$domain)){
		$dbName='gw_carasco';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

	if(in_array('savignone',$domain)){
		$dbName='gw_savignone';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
	
	if(in_array('noli',$domain)){
                $dbName='gw_noli';
                $charSet='ISO-8859-15';
                $dbSchema='gisclient_22';
                $userSchema=$dbSchema;
        }

	if(in_array('demo',$domain)){
		$dbName='gw_demo';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

	if(in_array('vezzano',$domain)){
		$dbName='gw_vezzano';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

	if(in_array('sori',$domain)){
		$dbName='gw_sori';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
	if(in_array('rapallo',$domain)){
		$dbName='gw_rapallo';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema='admin';
	}

      if(in_array('moneglia',$domain)){
		$dbName='gw_moneglia';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

      if(in_array('pieveligure',$domain)){
		$dbName='gw_pieveligure';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

      if(in_array('camogli',$domain)){
		$dbName='gw_camogli';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

       if(in_array('bolano',$domain)){
		$dbName='gw_bolano';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

       if(in_array('busalla',$domain)){
		$dbName='gw_busalla';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('fivizzano',$domain)){
		$dbName='gw_fivizzano';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('vernazza',$domain)){
		$dbName='gw_vernazza';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('dego',$domain)){
		$dbName='gw_dego';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('ceriale',$domain)){
		$dbName='gw_ceriale';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('leivi',$domain)){
		$dbName='gw_leivi';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

       if(in_array('dianosanpietro',$domain)){
		$dbName='gw_dianosanpietro';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

      if(in_array('pianacrixia',$domain)){
		$dbName='gw_pianacrixia';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}


       if(in_array('plone3',$domain)){
		$dbName='gw_savona';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}


       if(in_array('cairomontenotte',$domain)){
		$dbName='gw_cairo';
		$charSet='UTF8';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('giusvalla',$domain)){
		$dbName='gw_giusvalla';
		$charSet='ISO-8859-15';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}

       if(in_array('sestrilevante',$domain)){
		$dbName='sit';
		$charSet='UTF8';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}
       if(in_array('magliolo',$domain)){
		$dbName='gw_magliolo';
		$charSet='UTF8';
		$dbSchema='gisclient_22';
		$userSchema=$dbSchema;
	}




	//Impostazioni database Postgresql
	define('DB_NAME',$dbName);
	define('DB_SCHEMA',$dbSchema);
	define('USER_SCHEMA',$userSchema);
	define('CHAR_SET',$charSet);
	define('DB_HOST','127.0.0.1');
	define('DB_PORT','5432');
	define('DB_USER','postgres');//Superutente
	define('DB_PWD','postgres');
	
	//Utente scritto sul file .map
	define('MAP_USER','mapserver');
	define('MAP_PWD','mapserver');
	
	//Superutente per l'accesso ad Author
	define('SUPER_USER','Admin');
	define('SUPER_PWD','@1sw3b');	
	

	
?>
