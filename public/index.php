<?php

if (!file_exists("../config/config.php")) die ("Manca setup");
include_once "../config/config.php";

header("Content-Type: text/html; Charset=".CHAR_SET);
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");

if(!empty($_REQUEST["logout"])){
	unset($_SESSION["USERNAME"]);
	unset($_SESSION["GROUPS"]);
	unset($_SESSION["ROLE"]);
}

$groupList="''";
if (isset($_SESSION["USERNAME"]) && isset($_SESSION["GROUPS"])){
	$aGroups = array();
	foreach ($_SESSION["GROUPS"] as $grp) $aGroups[] = addslashes($grp); 
	$groupList="'".implode("','",$aGroups)."'";
}

if (isset($_SESSION["USERNAME"]) && isset($_SESSION["GROUPS"])) {
	$groupList="'".implode("','",$_SESSION["GROUPS"])."'";
} else {
	$groupList="''";
}
$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id)  die( "Impossibile connettersi al database");

if(isset($_SESSION["USERNAME"]) && $_SESSION["USERNAME"]==SUPER_USER) {
	$sql="SELECT distinct mapset_name,mapset_title,mapset_extent,project_name,template,project_title FROM ".DB_SCHEMA.".mapset INNER JOIN ".DB_SCHEMA.".project using(project_name) order by project_title,project_name,mapset_title,mapset_name;";
} else{
	$sqlLocalAdmin = "select project_name from $dbSchema.project_admin where username = '" . $_SESSION["USERNAME"] ."'";
	$sql="SELECT distinct mapset_name,mapset_title,mapset_extent,project_name,template,project_title FROM ".DB_SCHEMA.".mapset LEFT JOIN ".DB_SCHEMA.".mapset_groups using(mapset_name) LEFT JOIN ".DB_SCHEMA.".project using(project_name) WHERE (private=0 or project_name in ($sqlLocalAdmin) or group_name in ($groupList)) order by  project_title,project_name,mapset_title,mapset_name;";
}
if(!$db->sql_query($sql) && $_SESSION["USERNAME"])
	echo "$sql";

$ris=$db->sql_fetchrowset();
$mapset=array();
for($i=0;$i<count($ris);$i++){
	$mapset[$ris[$i]["project_name"]][]=Array("name"=>$ris[$i]["mapset_name"],"title"=>$ris[$i]["mapset_title"],"template"=>$ris[$i]["template"],"extent"=>$ris[$i]["mapset_extent"]);
	//$mapset[$ris[$i]["project_name"]]["description"]=$ris[$i]["project_title"];
}

$table = '';
$newTable = '';
foreach($mapset as $key=>$map){
	$table.="\n\t<tr>
		<td colspan=\"2\" class=\"title\">Progetto $key</td>
	</tr>";
	$newTable.="
	<tr>
		<td>
			<fieldset>
				<legend class=\"title\">  <img src=\"images/plus.gif\" onclick=\"javascript:showMaps(this,'table_$key')\">   Progetto $key</legend>
				<table id=\"table_$key\" >
	";
	for($j=0;$j<count($map);$j++){
		$extent=implode(",",explode(" ",trim($map[$j]["extent"])));
		$newTable.="\n\t<tr>
			<td><a href=\"#\" onclick=\"javascript:GisClient.OpenMapset('".$map[$j]["name"]."','".$map[$j]["template"]."')\"><img src=\"./images/page_white_world.png\" border=0></a></td>
			<td class=\"data\">".$map[$j]["title"]."</td>
		</tr>";
	}
	$newTable.="				
				</table>
			</fieldset>
		</td>
	</tr>";
	for($j=0;$j<count($map);$j++){
		$extent=implode(",",explode(" ",trim($map[$j]["extent"])));
		$table.="\n\t<tr>
			<td><a href=\"#\" onclick=\"javascript:GisClient.OpenMapset('".$map[$j]["name"]."','".$map[$j]["template"]."')\"><img src=\"./images/page_white_world.png\" border=0></a></td>
			<td class=\"data\">".$map[$j]["title"]."</td>
		</tr>";
	}
}

if(!count($mapset)){
	$table="\n\t<tr>
		<td colspan=\"2\" class=\"title\">L'utente non ha accesso a nessuna mappa</td>
	</tr>";
}
if(!isset($_SESSION["USERNAME"])){
	$logTitle="Login";
	$logJs="javascript:return encript_pwd('password','frm_enter');";
	$logout=0;
	$btn="Entra";
	$usrEnabled="";
	$pwdEnabled="";
}
else{
	$logTitle="Logout";
	$logJs="";
	$logout=1;
	$btn="Esci";
	$usrEnabled="disabled";
	$pwdEnabled="disabled";
}
$user=(empty($_SESSION["USERNAME"]))?("GUEST"):(($_SESSION["USERNAME"]==SUPER_USER)?("AMMINISTRATORE"):($_SESSION["USERNAME"]));

?>

<html>
<head>
	<title>Maps</title>
	<LINK media="screen" href="admin/css/styles.css" type="text/css" rel="stylesheet">
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
	<SCRIPT language="javascript" src="jslib/GisClient.js" type="text/javascript"></SCRIPT>
	<SCRIPT language="javascript" src="jslib/jxlib.js" type="text/javascript"></SCRIPT>
	<SCRIPT language="javascript" src="./admin/js/administrator.js" type="text/javascript"></SCRIPT>
	<SCRIPT language="javascript" src="./admin/js/md5.js" type="text/javascript"></SCRIPT>
	<style>
		.data{
			WORD-SPACING: 0em;
			FONT: 13px/1.3em Verdana, Geneva, Arial, sans-serif;
		}
		.title{
			WORD-SPACING: 0em;
			FONT: 15px/1.3em Verdana, Geneva, Arial, sans-serif;
			PADDING:5px 0px 5px 0px; 
		}
	</style>
	
	<script  type="text/javascript">
		function showMaps(img,id){
			if($(id).style.display=='none'){
				img.src='./images/plus.gif';
				$(id).style.display='';
			}
			else{
				img.src='./images/minus.gif';
				$(id).style.display='none';
			
			}

		}
	</script>
	

</head>
<body>
<?php
include ROOT_PATH."public/admin/inc/inc.page_header.php";


?>
<table>
	<tr>
		<td width="85%" valign="top">
			<table>
				<tr>
					<td colspan="2"><b>Elenco delle mappe disponibili</b></td>
				</tr>
				<?php echo $newTable;?>
			</table>
		</td>

		<td valign="top">
			<form action="<?php echo $_SERVER["PHP_SELF"]?>" method="post" class="riquadro" id="frm_enter">
				<fieldset>
					<legend><?php echo $logTitle;?></legend>
					
					<fieldset style="border:0px;">
						<legend>Nome Utente</legend>
						<input name="username" type="text" id="username" tabindex=1 <?php echo $usrEnabled?>>
					</fieldset>
					<fieldset style="border:0px;">
						<legend>Password</legend>
						<input name="password" type="password" id="password" tabindex=2 <?php echo $pwdEnabled?>>
						<input name="enc_password" type="hidden" id="enc_password" tabindex=2>
						<input name="logout" type="hidden" value="<?php echo $logout;?>">
					</fieldset>
					<input type="submit" name="azione" value="<?php echo $btn;?>" style="width:80" tabindex="3" onclick="<?php echo $logJs;?>">
				</fieldset>	
			</form>
		</td>
	</tr>
</table>
</body>
</html>
