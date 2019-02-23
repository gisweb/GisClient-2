window.Author = {

	BaseUrl:"./js/", 
	//BaseUrl:"/", 
	VERSION:"0.1",
	
	
	LoadLibs: function (){
		var jslibs =  new Array(
			"../../jslib/jxlib",
			"md5",
			"administrator",
			"elenco",
			"window1"
			);
			
		

		var languageCode = navigator.language ? navigator.language.substring(0,2):navigator.userLanguage.substring(0,2);
		var s = window.location.search.toLowerCase();
		var idx = s.indexOf('language=');
		if (idx>0) languageCode = s.substring(idx+9,idx+11);
		
			
		var langPath = this.BaseUrl + "lang/";
			
		for(i=0;i<jslibs.length;i++)
			document.write("<script type=\"text/javascript\" src=\"" + this.BaseUrl + "" + jslibs[i] + ".js\"></script>");
		
		var ua = navigator.userAgent.toLowerCase();
		var isIE6 = ua.indexOf("msie 6") > -1, isIE7 = ua.indexOf("msie 7") > -1;
		//if(isIE6)
			//document.write("<link rel=\"StyleSheet\" media=\"screen\" href=\"../../css/ie6.css\" type=\"text/css\" />");
		//if(isIE7)
			//document.write("<link rel=\"StyleSheet\" media=\"screen\" href=\"../../css/ie7.css\" type=\"text/css\" />");
	}
};

// carico le librerie 
Author.LoadLibs();
