jQuery(document).ready(function ($) {
  $('body').prepend('<div id="header-bg" class="gradient-green"></div>');

  $('#user_login').attr('placeholder', 'Username');
  $('#user_pass').attr('placeholder', 'Password');
  // initialize collapse script
  //$('.collapse').collapse();

  // hide collapsable things
  //$('.collapse').collapse('hide');


  //reposition author box
  if ($('.author-box').length && $('.linkwithin_hook').length) {
    var authorBoxHtml = $('.author-box').html();
    $('.author-box').remove();
    $('.linkwithin_hook').before("<div class='author-box'>" + authorBoxHtml + "</div>");
  }


  // // Image path updating: when running locally
  // if (window.location.hostname === 'localhost') {
  //   $('img').each(function (item) {
  //     "use strict";
  //     var src = this.getAttribute('src');
  //     if (src.indexOf('/') === 0) {
  //       this.src = 'https://poweroffamilies.com' + src;
  //     }
  //   });
  // }
  // [].forEach.call($('img'),function(img){var src=img.getAttribute('src');if(src.indexOf('/')===0){img.src='http://powerofmoms.com'+src;}})
  document.querySelectorAll('img').forEach(img=>{
    const src = img.getAttribute('src');
    const dataSrc = img.dataset;
    if(src.indexOf('/') === 0){
      console.log('src', img.src);
      img.src = 'https://poweroffamilies.com' + src;
      console.log('newsrc', img.src);

    }
    if(dataSrc?.lazySrc) {
      dataSrc.lazySrc=dataSrc.lazySrc.split(' ').map(path=> path.indexOf('/')===0 ? 'https://poweroffamilies.com'+path : path).join(' ');
      console.log('src', dataSrc.src);
    }
  });

});
console.log('OUTPUT');