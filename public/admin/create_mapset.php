<?php
	include "../../config/config.php";
	define('MAPSERVER_HOST','');
	$projectName = $_GET["project_name"];
	$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$sql="SELECT DISTINCT mapset_name,template,mapset_title,test_extent FROM ".DB_SCHEMA.".mapset WHERE project_name='$projectName' order by mapset_title;";
	if(!$db->sql_query($sql))
		print_debug($sql,Null,"errori");
	$maps=$db->sql_fetchrowset();
	if (count($maps)>0){
		$rows[]="<tr><th width=\"3%\" style=\"text-align:left;\">&nbsp;</th><th width=\"20%\" style=\"text-align:left;\">Nome</th><th width=\"30%\"style=\"text-align:left;\">Titolo</th><th style=\"text-align:left;\">Messaggi</th><th style=\"text-align:left;\">Link</th></tr>";
		for($i=0;$i<count($maps);$i++){
			$map=$maps[$i];
			if($map["test_extent"]){
				$ext=explode(" ",$map["test_extent"]);
				for($j=0;$j<count($ext);$j++) $ext[$j]=trim($ext[$j]);
				$param="&reset=1&extent=".@implode(",",$ext);
			}
			$_map[]="'".$map["mapset_name"]."'";
			//$cgitest=CGI_PATH."map=".ROOT_PATH."mapset/map/".$projectId."_".$map["name"].".map&mode=map";
			//<td><input type=\"checkbox\" name=\"mappa\" id=\"\" value=\"".$map["id"]."\"></td>
			$rows[]="\n\t<tr>
			<td><a href=\"javascript:updateMapset('".$map["mapset_name"]."')\"><img src=\"../images/reload.gif\" border=\"0\" ></a></td>
			<td>".$map["mapset_name"]."</td>
			<td>".$map["mapset_title"]."</td>
			<td><span id=\"mex_".$map["mapset_name"]."\"></span></td>
			<td><a href=\"javascript:GisClient.OpenMapset('".$map["mapset_name"]."','".$map["template"]."','$param')\"><img src=\"../images/zoom.gif\" border=\"0\">&nbsp;Vai alla Mappa</a></td>
		</tr>";
		}
		$elenco_map=implode(",",$_map);
		$rows[]="<tr><td colspan=\"5\"><hr></td></tr>";
		$rows[]="<tr><td colspan=\"5\">
		<input type=\"button\" class=\"hexfield\" style=\"width:80px;margin-right:5px;\" value=\"Chiudi\" onclick=\"javascript:chiudi();\">
		<input type=\"button\" class=\"hexfield\" style=\"width:160px;margin-right:5px;\" value=\"Genera Tutti Mapset\" onclick=\"javascript:updateAll([$elenco_map]);\">
</td></tr>";
	}
	else
		$rows[]="<tr><th>Non è stata definita nessuna mappa</th></tr>";
		
	$table=implode("\n",$rows);	
 ?>
 
<html>
<head>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
	<title>Online Maps</title>
	<SCRIPT language="javascript" src="../jslib/GisClient.js" type="text/javascript"></SCRIPT>
	<SCRIPT language="javascript" src="./js/Author.js" type="text/javascript"></SCRIPT>
	<SCRIPT language="javascript" src="./js/http_request.js" type="text/javascript"></SCRIPT>

	<script language="javascript">
		
		function chiudi(){
			var w=window.opener;
			w.focus();
			window.close();
		}
		function updateAll(maps){
			for(i=0;i<maps.length;i++){
				updateMapset(maps[i]);
				$('mex_' + maps[i]).set('html','');
			}
			
		}
		function updateMapset(idMap){
			var param='action=writeMapset&mapset=' + idMap;
			var xMapServer = "../xserver/xMapServer.php";
			var txt=$('mex_' + idMap);
			if (txt) txt.innerHTML="Elaborazione in corso";
			$('wait').display='';
			xRequest(xMapServer,param,'set_mapset_message','POST');
		}
		//SISTEMARE Chiamata multipla e messaggio in lingue
		function set_mapset_message(aResult){
			$('wait').display='none';
			//aResult=obj.ares;
			if(aResult[0]){
				//xInnerHtml('mex_' + aResult[0][0], aResult[0][1]);
				$('mex_' + aResult[0][0]).set('html',aResult[0][1]);
			}
			else
				alert('Errore nella generazione della mappa');
		}
	</script>
</head>
<body>
	<table class="stiletabella" cellspacing="5" cellpadding="5" width="100%">
		<?php echo $table;?>
	</table>
	<img src="../images/busy.gif" border="0" style="position:absolute;top:250px;left:250px;display:none;" id="wait">
</body>
</html>