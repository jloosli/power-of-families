/**
 * Adds link to the learn more button on product archive pages.
 * IMPORTANT: In order for this to work, a button with class "learn-more-button"
 * must be added to the Product Template on the landing page for the store
 */

const context = 'data-wp-context';

function addLinkToLearnMoreButton() {
	document.addEventListener('DOMContentLoaded', () =>
		setTimeout(() => addLinks())
	);
}
function addLinks() {
	document.querySelectorAll('.learn-more-button').forEach((theDiv) => {
		const productString = theDiv
			.closest(`[${context}]`)
			?.getAttribute(context);
		if (!productString) return;
		const productId = JSON.parse(productString)?.productId;
		// console.log({ productId });
		const theButton = theDiv.querySelector('a');
		if (!theButton) return;
		theButton.setAttribute('href', `/?page_id=${productId}`);
	});
}

export { addLinkToLearnMoreButton };
