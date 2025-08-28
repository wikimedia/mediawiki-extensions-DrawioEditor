ve.ui.DrawioNodeTool = function DrawioNodeTool( toolGroup, config ) {
	ve.ui.DrawioNodeTool.super.call( this, toolGroup, config );
};

OO.inheritClass( ve.ui.DrawioNodeTool, ve.ui.FragmentInspectorTool );

ve.ui.DrawioNodeTool.static.name = 'drawioTool';
ve.ui.DrawioNodeTool.static.group = 'none';
ve.ui.DrawioNodeTool.static.autoAddToCatchall = false;
ve.ui.DrawioNodeTool.static.icon = 'drawio';
ve.ui.DrawioNodeTool.static.title = mw.message( 'drawioeditor-tag-title' ).text();
ve.ui.DrawioNodeTool.static.modelClasses = [ ve.dm.DrawioNode ];
ve.ui.DrawioNodeTool.static.commandName = 'drawioCommand';

ve.ui.toolFactory.register( ve.ui.DrawioNodeTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'drawioCommand', 'window', 'open',
		{ args: [ 'drawioInspector' ], supportedSelections: [ 'linear' ] }
	)
);
