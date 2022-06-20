ve.ce.DrawioNode = function VeCeAttachmentNode() {
	// Parent constructor
	ve.ce.DrawioNode.super.apply( this, arguments );
};

/* Inheritance */
OO.inheritClass( ve.ce.DrawioNode, ve.ce.MWInlineExtensionNode );

/* Static properties */
ve.ce.DrawioNode.static.name = 'drawio';
ve.ce.DrawioNode.static.primaryCommandName = 'drawio';

// If body is empty, tag does not render anything
ve.ce.DrawioNode.static.rendersEmpty = false;

/* Registration */
ve.ce.nodeFactory.register( ve.ce.DrawioNode );
