function openMap(mapsetid,template,parameters){
	//var baseURL='/gisclient2/';
	var baseURL='/';
	var defaultTemplate='gisclient';
	
	if(!template) template = defaultTemplate;
	var winWidth = window.screen.availWidth-8;
	var winHeight = window.screen.availHeight-55;
	var winName = 'mapset_'+mapsetid;
	template="template/" + template;
	if(!parameters) parameters='';
	if(template.indexOf('?')>0)
		template=template + '&';
	else
		template=template + '?';

	var mywin=window.open(baseURL + template + "mapset=" + mapsetid + '&' + parameters, winName,"width=" + winWidth + ",height=" + winHeight + ",menubar=no,toolbar=no,scrollbar=auto,location=no,resizable=yes,top=0,left=0,status=yes");
	mywin.focus();
}