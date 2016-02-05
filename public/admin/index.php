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

if (!file_exists("../../config/config.php")) die ("Manca setup");
include_once "../../config/config.php";
header("Content-Type: text/html; Charset=".CHAR_SET);
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");

$Errors=array();
$Notice=array();

if(isset($_REQUEST["logout"]) && $_REQUEST["logout"]==1){
	unset($_SESSION["USERNAME"]);
	unset($_SESSION["GROUPS"]);
	unset($_SESSION["ROLE"]);
	$usr->status=false;
} 


if (!$usr->status) {
	include_once ADMIN_PATH."enter.php";
	exit;
}


include ADMIN_PATH."lib/page.class.php";

$param=array();
$arr_action=Array("salva","aggiungi","cancella","elimina","genera mappa","copia","sposta");
$arr_noaction=Array("chiudi","annulla","avvia importazione");
if (!empty($_REQUEST["parametri"]))
	$param=$_REQUEST["parametri"];

$action=@array_pop(@array_keys($_POST["azione"]));

$p=new page($_REQUEST,1);

$p->get_conf();
if (in_array(strtolower($p->action),$arr_action) || in_array(strtolower($p->action),$arr_noaction)){
	include_once ADMIN_PATH."lib/savedata.class.php";
	if (!$_POST["savedata"] || !file_exists(ADMIN_PATH."db/db.".$_POST["savedata"].".php")) 
		include ADMIN_PATH."db/db.save.php";
	else
		include ADMIN_PATH."db/db.".$_POST["savedata"].".php";
	
}
else
	unset($_SESSION["ADD_NEW"]);
//print_array($p);
?>
<html>
<head>
	<title>Author</title>
	<LINK media="screen" href="css/styles.css" type="text/css" rel="stylesheet">
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?php echo CHAR_SET ?>">
	<script  type="text/javascript" src="./js/Author.js"></script>
	<script type="text/javascript">
		window.addEvent('domready', function() {
			var container = $('containment');
			var d=$('dwindow');
			if(d) new Drag.Move('dwindow', {'container': container});
		});
	</script>
</head>
<body>

<?php
include ADMIN_PATH."inc/inc.admin.page_header.php";



$p->writeMenuNav();
?>
<div id="containment" style="position: relative;">
<?php
echo "<table width=\"100%\" border=\"0\"><tr><td>";

$p->writePage($Errors,$Notice);

echo "</td></tr></table>";


?>

<form method="POST" id="frm_param" name="frm_param">
<?php
	$p->write_parameter();
?>
</form>

<?php
include ADMIN_PATH."inc/inc.window.php";

?>
</div>
</body>
</html>
