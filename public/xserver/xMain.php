<?php
require_once "../../config/config.php";
require_once (ROOT_PATH.'lib/functions.php');
/*function connAdminInfofromPath($sPath){
	$pathInfo = explode("/",$sPath);
	$datalayerSchema = $pathInfo[1];
	$connInfo=explode(" ",$pathInfo[0]);
		
	if(count($connInfo)==1)//abbiamo il nome del db
		$connString = "user=".DB_USER." password=".DB_PWD." dbname=".$connInfo[0]." host=".DB_HOST." port=".DB_PORT;
	else//abbiamo la stringa di connessione
		$connString = $pathInfo[0];
		
	return array($connString,$datalayerSchema);
}
*/

extract($_REQUEST);
$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id)  die( "Impossibile connettersi al database ".DB_NAME);

switch($_REQUEST["action"]){
	case "getChild":
		if(!$level){
			$response["action"]="setChildTree";
			$response["error"]=0;
			$response["level"]=Array("name"=>"project","label"=>"Progetti");
			$sql="SELECT DISTINCT project_name as name,project_description as title FROM ".DB_SCHEMA.".project";
			//echo $sql;
			$db->sql_query($sql);
			$ris=$db->sql_fetchrowset();
			for($i=0;$i<count($ris);$i++) $response["object"][]=Array("name"=>$ris[$i]["name"],"title"=>$ris[$i]["title"]);
		}
		//$sql="select e_form.name as form_name,e_form.save_data,config_file,title,tab_type,form_destination,e_form.parent_level,foo.parent_name,e_level.name as level,e_form.js as javascript,order_fld,coalesce(foo.depth,-1) from ".DB_SCHEMA.".form_level left join ".DB_SCHEMA.".e_form on (form_level.form=e_form.id) left join ".DB_SCHEMA.".e_level on (e_form.level_destination=e_level.id) left join ".DB_SCHEMA.".e_level as foo on (form_level.level=foo.id) where $filter_mode and foo.name='$lev' and visible=1 order by e_level.depth,order_fld;";
		break;
	case "init.QB":
		$sql="SELECT *,case when(qtrelation_id=0) then '__data__' else (select qtrelation_name from ".DB_SCHEMA.".qtrelation where qtrelation_id=X.qtrelation_id) end as table_name from ".DB_SCHEMA.".qtfield X WHERE qt_id=$qt order by table_name DESC,field_header";
		$db->sql_query($sql);
		$ris=$db->sql_fetchrowset();
		$response["object"]="fieldList";
		for($i=0;$i<count($ris);$i++) {
			
			$response["fieldList"][]=Array("qtfield_id"=>$ris[$i]["qtfield_id"],"header"=>$ris[$i]["field_header"],"table"=>$ris[$i]["table_name"]);
		}
		break;
	case "getFieldValues.QB":
		$sql="SELECT qtfield_name,case when(qtrelation_id=0) then (select data from ".DB_SCHEMA.".qt inner join ".DB_SCHEMA.".layer using(layer_id) WHERE qt_id=X.qt_id) else (select table_name from ".DB_SCHEMA.".qtrelation where qtrelation_id=X.qtrelation_id) end as table_name,case when(qtrelation_id=0) then (select catalog_path from ".DB_SCHEMA.".qt inner join ".DB_SCHEMA.".layer using(layer_id) inner join ".DB_SCHEMA.".catalog using(catalog_id) WHERE qt_id=X.qt_id) else (select catalog_path from ".DB_SCHEMA.".qtrelation inner join ".DB_SCHEMA.".catalog using(catalog_id) where qtrelation_id=X.qtrelation_id) end as table_name from ".DB_SCHEMA.".qtfield X WHERE qtfield_id=$qtfield;";
		
		$db->sql_query($sql);
		$ris=$db->sql_fetchrow();
		$response["object"]="valList";
		list($connStr,$schema)=connAdminInfofromPath($ris[2]);
		$sql="SELECT DISTINCT ".$ris[0]." as value FROM ".$schema.".".$ris[1];
		$db2=pg_connect($connStr);
		if(!$db2){
			$response["error"]="Impossibile connettersi al database";
			break;
		}
		$result=pg_query($db2,$sql);
		if($result){
			$ris=pg_fetch_all($result);
			for($i=0;$i<count($ris);$i++) $response["valList"][]=$ris[$i]["value"];
		}
		break;
	default: 
		$response["error"]="";
		break;
}

jsonString($response);
?>