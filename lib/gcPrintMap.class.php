<?php
//TODO DOPPIA VERSIONE 1 CON FPDF L'ALTRA CON TEMPLATE CON TCPDF
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


require_once('../../lib/fpdf.php');
define('MAP_MAXSCALE',10000000);
define('MAP_MINSCALE',0);
//require_once('tcpdf/config/lang/eng.php');
class printMap {

	var $imageFile;
	var $imageWidth;
	var $imageHeight;
	var $scaleimage;
	var $imageDim=Array("height"=>null,"width"=>null);
	var $rapporto;
	/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	var $aLegend=Array();
	var $legendCol;
	var $legendImgSize;
	var $colSize;
	var $map;
	var $layerList;
	/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	var $title;
	var $header;
	var $footer;
	var $pageLayout;
	var $format;
	var $margin=Array();
	var $font;
	var $fontSize;
	var $pageDim=Array();
	/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	var $errors;
	var $maperror;
	/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
	var $pdf;
	var $db;

	function __construct($mapsetName,$pageLayout,$pageFormat){
		$this->mapsetName=$mapsetName;
		$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$this->db->db_connect_id) $this->mapError=100;
		$this->pdf = new FPDF($pageLayout,"pt",$pageFormat);
		$this->pageLayout = $pageLayout;
		$this->pageFormat = $pageFormat;
		$this->font = PRINT_PDF_FONT;
		
		$this->_initPage();
		
	}
	
	function __destruct(){
		$this->db->sql_close();
	}
	
	function _initPage(){
		
		$this->cellMargin=2;
		$this->a4Ratio=$this->pdf->w/595.28;
		//TODO CALCOLARE LA DIMESIONE DELL IMMAGINE CON I MARGINI CORRETTI METTERE TUTTO SU MATRICE/FILE DI CONFIGURAZIONE
		switch ($this->pageFormat){
			case "A0":
				$this->legendCol=($this->pageLayout=="P")?(4):(7);
				$this->margin=Array("left"=>50,"top"=>50,"right"=>50,"bottom"=>50);
				$this->fontSize=Array("default"=>25,"layergroup"=>27,"theme"=>30,"title"=>35);
				//$this->rowHeight=$this->pdf->pixelsToUnits(24);
				$this->legendImgSize=Array("width"=>36,"height"=>24);
				break;
			case "A1":
				$this->legendCol=($this->pageLayout=="P")?(5):(6);
				$this->margin=Array("left"=>75,"top"=>75,"right"=>75,"bottom"=>75);
				$this->fontSize=Array("default"=>18,"layergroup"=>22,"theme"=>25,"title"=>32);
				//$this->rowHeight=$this->pdf->pixelsToUnits(24);
				$this->legendImgSize=Array("width"=>36,"height"=>24);
				break;
			case "A2":
				$this->legendCol=($this->pageLayout=="P")?(4):(5);
				$this->margin=Array("left"=>60,"top"=>60,"right"=>60,"bottom"=>60);
				$this->fontSize=Array("default"=>14,"layergroup"=>18,"theme"=>21,"title"=>28);
				//$this->rowHeight=$this->pdf->pixelsToUnits(24);
				$this->legendImgSize=Array("width"=>36,"height"=>24);
				break;
			case "A3":
				$this->legendCol=($this->pageLayout=="P")?(3):(4);
				$this->margin=Array("left"=>45,"top"=>45,"right"=>45,"bottom"=>45);
				$this->fontSize=Array("default"=>11,"layergroup"=>14,"theme"=>17,"title"=>24);
				//$this->rowHeight=$this->pdf->pixelsToUnits(24);
				$this->legendImgSize=Array("width"=>36,"height"=>24);
				break;
			case "A4":
				$this->legendCol=($this->pageLayout=="P")?(2):(3);
				$this->margin=Array("left"=>29,"top"=>29,"right"=>29,"bottom"=>29);
				$this->fontSize=Array("default"=>8,"layergroup"=>10,"theme"=>12,"title"=>20);
				//$this->rowHeight=$this->pdf->pixelsToUnits(16);
				$this->legendImgSize=Array("width"=>24,"height"=>16);
				break;
			default:
				die("Nessuna dimensione della Pagina.");
				break;
		}

		
		$marginW=$this->margin["left"]+$this->margin["right"];
		$marginH=$this->margin["top"]+$this->margin["bottom"];

		$this->rowHeight=$this->legendImgSize["height"];

		$this->pageDim=Array("width"=>($this->pdf->w - $marginW),"height"=>( $this->pdf->h - $marginH));
		
		$titleHeight=ceil($this->fontSize["title"]+($this->fontSize["default"]/10));
		$this->reqImageWidth=$this->pageDim["width"]*(MAP_DPI/72);
		$this->reqImageHeight=($this->pageDim["height"]-$titleHeight)*(MAP_DPI/72);
		
		$this->imageWidth = $this->pageDim["width"];
		$this->imageHeight = $this->pageDim["height"]-$titleHeight;
		$this->colSize=floor(($this->pageDim["width"]-4*$this->legendCol)/$this->legendCol);
		$this->pdf->SetFont($this->font, null, $this->fontSize["default"]);
		$this->pdf->SetMargins($this->margin["left"],$this->margin["top"],$this->margin["right"]);
		$this->pdf->SetAutoPageBreak(False, $this->margin["bottom"]);
		$this->pdf->AddPage();


	}
	
	
	function printPdf(){
		
		if(!$this->mapImage){
			$this->errors[]="No Image File";
			$this->maperror=20;
			return;
		}
		elseif(!file_exists($this->mapImage)){
			$this->errors[]="Image File not Found";
			$this->maperror=21;
			return;
		}
		
		$this->title=$_REQUEST["printtitle"];
		$this->pdfwriteMapImage();
		
		if($this->legendOption>0){
			$this->createLegend();
			$this->pdf->AddPage();
			$this->pdfwriteLegend();
		}
		$filename=substr($this->mapImage,0,-3)."pdf";
		if (!$this->maperror){
			$this->pdf->Output($filename,"F");
			return basename($filename);
		}		
	}
	
	function getStrWidth($str,$colW,$fSize=null,$fFamily=null,$fWeight=null){
		if(!$fFamily) $fFamily=$this->font;
		if(!$fSize) $fSize=$this->fontSize["default"];
		$this->pdf->SetFont($fFamily,null,$fSize);
		$tmpStr=explode(" ",$str);
		$sepLen=$this->pdf->GetStringWidth(" ");
		if(!count($tmpStr)){
			return $this->pdf->GetStringWidth($text);
		}
		else{
			$totLen=$this->pdf->GetStringWidth($tmpStr[0])+$sepLen;
			$remainingW=$colW-fmod($totLen,$colW);
			for($i=1;$i<count($tmpStr);$i++){
				if (($this->pdf->GetStringWidth($tmpStr[$i])+$sepLen) > $remainingW ){
					$totLen=(floor(($totLen/$colW)+1)*$colW + ($this->pdf->GetStringWidth($tmpStr[$i]))+$sepLen);
				}
				else{
					$totLen+=(($this->pdf->GetStringWidth($tmpStr[$i]))+$sepLen);
				}
				$remainingW=$colW-fmod($totLen,$colW);
			}
			return $totLen;
		}
	}
	//NUOVA FUNZIONE DI ACQUISIZIONE DELLA LEGENDA
	function createLegend(){
	
		$mapset=$_REQUEST["mapset"];
		$groupOn=$_SESSION["MAPSET_$mapset"]["GROUPS_ON"];
			
		$sql="SELECT distinct theme_id,theme_title as title,theme_name as name,theme_order FROM ".DB_SCHEMA.".mapset_layergroup left join ".DB_SCHEMA.".layergroup using(layergroup_id) left join ".DB_SCHEMA.".theme using(theme_id) WHERE mapset_NAME='$mapset' AND layergroup_id IN (".@implode(',',$groupOn).") order by theme_order;";
		if(!$this->db->sql_query($sql)){
			$this->maperror=10;
			$this->errors=$this->db->errors;
			return;
		}
		$themeList=$this->db->sql_fetchrowset();
		for($i=0;$i<count($themeList);$i++){
			$theme=$themeList[$i];
			$text=($theme["title"])?($theme["title"]):($theme["name"]);
			$text=utf8_encode ($text);
			$this->pdf->SetFont($this->font, null, $this->fontSize["theme"]);
			$height=ceil($this->pdf->GetStringWidth($text)/$this->pageDim["width"])*$this->rowHeight;
			$this->aLegend[]=Array(0=>Array("value"=>$text,"width"=>$this->pageDim["width"],"height"=>$height,"type"=>"theme"),"rowheight"=>$height);
			$sql="SELECT distinct layergroup_id,layergroup_title as title,layergroup_name as name,layergroup_order FROM ".DB_SCHEMA.".mapset_layergroup left join ".DB_SCHEMA.".layergroup using(layergroup_id) left join ".DB_SCHEMA.".theme using(theme_id) WHERE mapset_name='$mapset' and theme_id=".$theme["theme_id"]." AND layergroup_id IN (".@implode(',',$groupOn).") order by layergroup_order;";
			if(!$this->db->sql_query($sql)){
				$this->maperror=10;
				$this->errors=$this->db->errors;
				return;
			}
			$lgroupList=$this->db->sql_fetchrowset();
			for($j=0;$j<count($lgroupList);$j++){
				$lgroup=$lgroupList[$j];
				$text=($lgroup["title"])?($lgroup["title"]):($lgroup["name"]);
				$text=utf8_encode ("      ".$text);$text;
				$this->pdf->SetFont($this->font, null, $this->fontSize["layergroup"]);
				$height=ceil($this->pdf->GetStringWidth($text)/$this->pageDim["width"])*$this->rowHeight;
				$this->aLegend[]=Array(0=>Array("value"=>$text,"width"=>$this->pageDim["width"],"height"=>$height,"type"=>"layergroup"),"rowheight"=>$height);
				$sql="SELECT distinct class_id,class_name as name,class_title as title,class_image,layer_order,class_order FROM ".DB_SCHEMA.".layer left join ".DB_SCHEMA.".class using(layer_id) where legendtype_id=1 and layergroup_id=".$lgroup["layergroup_id"]." AND layergroup_id in (".implode(',',$groupOn).") AND $this->scale >=coalesce(class.minscale::integer,".MAP_MINSCALE.") AND  $this->scale <= coalesce(class.maxscale::integer,".MAP_MAXSCALE.") AND  $this->scale >=coalesce(layer.minscale::integer,".MAP_MINSCALE.") AND  $this->scale <= coalesce(layer.maxscale::integer,".MAP_MAXSCALE.")  order by layer_order,class_order ;";
				print_debug($sql,null,'LEGENDA');
				if(!$this->db->sql_query($sql)) $this->errors=$this->db->errors;
				
				$clsList=$this->db->sql_fetchrowset();
				$maxHeight=$this->rowHeight;
				if(!count($clsList)){
					array_pop($this->aLegend);
				}
				for($k=0;$k<count($clsList);$k++){
					
					$cls=$clsList[$k];
					$text=($cls["title"])?($cls["title"]):($cls["name"]);
					$text=utf8_encode ($text);
					$this->pdf->SetFont($this->font, null, $this->fontSize["default"]);
					
					//Scrivo l'icona della classe in tmp
					if($cls["class_image"]){
						$classImageFile = IMAGE_PATH.time().$cls["class_id"].'.png';
						$f=fopen($classImageFile,'w+');
						$image=pg_unescape_bytea($cls["class_image"]);
						fwrite($f,$image);
						fclose($f);
					}
							
					$width=($this->pageDim["width"]/($this->legendCol)-$this->cellMargin)-$this->legendImgSize["width"];
					$strWidth=$this->getStrWidth($text,$width,$this->font,null,$this->fontSize["default"]);
					$height=ceil($strWidth/$width)*$this->rowHeight;					
					$maxHeight=($height > $maxHeight)?($height):($maxHeight);
					$tmp[]=Array("value"=>$text,"width"=>$width,"height"=>$height,"type"=>"standard","image"=>$classImageFile);
					if($k!=0 && ($k%$this->legendCol)==($this->legendCol-1)){
						$tmp["rowheight"]=$maxHeight;
						$this->aLegend[]=$tmp;
						$tmp=Array();
						$maxHeight=$this->rowHeight;
					}
				}
				if(count($tmp)){
					$tmp["rowheight"]=$maxHeight;
					$this->aLegend[]=$tmp;
					$tmp=Array();
					$maxHeight=$this->rowHeight;
				}
			}
			
		}
	}
	
	private function _writeTitle($str){
		$this->pdf->SetFont($this->font, null, $this->fontSize["title"]);
		$h=$this->_getFontHeight();
		$this->totHeight+=$h;
		$this->pdf->Cell($this->imageDim["width"],$h,$str,1,1,"C");
		$this->pdf->SetFont($this->font, "", $this->fontSize["default"]);
		
	}
	
	private function _getLineNum($str){
		
		return ceil((double)$this->pdf->GetStringWidth($str)/(double)($this->colSize-$this->legendImgSize["width"]));
	}
	function pdfwriteMapImage($X=0,$Y=0){
		
		$scale=$_REQUEST["labelscale"]." 1:$this->scale ($this->pageFormat)";
		if (function_exists('iconv')) {
			$scale = iconv('UTF-8', 'ISO-8859-15', $scale);
		}
		$this->pdf->SetFont($this->font, null, $this->fontSize["title"]);
		$h=ceil($this->fontSize["title"]+2*($this->fontSize["title"]/10));
		
		$title = $this->title;
		if (function_exists('iconv')) {
			$title = iconv('UTF-8', 'ISO-8859-15', $title);
		}
		if(!empty($this->imageWidth) && !empty($title)){
			$this->pdf->Cell($this->imageWidth,$h,$title,1,1,"C");
		}
		if(!$X) $X=$this->pdf->GetX();
		if(!$Y) $Y=$this->pdf->GetY();
		
		$this->pdf->Image($this->mapImage,$X,$Y,$this->imageWidth,null,"PNG");
		$this->pdf->SetFont($this->font, "", $this->fontSize["default"]);
		$this->pdf->Cell($this->imageWidth,$this->imageHeight,'',1,1);
		$h=ceil($this->fontSize["title"]+($this->fontSize["default"]/10));
		$this->pdf->Cell($this->imageDim["width"],1,'',0,1,"L");
		$this->pdf->SetY($Y+$this->imageHeight-$h);
		$this->pdf->SetFillColor(255,255,255);
		$colW=$this->getStrWidth($scale,$this->fontSize["title"]);
		$this->pdf->Cell($colW+($colW/20),$h,$scale,1,1,"C",1);
	}	
	
	private function _writeSingleLine($data,$h,$align,$newline){
		$this->pdf->Cell($data["width"],$data["height"],$data["value"],0,0,$align,0);
		$this->pdf->SetX($this->pdf->GetX()-$data["width"]);
		$this->pdf->Cell($data["width"]+$this->cellMargin,$h,'',1,$newline,'',0);
	}
	private function _writeMultiLine($data,$h,$align,$newline){
		$xStart=$this->pdf->GetX();
		$this->pdf->MultiCell($data["width"],$this->rowHeight,$data["value"],0,$align);
		$this->pdf->SetXY($xStart,$this->pdf->GetY()-$data["height"]);
		$this->pdf->Cell($data["width"]+$this->cellMargin,$h,'',1,$newline);
		
	}
	
	function pdfwriteLegend(){
		for($i=0;$i<count($this->aLegend);$i++){
			$row=$this->aLegend[$i];
			$nl=0;
			for($j=0;$j<count($row)-1;$j++){
				$cell=$row[$j];
				if($j==(count($row)-2)) $nl=1;
				switch($cell["type"]){
					case "theme":
						$align='L';
						$this->pdf->SetFont($this->font, "bi", $this->fontSize["theme"]);
						$th=$row;
						if($this->pageDim["height"]<($this->pdf->getY()+$row["rowheight"])) $this->pdf->AddPage();
						$this->pdf->Cell(1,5,'',0,1);
						break;
					case "layergroup":
						$align='L';
						$lgr=$row;
						$this->pdf->SetFont($this->font, "bi", $this->fontSize["layergroup"]);
						if($this->pageDim["height"]<($this->pdf->getY()+$row["rowheight"])) $this->pdf->AddPage();
						break;
					default:
						$align='L';
						$this->pdf->SetFont($this->font, "", $this->fontSize["default"]);
						if($this->pageDim["height"]<($this->pdf->getY()+$row["rowheight"])){
							$this->pdf->AddPage();
							//$this->pdf->SetFont($this->font, "bi", $this->fontSize["theme"]);
							$nlint=0;
							for($k=0;$k<count($th)-1;$k++){
								if($k==(count($th)-2)) $nlint=1;
								if($th[$k]["height"]==$this->rowHeight){
									$this->_writeSingleLine($th[$k],$th["rowheight"],'L',$nlint);
								}
								else{
									$this->_writeMultiLine($th[$k],$th["rowheight"],'L',$nlint);
								}
							}
							$nlint=0;
							$this->pdf->SetFont($this->font, "bi", $this->fontSize["layergroup"]);
							for($k=0;$k<count($lgr)-1;$k++){
								if($k==(count($lgr)-2)) $nlint=1;
								if($lgr[$k]["height"]==$this->rowHeight){
									$this->_writeSingleLine($lgr[$k],$lgr["rowheight"],'L',$nlint);
								}
								else{
									$this->_writeMultiLine($lgr[$k],$lgr["rowheight"],'L',$nlint);
								}
							}
							$this->pdf->SetFont($this->font, "", $this->fontSize["default"]);
							
						}
						$this->pdf->Image($cell["image"],$this->pdf->GetX(),$this->pdf->GetY(),$this->legendImgSize["width"],$this->legendImgSize["height"],'png');
						//$this->pdf->setX($this->pdf->GetX()-$this->legendImgSize["width"]);
						$this->pdf->Cell($this->legendImgSize["width"],$row["rowheight"],'',1);
						break;
				}
				if($cell["height"]==$this->rowHeight){
					$this->_writeSingleLine($cell,$row["rowheight"],$align,$nl);
				}
				else{
					$this->_writeMultiLine($cell,$row["rowheight"],$align,$nl);
				}
					
			}
		}
	}
	private function _getFontHeight(){
		$yStart=$this->pdf->GetY();
		$this->pdf->Cell(10,1,' ',0,1);
		$yEnd=$this->pdf->GetY();
		$this->pdf->SetY($yStart);
		return ($yEnd-$yStart);
	}
	private function _filename($str){
		
		return $str.".pdf";
	}
	

}
?>
