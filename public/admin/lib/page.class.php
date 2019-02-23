<?php
	
	
	include_once ADMIN_PATH."lib/tabella_h.class.php";
	include_once ADMIN_PATH."lib/tabella_v.class.php";
	include_once ADMIN_PATH."lib/savedata.class.php";
	include_once ADMIN_PATH."lib/export.php";

	
	
	class page{
		
		var $parametri;	// Elenco dei parametri
		var $tableList;	// Elenco delle tabelle da disegnare
		var $arr_mode=Array("view"=>0,"edit"=>1,"new"=>2,"list"=>3);
		var $mode;
		var $livello;
		var $array_levels=Array();
		var $db; 	// Connessione ad DB postegres
		var $tb;			// Oggetto Tabella
		var $save;			//Oggetto SaveData
		var $errors;
		var $notice;
		var $pageKeys;
		var $action;
		
		// Costruttore della classe
		function page($param=Array()){
			
			$pk=_getPKeys();
			$this->primary_keys=$pk["pkey"];
			$this->_get_parameter($param);
			$this->admintype=($_SESSION["USERNAME"]==SUPER_USER)?(1):(2);
			$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
			if(!$this->db->db_connect_id)  die( "Impossibile connettersi al database ".DB_NAME);
			$sql="select e_level.id,e_level.name,coalesce(e_level.parent_id,0) as parent,X.name as parent_name,e_level.leaf from ".DB_SCHEMA.".e_level left join ".DB_SCHEMA.".e_level X on (e_level.parent_id=X.id)  order by e_level.depth asc;";
			if (!$this->db->sql_query($sql)) print_debug($sql,null,"page_obj");
				print_debug($sql,null,"conf");
			$ris=$this->db->sql_fetchrowset();
			foreach($ris as $v) $this->array_levels[$v["id"]]=Array("name"=>$v["name"],"parent"=>$v["parent"],"leaf"=>$v["leaf"]);
			
		}
		private function _get_frm_parameter(){
			$out = null;
			if (is_array($this->parametri) && count($this->parametri)){
				$i=0;
				foreach($this->parametri as $key=>$val){
					$out["parametri[$i][$key]"]=$val;
					$i++;
				}
			}
			return $out;
		}
		private function _get_pkey($lev){
			return $this->primary_keys[$lev];
		}
		private function _get_pkey_value($pk){
			foreach($this->parametri as $k=>$v){
				//if ($pk==$k."_id" || $pk==$k."_name")
					if(in_array($pk,$this->primary_keys[$k])){
						
						for($i=0;$i<count($this->primary_keys[$k]);$i++){
							if($this->primary_keys[$k][$i]==$pk){
								if(is_numeric($v) && (int)$v<=0) return 0;
								return stripslashes($v);
							}
						}
					}
			}
			return 0;
		}
		function _getKey($value){
			if (is_null($this->parametri)) {
				return;
			}
			foreach($this->parametri as $key=>$val){
				$ris=$this->_get_pkey($key);
				foreach($ris as $val){
					$v=$this->_get_pkey_value($val);
					if($v)
						$tmp[$val]=$v;
					elseif ($value) $tmp[$val]=stripslashes($value);
				}
				$this->levKey[$key]=$tmp;
				$tmp=null;
			}
		}

		// Metodo che prende le configurazioni della pagina da Database
		function get_conf(){
			if(!$this->livello) $lev="root";
			else
				$lev=$this->livello;
				
			if ($this->mode==0 or $this->mode==3)
				$filter_mode="(mode=0 or mode=3)";
			else
				$filter_mode="(mode=$this->mode)";
			$sql="select e_form.name as form_name,e_form.save_data,config_file,tab_type,form_destination,e_form.parent_level,foo.parent_name,e_level.name as level,e_form.js as javascript,order_fld,coalesce(foo.depth,-1) from ".DB_SCHEMA.".form_level left join ".DB_SCHEMA.".e_form on (form_level.form=e_form.id) left join ".DB_SCHEMA.".e_level on (e_form.level_destination=e_level.id) left join ".DB_SCHEMA.".e_level as foo on (form_level.level=foo.id) where $filter_mode and foo.name='$lev' and visible=1 and ".$this->admintype." <= e_level.admintype_id order by e_level.depth,order_fld;";
			
			print_debug($sql,null,"conf");
			if (!$this->db->sql_query($sql)){
				print_debug($sql,null,'error');
				echo "<p>Errore nella configurazione del sistema</p>";
				exit;
			}
			
			$res=$this->db->sql_fetchrowset();
			
			$sql="select id as val,name as key,menu_field as field from ".DB_SCHEMA.".e_level order by id";
			$this->db->sql_query($sql);
			$arr_livelli=$this->db->sql_fetchrowset();
			foreach($arr_livelli as $value){
				list($lvl_id,$lvl_name,$lvl_header)=array_values($value);
				$this->navTreeValues[$lvl_name]=$lvl_header;
				$livelli[$lvl_id]=Array("val"=>$lvl_id,"key"=>$lvl_name);
			}
			unset($this->tableList);			
			
			for($i=0;$i<count($res);$i++){
				$res[$i]["parent_level"]=isset($livelli[$res[$i]["parent_level"]])?$livelli[$res[$i]["parent_level"]]:null;
				$this->tableList[]=$res[$i];
			}
			
		}
		
		//Metodo che scrive il menu di navigazione
		function writeMenuChild(){	//Da Fare!!!!!!
			
		}
		
		function writeMenuNav(){
			//Modifica 5/10/2009 x permettere Menu di navigazione multilingua
		
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			} else {
				$langs=array('it', 'en');
			}
			$mylang=(!empty($_REQUEST["language"]))?$_REQUEST["language"]:substr($langs[0],0,2);
			$rel_dir="config/tab/$mylang/";
			if(!is_dir(ROOT_PATH.$rel_dir)) $rel_dir="config/tab/it/";
			if(defined('TAB_DIR')) $rel_dir="config/tab/".TAB_DIR."/";
			
			$tmp=parse_ini_file(ROOT_PATH.$rel_dir.'menu.tab',true);
			$this->navTreeValues=$tmp;
			$lbl="<a class=\"link_label\" href=\"#\" onclick=\"javascript:navigate([],[])\">Admin</a>\n\t\t\t\t\t";
			$n_elem=count($this->parametri);
			if ($n_elem>0){
				$lvl=Array();
				$val=Array();
				foreach($this->parametri as $key=>$value){
					array_push($lvl,$key);
					array_push($val,$value);
					$pk=$this->_get_pkey($key);
					
					if(($this->mode==2 || !isset($this->navTreeValues[$key]["standard"])) && $key==$this->livello){
						$sql="SELECT ".$this->navTreeValues[$key]["constant"]." as val" ;
					}
					else{
						$filter=Array();
						foreach($pk as $v){
							$value=$this->_get_pkey_value($v); 
							if($value) $filter[]="$v='$value'";
						}
						$schema=(in_array($key,Array("users","groups","user_group")))?(USER_SCHEMA):(DB_SCHEMA);
						$sql="SELECT coalesce(".$this->navTreeValues[$key]["standard"]."::varchar,'') as val FROM ".$schema.".$key WHERE ".implode(' AND ',$filter);
						
					}

					if(!$this->db->sql_query($sql)){
						print_debug($sql,null,"navtree");	
						
					}
					$navTreeTitle=strtolower($this->db->sql_fetchfield("val"));
					if ((is_numeric($value) && $value>0) || (!is_numeric($value) && strlen($value)>0))
						$lbl.=" > <a class=\"link_label\" href=\"#\" onclick=\"javascript:navigate(['".@implode("','",$lvl)."'],['".@implode("','",$val)."'])\">$navTreeTitle</a>\n\t\t\t\t\t";
					else
						$lbl.=" > <a class=\"link_label\" href=\"#\">$navTreeTitle</a>\n\t\t\t\t\t";
				}
			}
			echo "
			<form name=\"frm_label\" id=\"frm_label\" method=\"POST\">
				<div id=\"div_label\" style=\"background-color:#E7EFFF;width:100%;\">\n\t\t\t\t\t".$lbl."</div>
			</form>";
		}
		
		// Metodo privato che setta i parametri della classe
		function _get_parameter($p){
			$m=(!empty($p["mode"]))?($p["mode"]):('view');
			$this->mode=$this->arr_mode[$m];
			if (!empty($p["parametri"])){
				
				for($i=0;$i<count($p["parametri"]);$i++){
					$arr=$p["parametri"][$i];
					$val=each($arr);
					if(preg_match("|^'(.+)'$|",stripslashes($val["value"]),$match)) $this->parametri[$val["key"]]=$match[1];
					else
						$this->parametri[$val["key"]]=$val["value"];
				}
			}
			
				
			$this->last_livello=(!empty($p["parametri"]))?(array_pop(array_keys(array_pop($p["parametri"])))):("project");
			$this->livello=(!empty($p["livello"]))?($p["livello"]):("");
			if (!empty($p["azione"])){
				$this->action=strtolower($p["azione"]);
				if($this->action=="esporta") $this->mode=$this->arr_mode["edit"];
				if($this->action=="esporta test") $this->mode=$this->arr_mode["edit"];
				if($this->action=="importa") $this->mode=$this->arr_mode["new"];
				if($this->action=="importa raster") $this->mode=$this->arr_mode["edit"];
                if($this->action=="importa catalogo") $this->mode=$this->arr_mode["edit"];
				if($this->action=="wizard wms") $this->mode=$this->arr_mode["new"];
			}
		}
		
		function write_parameter(){
			
			if(is_array($this->parametri) && count($this->parametri)){
				$i=0;
				foreach ($this->parametri as $key=>$val){
					echo "\t<input type=\"hidden\" name=\"parametri[$i][$key]\" id=\"$key\" value=\"".stripslashes($val)."\">\n";
					$i++;
				}
			}
		}
		
		function write_page_param($param){
			
			if (count($param)>0){
			foreach($param as $key=>$value)
				if ($value) echo "\t<input type=\"hidden\" name=\"$key\" value=\"".stripslashes($value)."\" id=\"prm_$key\">\n";
			}
		}
		
		function get_livello(){
			if(count($this->parametri)){
				$lvl=array_keys($this->parametri);
				return $lvl[count($lvl)-1];
			}
			else
				return "";
		}
		function get_value(){
			if(count($this->parametri)){
				$tmp=array_keys($this->parametri);
				return $this->parametri[$tmp[count($this->parametri)-1]];
			}
			else
				return 0;
		}
		
		function get_parentValue(){
			if(count($this->parametri)>1){
				$tmp=array_keys($this->parametri);
				return $this->parametri[$tmp[count($this->parametri)-2]];
			}
			else
				return 0;
		}
		function get_idLivello($lev=""){
			if(!$lev){
				$sql="SELECT id FROM ".DB_SCHEMA.".e_level WHERE name='".$this->livello."'";
				if(!$this->db->sql_query($sql)){
					
				}
				else{
					return $this->db->sql_fetchfield("id");
				}
			}
			else{
				foreach($this->array_levels as $key=>$value){
					if ($value["name"]==$lev)
						return $key;
				}
				return null;
			}
			
		}
		function _getChild(){
			$out=Array();
			foreach($this->array_levels as $key=>$val) if($val["parent"]==$this->livello) $out[]=$val;
			return $out;
		}
		function writeAction($mode){
			$lev=$this->livello;
			require_once ADMIN_PATH."lib/filesystem.php";
			$dir=ADMIN_PATH."export/";
			$tmp=elenco_file($dir,"sql");
			for($i=0;$i<count($tmp);$i++){
				$list=file($dir.$tmp[$i]);
				if (strtolower(trim($list[1]))=="--type:$lev"){
					$rows[]="<tr><td><input type=\radio\"></td><td></td><td></td></tr>";
				}
			}
			include ADMIN_PATH."inc/import.php";
		}
		function setErrors($err){
			foreach($err as $key=>$val){
				$this->errors[$key]=$val;
			}
		}
		function setNotice($notice){
			foreach($notice as $val)
				if ($val) $this->notice[]=$val;
		}
		
		//Metodo che scrive il div dei Messagii e Errori Generici
		private function writeMessage($msg){
			if(!empty($this->errors["generic"]) || !empty($msg["generic"]) || $this->notice){
				$generic=Array();
				for($i=0;$i<count($this->notice);$i++)
					if ($this->notice[$i]) $generic[]=$this->notice[$i];
				for($i=0;$i<count($this->errors["generic"]);$i++)
					if ($this->errors["generic"][$i]) $generic[]=$this->errors["generic"][$i];
				for($i=0;$i<count($msg["generic"]);$i++)
					if ($msg["generic"][$i]) $generic[]=$msg["generic"][$i];
				echo "<div id=\"error\" class=\"errori\" style=\"width=100%;color:red;font-weight:bold;\"><ul><li>".@implode("</li><li>",$generic)."</li></ul></div>";
			}
		}
		
		//Metodo che scrive il Form in modalit� List  Elenco dei Child
		
		private function writeListForm($tab,$el,&$prm){
			switch ($tab["tab_type"]){
				
				case 0:	//elenco con molteplici valori (TABELLA H)
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="";
					
					if (is_array($el) && $el["value"] && $tab["parent_name"]) $filter=$tab["parent_name"]."_name = '".$el["value"]."'";
					
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					
					foreach($this->pageKeys as $key) if ($el["value"]) $flt[]="$key = '".$el["value"]."'";
					$filter=@implode(" AND ",$flt);
					if($tab["level"]=="project" && $_SESSION["USERNAME"]!=SUPER_USER && defined('USER_SCHEMA'))
						$filter="project_name in (SELECT DISTINCT project_name FROM ".DB_SCHEMA.".project_admin WHERE username='$_SESSION[USERNAME]')";
					$butt="nuovo";
					if($tab["level"]=="project" && $this->admintype==2) $butt="";
					if($tab["level"]=='tb_logs') $butt="";
					//$tb->set_titolo($tab["title"],$butt,$prm,20);
					$tb->set_titolo($tb->FileTitle,$butt,$prm,20);
					$tb->tag=$tab["level"];
					$tb->set_dati($filter,(isset($tab["order_by"]))?$tab["order_by"]:null);
					$tb->get_titolo();
					$tb->elenco();
					break;
					
				case 2:	//elenco con molteplici valori (TABELLA H) che porta alla modifica tramite Aggiungi
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="-1";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_id = ".$el["value"];
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					foreach($this->pageKeys as $key) if ($el["value"]) $flt[]="$key = '".$el["value"]."'";
					$filter=@implode(" AND ",$flt);
					switch($tab["level"]){
						case "project_groups":
							$filter.=" AND NOT group_name ilike 'gisclient_author'";
							break;
						case "mapset_link":
							$filter.=" AND presente>0";
							break;
						default:
							break;
					}
					$tb->set_titolo($tb->FileTitle,"modifica",$prm);
					$tb->tag=$tab["level"];
					$tb->set_dati($filter,isset($tab["order_by"])?$tab["order_by"]:null);
					$tb->get_titolo();
					$tb->elenco();
					break;
					
				case 3:	// Elenco con un solo valore(TABELLA H)
					
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="-1";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_name = '".$el["value"]."'";
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_dati($filter);
					if ($tb->num_record==0){
						$tb->set_titolo($tb->FileTitle,"nuovo",$prm);
					}	
					else{
						foreach($tb->pkeys as $key) $prm["parametri[][".$tab["level"]."]"]=$tb->array_dati[0][$key];	//Passo i valori delle Primary Key
						if($tab["level"]!='tb_import')
							$tb->set_titolo($tb->FileTitle,"modifica",$prm);
						else
							$tb->set_titolo($tb->FileTitle,"",$prm);
					}
					$tb->tag=$tab["level"];
					
					$tb->get_titolo();
					$tb->elenco();
					break;
					
				case 4:	//elenco con molteplici valori (TABELLA H) dove si include un file di configurazione
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="-1";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_id = ".$el["value"];
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$data=Array();
					$enabled=1;
					if ($tab["save_data"])
						include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";

					$tb->set_titolo($tb->FileTitle,$button,$prm);
					$tb->tag=$tab["level"];
					
					
					$tb->set_multiple_data($data);
					$tb->get_titolo();
					$tb->elenco();
							
					break;
					
				case 5:
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="";
					if (is_array($el) && $el["value"] && $tab["parent_name"]) $filter=$tab["parent_name"]."_name = '".$el["value"]."'";
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->tag=$tab["level"];
					$tb->set_dati($filter,$tab["order_by"]);
					$tb->get_titolo();
					$tb->elenco();
					break;
			}
		}
		
		//Metodo che scrive il Form in modalit� View
		
		private function writeViewForm($tab,$el,&$prm){
			
			$frm = '';
			switch ($tab["tab_type"]){
				case 1:	// MODALITA' VIEW STANDARD (TABELLA V)
					$tb=new Tabella_v($tab["config_file"].".tab","view");

					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];

					$e=array_pop($this->levKey);
					
					foreach($e as $k=>$v){
						$flt[]="$k='".addslashes($v)."'";
					}
					$filter=@implode(" AND ",$flt);
					if(trim($tab["form_destination"])) $frm=trim($tab["form_destination"]);
					$tb->set_dati($filter);
					$prm["livello"]=$tab["level"];
					if ($tb->num_record>0){
						
						for($j=0;$j<count($tb->pkeys);$j++){
							$tb->pkeys_value[$j]=isset($tb->pkeys[$j])?$this->_get_pkey_value($tb->pkeys[$j]):null;
						}
						$b="modifica";
						$tb->set_titolo($tb->FileTitle,$b,$prm);
						$tb->get_titolo($frm);
						$tb->tabella();
					}
					else{
						$b="nuovo";
						$tb->set_titolo($tb->FileTitle,$b,$prm);
						$tb->get_titolo($frm);
							echo "<p><b>Nessun Dato Presente</b></p>";
						}
					break;
					
				case 50: // MODALITA' VIEW Inclusione File(TABELLA V)
					$tb=new Tabella_v($tab["config_file"].".tab","view");
					$data=Array();
					include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
					
					if(trim($tab["form_destination"])) $frm=trim($tab["form_destination"]);
					$tb->set_dati(isset($data[0])?$data[0]:null);
					$prm["livello"]=$tab["level"];
					for($j=0;$j<count($tb->pkeys);$j++){
						$tb->pkeys_value[$j]=isset($tb->pkeys[$j])?$this->_get_pkey_value($tb->pkeys[$j]):null;
					}
					$tb->set_titolo($tb->FileTitle,$button,$prm);
					$tb->get_titolo($frm);
					$tb->tabella();
					break;
			}
			echo "<hr>\n";
		}
		//Metodo che scrive il Form in modalit� EDIT
		private function writeEditForm($tab,$el,&$prm){
			
			switch ($tab["tab_type"]){
				case 110:
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$prm["mode"]="new";

					$tb=new Tabella_h($tab["config_file"].".tab","list");
					foreach($tb->pkeys as $key=>$value) if ($el["value"]) $flt[]="$key = '".$el["value"]."'";
					$filter=@implode(" AND ",$flt);
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$button=@implode("\n\t\t",$btn);
					
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->get_titolo();
					$tb->mode="edit";
					if($tab["level"]=="qt_link") {
						
						$filter=$tab["parent_name"]."_id = ".$this->parametri[$tab["parent_name"]];
						$tb->tag=Array("pkey"=>"link","pkey_value"=>0);
					}
					$tb->set_dati($filter);
					$tb->elenco();
					echo "<hr>$button";
					echo "</form>";
					
					
					break;
					
				case 100: //Tabella H per elencare tutti i valori possibili e quelli selezionati
					$prm["livello"]=$tab["level"];
					$prm["savedata"]=$tab["save_data"];
					$tmp=array_values($this->parametri);
					$parent_key=$tmp[count($tmp)-2];
					$tb=new Tabella_h($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					switch($tab["level"]){
						case "mapset_layergroup":
							$param="layergroup";
							break;
						case "mapset_usergroup":
							$param="usergroup";
							break;
						case "mapset_qt":
							$param="qt";
							break;
						case "mapset_link":
							$param="link";
							break;
						case "qt_selgroup":
							$param="qt";
							break;
						case "project_groups":
							$filter.=" NOT group_name ilike 'gisclient_author'";
							break;
						case "":
							$param="";
							break;
						default:
							break;
					}
					
					$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
					$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
					$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'$param');\">\n";
					$tb->get_titolo();
					$tb->set_dati($filter);
					$tb->elenco();
					$button=@implode("\n\t\t",$btn);
					
					echo "<hr>$button";
					echo "\n<input type=\"hidden\" name=\"save_type\" value=\"multiple\">";
					echo "</form>";
					break;
					
				case 0:		//SERVE PER ELENCARE I VALORI IN FUNZIONE DEL PARENT (TABELLA H)
					
					$prm["livello"]=$tab["level"];
					$tmp=array_values($this->parametri);
					$parent_key=$tmp[count($tmp)-2];
					$filter=$tab["parent_level"]["key"]."_id = ".$parent_key;
					$prm["parametri[][".$tab["level"]."]"]="";
					
					$tb=new Tabella_h($tab["config_file"].".tab",$mode);
					
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					
					$tb->get_titolo();
					$tb->set_dati($filter);
					$tb->elenco();
				
					break;
				case 1:	//MODALITA' STANDARD
				case 50:
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
									
					$tb=new Tabella_v($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];	
					$j=0;
					$e=array_pop($this->levKey);
					$j=0;
					foreach($e as $k=>$v){
						$flt[]="$k='$v'";
						$prm["pkey[$j]"]=$k;
						$prm["pkey_value[$j]"]=$v;
						$j++;
					}
					$j=0;					
					$filter=@implode(" AND ",$flt);
					
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					else{						
						if($tab["tab_type"]==1)
							$tb->set_dati($filter);
						else{
							include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
							$tb->set_dati(isset($data[0])?$data[0]:null);
						}
					}
					$tb->set_titolo($tb->FileTitle,"",$prm);
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->get_titolo();
					$tb->edita();
					$this->write_page_param($prm);
					echo "</form>";
					break;
				case 2:
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$tb=new Tabella_v($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					
					for($j=0;$j<count($tb->pkeys);$j++){
						$prm["pkey[$j]"]=$tb->pkeys[$j];
						$prm["pkey_value[$j]"]=$this->_get_pkey_value($tb->pkeys[$j]);
					}
					$e=array_pop($this->levKey);
					foreach($e as $k=>$v){
						$flt[]="$k='$v'";
					}
					$filter=@implode(" AND ",$flt);
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					
					$tb->edita();
					$this->write_page_param($prm);
					echo "</form>";
					break;
				case 4:
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$tb=new Tabella_v($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
					for($j=0;$j<count($tb->pkeys);$j++){
						$prm["pkey[$j]"]=$tb->pkeys[$j];
						$prm["pkey_value[$j]"]=$this->_get_pkey_value($tb->pkeys[$j]);
					}
					$filter=$tab["parent_name"]."_id = ".$el["value"];
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					$tb->edita();
					$this->write_page_param($prm);
					echo "</form>";
					break;
				case 10:	// Caso di Form AGGIUNGI  DA DEFINIRE
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$prm["mode"]="new";
					$tb=new Tabella_h($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					switch($tb->tabelladb){
						case "vista_mapset_layergroup":
							$filtro="mapset_id in (0,".$this->parametri["mapset"].") and project_id=".$this->parametri["project"]." ORDER BY layergroup_name;";
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Layer\" onclick=\"javascript:selectAll(this,'layergroup');\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Status\" onclick=\"javascript:selectAll(this,'status');\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona RefMap\" onclick=\"javascript:selectAll(this,'refmap');\">";
							break;
						case "vista_qt_selgroup":
							$filtro="project_id=".$this->parametri["project"];
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:180px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Query Template\" onclick=\"javascript:selectAll(this,'qt');\">";
							break;
						case "user_project":
							$filtro="user_id=".$this->parametri["user"];
							break;
						default:
							$filtro=($tab["parent_level"]["val"])?(" ".$tab["parent_level"]["key"]." = ".$tab["parent_level"]["val"]):("");
							break;
					}
					$button=@implode("\n\t\t",$btn);
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->get_titolo();
					$tb->set_dati($filtro);
					
					$tb->elenco();
					echo "<hr>$button";
					echo "</form>";
					break;
				case 5:		//CON FILE DI INCLUSIONE (TABELLA H)
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$tb=new Tabella_h($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$msg="";
					include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
					for($j=0;$j<count($tb->pkeys);$j++){
						$prm["pkey[$j]"]=isset($tb->pkeys[$j])?$tb->pkeys[$j]:null;
						$prm["pkey_value[$j]"]=isset($tb->pkeys[$j])?$this->_get_pkey_value($tb->pkeys[$j]):null;
					}
					$filter=$tab["parent_name"]."_id = ".$el["value"];
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					//$tb->set_titolo($tab["title"],"",$prm);
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					if (is_array($data) && !$data && !$msg) $data=$filter;
					
					$tb->set_multiple_data($data);
					$tb->elenco($msg);
					if(count($btn)) $button=implode("\n\t",$btn);
					echo "<hr>$button";
					echo "<input type=\"hidden\" name=\"save_type\" value=\"multiple\">";
					echo "</form>";
					break;
					
			}	
		}
		//Metodo che scrive il Form in modalit� NEW
		private function writeNewForm($tab,$el,&$prm){
			$j=0;
			
			foreach($this->pageKeys as $v){
				$prm["pkey[$j]"]=$v;
				$j++;
			}
			$j=0;
			switch ($tab["tab_type"]){
				
				case 0:
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_id = ".$el["value"];
					$tb=new Tabella_h($tab["config_file"].".tab","new");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->get_titolo();
					$tb->elenco();
					break;
				case 50:
				case 1:
				case 2:
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					if($this->action=='wizard wms'){
						$prm["config_file"]="layergroup_wms.tab";
						$prm["savedata"]="layergroup_wms";
						$tab["title"]="Nuovo Layergroup da WMS";
					}
					$tb=new Tabella_v($prm["config_file"],"new");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					$tb->edita($prm);
					$this->write_page_param($prm);
					echo "</form>";
					break;
			}		
		}
		// Metodo che costruisce la pagina
		function writePage($err=Array()){
			
			//Stampa errori generici e messaggi se ci sono
			$this->writeMessage($err);
			if(!empty($this->tableList)){
				/*RECUPERO I DATI DELLA TABELLA PRIMARIA*/
				$table=new Tabella_v($this->tableList[0]["config_file"].".tab");
				
				$this->pageKeys=array_keys($table->pkeys);
				if (!is_null($this->parametri)) {
					foreach($table->pkeys as $k=>$v) 
						foreach($this->parametri as $k1=>$v1)
							if(preg_match("/(".$k1."_id|".$k1."_name)/Ui",$k))
								if(!empty($_POST["dati"][$k])) $this->parametri[$k1]=$_POST["dati"][$k];
				}
				unset($table);
				
				for($i=0;$i<count($this->tableList);$i++){
					
					$el=@each(@array_reverse($this->parametri,true));
					$this->_getKey($el["value"]);
					$filter="";
					$prm=$this->_get_frm_parameter();
					//VALORIZZO SE PRESENTI I PARAMETRI DELLE FUNZIONI DI SELECT
					$tab=$this->tableList[$i];
					switch ($this->mode){		//IDENTIFICO LA MODALITA DI VISUALIZZAZIONE 0:VIEW --- 1:EDIT --- 2:NEW
						
						case 0:					//MODALITA VIEW
						case 3:					// MODALITA LIST
							if($tab["tab_type"]==1 || $tab["tab_type"]==50) {
								$this->currentMode='view';
								$this->writeViewForm($tab,$el,$prm);
							}
							else{
								$this->currentMode='list';
								$this->writeListForm($tab,$el,$prm);
							}
							break;
						case 1:					//MODALITA EDIT
							$this->currentMode='edit';
							$prm["modo"]="edit";
							$prm["livello"]=$tab["level"];
							$prm["config_file"]=$tab["config_file"].".tab";
							switch ($this->action){
								case "importa raster":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->parametri[$tab["level"]];
									include ADMIN_PATH."include/import_raster.php";
									$this->write_page_param($prm);
									echo "</form>";
									echo $resultForm;
									break;
								case "wizard wms":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->parametri[$tab["level"]];
									include ADMIN_PATH."include/import_raster.php";
									$this->write_page_param($prm);
									echo "</form>";
									echo $resultForm;
									break;
									break;
								case "esporta test":
								case "esporta":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->parametri[$tab["level"]];
									include ADMIN_PATH."include/export.php";
									$this->write_page_param($prm);
									echo "</form>";
									if(isset($resultForm)) echo $resultForm;
									break;
                                case "importa catalogo":
                                    include ADMIN_PATH."include/catalog_import.php";
                                    break;
								default:
									
									$this->writeEditForm($tab,$el,$prm);
									break;
							}
							break;
						case 2:					////MODALITA NEW
							$prm["modo"]="new";
							$this->currentMode='new';
							foreach($prm as $key=>$val){
								if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
									$prm[$ris[2]]=$val;
								}
							}
							
							switch ($this->action){
								case "importa":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->get_parentValue();
									$livello=$this->livello;
									include ADMIN_PATH."include/import.php";
									$this->write_page_param($this->parametri);
									$this->write_parameter(array_pop($this->parametri));
									echo "</form>";
									echo $resultForm;
									break;
								default:	
									$this->writeNewForm($tab,$el,$prm);
									break;
							}
							break;
						
					}
					//if ($action_btn) echo "$action_btn \n";
					$action_btn="";
					if($tab["javascript"]){
						echo "<script>\n\t".$tab["javascript"]."('".$tab["form_name"]."');\n</script> \n";
					}
				}
				$arr_keys=(count($this->parametri))?(array_keys($this->parametri)):(Array());

				if(in_array($this->mode,Array(0,3)) && !empty($arr_keys[0])){
					$tmp=$this->parametri;
					array_pop($tmp);
					$arrkeys=array_keys($tmp);
					$arrvalues=array_values($tmp);
					$keys=(count($arrkeys))?("'".implode("','",$arrkeys)."'"):("");
					$values=(count($arrvalues))?("'".implode("','",$arrvalues)."'"):("");
					$map_btn= '';
					if($arr_keys[0]=="project") $map_btn="<td><input type=\"button\" class=\"hexfield\" value=\"Mappe OnLine\" style=\"width:120px;\" onclick=\"var win=window.open('create_mapset.php?project_name=".$this->parametri["project"]."','mapset','resizable=yes,width=800,height=400,status=no,location=no,toolbar=no,scrollbars=yes');win.focus();\"></td>";
					
					$btn="\n\t<table cellspacing=\"5\">
			<tr>
				<td><input type=\"button\" class=\"hexfield\" value=\"Indietro\" onclick=\"javascript:navigate([$keys],[$values])\"></td>
				$map_btn
			</tr>
		</table>
				";
				
					echo $btn;
				}
			}
			else
				echo "<p>Nessun configurazione definita per la pagina</p>";
			//$this->showTime();
		}
		function getTime($str){
			$tmp=explode(" ",microtime());
			$t=$tmp[0]+$tmp[1];
			$this->time[$str]=$t;
		}
		function showTime(){
			print_debug($this->time,null,'TIME');
		}
		
	}
	
?>
