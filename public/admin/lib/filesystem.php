<?php
//FUNZIONE CHE RESTITUISCE UN ARRAY CON TUTTI I FILE DELLA DIRECTORY
function elenco_file($p,$ext="",$fname=""){
	if($fname) $fname=str_replace("*","(.*)",str_replace(".",".",$fname));
	if (is_dir($p)) {
	    if ($dh = opendir($p)) {
	        while (($file = readdir($dh)) !== false) {
				if ($ext!="" && $fname!=""){
					if(eregi("^$fname$",strtoupper($file)) && eregi("\.$ext$",strtoupper($file))){
						$elenco[]=$file;
					}
				}
				elseif($fname){
					if(eregi("^$fname$",strtoupper($file))){
						$elenco[]=$file;
					}
				}
				elseif($ext){
					if(eregi("\.$ext$",strtoupper($file))){
						$elenco[]=$file;
					}
				}
				else{
					if (!is_dir($p."/".$file))
						$elenco[]=$file;
				}
	        }
	        closedir($dh);
		}
	}
	return $elenco;
}

function elenco_dir($p){
	if (is_dir($p)) {
	    if ($dh = opendir($p)) {
	        while (($file = readdir($dh)) !== false) {
				if (is_dir($p."/".$file) && !in_array($file,Array(".","..")))
					$elenco[]=$file;
			}
			closedir($dh);
		}
	}
	return $elenco;
}

function new_file_name($file){
	$arr=explode(".",$file);
	if (is_array($arr)){
		$ext=array_pop($arr);
		$ext=".".$ext;
		$filename=implode(".",$arr);
	}
	else{
		$ext="";
		$filename=$file;		
	}
	$index="";
	$i=0;
	while(file_exists($filename.$index.$ext)){
		$i++;
		$index=".$i";
	}
	return $filename.$index.$ext;
		
}
?>