class Development {
  constructor() {
    this.useServerImagesIfDevelopingLocally();
  }

  useServerImagesIfDevelopingLocally() {
    if (window.location.hostname === 'pof.loc') {
      const images = document.querySelectorAll('img');
      [].forEach.call(images, (img: HTMLImageElement) => {
        if (img.src !== img.currentSrc) {
          const temp = document.createElement('a');
          temp.href=img.src;
          img.src='https://poweroffamilies.com' + temp.pathname;
        }
      });
    }
  }
}

export {Development}