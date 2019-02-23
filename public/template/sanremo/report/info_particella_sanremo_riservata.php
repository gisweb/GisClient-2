<?php 
session_start();

//configurazione del sistema
require_once('../../../../config/config.db.php');
require_once('../../../../config/config.php');



//print('<pre>');
//print_r($_REQUEST);
//print_r($_SESSION);
//print (DB_HOST);
//trovo la stringa di conessione e altre info dato il layerid
//$db = new sql_db(DB_HOST.":5434",DB_USER,DB_PWD,DB_NAME, false);
//echo $db->db_connect_id;
//if(!$db->db_connect_id) die( "Impossibile connettersi al database " . DB_NAME); 


define('AREA_MIN','5');//area minima di intersezione per le query di overlay


require_once('report_common.php');

define('THE_GEOM',$layerGeom);
//echo "<p>$layerGeom</p>";
if ($layerFilter) $layerFilter=" and " .$layerFilter;

$viewVincoli="(select vincolo.descrizione as descvincolo,vincolo.norma,tavola.descrizione as desctavola,zona.descrizione as desczona,zona.sigla as siglazona,zona.nome_vincolo,zona.nome_tavola,zona.nome_zona,zona_plg.the_geom,vincolo.ordine as ordvincolo,tavola.ordine as ordtavola,zona.ordine as ordzona from vincoli.zona_plg inner join vincoli.zona using(nome_vincolo,nome_tavola,nome_zona) inner join vincoli.tavola using (nome_vincolo,nome_tavola) inner join vincoli.vincolo using (nome_vincolo) where (data_a is null or data_a>current_date) and (data_da<current_date or data_da is null))";
	//print "<p>$viewVincoli</p>";
$queryString="select view_vincoli.descvincolo,view_vincoli.norma,view_vincoli.desctavola,view_vincoli.desczona,view_vincoli.siglazona,view_vincoli.nome_vincolo,view_vincoli.nome_tavola,view_vincoli.nome_zona,sezione,foglio,mappale,round(sum(st_area(st_intersection(particelle.".THE_GEOM.",view_vincoli.the_geom))/st_area (particelle.".THE_GEOM.")*100)::numeric,1) as perc_area
	from nct.particelle,$viewVincoli as view_vincoli
	where particelle.$layerUniqueField = $resultIdList
	and view_vincoli.nome_vincolo!='PUC'
	and (st_area(st_intersection (particelle.".THE_GEOM.",view_vincoli.the_geom))>5 or st_area(st_intersection(particelle.".THE_GEOM.",view_vincoli.the_geom))/st_area (particelle.".THE_GEOM.")>=0.02)
	group by particelle.".THE_GEOM.",1,2,3,4,5,6,7,8,9,10,11,ordvincolo,ordtavola,ordzona order by ordvincolo,ordtavola,ordzona,perc_area desc;";
	//print "<p>$queryString</p>";
	$db->sql_query ($queryString);
	while($row = $db->sql_fetchrow()){
		$sezione=$row["sezione"];
		$foglio=$row["foglio"];
		$mappale=$row["mappale"];
		$aVincoli[$row["nome_vincolo"]]["DESCRIZIONE"]=$row["descvincolo"];
		$aVincoli[$row["nome_vincolo"]]["NORMA"]=$row["norma"];
		$aVincoli[$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["DESCRIZIONE"]=$row["desctavola"];
		$aVincoli[$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["ZONE"][$row["nome_zona"]]["DESCRIZIONE"]=$row["desczona"];
		$aVincoli[$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["ZONE"][$row["nome_zona"]]["SIGLA"]=$row["siglazona"];
		$aVincoli[$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["ZONE"][$row["nome_zona"]]["PERCAREA"]=$row["perc_area"];
	}	
//print_array($db->sql_fetchrowset());
//print_array($aVincoli);exit;	
	
	
	
	//gid, nr, zc, fg, mappale, mq, mqass, prg, note, cognome, nome, superficie, id1, prat_ed, vol_res, vol_non_re
	
$queryStringAsservimenti="SELECT distinct nr, prat_ed
							FROM asservimenti.asservimenti
							where st_area(st_intersection(the_geom,(select bordo_gb from nct.particelle where particelle.$layerUniqueField = $resultIdList)))>1";
	$db->sql_query ($queryStringAsservimenti);

	
	while($row = $db->sql_fetchrow()){
		$aAsservimenti[$row["gid"]]["NUMERO"]=$row["nr"];
		//$aAsservimenti[$row["gid"]]["SEZIONE"]=$row["zc"];
		//$aAsservimenti[$row["gid"]]["FOGLIO"]=$row["fg"];
		//$aAsservimenti[$row["gid"]]["MAPPALE"]=$row["mappale"];
		//$aAsservimenti[$row["gid"]]["MQ"]=$row["mq"];
		//$aAsservimenti[$row["gid"]]["MQASS"]=$row["mq"];
		//$aAsservimenti[$row["gid"]]["NOTE"]=$row["note"];
		//$aAsservimenti[$row["gid"]]["COGNOME"]=$row["cognome"];
		//$aAsservimenti[$row["gid"]]["NOME"]=$row["nome"];
		//$aAsservimenti[$row["gid"]]["SUPERFICIE"]=$row["superficie"];
		$aAsservimenti[$row["gid"]]["PRATICA_EDILIZIA"]=$row["prat_ed"];
		//$aAsservimenti[$row["gid"]]["VOL_RES"]=$row["vol_res"];
		//$aAsservimenti[$row["gid"]]["VOL_NON_RE"]=$row["vol_non_re"];
		
		
	}	
	
	//print_array($aAsservimenti);exit;	

	?>
<html>
	<head>
		<title>Informazioni</title>
		<style>
			BODY.info_particella
				{ margin-left:4px; margin-right:4px; margin-top:10px; margin-bottom:0px; background-color:rgb(255,255,255); }
				
			BODY.info_particella TABLE.tabella
				{ width:100%; font-family:arial; border-collapse:collapse; font-size:14px; }
				
			BODY.info_particella DIV.mappale
				{ font-family:arial; font-size:18px; text-align:center; font-weight:bold; }
				
			BODY.info_particella TABLE.tabella TD.vincolo
				{ border-bottom:1px solid gray; padding-top:30px; color:rgb(53,84,186); font-weight:bold; font-size:18px; }
				
			BODY.info_particella TABLE.tabella TD.tavola
				{ padding-top:12px; font-weight:bold; }
				
			BODY.info_particella TABLE.tabella TR.zona
				{ background-color:rgb(235,235,245); }
				
			BODY.info_particella TABLE.tabella TD.sigla
				{ padding-right:4px; border-bottom:2px solid white; }
				
			BODY.info_particella TABLE.tabella TD.descrizione
				{ border-bottom:2px solid white; }
				
			BODY.info_particella TABLE.tabella TD.percentuale
				{ text-align:right; border-bottom:2px solid white; }
				
			BODY.info_particella TABLE.tabella TD.data_da
				{ text-align:center; border-bottom:2px solid white; padding-left:24px; padding-right:8px; }
			
			BODY.info_particella TABLE.tabella TD.data_a
				{ text-align:center; border-bottom:2px solid white; padding-left:8px; padding-right:24px; color:red; }
				
			BODY.info_particella .evidenziato
				{ color:red; }
				
			BODY.info_particella .pulsante
				{ font-family:arial; font-size:14px; color:gray; margin-top:24px; margin-bottom:10px; margin-right:10px; cursor:hand; cursor:pointer; width:65px; height:20px; border:1px solid rgb(180,180,180); background-color:rgb(245,245,245); padding:2px; text-align:center; float:right; }
		</style>
	</head>
	<body class="info_particella">
		<div align="center" style="margin-bottom:36px; font-family:arial">
			<img src="logo_sanremo.jpg"><br><br>
			<big><b>Scheda urbanistica particella</b></big><br>
			<small>(I dati contenuti nella presente scheda sono da ritenersi indicativi e non probatori)</small>
		</div>
		<div class="mappale">
			<?php if($sezione)
				{
				if($sezione=='A') $sezione="Sanremo";
				if($sezione=='B') $sezione="Bussana";
				if($sezione=='C') $sezione="Coldirodi";
				print("Sezione:  <span class=\"evidenziato\">$sezione</span>");
				};
			?>
			Foglio:  <span class="evidenziato"><?php echo $foglio ?></span>
			Mappale: <span class="evidenziato"><?php echo $mappale ?></span>
		</div>
		<?php
			echo("<table class=\"tabella\" border=\"0\">");
			foreach ($aVincoli as $key0 => $value0)
				{
				echo("<tr>");
				echo("<td colspan=\"3\" class=\"vincolo\">");
				echo($value0[DESCRIZIONE]);
				echo("</td>");
				echo("</tr>");
				foreach ($value0[TAVOLE] as $key1 => $value1)
					{
					echo("<tr>");
					echo("<td colspan=\"3\" class=\"tavola\">");
					echo($value1[DESCRIZIONE]);
					echo("</td>");
					echo("</tr>");
					foreach ($value1[ZONE] as $key2 => $value2)
						{
						echo("<tr class=\"zona\">");
						echo("<td class=\"sigla\" valign=\"top\">");
						echo($value2[SIGLA]);
						echo("</td>");
						echo("<td class=\"descrizione\" valign=\"top\">");
						echo($value2[DESCRIZIONE]);
						echo("</td>");
						echo("<td class=\"percentuale\" valign=\"top\">");
						echo($value2[PERCAREA]);
						echo("%</td>");
						echo("</tr>");
						};
					};
				};
			echo("</table>");
			
			
			echo("<br><br><br><table class=\"tabella\" border=\"0\" style=\"width:100%\">");
			echo("<tr><td class=\"vincolo\" style=\"border:0px solid black\">Asservimenti</td></tr><tr><td>Il database degli asservimenti &egrave; consultabile all'indirizzo di rete: <a href=\"Z:\bacheca\Asservimenti\Database_ass.mdb\">Z:\bacheca\Asservimenti\Database_ass.mdb

 </a>  
</td></tr>");
			echo("</table><table class=\"tabella\" border=\"1\" style=\"width:auto; margin-top:8px\"><tr>");
			echo("<td class=\"tavola\" style=\"height:auto; padding:2px\">N. asservimento</td>");
			//echo("<td class=\"tavola\">Sez.</td>");
			//echo("<td class=\"tavola\">Foglio</td>");
			//echo("<td class=\"tavola\">Mappale</td>");
			//echo("<td class=\"tavola\">Mq</td>");
			//echo("<td class=\"tavola\">Mq ass.</td>");
			//echo("<td class=\"tavola\">Note</td>");
			//echo("<td class=\"tavola\">Cognome</td>");
			//echo("<td class=\"tavola\">Nome</td>");
			//echo("<td class=\"tavola\">Superficie</td>");
			echo("<td class=\"tavola\" style=\"height:auto; padding:2px\">Prat. edilizia</td>");
			//echo("<td class=\"tavola\">Vol. res.</td>");
			//echo("<td class=\"tavola\">Vol. non re.</td>");
			echo("</tr>");

			foreach ($aAsservimenti as $key1 => $value1)
				{
				echo("<tr>");
				echo("<td style=\"height:auto; padding:2px\">");
				echo($value1[NUMERO]);
				echo("</td>");
				echo("<td style=\"height:auto; padding:2px\">");
				echo($value1[PRATICA_EDILIZIA]);
				echo("</td>");
				echo("</tr>");
				};
			echo("</table>");
		?>
		<div onclick="window.close()" class="pulsante">Chiudi</div>
		<div onclick="window.print()" class="pulsante">Stampa</div>
		
	</body>
</html>
