<?php
$config_file=$_POST["config_file"];
$parent=$_POST["parametri"][count($_POST["parametri"])-2];
$pkey=$_POST["parametri"][count($_POST["parametri"])-1];
$save=new saveData($_POST);

if($save->action=="genera mappa"){
	$save->action="salva";
	$upd_map=1;
}

$save->performAction($p);
$upd_sql = array();
if($save->status==1){
	if(!$_POST["dati"]["mapset_extent"]){
		$sql="SELECT project_extent as extent FROM ".DB_SCHEMA.".project WHERE project_name='$parent'";
		$ext=($save->db->sql_query($sql))?($save->db->sql_fetchfield('extent')):("");
		$upd_sql[]="mapset_extent='$ext'";
	}
	else	
		$ext=$_POST["dati"]["mapset_extent"];
	if(!trim($_POST["dati"]["refmap_extent"])){
		$upd_sql[]="refmap_extent='$ext'";
	}
	if(!trim($_POST["dati"]["test_extent"])){
		$upd_sql[]="test_extent='$ext'";
	}
	if(count($upd_sql)){
		$sql="UPDATE ".DB_SCHEMA.".mapset set ".implode(",",$upd_sql)." WHERE mapset_name='".$_POST["dati"]["mapset_name"]."'";
		if(!$save->db->sql_query($sql)){
			$p->setErrors(Array("generico"=>"Errore nell'Aggiornamento degli Extent della Mappa"));
		}
	}
}
?>
