<html> 
<head> 
<title>Color</title> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"> 
<style type="text/css"> 
body { 
font-size: 7pt; 
font-family: "verdana"; 
SCROLLBAR-FACE-COLOR: #FFFFFF; 
SCROLLBAR-HIGHLIGHT-COLOR: #FFFFFF; 
SCROLLBAR-SHADOW-COLOR: #FFFFFF; 
SCROLLBAR-3D-LIGHT-COLOR: #FFFFFF; 
SCROLLBAR-ARROW-COLOR: #FFFFFF; 
SCROLLBAR-TRACK-COLOR: #FFFFFF; 
SCROLLBAR-DARK-SHADOW-COLOR: #FFFFFF; 
SCROLLBAR-BORDER: 0px; 
} 
td { 
font-size: 11pt; 
font-family: "verdana"; 
} 
a { 
font-size: 11pt; 
font-family: "verdana"; 
} 
input { 
font-size: 7pt; 
font-family: "verdana"; 
} 
.color{
	cursor:hand;
	cursor:pointer;
}
</style> 
<script language="JavaScript"> 
function setColor(color) { 
document.form.color_hex.value = "#"+color; 
document.form.color.style.backgroundColor = "#"+color; 
} 
</script> 
</head> 
<body bgcolor="" leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0"> 
<?php 
// color cell fill 
$fill = "&nbsp;&nbsp;&nbsp;"; 
$col_r = 0; // red color 
$col_g = 0; // green color 
$col_b = 0; // blue color 

$row_return = 0; 
$block_return = 0; 

echo "<table border='1' cellspacing='0' cellpadding='0' align='center'> 
<tr>"; 

while($col_r <= 255) { 
	$col_g = 0; 

	echo "<td class='color'>"; 
	$block_return++; 

	while($col_g <= 255) { 
		$col_b = 0; 
		$arr_color=Array();
		while($col_b <= 255) { 
			$red = strtoupper(dechex($col_r)); 
			$green = strtoupper(dechex($col_g)); 
			$blue = strtoupper(dechex($col_b)); 
			$color = str_pad($red, 2, '0', STR_PAD_LEFT)."".str_pad($green, 2, '0', STR_PAD_LEFT)."".str_pad($blue, 2, '0', STR_PAD_LEFT); 
			//$arr_color[]="<td class='color'> <a onMouseDown=\"setColor('$color')\" style=\"background: #$color;\">$fill;</a></td> ";
			?> 
			<td class="color"> <a onMouseDown="setColor('<?php echo $color ?>')" style="cursor: hand; background: #<?php echo $color ?>;"><?php echo $fill; ?></a></td> 
			<?php 
			// Form the row of 6 colors... 
			$row_return++; 
			if($row_return==6) { 
				echo "<br>"; 
				$row_return = 0; 
			} 
			$col_b+=51; // decrement for more colors, but be carefull, there are over 16 Million colors 
		} 
		$col_g+=51; // decrement for more colors, but be carefull, there are over 16 Million colors 
	} 
	$col_r+=51; // decrement for more colors, but be carefull, there are over 16 Million colors 

	// deal with the end rows in order to display the colors in a table... 
	if($block_return == 3) { 
		echo "</td></tr><tr>"; 
	}
	else { 
		echo "</td>"; 
	} 
} 
echo "</tr></table>"; // end row & table 


// Display the Grey colors 
$col = 16; 
echo "<div align=\"center\">"; 
echo "<h4>Greyscale</h4>"; 
while($col <= 255) { 
$red = strtoupper(dechex($col)); 
$green = strtoupper(dechex($col)); 
$blue = strtoupper(dechex($col)); 
$color = str_pad($red, 2, '0', STR_PAD_LEFT)."".str_pad($green, 2, '0', STR_PAD_LEFT)."".str_pad($blue, 2, '0', STR_PAD_LEFT); 
?> 
<a onMouseDown="setColor('<?php echo $color ?>')" style="cursor: hand; background: #<?php echo $color ?>;"><?php echo $fill; ?></a> 
<?php 
$col +=16; 
} 
echo "</div>"; 
?> 
<center> 
<form name="form"> 
Hex Value: 
<input type="text" name="color_hex" size="10"><br> 
<textarea cols="40" rows="10" name="color" style="background: white; border: 0;" disabled></textarea> 
</form> 
</center> 

</body> 
</html> 
