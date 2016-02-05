GisClient.Redline = new Class({
	
	initialize: function(objDiv,app) {   
	
		this.owner = app;
		//NON SONO RIUSCITO A PASSARLI VIA CSS
		this.annotationDiv = new Element('div',{'id':'annotationDiv','styles':{'position':'absolute','left':110,'top':220,'width':300,'height':150,'border':'2px solid #000000','visibility':'hidden','background-color':'#dddddd'}});
		this.annotationTxt = new Element('textarea',{'id':'annotationTxt','styles':{'margin-left':5,'width':275,'height':100}});
		var annotationSave = new Element('input',{
			'id':'annotationSave',
			'type':'button',
			'value':GC_LABEL["Insert"],
			'styles':{'margin-top':2,'margin-left':170,'width':60,'height':25}
		});
		annotationSave.addEvent('click',function(){this.add()}.bindWithEvent(this));
		var annotationCancel = new Element('input',{
			'id':'annotationCancel',
			'type':'button',
			'value':GC_LABEL["Cancel"],
			'styles':{'margin-top':2,'margin-left':5,'width':60,'height':25}
		});
		annotationCancel.addEvent('click',function(){this.cancel()}.bindWithEvent(this));
		this.annotationDiv.appendChild(this.annotationTxt);
		this.annotationDiv.appendChild(annotationSave);
		this.annotationDiv.appendChild(annotationCancel);
		objDiv.appendChild(this.annotationDiv);

	},
	
	addText: function(geom){
		this.geom = geom;
		var x = geom.X[geom.X.length-1];
		var y = geom.Y[geom.Y.length-1];
		this.annotationDiv.setStyles({'visibility':'visible','left':x,'top':y-50});
		this.annotationTxt.focus();
		
	},
	
	add: function(geom){
		
		if(geom) this.geom = geom;
		if(this.geom.X.length<2){
			alert("Elemento non valido");
			return;
		}
		this.owner.setBusy(true);	
		var param = {'mapset':this.owner.mapset,'action':'redline','imageWidth':this.owner.map.getWidth(),'imageHeight':this.owner.map.getHeight(),'geopixel':this.owner.geoPixel,'Xgeo':this.owner.oXgeo,'Ygeo':this.owner.oYgeo,'imgX':this.geom.X,'imgY':this.geom.Y,'imgT':this.annotationTxt.get('value')};	
		this.owner.setBusy(true);	
		this.owner.post(param);
		this.cancel();
	
	},
	
	remove: function(){
		var param = {'mapset':this.owner.mapset,'action':'redline','imageWidth':this.owner.map.getWidth(),'imageHeight':this.owner.map.getHeight(),'remove':1};	
		this.owner.setBusy(true);
		this.owner.post(param);
	},
	
	cancel:function(){
		this.annotationTxt.set('value','');
		this.annotationDiv.setStyle('visibility','hidden');
		this.owner.map.clear();
	}

});