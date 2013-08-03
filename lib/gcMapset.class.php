<?php
/*
GisClient map browser

Copyright (C) 2008 - 2009  Roberto Starnini - Gis & Web S.r.l. -info@gisweb.it

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/
class GCMapset{
	public $db;
	public $mapError=0;
	public $staticReference;
	public $utmZone;
	public $utmSouthemi;
	public $srsName;
	public $selectedqTheme;
	public $selectedQt;
	public $selectedObj;

	function __construct ($mapsetName){
		$this->mapsetName=$mapsetName;
		$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$this->db->db_connect_id) $this->mapError=100;
	}
	function __destruct (){
		$this->db->sql_close();
		unset($this->db);
		unset($this->mapsetName);
		unset($this->mapError);
	}

	function initMapset(){
	
		//Inizializza la sessione dell'utente
		//$this->_initUserSession();
		//include_once "../../include/logingc.php";
		
		//Inizializza la mappa mettendo in sessione i valori
		$dbSchema=DB_SCHEMA;
		if ($this->mapError==100) return;
		$aGroups = array(); 
		if (isset($_SESSION["GROUPS"])) {
			foreach ($_SESSION["GROUPS"] as $grp) $aGroups[] = addslashes($grp); 
		}
		else { 
			$aGroups[] = 'ANONYMOUS'; 
		} 
		
		
		$userGroup="'".implode("','",$aGroups)."'";
		
		$myMap = "MAPSET_".$this->mapsetName;
		if(isset($_REQUEST["reset"])) unset ($_SESSION[$myMap]);
		unset($_SESSION[$myMap]["CUSTOM_OBJECT"]);
		
		//Metto in sessione i possibili valori di labelitem x layer e i diversi prj4string e  i layer soggetti al mapset_filter
		$sql="select layergroup_id,layer_name,data_srid,mapset_filter,language_layer.labelitem,language_layer.language_id from $dbSchema.layer inner join $dbSchema.mapset_layergroup using(layergroup_id) left join $dbSchema.language_layer using (layer_id) where mapset_name='".$this->mapsetName."'";
		$this->db->sql_query ($sql);
		$aLabel=array();
		$aSrid=array();
		$aFilterLayer=array();
		$langId=(isset($_REQUEST["language"]))?$_REQUEST["language"]:null;
		while($row = $this->db->sql_fetchrow()){
			if($row["labelitem"]) $aLabel[$row["layergroup_id"]][$row["layer_name"]][$row["language_id"]] = $row["labelitem"];
			if($row["data_srid"]>0 && !in_array($row["data_srid"],$aSrid)) $aSrid[]=$row["data_srid"];
			if($row["mapset_filter"]==1){
				if(!isset($aFilterLayer[$row["layergroup_id"]])) $aFilterLayer[$row["layergroup_id"]]=array();
				if(!in_array($row["layer_name"],$aFilterLayer[$row["layergroup_id"]])) $aFilterLayer[$row["layergroup_id"]][] = $row["layer_name"];
			}
		}	
		
		if($aLabel) $_SESSION[$myMap]["LABELITEM"] = $aLabel;
		print_debug($sql,null,'titoli');
		$sqlaltTitle="left join (select mapset_name,title as mapset_title_alt from $dbSchema.language_mapset where language_id='$langId') as language_table using (mapset_name) ";
		//Se l'utente è un author non controllo		
		
		//VERIFICARE DOVE METTERE LA TABELLA MAPSET GROUPS
		//********************************************************************
		
		//Accesso a funzioni di editing e redline
		$sql = "select count(*) from $dbSchema.mapset_groups where group_name in ($userGroup) and edit=1 and mapset_name='".$this->mapsetName."'"; 
		$this->db->sql_query ($sql);
		$row = $this->db->sql_fetchrow();
		$this->edit = $row[0];

		$sql = "select count(*) from $dbSchema.mapset_groups where group_name in ($userGroup) and redline=1 and mapset_name='".$this->mapsetName."'"; 
		$this->db->sql_query ($sql);
		$row = $this->db->sql_fetchrow();
		$this->redline = $row[0];
		
		$userSchema = USER_SCHEMA;
		if(!isset($userSchema) || (isset($_SESSION["USERNAME"]) && $_SESSION["USERNAME"]==SUPER_USER)){
			$sql="select distinct mapset.*, mapset_title_alt, project.*,sizeunits_name from $dbSchema.project inner join $dbSchema.mapset using (project_name) left join $dbSchema.e_sizeunits using(sizeunits_id) $sqlaltTitle where mapset.mapset_name='".$this->mapsetName."'";
			$this->edit=1;$this->redline=1;
		}
		else{
			$userName = isset($_SESSION["USERNAME"])?$_SESSION["USERNAME"]:'';
			$sqlLocalAdmin = "select project_name from $dbSchema.project_admin where username = '$userName'";
			$sql = "select distinct mapset.*, mapset_title_alt, project.*,sizeunits_name from $dbSchema.project inner join $dbSchema.mapset using (project_name) left join $dbSchema.mapset_groups using (mapset_name) left join $dbSchema.e_sizeunits using(sizeunits_id) $sqlaltTitle where (private=0 or project_name in ($sqlLocalAdmin) or group_name in ($userGroup)) and mapset.mapset_name='".$this->mapsetName."'";
		}
		
		print_debug($sql,null,'mapset');
		$this->db->sql_query ($sql);
		$result = $this->db->sql_fetchrow();
		if(!$result){
			$this->mapError=110;//L'utente non ha accesso al mapset
			return;
		}
        
		$baseURL = $result["base_url"];
		$mapsetSRID = $result["mapset_srid"];
		//Se SRID del mapset non è presente in elenco lo aggiungo
		if($mapsetSRID>0 && !in_array($mapsetSRID,$aSrid)) $aSrid[]=$mapsetSRID;
        
        $_SESSION[$myMap]["PROJECT_NAME"] = $result["project_name"];
        $_SESSION[$myMap]["TEMPLATE"] = $result["template"];
		
		$_SESSION[$myMap]["LANGUAGE"] = $langId;
		$_SESSION[$myMap]["SRID"] = $mapsetSRID ? $mapsetSRID : -1;
		$_SESSION[$myMap]["FILTER"] = (isset($_REQUEST["filter"]) && $_REQUEST["filter"])?stripslashes ($_REQUEST["filter"]):$result["filter_data"];
		$_SESSION[$myMap]["FILTER_LAYER"] = $aFilterLayer;
		$_SESSION[$myMap]["READLINE_COLOR"] = $result["readline_color"];
		
		print_debug($_SESSION[$myMap]["FILTER"],null,'session');		


		//Metto in sessione la definizione degli SRS usati
		$sql="select srid,srtext,proj4text,param from spatial_ref_sys left join (select srid,param from $dbSchema.project_srs  where project_name='".$result["project_name"]."') as foo using(srid) where  srid in (".implode(",",$aSrid).");";
		$this->db->sql_query($sql);
		print_debug($sql,null,'srs');
		while($row = $this->db->sql_fetchrow()) {
			$srid=$row[0];
			//Setto le proprietà per la proiezione del mapset
			if(intval($mapsetSRID) == intval($srid)){
				$v=explode(",",$row[1]);
				$this->srsName = substr($v[0],8,-1);
				$projparams = explode("+",$row[2]);
				for($i=0;$i<count($projparams);$i++){
					$v=explode('=',$projparams[$i]);		
					print_debug($v,null,'mapsetrsr');
					if(trim($v[0])=='proj' && trim($projparams[$i][1])=='utm') $utmProj=true;
					if(trim($v[0])=='zone') $this->utmZone = intval(trim($v[1]));
					if(trim($v[0])=='ellps') $this->utmEllps=trim($v[1]);
					$this->utmSouthemi = (trim($v[0])=='south'); 
				}
			}
			//metto in sessione le stringhe di trasformazione
			$_SESSION[$myMap]["SRS"][$srid] = trim($row[2]);
			if($row[3]) $_SESSION[$myMap]["SRS"][$srid] .= ' +towgs84=' . trim($row[3]);
		}

		//Colori per la selezione degli oggetti
		$color = $result["sel_user_color"]?$result["sel_user_color"]:COLOR_SELECTION;
		$selectionColor = preg_split('/[\s,]+/',$color);
		$this->selColor = "RGB(".$selectionColor[0].",".$selectionColor[1].",".$selectionColor[2].")";
		$_SESSION[$myMap]["SELECTION_COLOR"] = $selectionColor;
		
		if(isset($_REQUEST["mapsetextent"])){
			$extent=explode(",",trim($_REQUEST["mapsetextent"]));
			$_SESSION[$myMap]["REF_MAP"]["EXTENT"] = $extent;//Estensione iniziale reference
		}else
			$extent=$result["mapset_extent"]?explode(" ",trim($result["mapset_extent"])):explode(" ",trim($result["extent"]));

		if(!$extent){
			$this->mapError=130;//Manca l'estensione della mappa e del progetto
			return;
		}	
		
		$this->mapUnits = $result["sizeunits_name"];
		$this->geocoord = intval($result["geocoord"]);
		//Visualizzo le coordinate geografiche solo per sistemi UTM in WGS84  TODO: Datum diversi

		
		$this->printSize = $result["page_size"]?explode(",",$result["page_size"]):null;
		$this->imageRes = $result["dl_image_res"]?explode(",",$result["dl_image_res"]):null;
		$this->mapsetTitle = $result["mapset_title_alt"]?$result["mapset_title_alt"]:$result["mapset_title"];
	
		
		//Se passo alla mappa un'estensione mapset metto in sessione il valore dell'estensione del mapset 
		$_SESSION[$myMap]["MAPSET_EXTENT"] = $extent;
			
		//Se passo alla mappa un'estensione metto in sessione il valore dell'estensione corrente 
		if(isset($_REQUEST["extent"]))
			$_SESSION[$myMap]["MAP_EXTENT"]=explode(",",$_REQUEST["extent"]);//estensione corrente
		
		//Se ho già l'esensione corrente della mappa non la cambio
		if(!isset($_SESSION[$myMap]["MAP_EXTENT"]))
			$_SESSION[$myMap]["MAP_EXTENT"] = $extent;//estensione corrente
		

		if($result["static_reference"]){
			$this->staticReference = "images/reference/ref_".$this->mapsetName.".png";
			$_SESSION[$myMap]["REF_MAP"]["WIDTH"] = $_REQUEST["referenceW"];
			$_SESSION[$myMap]["REF_MAP"]["HEIGHT"] = $_REQUEST["referenceH"];
		}

		//Se ho già l'esensione corrente del reference non la cambio
		if(!isset($_SESSION[$myMap]["REF_MAP"]["EXTENT"]))
			$_SESSION[$myMap]["REF_MAP"]["EXTENT"] = $result["refmap_extent"]?explode(" ",trim($result["refmap_extent"])):$extent;//Estensione iniziale reference
/*
		//Se ho già l'esensione corrente del reference non la cambio
		if(!isset($_SESSION[$myMap]["REF_MAP"]["EXTENT"])){
			if($_REQUEST["mapsetextent"])
				$_SESSION[$myMap]["REF_MAP"]["EXTENT"] = $_SESSION[$myMap]["MAPSET_EXTENT"];
			else
				$_SESSION[$myMap]["REF_MAP"]["EXTENT"] = $result["refmap_extent"]?explode(" ",trim($result["refmap_extent"])):$extent;//Estensione iniziale reference
			
		}
	
*/	
		//Se devo mettere la scritta sulla mappa metto in sessione le impostazioni
		if($result["imagelabel"]){
			$_SESSION[$myMap]["IMAGELABEL"]["font"]= $result["imagelabel_font"];
			$_SESSION[$myMap]["IMAGELABEL"]["text"]= $result["imagelabel_text"];
			$_SESSION[$myMap]["IMAGELABEL"]["size"]= $result["imagelabel_size"];
			$_SESSION[$myMap]["IMAGELABEL"]["color"]= $result["imagelabel_color"];			
			$_SESSION[$myMap]["IMAGELABEL"]["offset_x"]= $result["imagelabel_offset_x"];
			$_SESSION[$myMap]["IMAGELABEL"]["offset_y"]= $result["imagelabel_offset_y"];
			$_SESSION[$myMap]["IMAGELABEL"]["position"]= $result["imagelabel_position"];
		}
		
		//carico le informazioni dei layergroup
		//Elenco delle classi 
		$sqlaltTitle="left join (select class_id,title as class_title_alt,template as template_alt,link as link_alt from $dbSchema.language_class where language_id='$langId') as language_table using(class_id) ";
		$sql="select layergroup_id,static,class_id,class_name,class_title,class_title_alt,class_link,catalog_url,catalog_path,connection_type from $dbSchema.class inner join $dbSchema.layer using (layer_id) inner join $dbSchema.catalog using (catalog_id) inner join $dbSchema.mapset_layergroup using (layergroup_id) $sqlaltTitle where mapset_layergroup.mapset_name='".$this->mapsetName."' and legendtype_id=1 order by layer_order,class_order,class_title";
		print_debug($sql,null,'titoli');
		$this->db->sql_query ($sql);
		$isDynamicLayer=array();
		$aInfoLink=array();
		while($row = $this->db->sql_fetchrow()){
			//$classid=intval($row["class_id"]);	
			$classLink='';
			$classtitle=$row["class_title_alt"]?$row["class_title_alt"]:($row["class_title"]?$row["class_title"]:$row["class_name"]);
			$layergroupid=$row["layergroup_id"];
			if($row["class_link"]) $classLink=setLink($row["class_link"],"name=".$row["class_name"],$row["catalog_path"],$row["catalog_url"]);

			$aClass[$row["layergroup_id"]][] = array($classtitle,$classLink);
			$aInfoLink[$layergroupid]["catalog_path"]=$row["catalog_path"];
			$aInfoLink[$layergroupid]["catalog_url"]=$row["catalog_url"];
			$isDynamicLayer[$layergroupid]=(isset($isDynamicLayer[$layergroupid]) && $isDynamicLayer[$layergroupid])?true:($row["static"]==0);
		}
		
		//Elenco delle legende WMS
		$sql="select layergroup_id,catalog_path,metadata from $dbSchema.layer inner join $dbSchema.catalog using(catalog_id) inner join $dbSchema.mapset_layergroup using (layergroup_id) where mapset_name='".$this->mapsetName."' and connection_type=7;";
		$this->db->sql_query ($sql);
		print_debug($sql,null,'titoli');
		$wmsLegend=array();
		while($row = $this->db->sql_fetchrow()){
			$mswlist = explode("\n",$row["metadata"]);
			$url=$row["catalog_path"];
			$a=array();
			foreach($mswlist as $w){
				$regexp="|\"(.+)\"([ ]+)\"(.+)\"|Ui";
				preg_match($regexp,$w,$l);
				eval("\$a['".$l[1]."']='".$l[3]."';");
			}
			if(strpos($url,'?')===false)
				$url.='?';
			elseif(substr($url,-1)!='&')
				$url.='&';
			$url.="SERVICE=WMS&VERSION=".(isset($a["wms_server_version"])?$a["wms_server_version"]:'1.1.1');
			$getLegend=$url."&REQUEST=GetLegendGraphic&LAYER=".(isset($a["wms_name"])?$a["wms_name"]:'')."&Format=".(isset($a["wms_format"])?$a["wms_format"]:'image/png');
			$getcapabilities=$url."&REQUEST=GetCapabilities";
			$wmsLegend[$row["layergroup_id"]][]=array($getLegend,$getcapabilities);
		}

		//Elenco dei check
		$sqlaltTitle="left join (select theme_id,title as theme_title_alt,link as link_alt from $dbSchema.language_theme where language_id='$langId') as language_theme using (theme_id) ";
		$sqlaltTitle.="left join (select layergroup_id,title as layergroup_title_alt,link as link_alt from $dbSchema.language_layergroup where language_id='$langId') as language_layergroup using(layergroup_id) ";		
		$sql = "select layergroup_id, layergroup.layergroup_name, layergroup_title, theme_title_alt, layergroup_title_alt, layergroup_link, multi, mapset_layergroup.status, mapset_layergroup.refmap,theme_id, theme.theme_name, theme_title, theme_link from ($dbSchema.layergroup inner join $dbSchema.mapset_layergroup using (layergroup_id)) inner join $dbSchema.theme using(theme_id) $sqlaltTitle where layergroup.hidden=0 and mapset_layergroup.mapset_name='".$this->mapsetName."' order by theme.theme_order,theme.theme_title,layergroup.layergroup_order,layergroup.layergroup_title;"; 
		print_debug($sql,null,'titoli');
		$this->db->sql_query ($sql);
		if($this->db->sql_numrows()==0){
			$this->mapError=200;//Mancano i layers
			echo 'NO LAYERS';
			return;
		}	
		$allGroups = array();
		$aThemeOpen = array();
		$aGroupOn = array();
		$aRefGroupOn = array();
		while($aLayergroup = $this->db->sql_fetchrow()){
			$theme_id = intval($aLayergroup["theme_id"]);
			$layergroupId = intval($aLayergroup["layergroup_id"]);				
			$layergroupName = str_replace(" ","_",$aLayergroup["layergroup_name"])."_".$layergroupId;
			$grpMulti = intval($aLayergroup["multi"]);		
			$allGroups[$layergroupId] = $layergroupName;
			if($aLayergroup["refmap"]==1) $aRefGroupOn[]=$layergroupId;			
			if($aLayergroup["status"]==1){
				$aGroupOn[]=intval($layergroupId);
				if (!in_array($theme_id,$aThemeOpen)) $aThemeOpen[]=intval($theme_id);//per ogni livello acceso aggiungo il tema a quelli aperti
			}
			
			//preparazione dell'array x la gestione dei layers
			//per i layergroup e i temi uso la descrizione se presente altrimenti il nome
			$themeLink='';$grpLink='';$grpClass=array();
			$themeTitle = $aLayergroup["theme_title_alt"]?$aLayergroup["theme_title_alt"]:($aLayergroup["theme_title"]?$aLayergroup["theme_title"]:$aLayergroup["theme_name"]);
			$grpTitle = $aLayergroup["layergroup_title_alt"]?$aLayergroup["layergroup_title_alt"]:($aLayergroup["layergroup_title"]?$aLayergroup["layergroup_title"]:$aLayergroup["layergroup_name"]);
			if(isset($isDynamicLayer[$layergroupId]) && $isDynamicLayer[$layergroupId]==true) $grpTitle.=" (*)";
			if(isset($aLayergroup["theme_link"])) $themeLink = setLink($aLayergroup["theme_link"],"name=".$aLayergroup["theme_name"],$aInfoLink[$layergroupId]["catalog_url"],$baseURL);
			if(isset($aLayergroup["layergroup_link"])) $grpLink = setLink($aLayergroup["layergroup_link"],"name=".$aLayergroup["layergroup_name"],$aInfoLink[$layergroupId]["catalog_url"],$baseURL);
			if(isset($aClass[$layergroupId])) $grpClass = $aClass[$layergroupId];
			if(isset($wmsLegend[$layergroupId])) $grpClass = $wmsLegend[$layergroupId];
			$tocLayers[$theme_id]["theme"] = array($theme_id,$themeTitle,$themeLink);
			$tocLayers[$theme_id]["group"][] = array($layergroupId,$grpMulti,$grpTitle,$grpLink,$grpClass); 
		}
		
		foreach($tocLayers as $aTheme){
			$this->tocLayers[]=array($aTheme["theme"][0],$aTheme["theme"][1],$aTheme["theme"][2],$aTheme["group"]);
		}
		print_debug($this->tocLayers,null,'layertree');
		
		//Metto in sessione l'array dei layergroup con il relativo stato
		$_SESSION[$myMap]["LAYERGROUPS"] = $allGroups;
		
		//se esistono gia i layer accesi e i temi aperti non li sovrascrivo
		if(!isset($_SESSION[$myMap]["GROUPS_ON"])){
			$_SESSION[$myMap]["GROUPS_ON"] = $aGroupOn;
			$_SESSION[$myMap]["THEME_OPEN"] = $aThemeOpen;
		}
		$_SESSION[$myMap]["REF_MAP"]["GROUPS"] = $aRefGroupOn;	

		//carico la lista dei temi per delle ricerche definite dai query_templates  disponibili all'utente:
		//$sql="select distinct theme_id,theme_title,theme_order from $dbSchema.theme inner join $dbSchema.layergroup using(theme_id) inner join $dbSchema.mapset_layergroup using(layergroup_id)inner join $dbSchema.qt using (theme_id) where mapset_name='".$this->mapsetName."' order by theme_order;";

		$sqlaltTitle="left join (select theme_id,title as theme_title_alt from $dbSchema.language_theme where language_id='$langId') as language_table using (theme_id) ";
		$sql="select distinct theme_id,theme_name,theme_title,theme_title_alt, theme_order from ($dbSchema.qt inner join $dbSchema.theme using (theme_id)) inner join $dbSchema.mapset_qt using(qt_id) $sqlaltTitle where mapset_name='".$this->mapsetName."' order by theme_order;";
		print_debug($sql,null,'temiricerca');
		$this->db->sql_query ($sql);
		$this->queryThemes = $this->db->sql_fetchrowset();	
		//$this->initMode='map';
		
		//Se passo un modello di ricerca in avvio ricavo il corrispondente tema
		if(isset($_REQUEST["qt"]) && $_REQUEST["qt"]){
			$this->selectedQt = intval($_REQUEST["qt"]);
			$sql="select theme_id from $dbSchema.qt where qt_id=".$_REQUEST["qt"];
			print_debug($sql,null,'temiricerca');
			$this->db->sql_query ($sql);
			$this->selectedqTheme = intval($this->db->sql_fetchfield("theme_id"));
		}
		//Se passo l'elenco degli oggetti da zoomare
		if(isset($_REQUEST["objid"]) && $_REQUEST["objid"]){
			$this->selectedObj = $_REQUEST["objid"];
		}
		//Modalità di avvio (editobject o addobject)
		//if($_REQUEST["mode"]) $this->initMode=$_REQUEST["mode"];

		
		//gruppi di selezione
		$sqlaltTitle="left join (select selgroup_id,title as selgroup_title_alt from $dbSchema.language_selgroup where language_id='$langId') as language_table using (selgroup_id) ";
		$sql="select distinct selgroup_id,selgroup_name,selgroup_title,selgroup_title_alt, selgroup_order from $dbSchema.selgroup inner join ($dbSchema.qt_selgroup inner join $dbSchema.mapset_qt using(qt_id)) using (selgroup_id) $sqlaltTitle where mapset_name='".$this->mapsetName."' order by selgroup_order;";
		print_debug($sql,null,'selgroup');
		$this->db->sql_query ($sql);
		$this->selGroupList = $this->db->sql_fetchrowset();
		
		//Interrogazioni WMS
		$sqlaltTitle="left join (select layergroup_id,title as layergroup_title_alt from $dbSchema.language_layergroup where language_id='$langId') as language_table using (layergroup_id) ";
		$sql="select layergroup_id,layergroup_title,layergroup_title_alt,layer_name,catalog_path,metadata from $dbSchema.layergroup inner join $dbSchema.layer using (layergroup_id) inner join $dbSchema.catalog using(catalog_id) inner join $dbSchema.mapset_layergroup using(layergroup_id) $sqlaltTitle where connection_type=7 and mapset_name='".$this->mapsetName."' order by layergroup_order;";
		print_debug($sql,null,'layergroupwms');
		$this->db->sql_query ($sql);
		$this->selWMSList = $this->db->sql_fetchrowset();
		
		
		
		
/* EDIT LIST RIMOSSO		
		//Verificare che il gruppo di utenti abbia l'autorizzazione all'edit
		//layer per aggiungi oggetto
		$sqlaltTitle="left join (select qt_id,name as qt_name_alt from $dbSchema.language_qt where language_id='$langId') as language_table using(qt_id) ";
		$sql="select qt_id,layertype_id,qt_name_alt,qt.qt_name from $dbSchema.qt inner join $dbSchema.mapset_qt using (qt_id) inner join $dbSchema.layer using (layer_id) $sqlaltTitle where qt.edit_url is not null and mapset_name='".$this->mapsetName."' order by qt_order;";
		print_debug($sql,null,'selgroup');
		$this->db->sql_query ($sql);
		$this->addobjectList =  $this->db->sql_fetchrowset();
*/
		
		//Fine
		$this->mapError=0;
	}
	

	function getqueryThemes(){
		$aOption=array();
		for($i=0;$i<count($this->queryThemes);$i++){
			$option = $this->queryThemes[$i];
			$themeTitle = $option["theme_title_alt"]?$option["theme_title_alt"]:($option["theme_title"]?$option["theme_title"]:$option["theme_name"]);
			$aOption[$i] = array(intval($option["theme_id"]),$themeTitle);
		}
		return $aOption;
	}

	function getselGroupList(){
		$aOption=array();
		for($i=0;$i<count($this->selGroupList);$i++){
			$option = $this->selGroupList[$i];
			$id=$option["selgroup_id"];
			if($option["selgroup_order"]<0) $id=-$id;
			$selgroupName=$option["selgroup_title_alt"]?$option["selgroup_title_alt"]:($option["selgroup_title"]?$option["selgroup_title"]:$option["selgroup_name"]);
			$aOption[$i] = array(intval($id),$selgroupName);
		}
		return $aOption;
	}
		
	function getselWMSList(){
		$aOption=array();
		for($i=0;$i<count($this->selWMSList);$i++){
			$option = $this->selWMSList[$i];
			$id=$option["layer_name"];
			
			$a=array();
			$a["grpid"]=$option["layergroup_id"];
			$a["layername"]=$option["layer_name"];
			//$a["url"]=$option["catalog_path"];
			$a["title"]=$option["layergroup_title_alt"]?$option["layergroup_title_alt"]:($option["layergroup_title"]?$option["layergroup_title"]:$option["layergroup_name"]);
			/*$mswlist = explode("\n",$option["metadata"]);
			foreach($mswlist as $w){
				$regexp="|\"(.+)\"([ ]+)\"(.+)\"|Ui";
				preg_match($regexp,$w,$l);
				eval("\$a['".$l[1]."']='".$l[3]."';");
			}*/
			$aOption[]=$a;
			print_debug($aOption,null,'wmslist');
			$layergroupName=$option["layergroup_title_alt"]?$option["layergroup_title_alt"]:($option["layergroup_title"]?$option["layergroup_title"]:$option["layergroup_name"]);
			//$aOption[$i] = array($id,$layergroupName);
		}
		return $aOption;
	}
	
	
/*	
	function getaddobjectList(){
		$aOption=array();
		for($i=0;$i<count($this->addobjectList);$i++){
			$option = $this->addobjectList[$i];
			$id=intval($option["qt_id"]);
			$layertype=intval($option["layertype_id"]);
			$qtName=$option["qt_name_alt"]?$option["qt_name_alt"]:$option["qt_name"];
			$aOption[$i] = array($id,$layertype,$qtName);
		}
		return $aOption;
	}
*/

	function getThemeOpen(){
		$items = array();
		$myMap = "MAPSET_".$this->mapsetName;
		for($i=0;$i<count($_SESSION[$myMap]["THEME_OPEN"]);$i++)
			$items[] = intval($_SESSION[$myMap]["THEME_OPEN"][$i]);
		return $items;
	}
	
	function getGroupsOn(){
		$items = array();
		$myMap = "MAPSET_".$this->mapsetName;
		for($i=0;$i<count($_SESSION[$myMap]["GROUPS_ON"]);$i++)
			$items[] = intval($_SESSION[$myMap]["GROUPS_ON"][$i]);
		return $items;
	}
	 
//###############################################################################################

					//METODI PER LA CREAZIONE ELENCO QT E ELENCO QTFIELD

//###############################################################################################	

	function getQTname($val){
		$myMap = "MAPSET_".$this->mapsetName;
		$langId = $_SESSION[$myMap]["LANGUAGE"];
		$dbSchema=DB_SCHEMA;

		$sql="select qt.qt_id, qt.qt_name from ($dbSchema.qt inner join $dbSchema.mapset_qt using (qt_id)) inner join $dbSchema.theme using(theme_id) where theme_id=$val and mapset_name='".$this->mapsetName."' order by qt_order;";
		print_debug($sql,null,'elencoqt');
		$this->db->sql_query ($sql);
		$aOption=array();
		while($row = $this->db->sql_fetchrow()){
			$qtName=(isset($row["qt_name_alt"]) && $row["qt_name_alt"])?$row["qt_name_alt"]:$row["qt_name"];
			$qtList[] = array(intval($row["qt_id"]),$qtName);
		}
		$jsObject['updatemap']=0;
		$jsObject['qtname']=$qtList;
		if (!isset($jsObject['error'])) $jsObject['error'] = 0;
		if(!$jsObject['error']) $jsObject['error']=0;
		return $jsObject;
	}
	
	function getQTfield($val){
		$myMap = "MAPSET_".$this->mapsetName;
		$langId = $_SESSION[$myMap]["LANGUAGE"];
		$dbSchema=DB_SCHEMA;
		$sql = "select qtfield_id,qtfield_name,field_header,searchtype_id,resultype_id,fieldtype_id,default_op,datatype_id,field_filter,tolerance,layertype_id,data_srid,data_unique,edit_url from $dbSchema.qtfield inner join $dbSchema.qt using (qt_id)inner join $dbSchema.layer using (layer_id) where qt_id=$val and searchtype_id > 0 and fieldtype_id < 100 order by qtfield_order";
		//$sql = "select qtfield_id,qtfield_name,field_header,searchtype_id,resultype_id,fieldtype_id,default_op,datatype_id,field_filter,tolerance,layertype_id,data_srid,data_unique,edit_url from $dbSchema.qtfield inner join $dbSchema.qt using (qt_id)inner join $dbSchema.layer using (layer_id) where qt_id=$val and searchtype_id > 0 and fieldtype_id < 100 order by qtfield_order";
		print_debug($sql,null,'elencoqt');
		$this->db->sql_query ($sql);
		$aField = array();
		$layertype = -1;
		$srid = -1;
		$qtfieldList = array();
		//$tolerance = DEFAULT_TOLERANCE;
		$layerKey = null;
		$editurl = null;
		$defaultOp = null;
		while($row = $this->db->sql_fetchrow()){
			$idFilter = $row["field_filter"]?intval($row["field_filter"]):0;
			if($row["tolerance"]) $tolerance = $row["tolerance"];
			$qtfieldTitle=(isset($row["field_header_alt"]) && $row["field_header_alt"])?$row["field_header_alt"]:$row["field_header"];
			$qtfieldList[] = array(intval($row["qtfield_id"]),$qtfieldTitle,intval($row["searchtype_id"]),intval($row["fieldtype_id"]),$idFilter);
			$defaultOp =  $row["default_op"];
			$editurl = $row["edit_url"];
			$layertype = intval($row["layertype_id"]);
			if($row["data_srid"]) $srid = intval($row["data_srid"]);
			$layerKey=$row["data_unique"];
		}
		$jsObject['updatemap']=0;
		$jsObject['qtfield']=$qtfieldList;
		$jsObject['layerkey']=$layerKey;
		$jsObject['qlayertype']=$layertype;
		$jsObject['default_op']=$defaultOp;
		$jsObject['datasrid']=$srid;
		$jsObject['editurl']=$editurl;
		if(!isset($jsObject['error'])) $jsObject['error']=0;
		if(!$jsObject['error']) $jsObject['error']=0;
		
		return $jsObject;		
	}
	
	
	
//###############################################################################################

					//METODI PER LA CREAZIONE DEL FILE MAP

//###############################################################################################	
	function writeMap(){
		$dbSchema=DB_SCHEMA;
		//carico la lista degli EPSG dal db principale con i parametri di correzione del progetto
		$this->db->sql_query("select srid,lower(auth_name) as auth_name,auth_srid,proj4text,param from spatial_ref_sys left join $dbSchema.project_srs using(srid)");
		while($row = $this->db->sql_fetchrow()){
			$this->rsProj[$row[0]]["SRS"] = $row[1].':'.$row[2];
			$this->rsProj[$row[0]]["PARAM"] = trim($row[4]);
		}
		
		$aResult = array();
		//$slist = str_replace("'","','",$this->mapsetName);
		$this->aSymbol=array();
		$sql="select mapset_name,project_name,base_path,mapset_extent,test_extent,project_extent,mapset_srid,project_srid,sizeunits_name,bg_color,filter_data,mapset_def,mask,metadata,outputformat_def as imgformat,interlace from $dbSchema.mapset inner join $dbSchema.project using (project_name) left join $dbSchema.e_sizeunits using(sizeunits_id) left join $dbSchema.e_outputformat using (outputformat_id) where mapset_name='".$this->mapsetName."';";
		$this->db->sql_query($sql);
		print_debug($sql,null,'mapset');

		$row = $this->db->sql_fetchrow();
		$mapName=$row["mapset_name"];
		$mapExtent=trim($row["mapset_extent"])?$row["mapset_extent"]:$row["proj_extent"];
		$testExtent=$row["test_extent"]?$row["test_extent"]:$row["mapset_extent"];
		$this->mapProjection=($row["mapset_srid"] && $row["mapset_srid"]!=-1)?$row["mapset_srid"]:false;
		$this->basePath=$row["base_path"];
		$mapColor=trim($row["bg_color"])?$row["bg_color"]:MAP_BG_COLOR;
		$this->mapFilter=$row["filter_data"];
		$fontList=(defined('FONT_LIST'))?FONT_LIST:'fonts';
		
		$this->notServerWS = (strpos($row["metadata"],'wms_title')===false && strpos($row["metadata"],'wfs_title')===false);

		//MAP
		$mapText=array();
		$mapText[]="MAP";
		$mapText[]="\tNAME \"$mapName\"";	
		$mapText[]="\tEXTENT $mapExtent";
		$mapText[]="\tIMAGECOLOR $mapColor";
		$mapText[]="\tSIZE 540 430";
		$mapText[]="\tRESOLUTION ".MAP_DPI;
		if($row["sizeunits_name"]) $mapText[]="\tUNITS ".  $row["sizeunits_name"];
		$mapText[]="\tFONTSET \"../fonts/$fontList.list\"";	
		if(defined('PROJ_LIB')) $mapText[]="\tCONFIG 'PROJ_LIB' '".PROJ_LIB."'";
		$mapText[]="\tOUTPUTFORMAT";
		if($row["imgformat"])
			$mapText[]="\t\t".str_replace("\n","\n\t\t",$row["imgformat"]);	
		else{
			$mapText[]="\t\tNAME png8";
			$mapText[]="\t\tDRIVER \"GD/PNG\"";
			$mapText[]="\t\tMIMETYPE \"image/png\"";
			$mapText[]="\t\tIMAGEMODE PC256";
			$mapText[]="\t\tEXTENSION \"png\"";
		}
		if($row["interlace"]) $mapText[]="\t\tFORMATOPTION  INTERLACE=ON";
		$mapText[]="\tEND";
				
		if($this->mapProjection)
			$mapText[] = "\t". $this->_getProjString($row["mapset_srid"]);
			
		$mapText[]="WEB";
		$mapText[]="\tIMAGEPATH \"".IMAGE_PATH."\"";
		$mapText[]="\tIMAGEURL \"".IMAGE_URL."\"";
		$mapText[]="\tMETADATA";
		$mapText[]="\t\t\"ows_enable_request\" \"*\"";
		if($row["metadata"]){ 
			$mapText[]="\t\t".str_replace("\n","\n\t\t",$row["metadata"]);
		}
		$mapText[]="\tEND";		
		$mapText[]="END";
		if($row["mapset_def"]) $mapText[]=$row["mapset_def"];	
			
		//LAYERS
		//$sql="select layergroup_name,layergroup_maxscale,layergroup_minscale,layergroup_smbscale,layer.*,e_layertype.name as layertype_name,layertype_ms,e_sizeunits.name as sizeunits,connection_type,hostname,dbname,dbport,dbschema,shape_dir,mapuser,mappwd from ((($dbSchema.layer inner join $dbSchema.e_layertype using (layertype_id)) inner join $dbSchema.layergroup  using (layergroup_id)) inner join (($dbSchema.catalog left join $dbSchema.connection using (connection_id)) inner join $dbSchema.e_dbtype using (dbtype_id)) using (catalog_id)) left join $dbSchema.e_sizeunits using(sizeunits_id) where layergroup_id in(select layergroup_id from $dbSchema.mapset_layergroup where mapset_name=".$this->mapsetName.") order by layer_order;";
		$sql="select layergroup.layergroup_name,layergroup_maxscale,layergroup_minscale,layergroup_smbscale,layergroup.hidden,layer.*,layertype_name,layertype_ms,sizeunits_name,connection_type,catalog_path from $dbSchema.layer inner join $dbSchema.e_layertype using (layertype_id) inner join $dbSchema.layergroup  using (layergroup_id) left join $dbSchema.catalog using (catalog_id) left join $dbSchema.e_sizeunits using(sizeunits_id) where layergroup_id in(select layergroup_id from $dbSchema.mapset_layergroup where mapset_name='".$this->mapsetName."') order by layer_order;";
		$this->db->sql_query($sql);
		$res=$this->db->sql_fetchrowset();
		print_debug($sql,null,'writelayer');
			
		for($i=0;$i<count($res);$i++){
			$aLayer=$res[$i];
			$mapText[]="LAYER #----------------$i-----------------";
			$mapText[]=$this->_getLayerText($aLayer);
			$mapText[]="END";
		}

			
	
		//Se ho un mask definito sul mapset aggiungo un layer con lo shape come ultimo livello
		if($row["mask"]){
			$path=$this->basePath;
			if(substr($path,-1)!="/") $path.="/";
			$shpfile=$path."mask/".$row["mask"];
			if (file_exists($shpfile.".shp")){
				$mapText[]="LAYER #----------------MASK-----------------";
				$mapText[]="NAME \"__MASK__\"";
				$mapText[]="TYPE POLYGON";
				$mapText[]="STATUS ON";		
				$mapText[]="DATA \"$shpfile\"";
				$mapText[]="CLASS";
				$mapText[]="COLOR $mapColor";
				$mapText[]="END";
				$mapText[]="END #----------------END MASK-----------------";
			}
		}

		$fileContent=implode("\n",$mapText);
		if($this->aSymbol) $fileContent.="\n".$this->_getSymbolListText()."\n";//Elenco dei simboli
		$scalebar = file_get_contents(ROOT_PATH."config/scalebar.def");
		$fileContent.= "\n $scalebar";
		$fileContent.="\nEND #endmap";
		//print($fileContent);
		//VEDERE X LINUX LA BARRA 
		//Gestione degli errori se manca la directory principale
			
		$mapsetDir=ROOT_PATH."mapset/map";
		if(!is_dir($mapsetDir))	mkdir($mapsetDir);		
		$mapsetFile=$mapsetDir."/".$mapName.".map";
		$f = fopen ($mapsetFile,"w");
		$ret=fwrite($f, $fileContent);
		fclose($f);
			
		//creo la legenda
		$this->_createLegend($mapName);
			
			
		if($ret)
			$aResult[]=array($mapName,'Operazione completata');
		else
			$aResult[]=array($mapName,false);

		return $aResult; 
	}
	
	function _getLayerText($aLayer){
		$dbSchema=DB_SCHEMA;
		$layText=array();
		$layText[]="\tGROUP \"".str_replace(" ","_",$aLayer["layergroup_name"])."_".$aLayer["layergroup_id"]."\"";	
		$layText[]="NAME \"".str_replace(" ","_",$aLayer["layer_name"])."\"";		
		//I layer sempre spenti sono quelli dinamici oppure quelli appartenenti a layergroup nascosti
		$layerStatus = "ON";
		if($aLayer["static"]==0) $layerStatus = "OFF";
		if($aLayer["hidden"]==1) $layerStatus = "OFF";
		$layText[]="STATUS $layerStatus";
		if(!$this->notServerWS) $layText[]="DUMP TRUE";
		
		if($aLayer["layertype_ms"]==99){//testo fisso su layer
			$layText[]="TYPE annotation";
			$layText[]="TRANSFORM FALSE";
			$layText[]="LABELCACHE OFF";
			$layText[]="FEATURE";
			if($aLayer["data_geom"])
				$layText[]="POINTS " . $aLayer["data_geom"] . " END";
			else
				$layText[]="POINTS 10 10 END";
			$layText[]="END";
			$layText[]="FORCE TRUE";
		}
		
		else{
			$filepath = false;
			if($aLayer["layertype_ms"]==7){//tileindex layer //RIVEDERE
				//Trovo il catalogo per il layer del layergroup che gestisce il raster
				//ATTENZIONE NEL GRUPPO CI DEVE ESSERE SOLO 1 LAYER DI TIPO RASTER
				$sql="select catalog_path from $dbSchema.layer inner join $dbSchema.e_layertype using(layertype_id) inner join $dbSchema.catalog using (catalog_id) where layergroup_id=".$aLayer["layergroup_id"]." and layertype_ms=3;";
				$this->db->sql_query($sql);
				$filepath=$this->db->sql_fetchfield('catalog_path');
				$filepath = (substr(trim($filepath),0,1)=='/')?trim($filepath):$this->basePath.trim($filepath);
				$aLayer["layertype_name"]="POLYGON";
			}
		
			$layText[]="TYPE ". strtoupper ($aLayer["layertype_name"]);
			$layText[]="METADATA";
			$layText[]="\t\"wms_group_title\"\t\"". str_replace(" ","_",$aLayer["layergroup_name"]) ."\"";
			$layText[]="\t\"ows_title\"\t\"". str_replace(" ","_",$aLayer["layergroup_name"]) ."\"";

			//Eventuali metadati
			if($aLayer["metadata"]){ 
				$layText[]="\t".str_replace("\n","\n\t\t",$aLayer["metadata"]);
			}
			$layText[]="END";
			//Sistema di riferimento
			if($aLayer["data_srid"]>0 && !($aLayer["connection_type"]==70 || $aLayer["connection_type"]==90)) 
				$layText[] = $this->_getProjString($aLayer["data_srid"]);

			//Tipo di connessione		
			if($aLayer["connection_type"]==1){//file locale shape o raster	
				$filepath = (substr(trim($aLayer["catalog_path"]),0,1)=='/')?trim($aLayer["catalog_path"]):$this->basePath.trim($aLayer["catalog_path"]);
				if(substr($filepath,-1)!="/") $filepath.="/";
				if($aLayer["data"]){
					if(file_exists($filepath.$aLayer["data"]))
						 $layText[]="DATA \"".$filepath.$aLayer["data"]."\"";
					else{
						$layText[]="TILEINDEX \"".$aLayer["data"]."\"";
						$layText[]="TILEITEM \"location\"";
					}
				}
			}
			elseif($aLayer["connection_type"]==8){//Oracle Spatial
				$layText[]="CONNECTIONTYPE oraclespatial";
				$layText[]="CONNECTION \"".$aLayer["catalog_path"]."\"";
				$sData=$aLayer["data"];
				if(preg_match("|select (.+) from (.+)|i",$sData))
					$sdata=$aLayer["data_geom"]." from ($sData) as foo";
				else
					$sData=$aLayer["data_geom"]." from ".$sData;
				$using = '';
				if($aLayer["data_unique"]) $using .=" unique " . $aLayer["data_unique"];
				if($aLayer["data_srid"]>0) $using .=" srid " . $aLayer["data_srid"];
				if ($using != '') {
					$sData .= " using $using";
				}
				$layText[]="DATA \"$sData\"";
				$layText[]="PROCESSING \"CLOSE_CONNECTION=DEFER\"";			
			}
			elseif($aLayer["connection_type"]==6){//POSTGIS
				$connString = false;
				$layText[]="CONNECTIONTYPE postgis";
				$aConnInfo = connInfofromPath($aLayer["catalog_path"]);
				$connString = $aConnInfo[0];
				$datalayerSchema = $aConnInfo[1];
				$layText[]="TEMPLATE \"FOO\"";
				$layText[]="CONNECTION \"$connString\"";
				$sData=$aLayer["data"];
				
				//??????????????????????????????????		
				if(preg_match("|select (.+) from (.+)|i",$sData))
					$sdata=$aLayer["data_geom"]." from ($sData) as foo";
				elseif($aLayer["layertype_ms"]==7){
					$location=$aLayer["classitem"];
					$sData=$aLayer["data_geom"]." from (select ".$aLayer["data_unique"].",".$aLayer["data_geom"].",'$filepath'||$location as location  from $datalayerSchema.$sData) as foo";
				}else
					$sData=$aLayer["data_geom"]." from ".$datalayerSchema.".".$sData;
					//$sData=$aLayer["data_geom"]." from (select * from ".$datalayerSchema.".".$sData." order by ".$aLayer["data_unique"].") as foo";

				if($aLayer["data_unique"]) $sData.=" using unique " . $aLayer["data_unique"];
				//if($aLayer["data_srid"]>0) $sData.=" using srid " . $aLayer["data_srid"];					
				$layText[]="DATA \"$sData\"";	
				$layText[]="PROCESSING \"CLOSE_CONNECTION=DEFER\"";			
			}
			elseif($aLayer["connection_type"]==4){//OGR
				$layText[]="CONNECTIONTYPE OGR";
				$layText[]="CONNECTION \"".$aLayer["catalog_path"]."\"";
				$layText[]="DATA \"".$aLayer["data"]."\"";
			}
			elseif($aLayer["connection_type"]==7){//WMS
				$layText[]="CONNECTIONTYPE WMS";
				$layText[]="CONNECTION \"".$aLayer["catalog_path"]."\"";
			}
			elseif($aLayer["connection_type"]==9){//WFS
				$layText[]="CONNECTIONTYPE WFS";
				$layText[]="CONNECTION \"".$aLayer["catalog_path"]."\"";
			}

/*
			if($aLayer["data_filter"])
				if($layerFilter) $layerFilter = "($layerFilter) AND (".$aLayer["data_filter"].")";
			else
				$layerFilter = $aLayer["data_filter"];
			if(!empty($layerFilter)) $layText[]="FILTER \"$layerFilter\"";
			
*/
			if(!empty($aLayer["data_filter"])) $layText[]="FILTER \"". $aLayer["data_filter"] ."\"";
			if($aLayer["sizeunits_name"]) $layText[]="SIZEUNITS ". $aLayer["sizeunits_name"];		
			if($aLayer["classitem"] && $aLayer["layertype_ms"]!=7) $layText[]="CLASSITEM \"". $aLayer["classitem"]."\"";
			if($aLayer["labelitem"]) $layText[]="LABELITEM \"". $aLayer["labelitem"]."\"";		
			if($aLayer["requires"]) $layText[]="REQUIRES ". $aLayer["requires"];
				
			if($aLayer["labelrequires"]) $layText[]="LABELREQUIRES ". $aLayer["labelrequires"];
			if($aLayer["labelminscale"]) $layText[]="LABELMINSCALEDENOM ". $aLayer["labelminscale"];
			if($aLayer["labelmaxscale"]) $layText[]="LABELMAXSCALEDENOM ". $aLayer["labelmaxscale"];
			
			if($aLayer["minscale"])
				$layText[]="MINSCALEDENOM ". $aLayer["minscale"];
			elseif ($aLayer["layergroup_minscale"])
				$layText[]="MINSCALEDENOM ". $aLayer["layergroup_minscale"];
			if($aLayer["maxscale"])
				$layText[]="MAXSCALEDENOM ". $aLayer["maxscale"];
			elseif ($aLayer["layergroup_maxscale"])
				$layText[]="MAXSCALEDENOM ". $aLayer["layergroup_maxscale"];

			if($aLayer["symbolscale"])
				$layText[]="SYMBOLSCALEDENOM ". $aLayer["symbolscale"];
			elseif ($aLayer["layergroup_smbscale"])
				$layText[]="SYMBOLSCALEDENOM ". $aLayer["layergroup_smbscale"];

			if(isset($aLayer["maxfeatures"])) $layText[]="MAXFEATURES ". $aLayer["maxfeatures"];
			if(isset($aLayer["transparency"])) $layText[]="OPACITY ". $aLayer["transparency"];
			if(isset($aLayer["tolerance"])) $layText[]="TOLERANCE ". $aLayer["tolerance"];
			if(isset($aLayer["toleranceunits"])) $layText[]="TOLERANCEUNITS ". $aLayer["toleranceunits"];
			
			if($aLayer["layertype_ms"]==0)//Simboli ttf
				$layText[]="POSTLABELCACHE TRUE";//Metto sempre

			if($aLayer["layer_def"]) $layText[]=$aLayer["layer_def"];	
			$this->classitem=$aLayer["classitem"];
		}
		
		$dbSchema=DB_SCHEMA;
		$sql="select class_id,class_name,class_title,class_text,expression,maxscale,minscale,label_font,label_angle,label_color,label_outlinecolor,label_bgcolor,label_size,label_minsize,label_maxsize,label_position,label_priority,label_buffer,label_force,label_wrap,label_def
		from $dbSchema.class where layer_id=".$aLayer["layer_id"]." order by class_order;";
		print_debug($sql,null,'writemap');
		if(!$this->db->sql_query($sql)) print_debug($this->db,null,'writemap-error');
		$res=$this->db->sql_fetchrowset();	
		
		for($i=0;$i<count($res);$i++){
			$aClass=$res[$i];
			$layText[]="CLASS";
			$layText[]=$this->_getClassText($aClass);
			$layText[]="END";
		}
		
		return implode("\n\t",$layText);
	}

	function _getClassText($aClass){
		print_debug($aClass,null,'classi');
		$lblFont=$aClass["label_font"];
		$clsText=array();
		$clsText[]="\tNAME \"".str_replace(" ","_",$aClass["class_name"])."\"";	
		if($aClass["class_title"])
			$clsText[]="TITLE \"".str_replace("\"","'",$aClass["class_title"])."\"";	
		if(isset($aClass["expression"])) 
		if($this->classitem)
			$clsText[]="EXPRESSION ".$aClass["expression"];
		else
			$clsText[]="EXPRESSION (". $aClass["expression"].")";	
			
		if(isset($aClass["class_text"])){
			$clsText[]="TEXT (". $aClass["class_text"].")";
		}elseif(isset($aClass["smbchar"])){//simbolo true type
			$clsText[]="TEXT (". $aClass["smbchar"].")";
		}
		
		if(isset($aClass["maxscale"])) $clsText[]="MAXSCALEDENOM ". $aClass["maxscale"];
		if(isset($aClass["minscale"])) $clsText[]="MINSCALEDENOM ". $aClass["minscale"];
		if(isset($aClass["class_def"])) $clsText[]=$aClass["class_def"];
		//Se ho impostato il font aggiungo la label
		if($lblFont){
			$clsText[]="LABEL";
			$clsText[]="\tTYPE TRUETYPE";
			//$clsText[]="\tANTIALIAS TRUE";
			$clsText[]="\tPARTIALS TRUE";			
			$clsText[]="\tFONT \"$lblFont\"";		
			if($aClass["label_angle"]) $clsText[]="\tANGLE ".$aClass["label_angle"];				
			if($aClass["label_color"]) $clsText[]="\tCOLOR ".$aClass["label_color"];			
			if($aClass["label_bgcolor"]) $clsText[]="\tBACKGROUNDCOLOR " .$aClass["label_bgcolor"];	
			if($aClass["label_outlinecolor"]) $clsText[]="\tOUTLINECOLOR " .$aClass["label_outlinecolor"];	
			if($aClass["label_size"]) $clsText[]="\tSIZE ".$aClass["label_size"];	
			if($aClass["label_minsize"]) $clsText[]="\tMINSIZE ".$aClass["label_minsize"];	
			if($aClass["label_maxsize"]) $clsText[]="\tMAXSIZE ".$aClass["label_maxsize"];	
			if($aClass["label_position"]) $clsText[]="\tPOSITION ".$aClass["label_position"];
			if($aClass["label_priority"]) $clsText[]="\tPRIORITY ".$aClass["label_priority"];
			if($aClass["label_buffer"]) $clsText[]="\tBUFFER ".$aClass["label_buffer"];
			if($aClass["label_force"]) $clsText[]="\tFORCE TRUE";
			if($aClass["label_wrap"]=='#')$aClass["label_wrap"]=' ';
			if($aClass["label_wrap"]) $clsText[]="\tWRAP \"".$aClass["label_wrap"]."\"";		
			if($aClass["label_def"]) $clsText[]=$aClass["label_def"];	
			$clsText[]="END";	
		}
		$dbSchema=DB_SCHEMA;
		$sql="select angle,color,outlinecolor,bgcolor,size,minsize,maxsize,minwidth,width,style_def,symbol.symbol_name,e_pattern.pattern_def
		from $dbSchema.style left join $dbSchema.symbol using (symbol_name) left join $dbSchema.e_pattern using(pattern_id) where class_id=".$aClass["class_id"] ." order by style_order;";
		print_debug($sql,null,'writemap');
		$this->db->sql_query($sql);
		$res=$this->db->sql_fetchrowset();	
		for($i=0;$i<count($res);$i++){
			$aStyle=$res[$i];
			$clsText[]="STYLE";
			$clsText[]=$this->_getStyleText($aStyle);
			$clsText[]="END";
		}	
		
		return implode("\n\t\t",$clsText);		
	}
	
	function _getStyleText($aStyle){	
		$styText=array();
		if(isset($aStyle["color"])) $styText[]="COLOR ".$aStyle["color"];
		if(isset($aStyle["symbol_name"])) $styText[]="SYMBOL \"".$aStyle["symbol_name"]."\"";
		if(isset($aStyle["bgcolor"])) $styText[]="BACKGROUNDCOLOR ".$aStyle["bgcolor"];
		if(isset($aStyle["outlinecolor"])) $styText[]="OUTLINECOLOR ".$aStyle["outlinecolor"];
		if(isset($aStyle["size"]) && $aStyle["size"]) $styText[]="SIZE ".$aStyle["size"];
		if(isset($aStyle["minsize"]) && $aStyle["minsize"]) $styText[]="MINSIZE ".$aStyle["minsize"];
		if(isset($aStyle["maxsize"]) && $aStyle["maxsize"]) $styText[]="MAXSIZE ".$aStyle["maxsize"];
		if(isset($aStyle["angle"])) $styText[]="ANGLE ".$aStyle["angle"];
		if(isset($aStyle["width"]) && $aStyle["width"]) 
			$styText[]="WIDTH ".$aStyle["width"];
		else
			$styText[]="WIDTH 1";//pach mapserver 5.6 non disegna un width di default
		if(isset($aStyle["pattern_def"]) && ms_GetVersionInt()>502000) $styText[]=$aStyle["pattern_def"];
		if(isset($aStyle["minwidth"]) && $aStyle["minwidth"]) $styText[]="MINWIDTH ".$aStyle["minwidth"];
		if(isset($aStyle["maxwidth"]) && $aStyle["maxwidth"]) $styText[]="MAXWIDTH ".$aStyle["maxwidth"];
		if((isset($aStyle["symbol_name"]))&&(!in_array($aStyle["symbol_name"],$this->aSymbol))) $this->aSymbol[]=$aStyle["symbol_name"];
		if(isset($aStyle["style_def"])) $styText[]=$aStyle["style_def"];
		return "\t".implode("\n\t\t\t",$styText);	
	}
	
	function _getSymbolListText(){
	//print_debug($this->aSymbol,null,$myMap);
		$dbSchema=DB_SCHEMA;
		$smbList=implode("','",$this->aSymbol);
		$sql="select symbol_name,symbol_def from $dbSchema.symbol where symbol_name in ('$smbList');";
		$this->db->sql_query($sql);
		$res=$this->db->sql_fetchrowset();	
		//print_debug($sql,null,'writemap');		
		$smbText=array();	
		for($i=0;$i<count($res);$i++){
			$smbText[]="SYMBOL";
			$smbText[]="NAME \"".$res[$i]["symbol_name"]."\"";
			$smbText[]=$res[$i]["symbol_def"];
			$smbText[]="END";
		}
		//Da mettere o nel codice o su config
		$smbText[]="SYMBOL";
		$smbText[]="NAME \"line_select\"";
		$smbText[]="TYPE ELLIPSE";
		$smbText[]="POINTS 1 1 END";
		$smbText[]="FILLED TRUE";
		$smbText[]="END";
		return implode("\n",$smbText);
	}
	
	function _getProjString($sridValue){
		$projText[]="PROJECTION";
		$projText[]="\t\t\"init=".$this->rsProj[$sridValue]["SRS"]."\"";
		if($this->notServerWS && $this->rsProj[$sridValue]["PARAM"]) $projText[]="\t\t\"+towgs84=".$this->rsProj[$sridValue]["PARAM"]."\"";
		//$aDef = explode("+",$this->);
		//for($i=0;$i<count($aDef);$i++){
			//if(strlen(trim($aDef[$i]))>0 && trim($aDef[$i])!='no_defs') $projText[] = "\t\t\"". trim($aDef[$i]) ."\"";	
		//}
		//if($param && $this->rsProj[$sridValue]["param"]) $projText[] = "\t\t".str_replace("+","\"",$this->rsProj[$sridValue]["param"])."\"";
		
		$projText[]="\tEND";	
		return implode("\n",$projText);
	}
	
	function _createLegend($mapset){
		$dbSchema=DB_SCHEMA;
		$sql="select layer_name,class_image from $dbSchema.class inner join $dbSchema.layer using(layer_id)
		inner join $dbSchema.layergroup using (layergroup_id) inner join $dbSchema.theme using (theme_id) 
		inner join $dbSchema.mapset_layergroup using (layergroup_id) where mapset_name='$mapset' and legendtype_id=1 and layertype_id<>10
		order by theme_order,theme_title,layergroup_order,layergroup_title,layer_order,class_order,class_title;";
		//echo ($sql);
		$this->db->sql_query($sql);
		$res=$this->db->sql_fetchrowset();	
		$numIcons=$this->db->sql_numrows();
		$imgwidth = LEGEND_ICON_W * $numIcons;
		$imgheight = LEGEND_ICON_H;
		$insertposition = 0;
		$legendimage = imagecreatetruecolor($imgwidth,$imgheight);

		for($i=0;$i<count($res);$i++){
			$img = imagecreatefromstring(pg_unescape_bytea($res[$i]["class_image"]));
			imagecopymerge($legendimage,$img,$insertposition,0,0,0,LEGEND_ICON_W,LEGEND_ICON_H,100);
			$insertposition += LEGEND_ICON_W;
			imagedestroy($img);
		}
		
		$legendDir=ROOT_PATH."public/images/legend/";
		$filename = $legendDir.$mapset.".png";
		imagepng($legendimage,$filename);
		imagedestroy($legendimage);
        include ROOT_PATH."public/kmlmapserver/createicons.php";
        
	}


}//END CLASS


?>
