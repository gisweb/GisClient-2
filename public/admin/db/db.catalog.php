<?php

require_once (ROOT_PATH."lib/functions.php");
$save=new saveData($_POST);

$p=$save->performAction($p);

if($save->action=="salva" && $save->status==1 && ($_POST["dati"]["catalog_path"])){
	
	//$save->db=new sql_db($hostname.":".$dbport,$user,$pwd,$dbname);
	if($_POST["dati"]["connection_type"]==6 && defined('MAP_USER')){
		list($connStr,$schema)=connAdminInfofromPath($_POST["dati"]["catalog_path"]);
		$db2=pg_connect($connStr);
		if(!$db2)  die( "Impossibile connettersi al database $connStr");
			setDBPermission($db2,'public',MAP_USER,'SELECT','GRANT');
			setDBPermission($db2,'public',MAP_USER,'EXECUTE','GRANT');
			setDBPermission($db2,$schema,MAP_USER,'SELECT','GRANT');
			setDBPermission($db2,$schema,MAP_USER,'EXECUTE','GRANT');
		}
}

?>
