<div class="row culqi_payment">
		<link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/culqi.css" type="text/css" media="all">
    <link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/waitMe.min.css" type="text/css" media="all">

    <div id="showresult" class="hide">
      <div id="showresultcontent"></div>
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

    $('#notifications .container').html(errorCard)
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
      logo: 'https://image.flaticon.com/icons/svg/25/25231.svg'
    }
  })
  Culqi.settings({
    title: 'Title Tienda',
    currency: 'PEN',
    description: 'Descripcion tienda',
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
          var result = data.constructor === String ? JSON.parse(eval(data)) : data;
          console.log(result);

          switch (result.object) {
            case 'charge':
              console.log('SUCCESS PAY!!!!!!!!!!!!!!!!!!!!!', result)
                    localStorage.setItem('culqi_message', '')
              // redirect to SUCCESS
              break;

            case 'error':
              showResult('red', result.user_message);
              // Culqi.close();
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
      console.log("Order confirmada con PagoEfectivo");
      console.log(Culqi.order);
      showResult('green', Culqi.order);
    }
    else if (Culqi.closeEvent){
      console.log(Culqi.closeEvent);
    }
    else {
      $('#response-panel').show();
      $('#response').html(Culqi.error.merchant_message);
      // $('body').waitMe('hide');
    }
  }

function run_waitMe() {
	$('body').waitMe({
	  effect: 'orbit',
	  text: 'Procesando pago...',
	  bg: 'rgba(255,255,255,0.7)',
	  color:'#28d2c8'
	});
}

function showResult(style,message) {
  localStorage.setItem('culqi_message', message);
  console.log('showResult ===> ', style, message);
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
