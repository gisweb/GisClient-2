<html>
	<head>
		<title>Piano Regolatore Generale</title>
		<style>
			BODY.cdu DIV.normativa { text-align:justify; font-size:10pt; font-family:arial; padding:12pt; }
			BODY.cdu DIV.normativa DIV.articolo { margin-top:12pt; font-size:11pt; font-weight:bold; margin-bottom:2pt; }
			BODY.cdu DIV.normativa DIV.titolo { margin-top:24pt; font-size:14pt; font-weight:bold; margin-bottom:6pt; }
			BODY.cdu DIV.normativa UL { margin-top:2pt; margin-bottom:2pt; }
			BODY.cdu DIV.normativa OL { margin-top:2pt; margin-bottom:2pt; }
			BODY.cdu DIV.normativa TABLE { font-size:10pt; border-collapse:collapse; }
			BODY.cdu DIV.normativa TABLE TD { padding-right:6px; }
			BODY.cdu DIV.normativa TABLE.comprensorio TD { border:1px solid black; padding:4px; }
		</style>
	</head>
	<body class="cdu">
		<?
			$connessione=pg_connect("host=localhost port=5432 dbname=gw_carasco user=Admin password=bebo");
			$sql="select * from cdu.normativa where nome_normativa='PIANO_DI_FABBRICAZIONE' order by ordine";
			$query=pg_query($connessione,$sql);
			echo("<div class=\"normativa\">");
			while($array=pg_fetch_assoc($query))
				{
				echo($array['normativa']);
				};
			echo("</div>");
		?>
	</body>
</html>