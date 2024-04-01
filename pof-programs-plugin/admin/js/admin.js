(function ($) {
  $(document).ready(function () {
    $('#pof_amazon_affiliate_run_now').on('click', function(){
      runAffiliates(this)
    })
  });

  function runAffiliates(me) {
    var message = document.createElement('span');
    me.parentNode.replaceChild(message, me);
    message.innerHTML = 'Working...';
    // message.onclick = null;
    jQuery.post(ajaxurl, {action: 'pof_affiliates_run'}, function (response) {
      console.log(response);
      message.innerHTML = response.message;
    });
    return false;
  }
})(jQuery);

