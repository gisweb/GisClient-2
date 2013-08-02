<html>
<head>
<title>Importazione geoweb 1</title>
</head>
<body>
<?php
require_once "../../../config/config.php";
print("<pre>");
$connString = "user=".DB_USER." password=".DB_PWD." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT;
echo $connString."\n" ;
$db = pg_connect($connString);
$DBSCHEMA_NEW=DB_SCHEMA;
$DBSCHEMA_OLD="geoweb";
$TEMPLATE='gisclient';
$TEMPLATE='geoweb';

include_once "tabelle.php";

//Sistemo i cataloghi che hanno sia il path che la connessione al db
$sqlcatalog="delete from $DBSCHEMA_OLD.catalog where connection_id not in (select connection_id from $DBSCHEMA_OLD.connection);
			delete from $DBSCHEMA_OLD.catalog where catalog_id not in (select catalog_id from $DBSCHEMA_OLD.layer);
			delete from $DBSCHEMA_OLD.catalog where connection_id is null;\n";
echo $sqlcatalog;
pg_query($db, $sqlcatalog);		
$sqlcatalog="select distinct catalog_id,project_id,catalog_name,shape_dir from $DBSCHEMA_OLD.catalog where dbschema is not null and shape_dir is not null;\n";
echo $sqlcatalog;
$result = pg_query($db, $sqlcatalog);
$newcatalog = pg_fetch_all($result);

//print_r($newcatalog);
//print count($newcatalog);

for($i=0;$i<count($newcatalog);$i++){
	$sqlIdx="select $DBSCHEMA_OLD.new_pkey('catalog','catalog_id');";
	$result = pg_query($db, $sqlIdx);
	$idx=pg_fetch_result($result,0,0);
	$sql="insert into $DBSCHEMA_OLD.catalog(catalog_id,project_id,catalog_name,description,dbschema,shape_dir,doc_path) select $idx,project_id,catalog_name || '_fld',description,null,shape_dir,doc_path from $DBSCHEMA_OLD.catalog where catalog_id=".$newcatalog[$i]["catalog_id"].";\n";
	$sql.="update $DBSCHEMA_OLD.catalog set shape_dir=null where catalog_id=".$newcatalog[$i]["catalog_id"].";\n";
	$sql.="update $DBSCHEMA_OLD.layer set catalog_id=$idx where layertype_id=4 and catalog_id=". $newcatalog[$i]["catalog_id"].";\n";
	echo $sql;
	pg_query($db, $sql);
}
echo "Aggiornamento cataloghi OK\n\n";

$sql="BEGIN;\n";
$sql.="update $DBSCHEMA_OLD.e_layertype set layertype_id=10 where layertype_id=6;\n";
//Nomi di mapset doppi 
$sql.="update $DBSCHEMA_OLD.mapset set mapset_name=mapset.mapset_name||'_'||mapset.mapset_id from (select mapset_name from $DBSCHEMA_OLD.mapset group by 1 having count(mapset_name)>1) as mm where mm.mapset_name=mapset.mapset_name;\n";
//Nomi di qt doppi per tema
$sql.="update $DBSCHEMA_OLD.qt set qt_name=qt.qt_name||'_'||qt.qt_id  from (select qt_name,theme_id from $DBSCHEMA_OLD.qt group by 1,2 having count(qt_name)>1) as qq where qt.qt_name=qq.qt_name and qt.theme_id=qq.theme_id;\n";
$sql.="update $DBSCHEMA_OLD.project set project_name=lower(replace(project_name,' ','_'));\n";
$sql.="update $DBSCHEMA_OLD.project set project_srid=-1 where project_srid is null;\n";
$sql.="update $DBSCHEMA_OLD.project set imagelabel_position='LR' where imagelabel_position is null;\n";
$sql.="update $DBSCHEMA_OLD.layer set layer_name=lower(replace(layer_name,' ','_'));\n";
$sql.="insert into  $DBSCHEMA_OLD.font (font_name,file) select distinct font,font from  $DBSCHEMA_OLD.symbol_ttf where font not in (select font_name from  $DBSCHEMA_OLD.font);\n";
$sql.="delete from $DBSCHEMA_OLD.qtrelation where catalog_id not in (select catalog_id from $DBSCHEMA_OLD.catalog);\n";
$sql.="delete from $DBSCHEMA_OLD.qtrelation where qt_id not in (select qt_id from $DBSCHEMA_OLD.qt);\n";
$sql.="delete from $DBSCHEMA_OLD.qtrelation where qt_id is null or data_field_1 is null or table_field_1 is null;\n";
$sql.="update $DBSCHEMA_OLD.qtfield set qtfield_order=0 where qtfield_order is null;\n";
$sql.="update $DBSCHEMA_OLD.qtfield set searchtype_id=1 where searchtype_id is null;\n";
$sql.="update $DBSCHEMA_OLD.qtfield set resultype_id=3 where resultype_id is null;\n";
$sql.="update $DBSCHEMA_OLD.qtfield set fieldtype_id=1 where fieldtype_id is null;\n";
$sql.="update $DBSCHEMA_OLD.e_layertype set name='text' where ms_layertype=99;\n";
$sql.="delete from $DBSCHEMA_OLD.mapset_layergroup where layergroup_id is null;\n";
$sql.="delete from $DBSCHEMA_OLD.mapset_layergroup where mapset_id is null;\n";
$sql.="delete from $DBSCHEMA_OLD.mapset_layergroup where layergroup_id not in (select layergroup_id from $DBSCHEMA_OLD.layergroup);\n";
$sql.="delete from $DBSCHEMA_OLD.qt_link where qt_id is null or link_id is null;\n";
$sql.= "COMMIT;\n";
echo $sql;

$result=pg_query($db,$sql);
if($result)
	echo "Pulizia db esistente OK\n\n";
else{
	echo "Pulizia db esistente fallita!\n\n";
	echo pg_result_error($result);
	exit;
}

$sql="BEGIN;\n";	
foreach($TABLES as $table=>$fields){
	$fieldsName=implode(",",array_keys($fields));
	$fieldsValue=implode(",",$fields);
	$sql.="delete from $DBSCHEMA_NEW.$table;insert into $DBSCHEMA_NEW.$table ($fieldsName) select $fieldsValue from $DBSCHEMA_OLD.$table;\n";
}
$sql.="update $DBSCHEMA_NEW.class set symbol_ttf_name=symbol_ttf.symbol_name from $DBSCHEMA_OLD.symbol_ttf where symbol_ttf_name=symbol_ttf_id;\n";
$sql.="update $DBSCHEMA_NEW.class set symbol_ttf_name=null where symbol_ttf_name ='';\n";
$sql.="update $DBSCHEMA_NEW.class set label_font=null where label_font ='';\n";
$sql.= "COMMIT;\n";

echo $sql;
$result=pg_query($db,$sql);
if($result)
	echo "Importazione OK\n";
else{
	echo "Importazione fallita!\n";
	echo pg_result_error($result);
	exit;
}

//Importazione degli utenti

//Utenti
$sql="BEGIN;\n";
$sql.="delete from $DBSCHEMA_NEW.users;
insert into $DBSCHEMA_NEW.users
select distinct on (username) username, pwd, enc_pwd, data_creazione, data_scadenza, data_modifica, attivato, ultimo_accesso, cognome, nome, macaddress, ip, host, controllo, userdata, email from $DBSCHEMA_OLD.user_admin;
insert into $DBSCHEMA_NEW.users
select distinct on (username) username, pwd, enc_pwd, data_creazione, data_scadenza, data_modifica, attivato, ultimo_accesso, cognome, nome, macaddress, ip, host, controllo, userdata, email from $DBSCHEMA_OLD.user 
where user_id<>1000 and username not in (select username from $DBSCHEMA_NEW.users);";

//Amministratori locali
$sql.="delete from $DBSCHEMA_NEW.project_admin;
insert into $DBSCHEMA_NEW.project_admin
select distinct project_name,username from $DBSCHEMA_OLD.user_admin inner join $DBSCHEMA_OLD.user_project using(user_id) inner join $DBSCHEMA_OLD.project using(project_id) 
where project_name in (select project_name from $DBSCHEMA_NEW.project) and username in (select username from $DBSCHEMA_NEW.users);";

//Gruppi
$sql.="delete from $DBSCHEMA_NEW.groups;
insert into $DBSCHEMA_NEW.groups
select distinct project_name|| '_'||usergroup as groupname,usergroup.description || ' (' || project_name || ')' as description from $DBSCHEMA_OLD.usergroup inner join $DBSCHEMA_OLD.project using(project_id);";

//Associazione utenti e gruppi
$sql.="delete from $DBSCHEMA_NEW.user_group;
insert into $DBSCHEMA_NEW.user_group
select distinct username,groupname from (
(select username,project_name|| '_'||usergroup.usergroup as groupname from $DBSCHEMA_OLD.user inner join $DBSCHEMA_OLD.user_project using(user_id)
inner join $DBSCHEMA_OLD.project using(project_id) inner join $DBSCHEMA_OLD.usergroup using(project_id) where user_id<>1000)
union
(select user_admin.username,project_name|| '_'||usergroup.usergroup as groupname from $DBSCHEMA_OLD.user_admin inner join $DBSCHEMA_OLD.user_project using(user_id)
inner join $DBSCHEMA_OLD.project using(project_id) inner join $DBSCHEMA_OLD.usergroup using(project_id))) as foo where username in (select username from $DBSCHEMA_NEW.users);";

//Associazione mapset e gruppi
$sql.="delete from $DBSCHEMA_NEW.mapset_groups;
insert into $DBSCHEMA_NEW.mapset_groups(mapset_name,group_name)
select distinct mapset_name, project_name|| '_'||usergroup.usergroup as groupname from $DBSCHEMA_OLD.user inner join $DBSCHEMA_OLD.user_project using(user_id)
inner join $DBSCHEMA_OLD.project using(project_id) inner join $DBSCHEMA_OLD.usergroup using(project_id) inner join $DBSCHEMA_OLD.mapset_usergroup
on (usergroup.usergroup_id=mapset_usergroup.usergroup_id) inner join $DBSCHEMA_OLD.mapset using (mapset_id) where user_id<>1000;\n";

$sql.= "COMMIT;\n";

echo $sql;
$result=pg_query($db,$sql);
if($result)
	echo "Importazione Utenti OK\n";
else{
	echo "Importazione Utenti fallita!\n";
	echo pg_result_error($result);
	exit;
}

//GENERAZIONE DELLE LEGENDE

require_once (ROOT_PATH."lib/functions.php");
require_once (ROOT_PATH."lib/gcSymbol.class.php");
$oSymbol=new Symbol("class");
$oSymbol->createIcon();




//VISUALIZZAZIONE DELLE LEGENDE
$smbList = $oSymbol->getList();

//print_array($smbList);
$htmlTable = "<table border=\"1\" cellspacing=\"1\" cellpadding=\"2\">\n";
$htmlTable .= "<tr><th>".implode("</th><th>",$smbList["headers"])."</th></tr>\n";
for($i=0;$i<count($smbList["values"]);$i++){
	$row="<td><img src=\"../getImage.php?".$smbList["values"][$i][0]."\" alt=\"ID ".$smbList["values"][$i][0]."\" /></td>";
	for($j=1;$j<count($smbList["values"][$i]);$j++)
		$row.="<td>".$smbList["values"][$i][$j]."</td>";
	$htmlTable .= "<tr>$row</tr>\n";
}
$htmlTable .= "</table>\n";
echo $htmlTable;

?>

<a href="../index.php">Vai alla pagina dell'Author</a>
<p>Ricorda di rimuovere lo schema setup</p>
</body>
</html>

