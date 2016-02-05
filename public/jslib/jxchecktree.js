/******************************************************************************
 * MooTools 1.2.2
 * Copyright (c) 2006-2007 [Valerio Proietti](http://mad4milk.net/).
 * MooTools is distributed under an MIT-style license.
 ******************************************************************************
 * reset.css - Copyright (c) 2006, Yahoo! Inc. All rights reserved.
 * Code licensed under the BSD License: http://developer.yahoo.net/yui/license.txt
 ******************************************************************************
 * Jx UI Library, 2.0.1
 * Copyright (c) 2006-2008, DM Solutions Group Inc. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *****************************************************************************/
// $Id: common.js 423 2009-05-12 12:37:56Z pagameba $
/**
 * Class: Jx
 * Jx is a global singleton object that contains the entire Jx library
 * within it.  All Jx functions, attributes and classes are accessed
 * through the global Jx object.  Jx should not create any other
 * global variables, if you discover that it does then please report
 * it as a bug
 *
 * License: 
 * Copyright (c) 2008, DM Solutions Group Inc.
 * 
 * This file is licensed under an MIT style license
 */

 
 
 
 
 // $Id: treeitem.js 423 2009-05-12 12:37:56Z pagameba $
/**
 * Class: Jx.TreeItem 
 *
 * Extends: Object
 *
 * Implements: Options, Events
 *
 * An item in a tree.  An item is a leaf node that has no children.
 *
 * Jx.TreeItem supports selection via the click event.  The application 
 * is responsible for changing the style of the selected item in the tree
 * and for tracking selection if that is important.
 *
 * Example:
 * (code)
 * (end)
 *
 * Events:
 * click - triggered when the tree item is clicked
 *
 * Implements:
 * Events - MooTools Class.Extras
 * Options - MooTools Class.Extras
 *
 * License: 
 * Copyright (c) 2008, DM Solutions Group Inc.
 * 
 * This file is licensed under an MIT style license
 */
Jx.CHKTreeItem = new Class ({
    Family: 'Jx.CHKTreeItem',
    Implements: [Options,Events],
    /**
     * Property: domObj
     * {HTMLElement} a reference to the HTML element that is the TreeItem
     * in the DOM
     */
    domObj : null,
    /**
     * Property: owner
     * {Object} the folder or tree that this item belongs to
     */
    owner: null,
    options: {
        /* Option: label
         * {String} the label to display for the TreeItem
         */        
        label: '',
        /* Option: data
         * {Object} any arbitrary data to be associated with the TreeItem
         */
        data: null,
        /* Option: contextMenu
         * {<Jx.ContextMenu>} the context menu to trigger if there
         * is a right click on the node
         */
        contextMenu: null,
        /* Option: enabled
         * {Boolean} the initial state of the TreeItem.  If the 
         * TreeItem is not enabled, it cannot be clicked.
         */
        enabled: true,
        type: 'Item',
        /* Option: image
         * {String} URL to an image to use as the icon next to the
         * label of this TreeItem
         */
        image: null,
        /* Option: imageClass
         * {String} CSS class to apply to the image, useful for using CSS
         * sprites
         */
        imageClass: '',
		
	/*******************************************/
	/*Added by Roberto Starnini for checkTree*/
		control:null,
		delayTime:5000,
		checked:false
	/*******************************************/
		
    },
    /**
     * Constructor: Jx.TreeItem
     * Create a new instance of Jx.TreeItem with the associated options
     *
     * Parameters:
     * options - <Jx.TreeItem.Options>
     */
    initialize : function( options ) {
        this.setOptions(options);

        this.domObj = new Element('li', {'class':'jxTree'+this.options.type});
        if (this.options.id) {
            this.domObj.id = this.options.id;
        }
      
        this.domNode = new Element('img',{
            'class': 'jxTreeImage', 
            src: Jx.aPixel.src,
            alt: '',
            title: ''
        });
        this.domObj.appendChild(this.domNode);
        
        this.domLabel = (this.options.draw) ? 
            this.options.draw.apply(this) : 
            this.draw();

        this.domObj.appendChild(this.domLabel);
        this.domObj.store('jxTreeItem', this);
        
        if (!this.options.enabled) {
            this.domObj.addClass('jxDisabled');
        }
    },
    draw: function() {
        var domImg = new Element('img',{
            'class':'jxTreeIcon', 
            src: Jx.aPixel.src,
            alt: '',
            title: ''
        });
        if (this.options.image) {
            domImg.setStyle('backgroundImage', 'url('+this.options.image+')');
        }
        if (this.options.imageClass) {
            domImg.addClass(this.options.imageClass);
        }
		
		/*******************************************/
		//chktree added by Roberto Starnini
		if(this.options.control){
			this.domControl=new Element('input',{
				'class':'jxTreeCheck',
				'name':this.options.name,
				'type':this.options.control,
				'checked':this.options.checked,
				events: {
	                click: this.selected.bindWithEvent(this)
	            }
			});	
			this.domObj.appendChild(this.domControl);	
		}
		/*******************************************/
		
        // the clickable part of the button
        var hasFocus;
        var mouseDown;
        
        var domA = new Element('a',{
            href:'javascript:void(0)',
            html: this.options.label
        });
        domA.addEvents({
            click: this.selected.bind(this),
            dblclick: this.selected.bind(this),
            drag: function(e) {e.stop();},
            contextmenu: function(e) { e.stop(); },
            mousedown: (function(e) {
               domA.addClass('jxTreeItemPressed');
               hasFocus = true;
               mouseDown = true;
               domA.focus();
               if (e.rightClick && this.options.contextMenu) {
                   this.options.contextMenu.show(e);
               }
            }).bind(this),
            mouseup: function(e) {
                domA.removeClass('jxTreeItemPressed');
                mouseDown = false;
            },
            mouseleave: function(e) {
                domA.removeClass('jxTreeItemPressed');
            },
            mouseenter: function(e) {
                if (hasFocus && mouseDown) {
                    domA.addClass('jxTreeItemPressed');
                }
            },
            keydown: function(e) {
                if (e.key == 'enter') {
                    domA.addClass('jxTreeItemPressed');
                }
            },
            keyup: function(e) {
                if (e.key == 'enter') {
                    domA.removeClass('jxTreeItemPressed');
                }
            },
            blur: function() { hasFocus = false; }
        });
        domA.appendChild(domImg);
        if (typeof Drag != 'undefined') {
            new Drag(domA, {
                onStart: function() {this.stop();}
            });
        }
        return domA;
    },
    /**
     * Method: finalize
     * Clean up the TreeItem and remove all DOM references
     */
    finalize: function() { this.finalizeItem(); },
    /**
     * Method: finalizeItem
     * Clean up the TreeItem and remove all DOM references
     */
    finalizeItem: function() {  
        if (!this.domObj) {
            return;
        }
        //this.domA.removeEvents();
        this.options = null;
        this.domObj.dispose();
        this.domObj = null;
        this.owner = null;
    },
    /**
     * Method: clone
     * Create a clone of the TreeItem
     * 
     * Returns: 
     * {<Jx.TreeItem>} a copy of the TreeItem
     */
    clone : function() {
        return new Jx.TreeItem(this.options);
    },
    /**
     * Method: update
     * Update the CSS of the TreeItem's DOM element in case it has changed
     * position
     *
     * Parameters:
     * shouldDescend - {Boolean} propagate changes to child nodes?
     */
    update : function(shouldDescend) {
        var isLast = (arguments.length > 1) ? arguments[1] : 
                     (this.owner && this.owner.isLastNode(this));
        if (isLast) {
            this.domObj.removeClass('jxTree'+this.options.type);
            this.domObj.addClass('jxTree'+this.options.type+'Last');
        } else {
            this.domObj.removeClass('jxTree'+this.options.type+'Last');
            this.domObj.addClass('jxTree'+this.options.type);
        }
    },
    /**
     * Method: selected
     * Called when the DOM element for the TreeItem is clicked, the
     * node is selected.
     *
     * Parameters:
     * e - {Event} the DOM event
  
    selected : function(e) {
        this.fireEvent('click', this);
    },
   */	
	/*******************************************/
	//Modificato Roberto Starnini
	selected : function(e) {
		//Ancora qualche problema RIVEDERE!!!!!(non setta a false il radio va bene anche così???)
		if( e.target.type=='radio'){// devo settare tutti i controlli
			for (var i=0; i<this.owner.nodes.length; i++){
				if(this.owner.nodes[i].options.control=='radio'){
					this.owner.nodes[i].options.checked=false;
					this.owner.nodes[i].domControl.checked=false
				}
			}
			
		}
		this.options.checked = (!this.options.checked);
		if(e.target.type!='checkbox')
			this.domControl.checked =(!this.domControl.checked);	
		
		var allchecked=false;
		for (var i=0; i<this.owner.nodes.length; i++)
        {
            if(this.owner.nodes[i].domControl.checked) allchecked=true;
        }

		this.owner.domControl.checked=allchecked;
		$clear(myTimer);
		myTimer = this.owner.owner.itemUpdated.delay(this.options.delayTime,this.owner.owner); 
    },
	
			
	/*******************************************/
	
    /**
     * Method: getName
     * Get the label associated with a TreeItem
     *
     * Returns: 
     * {String} the name
     */
    getName : function() { return this.options.label; },
    /**
     * Method: propertyChanged
     * A property of an object has changed, synchronize the state of the 
     * TreeItem with the state of the object
     *
     * Parameters:
     * obj - {Object} the object whose state has changed
     */
    propertyChanged : function(obj) {
        this.options.enabled = obj.isEnabled();
        if (this.options.enabled) {
            this.domObj.removeClass('jxDisabled');
        } else {
            this.domObj.addClass('jxDisabled');
        }
    }
});
// $Id: treefolder.js 423 2009-05-12 12:37:56Z pagameba $
/**
 * Class: Jx.TreeFolder
 * 
 * Extends: <Jx.TreeItem>
 *
 * A Jx.TreeFolder is an item in a tree that can contain other items.  It is
 * expandable and collapsible.
 *
 * Example:
 * (code)
 * (end)
 *
 * Extends:
 * <Jx.TreeItem>
 *
 * License: 
 * Copyright (c) 2008, DM Solutions Group Inc.
 * 
 * This file is licensed under an MIT style license
 */
Jx.CHKTreeFolder = new Class({
    Family: 'Jx.CHKTreeFolder',
    Extends: Jx.CHKTreeItem,
    /**
     * Property: subDomObj
     * {HTMLElement} an HTML container for the things inside the folder
     */
    subDomObj : null,
    /**
     * Property: nodes
     * {Array} an array of references to the javascript objects that are
     * children of this folder
     */
    nodes : null,

    options: {
        /* Option: open
         * is the folder open?  false by default.
         */
        open : false
    },
    /**
     * Constructor: Jx.TreeFolder
     * Create a new instance of Jx.TreeFolder
     *
     * Parameters:
     * options - <Jx.TreeFolder.Options> and <Jx.TreeItem.Options>
     */
    initialize : function( options ) {
        this.parent($merge(options,{type:'Branch'}));

        $(this.domNode).addEvent('click', this.clicked.bindWithEvent(this));
        this.addEvent('click', this.clicked.bindWithEvent(this));
                
        this.nodes = [];
        this.subDomObj = new Element('ul', {'class':'jxTree'});
        this.domObj.appendChild(this.subDomObj);
        if (this.options.open) {
            this.expand();
        } else {
            this.collapse();
        }
    },
    /**
     * Method: finalize
     * Clean up a TreeFolder.
     */
    finalize: function() {
        this.finalizeFolder();
        this.finalizeItem();
        this.subDomObj.dispose();
        this.subDomObj = null;
    },
    /**
     * Method: finalizeFolder
     * Internal method to clean up folder-related stuff.
     */
    finalizeFolder: function() {
        this.domObj.childNodes[0].removeEvents();
        for (var i=this.nodes.length-1; i>=0; i--) {
            this.nodes[i].finalize();
            this.nodes.pop();
        }
        
    },
    
    /**
     * Method: clone
     * Create a clone of the TreeFolder
     * 
     * Returns: 
     * {<Jx.TreeFolder>} a copy of the TreeFolder
     */
    clone : function() {
        var node = new Jx.CHKTreeFolder(this.options);
        this.nodes.each(function(n){node.append(n.clone());});
        return node;
    },
    /**
     * Method: isLastNode
     * Indicates if a node is the last thing in the folder.
     *
     * Parameters:
     * node - {Jx.TreeItem} the node to check
     *
     * Returns:
     *
     * {Boolean}
     */
    isLastNode : function(node) {
        if (this.nodes.length == 0) {
            return false;
        } else {
            return this.nodes[this.nodes.length-1] == node;
        }
    },
    /**
     * Method: update
     * Update the CSS of the TreeFolder's DOM element in case it has changed
     * position.
     *
     * Parameters:
     * shouldDescend - {Boolean} propagate changes to child nodes?
     */
    update : function(shouldDescend) {
        /* avoid update if not attached to tree yet */
        if (!this.parent) return;
        var isLast = false;
        if (arguments.length > 1) {
            isLast = arguments[1];
        } else {
            isLast = (this.owner && this.owner.isLastNode(this));
        }
        
        var c = 'jxTree'+this.options.type;
        c += isLast ? 'Last' : '';
        c += this.options.open ? 'Open' : 'Closed';
        this.domObj.className = c;
        
        if (isLast) {
            this.subDomObj.className = 'jxTree';
        } else {
            this.subDomObj.className = 'jxTree jxTreeNest';
        }
        
        if (this.nodes && shouldDescend) {
            var that = this;
            this.nodes.each(function(n,i){
                n.update(false, i==that.nodes.length-1);
            });
        }
		
		/*******************************************/
		//Modificato Roberto Starnini
		if (this.options.enabled) {
            this.domObj.removeClass('jxDisabled');
			this.domControl.disabled=false;
        } else {
            this.domObj.addClass('jxDisabled');
			this.domControl.disabled=true;
		}
		/*******************************************/
		
    },
		
	
    /**
     * Method: append
     * append a node at the end of the sub-tree
     *
     * Parameters:
     * node - {Object} the node to append.
     */
    append : function( node ) {
        node.owner = this;
        this.nodes.push(node);
        this.subDomObj.appendChild( node.domObj );
        this.update(true);
        return this;
    },
    /**
     * Method: insert
     * insert a node after refNode.  If refNode is null, insert at beginning
     *
     * Parameters:
     * node - {Object} the node to insert
     * refNode - {Object} the node to insert before
     */
    insert : function( node, refNode ) {
        node.owner = this;
        //if refNode is not supplied, insert at the beginning.
        if (!refNode) {
            this.nodes.unshift(node);
            //sanity check to make sure there is actually something there
            if (this.subDomObj.childNodes.length ==0) {
                this.subDomObj.appendChild(node.domObj);
            } else {
                this.subDomObj.insertBefore(node.domObj, this.subDomObj.childNodes[0]);                
            }
        } else {
            //walk all nodes looking for the ref node.  Track if it actually
            //happens so we can append if it fails.
            var b = false;
            for(var i=0;i<this.nodes.length;i++) {
                if (this.nodes[i] == refNode) {
                    //increment to append after ref node.  If this pushes us
                    //past the end, it'll get appended below anyway
                    i = i + 1;
                    if (i < this.nodes.length) {
                        this.nodes.splice(i, 0, node);
                        this.subDomObj.insertBefore(node.domObj, this.subDomObj.childNodes[i]);
                        b = true;
                        break;
                    }
                }
            }
            //if the node wasn't inserted, it is because refNode didn't exist
            //and so the fallback is to just append the node.
            if (!b) {
                this.nodes.push(node); 
                this.subDomObj.appendChild(node.domObj); 
            }
        }
        this.update(true);
        return this;
    },
    /**
     * Method: remove
     * remove the specified node from the tree
     *
     * Parameters:
     * node - {Object} the node to remove
     */
    remove : function(node) {
        node.owner = null;
        for(var i=0;i<this.nodes.length;i++) {
            if (this.nodes[i] == node) {
                this.nodes.splice(i, 1);
                this.subDomObj.removeChild(this.subDomObj.childNodes[i]);
                break;
            }
        }
        this.update(true);
        return this;
    },
    /**
     * Method: replace
     * Replace a node with another node
     *
     * Parameters:
     * newNode - {Object} the node to put into the tree
     * refNode - {Object} the node to replace
     *
     * Returns:
     * {Boolean} true if the replacement was successful.
     */
    replace: function( newNode, refNode ) {
        //walk all nodes looking for the ref node. 
        var b = false;
        for(var i=0;i<this.nodes.length;i++) {
            if (this.nodes[i] == refNode) {
                if (i < this.nodes.length) {
                    newNode.owner = this;
                    this.nodes.splice(i, 1, newNode);
                    this.subDomObj.replaceChild(newNode.domObj, refNode.domObj);
                    return true;
                }
            }
        }
        return false;
    },
    
    /**
     * Method: clicked
     * handle the user clicking on this folder by expanding or
     * collapsing it.
     *
     * Parameters: 
     * e - {Event} the event object
     */
    clicked : function(e) {
        if (this.options.open) {
            this.collapse();
        } else {
            this.expand();
        }
    },
	
	
	/*******************************************/
	//Modificato Roberto Starnini
	selected : function(e) {

	    for (var i=0; i<this.nodes.length; i++)
        {
            this.nodes[i].domControl.checked=this.domControl.checked;
			this.nodes[i].options.checked=this.domControl.checked;
        }
		if(typeof(e)=='object' && e.target.type=='checkbox' ){
			$clear(myTimer);
			myTimer = this.owner.itemUpdated.delay(this.options.delayTime,this.owner);
		}
    },
	/*******************************************/
	
    /**
     * Method: expand
     * Expands the folder
     */
    expand : function() {
        this.options.open = true;
        this.subDomObj.setStyle('display', 'block');
        this.update(true);
        this.fireEvent('disclosed', this);    
    },
    /**
     * Method: collapse
     * Collapses the folder
     */
    collapse : function() {
        this.options.open = false;
        this.subDomObj.setStyle('display', 'none');
        this.update(true);
        this.fireEvent('disclosed', this);
    },
    /**
     * Method: findChild
     * Get a reference to a child node by recursively searching the tree
     * 
     * Parameters:
     * path - {Array} an array of labels of nodes to search for
     *
     * Returns:
     * {Object} the node or null if the path was not found
     */
    findChild : function(path) {
        //path is empty - we are asking for this node
        if (path.length == 0)
            return this;
        
        //path has only one thing in it - looking for something in this folder
        if (path.length == 1)
        {
            for (var i=0; i<this.nodes.length; i++)
            {
                if (this.nodes[i].getName() == path[0])
                    return this.nodes[i];
            }
            return null;
        }
        //path has more than one thing in it, find a folder and descend into it    
        var childName = path.shift();
        for (var i=0; i<this.nodes.length; i++)
        {
            if (this.nodes[i].getName() == childName && this.nodes[i].findChild)
                return this.nodes[i].findChild(path);
        }
        return null;
    }
});// $Id: tree.js 423 2009-05-12 12:37:56Z pagameba $
/**
 * Class: Jx.Tree
 *
 * Extends: Jx.TreeFolder
 *
 * Implements: <Jx.Addable>
 *
 * Jx.Tree displays hierarchical data in a tree structure of folders and nodes.
 *
 * Example:
 * (code)
 * (end)
 *
 * Extends: <Jx.TreeFolder>
 *
 * License: 
 * Copyright (c) 2008, DM Solutions Group Inc.
 * 
 * This file is licensed under an MIT style license
 */
Jx.CHKTree = new Class({
    Extends: Jx.CHKTreeFolder,
	//Modificato Roberto Starnini
	//Implements: [Jx.Addable],
    Implements: [Jx.Addable,Events],
    Family: 'Jx.CHKTree',
    /**
     * Constructor: Jx.Tree
     * Create a new instance of Jx.Tree
     *
     * Parameters:
     * options: options for <Jx.Addable>
     */
    initialize : function( options ) {
        this.parent(options);
        this.subDomObj = new Element('ul',{
            'class':'jxTreeRoot'
        });
        
        this.nodes = [];
        this.isOpen = true;
        
        this.addable = this.subDomObj;
        
        if (this.options.parent) {
            this.addTo(this.options.parent);
        }
    },
    
    /**
     * Method: finalize
     * Clean up a Jx.Tree instance
     */
    finalize: function() { 
        this.clear(); 
        this.subDomObj.parentNode.removeChild(this.subDomObj); 
    },
    /**
     * Method: clear
     * Clear the tree of all child nodes
     */
    clear: function() {
        for (var i=this.nodes.length-1; i>=0; i--) {
            this.subDomObj.removeChild(this.nodes[i].domObj);
            this.nodes[i].finalize();
            this.nodes.pop();
        }
    },
    /**
     * Method: update
     * Update the CSS of the Tree's DOM element in case it has changed
     * position
     *
     * Parameters:
     * shouldDescend - {Boolean} propagate changes to child nodes?
     */
    update: function(shouldDescend) {
        var bLast = true;
        if (this.subDomObj)
        {
            if (bLast) {
                this.subDomObj.removeClass('jxTreeNest');
            } else {
                this.subDomObj.addClass('jxTreeNest');
            }
        }
        if (this.nodes && shouldDescend) {
            this.nodes.each(function(n){n.update(false);});
        }
    },
    /**
     * Method: append
     * Append a node at the end of the sub-tree
     * 
     * Parameters:
     * node - {Object} the node to append.
     */
    append: function( node ) {
        node.owner = this;
        this.nodes.push(node);
        this.subDomObj.appendChild( node.domObj );
        this.update(true);
        return this;    
    },
	
	/*******************************************/
	//Modificato Roberto Starnini
	setItems : function (selectedItems,disabledItems){
		for (var i=0; i<this.nodes.length; i++){
            var folder=this.nodes[i];
			var nodesDisabled=0;
			for (var j=0; j<folder.nodes.length; j++){
				if(selectedItems.contains(folder.nodes[j].options.id)){
					folder.nodes[j].domControl.checked = true;
					folder.domControl.checked = true;
				}
				
				if(disabledItems.contains(folder.nodes[j].options.id)){
					folder.nodes[j].options.enabled=false;
					folder.nodes[j].domObj.addClass('jxDisabled');
					folder.nodes[j].domControl.disabled=true;
				}else{
					//alert('abilita ' + folder.nodes[j].options.id);
					folder.nodes[j].options.enabled=true;
					folder.nodes[j].domObj.removeClass('jxDisabled');
					folder.nodes[j].domControl.disabled=false;
				}
				if (!folder.nodes[j].options.enabled) nodesDisabled++;
				//disabilito il folder se sono tutti disabilitati
				if(folder.nodes.length==nodesDisabled){
					folder.options.enabled=false;
					folder.domObj.addClass('jxDisabled');
					folder.domControl.disabled=true;
				}else{
					folder.options.enabled=true;
					folder.domObj.removeClass('jxDisabled');
					folder.domControl.disabled=false;
				}
			}
        }
		
	},
	
	itemUpdated : function (){
		var layers = new Array();
		var themes = new Array();
		for (var i=0; i<this.nodes.length; i++){
            var folder=this.nodes[i];
			if(folder.options.open)
				themes.push(folder.options.id)
			for (var j=0; j<folder.nodes.length; j++){
				if(folder.nodes[j].domControl.checked) 
					layers.push(folder.nodes[j].options.id)
			}
		}
		this.fireEvent('updated',{'layers':layers.join(','),'themes':themes.join(',')});
	}
	/*******************************************/
	
});

/*******************************************/
	//Modificato Roberto Starnini
	function initLayerTree(anchor,jsTree,thOpen,grpOn,laydelay){
	    layerTree = new Jx.CHKTree({parent: anchor,delayTime:laydelay});
		for(var i=0;i<jsTree.length;i++){//temi
			thId=jsTree[i][0];
			thTitle=jsTree[i][1];
			grpLayer = jsTree[i][3];
			var isopen = thOpen.contains(thId);
			var folder = new Jx.CHKTreeFolder({
			label: thTitle,
			id:thId,
			control: 'checkbox',
			delayTime:laydelay,
			open: isopen});
			this.layerTree.append(folder);
			var isThemechecked = false;
			for(var j=0;j<grpLayer.length;j++){//layergroup
				grpId=grpLayer[j][0];
				grpControl=(grpLayer[j][1]==0)?'checkbox':'radio';
				grpTitle=grpLayer[j][2];
				var ischecked = grpOn.contains(grpId);
				if(ischecked) isThemechecked = true;
				//var isenabled = !grpDisabled.contains(grpId);
				var item = new Jx.CHKTreeItem({
				label: grpTitle,
				control: grpControl,
				delayTime:laydelay,
				name: 'th_' + thId,
				checked: ischecked,
				enabled: true,//Va settato con i valori di ritorno
				id:grpId});
				folder.append(item);
			}
			folder.domControl.checked=isThemechecked;
		}
		return layerTree;
	};
/*******************************************/
	