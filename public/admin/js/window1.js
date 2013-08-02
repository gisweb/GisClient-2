function loadwindow(url,width,height){

	$("dwindow").style.display=''
	$("dwindow").style.width=initialwidth=width+"px"
	$("dwindow").style.height=initialheight=height+"px"
	$("dwindow").style.left="145px"
	$("dwindow").style.top="135px"
	$("cframe").src=url
	window.top

}

function NewWindow(url, winname, winwidth, winheight, scroll) {
	
	if (!winwidth)
		  winwidth =screen.availWidth-10;
	if (!winheight)
		  winheight = screen.availHeight-35;
	winprops = 'height='+winheight+',width='+winwidth+',scrollbars='+scroll+',menubar=no,top=0,status=no,left=0,screenX=0,screenY=0,resizable,close=no';
	
	
	win = window.open(url, winname, winprops)
	if (parseInt(navigator.appVersion) >= 4) { 
		win.window.focus(); 
	}
}

/*------------------------------------------------------------------------------------- LIBRERIA DI FUNZIONI PER LA FINESTRA ------------------------------------------------------------------------------------------------------*/

function get_azioni(obj_name,strParam){
	if (is_array(strParam))
		var prm=strParam;
	else
		var prm=strParam.split(',');
	
	var level=$('prm_livello');
	var project=$('project');
	
	for(i=0;i<prm.length;i++){
		var val=$(prm[i]);
		if (val) prm[i]='id='+val.value;
	}
	
	var param=(prm.length==0)?(''):('&'+prm.join('&'));
	loadwindow('action.php?action='+obj_name+'&level='+level.value+'&project='+project.value+param,600,600);

}
function get_elenco(txt_campo,dat){
	if (txt_campo.indexOf('.')>0){
		tmp=txt_campo.split('.');
		campo=tmp[0];
	}
	else{
		campo=txt_campo;
	}
	var param=new Array();
	if(isArray(dat)){
		var dato=dat;
	}
	else if (dat.length>0)
		var dato=dat.split('@');
	else
		var dato=new Array();
		
	for(i=0;i<dato.length;i++){
		var val=$(dato[i]);

		if (val){
			param.push(dato[i]+"="+val.value);
		}
	}
	var fk_fld=$('fk_'+txt_campo);
	var fld=$(txt_campo);
	var s=(fk_fld)?(fk_fld.value):((fld)?(fld.value):(''));
	var url='elenco.php?campo=' + campo + '&s='+s+'&'+param.join("&");
	var dim=(campo='wms')?(new Array(500,500)):(new Array(300,400));
	loadwindow(url,dim[0],dim[1]);
}

function get_wms(txt_campo,dat){
	
	if (txt_campo.indexOf('.')>0){
		tmp=txt_campo.split('.');
		campo=tmp[0];
	}
	else{
		campo=txt_campo;
	}
	var wms_data=$('metadata').value;
	var regexp=/^\"([A-z]+)\"([\s]{1})\"(.*)\"/;
	var row=wms_data.split('\n');
	var param=new Array();
	for(i=0;i<row.length;i++){
		
		var tmp=row[i].split(regexp);
		if(tmp.length>3)
			param.push('wmsInfo['+tmp[1]+']='+tmp[3]);
	}
	var fileConfig=dat;
	var url='elenco.wms.php?campo=' + campo + '&config='+ fileConfig +'&'+param.join("&");
	
	loadwindow(url,700,600);
}