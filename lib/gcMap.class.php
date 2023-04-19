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
class GCMap{

	public $map;
	public $geoExtent;
	public $scale;
	public $pixelsize;
	//restituisce l'elenco dei layer da disattivare via javascript nel tab dei layers
	public $layers_disabled=array();
	public $mapError;
	const minExtent = 1;  // dimensione lineare minima di un extent

	function __construct(){
		
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$sMapFile = ROOT_PATH."mapset/map/".$_REQUEST["mapset"].".map";
		if(!file_exists($sMapFile)){
			print $sMapFile;
			$this->mapError=140;
			return;
		}

		//test sintassi mapfile		
		ms_ResetErrorList();
		try {
			$this->map = @ms_newMapobj($sMapFile);
		} 
		catch (Exception $e) {
			$error = ms_GetErrorObj();		
			if($error->code != MS_NOERR){
				$this->mapError=150;
				while(is_object($error) && $error->code != MS_NOERR) {
					print("MAPFILE ERROR <br>");
					printf("Error in %s: %s<br>\n", $error->routine, $error->message);
					$error = $error->next();
				}
				return;
			}	
			return;
		}

		
		$this->mapFile = $sMapFile;
		$this->msVersion = substr(ms_GetVersionInt(),0,1);	
	}
	
	function initMap(){

		$oMap=$this->map;
		extract($_REQUEST);
		$oMap->set('width',$imageWidth);
		$oMap->set('height',$imageHeight);
		$myMap = "MAPSET_".$_REQUEST["mapset"];		
		
		//Setto la massima estensione per avere il valore di maxscale
		$this->setExtent($_SESSION[$myMap]["MAPSET_EXTENT"][0],$_SESSION[$myMap]["MAPSET_EXTENT"][1],$_SESSION[$myMap]["MAPSET_EXTENT"][2],$_SESSION[$myMap]["MAPSET_EXTENT"][3]);
		//$this->setExtent($oMap->extent->minx,$oMap->extent->miny,$oMap->extent->maxx,$oMap->extent->maxy);
		$this->maxscaledenom=$oMap->scaledenom;

		if($action=='zoomall')
			$_SESSION[$myMap]["MAP_EXTENT"] = $_SESSION[$myMap]["MAPSET_EXTENT"];
			//$_SESSION[$myMap]["MAP_EXTENT"]=array($oMap->extent->minx,$oMap->extent->miny,$oMap->extent->maxx,$oMap->extent->maxy);

		$this->geoExtent=$_SESSION[$myMap]["MAP_EXTENT"];

		
		//Aggiornamento HISTORY
		if(isset($history)){//zoom avanti o indietro
			$hindex = $_SESSION[$myMap]["HISTORY_INDEX"] + intval($history);
			$maxindex=min(MAX_HISTORY,count($_SESSION[$myMap]["HISTORY"]));
			if($hindex > $maxindex) $hindex = $maxindex;
			if($hindex < 0) $hindex = 0;
			$_SESSION[$myMap]["HISTORY_INDEX"] = $hindex;
			if($_SESSION[$myMap]["HISTORY"][$hindex])
				$this->geoExtent = $_SESSION[$myMap]["HISTORY"][$hindex];
		}		
		
		//Se il comando è reload ripulisco la sessione
		if (($action == "reload")||($action=="initmap")){
			unset($_SESSION[$myMap]["LAYERS_ORDER"]);
			unset($_SESSION[$myMap]["SELECTION_POLYGON"]);
			unset($_SESSION[$myMap]["RESULT"]);	
			unset($_SESSION[$myMap]["ADD_OBJECT"]);
		}
	}
	
	function updateExtent(){
		//rimetto in sessione i valori di estensione corretti ( possono essere cambiati per un resize o dopo inizializzazione della prima mappa) e memorizzo in history se non ho fatto solo il reload
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$oExtent=$this->map->extent;
		$aExtent=array($oExtent->minx,$oExtent->miny,$oExtent->maxx,$oExtent->maxy);
		//prima estensione da memorizzare
		if($_REQUEST["action"]=="initmap" && !(isset($_SESSION[$myMap]["HISTORY"]))){//Estensione massima in sessione come prima finestra
			$_SESSION[$myMap]["HISTORY"][0]=$aExtent;
			$_SESSION[$myMap]["HISTORY_INDEX"] = 0;
		}
		elseif(!(isset($_REQUEST["history"]) || isset($_REQUEST["layers"]))) {
			//Se ho raggiunto il numero massimo di viste tolgo la più vecchia
			if(!isset($_SESSION[$myMap]["HISTORY_INDEX"])) {
				$_SESSION[$myMap]["HISTORY_INDEX"] = 1;
			} else if($_SESSION[$myMap]["HISTORY_INDEX"] == MAX_HISTORY) {
				$_SESSION[$myMap]["HISTORY"]=array_slice($_SESSION[$myMap]["HISTORY"], 1);
			}else {
				$_SESSION[$myMap]["HISTORY_INDEX"]++;
			}
			$_SESSION[$myMap]["HISTORY"][$_SESSION[$myMap]["HISTORY_INDEX"]]=$aExtent;
		}
		
		$_SESSION[$myMap]["MAP_EXTENT"]=$aExtent;
		print_debug($_SESSION[$myMap]["HISTORY_INDEX"],null,'history');
		print_debug($_SESSION[$myMap]["HISTORY"],null,'history');
		return $oExtent;
	}
	
	function setExtent($x1,$y1,$x2,$y2){
		if ($x1 == $x2) {
			$x1 = $x1 - 0.5 * self::minExtent;
			$x2 = $x2 + 0.5 * self::minExtent;
		}
		if ($y1 == $y2) {
			$y1 = $y1 - 0.5 * self::minExtent;
			$y2 = $y2 + 0.5 * self::minExtent;
		}
		//wrap di setextent per la gestione degli errori DA PERFEZIONARE
		$this->map->setExtent($x1,$y1,$x2,$y2);
		
		
		/*
		try{
			$this->map->setExtent($x1,$y1,$x2,$y2);
		}
		catch(Exception $e){
			$myMap = "MAPSET_".$_REQUEST["mapset"];
			unset($_SESSION[$myMap]["HISTORY"]);
			unset($_SESSION[$myMap]["HISTORY_INDEX"]);
			//writejsObject(array('error'=>200));
		}*/
	}

	//Ridisegna la mappa (cambio layer e zoom_all)
	function redraw(){		
		$oMap=$this->map;
		$ext=$this->map->extent;
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;
		//Setto al max l'estensione della mappa
		$dx=$ext->maxx-$ext->minx;
		$dy=$ext->maxy-$ext->miny;
		$newdx = 2*($xMax - $xMin);
		$newdy = 2*($yMax - $yMin);
		//Setto al max l'estensione della mappa
		if(($newdx>$dx)||($newdy>$dy))
			$this->setExtent($ext->minx,$ext->miny,$ext->maxx,$ext->maxy);
		else
			$this->setExtent($xMin,$yMin,$xMax,$yMax);

	}

	//Zoom finestra NON USATO
	function zoomWindow(){	
		$oMap=$this->map;
		extract($_REQUEST);
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;
		$oMap->set('width',$imageWidth);
		$oMap->set('height',$imageHeight);
        if (!isset($asgeo)) {
            $x1= $xMin + ($xMax - $xMin) * ($imgX[0] / $imageWidth);
            $x2= $xMin + ($xMax - $xMin) * ($imgX[1] / $imageWidth);
            $y1= $yMax - ($yMax - $yMin) * ($imgY[1] / $imageHeight);
            $y2= $yMax - ($yMax - $yMin) * ($imgY[0] / $imageHeight);
        } else {
            $x1= $imgX[0];
            $x2= $imgX[1];
            $y1= $imgY[0];
            $y2= $imgY[1];
        }
		$this->setExtent($x1, $y1, $x2, $y2);
	}
	
	//Usata per zoomIn su punto, zoomOut e Pan
	function zoomPoint(){
		$oMap=$this->map;
		$ext=$this->map->extent;		
		extract($_REQUEST);
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;
		$dx=$ext->maxx-$ext->minx;
		$dy=$ext->maxy-$ext->miny;
		$newdx = 2*($xMax - $xMin);
		$newdy = 2*($yMax - $yMin);
		//Setto al max l'estensione della mappa
		if(($zoomStep!=2) && (($newdx>$dx)||($newdy>$dy))){
			$this->setExtent($ext->minx,$ext->miny,$ext->maxx,$ext->maxy);
		} 
		else{
			$oMap->set('width',$imageWidth);
			$oMap->set('height',$imageHeight);
			$oPoint=ms_newPointObj();
			$oPoint->setXY($imgX[0],$imgY[0]);
			$oMapExt=ms_newRectObj();
			$oMapExt->setextent($xMin,$yMin,$xMax,$yMax);
			$oMap->zoompoint($zoomStep,$oPoint,$imageWidth,$imageHeight,$oMapExt);
		}				
	}
	
	//Restituisce la mappa alla scala data
	function scale(){
		$oMap=$this->map;
		extract($_REQUEST);
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;	
		$oPoint=ms_newPointObj();
		$oPoint->setXY($imageWidth/2,$imageHeight/2);
		$oMapExt=ms_newRectObj();     
		$oMap->set('width',$imageWidth);
		$oMap->set('height',$imageHeight); 
		$oMapExt->setextent($xMin,$yMin,$xMax,$yMax);
		$oMap->zoomscale($scale,$oPoint,$imageWidth,$imageHeight,$oMapExt);
	}
	
	//Restituisce la mappa alla scala data centrata nel punto dato (coordinate in pixel)
	function scaleToImagePoint($scale, $iI, $iJ){
		$oMap=$this->map;
		extract($_REQUEST);
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;
		$oPoint=ms_newPointObj();
		$oPoint->setXY($iI,$iJ);
		$oMapExt=ms_newRectObj();     
		$oMapExt->setextent($xMin,$yMin,$xMax,$yMax);
		$oMap->set('width',$imageWidth);
		$oMap->set('height',$imageHeight); 
		$oMap->zoomscale($scale,$oPoint,$imageWidth,$imageHeight,$oMapExt);
	}


	function scaleToPoint($x,$y,$scale,$imageWidth,$imageHeight){
		$oMap=$this->map;
		extract($_REQUEST);
		$oPoint=ms_newPointObj();
		$oMapExt=ms_newRectObj();	
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;
		
		
		$oPoint->setXY($imageWidth/2,$imageHeight/2);
		$oMapExt->setextent($x-$imageWidth,$y-$imageHeight,$x+$imageWidth,$y+$imageHeight);
		$oMap->set('width',$imageWidth);
		$oMap->set('height',$mageHeight); 
		$oMap->zoomscale($scale,$oPoint,$imageWidth,$imageHeight,$oMapExt);
	}

	function getMapUrl(){
		$oMap=$this->map;
		ms_ResetErrorList();		
		$oImage=$oMap->draw();
		$error = ms_GetErrorObj();
		if($error->code != MS_NOERR){
			$myMap = "MAPSET_".$_REQUEST["mapset"];
			unset($_SESSION[$myMap]["GROUPS_ON"]);
			$this->mapError=150;
			while($error->code != MS_NOERR){
				print("CREATE MAP ERROR <br>");
				printf("Error in %s: %s<br>\n", $error->routine, $error->message);
				$error = $error->next();
			}
			exit;
		}
		
		//$scale = $oMap->drawScaleBar();
		//$oImage->pasteImage($scale,0x0c0c0c,50,$oMap->height-10,0);
		
		$img=$oImage->saveWebImage();
		return $img;
	}
	
	//Restituisce l'url della immagine scala
	function getScaleBarUrl() {
		$oMap=$this->map;
		$oImage = $oMap->drawScaleBar();
		$img=$oImage->saveWebImage();
		return  $img;
	}

	function getScale() {
		return $this->map->scaledenom;
	}
	function getMaxScale() {
		return $this->maxscaledenom;
	}
	
	function getPixelSize(){
		$oMap=$this->map;
		extract($_REQUEST);
		$extent=$oMap->extent;
		$pixelSize = max(($extent->maxx - $extent->minx)/$imageWidth, ($extent->maxy - $extent->miny)/$imageWidth);
		return $pixelSize;
	}
	
	function getMapObj(){
		return $this->map;
	}
	
	function getImagePath(){
		$oWeb=$this->map->web;
		return $oWeb->imagepath;
	}
	
	//METODO PRESO DAL PMAPPER
	function increaseLabels($factor)
    {
        $layers = $this->map->getAllLayerNames();
        foreach ($layers as $ln) {
            $layer = $this->map->getLayerByName($ln);
            $numclasses = $layer->numclasses;
             $classes = array();
                for ($cl=0; $cl < $numclasses; $cl++) {
                    $class = $layer->getClass($cl);
                    //MAPSERVER >6.2
					try{
						$label = $class->label;
					}
					catch (Exception $e) {
						if($class->numlabels > 0 )
							$label = $class->getLabel(0);
					}
                    if ($label) {
                        if ($label->type != 3) {
                            $labelSize0 = $label->size;
                            //$label->set("minsize", $label->minsize * $factor);
							$label->set("size", $label->size * $factor);
							//$label->set("maxsize", $label->maxsize * $factor);
                        }
                    }
                }
        }
    }
	
	

	/**
	**************************** GESTIONE LAYER
	*/
	
	function setLayersStatus(){
		$oMap = $this->map;		
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$langId = $_SESSION[$myMap]["LANGUAGE"];
		
		//Se ho passato i gruppi come richiesta aggiorno l'elenco dei gruppi accesi in sessione:	
		if(isset($_REQUEST["layers"]))
			$_SESSION[$myMap]["GROUPS_ON"] = explode(",",$_REQUEST["layers"]);
        
        //Aggiungo i layers dinamici qui???
            
            
		
		if(isset($_REQUEST["thopen"]))
			$_SESSION[$myMap]["THEME_OPEN"] = explode(",",$_REQUEST["thopen"]);

		$allGroup = $_SESSION[$myMap]["LAYERGROUPS"];
		if(!isset($allGroup)){
			print("ERRORE DA GESTIRE ");
			exit;
		}
		
		$groupsDisabled=Array();
		//Verifica della disponibilità dei layergroup in funzione della scala. Trovo l'elenco dei check da disabilitare in quanto layer non visibili alla scala corrente 
		//Imposta per ogni layer lo stato ed eventualmente il labelitem
		foreach($allGroup as $idx=>$grpName){
			$aLayersIndexes=$oMap->getLayersIndexByGroup($grpName);
			$numLayers=count($aLayersIndexes);
			$nl = 0;
			$status = (in_array($idx,$_SESSION[$myMap]["GROUPS_ON"]))?MS_ON:MS_OFF;
			for($i=0;$i<$numLayers;$i++){
				$oLayer=$oMap->getLayer($aLayersIndexes[$i]);
				//Setto al volo il labelitem in altra lingua
				if($langLabelitem = (isset($_SESSION[$myMap]["LABELITEM"][$idx][$oLayer->name][$langId]))?$_SESSION[$myMap]["LABELITEM"][$idx][$oLayer->name][$langId]:null) $oLayer->set('labelitem',$langLabelitem);
				//Setto al volo il filtro mapset
				
				if($_SESSION[$myMap]["FILTER"] && in_array($oLayer->name, $_SESSION[$myMap]["FILTER_LAYER"][$idx])){
					$layerFilter = $_SESSION[$myMap]["FILTER"];
					if($this->msVersion=='7'){
						//ATTENZIONE per velocizzare considero che processing sia fatto da 2 voci CLOSE_CONNECTION e NATIVE_FILTER
						$processing=$oLayer->getProcessing();
						if(count($processing)>1){
							$layerFilter = str_replace("\"","",$processing[1])." AND " .$layerFilter;
							$oLayer->clearProcessing();
							$oLayer->setprocessing("CLOSE_CONNECTION=DEFER");
							$oLayer->setprocessing($layerFilter);
						}
						else{
							$oLayer->setprocessing("NATIVE_FILTER=" . $layerFilter);
						}
					}
					else{
						if($oLayer->getFilterString()) $layerFilter = str_replace("\"","",$oLayer->getFilterString())." AND " .$layerFilter;
						$oLayer->setFilter($layerFilter);
					}
				} 

				$dynamicFilter = $this->getDynamicFilter($oLayer->name);
				if($dynamicFilter){//layer dinamico e oggetti da restituire in mappa
					if($this->msVersion=='7'){
						//ATTENZIONE per velocizzare considero che processing sia fatto da 2 voci CLOSE_CONNECTION e NATIVE_FILTER
						$processing=$oLayer->getProcessing();
						if(count($processing)>1){
							$dynamicFilter = str_replace("\"","",$processing[1])." AND " .$dynamicFilter;
							$oLayer->clearProcessing();
							$oLayer->setprocessing("CLOSE_CONNECTION=DEFER");
							$oLayer->setprocessing($dynamicFilter);
						}
						else{
							$oLayer->setprocessing("NATIVE_FILTER=" . $dynamicFilter);
						}
					}
					else{					
						if($oLayer->getFilterString()) $dynamicFilter = str_replace("\"","",$oLayer->getFilterString())." AND " .$dynamicFilter;
						$oLayer->setFilter($dynamicFilter);
					}
					$oLayer->set('status',$status);//ci sono oggetti da restituire accedo il layer
				} 
				else{
					if($oLayer->status==MS_ON) $oLayer->set('status',$status);//non accendo quelli con status off sulla mappa
				}
				if($this->_islayerDisabled($oLayer)) $nl++;
			}
			if ($nl == $numLayers) $groupsDisabled[] = $idx;//se tutti i livelli del gruppo non sono disponibili inserisco il gruppo tra i non disponibili
		}
		
		$this->groupsDisabled=$groupsDisabled;
		//Riorganizzazione dell'ordine dei livelli in funzione dell'ultimo salvato
		if(!isset($_SESSION[$myMap]["LAYERS_ORDER"])) $_SESSION[$myMap]["LAYERS_ORDER"] = array();
		$layers_order = $_SESSION[$myMap]["LAYERS_ORDER"];
		if($layers_order) $oMap->setlayersdrawingorder($layers_order);
		//$this->layergroupInLegend=array_diff($groupsOn,$groupsDisabled);
	}
	
	//TODO Restituire l'elenco delle classi/layer da nascondere in legenda
	function _islayerDisabled($oLayer){
		$scale=$this->map->scaledenom;
		$numCls = $oLayer->numclasses;
		$disabled = true;
		if((($oLayer->maxscaledenom == -1) || ($scale <= $oLayer->maxscaledenom)) && (($oLayer->minscaledenom == -1) || ($scale >= $oLayer->minscaledenom))){
			if($numCls > 0){
				//verifica sulle classi
				for ($clno=0; $clno < $numCls; $clno++) {
					$class = $oLayer->getClass($clno);
					if((($class->maxscaledenom == -1) || ($scale <= $class->maxscaledenom)) && (($class->minscaledenom == -1) || ($scale >= $class->minscaledenom))){
						$disabled = false;
						break;	
						//Esiste almeno una classe visibile, il layer è abilitato
					}
				}
			}
			else
				$disabled = false;	
		}
		return $disabled;
	}
	
	function getLayersDisabled(){
		//ritorna i nomi layer o gruppi da disabilitare nella gestione dei livelli perché non visibili
		$layers_disabled = implode(",",$this->layers_disabled);
		return $layers_disabled;
	}		
	
	function getDynamicFilter($layerName){
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$filter=false;
        
		if (!isset($_SESSION[$myMap]["RESULT"])) $_SESSION[$myMap]["RESULT"]=array();
		foreach($_SESSION[$myMap]["RESULT"] as $qResult){
			if($qResult["LAYER"]==$layerName && $qResult["STATIC"]==0 && count($qResult["ID_LIST"])>0){
				$filter=$qResult["ID_FIELD"]." in(".implode(",",$qResult["ID_LIST"]).")";
			}
		}
		return $filter;
	}
	
	function getGroupsOn(){
		$items = array();
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		for($i=0;$i<count($_SESSION[$myMap]["GROUPS_ON"]);$i++)
			$items[] = intval($_SESSION[$myMap]["GROUPS_ON"][$i]);
		return $items;
	}
	
	function _getLabelItem($layername){
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$labelitem = $_SESSION[$myMap]["LABELITEM"][$layername];
	
	}
	
	/*
	**************************  QUERY WMS

	*/
	
	
	
	function getWMSInfo(){
		
		$oMap = $this->map;		
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$grpId = $_REQUEST["grpid"];
		$grpName = $_SESSION[$myMap]["LAYERGROUPS"][$grpId];
		$aLayersIndexes=$oMap->getLayersIndexByGroup($grpName);
		$numLayers=count($aLayersIndexes);
		for($i=0;$i<$numLayers;$i++){
			$oLayer = $oMap->getLayer($aLayersIndexes[$i]);
			if($oLayer->name == $_REQUEST["item"]) $myLayer = $oLayer;
		}
		$dataResult = array();
		if(isset($oLayer)){
			
			if($_REQUEST["spatialQuery"] == QUERY_CURRENT){//selezione in sessione
				$X = $_SESSION[$myMap]["SELECTIONPOINT"][0];
				$Y = $_SESSION[$myMap]["SELECTIONPOINT"][1];
			}
			else{
				$X = $_REQUEST["imgX"];
				$Y = $_REQUEST["imgY"];
				$_SESSION[$myMap]["SELECTIONPOINT"][0] = $X; 
				$_SESSION[$myMap]["SELECTIONPOINT"][1] = $Y;
			}

			$wmslayer = $oLayer->getMetadata("wms_name");
			$oLayer->set('connection',$oLayer->connection . "&QUERY_LAYERS=" . $wmslayer);
			$wmsResult = file($oLayer->getWMSFeatureInfoURL($X, $Y, 10, "text/plain"));
			$wmsNumRes = count($wmsResult);
			//echo $oLayer->getWMSFeatureInfoURL($X, $Y, 10, "MIME");
			//print_array($wmsResult);
			$row=array();
			
			$dataResult["title"] = $_REQUEST["title"];
			$dataResult["istable"] = 1;
			$dataResult["qtid"] = 0;
			//$dataResult["layer"] = $oLayer->name;
			$dataResult["objid"]=array();
			$dataResult["resultextent"]=array();
			$dataResult["extent"]=array();
			$dataResult["columnwidth"]=array();
			
	        for ($i=0;$i<count($wmsResult);$i++) {
	            if (preg_match ("/ServiceException/i", $wmsResult[$i])) {
					echo "SERVIZIO NON DISPONIBILE";
	                return false;
	            }
	            if (preg_match ("/\sFeature\s/i", $wmsResult[$i])) {
					if($row){
						$dataResult["tableheaders"]=array_keys($row);
						$dataResult["data"][]=array_values($row);
					}
					$row=array();
	            
				} elseif (preg_match ("/\=/", $wmsResult[$i])) {
	                $res = preg_split ("/\=/", $wmsResult[$i]);
					$val=trim(str_replace("'","",$res[1]));
					if(strtoupper(CHAR_SET)=='UTF-8') $val = utf8_encode($val);
					$row[trim($res[0])] = $val;
	            }
				
	        }
			//Aggiungo l'ultimo
			if($row){
				$dataResult["tableheaders"]=array_keys($row);
				$dataResult["data"][]=array_values($row);
			}

			$dataResult["numrows"]=isset($dataResult["data"])?count($dataResult["data"]):0;
			$dataResult["fieldtype"]=isset($dataResult["tableheaders"])?array_values(array_fill(0,count($dataResult["tableheaders"]),1)):array();
			
		}
		
	return $dataResult;	

	}
	
	
	
	/*
	**************************  REFERENCE MAP
	CREA IL REFERENCE
	*/
	function getReferenceMap(){
		//inizializzo l'extent con il/i layer relativi al reference e l'extent della mappa

		$oMap = $this->map;
		$oMap->outputformat->set('name','png');
		$oMap->outputformat->set('driver','GD/PNG');
		$oMap->outputformat->set('extension','png');
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		
		
		
		//$refWidth = $_SESSION[$myMap]["REF_MAP"]["WIDTH"];
		//$refHeight = $_SESSION[$myMap]["REF_MAP"]["HEIGHT"];
		$refGroup = $_SESSION[$myMap]["REF_MAP"]["GROUPS"];

		$oMap->set('width',$_REQUEST["referenceW"]);
		$oMap->set('height',$_REQUEST["referenceH"]);
		
		$_SESSION[$myMap]["REF_MAP"]["WIDTH"] = $_REQUEST["referenceW"];
		$_SESSION[$myMap]["REF_MAP"]["HEIGHT"] = $_REQUEST["referenceH"];
		
		if($_REQUEST["action"]=="reloadref")
			list($xMin,$yMin,$xMax,$yMax)=$_SESSION[$myMap]["MAP_EXTENT"];
		else
			list($xMin,$yMin,$xMax,$yMax)=$_SESSION[$myMap]["REF_MAP"]["EXTENT"];

		$oMap->setExtent($xMin,$yMin,$xMax,$yMax);	
		$allGroup = $_SESSION[$myMap]["LAYERGROUPS"];

		if($allGroup){
			foreach($allGroup as $idx=>$grpName){
				$aLayersIndexes=$oMap->getLayersIndexByGroup($grpName);
				$numLayers=count($aLayersIndexes);
				$status = (in_array($idx,$refGroup))?1:0;
				for($i=0;$i<$numLayers;$i++){
					$oLayer=$oMap->getLayer($aLayersIndexes[$i]);
					$oLayer->set('status',$status);
					//Setto al volo il filtro mapset
					if($_SESSION[$myMap]["FILTER"] && in_array($oLayer->name, $_SESSION[$myMap]["FILTER_LAYER"][$idx])){
						$layerFilter = $_SESSION[$myMap]["FILTER"];
						if($this->msVersion=='7'){
							//ATTENZIONE per velocizzare considero che processing sia fatto da 2 voci CLOSE_CONNECTION e NATIVE_FILTER
							$processing=$oLayer->getProcessing();
							if(count($processing)>1){
								$layerFilter = str_replace("\"","",$processing[1])." AND " .$layerFilter;
								$oLayer->clearProcessing();
								$oLayer->setprocessing("CLOSE_CONNECTION=DEFER");
								$oLayer->setprocessing($layerFilter);
							}
							else{
								$oLayer->setprocessing("NATIVE_FILTER=" . $layerFilter);
							}
						}
						else{
							if($oLayer->getFilterString()) $layerFilter = str_replace("\"","",$oLayer->getFilterString())." AND " .$layerFilter;
							$oLayer->setFilter($layerFilter);
						}
					} 
				}
			}
		}
		$_SESSION[$myMap]["REF_MAP"]["EXTENT"] = array($oMap->extent->minx,$oMap->extent->miny,$oMap->extent->maxx,$oMap->extent->maxy);
		//test sintassi mapfile		
		ms_ResetErrorList();
		try {
			$oImage=@$oMap->draw();
		} 
		catch (Exception $e) {
			$error = ms_GetErrorObj();		
			if($error->code != MS_NOERR){
				$this->mapError=150;
				while(is_object($error) && $error->code != MS_NOERR) {
					print("CREATE MAP ERROR <br>");
					printf("Error in %s: %s<br>\n", $error->routine, $error->message);
					$error = $error->next();
				}
				return;
			}	
			return;
		}
		$imgRef=$oImage->saveWebImage();
		return $imgRef;
	}

	//Ritorna le coordinate in pixel del riquadro refbox
	function getReferenceBox(){
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		if(!isset($_SESSION[$myMap]["REF_MAP"]["WIDTH"])) return;
		$oMap=$this->map;
		$extent=$oMap->extent;
		
		$refWidth = $_SESSION[$myMap]["REF_MAP"]["WIDTH"];
		$refHeight = $_SESSION[$myMap]["REF_MAP"]["HEIGHT"];
		list($xMin,$yMin,$xMax,$yMax) = $_SESSION[$myMap]["REF_MAP"]["EXTENT"];
		$pixelSizeX = ($xMax - $xMin)/$refWidth;		
		$pixelSizeY = ($yMax - $yMin)/$refHeight;	

	   $oRefBoxL  = max(0, round(($extent->minx-$xMin) / $pixelSizeX));
	   $oRefBoxT  = max(0, round(($yMax - $extent->maxy) / $pixelSizeY));	
	   $oRefBoxW  = round(($extent->maxx - $extent->minx)  / $pixelSizeX);
	   $oRefBoxH  = round(($extent->maxy - $extent->miny) / $pixelSizeY);

	   return array($oRefBoxL,$oRefBoxT,$oRefBoxW,$oRefBoxH);

	}
	
	//zoom sul riquadro settato sul reference
	function zoomReference(){
		$oMap=$this->map;
		extract($_REQUEST);
		$myMap = "MAPSET_".$mapset;
		$refWidth = $_SESSION[$myMap]["REF_MAP"]["WIDTH"]; 
		$refHeight = $_SESSION[$myMap]["REF_MAP"]["HEIGHT"];
		list($xMin,$yMin,$xMax,$yMax) = $_SESSION[$myMap]["REF_MAP"]["EXTENT"];		
		$pixelSizeX = ($xMax - $xMin)/$refWidth;		
		$pixelSizeY = ($yMax - $yMin)/$refHeight;	
		$newX = $xMin + $refX*$pixelSizeX;
		$newY = $yMax - $refY*$pixelSizeY;
		
		list($xMin,$yMin,$xMax,$yMax)=$this->geoExtent;
		$dx = ($xMax - $xMin)/2;
		$dy = ($yMax - $yMin)/2;
		$this->setExtent($newX-$dx,$newY-$dy,$newX+$dx,$newY+$dy);

	}

	/*
	********************FUNZIONI DI SELEZIONE: SELEZIONE OGGETTO E ZOOM SU OGGETTO
	*/
	function zoomResult($resultExtent=false){
		//Mette in sessione (se non c'e) l'oggetto e setta l'extent in modo da zoomare la prima volta e mantenere l'oggetto selezionato per la navigazione
		$oMap=$this->map;
		$myMap = "MAPSET_".$_REQUEST["mapset"];

		//Aggiungo gli oggetti in sessione
		if(isset($_REQUEST["objid"]) && $_REQUEST["objid"]){//chiamata da metodo zoomObj su un oggetto
			if(!in_array($_REQUEST["layerGroup"],$_SESSION[$myMap]["GROUPS_ON"]))
				$_SESSION[$myMap]["GROUPS_ON"][] = $_REQUEST["layerGroup"];//accendo tutti i layer del gruppo
			$this->_layerTop($_REQUEST["layerGroup"]);//sposto i layer del gruppo sopra tutti gli altri			
			
			if($_REQUEST["resultAction"]>0){
				$qtId = $_REQUEST["qtid"];
				$_SESSION[$myMap]["RESULT"][$qtId]["LAYERGROUP"] = $_REQUEST["layerGroup"];
				$_SESSION[$myMap]["RESULT"][$qtId]["LAYER"] = $_REQUEST["layername"];
				$_SESSION[$myMap]["RESULT"][$qtId]["ID_FIELD"] = $_REQUEST["layerkey"];
				$_SESSION[$myMap]["RESULT"][$qtId]["STATIC"] = $_REQUEST["staticLayer"];
				$_SESSION[$myMap]["RESULT"][$qtId]["COLOR"] = $_REQUEST["selcolor"];
				$_SESSION[$myMap]["RESULT"][$qtId]["ID_LIST"] = $_REQUEST["objid"];
				/*
				Il porta in promo piano delle selezioni NON funziona perchè le aggiungo dopo (da vedere)
				$oLayer = $oMap->getLayerByName(LAYER_SELECTION.$_REQUEST["layername"]);
				$idxlayer = $oLayer->index;	
				$layNum = $oMap->numlayers;
				print_debug($idxlayer,null,'prova');
				$i=0;
				while ($i <= $layNum+1){ 
					$oMap->moveLayerDown($idxlayer);	
					$i++;
					
				}
				*/
				
			}
		}
		
		if($_REQUEST["resultAction"]>1){
			//Zoom e/o cetra il set di oggetti trovati
			$extent = $resultExtent?$resultExtent:$_REQUEST["extent"];	
			if($_REQUEST["resultAction"]==3){//ricentro la mappa
				$x=$extent[0]+($extent[2]-$extent[0])/2;
				$y=$extent[1]+($extent[3]-$extent[1])/2;
				$mapExtent=$_SESSION[$myMap]["MAP_EXTENT"];
				$w=($mapExtent[2]-$mapExtent[0])/2;
				$h=($mapExtent[3]-$mapExtent[1])/2;
				$this->geoExtent =  array($x-$w,$y-$h,$x+$w,$y+$h);
			}else
				$this->geoExtent = $extent;
		}
		$this->redraw();
		
	}
	
	

	function addRedline(){
	
		extract($_REQUEST);
		$myMap = "MAPSET_$mapset";	
		if(isset($imgX) && isset($imgY)){
			for($i=0;$i<count($imgX);$i++){
				$px = round($Xgeo + $imgX[$i]*$geopixel,2);
				$py = round($Ygeo - $imgY[$i]*$geopixel,2);
				$p[] = "$px $py";
			}
		}
		if(isset($imgT)){
			$px  = round($Xgeo + $imgX[count($imgX)-1]*$geopixel,2);
			$py  = round($Ygeo - $imgY[count($imgY)-1]*$geopixel,2);
			$txtlen=strlen($imgT);
			$px1 = round($px + $txtlen*10*$geopixel,2);	
		}
		
		$myObject=array("TEXT"=>$imgT,"TXTLINE"=>"LINESTRING($px $py,$px1 $py)","LINE"=>"LINESTRING(".implode(",",$p).")");
		$_SESSION[$myMap]["REDLINE"][] = $myObject;

	}
	
	
	//TODO :  RIMUOVERE UN OGGETTO CON SPECIFICO INDICE NON SOLO L'ULTIMO 
	function removeRedline(){
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		if(isset($_SESSION[$myMap]["REDLINE"]) && count($_SESSION[$myMap]["REDLINE"])>0)
			$_SESSION[$myMap]["REDLINE"] = array_slice($_SESSION[$myMap]["REDLINE"],0,count($_SESSION[$myMap]["REDLINE"])-1);
	}

	//Aggiunge gli oggetti in sessione (PER ORA TIPO LINEARE DA COMPLETARE X GENERALIZZARE)
	function addObjects($ssId){

		$myMap = "MAPSET_".$_REQUEST["mapset"];	
        $txtLayer = ms_newLayerObj($this->map);
        $txtLayer->set("name", LAYER_READLINE);
        $txtLayer->set("type", MS_LAYER_LINE);
        $txtLayer->set("status", MS_ON);
		$color = preg_split('/[\s,]+/', COLOR_REDLINE);
		
		// Class properties
        $pntClass = ms_newClassObj($txtLayer);
        $clStyle = ms_newStyleObj($pntClass);
        $clStyle->color->setRGB($color[0], $color[1], $color[2]);

		//MAPSERVER >6.2
		try{
			$label = $pntClass->label;
		}
		catch (Exception $e) {
			$label = new labelObj();
			$pntClass->addLabel($label);
		}
            
        // Label properties
        $label->set("position", MS_UC);
        $label->set("font", "arial");
        // $label->set("type", MS_TRUETYPE);non va in 
        $label->set("size", 14);
        $label->set("wrap", ord(WRAP_READLINE));
        $label->color->setRGB($color[0], $color[1], $color[2]);

		//AGGIUNGO GLI OGGETTI
		$redline = $_SESSION[$myMap]["REDLINE"];
		for($i=0;$i<count($redline);$i++){
			if($redline[$i]["TEXT"]!=''){
				$newShape = ms_shapeObjFromWkt($redline[$i]["TXTLINE"]);
				$newShape->set("text", $redline[$i]["TEXT"]);
				if($newShape) $txtLayer->addFeature($newShape);
			}
			$newShape = ms_shapeObjFromWkt($redline[$i]["LINE"]);
			if($newShape) $txtLayer->addFeature($newShape);
		}
	}
		

	
	function addCustomObject(){
		$myMap = "MAPSET_".$_REQUEST["mapset"];	
		if($_SESSION[$myMap]["CUSTOM_OBJECT"]){
			$layer = ms_newLayerObj($this->map);
			$layer->set("type", MS_LAYER_LINE);
			$layer->set("status", MS_ON);
			if(isset($_SESSION[$myMap]["CUSTOM_OBJECT"]["PROJ4"]))
				$layer->setProjection=$_SESSION[$myMap]["CUSTOM_OBJECT"]["PROJ4"]; 
			$class = ms_newClassObj($layer);
			$style = ms_newStyleObj($class);
			$color = $_SESSION[$myMap]["CUSTOM_OBJECT"]["COLOR"];
			if(!isset($color)) $color=array(0, 0, 255);
			$style->color->setRGB($color[0], $color[1], $color[2]);
            // TODO: apply different Size 
            // $style->symbolname = 'circle';
            // $style->size = 3;
			$line = $_SESSION[$myMap]["CUSTOM_OBJECT"]["LINESTRING"];
			$newShape = ms_shapeObjFromWkt($line);
			if($newShape) $layer->addFeature($newShape);
		}
	}
	
	//Aggiungo alla mappa gli oggetti appartenenti alla selezione corrente (tranne i layer dinamici)
	function addObjectSelected(){
		$oMap = $this->map;
		$myMap = "MAPSET_".$_REQUEST["mapset"];

		foreach($_SESSION[$myMap]["RESULT"] as $result){
			if($result["STATIC"]==1){
				$layerColor = $result["COLOR"];
				$layerGroupId = $result["LAYERGROUP"];
				$layerGroup = $_SESSION[$myMap]["LAYERGROUPS"][$layerGroupId];
				$layerName = $result["LAYER"];
				$idList = $result["ID_LIST"];
				$idField = $result["ID_FIELD"];
				//Trovo il layer con quel nome nel layergroup
				$aLayersIndexes=$oMap->getLayersIndexByGroup($layerGroup);
				foreach ($aLayersIndexes as $idxlayer) {
					$oLayer=$oMap->getLayer($idxlayer);
					if($oLayer->name == $layerName)	break;
				}
				//$oLayer=$oMap->getLayerByName($layerName);tolto l'id dai gruppi e layer
				
				//Aggiungo i livelli di selezione che servono
				$selLayer=$this->_addSelectionLayer($oLayer,$layerColor);
				$sProj=$oLayer->getProjection();
				if($sProj) $selLayer->setProjection($sProj);		
				//Aggiungo un riferimento ogni oggetto selezionato ai layer di selezione
				$count=0;

				if($this->msVersion >= '7'){
					//In ms7 queryByAttributes filtra tutt il layer quindi devo fare un clone
					$tmpExpr = array();
					$clone = ms_newLayerObj($oMap, $oLayer);
					foreach($idList as $id){
						$ret = $clone->queryByAttributes($idField,$id,MS_SINGLE);	
						if($clone->getNumResults()>0){
							$resShape = $clone->getShape($clone->getResult(0));
							if($resShape) $selLayer->addFeature($resShape);
							$count++;						
							if (MAX_OBJ_SELECTED && $count==MAX_OBJ_SELECTED){
								$this->message="Superato il massimo numero di oggetti selezionabili";
								break;
							}
						}
					}
				}
				elseif($this->msVersion >= '6'){
					$tmpExpr = array();
					//TOLGO LA CLASSIFICAZIONE CHE CREA PROBLEMI
					for ($cl=0; $cl < $oLayer->numclasses; $cl++) {
						$oCls = $oLayer->getClass($cl);
						$tmpExpr[$cl] = $oCls->getExpressionString();
						$oCls->setExpression('');
					}
					foreach($idList as $id){
						$ret = $oLayer->queryByAttributes($idField,$idField."=".$id,MS_SINGLE);	
						if($oLayer->getNumResults()>0){
							$resShape = $oLayer->getShape($oLayer->getResult(0));
							if($resShape) $selLayer->addFeature($resShape);
							$count++;						
							if (MAX_OBJ_SELECTED && $count==MAX_OBJ_SELECTED){
								$this->message="Superato il massimo numero di oggetti selezionabili";
								break;
							}
						}
					}
					//LA RIMETTO --- vedere nuovo metodo clone de layer
					for ($cl=0; $cl < $oLayer->numclasses; $cl++) {
						$oCls = $oLayer->getClass($cl);
						$oCls->setExpression($tmpExpr[$cl]);
					}					
			    }
				else{
					$oLayer->open();
					foreach($idList as $id){
						$resShape = $oLayer->getFeature($id);
						$count++;
						if($resShape) $selLayer->addFeature($resShape);
						if (MAX_OBJ_SELECTED && $count==MAX_OBJ_SELECTED){
							$this->message="Superato il massimo numero di oggetti selezionabili";
							break;
						}
					}
					$oLayer->close();		
				}
			}
		}
	}
	

	function _addSelectionLayer($objLayer,$layerColor){
		// Aggiungo i layer per i selezionati: un layer di punti e un layer di linee(nel caso di linee e poligoni)
		//TODO: copiare il layer in modo da restituire gli oggetti selezionati al posto dei pallini ????
		
		$oMap=$this->map;
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$selLayer = ms_newLayerObj($oMap);
		$selLayer->set("name", LAYER_SELECTION.$objLayer->name);
	
		if ($objLayer->type == MS_LAYER_LINE || $objLayer->type == MS_LAYER_POLYGON){
			$selLayer->set("type", MS_LAYER_LINE);//Uso sempre solo il bordo
			$selLClass = ms_newClassObj($selLayer);
			$clLStyle = ms_newStyleObj($selLClass);
			$clLStyle->outlinecolor->setRGB($layerColor[0], $layerColor[1], $layerColor[2]);
			$clLStyle->set("width", WIDTH_SELECTION);
		}
		else{
			$selLayer->set("type", MS_LAYER_POINT);
			$nId = ms_newsymbolobj($oMap, "obj_select");
			$oSymbol = $oMap->getsymbolobjectbyid($nId);
			$oSymbol->set("type", MS_SYMBOL_ELLIPSE);
			$oSymbol->set("filled", MS_TRUE);
			$aPoints=array(1,1);
			$oSymbol->setpoints($aPoints);			
			$selClass = ms_newClassObj($selLayer);
			$selStyle = ms_newStyleObj($selClass);
			$selStyle->color->setRGB($layerColor[0], $layerColor[1], $layerColor[2]);
			$selStyle->set("symbolname", "obj_select");
			if(!defined('SIZE_SELECTION')) define('SIZE_SELECTION',20);
			$selStyle->set("size", SIZE_SELECTION);
		}
		$selLayer->set("status", MS_ON);	
		return $selLayer;
	}
	
	function addSelectionObject(){
		$oMap=$this->map;
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$selLayer = ms_newLayerObj($oMap);
		$selLayer->set("name", LAYER_SELECTION.'_S');	
		$selectionColor = $_SESSION[$myMap]["SELECTION_COLOR"];
		$selLayer->set("status", MS_ON);
		$selLayer->set("type", MS_LAYER_POLYGON);
		$selLayer->set("opacity", TRASP_SELECTION);
		$selClass = ms_newClassObj($selLayer);
		$selStyle = ms_newStyleObj($selClass);		
		$selStyle->outlinecolor->setRGB($selectionColor[0], $selectionColor[1], $selectionColor[2]);
		$selStyle->set("width", WIDTH_SELECTION);
		if(isset($_SESSION[$myMap]["SELECTION_POLYGON"])){
			$wkt = $_SESSION[$myMap]["SELECTION_POLYGON"];
			$shpSelect = ms_shapeObjFromWkt($wkt);
			//print_debug($shpSelect,null,'shapeselect');
			if(isset($shpSelect)) $selLayer->addFeature($shpSelect);		
		}

		
	}

	//Sposto ogni livello del gruppo selezionato in fondo alla lista e quindi in cima alla mappa
	function _layerTop($layergroup){
		$myMap="MAPSET_".$_REQUEST["mapset"];
		$oMap=$this->map;
		$aLayerNames=$oMap->getAllLayerNames();
		$layNum = count($aLayerNames);
		if(in_array("__MASK__",$aLayerNames)) $layNum--;
		$layergroup=$_SESSION[$myMap]["LAYERGROUPS"][$layergroup];	
		$aLayersIndexes=$oMap->getLayersIndexByGroup($layergroup);
		foreach ($aLayersIndexes as $idxlayer) {
			for ($i=0; $i < $layNum; $i++){
				$oMap->moveLayerDown($idxlayer);
			}
		}
		//Salvo in sessione il nuovo ordine
		$_SESSION[$myMap]["LAYERS_ORDER"]=$oMap->getlayersdrawingorder();
	}

	function getMessage(){
		return $this->message;
	}
	

	//AGGIUNGE LABEL COPYRIGHT SU IMMAGINE GENERATA
	function setImageLabel($fact=1) {
		$myMap="MAPSET_".$_REQUEST["mapset"];
		extract($_SESSION[$myMap]["IMAGELABEL"]);
		$sLayer=LAYER_IMAGELABEL;
		$sText=$_SESSION[$myMap]["IMAGELABEL"]["text"];
		$sFont=$_SESSION[$myMap]["IMAGELABEL"]["font"];
		$Size=$_SESSION[$myMap]["IMAGELABEL"]["size"]*$fact;
		$Color=$_SESSION[$myMap]["IMAGELABEL"]["color"];				
		$sOffset_x=$_SESSION[$myMap]["IMAGELABEL"]["offset_x"];
		$sOffset_y=$_SESSION[$myMap]["IMAGELABEL"]["offset_y"];
		$sPosition=$_SESSION[$myMap]["IMAGELABEL"]["position"];
		$colorList = preg_split('/[\s,]+/', $Color);
		
		if (($sLayer!="") && ($sText!="") && ($sOffset_x !="") && ($sOffset_y !="")&& ($sPosition!="")) {
			$oMap=$this->map;
			$sPosition=strtoupper($sPosition);
			$iOffset=array(intval($sOffset_x),intval($sOffset_y));
			$imageWidth=$oMap->width;
			$imageHeight=$oMap->height;
			switch ($sPosition) {
				case "UL":
				$iPosition=MS_LR;
					break;

				case "LL":
					$sOffset[1]=$imageHeight - $iOffset[1];
					$iPosition=MS_UR;
				break; 

				case "LR":
					$iOffset[0]=$imageWidth - $iOffset[0];
					$iOffset[1]=$imageHeight - $iOffset[1];  
					$iPosition=MS_UL;
				break;

				case "UR":
					$iOffset[0]=$imageWidth - $iOffset[0];  
					$iPosition=MS_LL;
				break;
			}

			$oPoint=ms_newPointObj();
			$oPoint->setXY($iOffset[0],$iOffset[1]);	

			$oLine=ms_newLineObj();
			$oLine->add($oPoint);

			$oShape=ms_newShapeObj(MS_SHAPE_POINT);
			$oShape->set('text',$sText);		
			$oShape->add($oLine);

			$oLayer = ms_newLayerObj($oMap);
			$oLayer->set('name', $sLayer);
			$oLayer->set('status', MS_DEFAULT);
			$oLayer->set('transform', MS_FALSE);
			$oLayer->set('type', MS_LAYER_POINT);
			$oLayer->set('sizeunits', MS_PIXELS);	
			
			$oClass = ms_newClassObj($oLayer);
			
			//MAPSERVER >6.2

			if ($this->msVersion < 6){
				$label = $oClass->label;
			}
			else {
				$label = new labelObj();
				$oClass->addLabel($label);
			}
			if ($this->msVersion < 7){
				$label->set('type', MS_TRUETYPE);
			}
			$label->set('font', $sFont);
			$label->color->setRGB($colorList[0], $colorList[1], $colorList[2]);
			$label->set('size', $Size);
			$label->outlinecolor->setRGB(255, 255, 255);
			$label->set('position', $iPosition);			
			
			$oLayer->addFeature($oShape);

		}
	}	


}



?>
