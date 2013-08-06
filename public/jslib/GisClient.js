window.GisClient = {

	BaseUrl:"/gisclient/", // if using https, put the full URL here as a hint for IE 
	Template:"gisclient", 
	VERSION:"2.7",
	gcEditGeometry:false,
	LanguageList:['en','it'],
	Language:navigator.language ? navigator.language.substring(0,2):navigator.userLanguage.substring(0,2),
	
	LoadLibs: function (){
		var jslibs =  new Array(
			"jxlib",
			"wz_jsgraphics",
			"vlaCal-v2.1",
			"vlaCal-v2.1-clientside-v1.0.1",
			"Autocompleter",
			"Observer",
			"Autocompleter.Request",
			"Autocompleter.Local",
			//"GisClient.Mapset.ToolbarV",
			"GisClient.Mapset",
			"GisClient.mapBox",
			"GisClient.Reference",
			"GisClient.Redline",
			// "GisClient.querybuilder",
			"winpopup",
			"coordinates",
			//"inlineeditor",
			//"OpenLayers",
			"jxchecktree",
			"todo/legendtree",
			"todo/tabInfo",
			"todo/pageInfo",
			"todo/selectOptions"
		);
		
		var s = window.location.search.toLowerCase();
		var idx = s.indexOf('language=');
		if (idx>0) this.Language = s.substring(idx+9,idx+11);
		
		var langPath = this.BaseUrl + "lang/";
		
		document.write("<script type=\"text/javascript\" src=\"" + langPath + this.Language + "/labels.js\"></script>");
		document.write("<script type=\"text/javascript\" src=\"" + langPath + this.Language + "/messages.js\"></script>");
			
		for(i=0;i<jslibs.length;i++)
			document.write("<script type=\"text/javascript\" src=\"" + this.BaseUrl + "jslib/" + jslibs[i] + ".js\"></script>");

	},
	
	LoadCss: function(){	
        var filecss =  new Array(
		"jxtheme",
		"jxCheckTree",
		"Autocompleter",
		"vlaCal-v2.1",
		"vlaCal-v2.1-adobe_cs3",
		"vlaCal-v2.1-apple_widget",
		"newcss",
		"result",
		"global-0.14");
		var ua = navigator.userAgent.toLowerCase();
		var isIE6 = ua.indexOf("msie 6") > -1, isIE7 = ua.indexOf("msie 7") > -1, isIE8 = ua.indexOf("msie 8") > -1;
		if(isIE6)
			document.write("<link rel=\"StyleSheet\" media=\"screen\" href=\"" + this.BaseUrl + "css/ie6.css\" type=\"text/css\" />");
		if(isIE7)
			document.write("<link rel=\"StyleSheet\" media=\"screen\" href=\"" + this.BaseUrl + "css/ie7.css\" type=\"text/css\" />");
		if(isIE8)
			document.write("<link rel=\"StyleSheet\" media=\"screen\" href=\"" + this.BaseUrl + "css/ie7.css\" type=\"text/css\" />");
		for(i=0;i<filecss.length;i++)
			document.write("<link rel=\"StyleSheet\" media=\"screen\" href=\"" + this.BaseUrl + "css/" + filecss[i] + ".css\" type=\"text/css\" />");
    },

	OpenMapset: function (mapsetid,template,parameters){
		if(!template) template = this.Template;
		var winWidth = window.screen.availWidth-8;
		var winHeight = window.screen.availHeight-55;
		var winName = 'mapset_'+mapsetid;
		template="template/" + template;
		if(!parameters) parameters='';
		if(template.indexOf('?')>0)
			template=template + '&';
		else
			template=template + '?';
		var mywin=window.open(this.BaseUrl + template + "mapset=" + mapsetid + "&" + parameters, winName,"width=" + winWidth + ",height=" + winHeight + ",menubar=no,toolbar=no,scrollbar=auto,location=no,resizable=yes,top=0,left=0,status=yes");
		mywin.focus();
	}
	
};
