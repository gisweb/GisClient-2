<?php
/*
$prova=Array
(
    "PTCP" => Array
        (
            "DESCRIZIONE" => 'Piano Territoriale di Coordinamento Paesistico',
            "NORMA" => '',
            "TAVOLE" => Array
                (
                    "GEOMORFOLOGICO" => Array
                        (
                            "DESCRIZIONE" => 'Assetto geomorfologico',
                            "ZONE" => Array
                                (
                                    "MO_B" => Array
                                        (
                                            "DESCRIZIONE" => 'Modificabilità di tipo B',
                                            "SIGLA" => 'MO-B',
                                            "PERCAREA" => '100.0'
                                        )
                                )
                        ),
                    "VEGETAZIONALE" => Array
                        (
                            "DESCRIZIONE" => 'Assetto vegetazionale',
                            "ZONE" => Array
                                (
                                    "COL_ISSMA" => Array
                                        (
                                            "DESCRIZIONE" => 'colture, insediamenti sparsi di serre - Mantenimento',
                                            "SIGLA" => 'COL-ISS MA',
                                            "PERCAREA" => '97.2'
                                        ),

                                    "BATCO" => Array
                                        (
                                            "DESCRIZIONE" => 'Bosco di angiosperme termofile - Consolidamento',
                                            "SIGLA" => 'BAT CO',
                                            "PERCAREA" => '2.8'
                                        )
                                )
                        ),
                    "INSEDIATIVO" => Array
                        (
                            "DESCRIZIONE" => 'Assetto insediativo',
                            "ZONE" => Array
                                (
                                    "ISMA" => Array
                                        (
                                            "DESCRIZIONE" => 'Insediamento sparso - Mantenimento',
                                            "SIGLA" => 'IS MA',
                                            "PERCAREA" => '90.2'
                                        ),
                                    "ISMO_B" => Array
                                        (
                                            "DESCRIZIONE" => 'Insediamento sparso - Modificabilità di tipo B',
                                            "SIGLA" => 'IS MO-B',
                                            "PERCAREA" => '9.8'
                                        )
                                )
                        )
                )
        ),
    "PRG" => Array
        (
            "DESCRIZIONE" => 'Piano Regolatore Generale',
            "NORMA" => '',
            "TAVOLE" => Array
                (
                    "TESSUTI" => Array
                        (
                            "DESCRIZIONE" => 'Tessuti',
                            "ZONE" => Array
                                (
                                    "TESS_7_10" => Array
                                        (
                                            "DESCRIZIONE" => 'Tessuto 7/10',
                                            "SIGLA" => '7/10',
                                            "PERCAREA" => '90.8'
                                        )
                                )
                        ),
                    "ASSETTO_AMBIENTALE" => Array
                        (
                            "DESCRIZIONE" => 'Assetto ambientale',
                            "ZONE" => Array
                                (
                                    "BATCO" => Array
                                        (
                                            "DESCRIZIONE" => 'Bosco di angiosperme termofile (BAT)',
                                            "SIGLA" => 'BAT - CO',
                                            "PERCAREA" => '9.2'
                                        )
                                )
                        ),
                    "FABBRICATI" => Array
                        (
                            "DESCRIZIONE" => 'Fabbricati',
                            "ZONE" => Array
                                (
                                    "FABBRICATI" => Array
                                        (
                                            "DESCRIZIONE" => 'Fabbricato',
                                            "SIGLA" => 'Fabbricato',
                                            "PERCAREA" => '0.2'
                                        )
                                )
                        )
                )
        ),
    "VINCOLI_TERRITORIALI" => Array
        (
            "DESCRIZIONE" => 'Vincoli territoriali',
            "NORMA" => '',
            "TAVOLE" => Array
                (
                    "IDROGEOLOGICO" => Array
                        (
                            "DESCRIZIONE" => 'Vincolo idrogeologico (R.D. 3267/23)',
                            "ZONE" => Array
                                (
                                    "IDROGEOLOGICO" => Array
                                        (
                                            "DESCRIZIONE" => 'Area vincolata',
                                            "SIGLA" => 'Vinc. Idrogeo.',
                                            "PERCAREA" => '100.0'
                                        )
                                )
                        ),
                    "DLGS_42_2004_ART_142" => Array
                        (
                            "DESCRIZIONE" => 'Vincoli ex art. 142, Dlgs 42/2004',
                            "ZONE" => Array
                                (
                                    "RISPETTO_ACQUE" => Array
                                        (
                                            "DESCRIZIONE" => 'Territorio compreso nella fascia di 150 m. da corso d\'acqua pubblico',
                                            "SIGLA" => 'Risp. acque',
                                            "PERCAREA" => '13.6'
                                        ),
                                    "BOSCHI" => Array
                                        (
                                            "DESCRIZIONE" => 'Aree boscate ex D.Lgs. 42/2004',
                                            "SIGLA" => 'Area boscata',
                                            "PERCAREA" => '9.2'
                                        )
                                )
                        )
                )
        )
);
*/
session_start();
define('AREA_MIN','5');//area minima di intersezione per le query di overlay
define('THE_GEOM','bordo_cs');
require("../../gisclient/report/report_common.php");//COMUNE A TUTTI I REPORT

if ($layerFilter) $layerFilter=" and " .$layerFilter;

$viewVincoli="(select vincolo.descrizione as descvincolo,vincolo.norma,tavola.descrizione as desctavola,zona.descrizione as desczona,zona.sigla as siglazona,zona.nome_vincolo,zona.nome_tavola,zona.nome_zona,zona_plg.the_geom,vincolo.ordine as ordvincolo,tavola.ordine as ordtavola,zona.ordine as ordzona from vincoli.zona_plg inner join vincoli.zona using(nome_vincolo,nome_tavola,nome_zona) inner join vincoli.tavola using (nome_vincolo,nome_tavola) inner join vincoli.vincolo using (nome_vincolo) where nome_vincolo not in ('PUC','PRG') and zona_plg.nome_vincolo not in ('FOGNATURA','RETE_IDRICA') and not (zona_plg.nome_vincolo='VINCOLI_TERRITORIALI' and zona_plg.nome_tavola in ('CORPI_IDRICI','SERVITU_MILITARE')))";

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
				
			BODY.info_particella .evidenziato
				{ color:red; }
				
			BODY.info_particella .pulsante
				{ font-family:arial; font-size:14px; color:gray; margin-top:24px; margin-bottom:10px; margin-right:10px; cursor:hand; cursor:pointer; width:65px; height:20px; border:1px solid rgb(180,180,180); background-color:rgb(245,245,245); padding:2px; text-align:center; float:right; }
		</style>
	</head>
	<body class="info_particella">
		<div class="mappale">
			<?php if($sezione)print("Sezione:  <span class=\"evidenziato\">$sezione</span>");?>
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
		?>
		<div onclick="window.close()" class="pulsante">Chiudi</div>
		<div onclick="window.print()" class="pulsante">Stampa</div>
		
	</body>
</html>
