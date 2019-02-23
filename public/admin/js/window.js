// JavaScript Document
//DHTML Window script- Copyright Dynamic Drive (http://www.dynamicdrive.com)
//For full source code, documentation, and terms of usage,
//Visit http://www.dynamicdrive.com/dynamicindex9/dhtmlwindow.htm

var dragapproved=false
var minrestore=0
var initialwidth,initialheight
var ie5=document.all&&document.getElementById
var ns6=document.getElementById&&!document.all

function iecompattest(){
return (document.compatMode!="BackCompat")? document.documentElement : document.body
}

function drag_drop(e){
if (ie5&&dragapproved&&event.button==1){
document.getElementById("dwindow").style.left=tempx+event.clientX-offsetx+"px"
document.getElementById("dwindow").style.top=tempy+event.clientY-offsety+"px"
}
else if (ns6&&dragapproved){
("dwindow").style.left=tempx+e.clientX-offsetx+"px"
document.getElementById("dwindow").style.top=tempy+e.clientY-offsety+"px"
}
}

function initializedrag(e){
offsetx=ie5? event.clientX : e.clientX
offsety=ie5? event.clientY : e.clientY
document.getElementById("dwindowcontent").style.display="none" //extra
tempx=parseInt(document.getElementById("dwindow").style.left)
tempy=parseInt(document.getElementById("dwindow").style.top)

dragapproved=true
document.getElementById("dwindow").onmousemove=drag_drop
}

function loadwindow(url,width,height){

if (!ie5&&!ns6)
window.open(url,"","width=width,height=height,scrollbars=1")
else{
document.getElementById("dwindow").style.display=''
document.getElementById("dwindow").style.width=initialwidth=width+"px"
document.getElementById("dwindow").style.height=initialheight=height+"px"
document.getElementById("dwindow").style.left="145px"
document.getElementById("dwindow").style.top="135px"
//document.getElementById("dwindow").style.top=ns6? window.pageYOffset*1+30+"px" : iecompattest().scrollTop*1+30+"px"
document.getElementById("cframe").src=url
window.top
}
}

function maximize(){
if (minrestore==0){
minrestore=1 //maximize window
document.getElementById("maxname").setAttribute("src","restore.gif")
document.getElementById("dwindow").style.width=ns6? window.innerWidth-20+"px" : iecompattest().clientWidth+"px"
document.getElementById("dwindow").style.height=ns6? window.innerHeight-20+"px" : iecompattest().clientHeight+"px"
}
else{
minrestore=0 //restore window
document.getElementById("maxname").setAttribute("src","max.gif")
document.getElementById("dwindow").style.width=initialwidth
document.getElementById("dwindow").style.height=initialheight
}
document.getElementById("dwindow").style.left=ns6? window.pageXOffset+"px" : iecompattest().scrollLeft+"px"
document.getElementById("dwindow").style.top=ns6? window.pageYOffset+"px" : iecompattest().scrollTop+"px"
}

function closeit(){
document.getElementById("cframe").src=""//se è lento caricare di base un file di attesa
document.getElementById("dwindow").style.display="none"
}

function stopdrag(){
dragapproved=false;
document.getElementById("dwindow").onmousemove=null;
document.getElementById("dwindowcontent").style.display="" //extra
}


function visibile(sezione, div_c, div_o){
	sezione.style.display = ''
	div_c.style.display = ''
	div_o.style.display = 'none'
}
function invisibile(sezione, div_c, div_o, hid_val){
	sezione.style.display = 'none'
	div_c.style.display = 'none'
	div_o.style.display = ''
}

function get_azioni(obj_name,strParam){
	//var id=xGetElementById(level).value;
	if (is_array(strParam))
		var prm=strParam;
	else
		var prm=strParam.split(',');
	
	var level=xGetElementById('prm_livello');
	var project=xGetElementById('project');
	
	for(i=0;i<prm.length;i++){
		var val=xGetElementById(prm[i]);
		if (val) prm[i]='id='+val.value;
	}
	
	var param=(prm.length==0)?(''):('&'+prm.join('&'));
	loadwindow('action.php?action='+obj_name+'&level='+level.value+'&project='+project.value+param,600,600);
	//loadwindow('action.php?level1_id='+level1_id+'&level1='+level1+'&level2_id='+level2_id+'&level2='+level2+'&level3_id='+level3_id+'&level3='+level3,400,400);
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
		var val=xGetElementById(dato[i]);

		if (val){
			param.push(dato[i]+"="+val.value);
		}
	}
	var fk_fld=xGetElementById('fk_'+txt_campo);
	var fld=xGetElementById(txt_campo);
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
	var wms_data=xGetElementById('metadata').value;
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

function get_file(txt_campo){
	loadwindow('carica_foto.php?campo=' + txt_campo + '&s=' + document.getElementById(txt_campo).value,600,300);
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



function link0(){
  var args = link0.arguments;
  var numargs = args.length;
  var key=args[0];
  var pratica=args[1];
  var target=args[2];
  switch(target) {
  
	case 'cn.scheda_documento'://dettaglio del documento allegato (args=id documento)
		window.location=target+'.php?id='+key+'&pratica='+pratica;
	break;
	
	case 'cn.integrazioni'://dettaglio richeste integrazioni e integrazioni documenti(args=id integrazione o richiesta)
		iter=args[3];
		nomeiter=args[4];
		window.location=target+'.php?id='+key+'&pratica='+pratica+'&iter='+iter+'&nomeiter='+nomeiter;
	break;
	
	
	
	
	
	
	
	
	
	
   }

}