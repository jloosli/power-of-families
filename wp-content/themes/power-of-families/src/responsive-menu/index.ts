class ResponsiveMenu {
	constructor() {
		document.addEventListener('DOMContentLoaded', () =>
			setTimeout(() => {
				this.attachMenus();
				this.addListeners();
			}, 500)
		);
	}

	attachMenus() {
		// Add toggles to primary menu
		const navPrimary = document.querySelector('nav.nav-primary');
		if (navPrimary && navPrimary.parentNode) {
			const container = document.createElement('div');
			container.className = 'sub-menu-toggle-container';

			const button = document.createElement('button');
			button.className = 'menu-toggle';
			button.setAttribute('role', 'button');
			button.setAttribute('aria-pressed', 'false');

			const spanOpen = document.createElement('span');
			spanOpen.className = 'hide-activated';
			spanOpen.textContent = 'Open Navigation';

			const spanClose = document.createElement('span');
			spanClose.className = 'hide-deactivated';
			spanClose.textContent = 'Close Navigation';

			button.appendChild(spanOpen);
			button.appendChild(spanClose);
			container.appendChild(button);

			navPrimary.parentNode.insertBefore(container, navPrimary);
		}
	}

	addListeners() {
		// Show/hide the navigation
		const toggles = document.querySelectorAll(
			'.menu-toggle, .sub-menu-toggle'
		);
		toggles.forEach((toggle) => {
			toggle.addEventListener('click', () => {
				toggle.classList.toggle('activated');
				toggle.setAttribute(
					'aria-pressed',
					toggle.classList.contains('activated').toString()
				);
				toggle?.parentElement?.parentElement
					?.querySelector('nav, .sub-menu')
					?.classList.toggle('activated');
			});
		});
		// jQuery('.menu-toggle, .sub-menu-toggle').on('click', function () {
		// 	const $this = jQuery(this);
		// 	$this.attr('aria-pressed', function (index, value) {
		// 		return 'false' === value ? 'true' : 'false';
		// 	});

		// 	$this.toggleClass('activated');
		// 	$this
		// 		.parent()
		// 		.next('nav:not(.nav-secondary), .sub-menu')
		// 		.slideToggle('fast');
		// });
	}
}

export { ResponsiveMenu };
