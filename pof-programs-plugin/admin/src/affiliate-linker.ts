declare var jQuery: any;
declare var ajaxurl: string;

export const al = (me: any) => {
  const message = document.createElement('span');
  me.parentNode.replaceChild(message, me);
  message.innerHTML = 'Working...';
  message.onclick = null;
  jQuery.post(ajaxurl, {action: 'pof_affiliates_run'}, function (response: any) {
    console.log(response);
    message.innerHTML = response.message;
  });
  return false;
}

export const test = () => {
  alert(1);
}

export class AffiliateLinker {
  constructor(){
    console.log('Linker!')
  }

  test() {
    alert('test');
  }
}
