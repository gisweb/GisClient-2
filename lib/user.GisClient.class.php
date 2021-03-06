<?php
/*
CODICI DI ERRORE DELLA VALIDAZIONE
"1" ERRORE NELLA QUERY
-2 NESSUN UTENTE CON QUESTA PASSWORD
-3 UTENTE DISATTIVATO
-4 NESSUN GRUPPO ASSEGNATO A QUESTO UTENTE
"1"01 ERRORE NELLA CODIFICA DEI DATI (HACKING)
"1"02 FILTRO VALIDAZIONE ERRATO (HACKING)
"1"03 SESSIONE SCADUTA
*/

require_once ROOT_PATH."lib/user.abstract.php";
require_once ROOT_PATH."lib/postgres.php";

class userApps extends user{
	var $flds;
	var $editUser=1;
	var $editGroup=1;
	var $userIp;
	var $schema;
	var $dbschema=DB_SCHEMA;
	var $virtualGroups=Array();

	function _init($obj){
		$this->flds=$obj;
		if(isset($this->data[$this->flds["user"]]) && isset($this->data[$this->flds["pwd"]]))
			$this->action = "valida";
		
		$_SESSION["VIRTUAL_GROUPS"]=$this->virtualGroups;
		$this->schema=USER_SCHEMA;
		$this->username=isset($_SESSION["USERNAME"])?$_SESSION["USERNAME"]:null;
		$this->groups=isset($_SESSION["GROUPS"])?$_SESSION["GROUPS"]:null;
		$this->role=isset($_SESSION["ROLE"]) ? $_SESSION["ROLE"] : null;
		$this->userIp=getenv("REMOTE_ADDR");
		$this->get_db();
		
	}
	
	function writeAccessInfo(){	//ACCESSO ANDATO A BUON FINE SCRIVO LE INFORMAZIONI DI ACCESSO
		$sql="INSERT INTO ".$this->schema.".accessi_log(ipaddr,username,data_enter,application) VALUES(".
				$this->db->quote($this->user_ip).",".$this->db->quote($this->username).",CURRENT_TIMESTAMP(1),".$this->db->quote($app).")";
		$this->db->sql_query($sql);
	}
	
	function getRoles(){
		return Array();
	}

	function getGisclientAdmin($project,$mode){
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="select distinct '$project' as project_name,user_group.username,case when (coalesce(X.username,'')<>'') then 1 else 0 end as presente from ".USER_SCHEMA.".user_group $JOIN (SELECT username FROM ".DB_SCHEMA.".project_admin WHERE project_name="
		.$this->db->quote($project).") X on (user_group.username=X.username) where coalesce(user_group.username,'')<>'' order by user_group.username";

		print_debug($sql,null,'GC_USERS');
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}

	function getGroup($group){
                $filter = '';
		if($group) $filter=" AND groupname ='$group'";
		$sql="select distinct groupname,description from ".$this->schema.".groups WHERE coalesce(groupname,'')<>'' $filter";
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}
	//METODO CHE RECUPERA INFO SUI GRUPPI DELL'UTENTE
	function getGroups($username){
		//SELEZIONO DAL DATABASE L'ELENCO DEI GRUPPI AI QUALI L'UTENTE APPARTIENE
		$sql = "SELECT groupname FROM ".$this->schema.".user_group WHERE username=".
				$this->db->quote($username);
		print_debug($sql,null,'tabella');
		if($this->db->sql_query($sql)){		//STRUTTURA GRUPPI GISCLIENT
			$ris=$this->db->sql_fetchlist('groupname');
			if(!$ris) {
				$this->error->getError("C001");
				return false;
			}
			foreach($ris as $g){
				$this->groups[]=$g;
			}
			return $this->groups;
		}
		else{
			$this->error->setNote($sql);
			$this->error->getError("1");	
			return false;
		}
	}

	function getGroupsList($user,$mode){
		//$fldList=implode(',',$conf);
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="SELECT ".$this->db->quote($user)." as username,coalesce(X.groupname,'') as groupname,CASE WHEN coalesce(Y.groupname,'')='' THEN 0 ELSE 1 END as presente FROM ".$this->schema.".groups X $JOIN (SELECT * FROM ".DB_SCHEMA.".user_group WHERE username=".
				$this->db->quote($user).") Y using(groupname) ";
		print_debug($sql,null,'tabella');
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}

	function getUser($user,$mode){
                $filter = '';
		if($user) $filter=" WHERE username=".$this->db->quote($user);
		$sql="select * from ".$this->schema.".users $filter";
		print_debug($sql,null,'tabella');
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}
	
	function getUsersList($group,$mode){
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="SELECT ".$this->db->quote($group)." as groupname,coalesce(X.username,'') as username,CASE WHEN coalesce(Y.username,'')='' THEN 0 ELSE 1 END as presente FROM ".$this->schema.".users X $JOIN (SELECT * FROM ".DB_SCHEMA.".user_group  WHERE groupname=".$this->db->quote($group).") Y using(username)";
		print_debug($sql,null,'tabella');
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}

	function getUserMapset(){

	}

	function validateUser(){
		if(!$this->username){
			if(!$this->data[$this->flds["user"]]){
				$this->error->getError("A004");
				return false;
			}
			if(!$this->data[$this->flds["pwd"]]){
				$this->error->getError("A005");
				return false;
			}
			$sql = "SELECT count(*) as presente FROM ".$this->schema.".users  WHERE username=".$this->db->quote($this->data[$this->flds["user"]]).
					" AND enc_pwd = ".$this->db->quote($this->data[$this->flds["pwd"]]);
			print_debug($sql,null,'GC_USERS');
			if(!$this->db->sql_query($sql)){
				$this->error->setNote($sql);
				$this->error->getError("1");
				return false;
			}
			$presente = $this->db->sql_fetchrow();
			$presente=$presente[0];
			if ($presente == 0){
				return false;
			}

			if($this->context=='author'){
				$sql = "SELECT count(*) as presente FROM ".DB_SCHEMA.".project_admin  WHERE username=".
				$this->db->quote($this->data[$this->flds["user"]]);
				print_debug($sql,null,'GC_USERS');
				if(!$this->db->sql_query($sql)){
					$this->error->setNote($sql);
					$this->error->getError("1");
					return false;
				}
			}

			return $this->setInfo($presente,$this->data["username"]);
			
		}
	}

	//METODI DI CONNESSIONE AL DB
	function set_db(){
		$this->db=$this->db;
	}

	function get_db(){
		if(!isset($this->db)) $this->connettidb();
		return $this->db;
	}

	function connettidb(){
		$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$this->db->db_connect_id)  die( "UTENTI Impossibile connettersi al database ".DB_NAME );
	}

	function close_db(){
		if(isset($this->db)) $this->db->sql_close;
	}

	function authenticateUser(){}
}
?>
