
<html>
<head>
<title>GisClient Setup</title>
<style>

FIELDSET.searchInput {
 	margin-left:5px;
	margin-top:5px;
}

FIELDSET.searchInput SELECT {
	width:20em;
}

FIELDSET.searchInput INPUT {
  left:555px;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 14px;  
  line-height: 20px;
  font-weight: normal;
  color: #666666;
  margin-left: 4px;
  font-weight:bold;
}

FIELDSET.searchInput LEGEND {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 14px;  
  line-height: 20px;
  font-weight: normal;
  color: #666666;
  margin-left: 4px;
  font-weight:bold;
}

FIELDSET.searchInput LABEL {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 14px;  
  line-height: 20px;
  font-weight: normal;
  color: #666666;
  margin-left: 4px;
}

</style>
</head>
<body>


<?php if (!isset($_POST["azione"])){
	if(file_exists("../../../config/config.php")) die ("Configurazione presente");
?>
<form action="index.php" method="post">
	<fieldset  class="searchInput">
	<legend>Setup:</legend>

	Superutente progetti: <br /><input  style="left:400px" type="text" name="<@SUPERUSER@>" value="Admin" /><br />
	Password superutente progetti:<br /><input type="text" name="<@SUPERPWD@>" value="Admin" /><br />
	Database: <br /><input type="text" name="<@DBNAME@>" value="gisclient_demo" /><br />
	Schema Author: <br /><input type="text" name="<@DBSCHEMA@>" value="gisclient_21" /><br />
	Amministratore database: <br /><input type="text" name="<@DBADMIN@>" value="Admin" /><br />
	Password amministratore database: <br /><input type="text" name="<@DBPWD@>" value="Admin" /><br />
	Utente mapfile: <br /><input type="text" name="<@MAPUSER@>" value="mapserver" /><br />
	Password utente mapfile: <br /><input type="text" name="<@MAPPWD@>" value="mapserver" /><br />
	DB Host: <br /><input type="text" name="<@DBHOST@>" value="127.0.0.1" /><br />
	DB Porta: <br /><input type="text" name="<@DBPORT@>" value="5432" /><br />
	Set caratteri: <br /><input type="text" name="<@CHARSET@>" value="UTF-8" /><br />
	Cartella immagini: <br /><input type="text" name="<@IMAGEPATH@>" value="/tmp/ms_tmp/" /><br />
	Url immagini: <br /><input type="text" name="<@IMAGEURL@>" value="/tmp/" /><br />
	<br>
	<input type="submit" style="width:150px" value="Invia" name="azione">
	</fieldset>
</form>

<?php

}else{

	if(file_exists("../../../config/config.php")) die ("Configurazione presente");
	
	$configFile=file_get_contents("config.setup"); 
	$configdbFile=file_get_contents("config.db.setup"); 
	$schemaFile=file_get_contents("schema.setup"); 

	chdir("../../../");
	$rootPath = str_replace('\\','/',getcwd()) . "/";

	//VERIFICARE L'ESISTENZA -> MESSAGGIO DI ERRORE
	$aSetup = $_POST;
	$aSetup["<@ROOTPATH@>"] = $rootPath;

	print('<pre>');
	print_r($aSetup);


	//Connessione al db, verifica esistenza (messaggio di errore) e creazione dello schema del gisclient con i dati preinseriti nel file 
	$connString = "user=".$aSetup["<@DBADMIN@>"]." password=".$aSetup["<@DBPWD@>"]." dbname=".$aSetup["<@DBNAME@>"]." host=".$aSetup["<@DBHOST@>"]." port=".$aSetup["<@DBPORT@>"];
	echo $connString."\n" ;
	if(!($db = pg_connect($connString))) die ("Connessione al db ".$aSetup["<@DBNAME@>"]." fallita");
	//Creo lo schema dell'author con il nome che voglio ed eseguo il file schema.setup con le impostazioni desiderate (lista di font, funzioni, tabelle accessorie x plugin.. ecc)
	$schemaFile = str_replace("<@DBSCHEMA@>",$aSetup["<@DBSCHEMA@>"],$schemaFile);
	
	$result=pg_query($db,$schemaFile);
	if($result)
		echo "Creazione schema vuoto OK\n\n";
	else{
		echo "Creazione schema vuoto fallita!\n\n";//(Magari verificare prima se c'è)
		echo pg_result_error($result);
	}
	// see http://www.postgresql.org/docs/9.0/static/catalog-pg-authid.html for
    // any further information
    $sqlUserExists="SELECT rolcanlogin, rolpassword, 'md5'||md5('".pg_escape_string($aSetup["<@MAPPWD@>"])."'||rolname) AS password_trial FROM pg_catalog.pg_authid WHERE rolname='".pg_escape_string($aSetup["<@MAPUSER@>"])."'";
    if (($result=pg_query($db,$sqlUserExists)) === FALSE){
		echo "Impossibile richiedere informazioni sull'utente db {$aSetup["<@MAPUSER@>"]}!\n\n";
		echo pg_result_error($result);
        die();
	}
    $mapUserFound = false;
    while($row = pg_fetch_array($result)){
		if(!$row['rolcanlogin']){
			echo "L'utente {$aSetup["<@MAPUSER@>"]} esiste, ma non è un utente login";
			die();
        }else if($row['rolpassword'] != $row['password_trial']) {
			echo "L'utente {$aSetup["<@MAPUSER@>"]} esiste, ma le password non coincide con quella esiste in db";
			die();
		}else{
    		$mapUserFound = true;
		}
	}
    if (!$mapUserFound) {
	//CREAZIONE UTENTE SE NON C'e'
		$sqlUser="CREATE ROLE ".$aSetup["<@MAPUSER@>"]." LOGIN ENCRYPTED PASSWORD '".$aSetup["<@MAPPWD@>"]."' NOSUPERUSER NOINHERIT NOCREATEDB NOCREATEROLE;";
		$result=pg_query($db,$sqlUser);
		if($result)
			echo "Creazione utente OK\n\n";
		else{
			echo "Creazione utente fallita!\n\n";//(Magari verificare prima se c'è)
			echo pg_result_error($result);
		}
	}
		

	$configFile = str_replace(array_keys($aSetup),array_values($aSetup),$configFile);
	$configdbFile = str_replace(array_keys($aSetup),array_values($aSetup),$configdbFile);

	$fp = fopen($rootPath."/config/config.php", "w");
	if ($fp === FALSE) {
		die("Non posso scrivere il file ".$rootPath."/config/config.php");
	} else {
		fwrite($fp, $configFile); 
		fclose($fp);
	}
	$fp = fopen($rootPath."/config/config.db.php", "w");
	if ($fp === FALSE) {
		die("Non posso scrivere il file ".$rootPath."/config/config.db.php");
	} else {
		fwrite($fp, $configdbFile); 
		fclose($fp);
	}
	print("
	<p><a href=\"../index.php\">Vai alla pagina dell'Author</a></p>
	<p>Ricorda di rimuovere lo schema setup</p>
	");
}

?>

</body>
</html>
