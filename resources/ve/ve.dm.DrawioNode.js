ve.dm.DrawioNode = function DrawioNode() {
	// Parent constructor
	ve.dm.DrawioNode.super.apply( this, arguments );
};

/* Inheritance */
OO.inheritClass( ve.dm.DrawioNode, ve.dm.MWInlineExtensionNode );

/* Static members */
ve.dm.DrawioNode.static.name = 'drawio';
ve.dm.DrawioNode.static.tagName = 'drawio';

// Name of the parser tag
ve.dm.DrawioNode.static.extensionName = 'drawio';

// This tag renders without content
ve.dm.DrawioNode.static.childNodeTypes = [];
ve.dm.DrawioNode.static.isContent = false;

/* Registration */
ve.dm.modelRegistry.register( ve.dm.DrawioNode );
