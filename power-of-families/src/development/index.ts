class Development {
  constructor() {
    this.useServerImagesIfDevelopingLocally();
  }

  useServerImagesIfDevelopingLocally() {
    const ProductionRegex = /\.com$/;
    const BeginningOfStringRegex = /^\//;
    if (!ProductionRegex.test(window.location.hostname)) {
      const images = document.querySelectorAll('img');
      [].forEach.call(images, (img: HTMLImageElement) => {
        if (img.src !== img.currentSrc) {
          img.src = this.createUrlToImage(img.src);
          if (img.srcset) {

            img.srcset = img.srcset.split(',')
              .map(str => str.trim())
              .map(src => BeginningOfStringRegex.test(src) ? 'https://poweroffamilies.com' + src : src)
              .join(",\n");
          }
        }
      });
    }
  }

  createUrlToImage(location: string): string {
    const temp = document.createElement('a');
    temp.href = location;
    return 'https://poweroffamilies.com' + temp.pathname;
  }
}

export {Development}