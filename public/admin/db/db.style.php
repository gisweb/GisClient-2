<?php
$save=new saveData($_POST);
$p=$save->performAction($p);

if($save->status==1 && $save->action=="salva"){
	$class_id=$save->parent_flds["class"];
	$sql="SELECT legendtype_id as type FROM ".DB_SCHEMA.".class WHERE class_id=$class_id";
	$save->db->sql_query($sql);
	print_debug($sql,null,"save.class");
	$type=$save->db->sql_fetchfield("type");
	if($type==1){
		include_once ROOT_PATH."lib/gcSymbol.class.php";
		$smb=new Symbol("class");
		//$smb->_iconFromClass($class_id);
		$smb->table='class';
		$smb->filter="class.class_id=$class_id";
		$smb->createIcon();	
	}
	
}
?>