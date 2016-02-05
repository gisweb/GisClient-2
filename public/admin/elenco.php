<?php
header("Content-Type: text/html; Charset=ISO-8859-15");
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");
//include_once "login.php";
include_once "../../config/config.php";
require_once (ROOT_PATH."lib/functions.php");
//print_array($_REQUEST);
$filename="";
$frm="";
$filter="";
$button=array();
$frmPrm=array();
$row=Array();
$campo=trim($_REQUEST["campo"]);
$sql=isset($_REQUEST["s"])?$_REQUEST["s"]:"";
$self=$_SERVER["PHP_SELF"];
$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id)  die( "Impossibile connettersi al database");

//Casino temporaneo per gestire due campi con lo stesso nome.
if(isset($_REQUEST["prm_livello"]) && $_REQUEST["prm_livello"]=="style" && $campo=="symbol_name") $campo="symbol_id";

switch($campo) {	
	case "data":		//LAYER (SCELTA DELLA TABELLA - GEOMETRIA - CHIAVE - SRID)
		$idcat=$_REQUEST["catalog_id"];
		$lType=$_REQUEST["layertype_id"];
		// CONNESSIONE GISCLIENT
		$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=$idcat";
		
		if (!$db->sql_query($sql))
			print_debug($sql,null,"elenco");
		$ris=$db->sql_fetchrow();
		
		list($connStr,$schema)=connAdminInfofromPath($ris["catalog_path"]);
		
		if ($lType==-1)
			$warningMex="<p><b>Selezionare un tipo di Layer.</b></p>";
		if($idcat==-1)
			$warningMex="<p><b>Selezionare un catalogo.</b></p>";
			
		switch($ris["connection_type"]){
			case 1:		//Local Folder
				require_once ADMIN_PATH."lib/filesystem.php";
				$fileDir=trim($ris["catalog_path"]);
				if(substr($fileDir,0,1)!='/'){// SOTTO CARTELLA
				//if (!preg_match($fileDir,'|^/(.*)|')){	
					$project=$_REQUEST["project"];
					// CONNESSIONE GISCLIENT
					//Recupero base path del progetto
					$sql="select base_path from ".DB_SCHEMA.".project where project_name='$project'";
					if (!$db->sql_query($sql))
					print_debug($sql,null,"elenco");
					$row = $db->sql_fetchrow();
					$projectPath=$row[0];
					$fileDir=$projectPath.$fileDir;	
				}
				$navDir=($_REQUEST["dir"])?("/".@implode("/",$_REQUEST["dir"])):("");
				$dir=$fileDir.$navDir;
				$ext=explode(",",CATALOG_EXT);
				$fileList=Array();
				$arrNav=$_REQUEST["dir"];
				
				for($i=0;$i<count($arrNav);$i++){
					$d=$arrNav[$i];
					$paramNav="<input type=\"hidden\" name=\"dir[]\" value=\"$d\">\n\t";
				}
				
				$tmp=elenco_dir($dir);
				
				sort($tmp);
				if($arrNav){
					$fileList[]=Array("type"=>"dir","name"=>"..");
					$prm="'".@implode("','",$arrNav)."'";
					$paramUp="['".@implode("','",array_pop($arrNav))."']";
				}
				foreach($tmp as $v){
					$fileList[]=Array("type"=>"dir","name"=>$v);
				}
				foreach($ext as $e){
					$tmp=elenco_file($dir,$e);
					sort($tmp);
					foreach($tmp as $v)
						$fileList[]=Array("type"=>"file","name"=>$v);
				}

				if(!$_REQUEST["dir"]){
					$lgroup=$_REQUEST["layergroup"];
					$sql="SELECT layer_name FROM ".DB_SCHEMA.".layer INNER JOIN ".DB_SCHEMA.".e_layertype USING(layertype_id) WHERE layergroup_id=$lgroup and layertype_ms=7;";
					
					if (!$db->sql_query($sql))
						print_debug($sql,null,"elenco");
					$tileIndex=$db->sql_fetchlist('layer_name');
					for($i=0;$i<count($tileIndex);$i++){
						$fileList[]=Array("type"=>"TileIndex","name"=>$tileIndex[$i]);
					}
				}
				if(!count($fileList))
					$table= "<p><b>Nessun File presente.</b></p>";
				else{
					$row[]="<tr><td><b>Nome</b></td><td>Tipo<b></b></td></tr>";
					foreach($fileList as $val){
						$type=$val["type"];
						$file=$val["name"];
						if ($type=="file"){
							if($navDir) $navDir.="/";
							$obj="[{id:'data', value:'$navDir$file'}]";
							$row[]="<tr><td width=\"50%\"><a href=\"#\" onclick=\"javascript:setdata($obj);\">$file</a></td><td>File</td></tr>";
						}
						elseif($type=="TileIndex"){
							$obj="[{id:'data', value:'$file'}]";
							$row[]="<tr><td width=\"50%\"><a href=\"#\" onclick=\"javascript:setdata($obj);\">$file</a></td><td>TileIndex</td></tr>";
						}
						else{
							$param=($file=="..")?($paramUp):("[".$prm.",'$file']");
							$row[]="<tr><td width=\"50%\"><a href=\"#\" onclick=\"javascript:nav($param);\">$file</a></td><td>Directory</td></tr>";
						}
					}
					$frmPrm=Array("");
				}
				break;
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:
				$db2=pg_connect($connStr);
				
				if(!$db2)  die( "Impossibile connettersi al database");
				
				if (!isset($_REQUEST["step"])){
					// CONNESSIONE OGGETTI
					$sql="SELECT f_table_name as table,f_geometry_column as column,srid FROM geometry_columns WHERE f_table_schema='$schema' order by f_table_name,f_geometry_column";
					$result=pg_query($db2,$sql);
					if($result){
						$ris=pg_fetch_all($result);
						$row[]="<tr><th>Tabella</th><th>Campo Geometria</th></tr>";
						
						for($i=0;$i<count($ris);$i++){
							$row[]="\t<tr><td><a href=\"#\" onclick=\"javascript:set_layerdata('".trim($ris[$i]["table"])."','".trim($ris[$i]["column"])."','".$ris[$i]["srid"]."','',0)\">".$ris[$i]["table"]."</a></td><td>".$ris[$i]["column"]."</td></tr>";
						}
						$frmPrm[]="<input type=\"hidden\" name=\"campo\" value=\"data\">";
						$frmPrm[]="<input type=\"hidden\" name=\"catalog_id\" value=\"$idcat\">";
						$frmPrm[]="<input type=\"hidden\" name=\"table\" id=\"table\" value=\"\">";
						$frmPrm[]="<input type=\"hidden\" name=\"geom\" id=\"geom\" value=\"\">";
						$frmPrm[]="<input type=\"hidden\" name=\"srid\" id=\"srid\" value=\"\">";
						$frmPrm[]="<input type=\"hidden\" name=\"layertype_id\" id=\"layertype_id\" value=\"$lType\">";
						$frmPrm[]="<input type=\"hidden\" name=\"step\" id=\"step\" value=\"2\">";
					}
					else{
						print_debug($sql,null,"elenco");
						$warningMex="<p>Non si dispone dei diritti per accedere alle tabelle di sistema.</p>";
					}
				}
				else{
					$table_name=$_REQUEST["table"];
					$geom_name=$_REQUEST["geom"];
					$srid=$_REQUEST["srid"];
					// CONNESSIONE OGGETTI
					$sql="SELECT column_name FROM information_schema.columns WHERE table_schema='$schema' AND table_name='$table_name' ORDER BY column_name;";
					$result=pg_query($db2,$sql);
					if($result){
						$ris=pg_fetch_all($result);
						
						$row[]="<tr><th>Campo Chiave</th></tr>";
						for($i=0;$i<count($ris);$i++){
							$row[]="\t<tr><td><a href=\"#\" onclick=\"javascript:set_layerdata('".trim($table_name)."','$geom_name','$srid','".$ris[$i]["column_name"]."',1)\">".$ris[$i]["column_name"]."</a></td></tr>\n";
						}
					}
					else{
						print_debug("Non si dispone dei diritti per accedere alle tabelle di sistema.\n".$sql,null,"elenco");
						$warningMex="<p>Non si dispone dei diritti per accedere alle tabelle di sistema.</p>";
					}
				}
				break;
			case 7:		//WMS

				$step=($_REQUEST["step"])?($_REQUEST["step"]):(1);		
				if($step==1){
					require_once ADMIN_PATH.'lib/ParseXml.class.php';
					$xml = new ParseXml();
					// Fare il controllo se è un URL ben formata.
					$param=Array("SERVICE"=>"WMS","REQUEST"=>"GetCapabilities","VERSION"=>"1.1.1");
					$u=parse_url($ris["catalog_path"]);
					parse_str($u["query"],$out);
					if($out) foreach($out as $key=>$val)	$param[strtoupper($key)]=$val;
					foreach($param as $key=>$val) $prm[]="$key=$val";
					$u["query"]=implode("&",$prm);
					$pageurl="";
					if($u["scheme"]) $pageurl.=$u["scheme"]."://";
					if($u["host"]) $pageurl.=$u["host"];
					if($u["port"]) $pageurl.=":$u[port]";
					if($u["path"]) $pageurl.=(strpos("/",$u["path"])>1)?("/".$u["path"]):($u["path"]);
					if($u["query"]) $pageurl.="?$u[query]";
					$xml->LoadRemote($pageurl, 3);
					
					$row=Array();
					if($xml->xmlStr){

						$data = $xml->ToArray();
						if($data){
							$_SESSION["AUTHOR"]["WMS"]=$data;
							$serviceVersion=$data["@attributes"]["version"];
							$serviceName=$data["Service"]["Name"];
							$serviceTitle=$data["Service"]["Title"];
							$serviceAbstract=$data["Service"]["Abstract"];
							$formats=$data["Capability"]["Request"]["GetMap"]["Format"];
							$formatsList=implode($formats,",");
							$format=$formats[0];
							$theme=$data["Capability"]["Layer"];
							$lThemeTitle=$theme["Title"];
							$lThemeSRS=(is_array($theme["SRS"]))?($theme["SRS"]):(Array($theme["SRS"]));
							$epsgList=implode($lThemeSRS," ");
							if($theme["Layer"]["Name"]){
								$tmp=$theme["Layer"];
								$theme["Layer"]=Array();
								$theme["Layer"][0]=$tmp;
							} 
							for($i=0;$i<count($theme["Layer"]);$i++){	
								$lGrp=$theme["Layer"][$i];
								$lGroup[$i]["name"]=$lGrp["Name"];
								$lGroup[$i]["title"]=$lGrp["Title"];
								$lGroup[$i]["abstract"]=$lGrp["Abstract"];
								$epsg=($lGrp["SRS"] && is_array($lGrp["SRS"]))?($lGrp["SRS"][0]):(($lGrp["SRS"])?($lGrp["SRS"]):($lThemeSRS[0]));
								$lGroup[$i]["srs"]=$epsg;
									
								if($lGrp["Style"]){
									if($lGrp["Style"]["Name"] && $lGrp["Style"]["Title"])
										$lGroup[$i]["layer"][0]=Array("name"=>$lGrp["Style"]["Name"],"title"=>$lGrp["Style"]["Title"],"style"=>1);
									else{
										for($j=0;$j<count($lGrp["Style"]);$j++){
											$lGroup[$i]["layer"][$j]=Array("name"=>$lGrp["Style"][$j]["Name"],"title"=>$lGrp["Style"][$j]["Title"],"style"=>1);
										}
									}
								}
								else{
									$lGroup[$i]["layer"][0]=Array("name"=>$lGrp["Name"],"title"=>$lGrp["Title"],"style"=>0);
								}
							}

							$row[]="<th><td><b>Nome</b></td><td><b>Titolo</b></td><td><b>Riassunto</b></td></th>";
							$k=0;
							for($i=0;$i<count($lGroup);$i++){
								$lgr=$lGroup[$i];
								$layerList=$lGroup[$i]["layer"];
								for($j=0;$j<count($layerList);$j++){
									$layer=$layerList[$j];
									$tmp=explode(":",$lgr[srs]);
									if($layer["style"])
										$obj="{campo:'$campo', lname:'layer_name',value:'$lgr[name]', epsg:'$tmp[1]',metadata:'$metaData',wms_srs:'$epsgList',wms_name:'$lgr[name]',wms_server_version:'$serviceVersion',wms_format:'$format', wms_style:'$layer[name]',wms_formatlist:'$formatsList'}";
									else
										$obj="{campo:'$campo', lname:'layer_name',value:'$lgr[name]', epsg:'$tmp[1]',metadata:'$metaData',wms_srs:'$epsgList',wms_name:'$lgr[name]',wms_server_version:'$serviceVersion',wms_format:'$format', wms_formatlist:'$formatsList'}";
									$js1="setWmsData($obj);";
									$_SESSION["AUTHOR"]["JS"][$k]=$js1;
									$js2="nextWms($k);";
									$Arr_lay[]="<li><a href=\"#\" onclick=\"javascript:$js2\">".$layer["title"]."</a></li>";
									$k++;
								}
								$lay=implode("",$Arr_lay);
									$js="$('$lgr[name]').style.display=($('$lgr[name]').style.display=='none')?(''):('none');";
									$row[]="
								<tr>
									<td valign=\"top\"><img src=\"./images/plus.gif\" onclick=\"javascript:$js\" id=\"img_$lgr[name]\"></td>
									<td valign=\"top\">".$lgr["name"]."</td>
									<td valign=\"top\">".$lgr["title"]."</td>
									<td valign=\"top\"></td>
								</tr>
								<tr style=\"display:none;padding-left:5px;\" id=\"$lgr[name]\">
									<td colspan=\"4\">
										<ol>
											$lay
										</ol>
									</td>
								</tr>
								";
								$Arr_lay=Array();
							}
							$frmPrm[]="<input type=\"hidden\" name=\"campo\" value=\"data\">";
							$frmPrm[]="<input type=\"hidden\" name=\"step\" value=\"2\">";
							$frmPrm[]="<input type=\"hidden\" name=\"index\" value=\"\" id=\"index\">";
							$frmPrm[]="<input type=\"hidden\" name=\"layertype_id\" id=\"layertype_id\" value=\"$lType\">";
							$frmPrm[]="<input type=\"hidden\" name=\"catalog_id\" value=\"$idcat\">";
						}
						print_debug($_SESSION["AUTHOR"]["WMS"],null,'WMS');
					}
					else
						$warningMex="<p><b>Servizio non disponibile</b></p>";
				}
				else{
					$layer=$_REQUEST["index"];
					$js=$_SESSION["AUTHOR"]["JS"][$layer];
					$_SESSION["AUTHOR"]["JS"]=null;
					$srs=$_SESSION["AUTHOR"]["WMS"]["Capability"]["Layer"]["SRS"];
					if(!is_array($srs)) $srs=Array($srs);
					$row[]="<th><td><b>SRS</b></td></th>";
					for($i=0;$i<count($srs);$i++){
						$epsg=str_ireplace("epsg:","",str_replace(" ","",$srs[$i]));
						$row[]="<tr><td><a href=\"\" onclick=\"javascript:setSrid('$epsg');$js\">$srs[$i]<a/></td></tr>";
					}
				}
				if(count($row)-1<=0)
					$table=($warningMex)?($warningMex):("<p>Nessun Gruppo di Layer Trovato!</p>");
				else{
					if ($frmPrm) 
						$frm="\n<form id=\"frm_data\" method=\"POST\" action=\"$self\">".implode("\n\t",$frmPrm)."\n</form>";
					$table="<table>\n".@implode("\n",$row)."\n</table>\n$frm";
				}	
				break;
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				require_once ADMIN_PATH.'lib/ParseXml.class.php';

				$xml = new ParseXml();
				// Fare il controllo se è un URL ben formata.
				$param=Array("SERVICE"=>"WFS","REQUEST"=>"GetCapabilities");
				$u=parse_url($ris["catalog_path"]);
				parse_str($u["query"],$out);
				if($out) foreach($out as $key=>$val)	$param[strtoupper($key)]=$val;
				foreach($param as $key=>$val) $prm[]="$key=$val";
				$u["query"]=implode("&",$prm);
				$pageurl="";
				if($u["scheme"]) $pageurl.=$u["scheme"]."://";
				if($u["host"]) $pageurl.=$u["host"];
				if($u["port"]) $pageurl.=":$u[port]";
				if($u["path"]) $pageurl.=(preg_match('(.+)/$||Ui',$u["host"])||preg_match('|^/(.+)|Ui',$u["path"]))?("$u[path]"):("/$u[path]");
				if($u["query"]) $pageurl.="?$u[query]";
				$xml->LoadRemote($pageurl);
				/*if(!$xml->LoadRemote($pageurl)){
					$f=fopen($pageurl,'r');
					while (!feof($f)) $buffer .= fgets($f, 4096);
					$xml->LoadString($buffer);
				}
				*/
				$row=Array();
				if($xml->xmlStr){
					$data = $xml->ToArray();
					$_SESSION["AUTHOR"]["WFS"]=$data;
					$serviceVersion=$data["@attributes"]["version"];
					$serviceName=$data["Service"]["Name"];
					$serviceTitle=$data["Service"]["Title"];
					$serviceAbstract=$data["Service"]["Abstract"];
					$features=$data["FeatureTypeList"];
					$row[]="<th><td><b>Nome</b></td><td><b>Titolo</b></td></th>";
					if($features["FeatureType"]["Name"]){
						$tmp=$features["FeatureType"];
						$features["FeatureType"]=Array();
						$features["FeatureType"][0]=$tmp;
					}
					for($i=0;$i<count($features["FeatureType"]);$i++){
						$feature=$features["FeatureType"][$i];
						$abstract=$feature["Abstract"];
						$epsg=trim(str_replace("EPSG:","",$feature["SRS"]));
						$bbox=implode(" ",$feature["LatLongBoundingBox"]["@attributes"]);
						$obj="{campo:'$campo', lname:'layer_name',value:'$feature[Name]', epsg:'$epsg',wfs_typename:'$feature[Name]',wfs_server_version:'$serviceVersion',wfs_latlongboundingbox:'$bbox'}";
						$js1="setWfsData($obj);";
						$js="$('$feature[Name]').style.display=($('$feature[Name]').style.display=='none')?(''):('none');";
						$row[]="
						<tr>
							<td valign=\"top\"><img src=\"./images/plus.gif\" onclick=\"javascript:$js\" id=\"img_$feature[Name]\"></td>
							<td valign=\"top\"><a href=\"#\" onclick=\"javascript:$js1\">".$feature["Name"]."</a></td>
							<td valign=\"top\">".$feature["Title"]."</td>
						</tr>
						<tr style=\"display:none;padding-left:5px;\" id=\"$feature[Name]\">
							<td></td>
							<td colspan=\"2\">
								$abstract
							</td>
						</tr>
						";
					}
					
				}
				else
					$warningMex="<p><b>Servizio non disponibile</b></p>";
				if(count($row)-1<=0){
					
					$table=($warningMex)?($warningMex):("<p>Nessun Features Trovata!</p>");
					echo $pageurl;
				}
				else{
					if ($frmPrm) 
						$frm="\n<form id=\"frm_data\" method=\"POST\" action=\"$self\">".implode("\n\t",$frmPrm)."\n</form>";
					$table="<table>\n".@implode("\n",$row)."\n</table>\n$frm";
				}	
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!$row)
			$table=($warningMex)?($warningMex):("<p>Elenco vuoto!</p>");
		else{
			
			if ($frmPrm) 
				$frm="\n<form id=\"frm_data\" method=\"POST\" action=\"$self\">".implode("\n\t",$frmPrm)."\n</form>";
			$table="<form method=\"post\" name=\"navigate\" id=\"frmNavigate\"><table>\n".@implode("\n",$row)."\n</table>\n<input type=\"hidden\" name=\"filtro\" value=\"$filename\">\n</form>\n$frm";
		}
		break;
	//CAMPI DI BINDING
	case "class_text":
	case "label_angle":
	case "label_color":
	case "label_outlinecolor":
	case "label_size":
	case "label_font":
	case "label_priority":
	case "angle":
	case "color":
	case "outlinecolor":
	case "size":
	case "symbol_name":
	case "pattern_name":
	case "labelitem":	
	case "labelsizeitem":	
	case "classitem":
		$idlayer=isset($_REQUEST["layer"])?$_REQUEST["layer"]:0;
		if(isset($_REQUEST["layer"]))
			// CONNESSIONE GISCLIENT
			$sql="select catalog_path,connection_type from ".DB_SCHEMA.".layer left join ".DB_SCHEMA.".catalog USING (catalog_id) where layer_id=".$_REQUEST["layer"];
		else{
			$idcatalog=$_REQUEST["catalog_id"];
			$idlayertype=$_REQUEST["layertype_id"];
			$data=$_REQUEST["data"];
			if(!$idcatalog || ($idcatalog <= 0)) 
				$warningMex="<p><b>Selezionare un catalogo.</b></p>";
			if(!$data)
				$warningMex= "<p><b>Selezionare un campo data.</b></p>";
			// CONNESSIONE GISCLIENT
			$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog  where catalog_id=$idcatalog;";
		}
		
		$db->sql_query($sql);
		$ris=$db->sql_fetchrow();
		
		list($connStr,$schema)=connAdminInfofromPath($ris["catalog_path"]);
		
		switch($ris["connection_type"]){
			case 1:		//Local Folder
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:
				//SELEZIONO IL CAMPO DATA DALLA TABELLA LAYER E VADO ALLA RICERCA DEI CAMPI SU CUI POSSO FARE IL JOIN
				if ($idlayer>0){
					// CONNESSIONE GISCLIENT
					$sql="SELECT data FROM ".DB_SCHEMA.".layer WHERE layer_id=$idlayer order by data;";
					$db->sql_query($sql);
					print_debug($sql,null,"elenco");
					$row = $db->sql_fetchrow();
					$data=$row[0];
				}
				// VERIFICO SE E' UNA TABELLA OPPURE UNA RELAZIONE
				if(preg_match("|select (.+) from (.+)|i",$data,$tmp))
					$sql=trim($data);
				elseif(preg_match("|([\w]*)[.]{0,1}([\w]+)$|i",trim($data)))
					// CONNESSIONE GISCLIENT
					$sql="SELECT * FROM $schema.".trim($data);
				else
					echo "<p>Data Layer mal definito</p>";
				$sql.=" LIMIT 0;";
				print_debug($sql,null,"elenco");
				$ris=Array();
				
				$db2=pg_connect($connStr);
				if(!$db2)  die( "Impossibile connettersi al database");
				$result=pg_query($db2,$sql);
				if($result){
					$n_fields=pg_num_fields($result);
					for($i=0;$i<$n_fields;$i++){
						$ris[]=pg_field_name($result,$i);
					}
				}
				print_debug($sql,null,"elenco");
				sort($ris);
				for($i=0;$i<count($ris);$i++){
					$value=(in_array($campo,Array("classitem","labelitem","labelsizeitem")))?($ris[$i]):("\[".$ris[$i]."\]");
					$obj="[{id:'$campo', value:'$value'}]";
					$row[]="<li><a href=\"#\" onclick=\"javascript:setdata($obj)\">".$ris[$i]."</a></li>";
				}
				break;
			case 7:		//WMS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!count($row))
			$table=($warningMex)?($warningMex):("<p>Nessuna tabella trovata!</p>");
		else
			$table="<ul>\n".@implode("\n",$row)."\n</ul>";
		break;
	case "qtfield_name":		//QT FIELD (CASINO)
		$idqt=$_REQUEST["qt"];
		$idqtrelation=$_REQUEST["qtrelation_id"];
		// CONNESSIONE GISCLIENT
		if($idqtrelation){		//RECUPERO I DATI DA UNA NUOVA RELAZIONE
			//$sql="select conntype_id,hostname,dbname,path,dbport,connection.username,connection.pwd from ".DB_SCHEMA.".qtrelation left join (".DB_SCHEMA.".catalog left join ".DB_SCHEMA.".connection using(connection_name)) USING (catalog_id) where qtrelation_id=$idqtrelation;";
			$sql="select catalog_path,connection_type from ".DB_SCHEMA.".qtrelation left join ".DB_SCHEMA.".catalog  USING (catalog_id) where qtrelation_id=$idqtrelation;";
		}
		else{					//RECUPERO I DATI DAL DATA LAYER
			$sql="select catalog_path,connection_type from ".DB_SCHEMA.".qt left join ".DB_SCHEMA.".layer USING (layer_id) left join ".DB_SCHEMA.".catalog USING (catalog_id) where qt_id=$idqt;";
		}
		print_debug($sql,null,"elenco");
		$db->sql_query($sql);
		$ris=$db->sql_fetchrow();
		
		list($connStr,$schema)=connInfofromPath($ris["catalog_path"]);
		
		
		// RECUPERO LE TABELLE DEFINITE NEI QT RELATION
		// CONNESSIONE GISCLIENT
		$sql="SELECT table_name FROM ".DB_SCHEMA.".qtrelation WHERE qtrelation_id=$idqtrelation order by table_name;";
		print_debug($sql,null,"elenco");
		$db->sql_query($sql);
		$row = $db->sql_fetchrow();
		$tb_name=$row[0];

		switch($ris["connection_type"]){
			case 1:		//Local Folder
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:		//POSTGIS
				//CONNESSIONE AL NUOVO CATALOGO

				$db2=pg_connect($connStr);
				if(!$db2)  die( "Impossibile connettersi al database");
				//SELEZIONO IL CAMPO DATA DALLA TABELLA LAYER E VADO ALLA RICERCA DEI CAMPI SU CUI POSSO FARE IL JOIN
					
				switch($idqtrelation){
					case -1:
						$warningMex="<p>Nessuna Relazione Definita</p>";
						break;
					case 0:		// CASO DEL DATA LAYER
						// CONNESSIONE GISCLIENT
						$sql="SELECT data FROM ".DB_SCHEMA.".layer LEFT JOIN ".DB_SCHEMA.".qt USING (layer_id) WHERE qt_id=$idqt order by data;";
						print_debug($sql,null,"elenco");
						$db->sql_query($sql);
						$row = $db->sql_fetchrow();
						$data=$db->row[0];
						// VERIFICO SE E' UNA TABELLA OPPURE UNA RELAZIONE
						if(preg_match("|select (.+) from (.+)|i",$data,$tmp))
							$sql=trim($data);
						elseif(preg_match("|([\w]*)[.]{0,1}([\w]+)$|i",trim($data)))
							// CONNESSIONE GISCLIENT
							$sql="SELECT * FROM $schema.".trim($data);
						else
							echo "<p>Data Layer mal definito</p>";
							
						

						$sql.=" LIMIT 0;";
						print_debug($sql,null,"elenco");
						$ris=Array();
						$result=pg_query($db2,$sql);
						if($result){
							$n_fields=pg_num_fields($result);
							for($i=0;$i<$n_fields;$i++){
								$tmp[]=pg_field_name($result,$i);
							}
							sort($tmp);
							for($i=0;$i<$n_fields;$i++){
								$ris[$i]["name"]=$tmp[$i];
							}
						}
						print_debug($sql,null,"elenco");
						break;
					default:	// RELAZIONE ESTERNA
						// CONNESSIONE OGGETTI
						$sql="SELECT column_name as name FROM information_schema.columns WHERE table_schema='$schema' AND table_name='$tb_name' order by column_name";
						print_debug($sql,null,"elenco");
						$result=pg_query($db2,$sql);
						$ris=pg_fetch_all($result);
						break;
				}
				
				
				for($i=0;$i<count($ris);$i++){
					$obj="[{id:'qtfield_name', value:'".$ris[$i]["name"]."'}]";
					$row[]="<li><a href=\"#\" onclick=\"javascript:setdata($obj)\">".$ris[$i]["name"]."</a></li>";
				}
				break;
			case 7:		//WMS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!count($ris))
			$table=($warningMex)?($warningMex):("<p>Nessun Campo trovato!</p>");
		else
			$table="<ul>\n".@implode("\n",$row)."\n</ul>";
		break;
	case "data_field_1":		// QT RELATION
	case "data_field_2":
	case "data_field_3":
		$idqt=$_REQUEST["qt"];
		// CONNESSIONE GISCLIENT
		$sql="select catalog_path,connection_type from ".DB_SCHEMA.".qt left join ".DB_SCHEMA.".layer USING (layer_id) left join ".DB_SCHEMA.".catalog USING (catalog_id) where qt_id=$idqt;";
		print_debug($sql);
		$db->sql_query($sql);
		$ris=$db->sql_fetchrow();
		list($connStr,$schema)=connInfofromPath($ris["catalog_path"]);
		switch($ris["connection_type"]){
			case 1:		//Local Folder
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:		//POSTGIS
				//CONNESSIONE AL NUOVO CATALOGO

				$db2=pg_connect($connStr);
				if(!$db2)  die( "Impossibile connettersi al database");
				//SELEZIONO IL CAMPO DATA DALLA TABELLA LAYER E VADO ALLA RICERCA DEI CAMPI SU CUI POSSO FARE IL JOIN
				// CONNESSIONE GISCLIENT
				$sql="SELECT data FROM ".DB_SCHEMA.".layer LEFT JOIN ".DB_SCHEMA.".qt USING (layer_id) WHERE qt_id=$idqt order by data;";
				print_debug($sql,null,"elenco");
				$db->sql_query($sql);
				$row = $db->sql_fetchrow();
				$data=$row[0];
				// VERIFICO SE E' UNA TABELLA OPPURE UNA RELAZIONE
				if(preg_match("|select (.+) from (.+)|i",$data,$tmp))
					$sql=trim($data);
				elseif(preg_match("|([\w]*)[.]{0,1}([\w]+)$|i",trim($data)))
					// CONNESSIONE GISCLIENT
					$sql="SELECT * FROM $schema.".trim($data);
				else
					echo "<p>Data Layer mal definito</p>";
				$sql.=" LIMIT 0;";
				print_debug($sql,null,"elenco");
				$ris=Array();
				$result=pg_query($db2,$sql);
				if($result){
					$n_fields=pg_num_fields($result);
					for($i=0;$i<$n_fields;$i++){
						$ris[]=pg_field_name($result,$i);
					}
				}
				print_debug($sql,null,"elenco");
				
				for($i=0;$i<count($ris);$i++){
					$obj="[{id:'$campo', value:'".$ris[$i]."'}]";
					$row[]="<li><a href=\"#\" onclick=\"javascript:setdata($obj)\">".$ris[$i]."</a></li>";
				}
				break;
			case 7:		//WMS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!count($ris))
			$table=($warningMex)?($warningMex):("<p>Nessun Campo trovato!</p>");
		else
			$table="<ul>\n".@implode("\n",$row)."\n</ul>";
		break;
	case "table_name":
		$idcat=$_REQUEST["catalog_id"];
		if($idcat==-1)
			echo "<p>Selezionare un catalogo.</p>";
		// CONNESSIONE GISCLIENT
		$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=$idcat";
		if (!$db->sql_query($sql))
			print_debug($sql,null,"elenco");
		$ris=$db->sql_fetchrow();
		
		list($connStr,$schema)=connInfofromPath($ris["catalog_path"]);
		switch($ris["connection_type"]){
			case 1:		//Local Folder
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:		//POSTGIS
				//CONNESSIONE AL NUOVO CATALOGO

				$db2=pg_connect($connStr);
				if(!$db2)  die( "Impossibile connettersi al database");
				//SELEZIONO IL CAMPO DATA DALLA TABELLA LAYER E VADO ALLA RICERCA DEI CAMPI SU CUI POSSO FARE IL JOIN
				// CONNESSIONE OGGETTI
				$sql="SELECT table_name FROM information_schema.tables WHERE table_schema='$schema' order by table_name";
				$result=pg_query($db2,$sql);
				if($result){
					$ris=pg_fetch_all($result);
					for($i=0;$i<count($ris);$i++){
						$obj="[{id:'$campo' ,value:'".$ris[$i]["table_name"]."'}]";
						$row[]="<li><a href=\"#\" onclick=\"javascript:setdata($obj)\">".$ris[$i]["table_name"]."</a></li>";
					}
				}	
				else{
					print_debug($sql,null,"elenco");
					$warningMex="<p>Non si dispone dei diritti per accedere al catalogo.</p>";
				}

				break;
			case 7:		//WMS
				
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!count($row))
			$table=($warningMex)?($warningMex):("<p>Nessuna tabella trovata!</p>");
		else
			$table="<ul>\n".@implode("\n",$row)."\n</ul>";
		break;
	case "table_field_1":
	case "table_field_2":
	case "table_field_3":
		$idcat=$_REQUEST["catalog_id"];
		$table_name=$_REQUEST["table_name"];
		// CONNESSIONE GISCLIENT
		$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=$idcat";
		if (!$db->sql_query($sql))
			print_debug($sql,null,"elenco");
		$ris=$db->sql_fetchrow();
		
		list($connStr,$schema)=connInfofromPath($ris["catalog_path"]);
		
		switch($ris["connection_type"]){
			case 1:		//Local Folder
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:
				//CONNESSIONE AL NUOVO CATALOGO
				$db2=pg_connect($connStr);
				if(!$db2)  die( "Impossibile connettersi al database");
				// CONNESSIONE OGGETTI
				$sql="SELECT column_name FROM information_schema.columns WHERE table_schema='$schema' AND table_name='$table_name' order by column_name";
				
				$result=pg_query($db2,$sql);
				if($result){
					$ris=pg_fetch_all($result);
					for($i=0;$i<count($ris);$i++){
						$obj="[{id:'$campo' ,value:'".$ris[$i]["column_name"]."'}]";
						$row[]="<li><a href=\"#\" onclick=\"javascript:setdata($obj)\">".$ris[$i]["column_name"]."</a></li>";
					}
				}
				else{
					print_debug($sql,null,"elenco");
					$warningMex="<p>Non si dispone dei diritti per accedere al catalogo.</p>";
				}
				if (!count($row))
					$table="<p><b>Nessuna Relazione Trovata!</b></p>";
				break;
			case 7:		//WMS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!count($row))
			$table=($warningMex)?($warningMex):("<p>Nessuna tabella trovata!</p>");
		else
			$table="<ul>\n".@implode("\n",$row)."\n</ul>";
		break;

	case "wms":
		$filter=$_REQUEST["s"];
		$idcat=$_REQUEST["catalog_id"];
		$lType=$_REQUEST["layertype_id"];
		// CONNESSIONE GISCLIENT
		$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=$idcat";
		if (!$db->sql_query($sql))
			print_debug($sql,null,"elenco");
		$ris=$db->sql_fetchrow();
		
		require_once ADMIN_PATH.'lib/ParseXml.class.php';
		$xml = new ParseXml();
		// Fare il controllo se è un URL ben formata.
		$pageurl=$ris["catalog_path"]."?SERVICE=WMS&REQUEST=GetCapabilities";
		$xml->LoadRemote($pageurl, 3);
		$data = $xml->ToArray();
		$_SESSION["AUTHOR"]["WMS"]=$data;
		$serviceVersion=$data["@attributes"]["version"];
		$serviceName=$data["Service"]["Name"];
		$serviceTitle=$data["Service"]["Title"];
		$serviceAbstract=$data["Service"]["Abstract"];
		$formats=$data["Capability"]["Request"]["GetMap"]["Format"];
		$theme=$data["Capability"]["Layer"];
		$lThemeTitle=$theme["Title"];
		$lThemeSRS=(is_array($theme["SRS"]))?($theme["SRS"]):(Array($theme["SRS"]));
		for($i=0;$i<count($theme["Layer"]);$i++){
			$lGrp=$theme["Layer"][$i];
			$lGroup[$i]["name"]=$lGrp["Name"];
			$lGroup[$i]["title"]=$lGrp["Title"];
			$lGroup[$i]["abstract"]=$lGrp["Abstract"];
			$epsg=($lGrp["SRS"] && is_array($lGrp["SRS"]))?($lGrp["SRS"][0]):(($lGrp["SRS"])?($lGrp["SRS"]):($lThemeSRS[0]));
			$lGroup[$i]["srs"]=$epsg;
			
			if($lGrp["Style"]){
				if($lGrp["Style"]["Name"] && $lGrp["Style"]["Title"])
					$lGroup[$i]["layer"][0]=Array("name"=>$lGrp["Style"]["Name"],"title"=>$lGrp["Style"]["Title"],"style"=>1);
				else{
					for($j=0;$j<count($lGrp["Style"]);$j++){
						$lGroup[$i]["layer"][$j]=Array("name"=>$lGrp["Style"][$j]["Name"],"title"=>$lGrp["Style"][$j]["Title"],"style"=>1);
					}
				}
			}
			else{
				$lGroup[$i]["layer"][0]=Array("name"=>$lGrp["Name"],"title"=>$lGrp["Title"],"style"=>0);
			}
			
		}

		$row[]="<th><td><b>Nome</b></td><td><b>Titolo</b></td><td><b>Riassunto</b></td></th>";
		for($i=0;$i<count($lGroup);$i++){
			$lgr=$lGroup[$i];
			$layerList=$lGroup[$i]["layer"];
			for($j=0;$j<count($layerList);$j++){
				$layer=$layerList[$j];
				$Arr_lay[]="<li>".$layer["title"]."</li>";
			}
			$lay=implode("",$Arr_lay);
				$obj="{campo:'$campo', lname:'layergroup_name',value:'$lgr[name]', title:'$lgr[title]',epsg:'$lgr[srs]'}";
				$js1="setWmsLayerGroup($obj);";
				$js="$('$lgr[name]').style.display=($('$lgr[name]').style.display=='none')?(''):('none');";
				$row[]="
			<tr>
				<td valign=\"top\"><img src=\"./images/plus.gif\" onclick=\"javascript:$js\"></td>
				<td valign=\"top\"><a href=\"#\" onclick=\"javascript:$js1\">".$lgr["name"]."</a></td>
				<td valign=\"top\"><a href=\"#\" onclick=\"javascript:$js1\">".$lgr["title"]."</a></td>
				<td valign=\"top\"></td>
			</tr>
			<tr style=\"display:none;padding-left:5px;\" id=\"$lgr[name]\">
				<td colspan=\"4\">
					<ol>
						$lay
					</ol>
				</td>
			</tr>
			";
			$Arr_lay=Array();
			//$js="setWmsData('')";
		}
		if(count($row)-1==0)
			$table=($warningMex)?($warningMex):("<p>NessunGruppo di Layer Trovato!</p>");
		else{
			if ($frmPrm) 
				$frm="\n<form id=\"frm_data\" method=\"POST\" action=\"$self\">".implode("\n\t",$frmPrm)."\n</form>";
			$table="<table>\n".@implode("\n",$row)."\n</table>\n$frm";
		}	
		break;
		
	//ELENCO DEI SIMBOLI
	case "class_symbol_ttf":	
	case "symbol_ttf_name":
		if ($_REQUEST["label_font"]) $filter="font_name='".$_REQUEST["label_font"]."'";
	case "symbol_id":
		$dbtable=($campo=="symbol_ttf_name"||$campo=="class_symbol_ttf")?("symbol_ttf"):("symbol");
		$campo=($campo=="symbol_id")?("symbol_name"):($campo);
		
		if($campo=="symbol_ttf_name" && !$_REQUEST["label_font"]){
			$row[]="<tr><td colspan=\"3\"><b>Selezionare un font</b></td></tr>";
		}
		else{
			
			if (($_REQUEST["s"]!='Non definito') && ($campo!="symbol_ttf_name"))$filter="symbol_name ilike '%".$_REQUEST["s"]."%'";
			include_once ROOT_PATH."lib/gcSymbol.class.php";
			$smb=new Symbol($dbtable,$filter);
			$smb->table=$dbtable;
			$smb->filter=$filter;
			$smbList=$smb->getList();
			
			if($dbtable=='class'){
			
			}
			elseif($dbtable=='symbol'){
				$js="setdata";
				$row[]="<tr><th>".implode("</th><th>",$smbList["headers"])."</th></tr>";
				for($i=0;$i<count($smbList["values"]);$i++){
					$cols="<td><img src=\"getImage.php?".$smbList["values"][$i][0]."\" /></td>";
					$obj="[{id:'symbol_name', value:'".$smbList["values"][$i][1]."'}]";
					$cols.="<td><a href=\"#\" onclick=\"javascript:$js($obj)\">".$smbList["values"][$i][1]."</a></td>";
					for($j=2;$j<count($smbList["values"][$i]);$j++)
						$cols.="<td>".$smbList["values"][$i][$j]."</td>";
					$row[]="<tr>$cols</tr>";
				}	
			}
			elseif($dbtable=='symbol_ttf'){
				$js="setElencoFKey";
				$row[]="<tr><th>".implode("</th><th>",$smbList["headers"])."</th></tr>";
				for($i=0;$i<count($smbList["values"]);$i++){
					$cols="<td><img src=\"getImage.php?".$smbList["values"][$i][0]."\" /></td>";
					$obj="{campo:'$campo', fk_campo:'fk_$campo', pkey:'".$smbList["values"][$i][1]."',fkey:'".$smbList["values"][$i][1]."',action:'setControls([{value:\'".$smbList["values"][$i][4]."\',id:\'label_position\'}])'}";
					$cols.="<td><a href=\"#\" onclick=\"javascript:$js($obj)\">".$smbList["values"][$i][1]."</a></td>";
					for($j=2;$j<count($smbList["values"][$i]);$j++)
						$cols.="<td>".$smbList["values"][$i][$j]."</td>";
					$row[]="<tr>$cols</tr>";
				}	
			}
			elseif($dbtable=='symbol_ttftt'){
				$js="setElencoFKey";
				$row[]="<tr><td>&nbsp;</td><td><b>Font</b></td><td><b>Nome</b></td></tr>";
				for($i=0;$i<count($ris["values"]);$i++){
					$el=$ris["values"][$i];
					$obj="{campo:'$campo', fk_campo:'fk_$campo',pkey:'".$el[0]."',fkey:'".$el[0]."',action:'setControls([{value:\'$el[3]\',id:\'label_position\'}])'}";
					$img="<img src=\"getImage.php?table=$dbtable&id=$el[0]&font=$el[1]\">";
					$row[]="<tr><td>$img</td><td>$el[1]</td><td><a href=\"#\" onclick=\"javascript:$js($obj)\">$el[0]</a></td></tr>";
				}
			}
			
			
			
			if(count($row)==1) $row[]="<tr><td colspan=\"3\">Nessun simbolo trovato</td></tr>";
		}
		$table="<table border=\"1\" cellpadding=\"2\" style=\"border-collapse:collapse;\">\n".@implode("\n",$row)."\n</table>\n$frm";
		break;
		
	case "layer_id":
		$step=isset($_REQUEST["step"])?$_REQUEST["step"]:false;
		$lgroup=isset($_REQUEST["layergroup"])?$_REQUEST["layergroup"]:0;
		$theme=isset($_REQUEST["theme"])?$_REQUEST["theme"]:0;
		if (!$step){
			// CONNESSIONE GISCLIENT
			$sql="SELECT layergroup_id,layergroup_name FROM ".DB_SCHEMA.".layergroup WHERE theme_id=$theme order by layergroup_name;";
			if($db->sql_query($sql)){
				$ris=$db->sql_fetchrowset();
				foreach($ris as $lgr){
					$td[]="<tr><td><a href=\"elenco.php?campo=layer_id&step=1&layergroup=".$lgr["layergroup_id"]."\">".$lgr["layergroup_name"]."</a></td></tr>";
				}
				$js="setElencoFKey({campo:'$campo', fk_campo:'fk_$campo',pkey:null,fkey:null, action:null});";
				$nullobj="<a href=\"#\" onclick=\"javascript:$js\">Nessuno</a>";
				$table=(count($td))?("<table><tr><td><b>Elenco dei Gruppi di Layer</b></td></tr><tr><td>$nullobj</td></tr>".@implode("",$td)."</table>"):("<table><tr><td><b>In questo tema non è presente nessun Gruppo di Layer.</b></td></tr></table>");
			}
			else
				print_debug($sql,null,"elenco");
		}
		else{
			// CONNESSIONE GISCLIENT
			$sql="SELECT layer_id,layer_name FROM ".DB_SCHEMA.".layer WHERE layergroup_id=$lgroup order by layer_name;";
			if($db->sql_query($sql)){
				$ris=$db->sql_fetchrowset();
				foreach($ris as $lay){
					//{pkey:".$row["id"].",fkey:'".$row["name"]."',action:'setControls([{value:\'".$row["font"]."\',id:\'label_font\'},{value:\'".$row["pos"]."\',id:\'label_position\'}])'}
					$js="setElencoFKey({campo:'$campo', fk_campo:'fk_$campo',pkey:".$lay["layer_id"].",fkey:'".$lay["layer_name"]."', action:null});";
					
					$td[]="<tr><td><a href=\"#\" onclick=\"javascript:$js\">".$lay["layer_name"]."</a></td></tr>";
				}
				$table=(count($td))?("<table><tr><td><b>Elenco dei Data Layer</b></td></tr>".@implode("",$td)."</table>"):("<table><tr><td><b>In questo gruppo di Layer non è presente nessun Data Layer.</b></td></tr></table>");
			}
			else
				print_debug($sql,null,"elenco");
		}
		break;
	case "filename":
		$project=$_REQUEST["imp_project"];
		$pr=($_REQUEST["project"])?($_REQUEST["project"]):("");
		$level=$_REQUEST["livello"];
		if ($project==-1)
			echo "<p><b>Selezionare un progetto dal quale importare i dati.</b></p>";
		else{
			include_once ADMIN_PATH."lib/filesystem.php";
			$dir=ADMIN_PATH."export/";
			$fileList=elenco_file($dir,"");
			if (count($fileList)>0){
				//print_array($fileList);
				foreach($fileList as $f){
					$rows=file($dir.$f);
					$js="setdata([{id:'filename', value:'$f'}]);";
					switch($project){
						case 0:
							if (preg_replace("|([\s])+|","",$rows[1])=="--Type:$level")
								$file[]="<tr><td><a href=\"#\" onclick=\"javascript:$js\">$f</a></td></tr>";
							break;
						default:
							if ((preg_replace("|([\s])+|","",$rows[1])=="--Type:$level") && (preg_replace("|([\s])+|","",$rows[0])=="--Project:$pr"))
								$file[]="<tr><td><a href=\"#\" onclick=\"javascript:$js\">$f</a></td></tr>";
							break;
					}
				}
				if (count($file)>0)
					$table="<table width=\"90%\">".@implode("\n",$file)."</table>";
				else
					echo "<p><b>Nessun File da Importare</b></p>";
			}
			else
				echo "<p><b>Nessun File da Importare</b></p>";
		}
		break;

	case "classtitle":
		$idcatalog=$_REQUEST["catalog_id"];
		$idlayertype=$_REQUEST["layertype_id"];
		$data=$_REQUEST["data"];
		if(!$idcatalog || ($idcatalog <= 0)) echo "<p><b>Selezionare un catalogo.</b></p>";
		if(!$data) echo "<p><b>Selezionare un campo data.</b></p>";

		$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog  where catalog_id=$idcatalog;";
		$db->sql_query($sql);
		$ris=$db->sql_fetchrow();
		list($connStr,$schema)=connInfofromPath($ris["path"]);
		
		switch($ris["connection_type"]){
			case 1:		//Local Folder
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 3:		//SDE
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 4:		//
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 6:
				//SELEZIONO IL CAMPO DATA DALLA TABELLA LAYER E VADO ALLA RICERCA DEI CAMPI SU CUI POSSO FARE IL JOIN
				if ($idlayer>0){
					// CONNESSIONE GISCLIENT
					$sql="SELECT data FROM ".DB_SCHEMA.".layer WHERE layer_id=$idlayer order by data;";
					$db->sql_query($sql);
					print_debug($sql,null,"elenco");
					$row = $db->sql_fetchrow();
					$data=$row[0];
				}
			
			
				
				
				// VERIFICO SE E' UNA TABELLA OPPURE UNA RELAZIONE
				if(preg_match("|select (.+) from (.+)|i",$data,$tmp))
					$sql=trim($data);
				elseif(preg_match("|([\w]*)[.]{0,1}([\w]+)$|i",trim($data)))
					// CONNESSIONE GISCLIENT
					$sql="SELECT * FROM $schema.".trim($data);
				else
					echo "<p>Data Layer mal definito</p>";
				$sql.=" LIMIT 0;";
				print_debug($sql,null,"elenco");
				$ris=Array();
				
				$db2=pg_connect($connStr);
				if(!$db2)  die( "Impossibile connettersi al database");
				$result=pg_query($db2,$sql);
				if($result){
					$n_fields=pg_num_fields($result);
					for($i=0;$i<$n_fields;$i++){
						$ris[]=pg_field_name($result,$i);
					}
				}
				print_debug($sql,null,"elenco");
				sort($ris);
				for($i=0;$i<count($ris);$i++){
					$value=(in_array($campo,Array("classitem","labelitem")))?($ris[$i]):("\[".$ris[$i]."\]");
					$obj="[{id:'$campo', value:'$value'}]";
					$row[]="<li><a href=\"#\" onclick=\"javascript:setdata($obj)\">".$ris[$i]."</a></li>";
				}
				break;
			case 7:		//WMS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 8:		//OGR
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			case 9:		//WFS
				$warningMex="<p>Funzionalità non ancora implementata</p>";
				break;
			default:
				$warningMex="<p>Errore</p>";
				break;
		}
		if(!count($row))
			$table=($warningMex)?($warningMex):("<p>Nessuna tabella trovata!</p>");
		else
			$table="<ul>\n".@implode("\n",$row)."\n</ul>";
		break;
	case "layer_order":		//Ordinamento dei layer del Progetto. E' un ordinamento assoluto
		$project_id=$_REQUEST["project"];
		$theme_id=$_REQUEST["theme"];
		$layergroup_id=$_REQUEST["layergroup"];
		$layer_id=$_REQUEST["layer"];
		//if($layer_id) $filter=" AND layer_id<>$layer_id ";
		// CONNESSIONE GISCLIENT
		$sql="SELECT layer_id,coalesce(layer_order,0) as layer_order,layer_name FROM ".DB_SCHEMA.".theme INNER JOIN ".DB_SCHEMA.".layergroup USING(theme_id) INNER JOIN ".DB_SCHEMA.".layer USING (layergroup_id) WHERE project_name='$project_id' $filter ORDER BY layer_order;";
		if(!$db->sql_query($sql)){
			$ris=Array();
			print_debug($sql,null,"elenco");
		}
		$js="setdata";
		$ris=$db->sql_fetchrowset();
		$link="<a href=\"#\" onclick=\"javascript:$js([{id:'layer_order',value:0},{id:'locked_layer_order',value:0}])\">Nessun Valore</a>";
		if (count($ris)){
			$last=$ris[count($ris)-1]["layer_order"]+1;
			$linkStart="<a href=\"#\" onclick=\"javascript:$js([{id:'layer_order',value:1}])\">Sfondo</a>";
			$linkEnd="<a href=\"#\" onclick=\"javascript:$js([{id:'layer_order',value:$last}])\">Primo Piano</a>";
			$row[]="\t\t<tr>
			<td width=\"70%\"><b>LAYER</b></td>
			<td width=\"30%\"><b>ORDINE</b></td>
		</tr>
		<tr>
			<td colspan=\"2\">$linkStart</td>
		</tr>
		<tr>
			<td colspan=\"2\">$linkEnd</td>
		</tr>
		<tr>
			<td colspan=\"2\">$link</td>
		</tr>";

			for($i=0;$i<count($ris);$i++){
				$val=$ris[$i];
				$val["layer_order"]=($val["layer_order"])?($val["layer_order"]):("Non Definito");
				if($layer_id!=$val["layer_id"])
					$link="<a href=\"#\" onclick=\"javascript:$js([{id:'layer_order',value:".($val["layer_order"]+1)."}])\">$val[layer_name]</a>";
				else
					$link="$val[layer_name]";
				$row[]="\t\t<tr>
			<td>$link</td>
			<td>$val[layer_order]</td>
		</tr>";
			}
			$table="<table width=\"99%\" border=\"1\" cellpadding=\"2\" cellspacing=\"0\">\n".@implode("\n",$row)."\n</table>";
		}
		else
			$table="<p><b>Nessun Layer definito.</b></p>";
		
		break;
	case "field_format":
		
			// CONNESSIONE GISCLIENT
			$sql="select fieldformat_name as description,fieldformat_format from ".DB_SCHEMA.".e_fieldformat order by fieldformat_order;";
			if(!$db->sql_query($sql)){
				$ris=Array();
				print_debug($sql,null,"elenco");
			}
			else{
				$js="setdata";
				$ris=$db->sql_fetchrowset();
				if (count($ris)){
					$row[]="\t\t<tr>
					<td><b>Descrizione</b></td>
					<td><b>Formato</b></td>
				</tr>";
					for($i=0;$i<count($ris);$i++){
						$val=$ris[$i];
						$obj="[{id:'$campo', value:'".addslashes($val["fieldformat_format"])."'}]";
						$link="<a href=\"#\" onclick=\"javascript:$js($obj)\">$val[description]</a>";
						$row[]="\t\t<tr>
					<td>$link</td>
					<td>$val[fieldformat_format]</td>
				</tr>";
					}
					$table="<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n".@implode("\n",$row)."\n</table>";
				}
				else
					$table="<p><b>Nessun formato definito.</b></p>";
			}
				
		break;
	case "rasterdir":
		$idCat=$_REQUEST["catalog_id"];
		$extension=explode(",",CATALOG_EXT);
		foreach($extension as $e){
			if($e!="SHP") $ext[]=$e;
		}
		$filename=isset($_REQUEST["filtro"])?$_REQUEST["filtro"]:'';
		if($idCat==-1){
			echo "<p><b>Errore nella Selezione del Catalogo</b></p>";
			break;
		}
		elseif($idCat==0){
			echo "<p><b>Seleziona un Catalogo</b></p>";
			break;
		}
		else{
			require_once ADMIN_PATH."lib/filesystem.php";
			// CONNESSIONE GISCLIENT
			$sql="select catalog_path,base_path from ".DB_SCHEMA.".catalog inner join ".DB_SCHEMA.".project using (project_name) where catalog_id=$idCat";
			$db->sql_query($sql);
			$row = $db->sql_fetchrow();
			$shp_dir=$row[0];
			$base_path=$row[1];
			if(substr($base_path,-1)!="/") $base_path.="/";
			$navDir=($_REQUEST["dir"])?("/".@implode("/",$_REQUEST["dir"])):("");
			$dir=str_replace("//","/",$base_path.$shp_dir.$navDir);
			$fileList=Array();
			$arrNav=$_REQUEST["dir"];
			
			for($i=0;$i<count($arrNav);$i++){
				$d=$arrNav[$i];
				$paramNav="<input type=\"hidden\" name=\"dir[]\" value=\"$d\">\n\t";
			}
				
			$tmp=elenco_dir($dir);
			sort($tmp);
			$tmpFile=Array();
			
			foreach($ext as $e){
				$tmpF=elenco_file($dir,$e,$filename);
				for($i=0;$i<count($tmpF);$i++) $tmpFile[]=$tmpF[$i];
			}
			
			sort($tmp);
			if($arrNav){
				$fileList[]=Array("type"=>"dir","name"=>"..");
				$prm="'".@implode("','",$arrNav)."'";
				array_pop($arrNav);
				$paramUp="['".@implode("','",$arrNav)."']";
			}
			foreach($tmp as $v){
				$fileList[]=Array("type"=>"dir","name"=>$v);
			}
			foreach($tmpFile as $v){
				$fileList[]=Array("type"=>"file","name"=>$v);
			}
			if(!count($fileList))
				$table= "<p><b>Nessun Directory presente.</b></p>";
			elseif(!count($tmpFile) && !count($tmp))
				$fileList[]= Array("type"=>"file","name"=>"<p><b>Nessun File presente.</b></p>");
			if($fileList){
			
				foreach($fileList as $val){
					$type=$val["type"];
					$file=$val["name"];
					if ($type!="file"){
						$param=($file=="..")?($paramUp):("[".$prm.",'$file']");
						$dirTree=(count($_REQUEST["dir"]))?(@implode("/",$_REQUEST["dir"])."/"):("");
						$row[]="<tr><td width=\"3%\">&nbsp;</td><td width=\"50%\"><a href=\"#\" onclick=\"javascript:nav($param);\">$file</a></td></tr>";
					}
					else{
						$row[]="<tr><td width=\"3%\">&nbsp;</td><td width=\"50%\">$file</td></tr>";
					}
					
				}
				$obj="[{id:'$campo',value:'$dirTree'}]";
				$button[]="<input type=\"button\" value=\"Seleziona\" onclick=\"setdata($obj)\">";
				$table="<form method=\"post\" name=\"navigate\" id=\"frmNavigate\">
				<input type=\"hidden\" name=\"filtro\" value=\"$filename\">
	<table width=\"99%\">\n".@implode("\n",$row)."\n</table>
</form>";
				}
		}
		break;
	default	:
		echo "<p>Opzione non prevista</p>";
		break;
}

/*

$tabella=DB_SCHEMA.".".$tabella;
$sql="select distinct $campo, $fld from $tabella where $filter AND $fld ilike '$sql%' order by $campo;";

//$campo=$_GET["campo"];//riprendo il valore nel caso lo avessi cambiato

$db->sql_query ($sql);
$elenco=$db->sql_fetchrowset();
$nrec=$db->sql_numrows();
$fk_campo="fk_$campo";
*/

?>

<html>
	<head>
		<script  type="text/javascript" src="./js/Author.js"></script>
		<style type="text/css">

body,td,th {
	font-family: Georgia, Times New Roman, Times, serif;
	font-size:11px;
	color: #000000;
}
a:link {
	color: #0000FF;
}
a:visited {
	color: #0000FF;
}
a:hover {
	color: #FF9966;
}
a:active {
	color: #0000FF;
}
body {
	background-color: #FFFFDF;
}

		</style>
<script>






</script>
</head>
<body >

<FONT Verdana, Geneva, Arial, sans-serif size="-1">
<?php
echo $table;

/*
if ($flag){
	for($i=0;$i<$nrec;$i++){
		$jsvalore= htmlentities($elenco[$i][$campo]);
		$valore=$elenco[$i][$fld];
		$obj="[{id:'$campo', value:'$valore'}]";
		print("<a href=\"javascript:setdata($obj)\">$valore</a><br>");	
	}
}
else
	echo $table;
*/
?>
</FONT>
<br>
<form method="get" action="<?php echo $self?>">
<input type="hidden" name="campo" value="<?php echo $campo?>">
<input type="hidden" name="project" value="<?php echo $project_id?>">
<input type="hidden" name="prm_livello" value="<?php echo $_REQUEST["prm_livello"]?>">
<input type="button" value="Chiudi" onclick="javascript:closeWin()">
<?php

	if(!in_array($campo,Array("symbol_name","symbol_ttf_name","rasterdir")) || ($campo=="symbol_name") && $dbtable=="symbol")
		echo "<input type=\"submit\" value=\"Elenco completo\">";
	if(count($button)) echo implode("\n\t",$button);
	
	
?>

</form>

</body>
</html>
