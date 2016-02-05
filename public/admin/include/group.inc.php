<?php
require_once "../../config/config.php";
	$group=(isset($this->parametri["groups"]))?$this->parametri["groups"]:array();

	$usr=new userApps(null);
	$ris=$usr->getGroup($group);
	if (is_array($ris) && count($ris)>0){
		foreach($ris as $val){
			extract($val);
			$data[]=Array("groupname"=>$groupname,"description"=>$description);
		}

	}
	else{
		$data=Array();
		$msg="Nessun Gruppo definito";
	}

	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'username');\">\n";
	if($usr->editUser==1) $button=($this->currentMode=='view')?("modifica"):("nuovo");
?>
