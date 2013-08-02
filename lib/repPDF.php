<?php
//session_start();
//header("content-type:text/html");
//configurazione del sistema
require_once('../../../../config/config.php');
require_once(ROOT_PATH."lib/gcPgQuery.class.php");
require_once(ROOT_PATH."lib/functions.php");
/*

//error_reporting (E_ERROR | E_ALL);
header("Content-Type: text/html; Charset=".CHAR_SET);
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");
*/


$sk=DB_SCHEMA;




/*$_REQUEST["relation"]=2;
$_REQUEST["mode"]="table";
$_REQUEST["resultable"]="true";
$_REQUEST["resultAction"]=2;
*/
$_REQUEST["papersize"]=1;


$db = new sql_db(DB_HOST,DB_USER,DB_PWD,DB_NAME, true);
if(!$db->db_connect_id)  die( "Impossibile connettersi al database    pippo   ".DB_NAME);
$sql="SELECT lower(papersize_size) as papersize_size,papersize_orientation FROM $sk.e_papersize WHERE papersize_id=".$_REQUEST["papersize"];

$db->sql_query($sql);
$size=$db->sql_fetchfield("papersize_size");
$orient=$db->sql_fetchfield("papersize_orientation");
$orient=($orient=="O")?"landscape":"portrait";
	
$oQuery=new PgQuery();
print_array($oQuery->allQueryResults);
//echo "<center>----------------------------------------------------------------------------------------------------------------------------</center>";
$dataQuery=$oQuery->allQueryResults[0];

if($dataQuery["tableheaders"]){

	$h=$dataQuery["tableheaders"];
	$t=$dataQuery["fieldtype"];
	$w=$dataQuery["columnwidth"];
	
	for($i=0;$i<count($h);$i++){
		if(in_array($t[$i],Array(STANDARD_FIELD_TYPE,EMAIL_FIELD_TYPE))) $headers[$h[$i]]=Array("pos"=>$i,"type"=>$t[$i],"width"=>$w[$i]);
		if(in_array($t[$i],Array(''))) $aggHeaders[$h[$i]]=Array("pos"=>$i,"type"=>$t[$i],"width"=>$w[$i]);
	}
}

//error_reporting (E_ERROR | E_ALL);
require_once "testEzpdf.php";exit;




















//require_once "gcReport.class.php";
//$pdf=new gcReport($size,$orient,$_REQUEST["item"]);
$pdf->ezStartPageNumbers(500,28,8,'right','{PAGENUM} di {TOTALPAGENUM}',1);

$footer=$pdf->openObject();
$pdf->saveState();
$pdf->setColor(0,0,0,1);

$pdf->setColor(0,0,0,1);

//$pdf->line(20,70,578,70);
//$pdf->addText(20,10,12,"<b><i>Sistemi Informativi Territoriali</i></b>");
$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($footer,'all');
$data=$pdf->getData($dataQuery,0);
//$pdf->ezSetMargins(30,60,30,30);
//$pdf->ezStartPageNumbers(150,30,15,'left','',1);
//$pdf->ezStartPageNumbers($pdf->point["BR"]["x"],$pdf->point["BR"]["y"]/2,12,'right','{PAGENUM} di {TOTALPAGENUM}',1);


$pdf->footer();
$pdf->buildReport($dataQuery["title"],$data);

exit;
















require_once ROOT_PATH."lib/report.class.php";

//set_error_handler('handleReportError');

$filename=ROOT_PATH.'config/config.pdf.ini';
$tmp=parse_ini_file($filename,true);
$pdfDefaultData=$tmp["default"];
$color=Array(
	"titolo"=>$pdfDefaultData["colorTitolo"],
	"aggregazione"=>$pdfDefaultData["colorAgg"],
	"somme"=>$pdfDefaultData["colorSomma"],
	"intestazione"=>$pdfDefaultData["colorIntest"],
	"dati"=>$pdfDefaultData["colorDati"]
);
$pdfData=$tmp[strtoupper($size)];
$font=Array(
	"family"=>$pdfDefaultData["fontFamily"],
	"size"=>Array(
		"titolo"=>$pdfData["fontSizeTitolo"],
		"aggregazione"=>$pdfData["fontSizeAgg"],
		"somme"=>$pdfData["fontSizeSomma"],
		"intestazione"=>$pdfData["fontSizeIntest"],
		"dati"=>$pdfData["fontSizeDati"]
	)
);
$margin=Array(
	"left"=>$pdfData["marginLeft"],
	"right"=>$pdfData["marginRight"],
	"top"=>$pdfData["marginTop"],
	"bottom"=>$pdfData["marginBottom"]
);

$pdf = new reportPDF($orient, 'pt', $size, true,'UTF-8'); 
$pdf->initReport($orient,$size,$color,$font,$margin);
$pdf->getHeaders($headers);
//$pdf->getData($arr);

$pdf->getData1($dataQuery,0,$dataQuery["tableheaders"]);
if ( $pdf->nDataResult > MAX_REPORT_ROWS) 
	trigger_error("N° di Risultati stampabili : ".MAX_REPORT_ROWS."<br> N° di Risultati : $pdf->nDataResult", E_USER_ERROR);

$pdfName=$_SESSION["USER_NAME"]."_".time().".pdf";

$pdf->buildReport();
$pdf->saveReport(IMAGE_PATH.$pdfName);
echo "<a href=\"/tmp/$pdfName\" target=\"report\">Apri report</a>"
//if ( $pdf->nDataResult <= MAX_REPORT_ROWS){ 
?>
<html>
<head>

</head>
</html>
<?php

?>