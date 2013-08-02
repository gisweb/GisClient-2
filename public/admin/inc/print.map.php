<?php
/*DEFINIZIONE DELLE COSTANTI DELLA TABELLA*/
session_start();
$size=strtolower($_REQUEST["pageformat"]);
$orient=($_REQUEST["pagelayout"]=="H")?("landscape"):("portrait");
include ADMIN_PATH."inc/init.pdf.php";
include_once ADMIN_PATH."lib/HTML_ToPDF.php";
include_once("debug.php");
$imageHeight=$_REQUEST["imageHeight"];
$titolo=$_REQUEST["title"];
$leg=$_REQUEST["legend"];
$char_dim="3";
/*FINE DEFINIZIONE*/
$imgPath=$oMap->getImagePath();
$oMap->scale();
$oMap->setLayersStatus();
$oMap->setImageLabel($properties["image_label_layer"],$properties["image_label_text"],$properties["image_label_offset"],$properties["image_label_position"]);
$mapImg = $imgPath.basename($oMap->getMapUrl());
$scaleBarImg = $imgPath.basename($oMap->getScaleBarUrl());
$aLegend = $oMap->getLegend();

//set_param($size,$orient);
$struct=get_structure($aLegend,$char_dim);
$legenda=$struct["new_legenda"];
print_debug($legenda,"",'legenda');
$first_tab=get_first_page_legend($legenda,$char_dim);
$first_page=get_titolo($orient,$char_dim,$first_tab,$mapImg,$scaleBarImg,$titolo);
$legend_tab=get_legend($legenda,$char_dim);
$html="<html>
<head>
<title>MAPPA</title>
	<style>
		@page{
			size : $size;
			margin-left: 1cm;
		     margin-right: 1cm;
		     margin-top: 1cm;
		     margin-bottom: 0.5cm;
		}
	</style>
</head>
<body>
$first_page
$legend_tab
</body></html>";

$pdfFile =$imgPath."printmap.pdf";
$pdf =& new HTML_ToPDF($html,'',$pdfFile);
$pdf->debug=0;

if ($orient=="landscape")
	$landscape="landscape : 1;";

$pdf->addHtml2PsSettings("
	option {
          titlepage: 0;         /* do not generate a title page */
          toc: 0;               /* no table of contents */
          colour: %pageInColor%; /* create the page in color */
          underline: %underlineLinks%;         /* underline links */
          grayscale: %grayScale%; /* Make images grayscale? */
          
          $landscape
        }
        package {
          geturl: %getUrlPath%; /* path to the geturl */
        }
        paper{
        	type: $size;
        }
		page-break: 1;
		break-table: 1;");
$result = $pdf->convert();


if (is_a($result, 'HTML_ToPDFException')) {
    $mex=$result->getMessage();
	//messaggio di errore
	
}
else {
	
	echo "{pdfFile:'/tmp/printmap.pdf'}";
}

?>

