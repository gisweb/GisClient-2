<?php
function handleReportError($errno, $errstr, $errfile, $errline){
	$mex='<html>
	<title>Report</title>
	<body>
		<table width="100%" align="center">
			<tr>
				<td style="font-size:14px;"><h2 style="color:red;">Si è verificato un errore nella generazione del Report</h2><p><b>'.$errstr.'</b></p></td>
			</tr>
		</table>
		<p><input type="button" value="Chiudi" onclick="javascript:window.opener.focus();window.close();"></p>
	</body>
</html>';
	switch ($errno) {
		case E_ERROR:
		case E_USER_ERROR:
			die($mex);
			break;
		default:
			break;
	}
}

require_once ROOT_PATH."lib/tcpdf.php";

class reportPDF extends TCPDF{

	public function initReport($o,$s,$color=Array(),$font=Array(),$margin=Array()){
		
		$this->pageFont=$font;
		$this->color=$color;
		$this->pageMargin=$margin;
		$this->pageWidth=$this->getPageWidth()-($this->pageMargin["left"]+$this->pageMargin["right"]);
		$this->pageHeight=$this->getPageHeight()-($this->pageMargin["bottom"]+$this->pageMargin["top"]);
		$this->SetAuthor("Gis&Web");
		$this->SetTitle("Report");
		$this->setHeaderFont(Array($this->pageFont["family"], '', $this->pageFont["size"]["titolo"]));
		
		// set default header data 
		$this->setFooterFont(Array($this->pageFont["family"], '', $this->pageFont["size"]["titolo"]));
		$this->SetMargins($this->pageMargin["left"],$this->pageMargin["top"],$this->pageMargin["right"]);
		$this->SetAutoPageBreak(TRUE, $this->pageMargin["bottom"]);
		$this->AliasNbPages();
		$this->AddPage();
		//ALTEZZA DELLA RIGA
		foreach($this->pageFont["size"] as $key=>$val){
			$this->SetFont(null, "",$this->pageFont["size"][$key]);
			$yStart=$this->GetY();
			$this->Cell(10,1,' ',0,1);
			$yEnd=$this->GetY();
			$this->SetY($yStart);
			$this->rowHeight[$key]=($yEnd-$yStart)+4;
			
		}

		
	}
	public function Header() { 
        $this->SetFont($this->pageFont["family"],'B',$this->pageFont["size"]["titolo"]); 
        $this->Cell($this->pageWidth,$this->rowHeight["titolo"],'Sistemi Informativi Territoriali',0,1,'C'); 
		$this->Ln(10);
    } 
	public function Footer() { 
        $this->SetFont($this->pageFont["family"],'i',$this->pageFont["size"]["somma"]); 
		$this->SetY($this->pageHeight);
		$this->Ln(10);
		$this->SetFillColor(200,200,200);
        $this->Cell($this->pageWidth,$this->rowHeight["somma"],'GeoWeb Reports',1,1,'R',1); 
    }
	
	private function _getFontHeight(){
		$yStart=$this->pdf->GetY();
		$this->pdf->Cell(10,1,' ',0,1);
		$yEnd=$this->pdf->GetY();
		$this->pdf->SetY($yStart);
		return ($yEnd-$yStart);
	}
	
	public function getHeaders($headers){
		if($headers){
			$w=0;
			foreach(array_values($headers) as $val){
					if($val["type"]) $this->headers[]=$val;
					if (!$val["width"]) $j++;
					else
						$w+=$val["width"];
			}
			
			$remainingWidth=$this->pageWidth*(1-((double)$w/100));
			if($j) $this->avgCellWidth=($remainingWidth/$j);
			else
				$this->avgCellWidth=(double)($this->pageWidth/$this->nCols);
			
			$this->nCols=count($this->headers);	
			$h=array_keys($headers);
			$v=array_values($headers);
			
			for($j=0;$j<count($h);$j++){	// Costruisco intestazione tabella dati
				$strWidth=$this->getStrWidth($h[$j],$v["width"],null,null,$this->pageFont["height"]-1);
				$w=($v["width"])?($v["width"]):($this->avgCellWidth);
				$height=ceil($strWidth/$w)*$this->rowHeight['dati'];
				$this->intestazione[$j]=Array("value"=>$h[$j],"width"=>($v["width"])?($v["width"]):($this->avgCellWidth),"height"=>$height,"type"=>"intestazione");
			}
		}
		else{
			$this->avgCellWidth=$this->pageWidth;
			$this->nCols=0;
		}
	}
	function getStrWidth($str,$colW,$fSize='',$fFamily='',$fWeight=''){
		$this->SetFont($fFamily, $fWeight, $fSize);
		$tmpStr=explode(" ",$str);
		$sepLen=$this->GetStringWidth(" ");
		if(!count($tmpStr)){
			return $this->GetStringWidth($text);
		}
		else{
			$totLen=$this->GetStringWidth($tmpStr[0])+$sepLen;
			$remainingW=$colW-fmod($totLen,$colW);
			for($i=1;$i<count($tmpStr);$i++){
				if (($this->GetStringWidth($tmpStr[$i])+$sepLen) > $remainingW ){
					$totLen=(floor(($totLen/$colW)+1)*$colW + ($this->GetStringWidth($tmpStr[$i]))+$sepLen);
				}
				else{
					$totLen+=(($this->GetStringWidth($tmpStr[$i]))+$sepLen);
				}
				$remainingW=$colW-fmod($totLen,$colW);
			}
			return $totLen;
		}
	}
	public function getData($data) { 
		$dataCount=0;
        for($i=0;$i<count($data);$i++){
			$key=key($data[$i]);
			switch($key){
				case "titolo":
				case "aggregazione":
				case "somme":
					for($j=0;$j<count($data[$i][$key]);$j++){
						$data[$i][$key][$j]=$data[$i][$key][$j];
					}
					$ident=count($data[$i][$key])-1;
					$text=@implode('',$data[$i][$key]);
					$height=ceil($this->GetStringWidth($text)/$this->pageWidth)*$this->rowHeight[$key];
					$this->data[$i][0]=Array("value"=>$text,"width"=>$this->pageWidth,"height"=>$height,"type"=>"$key","level"=>$ident);
					$this->data[$i]["rowheight"]=$height;
					break;
				default:
					$maxHeight=$this->rowHeight[$key];
					for($j=0;$j<count($data[$i][$key]);$j++){
						$text=$data[$i][$key][$j];
						$width=($this->headers[$j]["width"])?($this->pageWidth*($this->headers[$j]["width"]/100)):($this->avgCellWidth);
						$strWidth=$this->getStrWidth($text,$width,null,null,$this->pageFont["height"]-1);
						$height=ceil($strWidth/$width)*$this->rowHeight[$key];
						$this->data[$i][$j]=Array("value"=>$text,"width"=>($width)?($width):($this->avgCellWidth),"height"=>$height,"type"=>$key);
						$maxHeight=($height > $maxHeight)?($height):($maxHeight);
						if($key=='intestazione') $this->intestazione[$j]=Array("value"=>$text,"width"=>($width)?($width):($this->avgCellWidth),"height"=>$height);
						if($key=='dati') $dataCount++;
					}
					$this->data[$i]["rowheight"]=$maxHeight;
					if($key=='intestazione') $this->intestazione["rowheight"]=$maxHeight;
					break;
			}
		}
		if($this->nCols) $this->nDataResult=($dataCount/$this->nCols);
		print_debug($this->data,null,'DATA1');
    }
	/*AGGIUNTA*/
	public function getData1($data,$level,$allHeaders){
		//if(!$level) print_array($data);
		if (is_array($data["group"])){						//  RAGGRUPPAMENTO
			foreach($data["group"] as $key=>$val){
				$text=$allHeaders[$level]." : ".$key;
				$this->getGroup($text,$level);
				if($data["aggregate_data"]) $this->getAggregate($data["aggregate_data"],$level);
				$this->getData1($val,$level+1,$allHeaders);
			}
		}
		else{												//  TABELLA DATI
			if($data["data"]){
				$this->getTableData($data["data"],$level);
			}
		}
	}
	public function getGroup($group,$l){
		$height=ceil($this->GetStringWidth($group)/$this->pageWidth)*$this->rowHeight['titolo'];
		$this->data[]=Array("rowheight"=>$height,Array("value"=>$group,"width"=>$this->pageWidth,"height"=>$height,"type"=>"aggregazione","level"=>$l));
	}
	public function getAggregate($aggData,$l){
		print_array($aggData);
	}
	public function getTableData($d,$l){
		$maxHeight=$this->rowHeight['dati'];
		$this->data[]=$this->intestazione;
		for($i=0;$i<count($d);$i++){	//Ciclo sulle righe della tabella dei dati
			
			$tmp=Array();
			for($j=0;$j<count($this->headers);$j++){
				//$text=$d[$i][$this->headers[$j]["pos"]];
				$text=$d[$i][$j+1];
				$width=($this->headers[$j]["width"])?($this->pageWidth*($this->headers[$j]["width"]/100)):($this->avgCellWidth);
				$strWidth=$this->getStrWidth($text,$width,null,null,$this->pageFont["height"]-1);
				$height=ceil($strWidth/$width)*$this->rowHeight['dati'];
				$tmp[]=Array("value"=>$text,"width"=>($width)?($width):($this->avgCellWidth),"height"=>$height,"type"=>'dati');
				$maxHeight=($height > $maxHeight)?($height):($maxHeight);
				
				$dataCount++;
			}
			$this->data[]=$tmp;
		}
	}
	
	/*FINE AGGIUNTA*/
	private function _writeSingleLine($data,$h,$align,$newline){
			$this->Cell($data["width"],$h,'',1,0,'',1);
			$this->SetX($this->GetX()-$data["width"]);
		
		if(in_array($data["type"],Array('somme','aggregazione')) && $data["level"]){
			if ($data["type"]=="aggregazione") $txt='';
			$this->Cell(6*$data["level"],$data["height"],$txt,0,0,'R',0);
			$this->Cell($data["width"]-6*$data["level"],$data["height"],utf8_encode(html_entity_decode(($data["value"]))),0,0,$align,0);
		}
		else
			$this->Cell($data["width"],$data["height"],utf8_encode(html_entity_decode(($data["value"]))),0,0,$align,0);
		$this->SetX($this->GetX()-$data["width"]);
		$this->Cell($data["width"],$h,'',0,$newline,'',0);
	}
	private function _writeMultiLine($data,$h,$align,$newline){
		$this->Cell($data["width"],$h,'',1,0,'',1);
		$this->SetX($this->GetX()-$data["width"]);
		
		$this->MultiCell($data["width"],1,utf8_encode(html_entity_decode(($data["value"]))),0,$align,0,$newline);
		
	}
	public function buildReport(){
		print_debug($this->data,null,'REPORT');
		
		for($i=0;$i<count($this->data);$i++){
			$data=$this->data[$i];
			$nl=0;
			for($j=0;$j<count($data)-1;$j++){
				$dato=$data[$j];
				if($j==(count($data)-2)) $nl=1;
				$this->SetTextColor(0);
				switch($dato["type"]){
					case "titolo":
						$align='C';
						$this->SetTextColor(0,0,220);
						$this->SetFont('', "bi", $this->pageFont["size"][$dato["type"]]);
						if($this->pageHeight<($this->getY()+$data["rowheight"])) $this->AddPage();

						break;
					case "aggregazione":
						$align='L';
						$this->SetFont('', "bi", $this->pageFont["size"][$dato["type"]]);
						if($this->pageHeight<($this->getY()+$data["rowheight"])) $this->AddPage();
						break;
					case "somme":
						$align='L';
						$this->SetFont('', "bi", $this->pageFont["size"][$dato["type"]]);
						if($this->pageHeight<($this->getY()+$data["rowheight"])) $this->AddPage();
						break;
					case "intestazione":
						$this->SetFont('', "bi", $this->pageFont["size"][$dato["type"]]);
						$align='C';
						if($this->pageHeight<($this->getY()+$data["rowheight"])) $this->AddPage();
						break;
					case "dati":
						$align='L';
						if($this->pageHeight < ($this->getY() + $data["rowheight"])){
							$this->AddPage();
							$nlint=0;
							$this->SetFillColor($this->color["intestazione"][0],$this->color["intestazione"][1],$this->color["intestazione"][2]);
							for($k=0;$k<count($this->intestazione)-1;$k++){
								$this->SetFont('', "bi", $this->pageFont["size"]["intestazione"]);
								if($k==(count($this->intestazione)-2)) $nlint=1;
								if($this->intestazione[$k]["height"]==$this->rowHeight[$dato["type"]]){
									$this->_writeSingleLine($this->intestazione[$k],$this->intestazione["rowheight"],'C',$nlint);
								}
								else{
									$this->_writeMultiLine($this->intestazione[$k],$this->intestazione["rowheight"],'C',$nlint);
								}
							}
						}
						$this->SetFont('', "bi", $this->pageFont["size"][$dato["type"]]);
						break;
					default:
						break;
				}
				if($this->color[$dato["type"]])
					$this->SetFillColor($this->color[$dato["type"]][0],$this->color[$dato["type"]][1],$this->color[$dato["type"]][2]);
				
				if($dato["height"]==$this->rowHeight[$dato["type"]]){
					$this->_writeSingleLine($dato,$data["rowheight"],$align,$nl);
				}
				else{
					$this->_writeMultiLine($dato,$data["rowheight"],$align,$nl);
				}
				
				if(in_array($dato["type"],Array("titolo")))
					$this->Cell($this->pageWidth,1,'',0,1);
			}
		}
	}
	public function createReport(){
		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="report.pdf"');
		$this->Output();
	}
	public function saveReport($name){
		$this->Output($name);
	}
}
// FINE CLASSE


?>