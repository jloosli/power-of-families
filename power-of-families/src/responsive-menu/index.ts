class ResponsiveMenu {
  constructor() {
    this.attachMenus();
    this.addListeners();
  }

  attachMenus() {
    jQuery('nav.nav-primary').before('<div class="sub-menu-toggle-container"><button class="menu-toggle" role="button" aria-pressed="false"><span class="hide-activated">Open Navigation</span><span class="hide-deactivated">Close Navigation</span></button></div>'); // Add toggles to menus
    // jQuery( 'nav:not(.nav-secondary):not(.ubermenu) .sub-menu' ).before( '<div class="sub-menu-toggle-container"><button class="sub-menu-toggle" role="button" aria-pressed="false"><span class="hide-activated">Open Navigation</span><span class="hide-deactivated">Close Navigation</span></button></div>' ); // Add toggles to sub menus
  }

  addListeners() {
    // Show/hide the navigation
    jQuery('.menu-toggle, .sub-menu-toggle').on('click', function () {
      const $this = jQuery(this);
      $this.attr('aria-pressed', function (index, value) {
        return 'false' === value ? 'true' : 'false';
      });

      $this.toggleClass('activated');
      $this.parent().next('nav:not(.nav-secondary):not(.ubermenu), .sub-menu').slideToggle('fast');

    });
  }

}

export {ResponsiveMenu}