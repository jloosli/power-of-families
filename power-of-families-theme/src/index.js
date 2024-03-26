import '../assets/scss/main.scss';

function useServerImagesIfDevelopingLocally() {
    const ProductionRegex = /\.com$/;
    const BeginningOfStringRegex = /^\//;
    if (!ProductionRegex.test(window.location.hostname)) {
      const images = document.querySelectorAll('img');
      [].forEach.call(images, (img) => {
        if (img.src !== img.currentSrc) {
          img.src = createUrlToImage(img.src);
          if (img.srcset) {

            img.srcset = img.srcset.split(',')
              .map(str => str.trim())
              .map(src => BeginningOfStringRegex.test(src) ? 'https://poweroffamilies.com' + src : src)
              .join(", ");
          }
        }
      });
    }
  }

  function createUrlToImage(location) {
    const url = new URL(location);
    return 'https://poweroffamilies.com' + url.pathname;
  }

useServerImagesIfDevelopingLocally();