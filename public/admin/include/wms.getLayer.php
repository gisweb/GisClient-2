<?php
require_once "../../config/config.php";
require_once './lib/ParseXml.class.php';
session_start();
$xml = new ParseXml();
$xml->LoadRemote($pageurl, 3);
$data = $xml->ToArray();

print_debug($data,null,'WMS');
$dataLayer=$data["Capability"]["Layer"]["Layer"];
for($i=0;$i<count($dataLayer);$i++){
	$layer=$dataLayer[$i];
	//$js="setWmsData('')";
	$epsg=(preg_match("|(.+):([0-9]+)|",$layer["SRS"],$out))?($out[2]):(-1);
		
	$obj="{layerName:'$layer[Name]',epsg:'$epsg',}";
	$js="setWmsData($obj);";
	$row[]="<li><a href=\"#\" onclick=\"javascript:$js\">".$layer["Title"]."</a></li>";
}
?>