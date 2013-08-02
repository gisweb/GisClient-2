<?php

//INCLUDERE ANCHE UN LOGIN !!!!!!!!!!!!!!!!!!!????????????????????????????
	session_start();
	require_once('../../config/config.php');
	require_once(ROOT_PATH.'lib/functions.php');

	$myMap = "MAPSET_".$_REQUEST["mapset"];
	$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$dbschema=DB_SCHEMA;
	
	if(isset($_REQUEST["suggest"])){
		$dbSchema = DB_SCHEMA;
		$inputString = $_REQUEST['suggest'];
		$qtfieldId = $_REQUEST["field"];
		$qtId = $_REQUEST["qt"];
			
		//Info layer: devo sempre richiedere le informazioni sul layer per verificare se ci sono filtri sul layer  
		$sql = "select catalog_path,layer.data,layer.data_unique,layer.data_filter,qt_filter,layer.mapset_filter from $dbSchema.qt inner join $dbSchema.layer using(layer_id) inner join $dbSchema.catalog  using (catalog_id) where qt.qt_id=$qtId;";
		print_debug($sql,null,'suggest');
		$db->sql_query($sql);
		$res = $db->sql_fetchrow();
		$aConnInfo = connInfofromPath($res["catalog_path"]);
		$datalayerConnection = $aConnInfo[0];//La stringa di connessione è sempre quella del layer
		$datalayerSchema = $aConnInfo[1];
		
		print_debug($aConnInfo,null,'suggest');
		$datalayerTable = $res["data"];
		$datalayerKey = $res["data_unique"];
		$datalayerFilter = '';
		//$sTable=$datalayerSchema?$datalayerSchema.".".$datalayerTable:$datalayerTable;
		$sTable=$datalayerSchema.".".$datalayerTable;
		
		//definizione alias della tabella o vista pricipale (nel caso l'utente abbia definito una vista)  (da valutare se ha senso)
		if(preg_match("|select (.+) from (.+)|i",$datalayerTable)) $sTable=$datalayerTable;
		
/*
		if($res["mapset_filter"]==1)
			$datalayerFilter=$_SESSION[$myMap]["FILTER"];
		if($res["data_filter"]){
			if($datalayerFilter) 
				$datalayerFilter.=" AND " . $res["data_filter"];
			else
				$datalayerFilter = $res["data_filter"];
		}
*/

		if($res["mapset_filter"]==1)
			$datalayerFilter=$_SESSION[$myMap]["FILTER"];
		if($res["qt_filter"]){
			if($datalayerFilter) 
				$datalayerFilter.=" AND " . $res["qt_filter"];
			else
				$datalayerFilter = $res["qt_filter"];
		}	
		if($res["data_filter"]){
			if($datalayerFilter) 
				$datalayerFilter.=" AND " . $res["data_filter"];
			else
				$datalayerFilter = $res["data_filter"];
		}


		if ($datalayerFilter){
			$datalayerFilter = "AND (".DATALAYER_ALIAS_TABLE.".$datalayerKey in (select $datalayerKey from $sTable where (" . $datalayerFilter . ")))";//SE C'E UN FILTRO APPLICATO AL LIVELLO LO APPLICO		
		}
		
		//print_debug($joinString,null,'xsearch');
		//Info campo oggetto di suggest
		$sql = "select qtfield.qtfield_id,qtfield_name,catalog_path,field_filter,qtrelation.qtrelation_name,qtrelation_id,data_field_1,data_field_2,data_field_3,table_field_1,table_field_2,table_field_3,table_name,catalog_path from $dbSchema.qtfield left join $dbSchema.qtrelation using (qtrelation_id) left join $dbSchema.catalog using (catalog_id) where qtfield.qtfield_id=$qtfieldId;";
		print_debug($sql,null,'suggest');
		$db->sql_query($sql);
		$qtField = $db->sql_fetchrow();
		if($qtField["qtrelation_id"]==0){
			$qtField["qtrelation_name"]=DATALAYER_ALIAS_TABLE;//alias per la tabella del livello
			$qtField["schema"]=$datalayerSchema;
			$qtField["table_name"]=$datalayerTable;
		}else{
			$aConnInfo = connInfofromPath($qtField["catalog_path"]);
			$qtField["schema"]=$aConnInfo[1];
		}
		
		// +++++++++++++++++ FILTRO AUTOSUGGEST ++++++++++++++++++++++++++++++++++//
		//Info campo che fa da filtro: ho passato una stringa di filtro a un campo che ha il campo filtro, devo cercare il campo di filtro stesso
		$qtfieldFilterId = $qtField["field_filter"];
		$qtfiltervalue = $_REQUEST["filtervalue"];
		$f = array();$joinList = array();
		$joinString = $sTable ." as " . DATALAYER_ALIAS_TABLE;
		
	
		if(isset($qtfieldFilterId) && $qtfiltervalue){
			$sql = "select qtfield.qtfield_id,qtfield_name,catalog_path,qtrelation.qtrelation_name,qtrelation_id,data_field_1,data_field_2,data_field_3,table_field_1,table_field_2,table_field_3,table_name from $dbSchema.qtfield left join $dbSchema.qtrelation using (qtrelation_id) left join $dbSchema.catalog using (catalog_id) where qtfield.qtfield_id=$qtfieldFilterId;";
			print_debug($sql,null,'suggest');
			$db->sql_query($sql);
			$qtFilterField = $db->sql_fetchrow();
			if($qtFilterField["qtrelation_id"]==0){
				$qtFilterField["qtrelation_name"]=DATALAYER_ALIAS_TABLE;//alias per la tabella del livello
				$qtFilterField["schema"]=$datalayerSchema;
				$qtFilterField["table_name"]=$datalayerTable;
			}else{
				$aConnInfo = connInfofromPath($qtFilterField["catalog_path"]);
				$qtFilterField["schema"]=$aConnInfo[1];
			}
			//caso più semplice il campo e il suo filtro stanno sulla stessa tabella che è quella del layer (nessun join)
			if($qtField["qtrelation_id"]==0 && $qtFilterField["qtrelation_id"]==0){
				$sqlQuery = "SELECT DISTINCT ". $qtField["qtfield_name"] ." FROM $datalayerSchema.$datalayerTable as ". DATALAYER_ALIAS_TABLE . " WHERE " . $qtFilterField["qtfield_name"]."='$qtfiltervalue' AND ". $qtField["qtfield_name"] ." ilike '%$inputString%' $datalayerFilter";
			}
			//entrambe le tabelle sono secondarie
			else{//if($qtField["qtrelation_id"]!=0 && $qtFilterField["qtrelation_id"]!=0){
				if(($qtField["data_field_1"])&&($qtField["table_field_1"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_1"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_1"];
				if(($qtField["data_field_2"])&&($qtField["table_field_2"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_2"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_2"];
				if(($qtField["data_field_3"])&&($qtField["table_field_3"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_3"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_3"];
				if( $joinList){//in questo caso qtField è sulla secondaria
					$joinFields=implode(" AND ",$joinList);$joinList=array();
					$joinString .= " inner join ". $qtField["schema"].".".$qtField["table_name"]." as \"". $qtField["qtrelation_name"]."\" on ($joinFields) ";
				}					
				if($qtFilterField["qtrelation_id"]!=$qtField["qtrelation_id"]){//il filtro sta su una tabella diversa
					if(($qtFilterField["data_field_1"])&&($qtFilterField["table_field_1"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtFilterField["data_field_1"]."=\"".$qtFilterField["qtrelation_name"]."\".".$qtFilterField["table_field_1"];
					if(($qtFilterField["data_field_2"])&&($qtFilterField["table_field_2"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtFilterField["data_field_2"]."=\"".$qtFilterField["qtrelation_name"]."\".".$qtFilterField["table_field_2"];
					if(($qtFilterField["data_field_3"])&&($qtFilterField["table_field_3"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtFilterField["data_field_3"]."=\"".$qtFilterField["qtrelation_name"]."\".".$qtFilterField["table_field_3"];			
					if( $joinList){//in questo caso qtFilterField è sulla secondaria
						$joinFields=implode(" AND ",$joinList);
						$joinString .= " inner join " .$qtFilterField["schema"].".".$qtFilterField["table_name"]." as \"". $qtFilterField["qtrelation_name"]."\" on ($joinFields)";
					}
				}
				$sqlQuery = "SELECT DISTINCT \"". $qtField["qtrelation_name"]."\"." . $qtField["qtfield_name"] ." FROM " .$joinString ." WHERE \"". $qtFilterField["qtrelation_name"]."\".".$qtFilterField["qtfield_name"]."='$qtfiltervalue' AND \"". $qtField["qtrelation_name"]."\".". $qtField["qtfield_name"] ." ilike '%$inputString%' $datalayerFilter";
			}
			//una delle 2 tabelle è quella del layer
			/*
			else{
				if(($qtField["data_field_1"])&&($qtField["table_field_1"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_1"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_1"];
				if(($qtField["data_field_2"])&&($qtField["table_field_2"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_2"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_2"];
				if(($qtField["data_field_3"])&&($qtField["table_field_3"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_3"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_3"];
				if(($qtFilterField["data_field_1"])&&($qtFilterField["table_field_1"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtFilterField["data_field_1"]."=\"".$qtFilterField["qtrelation_name"]."\".".$qtFilterField["table_field_1"];
				if(($qtFilterField["data_field_2"])&&($qtFilterField["table_field_2"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtFilterField["data_field_2"]."=\"".$qtFilterField["qtrelation_name"]."\".".$qtFilterField["table_field_2"];
				if(($qtFilterField["data_field_3"])&&($qtFilterField["table_field_3"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtFilterField["data_field_3"]."=\"".$qtFilterField["qtrelation_name"]."\".".$qtFilterField["table_field_3"];			
				$joinFields=implode(" AND ",$joinList);
				$joinString = ($qtField["qtrelation_id"]==0)?$datalayerTable:$qtField["schema"].".".$qtField["table_name"]." as \"".$qtField["qtrelation_name"]."\" ";
				$joinString .= "inner join ". $qtFilterField["schema"].".".$qtFilterField["table_name"]." as \"". $qtFilterField["qtrelation_name"]."\" on ($joinFields)";
				$sqlQuery = "SELECT DISTINCT \"". $qtField["qtrelation_name"]."\"." . $qtField["qtfield_name"] ." FROM " .$joinString ." WHERE \"". $qtFilterField["qtrelation_name"]."\".".$qtFilterField["qtfield_name"]."='$qtfiltervalue' AND \"". $qtField["qtrelation_name"]."\".". $qtField["qtfield_name"] ." ilike '%$inputString%' $datalayerFilter";
			}*/
		}
		//niente filtro
		else{
			if($qtField["qtrelation_id"]!=0){//il campo oggetto di autosuggest è su tabella secondaria
				if(($qtField["data_field_1"])&&($qtField["table_field_1"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_1"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_1"];
				if(($qtField["data_field_2"])&&($qtField["table_field_2"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_2"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_2"];
				if(($qtField["data_field_3"])&&($qtField["table_field_3"])) $joinList[]=DATALAYER_ALIAS_TABLE.".".$qtField["data_field_3"]."=\"".$qtField["qtrelation_name"]."\".".$qtField["table_field_3"];
				$joinFields=implode(" AND ",$joinList);
				$joinString .= " inner join ". $qtField["schema"].".".$qtField["table_name"]." as \"". $qtField["qtrelation_name"]."\" on ($joinFields) ";
				$sqlQuery = "SELECT DISTINCT \"". $qtField["qtrelation_name"]."\"." . $qtField["qtfield_name"] ." FROM " .$joinString ." WHERE \"".$qtField["qtrelation_name"]."\".". $qtField["qtfield_name"] ." ilike '%$inputString%' $datalayerFilter";
			}
			else{//caso elementare: il campo è su tabella del layer
				$sqlQuery = "SELECT DISTINCT ". $qtField["qtfield_name"] ." FROM " . $qtField["schema"].".". $qtField["table_name"] ." as " .DATALAYER_ALIAS_TABLE. " WHERE ". $qtField["qtfield_name"] ." ilike '%$inputString%' $datalayerFilter";
			}
		}

		$aValues = array();
		if(!$dbData = pg_connect($datalayerConnection)) print "errore da gestire";	
		$sqlQuery.= ' ORDER BY 1';
		$result = pg_query($dbData, $sqlQuery);

		while ($row = pg_fetch_row($result)) {
			$aValues[] = $row[0];
		}
		
		$aResults["results"]=$aValues;
		jsonString($aValues);
		return;
	}
	
?>