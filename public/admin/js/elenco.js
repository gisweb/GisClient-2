/*------------------------------------------------------------------------------------------ LIBRERIA DI FUNZIONI PER LA FINESTRA ELENCO -------------------------------------------------------------------------------------------*/
function closeWin(){
	parent.$("dwindow").style.display="none";
	parent.$("cframe").src="";
}
function nav(arrDir){
	var f=$("frmNavigate");
	if(is_array(arrDir))
	for(i=0;i<arrDir.length;i++){
		if(arrDir[i]){
			f.appendChild(new Element('input',{'type':'hidden','name':'dir['+i+']','value':+arrDir[i]}));
		}
	}
	f.submit();
}

function setdata(o){
	setControls(o);
	closeWin();
}
function set_font(val,obj_name){
	var obj=parent$(obj_name);
	
	if(obj){
		for(i=0;i<obj.options.length;i++){
			var opt=obj.options[i];
			if(opt.value==val){
				opt.selected=true;
				break;
			}
		}
	}
}

function nextWms(i){
	$('index').value=i;
	var frm=$('frm_data');
	frm.submit();
	
}
function setSrid(value){
	parent.$('data_srid').value=value;
}
function setWmsData(obj){
	parent.$(obj.campo).value=obj.value;
	parent.$('layer_name').value=obj.value;
	//parent.$('data_srid').value=obj.epsg;
	if(typeof(obj.style)=='undefined')
		str='"wms_name" "'+obj.wms_name+'"\n"wms_srs" "'+obj.wms_srs+'"\n"wms_server_version" "'+obj.wms_server_version+'"\n"wms_format" "'+obj.wms_format+'"\n"wms_formatlist" "'+obj.wms_formatlist+'"';
	else
		str='"wms_name" "'+obj.wms_name+'"\n"wms_srs" "'+obj.wms_srs+'"\n"wms_server_version" "'+obj.wms_server_version+'"\n"wms_style" "'+obj.wms_style+'"\n"wms_format" "'+obj.wms_format+'"\n"wms_formatlist" "'+obj.wms_formatlist+'"';
	parent.$('metadata').value=str;
	closeWin()
}
function setWfsData(obj){
	parent.$(obj.campo).value=obj.value;
	parent.$('layer_name').value=obj.value;
	parent.$('data_srid').value=obj.epsg;
	str='"wfs_typename" "'+obj.wfs_typename+'"\n"wfs_version" "'+obj.wfs_server_version+'"\n"wfs_latlongboundingbox" "'+obj.wfs_latlongboundingbox+'"';
	parent.$('metadata').value=str;
	closeWin()
}
function setWmsLayerGroup(obj){

	var lname=parent.$('layergroup_name');
	var ltitle=parent.$('layergroup_title');
	parent.$(obj.campo).value=obj.value;
	if (lname.value.length == 0) lname.value=obj.value;
	if (ltitle.value.length == 0) ltitle.value=obj.title;
	closeWin()
}
function setElencoFKey(obj){
	var objPKey=parent.$(obj.campo);
	var objFKey=parent.$(obj.fk_campo);
	objPKey.value=obj.pkey;
	objFKey.value=obj.fkey;
	if (obj.action) 
		eval(obj.action);
	closeWin()
}

function set_layerdata(tb,col,srid,pk,mode){
	if (mode==1){
		parent.$("data").value=tb;
		parent.$("data_geom").value=col;
		parent.$("data_unique").value=pk;
		parent.$("data_srid").value=srid;
		closeWin()
	}
	else{
		var frm=$("frm_data");
		$("table").value=tb;
		$("geom").value=col;
		$("srid").value=srid;
		frm.submit();
	}
}

/*------------------------------------------------------------------------------------- LIBRERIA DI FUNZIONI PER LA FINESTRA ELENCO WMS ---------------------------------------------------------------------------------------*/
function wmsSave(){
	var regexp=/dati\[(.+)\]/;
	var frm=$('frm_data');
	var wmsData=new Array();
	for(i=0;i<frm.elements.length;i++){
		var el=frm.elements[i];
		if (regexp.test(el.name) && el.value.length>0 ) 
			wmsData.push('"'+el.id+'" "'+el.value+'"');
	}
	parent.$('metadata').value=wmsData.join('\n');
	parent.window.focus();
	closeWin();
}



/*------------------------------------------------------------------------------------- LIBRERIA DI FUNZIONI PER LA FINESTRA ELENCO AZIONI ------------------------------------------------------------------------------------*/
