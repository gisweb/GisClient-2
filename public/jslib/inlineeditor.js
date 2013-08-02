//Author: birijan maharjan
//birijan.m@gmail.com
var Editor = new Class({

	Implements: [Options, Events],

	options: {
		editorHeight: '150px',
		confirmOnCancel: true,
		container: false,
		editableElement: false
	},

	initialize: function(options){
		this.setOptions(options);
		this.container = $(this.options.container);
		this.editableElement = $(this.options.editableElement);
		if(!this.container) return;
		this.prepareContainer();
		this.editableElement.addEvent('click', this.editMode.bind(this));
	},
	prepareContainer: function(){

		this.editableElement.setStyles({
			width: this.container.getStyle('width'),
			height: this.container.getStyle('height')
		});

		var tempContainer = new Element('div', {id: 'tempContainer'} );
		tempContainer.inject(this.container, 'bottom');

		var textArea = new Element('textarea', {id: 'editor', name: 'editor' });
		
		var width = this.container.getStyle('width');
		
		textArea.setStyle('width', width);
		textArea.setStyle('height', this.options.editorHeight);
		textArea.inject(tempContainer, 'bottom')

		var saveButton = new Element('input', {id: 'save', type: 'submit', value:'Save', name: 'save'  });
		saveButton.inject(tempContainer, 'bottom')

		var cancelButton = new Element('input', {id: 'cancel', type: 'submit',  value:'Cancel', name: 'cancel' });
		cancelButton.inject(tempContainer, 'bottom');
		
		saveButton.addEvent('click', this.save.bind(this));
		cancelButton.addEvent('click', this.cancel.bind(this));
		
		tempContainer.setStyle('display', 'none');

	},

	cancel: function(){
		var result = true;
		if(this.options.confirmOnCancel){
			result = confirm("Do you want to cancel changes ?");
		}
		if(result){
			this.editableElement.setStyle('display', 'inline');
			$('tempContainer').setStyle('display', 'none');
		}
	},
	save: function(){
		this.editableElement.setStyle('display', 'inline');
		var value = $('editor').get('value');
		if(value == '') { value = 'Click here to edit' };
		this.editableElement.set('text', value);
		$('tempContainer').setStyle('display', 'none');

	},

	editMode: function(){
		var editableText = this.editableElement.get('text');
		$('tempContainer').setStyle('display', 'inline');
		$('editor').set('value', editableText);
		this.editableElement.setStyle('display', 'none');
	} 

});