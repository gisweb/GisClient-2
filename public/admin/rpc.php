<?php

	require_once "../../config/config.php";
	include_once ROOT_PATH."lib/functions.php";
	$azione=$_REQUEST["azione"];
	$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	switch($azione){
		case "getExportObject":
			$level=$_REQUEST["level"];
			$project=$_REQUEST["project"];
			switch ($level){
				case 2:
					$sql="SELECT project_name as id,project_name as name FROM ".DB_SCHEMA.".project WHERE project_name='$project' ORDER BY project_name";
					break;
				case 5:
					$sql="SELECT theme_id as id,theme_name as name FROM ".DB_SCHEMA.".theme LEFT JOIN ".DB_SCHEMA.".project using (project_name) WHERE project_name='$project' ORDER BY theme_name";
					break;
				case 10:
					$sql="SELECT layergroup_id as id,layergroup_name as name FROM ".DB_SCHEMA.".layergroup LEFT JOIN ".DB_SCHEMA.".theme using (theme_id) LEFT JOIN ".DB_SCHEMA.".project using (project_name) WHERE project_name='$project' ORDER BY layergroup_name";
					break;
				case 11:
					break;
				case 12:
					break;
				case 14:
					break;
			}
			if(!$db->sql_query($sql))
				echo "{error:'Impossibile eseguire la query'}";
			else{
				$ris=$db->sql_fetchrowset();
				$obj="";
				$opt[]="['-1','Seleziona un oggetto']";
				for($i=0;$i<count($ris);$i++){
					$o=$ris[$i];
					$val=parse_code($o["name"]);
					//$val=$o["name"];
					$opt[]="['$o[id]','$val']";
				}
				$res="{name:'obj_id', val:[".implode(",",$opt)."]}";
			}
			break;
		case "request":
			$fk=$_REQUEST["parent_level"]."_id";
			$table=$_REQUEST["level"];
			$id=$_REQUEST["id"];
			switch($table){
				case "layer":
					$fld=$table."_id as id,".$table."_name as title";
					break;
				default:
					$fld=$table."_id as id,".$table."_title as title";
					break;
			}
			$sql="SELECT $fld FROM ".DB_SCHEMA.".$table WHERE $fk=$id order by 2;";
			if(!$db->sql_query($sql))
				echo "{error:'Impossibile eseguire la query $sql'}";
			else{
				$ris=$db->sql_fetchrowset();
				foreach($ris as $v){
					$opt[]="{id:$v[id],name:'".addslashes($v['title'])."'}";
				}
				$res="[".@implode(",",$opt)."],'$table'";
			}
			break;
		default:
			break;
	}
	header("Content-Type: text/plain; Charset=".CHAR_SET);
	echo $res;
?>