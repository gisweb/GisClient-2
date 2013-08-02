<?php

	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
    $fName='';
	if(isset($_POST["esporta"])){
		
		include_once ADMIN_PATH."lib/export.php";
		$level=$_POST["level"];
		$project=$_POST["project"];
		$objId=$_POST["obj_id"];
		$fName=$_POST["filename"];
		$overwrite=$_POST["overwrite"];
		//$pkey=parse_ini_file(ADMIN_PATH."include/primary_keys.ini");
		$structure=_getPKeys();
		if (!file_exists(ADMIN_PATH."export/$fName") || $overwrite){
			if (file_exists(ADMIN_PATH."export/$fName")) $overwrite_message="Il File $fName � stato sovrascritto.";
			if($_POST["azione"]="Esporta"){
				$l=$structure["pkey"][$_POST["livello"]][0];
				$sql="select e_level.id,e_level.name,coalesce(e_level.struct_parent_id,0) as parent,X.name as parent_name,e_level.leaf from ".DB_SCHEMA.".e_level left join ".DB_SCHEMA.".e_level X on (e_level.struct_parent_id=X.id) order by e_level.depth asc;";
				if (!$db->sql_query($sql)){
					print_debug($sql,null,"page_obj");
					die("<p>Impossibile eseguire la query : $sql</p>");
				}
				$ris=$db->sql_fetchrowset();
				foreach($ris as $v) $array_levels[$v["id"]]=Array("name"=>$v["name"],"parent"=>$v["parent"],"leaf"=>$v["leaf"]);
				$r=_export($fName,$_POST["livello"],$project,$structure,1,'',Array("$l"=>$objId));
				
				$message="$overwite_message <br> FILE <a href=\"#\" onclick=\"javascript:openFile('".ADMIN_PATH."export/$fName')\">$fName<a/> ESPORTATO CORRETTAMENTE";
			}
		}
	
			$resultForm="<DIV id=\"result\">
		<p style=\"color:red;\"><b>$message</b></p>
	<form name=\"file\" id=\"file\" target=\"_new\" method=\"POST\">
		
	</form>
</DIV>";
	}
	
?>
	<script>
		function openFile(f){
			var frm=$('file');
			frm.action='download.php'
			frm.appendChild(new Element('input',{'type':'hidden','name':'file','value':f})); 
			frm.appendChild(new Element('input',{'type':'hidden','name':'action','value':'view'})); 
			frm.appendChild(new Element('input',{'type':'hidden','name':'type','value':'text'}));
			frm.submit();
		}

	</script>

<table cellPadding="2" border="0" class="stiletabella" width="90%">
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Nome File</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" value="<?php echo $fName?>" name="filename" id="filename">
		</td>
	</tr>

	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Sovrascrivi File</b></font></td>
		<td valign="middle">
			<SELECT class="textbox" name="overwrite" >
				<OPTION value="0" selected>No</OPTION>
				<OPTION value="1">Si</OPTION>
			</SELECT>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		<hr>
			<input type="hidden" name="esporta" value="1">
			<input type="hidden" name="project" value="<?php echo $project;?>">
			<input type="hidden" name="obj_id" value="<?php echo $objId;?>">
			<input type="hidden" name="level" value="<?php echo $level;?>">
			
			<input type="submit" class="hexfield" value="Esporta" name="azione">
			<input type="submit" class="hexfield" value="Annulla" name="azione" style="margin-left:5px;" onclick="javascript:annulla()">
		</td>
	</tr>
</table>
