<html>
	<head>
		<title>Piano Regolatore Generale</title>
		<style>
			BODY.cdu DIV.normativa { font-family:arial; font-size:11pt; text-align:justify;  }
			BODY.cdu DIV.normativa DIV.articolo { font-weight:bold; margin-top:24pt; margin-bottom:4pt; font-size:14pt; }
			BODY.cdu DIV.normativa OL, BODY.cdu DIV.normativa UL { margin-top:0cm; margin-bottom:0cm; }
		</style>
	</head>
	<body class="cdu">
		<?
			$connessione=pg_connect("host=localhost port=5432 dbname=gw_sori user=Admin password=bebo");
			$sql="select * from cdu.normativa where nome_normativa='PRG' order by ordine";
			$query=pg_query($connessione,$sql);
			echo("<div class=\"normativa\">");
			while($array=pg_fetch_assoc($query))
				{
				echo("<!--");
				echo('<div style="height:120px; background-color:navy; color:white; font-weight:bold; font-size:110px; text-align:center">ID ');
				echo($array['id']);
				echo('</div>');
				echo("-->");
				echo($array['normativa']);
				};
			echo("</div>");
		?>
	</body>
</html>