<?php
$save=new saveData($_POST);
$p=$save->performAction($p);

if($save->action=="salva" && $save->status==1){
	require_once (ROOT_PATH."lib/functions.php");
	$catalog_id=$_POST["dati"]["catalog_id"];
	$tableName=$_POST["dati"]["data"];
	$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=$catalog_id";
	if (!$save->db->sql_query($sql))
		print_debug($sql,null,"elenco");
	$ris=$save->db->sql_fetchrow();
	
	
	if($ris["connection_type"]==6 && defined('MAP_USER')){
		list($connStr,$schema)=connAdminInfofromPath($ris["catalog_path"]);
		$newdb=pg_connect($connStr);
		setDBPermission($newdb,$schema,MAP_USER,'SELECT','GRANT',$tableName);
	}

}
?>