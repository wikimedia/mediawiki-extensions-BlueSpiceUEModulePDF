bs.ue.ui.plugin.Pdf = function ( config ) {
	bs.ue.ui.plugin.Pdf.parent.call( this, config );

	this.config = config || {};
	this.dialog = config.dialog || null;

	this.subModule = '';
	this.template = '';
	this.subModuleSelect = {};
	this.templateSelect = {};
	this.defaultTemplate = '';
};

OO.inheritClass( bs.ue.ui.plugin.Pdf, bs.ue.ui.plugin.Plugin );

bs.ue.registry.Plugin.register( 'pdf', bs.ue.ui.plugin.Pdf );

bs.ue.ui.plugin.Pdf.prototype.getName = function () {
	return 'pdf';
};

bs.ue.ui.plugin.Pdf.prototype.getFavoritePosition = function () {
	return 20;
};
bs.ue.ui.plugin.Pdf.prototype.getLabel = function () {
	return mw.message( 'bs-uemodulepdf-export-dialog-label-module-name' ).text();
};

bs.ue.ui.plugin.Pdf.prototype.getPanel = function () {
	this.defaultTemplate = mw.config.get( 'bsUEModulePDFDefaultTemplate' );
	var availableTemplates = mw.config.get( 'bsUEModulePDFAvailableTemplates' );

	var templates = [];
	for ( var index = 0; index < availableTemplates.length; index++ ) {
		templates.push(
			{
				label: availableTemplates[ index ].replace( '_', ' ' ),
				data: availableTemplates[ index ]
			}
		);
	}

	var modulePanel = new OO.ui.PanelLayout( {
		expanded: false,
		framed: false,
		padded: false,
		$content: ''
	} );

	var fieldset = new OO.ui.FieldsetLayout();

	/* Select submodule */
	this.subModuleSelect = new OO.ui.RadioSelectInputWidget( {
		options: [ {
			data: 'default',
			label: mw.message( 'bs-uemodulepdf-export-dialog-label-submodule-default' ).text()
		}, {
			data: 'subpages',
			label: mw.message( 'bs-uemodulepdf-export-dialog-label-submodule-subpages' ).text()
		}, {
			data: 'recursive',
			label: mw.message( 'bs-uemodulepdf-export-dialog-label-submodule-recursive' ).text()
		} ]
	} );

	this.subModuleSelect.setValue( 'default' );

	fieldset.addItems( [
		new OO.ui.FieldLayout(
			this.subModuleSelect,
			{
				align: 'left',
				label: mw.message( 'bs-uemodulepdf-export-dialog-label-select-submodule' ).text()
			}
		)
	] );

	/* Select template */
	this.templateSelect = new OO.ui.DropdownInputWidget( {
		options: templates,
		$overlay: this.dialog ? this.dialog.$overlay : true
	} );

	if ( templates.length > 0 ) {
		this.templateSelect.setValue( this.defaultTemplate );
		this.template = this.defaultTemplate;
	}

	this.templateSelect.on( 'change', this.onChangeTemplate.bind( this ) );

	fieldset.addItems( [
		new OO.ui.FieldLayout(
			this.templateSelect,
			{
				align: 'left',
				label: mw.message( 'bs-uemodulepdf-export-dialog-label-select-template' ).text()
			}
		)
	] );

	modulePanel.$element.append( fieldset.$element );

	return modulePanel;
};

bs.ue.ui.plugin.Pdf.prototype.getParams = function () {
	var params = {
		module: 'pdf'
	};

	if ( this.template !== this.defaultTemplate ) {
		params.template = this.template;
	}

	var subModule = this.subModuleSelect.getValue();
	if ( subModule !== 'default' ) {
		params[ subModule ] = 1;
	}

	return params;
};

bs.ue.ui.plugin.Pdf.prototype.onChangeTemplate = function () {
	this.template = this.templateSelect.getValue();
};
