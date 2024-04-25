class Development {
	constructor() {
		const ProductionRegex = /\.com$/;
		if (!ProductionRegex.test(window.location.hostname)) {
			document.addEventListener('DOMContentLoaded', () =>
				setTimeout(() => this.useServerImagesIfDevelopingLocally(), 500)
			);
		}
	}

	useServerImagesIfDevelopingLocally() {
		const BeginningOfStringRegex = /^\//;
		const images = document.querySelectorAll('img');
		images.forEach((img) => {
			if (img.src !== img.currentSrc) {
				img.src = this.createUrlToImage(img.src);
				if (img.srcset) {
					img.srcset = img.srcset
						.split(',')
						.map((str) => str.trim())
						.map((src) =>
							BeginningOfStringRegex.test(src)
								? 'https://poweroffamilies.com' + src
								: src
						)
						.join(', ');
				}
			}
		});
	}

	createUrlToImage(location: string) {
		const url = new URL(location);
		return 'https://poweroffamilies.com' + url.pathname;
	}
}

export { Development };
