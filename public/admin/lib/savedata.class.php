<?php

include_once ADMIN_PATH."lib/export.php";


Class saveData{
	var $data=Array();			//Array dei dati da salvare
	var $fields=Array();		//Array dei campi e definizione dei loro tipi
	var $fields_obbl;			//Array dei campi obbligatori	Da Eliminare e gestire unicamente da DATABASE con Vincolo NOT NULL
	var $parent_flds=Array();
	var $oldId;
	var $newId;
	var $table;					//Nome della tabella
	var $schema;				//Nome della schema
	var $pkeys=Array();			//Array delle chiavi primarie e loro valori
	var $errors=Array();		//Array degli errori trovati
	var $mode;					//Modalita di salvataggio
	var $action;				//Tipo di azione da eseguire
	var $array_action=Array("salva","aggiungi","elimina","cancella","copia","sposta");	//Elenco delle azioni possibili
	var $status;
	var $delete = 0;
	var $conf_dir;				//
	public $error;

	var $db;
	
	function __construct($arr_dati){
		
		$langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$mylang=(!empty($_REQUEST["language"]))?$_REQUEST["language"]:substr($langs[0],0,2);
		$rel_dir="config/tab/".$_SESSION["AUTHOR_LANGUAGE"]."/";
		if(!is_dir(ROOT_PATH.$rel_dir)) $rel_dir="config/tab/it/";
		if(defined('TAB_DIR')) $rel_dir="config/tab/".TAB_DIR."/";
		
		$this->conf_dir=ROOT_PATH.$rel_dir;

		$pk=_getPKeys();
		$this->primary_keys=$pk["pkey"];
		//ESTRAZIONE DEI DATI DAL ARR_DATI
		//ACQUISIZIONE DEI DATI SULLA MODE E SULL?AZIONE DA ESEGUIRE
		if(!$arr_dati["mode"] && !$arr_dati["modo"]){
			$this->errors["generic"][]="<p>Manca la modalità, non è possibile continuare</p>";
			$this->status=-1;
			return;
		}
		else
			$this->mode=($arr_dati["modo"])?(strtolower($arr_dati["modo"])):(strtolower($arr_dati["mode"]));
		
		if(!$arr_dati["azione"]){
			$this->errors["generic"]="<p>Manca l'azione da eseguire, non è possibile continuare</p>";
			$this->status=-1;
			return;
		}
		else{
			if(isset($arr_dati["save_type"]) && $arr_dati["save_type"]=="multiple"){
				$this->mode="multiple-save";
			}
			$this->action=strtolower($arr_dati["azione"]);	
		}
		
		//Acquisizione dei dati dal file di configurazione
		$config_file=$arr_dati["config_file"];
		if((!$config_file || !is_file($this->conf_dir.$config_file)) && $this->action!="annulla"){
			$this->errors["generic"][]="<p>Manca il file di definizione del form, non è possibile continuare</p>";
			$this->status=-1;
			return;
		}
		else{
			$this->_getConfig($this->conf_dir.$config_file,$arr_dati["pkey"],(isset($arr_dati["pkey_value"]))?$arr_dati["pkey_value"]:null);
			$this->newId=(isset($arr_dati["dataction"]))?$arr_dati["dataction"]["new"]:null;
			$this->oldId=(isset($arr_dati["dataction"]))?$arr_dati["dataction"]["old"]:null;
			$this->parent_flds=((count($arr_dati["parametri"])-2)<0)?(Array()):($arr_dati["parametri"][count($arr_dati["parametri"])-2]);
			if (!isset($arr_dati["dati"])) $arr_dati["dati"] = array();
			if($this->mode=="multiple-save"){
				foreach($this->fields as $arr){
					$fld=$arr["field"];
					if($arr_dati["dati"]){
						$cont=0;
						foreach($arr_dati["dati"] as $val){
							$this->data[$cont][$fld]=(isset($val[$fld]))?$val[$fld]:null;
							$cont++;
						}
					}
				}
			}
			else
				$this->data=$arr_dati["dati"];
			
			//CONNESSIONE AL DATABASE
			if(!$this->db){
				$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
				if(!$this->db->db_connect_id){
					$this->status=-1;
					$this->errors["generic"]="<p>Impossibile connettersi al database</p>";
				}
				else
					$this->status=1;
			}
		}
		
	}
	function getPKeys($level,$sk){
		return $this->primary_keys[$level];
	}
	function performAction($p=null){
		if ($this->status==-1){
			$p->setErrors($this->errors);
			$p->livello=$p->get_livello();	
			$p->get_conf();
			return $p;
		}
		
		switch($this->action){
			case "importa":
				return $p;
			case "elimina":
				array_pop($p->parametri);
				$p->mode=$p->arr_mode["list"];
				
			case "cancella":
				foreach($this->pkeys as $key=>$value) $flt[]="$key = '$value'";
				$filter=implode(" AND ",$flt);
				$sql="delete from $this->schema.$this->table where $filter;";
				print_debug($sql,null,"save.class");
				$this->db->sql_query ($sql);
				if($this->action=="cancella") $p->mode=$p->arr_mode["edit"];
				$this->delete=1;
				break;
			case "sposta":
				foreach($this->pkeys as $key=>$value) $flt[]="$key = $value";
				$filter=implode(" AND ",$flt);
				$parent=array_keys($this->parent_flds);
                // MODIFICA X SETTARE NUOVO NOME
                if ($this->data[$this->table."_name"])
                    $sql="UPDATE $this->schema.$this->table SET ".$parent[0]."_id = ".$this->newId.",".$this->table."_name = '".$this->data[$this->table."_name"]."'  WHERE $filter;";
				else
                    $sql="UPDATE $this->schema.$this->table SET ".$parent[0]."_id = ".$this->newId." WHERE $filter;";
                $this->db->sql_query($sql);
				$p->parametri[$parent[0]]=$this->newId;
				$p->get_conf();
				return $p;
				break;
			case "copia":
				//print_array($this);
				$idcopy=Array($this->newId);
				foreach($p->array_levels as $key=>$value){
					if($p->livello==$value["name"]) $idlevel=$key;
				}
				$parent=array_keys($this->parent_flds);
				$newName=$this->data[$this->table."_name"];
				if($this->newId && $this->oldId && $this->newId!=$this->oldId)
					$tree=$this->_copy_object($p->array_levels,$idlevel,$idcopy,Array("key"=>$parent[0],"value"=>$p->parametri[$parent[0]]),$idlevel,1,$newName);
				else{
					
					$tree=$this->_copy_object($p->array_levels,$idlevel,$idcopy,null,null,0,$newName);
				}
					
				array_pop($p->parametri);
				break;
			case "aggiungi":
				$this->mode="new";
				$Dati=$this->_validaDati();
				$tmp=array_keys($this->pkeys);
				$pkey=$tmp[0];
				$sql="select ".DB_SCHEMA.".new_pkey('$this->schema','$this->table','$pkey',1);";
				$this->db->sql_query ($sql);
				print_debug($sql,null,"save.class");
				$row = $this->db->sql_fetchrow();
				$newid=$row[0];
				$Dati[$pkey]=$newid;
				//INSERISCO I VALORI DEI GENITORI DELLA TABELLA
				if($this->parent_flds){
					foreach($this->parent_flds as $key=>$value){
						$Dati[$key."_id"]=$value;
					}
				}
				//INSERISCO I VALORI DELLA TABELLA
				foreach ($Dati as $campo=>$valore){
					if (strlen($valore)>0) {
						$sqlinsertfield[]="$campo";
						$sqlinsertvalues[]="$valore";
					}
				}
				$sql="insert into $this->schema.$this->table (".@implode(",",$sqlinsertfield).") values (".@implode(",",$sqlinsertvalues).");";
				$result=$this->db->sql_query ($sql);
				print_debug($sql,null,"save.class");
				if(!$result){
					foreach ($this->db->error_message as $val){
						$err=$this->_getDbError($val["code"],$val["text"],$sql);
						if($err)
							foreach($err as $v){
								list($fld,$err_type)=$v;
								if ($fld=="generic")
									$this->errors[$fld][]=$err_type;
								else
									$this->errors[$fld]=$err_type;
							}
					}
					$this->status=-1;
					$p->errors=$this->errors;
				}
				$p->mode=$p->arr_mode["edit"];
				break;
				
			case "salva":
				
				$Dati=$this->_validaDati();
				if ($this->errors){
					print_debug($this->errors,null,'errori');
					$this->db->sql_close();
					$p->errors=$this->errors;
					$p->mode=$p->arr_mode[$this->mode];
					$this->status=-1;
					$p->livello=$p->get_livello();	
					$p->get_conf();
					return $p;
				}
				if (isset($_SESSION["ADD_NEW"]) && $_SESSION["ADD_NEW"]){
					echo "Il record è già stato inserito ".$_SESSION["ADD_NEW"];
					$this->status=-1;
					$p->livello=$p->get_livello();	
					$p->get_conf();
					return $p;
				}
				switch ($this->mode){
					case "multiple-save":
						$Dati=$this->_validaMultipleDati();
						if ($this->errors){
							print_debug($this->errors,null,'errori');
							$this->db->sql_close();
							$p->errors=$this->errors;
							$p->mode=$p->arr_mode[$this->mode];
							$this->status=-1;
							$p->livello=$p->get_livello();	
							$p->get_conf();
							return $p;
						}
						
						$tmp=array_keys($this->pkeys);
						
						$pkey=$tmp[0];

						if($this->parent_flds){
							
							foreach($this->parent_flds as $key=>$value){
								$parentKeys=$this->getPKeys($key,DB_SCHEMA);
								
								foreach($parentKeys as $pk)
									for($i=0;$i<count($Dati);$i++) $Dati[$i][$pk]="'$value'";
								$arr_delete_filter[]="$pk = '$value'";
							}
							if(count($arr_delete_filter))
								$delete_filter="WHERE ".@implode(" AND ",$arr_delete_filter);
						}
						$sql="DELETE FROM $this->schema.$this->table $delete_filter;";
						$result = $this->db->sql_query ($sql);
						print_debug($sql,null,"save.class");
						for($i=0;$i<count($this->data);$i++){
							if ($pkey) {
								$sql="select ".DB_SCHEMA.".new_pkey('$this->schema','$this->table','$pkey',1);";
								$result = $this->db->sql_query ($sql);
								$row = $this->db->sql_fetchrow();
								$newid=$row[0];
								if ($newid) $Dati[$i][$pkey]=$newid;
							}
							$sqlinsertfield=Array();
							$sqlinsertvalues=Array();

							foreach ($Dati[$i] as $campo=>$valore){
								if ($campo && strlen($valore)>0 ) {
									$sqlinsertfield[]="$campo";
									$sqlinsertvalues[]="$valore";
								}
								
							}
							
							$sql="insert into $this->schema.$this->table (".@implode(",",$sqlinsertfield).") values (".@implode(",",$sqlinsertvalues).");";
							$result = $this->db->sql_query ($sql);
							print_debug($sql,null,"save.class");
						}
						array_pop($p->parametri);
						$p->livello=$p->get_livello();	
						$p->get_conf();
						$this->status=1;
						return $p;
						break;
					case "new":
						//CERCO IL VALORE DELLA NUOVA CHIAVE PRIMARIA   ---- AL MOMENTO FUNZIONA SOLO CON UNA CHIAVE PRIMARIA 
						$tmp=array_keys($this->pkeys);
						
						$pkey=$tmp[0];
						switch($this->table){	// Starting point della tabella
							default:
								$start=1;
								break;
						}

                        // ricerco le chiavi della tabella
						if(preg_match("|(.+)_id|Ui",$pkey) && $pkey != 'language_id'){ // strozzo Roberto (dice Marco)
							$sql="select ".DB_SCHEMA.".new_pkey('$this->schema','$this->table','$pkey',$start);";
							$this->db->sql_query ($sql);
							$row = $this->db->sql_fetchrow();
							$newid=$row[0];
							if($newid) $Dati[$pkey]=$newid;
						}
						else if (isset($this->data[$pkey])){
							
							$newid=$this->data[$pkey];
						}
						//INSERISCO I VALORI DEI GENITORI DELLA TABELLA
						
						if($this->parent_flds){
							foreach($this->parent_flds as $key=>$value){
								$pkeys=$this->getPKeys($key,$this->schema);
								for($i=0;$i<count($pkeys);$i++)
										$Dati[$pkeys[$i]]="'$value'";
							}
						}
						//INSERISCO I VALORI DELLA TABELLA
						
						foreach ($Dati as $campo=>$valore){
							
							if ($campo && strlen($valore)>0) {
								$sqlinsertfield[]="$campo";
								$sqlinsertvalues[]="$valore";
							}
						}
						$sql="insert into $this->schema.$this->table (".@implode(",",$sqlinsertfield).") values (".@implode(",",$sqlinsertvalues).");";
						break;
					case "edit":
						
						foreach($this->pkeys as $key=>$value) $flt[]=($value)?("$key = '".addslashes($value)."'"):("$key = '".$this->data[$key]."'");
						$filter=implode(" AND ",$flt);
						foreach ($Dati as $campo=>$valore){
							if($campo){
								if (strlen($valore)>0){
									$sqlupdate[]="$campo = $valore";
									if(in_array($campo,array_keys($this->pkeys))) $newKey=$valore;
								}
								else
									$sqlupdate[]="$campo = NULL";
							}
						}
						$sql="update $this->schema.$this->table set ".@implode(", ",$sqlupdate)." where $filter;";
						break;
				}
				if($this->status==1){
					$result = $this->db->sql_query ($sql);
				}
				print_debug($sql,null,"save.class");
				if (!$result){
										
					foreach ($this->db->error_message as $val){
						$err=$this->_getDbError($val["code"],$val["text"],$sql);
						if($err)
							foreach($err as $v){
								list($fld,$err_type)=$v;
								if ($fld=="generic")
									$this->errors[$fld][]=$err_type;
								else
									$this->errors[$fld]=$err_type;
							}
					}
					$p->errors=$this->errors;
					$p->mode=$p->arr_mode[$this->mode];
					$this->status=-1;
					$p->livello=$p->get_livello();	
					$p->get_conf();
					return $p;
				}
				else{
					if(isset($newid) && $this->mode=="new"){
						
						$_SESSION["ADD_NEW"]=$newid;
						$p->parametri[$p->get_livello()]=$newid;	
					}
					if($this->mode=="edit"){

						//$p->parametri[$p->get_livello()]=(preg_match("|^'(.+)'$|",$newKey,$match))?($match[1]):($newKey);
					}
					if ($p->array_levels[$p->get_idLivello()]["leaf"] && $this->delete){
						array_pop($p->parametri);

					}
				}
				break;
			default:
				if(in_array($this->mode,Array("new","multiple-save")) || $this->action=="chiudi"){
					array_pop($p->parametri);
				}
				break;
		}
		$p->livello=$p->get_livello();
		$p->get_conf();
		$this->status=1;
		return $p;
	}
	
	private function _copy_object($arr,$lev,$arr_id=Array(),$parent_fld=Array(),$start_lev=0,$modal=0,$newname=""){
		$struct["name"]=$arr[$lev]["name"];
		$el=$arr[$lev];
		if(!$arr[$lev]["leaf"]){
			$sql="SELECT id,name,leaf FROM ".DB_SCHEMA.".e_level WHERE export=1 AND parent_id=$lev;";
			print_debug($sql,null,"save.class");
			if($this->db->sql_query($sql))
				print_debug($sql);
			$child=$this->db->sql_fetchrowset();
		}
		else{
			$child=Array();
		}	
		if(count($arr_id)){
			$sql="SELECT column_name FROM information_schema.columns WHERE table_name='".$struct["name"]."' and table_schema='".DB_SCHEMA."' AND NOT column_name IN (SELECT Y.column_name FROM (select constraint_name FROM information_schema.table_constraints WHERE constraint_type='PRIMARY KEY' AND constraint_schema='".DB_SCHEMA."' and table_name='".$struct["name"]."')  as X left join (SELECT constraint_name,column_name FROM information_schema.constraint_column_usage WHERE constraint_schema='".DB_SCHEMA."' and table_name='".$struct["name"]."') as Y using(constraint_name))";
			print_debug($sql,null,"save.copy");
			$this->db->sql_query($sql);
			$tmp=$this->db->sql_fetchrowset();
			
			foreach($tmp as $v) {
				$flds[]=$v["column_name"];
				if($parent_fld["value"] && ($v["column_name"]==$arr[$arr[$lev]["parent"]]["name"]."_id" || $v["column_name"]==$arr[$arr[$lev]["parent"]]["name"]."_name")){
					$value[]=$parent_fld["value"];
				}
				elseif(preg_match("|(.*)name$|i",$v["column_name"])){
					$value[]=($newname!="" && $struct["name"]."_name"==$v["column_name"])?("'$newname'"):(($start_lev!=$lev)?($v["column_name"]):("'Copia di ' ||".$v["column_name"]));
				}
				else
					$value[]=$v["column_name"];
			}
			
				
			$list_flds=@implode(",",$flds);	
			$list_value=@implode(",",$value);	
		}
		
		// INSERISCO GLI ELEMENTI DI QUESTO LIVELLO
		if ($arr_id)
		foreach($arr_id as $id){
			$sql="select ".DB_SCHEMA.".new_pkey('".DB_SCHEMA."','".$struct["name"]."','".$struct["name"]."_id') as id;";
			$this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow();
			$idx=$row[0];
			$parent[$lev][$id]=Array("key"=>$struct["name"],"value"=>$idx);
			
			$sql="INSERT INTO ".DB_SCHEMA.".".$struct["name"]."(".$struct["name"]."_id,$list_flds) SELECT $idx,$list_value FROM ".DB_SCHEMA.".".$struct["name"]." WHERE ".$struct["name"]."_id=$id;";
			print_debug($sql,null,"save.copy");
			if (!$this->db->sql_query($sql)){
				print_debug($this->db->error_message,null,"save.copy.debug");
			}
		}
		foreach($child as $ch){
			$tb=$ch["name"];
			$fld=$tb."_id";
			foreach($arr_id as $id){
				$sql="SELECT DISTINCT $fld as id FROM ".DB_SCHEMA.".$tb WHERE ".$struct["name"]."_id=$id";
				print_debug($sql,null,"save.class");
				$this->db->sql_query($sql);
				$newArrId=Array();
				$newArrId=$this->db->sql_fetchlist('id');
				if (count($newArrId)){
					$struct["child"][$lev]=$this->_copy_object($arr,$ch["id"],$newArrId,$parent[$lev][$id],$start_lev,$modal);
				}
				else{
					$struct["child"][$lev]=Array();
				}
			}
		}
		return $struct;
	}
	
	private function _export_object($arr,$lev,$arr_id=Array()){
		$struct["name"]=$arr[$lev]["name"];
		$el=$arr[$lev];
		if(!$arr[$lev]["leaf"]){
			$sql="SELECT id,name,leaf FROM ".DB_SCHEMA.".e_level WHERE parent_id=$lev;";
			print_debug($sql,null,"save.class.debug");
			if($this->db->sql_query($sql))
				print_debug($sql);
			$child=$this->db->sql_fetchrowset();
		}
		else{
			$child=Array();
		}
		if(count($arr_id)){
			$sql="SELECT column_name FROM information_schema.columns WHERE table_name='".$struct["name"]."' and table_schema='".DB_SCHEMA."' AND NOT column_name IN (SELECT Y.column_name FROM (select constraint_name FROM information_schema.table_constraints WHERE constraint_type='PRIMARY KEY' AND constraint_schema='".DB_SCHEMA."' and table_name='".$struct["name"]."')  as X left join (SELECT constraint_name,column_name FROM information_schema.constraint_column_usage WHERE constraint_schema='".DB_SCHEMA."' and table_name='".$struct["name"]."') as Y using(constraint_name))";
			print_debug($sql,null,"save.class.debug");
			$this->db->sql_query($sql);
			$tmp=$this->db->sql_fetchrowset();
			foreach($tmp as $v) {
				$flds[]=$v["column_name"];
				
				if($parent_fld["value"] && $v["column_name"]==$arr[$arr[$lev]["parent"]]["name"]."_id"){
					$value[]="(select ".$arr[$arr[$lev]["parent"]]["name"]."_id from ".DB_SCHEMA.".".$arr[$arr[$lev]["parent"]]["name"]." where ".$arr[$arr[$lev]["parent"]]["name"]."_name='')";
				}
				else
					$value[]=$v["column_name"];
			}
			
				
			$list_flds=@implode(",",$flds);	
			$list_value=@implode(",",$value);	
		}
		
		// INSERISCO GLI ELEMENTI DI QUESTO LIVELLO
		foreach($arr_id as $id){
			$idx="(select ".DB_SCHEMA.".new_pkey('".DB_SCHEMA."','".$struct["name"]."','".$struct["name"]."_id'))";
			$parent[$lev][$id]=Array("key"=>$struct["name"],"value"=>$idx);

			$sql="INSERT INTO ".DB_SCHEMA.".".$struct["name"]."(".$struct["name"]."_id,$list_flds) SELECT $idx,$list_value FROM ".DB_SCHEMA.".".$struct["name"]." WHERE ".$struct["name"]."_id=$id;\n";
			print_debug($sql,null,"save.class.debug");
		}
		foreach($child as $ch){
			$tb=$ch["name"];
			$fld=$tb."_id";
			foreach($arr_id as $id){
				$sql="SELECT DISTINCT $fld as id FROM ".DB_SCHEMA.".$tb WHERE ".$struct["name"]."_id=$id";
				print_debug($sql,null,"save.class.debug");
				$this->db->sql_query($sql);
				$newArrId=Array();
				$newArrId=$this->db->sql_fetchlist('id');
				if (count($newArrId)){
					$struct["child"][$lev]=$this->_copy_object($arr,$ch["id"],$newArrId,$parent[$lev][$id],$modal);
				}
				else{
					$struct["child"][$lev]=Array();
				}
			}
		}
		return $struct;
	}
	
	private function _validaMultipleDati(){
		$dati = array();
		for($i=0;$i<count($this->data);$i++){
			$OK_Save=1;
			
			$dati[$i]=$this->_validaDati($i);
			$error=$this->error;
			$this->error=Array();
			$this->error[$i]=$error;
		}
		return $dati;
	}
	private function _validaDati($curr_rec=null){
		$array_data = array();
		//dall'array tratto dal file di configurazione crea l'array campi=>valori validati per il db
		$OK_Save=1;
		$sql="SELECT DISTINCT column_name as fields FROM information_schema.columns WHERE table_name='".$this->table."' AND table_schema='".$this->schema."'";
		if(!$this->db->sql_query($sql)){
			print_debug("Errore\n".$sql,null,"save.class.debug");
			return ;
		}
		else
			$flds=$this->db->sql_fetchlist('fields');
		foreach($this->fields as $def){
			$campo=$def["field"];
			$tipo=$def["type"];
                        if ($curr_rec===Null) {
                            if (!array_key_exists($campo, $this->data)) continue;
                            else $val = trim($this->data[$campo]);
                        } else {
                            if (!array_key_exists($campo, $this->data[$curr_rec])) continue;
                            else $val = trim($this->data[$curr_rec][$campo]);
                        }
			$present=(!in_array($campo,$flds))?(0):(1);
			//echo "Sto Validando $campo : $tipo con valore ".$val."<br>";
			switch ($tipo) {
				case "idriga":	
					$val=''; //inutile metterlo nella query
					break;
				case "pword":
				case "text":	
				
					if (strlen($val)>0){
						if (get_magic_quotes_runtime() or get_magic_quotes_gpc()) {
							$val="'".str_replace("'","'",$val)."'";
						}
						else{
							$val=$this->db->quote($val);;
						}
						
					}
					elseif (strlen($val)===0) $val="";
					break;
				case "textarea":
					if (strlen($val)>0){
						if (get_magic_quotes_runtime() or get_magic_quotes_gpc()) {
							$val="'".str_replace("'","'",$val)."'";
						}
						else{
							$val=$this->db->quote($val);;
						}
						
					}
					elseif (strlen($val)===0) $val="";
					break;
				case "data":
					$l=strlen($val);
					//primo controllo se i caratteri inseriti sono del tipo corretto
					if (strlen($val)>0 and !ereg ("([0123456789/.-]{".$l."})", $val)){
						$OK_Save=0;
						$this->errors[$campo]="Formato di data non valido $val";
					}
					else{
						list($giorno,$mese,$anno) = split('[/.-]', $val);
						//Da Verificare..... il 30 Febbraio 2005 lo prende se scritto come anno-mese-giorno con anno a 2 cifre!!!!! Errore
						if (strlen($val)>0 and (checkdate((int) $mese,(int) $giorno,(int) $anno))){
							$val="'".$giorno."/".$mese."/".$anno."'";
						}
						elseif (strlen($val)>0 and strlen($giorno)>3 and (checkdate((int) $mese,(int) $anno,(int) $giorno))) {
							$val="'".$anno."/".$mese."/".$giorno."'";
						}
						elseif (strlen($val)>0 and strlen($giorno)<=2 and (checkdate((int) $mese,(int) $anno,(int) $giorno))) {
							$OK_Save=0;
							$this->errors[$campo]="Data ambigua $val";
						}
						elseif (strlen($val)>0) {
							$OK_Save=0;
							$this->errors[$campo]="Data non valida $val";
						}
						elseif (strlen($val)===0) $val="NULL";
					}
					break;
				case "select":
					if ($val) $val="'".addslashes($val)."'";
					break;
					
				case "selectdb":
				case "selectRPC":
					if ($val==-1 && count($this->fields_obbl) && in_array($campo,$this->fields_obbl)) {
					//	$OK_Save=0;
					//	$this->errors[$campo]="Campo Obbligatorio";
					}
					elseif(!is_numeric($val)) $val="'".addslashes($val)."'";
				case "elenco":
					break;
				case "valuta":
					$val=str_replace("€","",$val);
					$val=str_replace(".","",$val);
					$val=str_replace(",",".",$val);
					if (strlen($val) and !is_numeric($val)){
						$OK_Save=0;
						$this->errors[$campo]="Dato non numerico";
					}
					elseif (strlen($val)==0) $val="";
					break;	
				case "ora":
					$val=str_replace(",",".",$val);
					$val=str_replace(":",".",$val);
					if (strlen($val) and !is_numeric($val)){
						$OK_Save=0;
						$this->errors[$campo]="Dato orario non valido";
					}
					
					break;	
				case "superficie":
					$val=str_replace("mq","",$val);
					$val=(double)str_replace(",",".",$val);
					if (strlen($val) and !is_float($val)){
						$OK_Save=0;
						$this->errors[$campo]="Dato non numerico";
					}
					break;
				case "intero":
				case "numero":
					$val=str_replace(",",".",$val);
					if (strlen($val) and !is_numeric($val)){
						$OK_Save=0;
						$this->errors[$campo]="Dato non numerico";
					}
					//else if (strlen($val)==0) $val=0.00;
					break;	
					
				case "bool":
					($val="SI")?($val="'t'"):($val="'f'");
					break;
					
				case "checkbox":
				case "semaforo":
					if ($val=='on')
						$val=1;
					else
						$val=0;
					break;	
				case "radio":
					$arvalue=$_POST[$campo];
					//print_r($arvalue);
					break;
				case "color":
					if ($val && !(preg_match("|[0-9]{1,3} [0-9]{1,3} [0-9]{1,3}|",$val) || preg_match("|^([\[]{1})([A-z0-9]+)([\]]{1})$|",$val))){
						$this->errors[$campo]="Valore non RGB";
						$OK_Save=0;
					}
					elseif($val)
						$val="'$val'";
					else
						$val="";
					break;
				case "chiave_esterna":
					$val=($campo=="symbol_ttf_name")?("'$val'"):($val);
					break;
				case "check1":
					
					$val=(isset($this->data[$curr_rec][$campo]))?($this->data[$curr_rec][$campo]):(0);
					break;
					
			}
			if(($tipo!="button") and ($tipo!="submit") && $present)
				$array_data[$campo]=$val;
			
		}
		return $array_data;
	}
	private function _getDbError($code,$mess,$query){
		$regexp=Array(
			"23502"=> "/null value in column \"(.+)\" violates not-null constraint/i",
			"23503"=>"/on table \"(.+)\" violates foreign key constraint \"(.+)\"/i",
//			"23503"=>"/\w+ or \w+ on \"(.+)\" violates foreign key constraint \"(.+)\" on \"(.+)\"/i",
			"23505"=>"/duplicate key violates unique constraint \"(.+)\"/i",
			"23514"=>"/new row for relation \"(.+)\" violates check constraint \"(.+)\"/i",
			"42601"=>"",
			"42703"=>"/ column \"(.+)\" of relation \"(.+)\" does not exist/i",
			"P0001"=>"/ERROR: (.+) @ (.+)/i"
		);
		
		$fld = '_unknown_';
		if (array_key_exists($code, $regexp)){
			$rv = preg_match($regexp[$code],$mess,$ris);
			if ($rv) {
				$fld=trim($ris[1]);
			}
		}
		
		switch ($code){
			case "23502":
				$res[]=Array($fld,"Campo Obbligatorio");
				break;
			case "23503":
				$fld=trim($ris[2]);
				$sql="SELECT column_name FROM information_schema.constraint_column_usage WHERE constraint_name='$fld' and constraint_schema='$this->schema'";
				$this->db->sql_query($sql);
				print_debug($sql,null,"save");
				$ris=$this->db->sql_fetchlist("column_name");
				for($i=0;$i<count($ris);$i++) $res[]=Array($ris[$i],"Chiave Esterna");
				break;
			case "23505":
				$sql="SELECT column_name FROM information_schema.constraint_column_usage WHERE constraint_name='$fld' and table_name='$this->table' and constraint_schema='$this->schema'";
				$this->db->sql_query($sql);
				print_debug($sql,null,"save");
				$ris=$this->db->sql_fetchlist("column_name");
				for($i=0;$i<count($ris);$i++){
					switch($ris[$i]){
						case "user_id":
							if($table=="user_project") $ris[$i]="usergroup_id";
							break;
						default:
							break;
					}
					$res[]=Array($ris[$i],"Campo Duplicato");
					
				}
				break;
			case "23514":
				$fld=trim($ris[2]);
				$sql="SELECT column_name FROM information_schema.constraint_column_usage WHERE constraint_name='$fld' and table_name='$this->table' and constraint_schema='$this->schema'";
				$this->db->sql_query($sql);
				print_debug($sql,null,"save");
				$ris=$this->db->sql_fetchlist("column_name");
				for($i=0;$i<count($ris);$i++) $res[]=Array($ris[$i],"Valore non Ammesso");
				break;
			case "42601":
				$res[]=Array("generic","Errore di Sintassi nella Query<br>$query");
				break;
			case "P0001":
				$res[]=Array($fld,$ris[2]);
				break;
			case "42703":
				if (!$fld) {
					preg_match("|record (.+) has no field (.+)|i",$mess,$ris);
					$fld=trim($ris[2]);
				}
				$res[]=Array("generic","Errore di Sintassi nella Query.Il campo $fld non esiste.");
			default:
				$res[]=Array("generic","Errore generico");
				break;
		}
		
		return $res;
	}

	private function _getConfig($file,$pk,$pk_val){
		
		$tmp=parse_ini_file($file,true);
		$array_config=$tmp["standard"];
		// ACQUISIZIONE DELLA TABELLA DEL DATABASE
		$dbtable=(isset($array_config["save_table"]) && $array_config["save_table"])?($array_config["save_table"]):($array_config["table"]);
		if(preg_match("|([\w]+)[.]{1}([\w]+)|i",trim($dbtable),$tmp)){
			$this->table=$tmp[2];
			$this->schema=$tmp[1];
		}
		else{
			$this->table=trim($dbtable);
			$this->schema=DB_SCHEMA;
		}
		// ACQUISIZIONE DELLE PRIMARY KEYS DELLA TABELLA (SI PUO' SOSTITUIRE PRENDENDO I DATI DALL?INFORMATION SCHEMA SU DB)
		
		if($array_config["pkey"]){
			$datipkeys=explode(';',$array_config["pkey"]);	
			//for($i=0;$i<count($datipkeys);$i++) $this->pkeys[trim($datipkeys[$i])]=$pk[str_replace("_name","",str_replace("_id","",trim($datipkeys[$i])))];
			for($i=0;$i<count($pk);$i++) $this->pkeys[$pk[$i]]=stripslashes($pk_val[$i]);
		}
		else{
			$this->pkeys=Array("id"=>"");
		}
		//ACQUISIZIONE DELLE DEFINIZIONI DEI CAMPI
		for ($i=0;$i<count($array_config["dato"]);$i++){
			$row_config=explode('|',$array_config["dato"][$i]);
			
			foreach($row_config as  $r){
				$def=array_pad(explode(';',$r), 4, '');
				$this->fields[]=array("field"=>trim($def[1]),"type"=>trim($def[3]));
			}
		}
	}
	function connectDb($host,$port,$dbname,$dbuser,$dbpwd,$dbtype=2){
		if ($dbtype==2) require_once ROOT_PATH."lib/postgres.php";
		if ($port) $host.=":$port";
		$this->db = new sql_db($host,$dbuser,$dbpwd,$dbname, false);
		if(!$this->db->db_connect_id){
			$this->status=-1;
			$this->errors["generic"]="<p>Impossibile connettersi al database</p>";
		}
		else
			$this->status=1;
	}
}
?>
