( function( window, $, undefined ) {
	'use strict';
 
	$( 'nav:not(.nav-secondary):not(.ubermenu)' ).before( '<div class="sub-menu-toggle-container"><button class="menu-toggle" role="button" aria-pressed="false"><span class="hide-activated">Open Navigation</span><span class="hide-deactivated">Close Navigation</span></button></div>' ); // Add toggles to menus
	$( 'nav:not(.nav-secondary):not(.ubermenu) .sub-menu' ).before( '<div class="sub-menu-toggle-container"><button class="sub-menu-toggle" role="button" aria-pressed="false"><span class="hide-activated">Open Navigation</span><span class="hide-deactivated">Close Navigation</span></button></div>' ); // Add toggles to sub menus
 
	// Show/hide the navigation
	$( '.menu-toggle, .sub-menu-toggle' ).on( 'click', function() {
		var $this = $( this );
		$this.attr( 'aria-pressed', function( index, value ) {
			return 'false' === value ? 'true' : 'false';
		});
 
		$this.toggleClass( 'activated' );
		$this.parent().next( 'nav:not(.nav-secondary):not(.ubermenu), .sub-menu' ).slideToggle( 'fast' );
 
	});
 
})( this, jQuery );