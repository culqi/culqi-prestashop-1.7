
const operationProcessing = 'processing';
$('#checkout').on('click', '#buyButton', function (e) {
        $('#buyButton').attr('disabled', true);
        $("[data-payment=culqi]").attr('disabled', true);
        generateOrder(e);
});

function generateOrder(e) {
    $.ajax({
        url: fnReplace(register_sale_url),
        data: {},
        type: "POST",
        dataType: 'json',
        success: function (response) {
            console.log('Se genero una orden:::', response);
            orderid = response;
            if (response.result === 'success') {
                if(response.show_modal) {
                    $('#order-created-modal').fadeIn();
                    $('#order-created-modal iframe').attr('src', response.redirect);
                    $('body').addClass('no-scroll');
                    //$('.woocommerce-loader').removeClass('flex');
                } else {
                    window.location.href = response.redirect;
                }
                jQuery('#place_order').attr('disabled', false);
            } else {
                alert('Order creation failed. Please try again.');
                //$('.woocommerce-loader').removeClass('flex');
            }
            $('#buyButton').removeAttr('disabled');
            $("[data-payment=culqi]").removeAttr('disabled');
            $('#showresult').hide();
            e.preventDefault();
        },
        error: function (error) {
            console.log('Error al generar orden :::', error);
            $('#showresult').show();
            orderid = '';
            $('#buyButton').removeAttr('disabled');
            $("[data-payment=culqi]").removeAttr('disabled');
            $('#showresult').hide();
            e.preventDefault();
        }
    });
}

function showResult(style, message) {

    var new_message = ''

    if (message == '') {
        new_message = 'ERROR CULQI';
    } else {
        new_message = message;
    }

    $('#showresult').removeClass('hide');
    $('#showresultcontent').attr('class', '');
    $('#showresultcontent').addClass(style);
    $('#showresultcontent').html(new_message);
}

function fnReplace(url) {
    return url.replace(/&amp;/g, '&');
}

function redirect() {

    if (Culqi.order) {
        var codigocip = Culqi.order['payment_code'];
        var url = fnReplace(phpData.postpaymentpending_url);
    } else if (Culqi.token) {
        var url = fnReplace(phpData.postpayment_url);
    }

}

window.addEventListener('message', function(event) {
    if (event.data.redirectUrl) {
        window.redirectUrl = event.data.redirectUrl;
    }
    if (event.data.operationType == operationProcessing) {
        if (window.redirectUrl) {
            customRedirect();
        }
    }
    if (event.data.action === 'closeModal') {
        $('#order-created-modal').fadeOut();
        $('body').removeClass('no-scroll');
        if (window.redirectUrl) {
            customRedirect();
        }
    }

}, false);

function customRedirect() {
    const redirectUrl = window.redirectUrl;
    delete window.redirectUrl;
    window.location.href = redirectUrl;
}