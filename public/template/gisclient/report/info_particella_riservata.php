<?php 
session_start();

//configurazione del sistema
require_once('../../../../config/config.db.php');
require_once('../../../../config/config.php');

//print('<pre>');
//print_r($_REQUEST);
//print_r($_SESSION);
//trovo la stringa di conessione e altre info dato il layerid
$db = new sql_db(DB_HOST.":5434",DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die( "Impossibile connettersi al database " . DB_NAME); 


define('AREA_MIN','5');//area minima di intersezione per le query di overlay


require_once('report_common.php');

define('THE_GEOM',$layerGeom);
//echo "<p>$layerGeom</p>";
if ($layerFilter) $layerFilter=" and " .$layerFilter;

$viewVincoli="(select vincolo.descrizione as descvincolo,vincolo.norma,tavola.descrizione as desctavola,zona.descrizione as desczona,zona.sigla as siglazona,zona.nome_vincolo,zona.nome_tavola,zona.nome_zona,zona_plg.the_geom,vincolo.ordine as ordvincolo,tavola.ordine as ordtavola,zona.ordine as ordzona from vincoli.zona_plg inner join vincoli.zona using(nome_vincolo,nome_tavola,nome_zona) inner join vincoli.tavola using (nome_vincolo,nome_tavola) inner join vincoli.vincolo using (nome_vincolo)
union
	(SELECT 'Asservimenti' as descvincolo, null::text as norma, 'Tipo di asservimento'::text as desctavola, descrizione||coalesce(' dalla pratica: '||label,'') as desczona, ''::text as siglazona, 'ASSERVIMENTI'::text as nome_vincolo, 'ASSERVIMENTI'::text as nome_tavola, asservimento||'_'||gid as nome_zona, the_geom, 250::int as ordvincolo, 250::int as ordtavola,250::int as ordzona
	FROM asservimenti.asservimenti)
)	
	";

$queryString="select view_vincoli.descvincolo,view_vincoli.norma,view_vincoli.desctavola,view_vincoli.desczona,view_vincoli.siglazona,view_vincoli.nome_vincolo,view_vincoli.nome_tavola,view_vincoli.nome_zona,sezione,foglio,mappale,round(sum(st_area(st_intersection(particelle.".THE_GEOM.",view_vincoli.the_geom))/st_area (particelle.".THE_GEOM.")*100)::numeric,1) as perc_area
	from nct.particelle,$viewVincoli as view_vincoli
	where particelle.$layerUniqueField in ($resultIdList)
	and (st_area(st_intersection (particelle.".THE_GEOM.",view_vincoli.the_geom))>10 or st_area(st_intersection(particelle.".THE_GEOM.",view_vincoli.the_geom))/st_area (particelle.".THE_GEOM.")>=0.02)
	group by particelle.".THE_GEOM.",1,2,3,4,5,6,7,8,9,10,11,ordvincolo,ordtavola,ordzona order by ordvincolo,ordtavola,ordzona,perc_area desc;";
	//print "<p>$queryString</p>";
	$db->sql_query ($queryString);
	while($row = $db->sql_fetchrow()){
		$sezione=$row["sezione"];
		$foglio=$row["foglio"];
		$mappale=$row["mappale"];
                $mapkey=$sezione.'#'.$foglio.'#'.$mappale.'#';
		$aVincoli[$mapkey][$row["nome_vincolo"]]["DESCRIZIONE"]=$row["descvincolo"];
		$aVincoli[$mapkey][$row["nome_vincolo"]]["NORMA"]=$row["norma"];
		$aVincoli[$mapkey][$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["DESCRIZIONE"]=$row["desctavola"];
		$aVincoli[$mapkey][$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["ZONE"][$row["nome_zona"]]["DESCRIZIONE"]=$row["desczona"];
		$aVincoli[$mapkey][$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["ZONE"][$row["nome_zona"]]["SIGLA"]=$row["siglazona"];
		$aVincoli[$mapkey][$row["nome_vincolo"]]["TAVOLE"][$row["nome_tavola"]]["ZONE"][$row["nome_zona"]]["PERCAREA"]=$row["perc_area"];
	}	
	//print_array($db->sql_fetchrowset());
	//print_array($aVincoli);//exit;	

$queryString="SELECT distinct particelle.sezione, particelle.foglio, particelle.mappale, avvioproc.pratica::text, avvioproc.numero, coalesce(to_char(avvioproc.data_presentazione,'dd-mm-YYYY'),'s.d.') as data_presentazione, coalesce(e_tipopratica.nome,'n.a.') AS tipo_pratica, coalesce(avvioproc.oggetto,'n.a.') as oggetto, anno
FROM nct.particelle
join pe.asservimenti_map on upper(asservimenti_map.sezione::text) = upper(particelle.sezione::text) AND asservimenti_map.foglio::text = particelle.foglio::text AND asservimenti_map.mappale::text = particelle.mappale::text
join pe.avvioproc using (pratica)
join pe.e_tipopratica on (avvioproc.tipo=e_tipopratica.id)
where particelle.$layerUniqueField in ($resultIdList)
order by anno desc, data_presentazione desc;";


$db->sql_query ($queryString);

	while($row = $db->sql_fetchrow()){
		$aAssPrat[$row["pratica"]]["SEZIONE"]=$row["sezione"];
		$aAssPrat[$row["pratica"]]["FOGLIO"]=$row["foglio"];
		$aAssPrat[$row["pratica"]]["MAPPALE"]=$row["mappale"];
		$aAssPrat[$row["pratica"]]["NUMERO"]=$row["numero"];
		$aAssPrat[$row["pratica"]]["DATA_PRESENTAZIONE"]=$row["data_presentazione"];
		$aAssPrat[$row["pratica"]]["TIPO_PRATICA"]=$row["tipo_pratica"];
		$aAssPrat[$row["pratica"]]["OGGETTO"]=$row["oggetto"];
		$aAssPrat[$row["pratica"]]["PRATICA"]=$row["pratica"];
	};
	
// SOGGETTI	
$queryString="select distinct
asservimenti_map.pratica, soggetti.id, upper(coalesce(soggetti.cognome,''))||' '||initcap(coalesce(soggetti.nome,''))||coalesce(' '||ragsoc,'') as soggetto
from nct.particelle
join pe.asservimenti_map on upper(asservimenti_map.sezione::text) = upper(particelle.sezione::text) AND asservimenti_map.foglio::text = particelle.foglio::text AND asservimenti_map.mappale::text = particelle.mappale::text
join pe.soggetti on asservimenti_map.pratica=soggetti.pratica
where particelle.$layerUniqueField in ($resultIdList)
and richiedente=1
order by 2";

$db->sql_query ($queryString);


while($row = $db->sql_fetchrow()){
$aAssPrat[$row["pratica"]]["SOGGETTI"].=$row["soggetto"]."<br>";
};

	
	
$queryString="select distinct
asservimenti_map.pratica, pareri.id, case when e_enti.nome is null then '' when e_enti.nome='COMMISSIONE PAESAGGIO' then 'C.P.' when e_enti.nome='COMMISSIONE EDILIZIA' then 'C.E.' when e_enti.nome='COMMISSIONE UFFICIO' then 'C.U.' else initcap(e_enti.nome) end ||' '|| to_char(pareri.data_ril,'dd-mm-YYYY') || coalesce(' n. '||pareri.numero_doc,'') as commissione, pareri.data_ril
from nct.particelle
join pe.asservimenti_map on upper(asservimenti_map.sezione::text) = upper(particelle.sezione::text) AND asservimenti_map.foglio::text = particelle.foglio::text AND asservimenti_map.mappale::text = particelle.mappale::text
join pe.pareri on asservimenti_map.pratica=pareri.pratica
join pe.e_enti on pareri.ente=e_enti.id
where
particelle.$layerUniqueField in ($resultIdList)
and pareri.ente in (2,8,18,23,32,36,37,38)
order by pareri.data_ril desc";

$db->sql_query ($queryString);
while($row = $db->sql_fetchrow()){
$aAssPrat[$row["pratica"]]["PARERI"].=$row["commissione"]."<br>";
//$aVincoli[$mapkey]["PRATICHE_ASSERVIMENTO"][$row["pratica"]]["PARERI"][$row["id"]]=$row["commissione"];
};

	
	
	
	
	
//print "<p>$queryString</p>";
//print_array($db);
//print_array($aAssPrat);//exit;
	
	
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

			BODY.info_particella DIV.titolo
				{ font-family:arial; font-size:26px; text-align:center; font-weight:bold; }

			BODY.info_particella .sottotitolo
				{ font-family:arial; font-size:22px; text-align:center; font-weight:bold; color:rgb(53,84,186) }

			BODY.info_particella .note
				{ font-family:arial; font-size:16px; text-align:center; font-weight:bold; color:red }
				
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
				
			BODY.info_particella .evidenziato
				{ color:red; }
				
			BODY.info_particella .pulsante
				{ font-family:arial; font-size:14px; color:gray; margin-top:24px; margin-bottom:10px; margin-right:10px; cursor:hand; cursor:pointer; width:65px; height:20px; border:1px solid rgb(180,180,180); background-color:rgb(245,245,245); padding:2px; text-align:center; float:right; }
		</style>
	</head>
	<body class="info_particella">
	 	<div class="titolo">
	 		COMUNE DI IMPERIA
		<br>
			<span class="sottotitolo">
			Scheda Urbanistica della Particella
			</span>
		<br>
			<span class="note">
			Avvertenze: la scheda ha valore puramente indicativo
			</span>	
		<br>
		</div>
		<?php			
                    foreach ($aVincoli as $key => $tavole)
                        {
                   $v=explode('#',$key);
                   $sezione=$v[0];
                   $foglio=$v[1];
                   $mappale=$v[2];
                 ?>   
		<div class="mappale" style="margin-top:20px;">
			<?php if($sezione)print("Sezione:  <span class=\"evidenziato\">$sezione</span>");?>
			Foglio:  <span class="evidenziato"><?php echo $foglio ?></span>
			Mappale: <span class="evidenziato"><?php echo $mappale ?></span>
		</div>
                    <?php
			foreach ($tavole as $key0 => $value0)
				{
                echo("<table class=\"tabella\" border=\"0\">");
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
                        };			
		
		
		
		
		//PRATICHE DI ASSERVIMENTO
		
		if ($aAssPrat)
		{
		
		
			echo("<table class=\"tabella\" border=\"0\" style=\"margin-top:60px\">");
			
			echo("<td colspan=\"6\" class=\"vincolo\" style=\"color:rgb(53,186,84);\">");
					echo("Pratiche di asservimento");
					echo("</td>");
			
			echo("<tr style=\"color:gray; font-size:12px\"><td>N. prat.</td><td>Richiedenti</td><td>Data presentazione</td><td>Tipo prat.</td><td>Oggetto</td><td>Commissione</td></tr>");
			foreach($aAssPrat as $key => $pratiche)
				{
				
				echo("<tr class=\"zona\">");
				echo("<td valign=\"top\">");
				echo("<a href=\"http://server13/praticaweb/praticaweb.php?pratica=$pratiche[PRATICA]\" target=\"new\" style=\"font-weight:bold; color:rgb(53,186,84);\">");
				echo($pratiche[NUMERO]);
				echo("</a>");
				echo("</td>");
				echo("<td valign=\"top\">");
				echo($pratiche[SOGGETTI]);
				echo("</td>");
				echo("<td valign=\"top\">");
				echo($pratiche[DATA_PRESENTAZIONE]);
				echo("</td>");
				echo("<td valign=\"top\">");
				echo($pratiche[TIPO_PRATICA]);
				echo("</td>");
				
				echo("<td valign=\"top\">");
				echo($pratiche[OGGETTO]);
				echo("</td>");
				echo("<td valign=\"top\">");
				echo($pratiche[PARERI]);
				echo("</td>");
				echo("</tr>");
				
				};
			echo("</table>");
		};
		
		?>
		<div onclick="window.close()" class="pulsante">Chiudi</div>
		<div onclick="window.print()" class="pulsante">Stampa</div>
		
	</body>
</html>
