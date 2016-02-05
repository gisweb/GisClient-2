var winPopup;
var t = null;
  function OpenPopup(strUrl, strTarget, param)
  {
	  var windowWidth, windowHeight, windowLeft, windowTop;
	  if(typeof window.screenX == "number" && typeof window.innerWidth == "number")
	  {
		  windowWidth = window.innerWidth * .68;
		  windowHeight = window.innerHeight * .68;
		  windowLeft = window.screenX + window.innerWidth * .16;
		  windowTop = window.screenY + window.innerHeight * .16;
	  }
	  else if(typeof window.screenTop == "number" && typeof document.documentElement.offsetHeight == "number")
	  {
		  windowWidth = document.documentElement.offsetWidth * .68;
		  windowHeight = document.documentElement.offsetHeight * .68;
		  windowLeft = window.screenLeft + document.documentElement.offsetWidth * .16;
		  windowTop = window.screenTop - 50;
	  }
	  else
	  {
		  windowWidth = 500;
		  windowHeight = 250;
		  windowLeft = 60;
		  windowTop = 40;
	  };

//alert("top=" + parseInt(windowTop) + ",left=" + parseInt(windowLeft) + ",width=" + parseInt(windowWidth) + ",height=" + parseInt(windowHeight) + ",status=no,resizable=yes,scrollbars=yes");

	  if(param){
			if(strUrl.indexOf('?')>0)
				strUrl=strUrl + '&' + param;
			else
				strUrl=strUrl + '?' + param;
	  }
	
	  winPopup = window.open(strUrl, strTarget.replace(/\W+/g, '_'), "top=" + parseInt(windowTop) + ",left=" + parseInt(windowLeft) + ",width=" + parseInt(windowWidth) + ",height=" + parseInt(windowHeight) + ",status=no,resizable=yes,scrollbars=yes");
if(winPopup)	  
t = setTimeout("winPopup.focus()",10);

  }
  
