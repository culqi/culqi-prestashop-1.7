<div class="row culqi_payment">
    <link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/culqi.css" type="text/css" media="all">
    <link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/waitMe.min.css" type="text/css" media="all">
    <div id="showresult" class="hide">
      <div class="showresultcontent"></div>
    </div>
</div>

<input id="clq_validation" type="hidden" value="{$link->getModuleLink('culqi', 'validation', ['clqtype' => 'CLQ_TYPE', 'clqcode' => 'CLQ_CODE'], true)|escape}">
<input id="clq_chargeajax" type="hidden" value="{$link->getModuleLink('culqi', 'chargeajax', [], true)|escape}">
<input id="clq_logo" type="hidden" value="{$logo|escape}">
<input id="clq_key" type="hidden" value="{$llave_publica|escape}">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script type="text/javascript" defer src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/waitMe.min.js"></script>
<script src="https://checkout.culqi.com/js/v3"></script>

{literal}
<script>
$(document).ready(function() {
	Culqi.publicKey = '{/literal}{$llave_publica|escape:'htmlall':'UTF-8'}{literal}';
	Culqi.useClasses = true;
	Culqi.init();

	/**
   * Muestra el error de la tarjeta al momento de intentar pagar.
   */
  if (localStorage.getItem('culqi_message') !== '') {
    var errorCard = "<div class=\"alert alert-danger\" role=\"alert\">" + localStorage.getItem('culqi_message') + "</div>";
    $('#notifications .container').html(errorCard)
    setInterval(function(){ localStorage.setItem('culqi_message', ''); }, 3000);
  }

  Culqi = new culqijs.Checkout();
  Culqi.publicKey = $('#clq_key').val();
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
      logo: $('#clq_logo').val()
    }
  })
  Culqi.settings({
    title: '{/literal}{$page.meta.title}{literal}',
    currency: '{/literal}{$currency}{literal}',
    description: '',
    amount: {/literal}{$total}{literal},
    order: '{/literal}{$order_culqi->id}{literal}'
  });

  $('#payment-confirmation > .ps-shown-by-js > button').click(function(e) {
    var myPaymentMethodSelected = $('.payment-options').find("input[data-module-name='culqi']").is(':checked');

    if (myPaymentMethodSelected) {
      e.preventDefault();
      Culqi.open();
      return false;
    }
  });
});

  function culqi() {
    if (Culqi.token) {
      var installments = (Culqi.token.metadata.installments === undefined) ? 0 : Culqi.token.metadata.installments;
      $.ajax({
        type: 'POST',
        url: $('#clq_chargeajax').val(),
        data: {
          ajax: true,
          action: 'displayAjax',
          token_id: Culqi.token.id,
          installments: installments
        },
        datatype: 'json',
        success: function(data) {
          var result;

          if (data === "Imposible conectar a Culqi API") {
			      showResult('red', data + ": aumentar el timeout de la consulta");
          } else if (data === "Error de autenticación") {
            showResult('red',data + ": verificar si su Llave Secreta es la correcta");
          } else {
            if (data.constructor === String) {
              let dataParsed = JSON.parse(data);
              if (dataParsed.constructor === String) {
                  result = JSON.parse(dataParsed);
              } else {
                result = dataParsed
              }
            }
            if (data.constructor === Object) {
              result = JSON.parse(JSON.stringify(data));
            }
            switch (result.object) {
              case 'charge':
                localStorage.setItem('culqi_message', '');
                validation('charge', result.outcome.code);
                break;

              case 'error':
                showResult('red', result.user_message);
                location.reload();
                break;

              default:
                showResult('black', result.user_message);
                // Culqi.close();
                break;
            }
          }
        },
        error: function(error) {
          showResult('red', JSON.stringify(error));
        }
      });
    } else if (Culqi.order) {
      validation('order', Culqi.order.payment_code)
    }
    else if (Culqi.closeEvent){
      console.log(Culqi.closeEvent);
    }
    else {
      $('#response-panel').show();
      $('#response').html(Culqi.error.merchant_message);
    }
  }

  function showResult(style, message) {
    localStorage.setItem('culqi_message', message);
      $('#showresult').removeClass('hide');
      $('.showresultcontent').attr('class', '').addClass(style).html(message);
  }

  function validation(type, code)   {
    const urlValidation = $('#clq_validation').val()
    location.href = urlValidation.replace('CLQ_TYPE', type).replace('CLQ_CODE', code);
  }

  function fnReplace(url) {
    return url.replace(/&amp;/g, '&');
  }
</script>
{/literal}
