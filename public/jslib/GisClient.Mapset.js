var boolOp = ['=','>','>=','<','<=','!=','like','ilike','in','not in'];
var myTimer;
var GC_currentScale;//DA VEDERE
var GC_currentMapsetTitle;
var GC_minSuggest = 5;

GisClient.Mapset = new Class({

	Extends: Request.JSON, 
	Implements: [Options,Events],
	
	options: { 
		'url': GisClient.BaseUrl + 'xserver/xMapServer.php',
		'suggesturl': GisClient.BaseUrl + 'xserver/xSuggest.php',
		'encoding ': 'ISO-8859-15',	
		'link':'chain',
		'async':false,
		'userMode':MODE_SELECT,
		'resultWin':false,
		'useForm':false,
		'cleanForm':true, //NON + USATO
		'selectMode':0,
		'selectAction':2,
		'zoomAction':2,
		'layerDelay':2000,
		'activateSelGroup':false,//x geoweb attiva i gruppi di selezione quando seleziono uno strumento di selezione
		'left':0,
		'right':0,
		'bottom':0,
		'top':0,
		'minwidth':360,
		'minHeight':400,
		'zoomstep': 2,
		'minSliderScale':100,
		//'scaleList': ['100','200','300','400','500','1000','1.500','2.000','3.000','4.000','5.000','10.000','25.000','30.000','40.000','50.000','100.000','500.000','1.000.000']
		'scaleList': [100,200,300,400,500,1000,1500,2000,3000,4000,5000,10000,25000,30000,40000,50000,100000,500000,1000000]
	}, 
	
	initialize: function(anchor,options) { 
		
		this.parent(options);//set options otherwise default
		//write dom object
		//set global DA VEDERE SPOSTO????
		GC_optionResultWin = this.options.resultWin;
		GC_optionUseForm = this.options.useForm;
		GC_optionSelect = this.options.selectMode;
		GC_optionAction = this.options.selectAction;
		
		this.containerDiv = new Element('div',{'class':'gisclientContainer'});
		this.toolbarDiv = new Element('div',{'class':'toolbarBox'});
		this.rtoolbarDiv = new Element('div',{'class':'toolbarBox'});
		this.appDiv = new Element('div',{'class':'layoutElement'});
		this.statusbarDiv = new Element('div',{'class':'toolbarBox'});
		var mapDiv = new Element('div',{'class':'mapArea'});
		this.panelDiv = new Element('div',{'class':'tabArea'});
		var sliderDiv = new Element('div',{'id':'myslider','class':'sliderBox'});
		var coordinatesDiv = new Element('span',{'id':'coordinates'});
		var statusinfoDiv = new Element('span',{'id':'statusinfo'});
		this.statusbarDiv.appendChild(coordinatesDiv);
		this.statusbarDiv.appendChild(statusinfoDiv);
		
		this.containerDiv.appendChild(this.toolbarDiv);
		this.containerDiv.appendChild(this.rtoolbarDiv);
		this.containerDiv.appendChild(this.appDiv);
		this.containerDiv.appendChild(this.statusbarDiv);		
		this.containerDiv.appendChild(sliderDiv);
		this.appDiv.appendChild(mapDiv);
		this.appDiv.appendChild(this.panelDiv);		
		
		this.Redline = new GisClient.Redline(this.containerDiv,this);

		$(anchor).appendChild(this.containerDiv);
		this.isBusy = false;
		

		//set div position 
		this.mainLayout = this.setLayout();
		new Jx.Layout(anchor).resize();
	//	return;		

		this.geoPixel=0;//pixel per units
		this.oXgeo=0;//coordinates  offset
		this.oYgeo=0;
		this.scale=0;
		this.mode = MODE_MAP;
		this.queryMode = MODE_SELECT;// this.options.userMode;
		
		
		//draw slider
		//this.setSlider(sliderDiv);
		//new Drag.Move(sliderDiv, {'container':mapDiv}); 
	
		//arrange layout on star
		
		
		var lockDiv = new Element('div',{id:'gisclient_lock'});
		var imgBusy = new Element('img',{id:'gisclient_busy_image',src:GisClient.BaseUrl + 'images/loading.gif'});
		//this.containerDiv.appendChild(lockDiv);
		//this.containerDiv.appendChild(imgBusy);
		document.body.appendChild(lockDiv);
		document.body.appendChild(imgBusy);

		
		//mapDiv.setStyles({'background-image':'url(../../images/gclogof.png)','background-repeat':'no-repeat','background-position':'center center'});


		new Jx.Layout(mapDiv).addEvent('sizeChange', function() {
			//this.refMap.zoomAction = false;
			myTimer = $clear(myTimer);
			myTimer = this.redraw.delay(200,this);
			//this.refMap.zoomAction = true; 
			
            this.fireEvent('sizeChange');
		}.bind(this));
		
		
		
		/*
		this.mainLayout.addEvent('sizeChange', function() {
			myTimer = $clear(myTimer);
			myTimer = this.reload.delay(200,this);
		}.bind(this));	
		*/
		
		
		var mapsetString = '';
		if(this.options.mapset) mapsetString='&mapset='+this.options.mapset;
	
		this.map = new GisClient.MapBox(mapDiv,this,this.options);//???????????????????????????VEDERE GERARCHIE
		this.setBusy(true);
		var refW = (this.options.refmapWidth)?this.options.refmapWidth:this.options.panelWidth;
		refW = refW ;
		refH = this.options.refmapHeight;
		var param = 'language=' + GisClient.Language + '&action=initapp&referenceW='+ refW +'&referenceH='+ refH + mapsetString +'&'+location.search.substring(1);
		this.send(param);
		
	
		//END INITIALIZE
	}, 
	
	
	//response json ok
	onSuccess: function(responseJSON, responseText){
	
		this.map.clear();
		
		if($type(responseJSON)!='object'){
			this.getErrorMessage(responseText);
			
			this.setBusy(false);
			
			return;
		
		}
	
		//alert(responseText);
		this.response = responseJSON;

		if(responseJSON.error!=0){
			if(responseJSON.error==110 && $chk(this.login))
				this.fireEvent('LoginFailed');//DAVEDERE
			else
				this.getErrorMessage(responseJSON.errorString);	
			this.setBusy(false);
			return;
		
		}
		
		if(this.response.initapp) this.initMap();
		if(this.response.updatemap) this.updateMap();

		if(this.response.qtname) this.updateQtName();
		if(this.response.qtfield) this.updateQtField();
		if(this.response.queryresult) this.showInfo();
		if(this.response.updatereference) this.updateRef();
		
		if(this.response.downloadimage) this.openPrintPage(this.response.imagefile, 'Immagine');
		if(this.response.printmap) this.openPrintPage(this.response.pdffile, 'PdfFile');
		if(this.response.printTable) this.openPrintPage(this.response.tablefile, 'TableFile');
		
		this.response.updatemap = this.response.initmap=0;

	},
	
	onFailure: function(xhr){
		//alert('fallito ' + xhr);?????????????????????????????????
	
	},
	
	onException: function(headerName, value){
	
		//alert('');?????????????????????
	
	},

	initMap: function(){
		//$('gisclient_lock').setStyles({'background-image': ''})
		this.mapset = this.response.mapset;
		this.mapsetTitle = this.response.mapsettitle;
		this.selGroup = this.response.selgroup;
		this.addObject = this.response.addobject;
		this.geocoord = this.response.geocoord;
		this.utmZone = this.response.utmzone;
		this.utmSouth = this.response.utmsouth;
		this.epsg = this.response.epsg;
		this.wmsList = this.response.selwms;
		this.projName = (this.response.projname)? (this.response.projname + " ( "+this.response.epsg+" )") : null;
		GC_currentMapsetTitle = this.response.mapsettitle;
		
		var objSelected = this.response.objselected;
		
		this.initToolbarH();
		if (this.options.toolbarWidth>0) this.initToolbarV();
		
		this.initTabPanels();
		
		//var refMapWidth = (this.response.initref == 2) ? this.options.refmapWidth:null;//reference statico passo la larghezza
		this.refMap = new GisClient.Reference('gisclient_reference',this);
		this.refMap.setRefMap(this.response.refmapurl);
		
		
		//RIVEDERE
		var layerTree = initLayerTree($('gisclient_layertree'),this.response.layertree,this.response.themeopen,this.response.groupon,this.options.layerDelay);
		layerTree.addEvent('updated',this.updateLayer.bind(this));	
		initLegendTree($('gisclient_legendtree'),this.response.layertree,this.mapset);
		if($('gisclient_buttonPrint')) $('gisclient_buttonPrint').addEvent('click',this.printMap.bindWithEvent(this));
		if($('gisclient_buttonDlImage')) $('gisclient_buttonDlImage').addEvent('click',this.dlImage.bindWithEvent(this));
		if($('gisclient_mapset_title')) $('gisclient_mapset_title').set('html',this.response.mapsettitle);
		
		if (this.response.qtheme.length > 0 || this.response.selwms.length > 0) {
			if(this.response.qtheme.length > 0)  this.initOptQueryGeom();
			this.initQueryMode();
		}		

		//2a chiamata genero la mappa
		this.map.addLayer('gisclient_image', GisClient.BaseUrl + 'images/spacer.gif');
		
		if($chk(objSelected)){
			var param={
				'mapset' : this.mapset,
				'action':'info',
				'mode': 'search',
				'item': $('list_gc_qt').getSelected()[0].get('value'),	
				'zoomobj': objSelected,
				'imageWidth': this.map.getWidth(),
				'imageHeight': this.map.getHeight(),
				'selectMode':0,//Estensione
				'resultAction':2,//selezione+zoom
				'resultype':GC_optionResultWin?2:1,//dove spedisco il risultato (info tabella)
				'spatialQuery' : 0
			};
			objSelected = null;
		}
		else
			var param = {'mapset':this.mapset,'action':'initmap','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		
		this.post(param);
		this.setBusy(false);
			
	},
	
	
	updateMap: function(){
		this.geoPixel = this.response.geopixel;
		this.oXgeo=this.response.ox;//coordinates  offset
		this.oYgeo=this.response.oy;
		
		var oldMaxScale = this.maxScale;
		this.maxScale=this.response.maxscale;
		this.currentScale=this.response.scale;
		GC_currentScale=this.response.scale;
		this.map.element.setStyle('background-image','');
		
		var sliderValue = Math.round(Math.pow((this.currentScale-this.options.minSliderScale)/this.maxScale,1/3)*100);
		
		if(!sliderValue) sliderValue=0;
		//this.GC_Slider.moveslider = false;
	//	this.GC_Slider.set(sliderValue);
		//this.GC_Slider.moveslider = true;
		
		//set radio status
		this.enableCurrentSelection = $chk(this.response.optselcurr);
		this.enableObjectSelection = $chk(this.response.optselobj);
		this.setSelectOptionStatus();
		
		//Setta valore di scala
		//this.setScaleList(oldMaxScale);
		this.setScaleList(oldMaxScale);
		
		//box sul reference
		this.refMap.setRefBox(this.response.refbox);
		
		//POI PASSER0' ANCHE L'ELENCO DEI LAYER ACCESI E SPENTI PER IMPLEMENTARE IL REQUIRE (SERVER SIDE)
		layerTree.setItems(this.response.groupon,this.response.grpdisabled);
		var thdisabled = new Array();
		for (var i=0; i<layerTree.nodes.length; i++)
			if(!layerTree.nodes[i].options.enabled || !layerTree.nodes[i].domControl.checked) thdisabled.push(layerTree.nodes[i].options.id);
		setLegendTree($('gisclient_legendtree'),this.response.groupon,this.response.grpdisabled,thdisabled);
		
		this.map.setImage(this.response.mapurl);
		this.fireEvent('updateMap');
		
		//this.setBusy(false);

	},

	printMap: function(){
		var printTitle = $('GC_prTitle').get('value');
		var printLabelScale = GC_LABEL["Scale"];
		var printLayout = $$('input[name=GC_printLayout]').filter(function(item) { return item.checked })[0].get('value');
		var printFormat = $$('input[name=GC_printFormat]').filter(function(item) { return item.checked })[0].get('value');
		var printLegend = $$('input[name=GC_printLegend]').filter(function(item) { return item.checked })[0].get('value');
		var printScale = parseInt($('GC_printScale').get('value'))>0?$('GC_printScale').get('value'):this.currentScale;
		var param = {'mapset':this.mapset,'action':'printmap','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight(),'mapsettitle':this.mapsetTitle,'scale':printScale,'labelscale':printLabelScale,'printtitle':printTitle,'pagelayout':printLayout,'pageformat':printFormat,'legend':printLegend};
		this.setBusy(true);	
		this.post(param);
	},
	
	dlImage: function(){
		var imgDpi = $$('input[name=GC_imgDpi]').filter(function(item) { return item.checked })[0].get('value');
		var gtiffFormat = $('GC_gtiffFormat').checked ? 1:0;
		var dlImgRatio = $('GC_dlImgRatio').get('value')?$('GC_dlImgRatio').get('value'):1;
		this.setBusy(true);	
		var param = {'mapset':this.mapset,'action':'dlimage','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight(),'scale':this.currentScale,'imgRatio':dlImgRatio,'gTiff':gtiffFormat,'imgDpi':imgDpi};
		this.post(param);	
	},
	
	printTable: function(qtId,destination,parentId,relationId){
		this.mode=this.queryMode;
		this.sendQuery({'printTable':1,'destination':destination,'qt':qtId,'parentId':parentId,'relation':relationId});
	},
	
	openPrintPage: function(pageUrl,pageType){
		OpenPopup(pageUrl, pageType);
		this.setBusy(false);
	},
	
	updateRef: function(){
		this.refMap.setRefMap(this.response.refmapurl);
		this.refMap.setRefBox(this.response.refbox);
		this.setBusy(false);
	},
	
	reloadRef: function(){
		if($('gisclient_reference').getSize().x>0 && $('gisclient_reference').getSize().y>0){
			var param = {'mapset':this.mapset,'action':'reloadref','referenceW':$('gisclient_reference').getSize().x,'referenceH':$('gisclient_reference').getSize().y};
			this.setBusy(true);	
			this.post(param);
		}
	},
	
	zoomRef: function(x,y){
		var param = {'mapset':this.mapset,'action':'zoomref','refX':x,'refY':y,'imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		this.setBusy(true);	
		this.post(param);
	},
	
	
	reload: function(){
		var param = {'mapset':this.mapset,'action':'reload','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		this.setBusy(true);	
		this.post(param);
	},
	
	redraw: function(){
		var param = {'mapset':this.mapset,'action':'redraw','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		this.setBusy(true);	
		this.post(param);
	},
	
	zoomAll: function(){
		var param = {'mapset':this.mapset,'action':'zoomall','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		this.setBusy(true);	
		this.post(param);
	},
	
	zoomBack: function(){
		var param = {'mapset':this.mapset,'action':'redraw','history':-1,'imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		this.setBusy(true);	
		this.post(param);
	},

	zoomFwd: function(){
		var param = {'mapset':this.mapset,'action':'redraw','history':1,'imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
		this.setBusy(true);	
		this.post(param);
	},
	
	zoomScale: function(scale){	
	    if($type(scale)=='string' && scale.indexOf(':')>0){
			scale=scale.substring(scale.indexOf(':')+1);
			scale=parseInt(scale.replace('.',''));
		}
		if(scale<=this.maxScale && scale>=this.options.minSliderScale){
			var param = {'mapset':this.mapset,'action':'scale','scale':scale,'imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight()};
			this.setBusy(true);	
			this.post(param);
		}
	},
	
	zoom: function(geom,direction,asgeo) {
	
		var step = false;
		var param = {'mapset':this.mapset,
                     'imageWidth':this.map.getWidth(),
                     'imageHeight':this.map.getHeight(),
                     'imgX':geom.X,
                     'imgY':geom.Y};
		if(geom.X.length == 1){
            step = (direction==0)?1:this.options.zoomstep * direction;
			param["action"] = 'zoompoint';
			param["zoomStep"] = step;
		} else {
			param["action"] = 'zoomwindow';	
        }
        if (asgeo) param['asgeo'] = 'T';
        
		this.setBusy(true);
		
		this.post(param);
    },
	
	zoomResult: function(extent,selColor,objId,layername,layerkey,staticLayer,qtId,layerGroup){
		resultAction = (objId && objId.length==1)?((GC_optionAction<2)?this.options.zoomAction:GC_optionAction):2;
		var param = {'mapset':this.mapset,'action':'zoom_result','resultAction':resultAction,'staticLayer':staticLayer,'imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight(),'objid':objId,'qtid':qtId,'layername':layername,'layerkey':layerkey,'layerGroup':layerGroup,'selcolor':selColor,'extent':extent};	
		this.setBusy(true);	
		this.post(param);
	},
	
	updateLayer: function(arg){
		//Se chiamo da fuori niente delay
		this.setBusy(true);	
		var param = {'mapset':this.mapset,'action':'redraw','imageWidth':this.map.getWidth(),'imageHeight':this.map.getHeight(),'layers':arg.layers,'thopen':arg.themes};	
		this.post(param);
	},
	

	addGeometry : function(obj){

		//if(this.mode==MODE_SEARCH || this.mode==MODE_SELECT) this.sendQuery(obj);
		//if(this.mode==MODE_EDIT || this.mode==MODE_MEASURE || this.mode==MODE_REDLINE) {
		
		if (this.map.mode==PICK_POINT){//Aggiunta di oggetti da fuori
			this.fireEvent('addObject',obj);
			return;
		}
		
		if (this.mode==MODE_REDLINE) {
			if(this.map.mode==DRAW_LINE) 
				this.Redline.addText(obj);
			else
				this.Redline.add(obj);
		}	
		
		else if (this.mode==MODE_EDIT){
			
			if(this.editUrl){
				//verifico se è settata gcEditGeometry se si la uso --- fare la stessa cosa da opener contrario
				if(GisClient.gcEditGeometry) 
					GisClient.gcEditGeometry(obj)
				else{
					var param = "type=" + obj.type + "&X=" + (this.oXgeo + obj.X*this.geoPixel).toFixed(2) + "&Y=" + (this.oYgeo - obj.Y*this.geoPixel).toFixed(2) + "&A=" + obj.A + "&R=" + (obj.R*this.geoPixel).toFixed(2);    
					OpenPopup(this.editUrl, 'EditPage', param);
				}
			}
		
		}
			
		
		//else if (this.map.mode!=PICK_POINT || this.map.mode!=MODE_MEASURE)	this.sendQuery(obj);
		else if (this.mode==MODE_SELECT || this.mode==MODE_SEARCH)
			this.sendQuery(obj);
		
		else if (this.mode==MODE_WMS){
			if(obj.X.length > 0)
				alert('Selezione WMS solo puntuale');
			else
				this.sendQuery(obj);
		}
			
		this.map.setMisure = false;
		this.fireEvent('addObject',obj);
	},
	
	removeRedline: function(){
	

	},
	
	nextResultPage: function(pageindex,totalrows,resultype){
	
		this.mode=this.queryMode;
		this.sendQuery({'paging':1,'pageIndex':pageindex,'totalRows':totalrows,'resultype':resultype});
	
	},
	
	mouseMove : function(obj){
		var x = this.oXgeo + obj.X*this.geoPixel;
		var y = this.oYgeo - obj.Y*this.geoPixel;
		var sxy = 'X: '+ x.toFixed(1) + '  Y: ' + y.toFixed(1);
		var sCoord = sxy;

		//Coordinate geografiche
		if(this.geocoord){
			var latlon =new Array();
			UTMXYToLatLon (x, y, this.utmZone, this.utmSouth, latlon);
			
			var lat = getDegree(RadToDeg(latlon[0]));
			var lon = getDegree(RadToDeg(latlon[1]));

			lat = '  Lat: ' + lat.degrees + 'd  ' + lat.minutes + "'  " + lat.seconds + "''";
			lon = '  Long: ' + lon.degrees + 'd  ' + lon.minutes + "'  " + lon.seconds + "''";
			sCoord += lat + lon;
		}
		
		if(this.projName) 
			sCoord += ' - ' + this.projName;
		
		$('coordinates').set('text',sCoord);
		if(this.map.setMisure) {
			var s='misura';
			if(this.map.mode == DRAW_POINT) s = GC_LABEL["measureRotation"] + ": " + obj.A +"d";
			
			if(this.map.mode == DRAW_CIRCLE) s = GC_LABEL["measureRadius"] + ": " + (obj.L*this.geoPixel).toFixed(3); 
			
			if(this.map.mode == DRAW_PEN) s = GC_LABEL["measurePath"] + ": " + (obj.TMPL*this.geoPixel).toFixed(3); 
			
			if(this.map.mode == DRAW_LINE){
				if($chk(obj.L)) s = GC_LABEL["measureTotalLength"] + ": " + (obj.L*this.geoPixel).toFixed(3); 
				if($chk(obj.TMPL)) s += '   ' + GC_LABEL["measureSegmentLength"] + ": " + (obj.TMPL*this.geoPixel).toFixed(3); 
			}
			
			if(this.map.mode == DRAW_POLYGON){
				s = GC_LABEL["measurePerimeter"] + ": " + ((obj.L + obj.TMPL)*this.geoPixel).toFixed(3); 
				var sup = Math.pow(Math.sqrt(obj.AREA)*this.geoPixel,2).toFixed(3); 
				s += "  " + GC_LABEL["measureArea"] + "  : " + sup;
			}
			$('coordinates').set('text',s);
		}
        
        this.fireEvent('mouseMove',obj);
	},
	
	
	setQueryMode:function(flag){
		
		this.queryMode = $('list_gc_mode').getSelected().get('value');
		this.mode = this.queryMode;

		if(this.queryMode==MODE_SELECT){
			if(this.editTool) this.editTool.setEnabled(false);
			//this.selectTools.setEnabled(true);
			$('gisclient_search').addClass('hidden');
			$('gisclient_wms').addClass('hidden');	
			$('gisclient_select').removeClass('hidden');			
			$('gisclient_querygeom').removeClass('hidden');	
			this.selectTools.options.items[0].setLabel(GC_LABEL.geometryPoint);
		}else if(this.queryMode==MODE_SEARCH){
			if(this.editTool) this.editTool.setEnabled($chk(this.editUrl));
			//this.selectTools.setEnabled(true);
			$('gisclient_select').addClass('hidden');
			$('gisclient_wms').addClass('hidden');	
			$('gisclient_search').removeClass('hidden');
			$('gisclient_querygeom').removeClass('hidden');	
			this.selectTools.options.items[0].setLabel(GC_LABEL.geometryPoint);
		}else if(this.queryMode==MODE_WMS){
			this.selectTools.setActiveButton(this.selectTools.options.items[0]);
			this.selectTools.options.items[0].setLabel(GC_LABEL.geometryPointWMS);
			$('gisclient_search').addClass('hidden');
			$('gisclient_querygeom').addClass('hidden');	
			$('gisclient_select').addClass('hidden');
			$('gisclient_wms').removeClass('hidden');	
		}
		
		if(!this.options.activateSelGroup){
			this.selectTools.setActive(true);
			this.selectTools.activeButton.fireEvent('click');
		}

	},
	
		
	setLayout:function() {
	    var jxl = new Jx.Layout(this.containerDiv,this.options);
	    new Jx.Layout(this.toolbarDiv, {
	        height:this.options.toolbarHeight
	    });
		new Jx.Layout(this.rtoolbarDiv, {
	        top:this.options.toolbarHeight,
			bottom:this.options.statusbarHeight,
			width:this.options.toolbarWidth
	    });
	    new Jx.Layout(this.appDiv, {
	        top:this.options.toolbarHeight,
	        bottom:this.options.statusbarHeight,
			left:this.options.toolbarWidth	
	    });
	    new Jx.Layout(this.statusbarDiv, {
	        top:null,
	        height: this.options.statusbarHeight
	    });
		
		var snapImg = new Element('img', {
	        src: Jx.aPixel.src
	    });
		
		var splitter = new Jx.Splitter(this.appDiv, {
	        useChildren: true,
	        barOptions: [
	            {snap:'after',snapElement: snapImg, snapEvents:['click']}
	        ],
			containerOptions:[
				{},{width:this.options.panelWidth}
			]
	    });
		
		return jxl;
	},
	

	initTabPanels:	function() {
	    this.tabBox = new Jx.TabBox({parent: this.panelDiv});
		var navPanels = new Jx.Button.Tab({
	            active: true,
	            label: GC_LABEL["panelMap"],
				image: GisClient.BaseUrl + 'images/map.png',
	            content: new Jx.PanelSet({
	                panels: [
	                    new Jx.Panel({
	                        label: GC_LABEL["Layers"],
							image: GisClient.BaseUrl + 'images/layers.gif',
							maximize: true,
							collapse:false,
	                        content: '<div id="gisclient_layertree" class="treeBox"></div>'
	                    }),
	                    new Jx.Panel({
	                        label: GC_LABEL["Legend"],
							image: GisClient.BaseUrl + 'images/icon_legend.gif',		
							maximize: true,
							collapse:false,
	                        content: '<div id="gisclient_legendtree" class="treeBox"></div>'
	                    }),
						new Jx.Panel({
	                        label: GC_LABEL["Reference"], 
							id:'gisclient_reference',
							image: GisClient.BaseUrl + 'images/legend-map.png',
							maximize: true,
							collapse:false,
							maxHeight:this.options.refmapHeight+5,//da vedere 
							height:this.options.refmapHeight+5
	          
	                    })
	                ]
	            })
	        });
		this.tabBox.add(navPanels);

		if (this.response.qtheme.length > 0 || this.wmsList.length > 0){
			this.queryTab = new Jx.Button.Tab({
				//active: true,
	            label: GC_LABEL["panelTools"],
	            image: GisClient.BaseUrl + 'images/map_magnify.png',
	            content: '<div id="gisclient_mode"></div><div id="gisclient_select" class=\"hidden\"></div><div id="gisclient_search" class=\"hidden\"></div><div id="gisclient_querygeom" ></div><div id="gisclient_wms" ></div>'
	        });
			this.tabBox.add(this.queryTab);
		}
	},
	
	initToolbarH: function(){
	
		var tBar = new Jx.Toolbar({parent:this.toolbarDiv});
		var bSet = new Jx.ButtonSet();
		
		var zoomTools = new Jx.Button.Multi({
        items: [
            new Jx.Button({
				image: GisClient.BaseUrl + 'images/zoom-full.png',
				tooltip: GC_LABEL["zoomAll"],
				onClick: function(){this.zoomAll();}.bind(this)
            }),
            new Jx.Button({
				image: GisClient.BaseUrl + 'images/back.gif',
				tooltip: GC_LABEL["zoomPrev"],
				onClick: function(){this.zoomBack();}.bind(this)
            }),
            new Jx.Button({
	            image: GisClient.BaseUrl + 'images/fwd.gif',
				tooltip: GC_LABEL["zoomNext"],
	            onClick: function(){this.zoomFwd();}.bind(this)
            })
        ]
		});
	    
		this.zoomIn = new Jx.Button({
	        image: GisClient.BaseUrl + 'images/zoomin.gif',
	        tooltip: GC_LABEL["zoomWin"],
			toggle:true,
	        onDown: function(){this.mode = MODE_MAP;this.map.mode = ZOOM_BOX;}.bind(this)
        });
        
		this.zoomOut = new Jx.Button({
			image: GisClient.BaseUrl + 'images/zoomout.gif',
			tooltip: GC_LABEL["zoomBack"],
			toggle:true,
			onDown: function(){this.mode = MODE_MAP;this.map.mode = ZOOM_OUT;}.bind(this)
        });
		
		this.pan = new Jx.Button({
			image: GisClient.BaseUrl + 'images/pan.gif',
			tooltip: GC_LABEL["Pan"],
			toggle:true,
			onDown: function(){this.mode = MODE_MAP;this.map.mode = PAN;}.bind(this)
        });	
		
		
		this.measureLine = new Jx.Button({
			image: GisClient.BaseUrl + 'images/measure.gif',
			tooltip: GC_LABEL["measureL"],
			toggle:true,
			onDown: function(){this.mode = MODE_MEASURE;this.map.mode = DRAW_LINE;}.bind(this)
        });	
		
		this.measurePolygon = new Jx.Button({
			image: GisClient.BaseUrl + 'images/measure_area.gif',
			tooltip: GC_LABEL["measureP"],
			toggle:true,
			onDown: function(){this.mode = MODE_MEASURE;this.map.mode = DRAW_POLYGON;}.bind(this)
        });	
		
		//list of scale
		this.scaleList = new Jx.Button.Combo({
			editable: true
		});
		
		reload = new Jx.Button({
			image: GisClient.BaseUrl + 'images/view-refresh.png',
			tooltip: GC_LABEL["Reload"],
			onClick: function(){this.reload();}.bind(this)
        });
		

		
		//add buttons to buttons set and toolbar
		
		bSet.add(this.zoomIn,this.zoomOut,this.pan);
		tBar.add(zoomTools);
		tBar.add(new Jx.Toolbar.Separator());
		tBar.add(this.scaleList);
		tBar.add(new Jx.Toolbar.Separator());
		tBar.add(this.zoomIn,this.zoomOut,this.pan);
		tBar.add(new Jx.Toolbar.Separator());
		
		if (this.response.qtheme.length > 0 || this.wmsList.length > 0){
			if (this.response.qtheme.length > 0){
				this.selectTools = new Jx.Button.Multi({
					toggle: true,
					items: [
					new Jx.Button({
						label: GC_LABEL.geometryPoint,
			            image:GisClient.BaseUrl + 'images/select-rectangle.gif',
			            onClick: function(){this.activateSelGroup();this.mode = this.queryMode;this.map.mode = DRAW_BOX;}.bind(this)
			        }),          
					new Jx.Button({
						label: GC_LABEL.geometryPolygon,
			            image:GisClient.BaseUrl + 'images/select-polygon.gif',
			            onClick: function(){this.activateSelGroup();this.mode = this.queryMode;this.map.mode = DRAW_POLYGON;}.bind(this)
			        }),		
			        new Jx.Button({
						label: GC_LABEL.geometryCircle,
			            image:GisClient.BaseUrl + 'images/select-radius.png',
			            onClick: function(){this.activateSelGroup();this.mode = this.queryMode;this.map.mode = DRAW_CIRCLE;}.bind(this)
			        }),
					new Jx.Button({
						label: GC_LABEL.geometryArea,
			            image:GisClient.BaseUrl + 'images/select-area.png',
			            onClick: function(){this.activateSelGroup();this.mode = this.queryMode;this.map.mode = DRAW_PEN_CLOSE;}.bind(this)
			        })
			        ]
				});
			}
			else{
				this.selectTools = new Jx.Button.Multi({
				toggle: true,
				items: [
					new Jx.Button({
						label: GC_LABEL.geometryPointWMS,
				        image:GisClient.BaseUrl + 'images/select-rectangle.gif',
				        onClick: function(){this.queryMode = MODE_WMS;this.mode = this.queryMode;this.map.mode = DRAW_POINT;}.bind(this)
				    })
					]
				});
			
			}
			tBar.add(this.selectTools);
			bSet.add(this.selectTools);
			tBar.add(new Jx.Toolbar.Separator());	
			if (this.response.edit==1){
				this.editTool = new Jx.Button({
					image: GisClient.BaseUrl + 'images/line_add.png',
					tooltip:  GC_LABEL["edit"],
					toggle:true,
					enabled: false,
					onDown: function(){
						this.mode = MODE_EDIT;
						switch (this.layerType) {
							case 1://point
							case 5://redline
								this.map.mode = DRAW_POINT;
							break;
							case 2://line
								this.map.mode = DRAW_LINE;
							break;
							case 3://polygon
								this.map.mode = DRAW_POLYGON;
							break;
						}
					//alert(this.map.mode);
					}.bind(this)
		        });	
				tBar.add(this.editTool);
				bSet.add(this.editTool);
				tBar.add(new Jx.Toolbar.Separator());	
			}
		

			
		
		};//End if
		
		if (this.response.redline==1){
		
			var redline = new Jx.Button({
	          image: GisClient.BaseUrl + 'images/comment_add.png',
	          tooltip:  GC_LABEL["Annotation"],
			  toggle:true,
	          onDown: function(){this.mode = MODE_REDLINE;this.map.mode = DRAW_LINE;}.bind(this)
	        });	
			
			var pen = new Jx.Button({
	          image: GisClient.BaseUrl + 'images/pencil_add.png',
	          tooltip:  GC_LABEL["Free_annotation"],
			  toggle:true,
	          onDown: function(){this.mode = MODE_REDLINE;this.map.mode = DRAW_PEN;}.bind(this)
	        });	
			
			var pendel = new Jx.Button({
	          image: GisClient.BaseUrl + 'images/pencil_delete.png',
	          tooltip:  GC_LABEL["Delete_annotation"],
	          onClick: function(){this.Redline.remove();}.bind(this)
	        });	
		
			bSet.add(redline,pen);
			tBar.add(redline,pen,pendel);
			tBar.add(new Jx.Toolbar.Separator());
		
		}
				
		bSet.add(this.measureLine,this.measurePolygon);
		tBar.add(this.measureLine,this.measurePolygon);
		tBar.add(new Jx.Toolbar.Separator());
		        
		var printDialog = new Jx.Dialog({
	        label: GC_LABEL["PrintMap"],
	        image: GisClient.BaseUrl + 'images/file-print.png',
	        modal: false, 
			collapse:false,
	        width: 350,
	        height: 350,
	        content: this.getPrintDialog()
		});
		
		var print = new Jx.Button({
		image: GisClient.BaseUrl + 'images/file-print.png',
		tooltip:  GC_LABEL["Print_pdf"],
		onClick: function(){
			printDialog.open();
			//USARE LA PROPRIETA DELL OGGETTO NON UNA VARIABILE GLOBALE !!!
			$('GC_prTitle').set('value',GC_currentMapsetTitle);
			$('GC_printScale').set('value',GC_currentScale);
		}.bind(printDialog)
		});
		
		var imgDialog = new Jx.Dialog({
	        label: GC_LABEL["DownloadImage"],
	        image: GisClient.BaseUrl + 'images/picture_save.png',
	        modal: false, 
			collapse:false,
	        width: 300,
	        height: 300,
	        content: this.getDownloadDialog()
		});
		
		var image = new Jx.Button({
		image: GisClient.BaseUrl + 'images/picture_save.png',
		tooltip: GC_LABEL["DownloadImage"],
		onClick: imgDialog.open.bind(imgDialog)
		});
		
		var helpDialog = new Jx.Dialog({
	        label: GC_LABEL["Guide"],
	        image: GisClient.BaseUrl + 'images/help.gif',
	        modal: false, 
			collapse:false,
	        width: 400,
	        height: 400,
	        contentURL: GisClient.BaseUrl + 'help/help_' + GisClient.Language + '.html',
			onContentLoaded: function () {
				var el = this.content.getElementById('test')
					if (el) {
						el.set('html', 'Caricato!');            
					}        
				}
		});
		
		var help = new Jx.Button({
		image: GisClient.BaseUrl + 'images/help.gif',
		tooltip: GC_LABEL["Guide"],
		onClick: helpDialog.open.bind(helpDialog)
		});
		
		
		tBar.add(reload);
		tBar.add(new Jx.Toolbar.Separator());
		tBar.add(print,image);
		
		//funzione reload ref solo se refmap dinamico
		if (this.response.initref == 1){
			var reloadRef = new Jx.Button({
			image: GisClient.BaseUrl + 'images/reloadref.gif',
			tooltip: GC_LABEL["Reload_reference"],
			onClick: function(){this.reloadRef();}.bind(this)
			});
			tBar.add(reloadRef);
		}
		
		var settings = new Jx.Button.Flyout({
	        label: GC_LABEL["Options"],
	        image: GisClient.BaseUrl + 'images/cog_edit.png',
			//onClose: function (){alert('chiuso')},
			content: setSelectOptions()
			//onClose:this.saveSettings()
	    })
		//$('imgCloseOption').addEvent('click',function(){alert('')});
		//settings.addEvent('close',alert(''));
		tBar.add(settings);
		tBar.add(new Jx.Toolbar.Separator());
		tBar.add(help);
		this.zoomIn.setActive(true);
		this.toolBar = tBar;
		this.buttonSet = bSet;
		
	},
	
	initToolbarV: function () {

				
				
	},
	
	activateSelGroup: function() {
		if(this.queryMode!=MODE_SELECT && this.options.activateSelGroup){
			$('list_gc_mode').selectedIndex = 0;
			this.setQueryMode();
		}
	
	},
	
	setSlider: function (sliderBox){
		var sliderPath = new Element('div',{'class':'slider'});
		sliderBox.appendChild(sliderPath);
		var sliderKnob = new Element('div',{'class':'sliderknob'});
		//sliderKnob.addEvent('mouseout',function(){if(drag) {drag = false;objSlider.fireEvent('Complete')}});
		sliderPath.appendChild(sliderKnob);
		var initApp = true;
		// Create the new slider instance
		var newScale;
		var drag=false;
		var objSlider = new Slider(sliderPath, sliderKnob, {
			mode: 'vertical',
			//wheel:true,// TODO******************
			onChange: function(value){
				//SOSTITUIRE CON ARRAY DI OGGETTI $$ CLASSE
				if(!$('gisclient_image')) return;
				drag=true;
				
				newScale=Math.pow(value/100,3) * this.maxScale  + this.options.minSliderScale;  
				var resizeFactor=this.currentScale/newScale
				
				var oldW = this.map.getWidth();
				var oldH = this.map.getHeight();
				var newW = oldW*resizeFactor;
				var newH = oldH*resizeFactor;
				var newLeft = parseInt((oldW - newW) / 2);
			    var newTop  = parseInt((oldH - newH) / 2);

				this.scaleList.setValue(GC_LABEL["Scale"]+' 1:' + numberFormat(Math.round(newScale)));
				$('gisclient_image').setStyles({'left':newLeft,'top':newTop,'width':newW,'height':newH});
				
			}.bind(this),
			
			onComplete: function(value){ 
				if(!initApp) this.zoomScale(Math.round(Math.pow(value/100,3) * this.response.maxscale  + this.options.minSliderScale));
				initApp = false;
				drag=false;
			}.bind(this),
			
			onTick: function(pos){
				objSlider.knob.setStyle(this.property,pos);
				if(objSlider.wheelslider){
					(function(){ objSlider.wheelslider = false }).delay(5000);
				}
			}
			
		});
		
		//sliderKnob.addEvent('mousedown', function(){objSlider.moveslider = true;});
		//TODO
		sliderPath.addEvent('mousewheel', function(){objSlider.wheelslider = true;});
		this.GC_Slider = objSlider;
	},
	
	setBusy:function(value){
		this.isBusy = value;
		this.fireEvent('busy',value);
		if(value){
			var pos = parseInt((this.map.getWidth()-this.map.offsetx)/2) +  'px ' +  parseInt((this.map.getHeight()-this.map.offsety)/2) +  'px'; 
			//$('gisclient_lock').setStyles({'display':'block','background-image': 'url(../../images/loading.gif)','background-position': pos})
			$('gisclient_lock').setStyles({'display':'block'})
			$('gisclient_busy_image').setStyles({'display':'block'})
			$('gisclient_busy_image').setStyles({position:'absolute','left':parseInt((this.map.getWidth()-this.map.offsetx)/2) +  'px ','top':  parseInt((this.map.getHeight()-this.map.offsety)/2) +  'px'});
		}
		else{
			$('gisclient_lock').setStyles({'display':'none'})
			$('gisclient_busy_image').setStyles({'display':'none'})
		}
	},
	
	//Se è cambiata la scala massima cambio l'elenco dei valori in lista 
	//Setta la scala corrente come valore della combo
	setScaleList: function(oldMaxScale){
		if(oldMaxScale != this.maxScale){
			if(this.scaleList.menu.items.length >0 ){//scale gia impostate aggiorno solo il valore di maxscale
				this.scaleList.menu.items[this.scaleList.menu.items.length-1].options.value = this.maxScale;
				this.scaleList.menu.items[this.scaleList.menu.items.length-1].options.label = GC_LABEL["Scale"]+' 1:' + numberFormat(this.maxScale);
			}
			else{//elenco scale + eventi
				for(var i=0;i<this.options.scaleList.length;i++){
					var value = this.options.scaleList[i];
					if(value < this.maxScale){
						var label = numberFormat(value);
						this.scaleList.add({'label':GC_LABEL["Scale"]+' 1:' + label,'value':value});
					}
				}
				this.scaleList.add({'label':GC_LABEL["Scale"]+' 1:' + numberFormat(this.maxScale),'value':this.maxScale});
				this.scaleList.valueChanged = function (){this.zoomScale(this.scaleList.getValue())}.bind(this);
				this.scaleList.addEvent('onChange',function (combo){
					var item = combo.menu.items.filter(function(item,index){return item.options.label == combo.getValue()});
					if(item[0].options.value) this.zoomScale(item[0].options.value);
				}.bindWithEvent(this));
			}			
		}
		this.scaleList.setValue(GC_LABEL["Scale"]+' 1:' + numberFormat(this.currentScale));
		//alert(this.scaleList.currentSelection);
	},
	
	
	saveSettings: function(){
	
		//TODO SAVE COOKIES
		alert($('gc_settings'));
	
	},
	
	initInfoDialog: function(){
	
		this.infoDialog = new Jx.Dialog({
	        label: GC_LABEL['Results'],
	        image: GisClient.BaseUrl + 'images/table.png',
	        modal: false, 
			resize: true,
	        horizontal: '200 left', 
	        vertical: '50 top', 
			//onClose:function (){alert('chiuso')},
			width: 800,
			height: 600
			
			//contentURL: 'templateInfo.html'

		});
	
	
	},
	

	/*
	**********************************************************************
	QT METHODS (RIVEDERE) RIFARE OGGETTI (+ avanti)i
	**********************************************************************	
	*/
	initQueryMode: function(){
		selOption ="<fieldset class=\"searchInput\"><label for=\"list_gc_mode\">" + GC_LABEL["ModeList"] + "<br /></label>";
		selOption+="<select name=\"list_gc_mode\" id=\"list_gc_mode\">";
		var isempty = true;
		
		if (this.response.qtheme.length > 0){
			selOption += "<option value=\"" + MODE_SELECT + "\" / >" + GC_LABEL["selectMode"] + "</option>";
			selOption += "<option value=\"" + MODE_SEARCH + "\" / >" + GC_LABEL["queryMode"] + "</option>";
			this.initSelGroup();
			isempty = false;
		}
		
		if(this.wmsList.length > 0){
			selOption += "<option value=\"" + MODE_WMS + "\" / >" + GC_LABEL["wmsMode"] + "</option>";
			this.initWMSList();
			isempty = false;
		}

		selOption += "</select></fieldset>";
		if(!isempty){
			$('gisclient_mode').set('html',selOption);
			this.initInfoDialog();
		}
		else{
			$('gisclient_mode').set('html','messaggio da fare.......');
			return;
		}
		
		//Da rifare con una funzione setvalue??
		if(this.response.qtselected){
			$('list_gc_mode').selectedIndex=1;
			$('gisclient_search').toggleClass('hidden');
			var tabTools = this.tabBox.tabSet.tabs.filter(function(item,index){return item.options.label == GC_LABEL["panelTools"]})[0]
			tabTools.setActive(true);
			this.queryMode = MODE_SEARCH;
		}else{
			$('gisclient_select').toggleClass('hidden');
			this.queryMode = MODE_SELECT
		}
		
		var isqtSelected = $chk(this.response.qtselected);
		if (this.response.qtheme.length > 0) this.initQtTheme();
		//if (this.wmsList.length > 0) this.initWMSList();
			
		$('list_gc_mode').addEvent('change',this.setQueryMode.bindWithEvent(this));
		if(!isqtSelected && this.editTool) this.editTool.setEnabled(false);//se non ho passato il qt la modalita e seleziona

		this.mode=this.queryMode;
	},
	
	initWMSList: function(){
	
		var selOption ="<fieldset class=\"searchInput\"><label for=\"list_gc_selgroup\">" + GC_LABEL["SelWMSList"] + "<br /></label>";
		selOption+="<select name=\"list_gc_wms\" id=\"list_gc_wms\">";
		for(var i=0;i<this.wmsList.length;i++){	
			selOption += "<option value=\"" + i + "\" / >" + this.wmsList[i]["title"] + "</option>";
		}
		selOption += "</select></fieldset><div id=\"gisclient_wms_info\"></div>";
		$('gisclient_wms').addClass('hidden');	
		$('gisclient_wms').set('html',selOption);
		$('list_gc_wms').addEvent('change',this.setWMSInfo.bindWithEvent(this));	
		this.setWMSInfo();
	},
	
	setWMSInfo: function(){
		var index = $('list_gc_wms').getSelected()[0].get('value');	
		var infoWMS = this.wmsList[index];
		var html = "<fieldset class=\"searchInput\">";
		html += infoWMS.title;
		html += "</fieldset>";
		$('gisclient_wms_info').set('html',html);
	
	},
	
	initSelGroup: function(){
		var selOption ="<fieldset class=\"searchInput\"><label for=\"list_gc_selgroup\">" + GC_LABEL["SelgroupList"] + "<br /></label>";
		selOption+="<select name=\"list_gc_selgroup\" id=\"list_gc_selgroup\">";
		for(var i=0;i<this.response.selgroup.length;i++){	
			if(this.response.selgroup[i][0] < 0)
				selOption += "<option value=\"" + (-1*this.response.selgroup[i][0]) + "\" / >" + this.response.selgroup[i][1] + "</option>";
		}
		selOption += "<option value=\"layers_on\" / >" + GC_LABEL["SelectLayersOn"] + "</option>";
		selOption += "<option value=\"layers_all\" / >" + GC_LABEL["SelectLayersAll"] + "</option>";
		for(var i=0;i<this.response.selgroup.length;i++){	
			if(this.response.selgroup[i][0] > 0)
				selOption += "<option value=\"" + this.response.selgroup[i][0] + "\" / >" + this.response.selgroup[i][1] + "</option>";
		}
		selOption += "</select><div>" + GC_LABEL["SelgroupList"] + "</div></fieldset>";
		$('gisclient_select').set('html',selOption);
	},
	
	initOptQueryGeom: function(){
	
		fieldSet="<fieldset class=\"searchInput\"><legend>" + GC_LABEL["FilterGeom"] + "<br /></legend>";
		fieldSet+="<input type=\"radio\" name=\"query_spatial\" value=\"0\" checked=\"checked\" />";
		fieldSet+="<label for=\"query_spatial\">" + GC_LABEL["FilterExtent"] + "</label><br />";
		fieldSet+="<input type=\"radio\" name=\"query_spatial\" value=\"1\" />";
		fieldSet+="<label for=\"query_spatial\">" + GC_LABEL["FilterCurrentView"] + "</label><br />";
		fieldSet+="<input type=\"radio\"  name=\"query_spatial\" value=\"2\" disabled=\"disabled\" />";
		fieldSet+="<label for=\"query_spatial\">" + GC_LABEL["FilterCurrentSel"] + "</label><br />"
		fieldSet+="<input type=\"radio\"  name=\"query_spatial\" value=\"3\" disabled=\"disabled\" />";
		fieldSet+="<label for=\"query_spatial\">" + GC_LABEL["FilterCurrentObj"] + "</label>&nbsp;";
		fieldSet+="<input type=\"text\"  id=\"query_buffer\" size=\"5\" value=\"0\" disabled=\"disabled\" /><label for=\"query_buffer\">" + GC_LABEL["QueryBuffer"] + "</label><br />";
		fieldSet+="<button type=\"button\" class=\"gc_ButtonSearch\" id=\"gc_ButtonSearchL\" size=\"5\" value=\"" + GC_LABEL["List"] + "\"><img src=\"" + GisClient.BaseUrl + "images/icon_invokescript.gif\" alt=\"" + GC_LABEL["List"] + "\" align=\"left\">&nbsp;" + GC_LABEL["List"] + "</button>";
		fieldSet+="<button type=\"button\" class=\"gc_ButtonSearch\" id=\"gc_ButtonSearchT\" size=\"5\" value=\"" + GC_LABEL["Table"] + "\"><img src=\"" + GisClient.BaseUrl + "images/icon_invokeurl.gif\" alt=\"" + GC_LABEL["Table"] + "\" align=\"left\">&nbsp;" + GC_LABEL["Table"] + "</button>";

		//fieldSet+="<input type=\"button\"  id=\"gc_ButtonAdvancedSearch\" size=\"5\" value=\"" + GC_LABEL["AdvancedSearch"] + "\"  />";
		fieldSet+="</fieldset>";
		$('gisclient_querygeom').set('html',fieldSet);

		$('gc_ButtonSearchL').addEvent('click', function (){this.mode=this.queryMode;this.sendQuery({resultype:1})}.bind(this));
		$('gc_ButtonSearchT').addEvent('click', function (){this.mode=this.queryMode;this.sendQuery({resultype:2})}.bind(this));

	},
	
/*	
	initEditList: function(){
		//Aggiungo sia l'id sel qt sia il tipo di oggetto (da trasformare in jsobject per jx)
		var selOption ="<fieldset class=\"searchInput\"><label for=\"list_gc_edit\">" + GC_LABEL["EditList"] + "<br /></label>";
		selOption+="<select name=\"list_gc_edit\" id=\"list_gc_edit\">";
		for(var i=0;i<this.response.edit.length;i++)
			selOption += "<option value=\"" + this.response.edit[i][0] + ":" + this.response.edit[i][1] + "\" / >" + this.response.edit[i][2] + "</option>";
		selOption += "</select></fieldset>";
		selOption += "<div>" + GC_MESSAGE["EditList"] + "</div>";
		$('gisclient_edit').set('html',selOption);
		$('list_gc_edit').addEvent('change',this.setEdit.bindWithEvent(this));		
	},
	
	initRedline: function(){
	
	},
	
*/
	
	//TODO Sostituire con oggetto indipendente?????????
	initQtTheme: function(){
		if(!$('gisclient_search')) return;
		var selOption="<form name=\"form_gc_search\" id=\"form_gc_search\"><fieldset class=\"searchInput\">";
		selOption+="<label for=\"list_gc_theme\">" + GC_LABEL["ThemeList"] + "<br /></label>";
		selOption+="<select name=\"list_gc_theme\" id=\"list_gc_theme\">";
		var idx=-1;
					
		for(var i=0;i<this.response.qtheme.length;i++){
			if (this.response.qthemeselected == this.response.qtheme[i][0]) idx=i;
			selOption += "<option value=\"" + this.response.qtheme[i][0] + "\" / >" + this.response.qtheme[i][1] + "</option>";
		}
		selOption += "</select>";
		selOption += "<div id=\"div_gc_search_qtname\"></div></fieldset>";
		selOption += "<div id=\"div_gc_search_qtfield\"></div></form>";
		$('gisclient_search').set('html',selOption);
		
		$('list_gc_theme').addEvent('change',this.getQtName.bindWithEvent(this));	
		if(idx > 0)	$('list_gc_theme').selectedIndex = idx;
		if (this.response.qthemeselected) this.queryMode = MODE_SEARCH;
		
		//DA VEDERE PERCHE NON VA SU OPERA
		if(!Browser.Engine.presto)
			$('list_gc_theme').fireEvent('change');

	},
	
	getQtName: function(){
		if(!$('list_gc_theme')) return;
		var val = $('list_gc_theme').getSelected()[0].get('value');	
 		var param = {'mapset':this.mapset,'action':'getQT','qTheme':val,'qtSelected':this.response.qtselected};
		this.post(param);
	},
	
	updateQtName: function(){
		var selOption="<label for=\"list_gc_qt\">" + GC_LABEL["QtList"] + "<br /></label>";
		selOption+="<select name=\"list_gc_qt\" id=\"list_gc_qt\" >";
		var idx=-1;
		if(this.response.qtname.length>0){
			for(var i=0;i<this.response.qtname.length;i++){
				if (this.response.qtname[i][0] == this.response.qtselected) idx=i;
				selOption += "<option value=\"" + this.response.qtname[i][0] + "\" />" + this.response.qtname[i][1] + "</option>";
			}
		}
		selOption += "</select>";
		$('div_gc_search_qtname').set('html',selOption);
		$('list_gc_qt').addEvent('change',this.getQtField.bindWithEvent(this));
		if(idx > 0) $('list_gc_qt').selectedIndex = idx;
		$('list_gc_qt').fireEvent('change');
	},	
	
	getQtField: function(){
		if(!$('list_gc_qt')) return;
		var val = $('list_gc_qt').getSelected()[0].get('value');
 		var param = {'mapset':this.mapset,'action':'getQT','qTname':val};
		this.post(param);
	},
	
	updateQtField: function(){
		if(!$('div_gc_search_qtfield')) return;
		//get radio value
		if($$('input[name=query_spatial]').length > 0)
			var querySpatial = $$('input[name=query_spatial]').filter(function(item) { return item.checked })[0].get('value');
			
		if($$('input[name=query_op]').length > 0)
			var queryOp = $$('input[name=query_op]').filter(function(item) { return item.checked })[0].get('value');

		fieldSet="<fieldset class=\"searchInput\">";
		for(var i=0;i<this.response.qtfield.length;i++){
			var btn='';
			var waitImage='';
			var w=35;

			if(this.response.qtfield[i][2]==TYPE_SUGGEST){				
				waitImage = "<img id=\"img_qf[" + this.response.qtfield[i][0] + "]\" src=\"" + GisClient.BaseUrl + "images/spinner.gif\" class=\"autocompleter-loading\" />";
				btn = "<img class=\"searchImage\" id=\"btn_qf[" + this.response.qtfield[i][0] + "]\" src=\"" + GisClient.BaseUrl + "images/nav_down.gif\" />"; 
				w=30;
			}
			else if(this.response.qtfield[i][2]==TYPE_DATE){
				btn="<img style=\"margin-left:5px\" id=\"btn_qf[" + this.response.qtfield[i][0] + "]\" src=\"" + GisClient.BaseUrl + "images/calendar.gif\" />";
				w=30;
			}	

			fieldSet += "<label class=\"searchLabel\" for=\"qf[" + this.response.qtfield[i][0] + "]\">" + this.response.qtfield[i][1] + waitImage + "</label>"; 
			fieldSet += "<input type=\"text\" id=\"op_qf[" + this.response.qtfield[i][0] + "]\" name=\"query_fields_op\" size=\"1\" value=\"=\" class=\"boolOp\" tabindex=\"-1\" />";
			fieldSet += "<input type=\"text\" id=\"qf[" + this.response.qtfield[i][0] + "]\" name=\"query_fields\" size=\"" + w + "\" />"+btn+"<br />";
		}

		fieldSet+="</fieldset>";
		fieldSet+="<div id=\"gc_advanced_search\"></div>";
		if(this.response.qtfield.length>0){
			fieldSet+="<fieldset class=\"searchInput\"><legend>" + GC_LABEL["QueryOp"] + "<br /></legend>";
			fieldSet+="<input type=\"radio\" name=\"query_op\" value=\"AND\" checked=\"checked\" />";
			fieldSet+="<label for=\"opt_queryop\">" + GC_LABEL["QueryAND"] + "</label>";
			fieldSet+="&nbsp;<input type=\"radio\" name=\"query_op\" value=\"OR\" />";
			fieldSet+="<label for=\"opt_queryop\">" + GC_LABEL["QueryOR"] + "</label></fieldset>";
		};
		$('div_gc_search_qtfield').set('html',fieldSet);
				
		if(this.editTool){
			this.editUrl=this.response.editurl;
			this.layerType=this.response.qlayertype;
			if($chk(this.editUrl)){
				this.editTool.setEnabled(true);
				if(this.editTool.isActive()) this.editTool.fireEvent('click');		
			}
			else{
				this.editTool.setEnabled(false);
				if(this.editTool.isActive()){
					this.selectTools.setActive(true);
					this.selectTools.activeButton.fireEvent('click');
				}
			} 
		}

	
		//set radio value
		$$('input[name=query_spatial]').filter(function(item) { return item.get('value')==querySpatial }).set('checked','checked');
		$$('input[name=query_op]').filter(function(item) { return item.get('value')==queryOp }).set('checked','checked');
		
		//set radio status
		this.setSelectOptionStatus();

		//Instance data and suggest object
		for(var i=0;i<this.response.qtfield.length;i++){
			if(this.response.qtfield[i][2]==TYPE_SUGGEST){

				var objId = "qf[" + this.response.qtfield[i][0] + "]";
				var imgobjId = "img_qf[" + this.response.qtfield[i][0] + "]";
				var btnobjId = "btn_qf[" + this.response.qtfield[i][0] + "]";
				
				//var fval = objIdfilter!=0?$("qf[" + objIdfilter + "]").get('value'):'';
				$(objId).addEvent("dblclick",function(){this.set('value','')});
				// An element as indicator, shown during background request
				//var indicator = $(objId).getPrevious().getElement('.autocompleter-loading');
				//var indicator = $(objId).getPrevious().getElement('.autocompleter-loading');
				//indicator.setStyle('display', 'none');
				var indicator = "img_qf[" + this.response.qtfield[i][0] + "]";
				var idx=this.response.qtfield[i][0];
				var ac = new Autocompleter.Request.JSON(objId,this.options.suggesturl,{
					/*selectMode:'type-ahead',
					
					selectFirst: false,
					filterSubset: true,
						*/
					//overflowMargin: 50,
					'indicator': indicator,
					'minLength': GC_minSuggest,
					'overflow': true,
					'cache':false,//(this.response.qtfield[i][4]==-1),//se c'e il filtro niente cache
					'postVar': 'suggest',
					'onFocus': function (){ 
						if($("qf[" + this.options.postData.filterfield + "]") && this.options.postData.filterfield > 0){
							this.options.postData.filtervalue = $("qf[" + this.options.postData.filterfield + "]").get('value')
						}
					},
					//onSelection
					'postData':{
						mapset:this.mapset,
						qt:$('list_gc_qt').getSelected()[0].get('value'),
						field:this.response.qtfield[i][0],
						filterfield:this.response.qtfield[i][4],
						filtervalue:''}
				});

				$(btnobjId).addEvent("click",function(){
				
					if($("qf[" + this.options.postData.filterfield + "]") && this.options.postData.filterfield > 0)
							this.options.postData.filtervalue = $("qf[" + this.options.postData.filterfield + "]").get('value')
					this.options.minLength=0;
					this.prefetch();
					this.options.minLength=GC_minSuggest;
				}.bind(ac));
				
			}
			else if(this.response.qtfield[i][2]==TYPE_DATE){
				var objId = "qf[" + this.response.qtfield[i][0] + "]";
				var btnobjId = "btn_qf[" + this.response.qtfield[i][0] + "]";
				var dt = new vlaDatePicker(objId,{ 
					style: 'adobe_cs3', 
					offset: { y: 1 },
					format: 'd/m/y', 
					alignX: 'center',
					prefillDate: false,
					weekDayLabels: GC_LABEL["weekDayLabels"],
					monthLabels: GC_LABEL["monthLabels"],
					monthSmallLabels: GC_LABEL["monthSmallLabels"],
					ieTransitionColor: '' 
					});
	
				$(btnobjId).addEvent("click",function(){
					this.show()
				}.bind(dt));
			}	
			
			var objId = "op_qf[" + this.response.qtfield[i][0] + "]";
			$(objId).addEvent("dblclick",function(){this.set('value','')});
			new Autocompleter.Local(objId, boolOp, {
				'width':100,
				'selectMode':'pick',
				'minLength': 0
			});
		}
	},
	
	getErrorMessage: function(message) {
    
		var d2 = new Jx.Dialog({
	        label: 'Errore nella restituzione della query',
	        image: GisClient.BaseUrl + 'images/warning.png',
	        modal: true, 
	        width: 800, 
	        height: 600, 
	        content: message, 
	        resize: true
		}).open()
    
	},
	
	
	setSelectOptionStatus: function(){
	
		var opt = $$('input[name=query_spatial]').filter(function(item) { return item.get('value')==2 }).set('disabled',this.enableCurrentSelection ? '' : 'disabled');
		if(!opt[0]) return;
		if(opt[0].checked && !this.enableCurrentSelection)
			$$('input[name=query_spatial]').filter(function(item) { return item.get('value')==1 }).set('checked','checked');		
		
		var opt = $$('input[name=query_spatial]').filter(function(item) { return item.get('value')==3 }).set('disabled',this.enableObjectSelection ? '' : 'disabled');
		if(opt[0].checked && !this.enableObjectSelection)
			$$('input[name=query_spatial]').filter(function(item) { return item.get('value')==1 }).set('checked','checked');		
		
		$('query_buffer').set('disabled',this.enableObjectSelection ? '' : 'disabled');

	},
	
	
	
	getPrintDialog: function(){

		var prtable="<form name=\"GC_printForm\" id=\"GC_printForm\"><fieldset class=\"searchInput\">";
		
		var v = this.response.printsize;
		if (v && v.length > 0){
			prtable+="<legend>" + GC_LABEL["PrintTitle"] + "<br /></legend>";
			prtable+="<input type=\"text\" name=\"GC_printTitle\" id=\"GC_prTitle\" value=\"\" size=\"40\"/></fieldset>";
			prtable+="<fieldset class=\"searchInput\"><legend>" + GC_LABEL["PrintScale"] + "<br /></legend>";
			prtable+="1: <input type=\"text\" name=\"GC_printScale\" id=\"GC_printScale\" value=\"\" size=\"12\"/></fieldset>";

			prtable+="<fieldset class=\"searchInput\"><legend>" + GC_LABEL["PrintLayout"] + "<br /></legend>";
			prtable+="<input type=\"radio\" name=\"GC_printLayout\" value=\"L\" checked=\"checked\" />";
			prtable+="<label for=\"GC_printLayout\">" + GC_LABEL["PrintLayoutL"] + "&nbsp;&nbsp;</label>"; 
			prtable+="<input type=\"radio\" name=\"GC_printLayout\" value=\"P\" />";
			prtable+="<label for=\"GC_printLayout\">" + GC_LABEL["PrintLayoutP"] + "</label></fieldset>"; 
			prtable+="<fieldset class=\"searchInput\"><legend>" + GC_LABEL["PrintSize"] + "<br /></legend>";
			var chk="checked=\"checked\"";
			for(var i=0;i<v.length;i++){
				prtable+="<input type=\"radio\" name=\"GC_printFormat\" value=\"" + v[i].trim() + "\" " + chk + " />";
				prtable+="<label for=\"GC_printFormat\">" + v[i].trim() + "&nbsp;&nbsp;</label>";
				chk='';
			}
			prtable+="</fieldset>";
			prtable+="<fieldset class=\"searchInput\"><legend>" + GC_LABEL["PrintLegend"] + "<br /></legend>";
			prtable+="<input type=\"radio\" name=\"GC_printLegend\" value=\"0\" checked=\"checked\" />";
			prtable+="<label for=\"GC_printLegend\">" + GC_LABEL["noLegend"] + "<br /></label>"; 
			//prtable+="<input type=\"radio\" name=\"legend\" value=\"1\" />legenda inclusa nella pagina<br />";
			prtable+="<input type=\"radio\" name=\"GC_printLegend\" value=\"2\" />";
			prtable+="<label for=\"GC_printLegend\">" + GC_LABEL["newPageLegend"] + "<br /></label>"; 
			prtable+="<br /><input id=\"gisclient_buttonPrint\" type=\"button\" value=\"" + GC_LABEL["btnPrintMap"] + "\" size=\"20\" />";		
			
		}
		else
			prtable+="<legend>" + GC_LABEL["NoPrintSize"] + "<br /></legend>";
		prtable+="</fieldset></form>";	
		return prtable;
	},
	
	getDownloadDialog: function(){
		var dlImage="<form name=\"GC_dlImage\" id=\"GC_dlImage\"><fieldset class=\"searchInput\">";
		dlImage+="<legend>" + GC_LABEL["DownloadRes"] + "<br /></legend>";
		var v = this.response.imageres;
		if (v && v.length > 0){
			var chk="checked=\"checked\"";
			for(var i=0;i<v.length;i++){
				dlImage+="<input type=\"radio\" name=\"GC_imgDpi\" value=\"" + v[i].trim() + "\" " + chk + " />";
				dlImage+="<label for=\"GC_imgDpi\">" + v[i].trim() + " dpi<br /></label>"; 
				chk='';
			}				
			dlImage+="<input type=\"text\" id=\"GC_dlImgRatio\" size=\"2\" value=\"1\" /><label for=\"GC_dlImgRatio\">" + GC_LABEL["dlRatio"] + "</label><br />";
			dlImage+="<br /><input type=\"checkbox\" id=\"GC_gtiffFormat\" />";
			dlImage+="<label for=\"GC_gtiffFormat\">" + GC_LABEL["GeoTiffFormat"] + "<br /></label><br />";
			//dlImage+=" <label for=\"GC_dlImgHeight\">H </label><input type=\"text\" id=\"GC_dlImgHeight\" size=\"6\" />";
			dlImage+="<input id=\"gisclient_buttonDlImage\" type=\"button\" value=\" " + GC_LABEL["btnDownloadImage"] + " \" size=\"20\" />";
		}
		else
			dlImage+="<legend>" + GC_LABEL["NoDownloadRes"] + "<br /></legend>";
		dlImage+="</fieldset></form>";		
		return dlImage;
	},

	
	showInfo: function(){
	
		if(!this.response.template) this.response.template='templateInfo.html';//TEST
		//verifico se il tab delle info è aperto

		//if((this.response.queryresult.length > 0 && this.response.queryresult[0].istable) || this.response.resultype==2 || GC_optionResultWin ){
		if(this.response.resultype==2){

			//var winparam='resizable=yes,width=800,height=400,status=no,location=no,toolbar=no,scrollbars=yes';
			//var myWin=window.open(this.response.template,'infoTable',winparam);	
			//$("tableData").set('html',setPageInfo(this.response.queryresult,this.mapset));
			//this.tabBox.tabSet.remove(tabInfo);
			
			//CAMBIARE LA DIMENSIONE DELLA FINESTRA (VEDI OPENPOPUP)
			
			this.infoDialog.setContent(setPageInfo(this.response.queryresult,this.mapset));
		//	$("tableData").set('html',setPageInfo(this.response.queryresult,this.mapset));
			this.infoDialog.open();

			
			//myWin.focus();
		}
		
		else{
			var tabInfo = this.tabBox.tabSet.tabs.filter(function(item,index){return item.options.label == GC_LABEL["infoTable"]})[0]	
			if($chk(tabInfo)){
				tabInfo.content.set('html',setTabInfo(this.response.queryresult,this.mapset));
				tabInfo.setActive(true);
			}else{
				this.tabBox.add(
		           new Jx.Button.Tab({
		                label:GC_LABEL["infoTable"],
		                active: true,
		                close: true,
						//onClose:function (){alert('chiuso')},
						image: GisClient.BaseUrl + 'images/table.png',
		                content: setTabInfo(this.response.queryresult,this.mapset)
		            })
				);
			}
		}
		
		//empty query fields
		/*
		if(GC_optionCleanForm){
			var queryFields = $$('input[name=query_fields]').filter(function(item) { return item.get('value') });
			if(queryFields.length>0){
				queryFields.each(function(el) {
					$('op_' + el.get('id')).set('value','=');
					$(el.get('id')).set('value','');
				});
			}
		}
		*/
		this.setBusy(false);
	
	},
	
	openInfoDialog: function (param){
		//var winparam='resizable=yes,width=800,height=400,status=no,location=no,toolbar=no,scrollbars=yes';
		//var myWin=window.open(InfoTable + '?' + param,'infoTable',winparam);			
		//myWin.focus();
		


	},
	
	sendQuery: function (obj){

	/*Metodo sendQuery: posso passare anche i parametri in modo da fare il merge con i parametri della applicazione per chiamate da fuori*/
		var cmd=['map','select','search','edit','redline'];
		
		var queryTemplate = $chk($('list_gc_qt'))?$('list_gc_qt').getSelected()[0].get('value'):0;
		var selGroup =  $chk($('list_gc_selgroup'))?$('list_gc_selgroup').getSelected()[0].get('value'):0;
		var queryFields = ($$('input[name=query_fields]').length>0)?$$('input[name=query_fields]').filter(function(item) { return item.get('value') }):0;
		var bufferSelected = $chk($('query_buffer'))?$('query_buffer').value:0;
		var querySpatial = ($$('input[name=query_spatial]').length>0)?$$('input[name=query_spatial]').filter(function(item) { return item.checked })[0].get('value'):0;
		
		var param={
			'mapset' : this.mapset,
			'action': 'info',
			'mode': cmd[this.mode],
			'item': (this.mode == MODE_SEARCH)? queryTemplate : selGroup,		
			'imageWidth': this.map.getWidth(),
			'imageHeight': this.map.getHeight(),
			'selectMode':GC_optionSelect,//contenuto o intersecato
			'resultAction':GC_optionAction,//cosa faccio sulla mappa (niente selezione selezione+zoom/
			'resultype':GC_optionResultWin?2:1,//dove spedisco il risultato (info tabella)
			'oXgeo':this.oXgeo,
			'oYgeo':this.oYgeo,
			'geoPix':this.geoPixel,
			'spatialQuery' : querySpatial,
			'bufferSelected': bufferSelected
		};
	
		if($chk(obj) && obj.resultype){
			param['resultype']=obj.resultype;
		}
		
		if($chk(obj) && obj.item){
			param['item']=obj.item;
			if(obj.objid) param['zoomobj']=obj.objid; 
			param['mode']='search';
			param['spatialQuery']=2;
			param['resultype']=1;
		}
		
		//Paging
		if($chk(obj) && obj.paging){
			//param['mode']='table';
			param['spatialQuery']=2;
			param['pageIndex']=obj.pageIndex;
			param['totalRows']=obj.totalRows;
		}
		
		//Tabella csv o pdf
		if($chk(obj) && obj.printTable){
			//param['mode']='table';
			param['spatialQuery']=2;
			param['resultype']=2;
			param['mode']='search';
			param['item']=obj.qt;
			
			if(obj.destination){
				param['destination']=obj.destination;
				param['allpage']=1;
				param['printTable']=1;
			}
			
			param['objid']=obj.parentId;
			
			if(obj.relation) {
			
				param['relation']=obj.relation;
				param['mode']='table';
			}
			
		}
		
		if(this.mode==MODE_WMS){
			param['action'] = 'infowms';
			var index = $('list_gc_wms').getSelected()[0].get('value');	
			var infoWMS = this.wmsList[index];
			param['item'] =  infoWMS.layername;
			param['grpid'] =  infoWMS.grpid;
			param['title'] =  infoWMS.title;
		}
		
		//Se interrogo la secondaria in obj ho le informazioni di chiamato altrimenti la geometria 	
		if($chk(obj) && obj.relation){//chiamata tabella secondaria
			param['mode'] = 'table';
			param['resultable'] = true;	
			param['resultype']=2;			
			param['item'] = obj.qt;
			param['relation'] = obj.relation;
			if(obj.idList) param['objid'] = obj.idList.join(',');
			
		} else if($chk(obj) && obj.type){	//define geometry filter
	
			if(obj.type=='point')
				var querySpatial=5;
			else if(obj.type=='polygon')
				querySpatial=6;
			else if(obj.type=='circle')
				querySpatial=7;		
			param['imgX'] = obj.X;
			param['imgY'] = obj.Y;
			param['imgA'] = obj.A;
			param['imgR'] = obj.R;
			param['spatialQuery'] = querySpatial;
		}

		
		//query fields
		if(queryFields.length>0 && (querySpatial < 6 || (querySpatial > 5 && GC_optionUseForm))){
			queryFields.each(function(el) {
				param['op_' + el.get('id')] = $('op_' + el.get('id')).get('value');
				param[el.get('id')] = $(el.get('id')).get('value');
			});
			param['queryOp'] = $$('input[name=query_op]').filter(function(item) { return item.checked })[0].get('value');
		}
		
		
		this.setBusy(true); 
		this.post.delay(1000, this, param);

		//setTimeout("this.post(param)",100);

	}
		
});   


//CONSTANTS:
var MODE_MAP = 0;
var MODE_SELECT = 1;
var MODE_SEARCH = 2;
var MODE_EDIT = 3;
var MODE_REDLINE = 4;
var MODE_MEASURE = 5;
var MODE_WMS = 6;

var ACTION_NO_ACTION = 0;
var ACTION_SELECT = 1;
var ACTION_SELECT_ZOOM = 2;
var ACTION_SELECT_CENTER = 3;
var SELECT_INTERSECT = 0;
var SELECT_WITHIN = 1;

var DRAW_BOX = 1;
var DRAW_POINT = 2;
var DRAW_LINE = 3;
var DRAW_POLYGON = 4;
var DRAW_CIRCLE = 5;
var DRAW_PEN = 6;
var DRAW_PEN_CLOSE = 7;
var PICK_POINT = 33;
var ZOOM_BOX = 8;
var ZOOM_OUT = 9;
var REF_BOX = 10;
var PAN = 11;
var TYPE_SUGGEST = 3;
var TYPE_DATE = 5;

//GLOBAL_OPTIONS
var GC_optionResultWin;
var GC_optionUseForm;
var GC_optionSelect;
var GC_optionAction;

//function numberFormat
function numberFormat(input,decimals, dec_point, thousands_sep) {
	decimals = Math.abs(decimals) + 1 ? decimals : 0;
	dec_point = dec_point || ',';
	thousands_sep = thousands_sep || '.';
	var matches = /(-)?(\d+)(\.\d+)?/.exec((isNaN(input) ? 0 : input) + ''); // returns matches[1] as sign, matches[2] as numbers and matches[2] as decimals
	var remainder = matches[2].length > 3 ? matches[2].length % 3 : 0;
	return (matches[1] ? matches[1] : '') + (remainder ? matches[2].substr(0, remainder) + thousands_sep : '') + matches[2].substr(remainder).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep) + (decimals ? dec_point + (+matches[3] || 0).toFixed(decimals).substr(2) : '');
}

