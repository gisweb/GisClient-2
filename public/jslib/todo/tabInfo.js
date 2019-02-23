//Scrive la tabella (passare il css come parametro???)
function setTabInfo(result,mapset){
	var infoTable='';
	var flag=false;
	if (result.length > 0){
		for(i=0;i<result.length;i++){//query template
			var qtRes = result[i];//Oggetto risultato della query
			var aHeaders = qtRes.tableheaders;
			var aValues = qtRes.data;
			var aFieldType = qtRes.fieldtype;
			var aObjid = qtRes.objid;
			var aExtent = qtRes.extent;
			var aResultExtent = qtRes.resultextent;
			var aLink = qtRes.link;
			var color = '';
			if(qtRes.color)
				color = "<span style=\"width:7px;height:7px;border:1px solid black;background-color:rgb(" + qtRes.color + ");\">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;";

			if(aValues && aHeaders && aHeaders.length>0 && aValues.length>0){
	
				flag=true;
				infoTable += "<div class=\"intestazione\">" +color + qtRes.title + "</div>";
				for(row=0;row < aValues.length;row++){//Record
					infoTable += "<table cellpadding=\"0\" cellspacing=\"0\" border=\"1\" class=\"tabinfo\">";	

					for(col=0;col<aValues[row].length;col++){//campi 
						infoTable += writeTablerow(qtRes.qtid,aHeaders[col],aFieldType[col],aValues[row][col],aObjid[row]);
					}
					infoTable += "</table>";
					//link per ogni oggetto
					if(aLink){
						//var param="?mapset=" + ms.mapset + "&layer=" + qtRes.layer + "&objid=" + qtRes.objid[row];
						for(lnk=0;lnk<aLink.length;lnk++){
							var param=(aLink[lnk][0].indexOf('?')>0)?("&mapset=" + mapset + "&grpid=" +  qtRes.grpid + "&layer=" +  qtRes.layer + "&objid=" + aObjid[row]):("?mapset=" + mapset + "&grpid=" +  qtRes.grpid + "&layer=" + qtRes.layer + "&objid=" + aObjid[row]);
							infoTable +="<div class=\"barretta_ultima\" style=\"text-align:left\"><a href=\"javascript:OpenPopup('" + aLink[lnk][0] + param + "','" + aLink[lnk][1] + "')\"><img src=\"" + GisClient.BaseUrl + "images/info.gif\" border=\"0\" width=\"12\" height=\"12\"/>&nbsp;" + aLink[lnk][1] + "</a></div>";	
						}
					}
					if(qtRes.editurl && myMap.editTool)
						infoTable +="<div class=\"barretta_ultima\" style=\"text-align:left;height:30\"><a href=\"javascript:openEditUrl('" + qtRes.editurl + "','" + qtRes.key + '=' + aObjid[row] + '&layer=' + qtRes.layer + '&qt=' + qtRes.qtid + '&grpid=' + qtRes.grpid + "')\"><img src=\"" + GisClient.BaseUrl + "images/editinfo.gif\" border=\"0\" width=\"16\" height=\"16\"/>&nbsp;"+GC_LABEL['editData']+"</a></div>";
					if(qtRes.layer)
						infoTable += "<div class=\"barretta_ultima\" style=\"text-align:left\"><a href=\"javascript:myMap.zoomResult([" + aExtent[row] + "],[" + qtRes.color + "],[" + aObjid[row] + "],'" + qtRes.layer + "','" + qtRes.key + "'," + qtRes.staticlayer + "," + qtRes.qtid + "," + qtRes.grpid +")\"><img src=\"" + GisClient.BaseUrl + "images/select-zoom.png\" border=\"0\" width=\"18\" height=\"18\" />&nbsp;"+GC_LABEL['FocusOnObject']+"</a></div>";
				}
				var sNumPage='';
				if(qtRes.numpages > 1){
					var maxPages=50;
					for(k=1;k<Math.min(qtRes.numpages+1,maxPages);k++){
						if(k==qtRes.pageindex)
							sNumPage += "<a style=\"font-weight:bold;color:#ff0000\" href=\"javascript:myMap.nextResultPage("+k+","+qtRes.numrows+",1)\"> "+k+" </a>";
						else
							sNumPage += "<a href=\"javascript:myMap.nextResultPage("+k+","+qtRes.numrows+",1)\"> "+k+" </a>";
					}
					var numObj = GC_LABEL['ObjectCount'].replace(/\{1\}/, qtRes.maxrows).replace(/\{2\}/, qtRes.numrows);
				}
				else
					var numObj =  GC_LABEL['TotalObjectsNr'].replace(/\{1\}/, qtRes.numrows);
				if(qtRes.layer)	
					infoTable += "<div class=\"barretta_ultima\" style=\"text-align:left\"><a href=\"javascript:myMap.zoomResult([" + aResultExtent + "],[" + qtRes.color + "],[" + aObjid + "],'" + qtRes.layer + "','" + qtRes.key + "'," + qtRes.staticlayer + "," + qtRes.qtid + "," + qtRes.grpid + ")\"><img src=\"" + GisClient.BaseUrl + "images/select-zoom.png\" border=\"0\" width=\"18\" height=\"18\" /> " + numObj + "</a></div>";
				infoTable +="<div class=\"barretta\">";
				infoTable +="<a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+qtRes.qtid+")\" ><img src=\""+GisClient.BaseUrl + "images/database_table.png\" />&nbsp;"+GC_LABEL['Table']+"</a>&nbsp;&nbsp;";
				//infoTable +="<a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+qtRes.qtid+",'pdf')\" ><img src=\""+GisClient.BaseUrl + "images/acrobat.gif\" />&nbsp;PDF</a>&nbsp;&nbsp;";
				//infoTable +="<a style=\"text-decoration:none\" href=\"javascript:myMap.printTable("+qtRes.qtid+",'xls')\" ><img src=\""+GisClient.BaseUrl + "images/xls.gif\" />&nbsp;XLS</a></div>";

				if(aLink){	
					for(lnk=0;lnk<aLink.length;lnk++){
						var param=(aLink[lnk][0].indexOf('?')>0)?("&mapset=" + mapset + "&grpid=" +  qtRes.grpid + "&layer=" +  qtRes.layer + "&objid=" + aObjid):("?mapset=" + mapset + "&grpid=" +  qtRes.grpid + "&layer=" + qtRes.layer + "&objid=" + aObjid);
						infoTable +="<div class=\"barretta_ultima\" style=\"text-align:left\"><a href=\"javascript:OpenPopup('" + aLink[lnk][0] + param + "','" + aLink[lnk][1] + "')\"><img src=\"" + GisClient.BaseUrl + "images/info.gif\" border=\"0\" width=\"12\" height=\"12\"/>&nbsp;" + aLink[lnk][1] + "</a></div>";	
					}
				}
				infoTable +="<div class=\"barretta_ultima\" style=\"text-align:left\">" +sNumPage+"</div>";
			}
				
		}

	}
	
	if(!flag)
		infoTable="<div class=\"intestazione\">"+GC_LABEL['noInformation']+"</div>";
		
	return infoTable;
}

function writeTablerow(qt,header,ftype,val,aObjId){

	var htmlrow = "<tr><td>&nbsp;</td></tr>";

	switch (ftype)
	{
		case 1: //Testo
			htmlrow = "<tr><td class=\"colonna1\">" + header + "</td><td class=\"colonna2\">" + val + "</td></tr>";//da vedere se aggiungere qualche css
			break 
		case 10: //raggruppamento
			htmlrow = "<tr><td class=\"colonna1\" >" + header + "</td><td class=\"colonna2\">" + val + "</td></tr>";//da vedere se aggiungere qualche css
			break 	
		case 2: //Collegamento
			if($type(val)=='string' && val.indexOf('javascript')!=-1)
				htmlrow = "<tr><td class=\"colonna1\">Link</td><td class=\"colonna2\"><a href=\"#\" onclick=\"" + val + "\">" + header + "</a></td></tr>";
			else if (val)
				htmlrow = "<tr><td class=\"colonna1\">Link</td><td class=\"colonna2\"><a href=\"" + val + "\" target=\"_new\">" + header + "</a></td></tr>";
			else
				htmlrow = "<tr><td class=\"colonna1\">Link</td><td class=\"colonna2\"></td></tr>";
			break
		case 3: //email
			htmlrow = "<tr><td class=\"colonna1\">" + header + "</td><td class=\"colonna2\"><a href=\"mailto:" + val + "\">" + val + "</a></td></tr>";
			break
		case 8: //immagine
			htmlrow = "<tr><td colspan=2 class=\"colonna1\">" + header + "</td></tr>";
			htmlrow += "<tr><td colspan=2 class=\"colonna2\">";
			if(val) htmlrow += "<img src=\"" + val + "\"  />";
			htmlrow += "</td></tr>";
			break
		case 44: //immagine
			yy = "<img src=\"" + infoValue + "\" width=30 />" + infoValue;
			break
		case 55: //documento locale
			yy = "<a href=\"" + infoValue + "\" target=\"_new\">" + infoValue + "</a>";
			break
	
		case 99://collegamento a tabella secondaria
			if (val){
				htmlrow = "<tr><td colspan=\"2\" class=\"colonna2\"><img src=\"" + GisClient.BaseUrl + "images/table.gif\">&nbsp;<a href=\"javascript:myMap.sendQuery({qt:"+qt+",relation:"+val+",idList:[" + aObjId +"]})\">" + header + "</a></td></tr>";
			}
		break;
	}
	
	return htmlrow;


}

function openLink(linkPage,winName,winW,winH){
/*		
	var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
		
	if (!regexp.test(linkPage))
		var mywin = window.open(linkPage, '', 'width=' + winW + ',height=' + winH + ',status=no,resizable=yes,scrollbars=yes');
	else
		var mywin = window.open(linkPage, '', 'width=' + winW + ',height=' + winH + ',status=no,resizable=yes,scrollbars=yes');
	if(mywin) setTimeout("mywin.focus()",100);
	*/
}

function openEditUrl(editurl,objkey){	
	var s = (editurl.indexOf('?')==-1)?'?':'&';
	if(objkey) 
		s += 'language=' + GisClient.language + '&mode=edit&' + objkey;
	else
		s += 'language=' + GisClient.language + '&mode=new' 
	OpenPopup(editurl + s, 'editPage');
}
