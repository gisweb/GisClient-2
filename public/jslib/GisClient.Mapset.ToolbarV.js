GisClient.setToolbarV = function (anchor) {

	    var b1 = new Jx.Button({label:'butt 1', toggle:true});
	    var b2 = new Jx.Button({label:'butt 2', toggle:true});
	    var b3 = new Jx.Button({label:'butt 3', toggle:false,
		onClick: function(){
			//var vv=this.tabBox.tabSet.tabs.filter(function(item,index){return item.options.label == GC_LABEL["results"]})
			
			alert(GisClient.MapSet);
			
			}
		
		
		});
	    var b4 = new Jx.Button({
			label:'Test',
			onClick: function(){
			//var vv=this.tabBox.tabSet.tabs.filter(function(item,index){return item.options.label == GC_LABEL["results"]})
			
			alert(vv.content);
			
			}.bind(this)});
	    
	    new Jx.ButtonSet().add(b1,b2,b3);
	    
	    new Jx.Toolbar({
	        parent:anchor,
	        position: 'right'
	    }).add(b1,b2,b3,b4);

	}