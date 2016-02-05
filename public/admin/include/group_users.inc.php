<?php
require_once "../../config/config.php";

	$group=isset($this->parametri["groups"])?$this->parametri["groups"]:array();
	$usr=new userApps(null);
	$ris=$usr->getUsersList($group,$this->mode);
	if (is_array($ris) && count($ris)>0){
		foreach($ris as $val){
			extract($val);
			if($this->mode!=0 || $presente==1)
				$data[]=Array("username"=>$username,"groupname"=>$groupname,"presente"=>$presente);
		}
	}
	else{
		$data=Array();
		$msg="Nessun Utente definito";
	}
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'username');\">\n";
	if($usr->editGroup==1) $button="modifica";
?>
