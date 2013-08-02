<html>
<head>
   <LINK media="screen" href="css/styles.css" type="text/css" rel="stylesheet">
	<script  type="text/javascript" src="./js/Author.js"></script>
</head>
<body style="background-color:#FFFFFF" onload="document.getElementById('username').focus();">
<?php
session_destroy();
include (ADMIN_PATH."inc/inc.admin.page_header.php");
?>


<TABLE cellSpacing="0" cellPadding="0" width="100%" border="0">
  <TBODY>
<?php
	if(empty($enter))
		echo "<TR><TD style=\"text-align:right; background-color:#728bb8; border-top:6px solid #415578; padding:6 6 1 6px\" colspan=\"5\">&nbsp;</td></TR>";
?>  
  <TR>
	<td class="testo_login">
		
		Accesso consentito agli utenti autorizzati.
		
	</td>
	<TD colspan="3">
		<form action="<?php echo $_SERVER["PHP_SELF"]?>" method="post" class="riquadro" id="frm_enter" onsubmit="">
		  <table width="100%" border="0" align="center" cellpadding="4" cellspacing="0">
		      <td width="22%" class="label">Utente:</td>
			  <td width="78%"><input name="username" style="width:200px;" type="text" id="username" tabindex=1></td>
		    </tr>
		    <tr>
		      <td class="label">Password:</td> 
		      <td>
				<input name="password" type="password" style="width:200px;" id="password" tabindex=2>
				<input name="enc_password" type="hidden" id="enc_password" tabindex=2>
			  </td>
		    </tr>
		    <tr>
				<td></td>
		      <td align="right">
				<input name="login" type="hidden" value="1" tabindex=2>
				<input type="submit" name="azione" value="Entra" style="width:80" tabindex="3" onclick="return encript_pwd('password','frm_enter');">
			  </td>
		    </tr>
			<tr>
				<td colspan="2" align="center"><span class="message"><?php if (isset($message)) echo $message;?></span></td>
			</tr>
		  </table>
<?php
		if (isset($par)) echo $par;
?>
		</form>
	</TD>
  </TR>
  <TR><TD style="text-align:right; background-color:#728bb8; border-bottom:6px solid #415578; padding:6 6 1 6px" colspan="5">&nbsp;<!--<a href="http://www.gisweb.it" target="_blank"><img src="../images/logoblu.png" border="0"></a>--></td></TR>
  </TBODY> 
</TABLE>
<!--<div style="background-image:url(images/sfondo.png); background-repeat:repeat-x; width:100%; height:8"></div>-->
</body>
</html>
