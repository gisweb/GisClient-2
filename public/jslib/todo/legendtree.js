//TODO: REPLACE WITH MOOTOOLS  OBJECT OR RESPONSE HTML ?????????????????



var iconOffset=0;
function initLegendTree(anchor,legend,mapset){
	for(var i=0;i<legend.length;i++){//temi
		thId=legend[i][0];
		thTitle=legend[i][1];
		thLink=legend[i][2];
		grpLayer = legend[i][3];
		var grpDiv = groupLegend(grpLayer,thId,legendItem(thId,thTitle,'legendTheme',thLink),mapset);
		if(grpDiv){
			anchor.appendChild(grpDiv);
		}
	}
}

function groupLegend(grpLayer,themeid,themetitle,mapset){
	var grpContainer=new Element('div',{"id":themeid,'class':'legend'});
	grpContainer.appendChild(themetitle);
	var isempty = true;
	for(var i=0;i<grpLayer.length;i++){//layergroup
		grpId=grpLayer[i][0];
		grpTitle=grpLayer[i][2];
		grpLink=grpLayer[i][3];
		grpClass = grpLayer[i][4];
		nIcon = grpClass.length;			
		if(nIcon>0){
			isempty=false;
			var s = grpClass[0][0].toUpperCase() ;
			if(s.indexOf('HTTP')!=-1){//WMS LEGEND
				var grpObj = new Element('table',{"id":grpId,"border":"0","width":"90%","cellpadding":"4","cellspacing":"0",'styles':{'margin-left':'5px'}});
				var tbody = new Element('tbody');
				grpObj.appendChild(tbody);
				var trObj = new Element('tr');
				trObj.appendChild(treeImg('firstItem',true));
				trObj.appendChild(legendItem(0,grpTitle,'legendItem',grpLink,true,true));
				tbody.appendChild(trObj);
				for(var j=0;j<nIcon;j++){//layerwms in layergroup
					layerLegend=grpClass[j][0];
					layerLink=grpClass[j][1];
					var trObj = new Element('tr');
					/*
					if(j==nIcon-1)
						trObj.appendChild(treeImg('lastItem',true));
					else
						trObj.appendChild(treeImg('Item',true));
					*/
					trObj.appendChild(new Element('td'));
					var td = new Element('td');
					var img = new Element('img',{"alt":"WMS Legend","src": layerLegend});
					td.appendChild(img);
					trObj.appendChild(td);
					tbody.appendChild(trObj);
				}
				grpContainer.appendChild(grpObj);
			}	
			
			else if(nIcon==1){
				var grpObj = new Element('table',{"id":grpId,"class":"uniqueItem","border":"0","width":"100%","cellpadding":"4","cellspacing":"0"});
				var tbody = new Element('tbody');
				grpObj.appendChild(tbody);
				var trObj = new Element('tr');
				classTitle=grpClass[0][0];
				classLink=grpClass[0][1];
				trObj.appendChild(legendIcon(true,mapset));//sostituire il numero con un valore letto da css
				trObj.appendChild(legendItem(0,classTitle,'legendItem',classLink,true));
				tbody.appendChild(trObj);
				grpContainer.appendChild(grpObj);
			}
			
			else{
				var grpObj = new Element('table',{"id":grpId,"border":"0","width":"90%","cellpadding":"4","cellspacing":"0",'styles':{'margin-left':'5px'}});
				var tbody = new Element('tbody');
				grpObj.appendChild(tbody);
				var trObj = new Element('tr');
				trObj.appendChild(treeImg('firstItem',true));
				trObj.appendChild(legendItem(0,grpTitle,'legendItem',grpLink,true,true));
				tbody.appendChild(trObj);
				for(var j=0;j<nIcon;j++){//layergroup
					classTitle=grpClass[j][0];
					classLink=grpClass[j][1];
					var trObj = new Element('tr');
					if(j==nIcon-1)
						trObj.appendChild(treeImg('lastItem',true));
					else
						trObj.appendChild(treeImg('Item',true));
					trObj.appendChild(legendIcon(true,mapset));//sostituire il numero con un valore letto da css
					trObj.appendChild(legendItem(0,classTitle,'legendItem',classLink,true));
					tbody.appendChild(trObj);
				}
				grpContainer.appendChild(grpObj);
			}
		
		}
	}
	if(!isempty)
		return grpContainer;
	else
		return false;
}

function legendItem(id,title,classname,link,table,first){
	var img = new Element('a',{
		"id":id,
		"class":classname,
		"alt":classname,
		"href":link?'javascript:OpenPopup(\''+link+'\',\'legendInfo\')':null,
		"html": title});
	if(table){
		var opt={"align":"left"};
		if(first) opt={"colspan":"2","align":"left"};
		var td = new Element('td',opt);
		td.appendChild(img);
		return td;
	}else
		return img;

}
function legendIcon(table,mapsetName){
	var img = new Element('img',{
		styles:{"background-image":"url("+ GisClient.BaseUrl +"images/legend/"+mapsetName+".png)",
		"background-position":iconOffset + "px 0px"},
		"class": "legend",
		"alt":"Legend Icon",
		"src": GisClient.BaseUrl + "images/a_pixel.png"});
	iconOffset-=24;
	if(table){
		var td = new Element('td',{"width":"16"});
		td.appendChild(img);
		return td;
	}else
		return img;	
}
function treeImg(type,table){
	var img = new Element('img',{
			"class":type,
			"alt":"Tree Icon",
			"src": GisClient.BaseUrl + "images/a_pixel.png"});
	if(table){
		var td = new Element('td',{"width":"12",'class':type});
		td.appendChild(img);
		return td;
	}else
		return img;	
}	
//Casino rifare-----
function setLegendTree(anchor,groupsOn,groupsDisabled,thdisabled){

	var myItems = $(anchor).getElements('table');
	for (i=0;i<myItems.length;i++){
		myItems[i].setStyle('display','block');
		if(!groupsOn.contains(parseInt(myItems[i].id)) || groupsDisabled.contains(parseInt(myItems[i].id)))
			myItems[i].setStyle('display','none');
	}

	var myItems = $$('a[class=legendTheme]')
	for (i=0;i<myItems.length;i++){
		myItems[i].setStyle('display','block');
		if(thdisabled.contains(parseInt(myItems[i].id)))
			myItems[i].setStyle('display','none');
	}	
	
}
