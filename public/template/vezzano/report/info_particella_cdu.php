<?php
session_start();
define('AREA_MIN','5');//area minima di intersezione per le query di overlay
define('THE_GEOM','bordo_cs');
require("../../gisclient/report/report_common.php");//COMUNE A TUTTI I REPORT

if ($layerFilter) $layerFilter=" and " .$layerFilter;

$viewVincoli="(select vincolo.descrizione as descvincolo,vincolo.norma,tavola.descrizione as desctavola,zona.descrizione as desczona,zona.sigla as siglazona,zona.nome_vincolo,zona.nome_tavola,zona.nome_zona,zona_plg.the_geom,vincolo.ordine as ordvincolo,tavola.ordine as ordtavola,zona.ordine as ordzona from vincoli.zona_plg inner join vincoli.zona using(nome_vincolo,nome_tavola,nome_zona) inner join vincoli.tavola using (nome_vincolo,nome_tavola) inner join vincoli.vincolo using (nome_vincolo) where zona_plg.nome_vincolo in ('PRG','ASSETTO_IDROGEOLOGICO','BENI_PAESISTICI','PTCP','VINCOLI_TERRITORIALI'))";



$queryString="select view_vincoli.descvincolo,view_vincoli.norma,view_vincoli.desctavola,view_vincoli.desczona,view_vincoli.siglazona,view_vincoli.nome_vincolo,view_vincoli.nome_tavola,view_vincoli.nome_zona,sezione,foglio,mappale,round(sum(st_area(st_intersection(particelle.".THE_GEOM.",view_vincoli.the_geom))/st_area (particelle.".THE_GEOM.")*100)::numeric,1) as perc_area
	from nct.particelle,$viewVincoli as view_vincoli
	where particelle.$layerUniqueField = $resultIdList
	and (st_area(st_intersection (particelle.".THE_GEOM.",view_vincoli.the_geom))>10 or st_area(st_intersection(particelle.".THE_GEOM.",view_vincoli.the_geom))/st_area (particelle.".THE_GEOM.")>=0.02)
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
	
	
	?>
<html>
	<head>
		<title>Informazioni</title>
		<style>
			BODY.info_particella
				{ margin-left:4px; margin-right:4px; margin-top:10px; margin-bottom:0px; background-color:rgb(255,255,255); }
				
			BODY.info_particella TABLE.tabella
				{ width:100%; font-family:arial; border-collapse:collapse; font-size:10pt; }
				
			BODY.info_particella DIV.mappale
				{ font-family:arial; font-size:12pt; text-align:center; font-weight:bold; margin-bottom:0.4cm; clear:both; }
				
			BODY.info_particella TABLE.tabella TD.vincolo
				{ border-bottom:1px solid gray; padding-top:30px; color:rgb(53,84,186); font-weight:bold; font-size:12pt; }
				
			BODY.info_particella TABLE.tabella TD.tavola
				{ padding-top:12px; font-weight:bold; }
				
			BODY.info_particella TABLE.tabella TR.zona
				{ background-color:rgb(235,235,245); }
				
			BODY.info_particella TABLE.tabella TD.sigla
				{ padding-right:4px; border-bottom:2px solid white; width:2.4cm; }
				
			BODY.info_particella TABLE.tabella TD.descrizione
				{ border-bottom:2px solid white; width:14.6cm; }
				
			BODY.info_particella TABLE.tabella TD.percentuale
				{ text-align:right; border-bottom:2px solid white; }
				
			BODY.info_particella .evidenziato
				{ color:red; }
				
			BODY.info_particella .pulsante
				{ font-family:arial; font-size:14px; color:gray; margin-top:24px; margin-bottom:10px; margin-right:10px; cursor:hand; cursor:pointer; width:65px; height:20px; border:1px solid rgb(180,180,180); background-color:rgb(245,245,245); padding:2px; text-align:center; float:right; }
		</style>
		<style type="text/css" media="print">
			BODY.info_particella .pulsante
				{ display:none; }
				
			BODY.info_particella TABLE.tabella TD.sigla
				{ border-bottom:1px solid rgb(235,235,245) }
				
			BODY.info_particella TABLE.tabella TD.descrizione
				{ border-bottom:1px solid rgb(235,235,245) }
				
			BODY.info_particella TABLE.tabella TD.percentuale
				{ border-bottom:1px solid rgb(235,235,245); }
				
			BODY.info_particella TABLE.tabella TD.tavola
				{ border-bottom:2px solid rgb(235,235,245); }
				
				
		</style>
	</head>
	<body class="info_particella">
		<div onclick="window.close()" class="pulsante">Chiudi</div>
		<div onclick="window.print()" class="pulsante">Stampa</div>
		<div class="mappale">
			<img src="http://vezzano.praticaweb.it/images/vezzano_new.jpg" style="width:15cm"><br><br><br><br>
			Destinazione urbanistica del mappale <span class="evidenziato"><?php echo $mappale ?></span> del foglio <span class="evidenziato"><?php echo $foglio ?></span>
			
		
			<?php if($sezione)print("(sezione <span class=\"evidenziato\">$sezione</span>)");?>
			
			<br><i><small>Elaborato non valido ai fini legali</small></i>
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
						//echo("<td class=\"percentuale\" valign=\"top\">");
						//echo($value2[PERCAREA]);
						//echo("%</td>");
						echo("</tr>");
						};
					};
				};
			echo("</table>");
		?>
	</body>
</html>
