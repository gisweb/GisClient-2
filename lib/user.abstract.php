<?php
require_once ROOT_PATH."lib/debug.php";

class error{
	var $code;
	var $message;
	var $note;
	var $errList=Array(
		"-1"=>"Errore Generico",													//ERRORI GENERICI
		"1"=>"Errore nella query",
		"2"=>"Tentativo di accesso non autorizzato",
		"A001"=>"Nessun utente con questa password",								//ERRORI SULLA VALIDAZIONE UTENTE
		"A002"=>"Utente disattivato.Contattare l'amministratore di sistema.",
		"A003"=>"Utente non assegnato ad alcun gruppo",
		"A004"=>"Nessun nome utente inserito",
		"A005"=>"Nessuna password inserita",
		"B001"=>"Errore nella codifica dei dati",									//ERRORI SULL'AUTENTICAZIONE DELL'UTENTE (TENTATIVO DI HACKING)
		"B002"=>"Filtro validazione errato",
		"B003"=>"Sessione scaduta",
		"C001"=>"Nessun Gruppo definito",											//ERRORI SUI GRUPPI
		"D001"=>"Nessun Ruolo definito"												//ERRORI SUI RUOLI
	);
	
	
	function __construct($err=Array()){
		if($err && is_array($err)) $this->errList=$err;
	}
	function getError($code='-1'){
		$this->code=$code;
		$this->message=$this->errList[$code];
		return $this;
	}
	function setNote($note=''){
		$this->note=$note;
	}
}

abstract class user{
	protected $domain;
	var $username;
	var $groups;
	var $roles;
	var $userIp;
	var $schema;
	var $error;
	var $virtualGroups;
	var $action;
	var $status=false;
	//METODO PER SETTARE INFORMAZIONI AGGIUNTIVE DURANTE L'ISTANZIAZIONE DELLA CLASSE
	abstract function _init($obj);
	//METODO PER L'AUTENTICAZIONE DELL'UTENTE TRAMITE USERNAME/PASSWORD
	abstract function validateUser();
	//METODO PER L'AUTENTICAZIONE DEL'UTENTE TRAMITE AUTHENTICATION STRING (ACCESSO DA APPLICAZIONE ESTERNA)
	abstract function authenticateUser();
	//METODO PER RECUPERARE LE INFORMAZIONI SUI GRUPPI AI QUALI APPARTIENE L'UTENTE
	abstract function getGroups($username);
	//METODO PER RECUPERARE I RUOLI CHE L'UTENTE HA NEI CONTESTI/APPLICAZIONI
	abstract function getRoles();
	//METODO CHE INSERISCE IN SESSIONE I DATI DELL'UTENTE
	//abstract function setInfo($activate,$username);
	//METODO CHE SCRIVE I DATI DELL'ACCESSO DELL'UTENTE 
	abstract function writeAccessInfo();	
	//abstract function setError($err,$username);
	
	
	public function __construct($obj){
		$this->data=$_REQUEST;
		$this->session=$_SESSION;
		$this->error=new error();
		$this->_init($obj);
	}
	public function __destruct(){
	
	}
	
	//METODO CHE GESTISCE IL TIPO DI VALIDAZIONE DA EFFETTUARE
	public function checkUser(){
		$this->status=false;
		if(isset($this->session["USERNAME"]) && $this->session["USERNAME"]){
			if($this->setGroups($this->getGroups($this->session["USERNAME"]))===true){
				$this->status=true;
				return true;
			}
			else
				return false;

		}
		if($this->action=="valida"){
			
			$ris=$this->validateUser();
		}
		elseif($this->action=="autentica")
			$ris=$this->authenticateUser();	
		else{
			$ris=false;
		}
		if(!$ris){
			return false;
		}
		else{
			$this->status=true;
			return true;
		}
	}
	
	//METODO CHE METTE IN SESSIONE LE INFORMAZIONI SUI GRUPPI
	public function setGroups($obj){
		if($obj instanceof error) return $obj;
		else if (is_array($obj)){
			$_SESSION["GROUPS"]=array_unique($obj);
		} else {
			$_SESSION["GROUPS"]=Array();
		}
		return true;
	}
	//METODO CHE METTE IN SESSIONE LE INFORMAZIONI SUI RUOLI
	public function setRoles($roles){
		if($roles instanceof error) return $roles;
		else
			$_SESSION["ROLES"]=$roles;
		return true;
	}
	//METODO CHE METTE IN SESSIONE LE INFORMAZIONI NECESSARIE
	function setInfo($activate,$username){
		switch($activate){
			case -1:
			case 0:
				$this->error->getError("A002");
				return false;
				break;
			default:
				$ris=$this->getGroups($username);
				//if(!$ris) 
				//	return false;
				$ris=$this->getRoles($username);
				if($ris instanceof error) 
					return false;
				$_SESSION['USERNAME'] = $username;
				$this->setGroups($this->groups);
				$this->setRoles($this->roles);
				return true;
				break;
			
			
				break;
		}
	}
	//Metodo che restituisce info su uno o più utenti
	public function getUsers($user,$mode){
		
	}
	//Metodo che restituisce l'elenco degli Utenti appartenenti ad un gruppo
	public function getUsersList($group,$mode){
	
	}
	public function getUserMapset(){
	
	}
	//Metodo che restituisce l'elenco dei Gruppi ai quali appartiene un Utente
	public function getGroupsList($user,$mode){
	
	}
	
	//Metodo che restituisce l'elenco degli amministratori locali del gisclient del progetto 
	public function getGisclientAdmin($project,$mode){
		
	}
	public function logout(){
		session_destroy();
		unset($_SESSION);
		session_start();
	}
	

	
	

}

?>
