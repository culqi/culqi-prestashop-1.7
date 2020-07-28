<div class="row culqi_payment">
		<link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/culqi.css" type="text/css" media="all">
    <link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/waitMe.min.css" type="text/css" media="all">

    <div id="showresult" class="hide">
      <div class="showresultcontent"></div>
    </div>

</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script type="text/javascript" defer src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/waitMe.min.js"></script>

<script src="https://checkout.culqi.com/js/v3"></script>

{literal}
<script>

$(document).ready(function() {

  if (localStorage.getItem('culqi_message') !== '') {
    var errorCard = "<div class=\"alert alert-danger\" role=\"alert\">" + localStorage.getItem('culqi_message') + "</div>";

    $('#showresult .showresultcontent').html(errorCard)

    setInterval(function(){ localStorage.setItem('culqi_message', ''); }, 2000);
    setInterval(function(){ $('#showresult .showresultcontent').html('') }, 10000);
  }

  Culqi = new culqijs.Checkout();
  Culqi.publicKey = '{/literal}{$llave_publica|escape:'htmlall':'UTF-8'}{literal}';
  Culqi.options({
    lang: 'auto',
    modal: true,
    installments: true,
    style: {
      bgcolor: '#f0f0f0',
      maincolor: '#53D3CA',
      disabledcolor: '#ffffff',
      buttontext: '#ffffff',
      maintext: '#4A4A4A',
      desctext: '#4A4A4A',
      logo: '{/literal}{$urls.img_ps_url}{$shop.logo}{literal}'
    }
  })
  Culqi.settings({
    title: '{/literal}{$page.meta.title}{literal}',
    currency: '{/literal}{$currency}{literal}',
    description: '',
    amount: 700,
    order: '{/literal}{$order_culqi->id}{literal}'
  });


	$('#payment-confirmation > .ps-shown-by-js > button').click(function(e) {
		var myPaymentMethodSelected = $('.payment-options').find("input[data-module-name='culqi']").is(':checked');

		if(myPaymentMethodSelected) {
        e.preventDefault();
        Culqi.open();
				return false;
		}

	});
});

  function culqi() {
    if (Culqi.token) {

      console.log("Token obtenido");
      console.log(Culqi.token);
      console.log("Respuesta desde iframe: ", Culqi.token);

      var installments = (Culqi.token.metadata.installments === undefined) ? 0 : Culqi.token.metadata.installments;
      $.ajax({
        type: 'POST',
        url: fnReplace("{/literal}{$link->getModuleLink('culqi', 'chargeajax', [], true)|escape:'htmlall':'UTF-8'}{literal}"),
        data: {
          ajax: true,
          action: 'displayAjax',
          token_id: Culqi.token.id,
          installments: installments
        },
        datatype: 'json',
        success: function(data) {
          var result;

          if (data.constructor === String) {
            result = JSON.parse(data);
          }
          if (data.constructor === Object) {
            result = JSON.parse(JSON.stringify(data));
          }

          console.log(result);
          switch (result.object) {
            case 'charge':
              localStorage.setItem('culqi_message', '');
              redirect();
              break;

            case 'error':
              showResult('red', result.user_message);
              location.reload();
              break;

            default:
              showResult('black', result.user_message);
              Culqi.close();
              break;
          }
        },
        error: function(error) {
          showResult('red', JSON.stringify(error));
        }
      });
    } else if (Culqi.order) {
      showResult('green', Culqi.order);
    }
    else if (Culqi.closeEvent){
      // console.log(Culqi.closeEvent);
    }
    else {
      $('#response-panel').show();
      $('#response').html(Culqi.error.merchant_message);
    }
  }

function showResult(style,message) {
  localStorage.setItem('culqi_message', message);
  // console.log('showResult ===> ', style, message);
	$('#showresult').removeClass('hide');
	$('#showresultcontent').attr('class', '');
	$('#showresultcontent').addClass(style);
	$('#showresultcontent').html(message);
}

function redirect() {
  var url = fnReplace("{/literal}{$link->getModuleLink('culqi', 'postpayment', [], true)|escape:'htmlall':'UTF-8'}{literal}");
  location.href = url;
}

function fnReplace(url) {
  return url.replace(/&amp;/g, '&');
}

</script>

{/literal}
