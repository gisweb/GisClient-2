<?php

session_start();
error_reporting(E_ERROR);
//configurazione del sistema
require_once('../../../../config/config.php');
require_once (ROOT_PATH.'lib/functions.php');

header("Content-Type: text/html; Charset=".CHAR_SET);

//print('<pre>');
//print_r($_REQUEST);
//print_r($_SESSION);
//trovo la stringa di conessione e altre info dato il layerid
$db = new sql_db(DB_HOST.":5434",DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die( "Impossibile connettersi al database " . DB_NAME); 
$dbschema=DB_SCHEMA; 
$layerGroupId = $_REQUEST["grpid"];
$layerName = $_REQUEST["layer"];
$sql="select distinct mapset_filter,catalog_path,catalog_url,connection_type,data,data_geom,data_filter,data_unique,data_srid,layertype_ms from $dbschema.qt inner join $dbschema.layer using (layer_id) inner join $dbschema.e_layertype using (layertype_id) inner join $dbschema.catalog using (catalog_id) where layergroup_id=$layerGroupId and layer_name='$layerName';";

$db->sql_query ($sql);
$row=$db->sql_fetchrow();
//$db->sql_close();

$myMap = "MAPSET_".$_REQUEST["mapset"];

if($_REQUEST["objid"]){
	$resultIdList=$_REQUEST["objid"];
	$numObjects=1;
}
else{	
	$resultIdList=implode(",",$_SESSION[$myMap]["RESULT"]["ID_LIST"]);
	$numObjects=count($_SESSION[$myMap]["RESULT"]["ID_LIST"]);
}

$aConnInfo = connInfofromPath($row["catalog_path"]);
$connString = $aConnInfo[0];
$datalayerSchema = $aConnInfo[1];

$layerGeom = $row['data_geom'];
$layerTable = $datalayerSchema.".".$row['data'];
$layerUniqueField = $row['data_unique'];
$layerSrid = $row['data_srid'];
$layerFilter = $row['data_filter'];
$layerType = $row['layertype_ms'];

$aConn = array();
$v=explode(" ",$connString);
foreach($v as $opt){
	$v1=explode("=",$opt);
	$aConn[trim($v1[0])]=trim($v1[1]);
}

$db = new sql_db($aConn["host"].":5434",$aConn["user"],$aConn["password"],$aConn["dbname"], false);
if(!$db->db_connect_id) die( "Impossibile connettersi al database " . $aConn["dbname"]);




/*
session_start();
//require_once ("postgres.php");
//require_once("debug.php");
require_once('../../../../config/config.db.php');
require_once('../../../../config/config.php');
//print('<pre>');
//print_r($_REQUEST);
//print_r($_SESSION);
//trovo la stringa di conessione e altre info dato il layerid
$db = new sql_db(DB_HOST,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die( "Impossibile connettersi al database p" . DB_NAME);
$dbschema=DB_SCHEMA; 
$infoLayer=explode(":",$_REQUEST["layer"]);
$layerId=$infoLayer[0];
$sql="select layer_name,layergroup_id,connection.username,connection.pwd,connection.dbname,connection.hostname,connection.dbport,catalog.dbschema,data,data_geom,data_filter,data_unique,data_srid,ms_layertype from $dbschema.layer inner join $dbschema.e_layertype using (layertype_id) inner join $dbschema.catalog using (catalog_id) inner join $dbschema.connection using (connection_id) where layer.layer_id=$layerId;";
//$sql="select distinct mapset_filter,catalog_path,catalog_url,connection_type,data,data_geom,data_filter,data_unique,data_srid,layertype_ms from $dbschema.qt inner join $dbschema.layer using (layer_id) inner join $dbschema.e_layertype using (layertype_id) inner join $dbschema.catalog using (catalog_id) where layergroup_id=$layerGroupId and layer_name='$layerName';";

echo"$sql";
$db->sql_query ($sql); 
$row=$db->sql_fetchrow();
//$db->sql_close();

$myMap = "MAPSET_".$_REQUEST["mapset"];

if($_REQUEST["objid"]){
	$resultIdList=$_REQUEST["objid"];
	$numObjects=1;
}
else{	
	$resultIdList=implode(",",$_SESSION[$myMap]["RESULT"]["ID_LIST"]);
	$numObjects=count($_SESSION[$myMap]["RESULT"]["ID_LIST"]);
}

$layerName=$row['layer_name'];
$layerGroupId=$row['layergroup_id'];
$layerGeom = $row['data_geom'];
$layerTable = $row['dbschema'].".".$row['data'];
$layerUniqueField = $row['data_unique'];
$layerSrid = $row['data_srid'];
$layerFilter = $row['data_filter'];
$layerType = $row['ms_layertype'];*/

?>
