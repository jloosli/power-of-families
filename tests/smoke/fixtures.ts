function required(name: string): string {
	const value = process.env[name];
	if (!value) {
		throw new Error(
			`Missing required env var: ${name}. Did bin/smoke-fixtures run before this spec?`
		);
	}
	return value;
}

export const env = {
	siteUrl: required('SITE_URL'),
	userEmail: required('SMOKE_USER_EMAIL'),
	userLogin: required('SMOKE_USER_LOGIN'),
	password: required('SMOKE_PASSWORD'),
	productUrl: required('SMOKE_PRODUCT_URL'),
	gatedUrl: required('SMOKE_GATED_URL'),
	gatedMarker: required('SMOKE_GATED_MARKER'),
	latestPostUrl: required('SMOKE_LATEST_POST_URL'),
};

// Strings WP renders when something blows up server-side.
export const phpErrorMarkers = [
	'Fatal error',
	'Parse error',
	'There has been a critical error on this website',
];

// Selectors that may need tweaking based on theme overrides. Centralized
// here so spec churn happens in one file.
export const selectors = {
	// WP login form
	loginUsername: '#user_login',
	loginPassword: '#user_pass',
	loginSubmit: '#wp-submit',
	// WooCommerce — verified during Task 9 implementation.
	addToCart: 'button[name="add-to-cart"], .single_add_to_cart_button',
	cartSuccess: '.woocommerce-message, .wc-block-components-notice-banner',
	checkoutForm: '.woocommerce-checkout, form.checkout',
};
