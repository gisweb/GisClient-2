var currentResult; //Da rifare con oggetti
var objList;
var isEdit=false;

function setPageInfo(result,mapset){
	var infoTable='';
	var flag=false;

	objList = new Array();

	if(result.length>0){
		for(var i=0;i<result.length;i++){//query template
						
			currentResult = result[i];			
			if(currentResult.tableheaders && currentResult.numrows>0){
				flag=true;
				var selcolor = '';
				if(currentResult.color)
					selcolor = "<span style=\"width:7px;height:7px;border:1px solid black;background-color:rgb(" + currentResult.color + ");\">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;";
		
				//Intestazione (titolo del modello di ricerca)
				infoTable += "<div style=\"width:100%\" class=\"intestazione\">" + selcolor + currentResult.title + "</div>";
				infoTable +="<div class=\"barretta\"><a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+currentResult.qtid+",'pdf','"+currentResult.parentid+"',"+currentResult.relation+")\" ><img src=\""+GisClient.BaseUrl + "images/acrobat.gif\" />&nbsp;PDF</a>&nbsp;&nbsp;<a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+currentResult.qtid+",'xls','"+currentResult.parentid+"',"+currentResult.relation+")\" ><img src=\""+GisClient.BaseUrl + "images/xls.gif\" />&nbsp;XLS</a></div>";
				infoTable += writeTableData(currentResult,0,mapset);
				infoTable +="<div class=\"barretta\"><a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+currentResult.qtid+",'pdf','"+currentResult.parentid+"',"+currentResult.relation+")\" ><img src=\""+GisClient.BaseUrl + "images/acrobat.gif\" />&nbsp;PDF</a>&nbsp;&nbsp;<a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+currentResult.qtid+",'xls','"+currentResult.parentid+"',"+currentResult.relation+")\" ><img src=\""+GisClient.BaseUrl + "images/xls.gif\" />&nbsp;XLS</a></div>";

				
				var sNumPage='';
				var maxPages=50;
				if(currentResult.numpages > 1){
					for(k=1;k<Math.min(currentResult.numpages+1,maxPages);k++){
						if(k==currentResult.pageindex)
							sNumPage += "<a style=\"font-weight:bold;color:#ff0000\" href=\"javascript:myMap.nextResultPage("+k+","+currentResult.numrows+",2)\"> "+k+" </a>";
						else
							sNumPage += "<a href=\"javascript:myMap.nextResultPage("+k+","+currentResult.numrows+",2)\"> "+k+" </a>";
					}
					infoTable +="<div class=\"barretta\">Totale Oggetti " +  currentResult.maxrows + " su " + currentResult.numrows + "  " + sNumPage + "</div>" 
					
					
				}
				else
					infoTable +="<div class=\"barretta\">Totale Oggetti " +  currentResult.numrows + "</div>" 
				
				if(currentResult.graph)
					infoTable +="<div class=\"barretta\"><a href=\"javascript:OpenPopup('/cgi-bin/R.cgi/test.R?filename="+currentResult.r_file+"','Grafici')\" >Apri Grafici</a></div>" 
			}
			
		}
	}
	
	if(!flag)
		infoTable="<div class=\"intestazione\">Nessuna informazione</div>";
		
	return infoTable;
}

function writeTableData(result,index,mapset){
var htmlTable;

	if(result.group){	//intestazione di gruppo (+ eventuale risultati aggregati) 

		for (var key in result.group) {
			//alert(key + ' => ' + result.group[key].groupdata);
			
			var title = currentResult.tableheaders[index] + ": "+key;
			if(!htmlTable) htmlTable='';
			htmlTable += "<div style=\"font-weight:bold;margin-left:"+(index*10)+"px;\">"+title+"</div>"; 
			//if (keyIndex==0){//Prendo solo la prima chiave -> tabella dei dati
				if(currentResult.groupheaders) htmlTable += writeGroupTableData(result.group[key].groupdata,index+1);
				htmlTable += writeTableData(result.group[key],index+1);
				
			//}
			//else {//seonda chiave groupdata -> tabella con le funzioni di aggregazione
				
				
			//}
			
			
		}
	}
	else{//Livello di tabella
	//if(true){//Livello di tabella
	
				//********************************************************************************************
				//Patch per l'editing su secondaria: cerco un campo che si chiama ID-EDIT e lo uso come chiave
				//TODO: AGGIUNGERE IL TIPO DI CAMPO CHIAVE ESTERNA IN AUTHOR X EDITING

				htmlTable='';
				var rowCount=0;
				if(index>0) htmlTable="<span style=\"font-weight:bold;\">Dettaglio:</span>"
				htmlTable +="<table cellpadding=\"0\" cellspacing=\"0\" border=\"1\" class=\"tabinfo\"  style=\"width:100%\"  >";
				//htmlTable += "<tr><td colspan=\"" + (result.tableheaders.length + 2) + "\" class=\"intestazione\" style=\"width:100%\" >" + recolor + qtRes.title + "</td></tr>";
				if(currentResult.istable){//tabella secondaria non metto la lente
					tablerow = "";
				}
				else
					tablerow = "<td class=\"colonna1\" style=\"width:5%\">&nbsp;</td>";
					
				var fieldcheck = false;
				var w='';
				for(var k=index;k<currentResult.tableheaders.length;k++){
					if($chk(currentResult.columnwidth)) w = currentResult.columnwidth[k]?'width=' + currentResult.columnwidth[k] + '%':'';
					tablerow += "<td class=\"colonna1\" " + w + " >" + currentResult.tableheaders[k] + "</td>";
					if(currentResult.fieldtype[k]<10)fieldcheck = true;
				}
				//se non ci sono campi in tabella dettaglio esco
				if (!fieldcheck) return '';
				htmlTable +="<tr>" + tablerow + "</tr>";//table headers
				
				var aLink = result.link;
				for(var row=0;row < result.data.length;row++){//Record	
					//rowCount++;
					if(currentResult.istable){//tabella secondaria non metto la lente
						tablerow = "";
					}
					else if(result.extent) {
						tablerow = "<td align=\"center\" class=\"colonna1\" width=\"5\"><a href=\"javascript:myMap.zoomResult([" + result.extent[row] + "],[" + currentResult.color + "],[" + result.objid[row] + "],'" + currentResult.layer + "','" + currentResult.key + "'," + currentResult.staticlayer + "," + currentResult.qtid + "," + currentResult.grpid + ")\"><img src=\"" + GisClient.BaseUrl + "images/select-zoom.png\" border=\"0\" width=\"18\" height=\"18\" /></a>";
						//link per ogni oggetto
						if(aLink) {
							for(lnk=0;lnk<aLink.length;lnk++){
								var param = (aLink[lnk][0].indexOf('?')>0) ? "&" : "?";
								param += "mapset=" + mapset + "&grpid=" + result.grpid + "&layer=" +  result.layer + "&objid=" + result.objid[row];
								tablerow += "&nbsp;<a href=\"javascript:OpenPopup('" + aLink[lnk][0] + param + "','" + aLink[lnk][1] + "')\"><img src=\"" + GisClient.BaseUrl + "images/info.gif\" border=\"0\" width=\"15\" height=\"15\" alt=\""+aLink[lnk][1]+"\" title=\""+aLink[lnk][1]+"\" style=\"margin-bottom:3px;\"/></a>";
							}
						}
						tablerow += "</td>";
					}
					
					if (objList.indexOf(parseInt(result.objid[row]))<0) objList.push(parseInt(result.objid[row]));
					var offset = currentResult.fieldtype.length - result.data[row].length
					for(col=0;col<result.data[row].length;col++){//campi 
						var idxCol = result.data[row].length - col;
						if(myMap.editTool && currentResult.tableheaders[offset + col]=='ID-EDIT'){
							tablerow += writeTableField(99,"'" + currentResult.editurl + "','" + currentResult.key + '=' + result.objid[row] + '&layer=' + currentResult.layer + '&qt=' + currentResult.qtid + '&grpid=' + currentResult.grpid + '&id_edit=' + result.data[row][col] + "'");
							isEdit = true;
						}
						else
							tablerow += writeTableField(currentResult.fieldtype[offset + col],result.data[row][col]);
					}
					htmlTable +="<tr>" + tablerow + "</tr>";//table row	
				}
				htmlTable +="</table>";
				htmlTable = "<div style=\"margin-left:"+(index*10)+"px;\">"+htmlTable+"</div>";
				if(isEdit)	htmlTable +="<div class=\"barretta\" style=\"height:25px\"><a href=\"javascript:openEditUrl('" + currentResult.editurl + "','" + currentResult.key + '=' + result.objid[result.data.length-1] + '&layer=' + currentResult.layer + '&qt=' + currentResult.qtid + '&grpid=' + currentResult.grpid + "&id_edit=0')\"><img src=\"" + GisClient.BaseUrl + "images/editinfo.gif\" border=\"0\" width=\"18\" height=\"18\" /> Nuovo record</a></div>";


	}
	
return htmlTable;

}

function writeTableField(ftype,val){

	switch (ftype)
	{
		case 1: //Testo
			htmlcell = "<td class=\"colonna2\">" + val + "</td>";//da vedere se aggiungere qualche css
			break 
		case 10: //raggruppamento
			htmlcell = "<td class=\"colonna2\">" + val + "</td>";//da vedere se aggiungere qualche css
			break 	
		case 2: //Collegamento
			if($type(val)=='string' && val.indexOf('javascript')!=-1)
				htmlcell = "<td class=\"colonna2\"><a href=\"" + val + "\" >Link</a></td>";
			else if (val)
				htmlcell = "<td class=\"colonna2\"><a href=\"" + val + "\" target=\"_new\">Link</a></td>";
			else
				htmlcell = "<td class=\"colonna2\"></td>";
			break
		case 3: //email
			htmlcell = "<td class=\"colonna2\"><a href=\"mailto:" + val + "\">" + val + "</a></td>";
			break
		case 8: //immagine
			htmlcell = "<td class=\"colonna2\"><img src=\""  + val + "\" width=\"80\" /></td>";
			break
		case 55: //documento locale
			yy = "<a href=\"" + infoValue + "\" target=\"_new\">" + infoValue + "</a>";
			break
			
		case 99://Edit secondaria TODO							
			htmlcell = "<td align=\"center\" class=\"colonna2\" width=\"5\"><a href=\"javascript:openEditUrl(" + val + ")\"><img src=\"" + GisClient.BaseUrl + "images/editinfo.gif\" border=\"0\" width=\"18\" height=\"18\" /></a></td>";
			break;
		case 999://collegamento a tabella secondaria
			if (val){
				htmlcell = "<td class=\"colonna2\"><a href=\"javascript:ms.getInfo('" + val +"')\">" + val + "</a></td>";
			}
			break;
		default:
				htmlcell = "<td class=\"colonna2\">" + val + "</td>";
			break;
	}
	
	return htmlcell;


}


function writeGroupTableData_table(groupData,index){

	var tablerow='';
	htmlTable="<table cellpadding=\"0\" cellspacing=\"0\" border=\"1\" class=\"tabinfo\"  style=\"width:100%\"  >";
	for(var i=0;i<currentResult.groupheaders.length;i++){
		var w = currentResult.groupcolumnwidth[i]?'width=' + currentResult.groupcolumnwidth[i] + '%':'';
		tablerow += "<th class=\"colonna1\" " + w + " >" + currentResult.groupheaders[i] + "</th>";
	}
	htmlTable +="<tr>" + tablerow + "</tr>";//table headers		
	tablerow='';
	for(var col=0;col < groupData.length;col++)//Campi con funzioni di  aggregazione	
		tablerow += "<td class=\"colonna2\">" + groupData[col] + "</td>";
	htmlTable +="<tr>" + tablerow + "</tr>";//table row	
	htmlTable +="</table>";
	htmlTable = "<div style=\"margin-left:"+(index*10)+"px;\">"+htmlTable+"</div>";
	return htmlTable;

}

function writeGroupTableData(groupData,index){

	var tablerow='';
	htmlTable="";
	for(var i=0;i<currentResult.groupheaders.length;i++){
		htmlTable += "<p>" + currentResult.groupheaders[i] + ": " + groupData[i] + "</p>";
	}
	htmlTable = "<div style=\"margin-left:"+(index*10)+"px;\">"+htmlTable+"</div>";
	return htmlTable;

}
