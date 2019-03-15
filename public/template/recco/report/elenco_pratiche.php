<?php
session_start();

require("report_common.php");//COMUNE A TUTTI I REPORT

/*
		$sql="select distinct pratica from nct.particelle,pe.cterreni 
			where coalesce(particelle.sezione,'')=coalesce(cterreni.sezione,'') and particelle.foglio=cterreni.foglio and particelle.mappale=cterreni.mappale and particelle.gid=$resultIdList;";
		$db->sql_query ($sql);
		if (!$db->sql_numrows()) //Nessun Risultato provo a cercare su catasto urbano
			{
			$sql="select distinct pratica from nct.particelle,pe.curbano 
			where coalesce(particelle.sezione,'')=coalesce(curbano.sezione,'') and particelle.foglio=curbano.foglio and particelle.mappale=curbano.mappale and particelle.gid=$resultIdList;";
			$db->sql_query ($sql);
			}
*/
		$sql="(select distinct pratica from nct.particelle,pe.cterreni 
			where coalesce(particelle.sezione,'')=coalesce(cterreni.sezione,'') and particelle.foglio=cterreni.foglio and particelle.mappale=cterreni.mappale and particelle.gid=$resultIdList)";
		$sql.=" union ";
		$sql.="(select distinct pratica from nct.particelle,pe.curbano 
			where coalesce(particelle.sezione,'')=coalesce(curbano.sezione,'') and particelle.foglio=curbano.foglio and particelle.mappale=curbano.mappale and particelle.gid=$resultIdList)";

		$db->sql_query($sql);

		
		$idp=$db->sql_fetchrowset(); 
		$numidp = $db->sql_numrows();
			// CICLO SU PRATICHE TROVATE
				// CONTROLLO CHE PRATICA SIA DI TIPO GIUSTO 2000/2100/10000/10200/11000/11220
			
			for($i=0;$i<count($idp);$i++) {
				$sql="SELECT tipo FROM pe.avvioproc WHERE pratica=".$idp[$i]["pratica"].";";
				

				$db->sql_query ($sql);
				$nrec = $db->sql_numrows();
				$tipop = $db->sql_fetchrow();
				
				switch ($tipop[0]){
					case '2000':
					case '2100': 
					case '11000': 
					case '11220': 	
						$sql="SELECT case when(coalesce(data_rilascio::varchar,'')='') then 0 else 1 end as rilasciato FROM pe.titolo WHERE pratica=".$idp[$i]["pratica"].";";					
					break;
					
					case '10000': 
					case '10200':  
						$sql="SELECT case when(coalesce(data_in_val::varchar,'')='') then 0 else 1 end as rilasciato FROM pe.infodia WHERE pratica=".$idp[$i]["pratica"].";";
					break;
				}
				
				$db->sql_query ($sql);
				$nrec = $db->sql_numrows();
				$data = $db->sql_fetchrow();
				if($data[0]==1) $temp[]=$idp[$i];
				
			}
			
			//print_r($idp);

		for($i=0;$i<$numidp;$i++){
			$idpratica=$idp[$i]['pratica'];
			$sql="select * from pe.elenco_pratiche where pratica=$idpratica";
			$db->sql_query ($sql);
			$nrec = $db->sql_numrows();
			$dati_pratica = $db->sql_fetchrowset();
			
			$sql="select * from pe.elenco_soggetti where pratica=$idpratica and (richiedente=1 or progettista=1)";
			$db->sql_query ($sql);
			$nrec = $db->sql_numrows();
			$soggetti = $db->sql_fetchrowset();
			
			for($j=0;$j<$nrec;$j++){
				if($soggetti[$j]["richiedente"]==1){
					if($nomi_richiedenti) $nomi_richiedenti.=" - ";
					$nomi_richiedenti.=$soggetti[$j]["soggetto"];
				}
			}
			for($j=0;$j<$nrec;$j++){
				if($soggetti[$j]["progettista"]==1){
					if($nomi_progettisti) $nomi_progettisti.=" - ";
					$nomi_progettisti.=$soggetti[$j]["soggetto"];
				}
			}
				
			$sql="select * from pe.indirizzi where pratica=$idpratica";
			$db->sql_query ($sql);
			$nrec = $db->sql_numrows();
			$indirizzi = $db->sql_fetchrowset();
			
			for($j=0;$j<$nrec;$j++){					
				if($elenco_indirizzi) $elenco_indirizzi.=" - ";	
				$elenco_indirizzi.=$indirizzi[$j]["via"]." ".$indirizzi[$j]["civico"];
			}
				
			$sql="select distinct * from pe.cterreni where pratica=$idpratica";
			$db->sql_query ($sql);
			$nrec = $db->sql_numrows();
			$terreni = $db->sql_fetchrowset();
			$elenco_terreni="<table border=\"0\" class=\"catasto\">";
			$elenco_terreni.="<tr><td class=\"intestazione\">Foglio</td><td class=\"intestazione\">Mappale</td></tr>";
			for($j=0;$j<$nrec;$j++){
				if($elenco_terreni) $elenco_terreni.=" ";
				$elenco_terreni.="<tr><td>".$terreni[$j]["foglio"]."</td><td>".$terreni[$j]["mappale"]."</td></tr>";
			};
			$elenco_terreni.="</table>";
				
			$url="/praticaweb.php?pratica=$idpratica";
			$titolo="<big><b>".$dati_pratica[0]["tipopratica"]."</big></b><br>Pratica n. <b style=\"color:rgb(53,84,186)\">".$dati_pratica[0]["numero"]."</b> del ".$dati_pratica[0]["data_presentazione"];
			if($dati_pratica["titolo"])
				$titolo.=" n. ".$dati_pratica[0]["titolo"]." del ".$dati_pratica[0]["data_rilascio"];
				
				
				
			//RISULTATI DELLA RICERCA
			//$elenco.="";
			//$elenco.="<div class=\"piano\">";
			if($_SESSION["USER_ID"]) $elenco.="<a href=\"javascript:NewWindow('$url','Praticaweb',0,0,'yes')\" class=\"intestazione_pratica\">$titolo</a>";
			else
				$elenco.="$titolo";
				
			$elenco.="<table class=\"tabella\">";
			$elenco.="<col style=\"font-weight:bold;\">";
			$elenco.="<tr><td valign=\"top\">Oggetto</td><td>".$dati_pratica[0]["oggetto"]."</td></tr>";
			$elenco.="<tr><td valign=\"top\">Richiedenti</td><td>$nomi_richiedenti</td></tr>";
			$elenco.="<tr><td valign=\"top\">Progettista</td><td>$nomi_progettisti</td></tr>";
			$elenco.="<tr><td valign=\"top\">Ubicazione</td><td>$elenco_indirizzi</td></tr>";
			$elenco.="<tr><td valign=\"top\">Catasto</td><td>$elenco_terreni</td></tr>";
			$elenco.="</table>";
			//$elenco.="</div>";
			unset($nomi_richiedenti);
			unset($nomi_progettisti);
			unset($elenco_indirizzi);
			unset($elenco_terreni);
		}
		
?>
<html>
	<head>
		<title>Pratiche</title>
		<style>
			BODY.elenco_pratiche
				{ margin-left:4px; margin-right:4px; margin-top:10px; margin-bottom:0px; background-color:rgb(255,255,255); }
				
			BODY.elenco_pratiche TABLE.tabella
				{ width:100%; font-family:arial; border-collapse:collapse; font-size:14px; }
				
			BODY.elenco_pratiche TABLE.tabella TD
				{ background-color:rgb(235,235,245); border-bottom:2px solid white; border-right:1px solid white; padding-top:2px; padding-bottom:2px; padding-left:2px; padding-right:5px; }
				
			BODY.elenco_pratiche TABLE.catasto
				{ font-family:arial; border-collapse:collapse; font-size:10px; }
				
			BODY.elenco_pratiche TABLE.catasto TD.intestazione
				{ font-size:10px; color:gray; padding-right:8px; }
				
			BODY.elenco_pratiche TABLE.tabella TD TABLE TR TD
				{ border-width:0px; padding:0px; }
				
			BODY.elenco_pratiche .pulsante
				{ font-family:arial; font-size:14px; color:gray; margin-top:24px; margin-bottom:10px; margin-right:10px; cursor:hand; cursor:pointer; width:65px; height:20px; border:1px solid rgb(180,180,180); background-color:rgb(245,245,245); padding:2px; text-align:center; float:right; }
				
			BODY.elenco_pratiche A.intestazione_pratica
				{ font-family:arial; color:rgb(0,0,0); text-decoration:none; padding-bottom:1px; padding-top:1px; display:block; margin-bottom:2px; background-color:rgb(205,205,215);}
				
			BODY.elenco_pratiche A:hover.intestazione_pratica
				{ background-color:rgb(170,210,255); }
		</style>
		<script src="/src/window.js"></script>
	</head>
	<body class="elenco_pratiche">
		<?php echo $elenco ?>
		<div onclick="window.close()" class="pulsante">Chiudi</div>
		<div onclick="window.print()" class="pulsante">Stampa</div>
	</body>
</html>