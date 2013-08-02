GisClient.Reference = new Class({

	initialize: function(refDiv,app) { 
 	
		this.element = $(refDiv);	
		this.owner = app;
		//this.zoomAction = true;

		//if(!refW) refW = this.element.getSize().x;
		//this.element.setStyles({'height':refH});
		this.refW = this.element.getSize().x;
		this.refH = this.element.getSize().y;
		
		//this.refDiv = new Element('div',{'id':'refmap','styles': {position:'absolute','left':this.element.getPosition().x,'top':this.element.getPosition().y,'width':refW,'height':refH}});
		this.refDiv = new Element('div',{'id':'refmap','styles': {position:'absolute','left':0,'top':0,'width':this.refW,'height':this.refH}});
		this.element.appendChild(this.refDiv);

		this.refBox = new Element('div',{'id':'refbox'});
		this.refDiv.appendChild(this.refBox);

	
		new Drag.Move('refbox', {
			'container':this.refDiv,
			onComplete: function(el){
				var styles = el.getStyles('width', 'height', 'left', 'top');
				var x = styles.left.toInt() + parseInt(styles.width.toInt()/2);
				var y = styles.top.toInt() + parseInt(styles.height.toInt()/2);
				//if(this.zoomAction) this.owner.zoomRef(x,y);
				this.owner.zoomRef(x,y);
				}.bind(this)

			}); 
			

    }, 

	setRefMap: function(imageUrl){
		if(imageUrl && imageUrl.lastIndexOf('images/reference')!=-1) imageUrl = GisClient.BaseUrl + imageUrl;
		this.refDiv.setStyles({'background-image':'url(' + imageUrl + ')','background-repeat':'no-repeat','background-position':'center left','width':this.element.getSize().x,'height':this.element.getSize().y})
	},

	setRefBox: function(refBox){
		if(!refBox) return;
		var left = refBox[0];
		var top = refBox[1];
		var width = (left + refBox[2])>this.refW?this.refW - left:refBox[2];
		var height =  (top + refBox[3])>this.refH?this.refH - top:refBox[3];
		this.refBox.setStyles({'left':left,'top':top,'width':width,'height':height})
	}


});  