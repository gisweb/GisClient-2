//MAPBOX TUTTO IN PIXEL (come unità di misura) 
//TODO SET CURSOR

GisClient.MapBox = new Class({   
       
    //implements   
    Implements: [Options,Events],   
  
    //options   //COPIARE ANCHE IN GISCLIENT
    options: {   
		'point_diameter':5,
        'color': '#00ff00',
		'thickness': 2,
		'color_move': 'red',
		'thickness_move': 2,
		'jitter': 4 // minimum size (in pixels_ of a mouse event resulting in a point. (default: 10).
    },   

       
    //initialization   
    initialize: function(mapDiv,app,options) {   
		this.setOptions(options);//set options otherwise default
		this.owner = app;
		this.element = mapDiv;	
		this.x1 = this.y1 = false;
		this.x = this.y = new Array(); // arrays to hold coordinates
		this.offsetx = this.element.getLeft();
		this.offsety = this.element.getTop();
		this.length = this.area = this.angle = 0;
		this.mode = ZOOM_BOX;
		this.setMisure = false;//blocca la stringa di misura
		
		//TEST GOOGLE MAP
		//this.Gmap = new GMap2(this.element);this.Gmap.setCenter(new GLatLng(44.1,9.82), 13);

		var elementSize = this.element.getSize();
		this.maps = new Element('div',{'id':'maps','styles': {'position':'absolute','left':'0px','top':'0px','width':elementSize.x,'height':elementSize.y}});
		this.element.appendChild(this.maps);
		
		//layer graphics 
		this.canvas = new Element('div',{'id':'canvas','styles': {'position':'absolute','left':'0px','top':'0px','width':elementSize.x,'height':elementSize.y}});
		this.graphics = new jsGraphics(this.canvas);
		this.graphics.setColor(this.options.color);
		this.graphics.setStroke(this.options.thickness);
		this.element.appendChild(this.canvas);
		
		//layer moving graphics 
		this.canvasMove = new Element('div',{'id':'canvas_move','styles': {'position':'absolute','left':'0px','top':'0px','width':elementSize.x,'height':elementSize.y}});
		this.graphicsMove = new jsGraphics(this.canvasMove);
		this.graphicsMove.setColor(this.options.color_move);
		this.graphicsMove.setStroke(this.options.thickness_move);	
		this.element.appendChild(this.canvasMove);
		
		//layer custom graphics 
		this.canvasCustom = new Element('div',{'id':'canvas_custom','styles': {'position':'absolute','left':'0px','top':'0px','width':elementSize.x,'height':elementSize.y}});
		this.graphicsCustom = new jsGraphics(this.canvasCustom);
		//this.graphicsCustom.setColor(this.options.color_Custom);
		//this.graphicsCustom.setStroke(this.options.thickness_custom);	
		this.element.appendChild(this.canvasCustom);
		
		
		this.element.addEvent('mousedown', this.start.bind(this));
		this.element.addEvent('mousemove', this.draw.bind(this));
		this.element.addEvent('mouseup', this.complete.bind(this));
		this.element.addEvent('mouseover', this.enter.bind(this));
		this.element.addEvent('mouseout', this.exit.bind(this));
		this.element.addEvent('dblclick', this.doubleclick.bind(this));		
		
		//if(Browser.Engine.trident5) 
		this.element.ondragstart = function() { return false; }; 
		
    },   
       
	//per le prove
    testgraphics: function() {   

		this.graphics.setColor("#00ff00"); // green
		this.graphics.drawRect(100, 400, 50, 80); // co-ordinates related to the document
		this.graphics.paint(); // draws, in this case, directly into the document

		this.graphicsMove.setColor("#0000ff"); // blue
		this.graphicsMove.drawRect(100, 100, 50, 80);
		this.graphicsMove.paint();

    },  
	//metodo chiamato su mousedown, this.dragging è un flag: true finchè l'utente "dragga" 
	start: function(event) {

		
		if(!$chk(event)) return;
		var e = new Event(event);
		
		var x1 = e.page.x - this.offsetx;	
		var y1 = e.page.y - this.offsety;
		

		
		if (this.mode == PICK_POINT) {
			this.x.push(x1);
			this.y.push(y1);

			
			//alert('click');
			return;
		
		}
		//flag 
		
		if (this.x.length == 0) this.clear();
		
		//Add first point
		this.x.push(x1);
		this.y.push(y1);
		
		
		this.dragging = true;
		
		if (this.mode != ZOOM_OUT && this.mode != ZOOM_BOX && this.mode != PAN){
			this.graphics.fillEllipse(x1 - this.options.point_diameter/2, y1 - this.options.point_diameter/2, this.options.point_diameter, this.options.point_diameter);
			this.graphics.paint();
			this.setMisure = true;
		}

	},
	//Metodo che esegue una operazione durante il drag in funzione di mapBox.mode
	draw:  function(event) {
		
		if(!$chk(event)) return;
		var e = new Event(event);
		
		var x1 = this.x[this.x.length - 1];
		var y1 = this.y[this.y.length - 1];		
		var x2 = e.page.x - this.offsetx;
		var y2 = e.page.y - this.offsety;
		
		if(this.dragging){
		
			if (this.mode == ZOOM_BOX) {
			
				w = Math.abs(x1 - x2);
				h = Math.abs(y1 - y2);
				x = Math.min(x1, x2);
				y = Math.min(y1, y2);
				this.graphicsMove.clear();
				this.graphicsMove.drawRect(x, y, w, h);
				this.graphicsMove.paint();
			}
		
			else if (this.mode == PAN) {
				this.moveTo(x2 - x1,y2 - y1);			
			}
		
			else if (this.mode == DRAW_POINT){
				var angle = Math.round((Math.PI + Math.atan2(x2 - x1, y2 - y1))/Math.PI*180.0);
				if (parseInt(angle)==360) angle=0;
				this.graphicsMove.clear();
				this.graphicsMove.drawLine(x1, y1, x2, y2);
				this.graphicsMove.drawLine(x1 + y2 - y1, y1 - x2 + x1, x1 - y2 + y1, y1 + x2 - x1); //linee di costruzione
				this.graphicsMove.paint();
				this.angle = angle;
			}
			
			else if (this.mode == DRAW_BOX) {
				w = Math.abs(x1 - x2);
				h = Math.abs(y1 - y2);
				x = Math.min(x1, x2);
				y = Math.min(y1, y2);
				this.area = w*h;
				this.graphics.clear();
				this.graphics.drawRect(x, y, w, h);
				this.graphics.paint();
			} 
			
			else if (this.mode == DRAW_CIRCLE){
				var r = this.getDistance(x1, y1, x2, y2 );
				var d = 2 * r;
				var angle = Math.atan2(y1 - y2, x2 - x1);
				var xe = x2 - r * (1 + Math.cos(angle));
				var ye = y2 - r * (1 - Math.sin(angle));
				//Radius
				this.graphicsMove.clear();
				this.graphicsMove.drawLine(x1, y1, x2, y2);
				this.graphicsMove.paint();
				//Circonferenza
				this.graphics.clear();
				this.graphics.drawEllipse(xe, ye, d, d);
				this.graphics.paint();	
				this.length = r;
				this.area = r*r*Math.PI;
			}
			
			else if(this.mode == DRAW_PEN || this.mode == DRAW_PEN_CLOSE){
				this.x.push(x2);
				this.y.push(y2);
				this.length += this.getDistance(x1, y1, x2, y2);
				this.graphics.drawLine(x1,y1,x2,y2);
				this.graphics.paint();
				if (this.mode == DRAW_PEN_CLOSE){
					this.graphicsMove.clear();
					this.graphicsMove.drawLine(this.x[0], this.y[0], x2, y2);
					this.graphicsMove.paint();
					this.area = this.getArea(this.x, this.y);
				}
			}

			else if (this.mode == REF_BOX) {
				var maxWidth = xWidth(this.anchor);
				var maxHeight = xHeight(this.anchor);
				var x = this.refboxX + x2 - x1;
				var y = this.refboxY + y2 - y1;

				x = Math.max(x,0);
				y = Math.max(y,0);
				x = Math.min(x, maxWidth - this.refboxW - this.thickness);
				y = Math.min(y, maxHeight - this.refboxH - this.thickness);
					
				this.graphics.clear();
				this.graphics.drawRect(x, y, this.refboxW, this.refboxH);
				this.graphics.paint();
					
				this.x1 = x;
				this.y1 = y;
			}	
		}
	
		else{
			if (this.mode == DRAW_LINE){
				this.graphicsMove.clear();
				this.graphicsMove.drawLine(x1, y1, x2, y2);
				this.graphicsMove.paint();
				var tmpDistance = this.getDistance(x1, y1, x2, y2);
			} 
			else if (this.mode == DRAW_POLYGON){
				var tmpx = this.x.slice();
				var tmpy = this.y.slice();
				var tmpDistance = this.getDistance(x1, y1, x2, y2) + this.getDistance(this.x[0], this.y[0], x2, y2)
				if (this.x.length>0){
					tmpx.push(x2);
					tmpy.push(y2);			
				}
				this.area = this.getArea(tmpx, tmpy);
				this.graphicsMove.clear();
				this.graphicsMove.drawLine(x1, y1, x2, y2);//linee di costruzione
				this.graphicsMove.drawLine(this.x[0], this.y[0], x2, y2);//linee di costruzione
				this.graphicsMove.paint();
			}
		}
		this.owner.mouseMove({'X':x2,'Y':y2,'A':this.angle,'L':this.length,'AREA':this.area,'TMPL':tmpDistance});
	},
	
	//Metodo associato a mouseup: termina l'operazione  e chiama i metodi del gisclient che a sua volta fa le chiamate al server
	complete: function(event) {
	
		if(!$chk(event)) return;
		var e = new Event(event);
		var np = this.x.length - 1;
		var x1 = this.x[np];
		var y1 = this.y[np];		
		var x2 = e.page.x - this.offsetx;
		var y2 = e.page.y - this.offsety;
		this.dragging = false;
		
		if(!x1) return;
		
		if (this.mode == ZOOM_BOX || this.mode == DRAW_BOX){
			var xmin = Math.min(x1, x2);
			var xmax = Math.max(x1, x2);
			var ymin = Math.min(y1, y2);
			var ymax = Math.max(y1, y2);
			if ((xmax-xmin) < this.options.jitter || (ymax-ymin) < this.options.jitter){
				var objZoom = {'X':[xmin],'Y':[ymin]};//zoom point
				var objGeom = {'type':'point','X':x1,'Y':y1,'A':0}
			}
			else{
				var objZoom = {'X':[xmin,xmax],'Y':[ymin,ymax]};
				var objGeom = {'type':'polygon','X':[xmin,xmin,xmax,xmax,xmin],'Y':[ymin,ymax,ymax,ymin,ymin],'AREA':this.area};
			}
			if(this.mode == ZOOM_BOX)
				this.owner.zoom(objZoom,1);
			else
				this.owner.addGeometry(objGeom);
			
		}
		
		else if(this.mode == ZOOM_OUT) {
			this.owner.zoom({'X':[x1],'Y':[y1]},-1);		
		}	
		
		else if(this.mode == PAN) {
			x1 = this.element.getSize().x / 2 - (x2 - x1);// + this.offsetx;
			y1 = this.element.getSize().y / 2 - (y2 - y1);// + this.offsety;
			this.owner.zoom({'X':[x1],'Y':[y1]},0);
		}
		
		else if(this.mode == DRAW_POINT || this.mode == PICK_POINT){
			this.owner.addGeometry({'type':'point','X':x1,'Y':y1,'A':this.angle});
		}
		
		else if(this.mode == DRAW_CIRCLE){
			this.owner.addGeometry({'type':'circle','X':x1,'Y':y1,'R':Math.round(this.length)});
		}
		
		else if(this.mode == DRAW_PEN){
			this.owner.addGeometry({'type':'line','X':this.x,'Y':this.y,'LENGTH':this.length});
		}
		
		else if (this.mode == DRAW_PEN_CLOSE){
		
			this.graphics.drawLine(this.x[np-1], this.y[np-1], this.x[0], this.y[0]);
			this.graphicsMove.clear();	
			this.graphicsMove.paint();		
			this.graphics.paint();
			if(this.x.length>3)
				this.owner.addGeometry({'type':'polygon','X':this.x,'Y':this.y,'AREA':this.area});			
		}
		
		else if (this.mode == REF_BOX) {
			if ((x1!=x2 && y1!=y2) && this.x1){//ho spostato il refbox
				this.refboxX = this.x1;
				this.refboxY = this.y1;
				if (this.setBoxHandler) this.setBoxHandler(this.refboxX, this.refboxY, this.refboxW, this.refboxH);
				this.x1 = this.y1 = false;	
			}
		} 
		
		//complete draw line or polygon
		else if (this.mode == DRAW_LINE || this.mode == DRAW_POLYGON){
			this.graphics.drawLine(this.x[np-1], this.y[np-1], this.x[np], this.y[np]);
			if(np>0) this.length += this.getDistance(this.x[np-1], this.y[np-1], this.x[np], this.y[np]);
			this.graphics.paint();				
			if(this.mode == DRAW_LINE){
				this.graphicsMove.clear();	
				this.graphicsMove.paint();
			}
			return;//Non resetto le coordinate: addgeometry viene fatto su doppio click
		}
		
		this.x = new Array();
		this.y = new Array();
		this.length = this.area = 0;
	}, //End mouseUp
	
	moveTo:  function(x,y){
	
		//DA FARE CON $$ PER TUTTE LE IMMAGINI
	
		$('gisclient_image').setStyles({'left':x,'top':y});	
	
	
	},
	
	
	clear: function(){
		this.x = new Array();
		this.y = new Array();
		this.graphicsMove.clear();
		this.graphics.clear();
		this.length = this.area = this.angle = 0;
		this.setMisure = false;
	},

	enter:  function(event) {
		this.element.setStyle('cursor','crosshair');
	},

	exit:  function(event) {
        this.element.setStyle('cursor','default');
        
	},

	doubleclick:  function(event) {			
		//Tolgo ultimi vertici aggiunti con il doppio click da firefox CONTROLLARE !!!!!!!!!!!!!!!!!!
		//************************************************************************
		if (this.x.length > 0 && (Browser.Engine.gecko)){//firefox non aggiunge il vertice su doppio click
			this.x.pop();
			this.y.pop();
		}
		
		if (this.mode == DRAW_LINE || this.mode == DRAW_POLYGON) {
			if(this.mode == DRAW_POLYGON && this.x.length > 2){//chiudo il poligono
				this.graphics.drawLine(this.x[0], this.y[0], this.x[this.x.length - 1], this.y[this.y.length - 1]);
				this.graphics.paint();
				this.area = this.getArea(this.x, this.y);
				this.owner.addGeometry({'type':'polygon','X':this.x,'Y':this.y,'AREA':this.area});
			}
			else
				this.owner.addGeometry({'type':'line','X':this.x,'Y':this.y,'LENGTH':this.length});
			this.x = new Array();
			this.y = new Array();
			this.length = this.area = 0;
			this.setMisure = false;
		}
	},
	
	getWidth: function(){
		return this.element.getSize().x;
	},
	
	getHeight: function(){
		return this.element.getSize().y;
	},
	
	//questo metodo era ipotizzato per gestire + layer. Se usiamo openlayers diventa superfluo
	addLayer:  function(imgId,imgSrc){
		var preloadId = imgId + '_preload';
		var imagepreload = new Element('img',{'id':preloadId,'position':'absolute','styles': {'visibility':'hidden'}});
		imagepreload.addEvent('load',function(){
			$(imgId).src=$(preloadId).src;
			$(imgId).fade('hide');
			$(imgId).setStyles({'position':'absolute','left':0,'top':0,'width':this.getWidth(),'height':this.getHeight()});	
			$(imgId).fade('show');
			this.owner.setBusy(false);
		}.bind(this));
		var image = new Element('img',{'id':imgId,'position':'absolute'});
		this.maps.appendChild(imagepreload);
		this.maps.appendChild(image);
	},

	setImage:  function(imgSrc){
		$('gisclient_image_preload').set('src',imgSrc);	
	},
	
	getDistance: function (x1, y1, x2, y2) {
		return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
	},
	
	getArea: function (x, y) {
		var area = 0;
		for (var i = 0; i < x.length; i++) {
			var j = (i + 1) % x.length;
			area += x[i] * y[j] - x[j] * y[i];
		}
		return (area < 0 ? -area / 2.0:area / 2.0);
	}

});  





