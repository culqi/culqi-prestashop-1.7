/**
 * @license
 * three.js - JavaScript 3D library
 * Copyright 2016 The three.js Authors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**** ONE PAGE CHECKOOUT COMPATIBILITY ******/
function onepageCheckoutCulqi(paymentMethod) {
    if (paymentMethod == "culqi") {
        $("#btn_place_order").attr("data-payment", "culqi");
        $("[data-payment=culqi]").removeAttr("id");
        $("[data-payment=culqi]").addClass("custom_btn_onepage_culqi");
    } else {
        $("[data-payment=culqi]").attr("id", "btn_place_order");
        $("[data-payment=culqi]").removeClass("custom_btn_onepage_culqi");
        $("#btn_place_order").removeAttr("dat a-payment");
    }
}

function validateForm() {
    if (typeof AppOPC != 'undefined') {
        return AppOPC.is_valid_all_form;
    }

    return null;
}

function createCustomerCulqiPs(e, value) {
    var invoice_id = '';
    var fields = Review.getFields();

    if (OnePageCheckoutPS.CONFIGS.OPC_ENABLE_INVOICE_ADDRESS && $('div#onepagecheckoutps #checkbox_create_invoice_address').length > 0) {
        if ($('div#onepagecheckoutps #checkbox_create_invoice_address').is(':checked')) {
            invoice_id = $('#invoice_id').val();
        }
    } else {
        invoice_id = $('#invoice_id').val();
    }
    var _extra_data = Review.getFieldsExtra({});
    var _data = $.extend({}, _extra_data, {
        'url_call': prestashop.urls.pages.order + '?checkout=1&rand=' + new Date().getTime(),
        'is_ajax': true,
        'dataType': 'json',
        'action': (OnePageCheckoutPS.IS_LOGGED ? 'placeOrder' : 'createCustomerAjax'),
        'id_customer': (!$.isEmpty(AppOPC.$opc_step_one.find('#customer_id').val()) ? AppOPC.$opc_step_one.find('#customer_id').val() : ''),
        'id_address_delivery': (!$.isEmpty(AppOPC.$opc_step_one.find('#delivery_id').val()) ? AppOPC.$opc_step_one.find('#delivery_id').val() : ''),
        'id_address_invoice': 0,
        'is_new_customer': (AppOPC.$opc_step_one.find('#checkbox_create_account_guest').is(':checked') ? 0 : 1),
        'fields_opc': JSON.stringify(fields),
    });
    var _json = {
        data: _data,
        beforeSend: function () {
            console.log("before send");
        },
        success: function (data) {
            console.log("guardado correctamente");
            $('#buyButton').attr('disabled', true);
            $("[data-payment=culqi]").attr('disabled', true);
            generateOrder(e, value);
        },
        complete: function () {
            console.log("guardado completamente");
        }
    };
    $.makeRequest(_json);
}

$(document).ready(function () {
    var checkDiv = setInterval(function () {
        const paymentMethodRadio = $('input[type=radio][name=payment-option]');
        var btnplaceOrderWidth = $("#btn_place_order").width();
        var btnplaceOrderCustomWidth = $(".custom_btn_onepage_culqi").width();
        var buyButtonWidth = $("#buyButton").width();
        if ((btnplaceOrderWidth > 0 || btnplaceOrderCustomWidth > 0) && buyButtonWidth > 0) {
            clearInterval(checkDiv);
            onepageCheckoutCulqi(paymentMethodRadio.filter(":checked").val());
            $("[data-payment=culqi]").click(function (event) {
                if (paymentMethodRadio.filter(":checked").val() == "culqi") {
                    $("#buyButton").trigger("click");
                }
            });
        }
    },
        10);
});

/**** END *****/
Culqi3DS.options = {
    closeModalAction: () => window.location.reload(true), // ACTION CUANDO SE CIERRA EL MODAL
};

window.addEventListener("message", async function (event) {

    if (event.origin === window.location.origin) {
        const { parameters3DS, error } = event.data;
        if (parameters3DS) {
            var token = Culqi.token.id;
            var email = Culqi.token.email;

            $(document).ajaxStart(function () {
                run_waitMe();
            });
            $(document).ajaxComplete(function () {
            });
            var payloadCharge = {
                ajax: true,
                action: 'displayAjax',
                token_id: Culqi.token.id,
                email: Culqi.token.email,
                device: device,
                parameters3DS: parameters3DS
            };
            if (Culqi.token.metadata != undefined && Culqi.token.metadata.installments != undefined){
                payloadCharge.installments = Culqi.token.metadata.installments;
            }

            $.ajax({
                url: fnReplace(phpData.chargeajax_url),
                data: payloadCharge,
                type: "POST",
                dataType: 'json',
                success: function (data, textStatus, xhr) {
                    console.log('data::::::::::::', data);
                    var result = "";

                    if (data.constructor == String) {
                        result = JSON.parse(data);
                    }
                    if (data.constructor == Object) {
                        result = JSON.parse(JSON.stringify(data));
                    }
                    if (result.object === 'charge') {
                        var card_number = result['source']['card_number'];
                        var card_brand = result['source']['iin']['card_brand'] + ' ' + result['source']['iin']['card_category'] + ' ' + result['source']['iin']['card_type'];
                        var chargeid = result['id'];
                        var brand = result['source']['iin']['card_brand']
                        showResult('green', result['user_message']);

                        var url = fnReplace(phpData.postpayment_url);
                        var success_url = url + '?card_number=' + card_number + '&card_brand=' + card_brand + '&orderid=' + orderid + '&chargeid=' + chargeid;

                        console.log("Marca de tarjeta: " + result['source']['iin']['card_brand']);

                        if (brand.toUpperCase() == "MASTERCARD") {
                            fnMcSonic(success_url);
                        } else if (brand.toUpperCase() == "VISA") {
                            fnBrandvisa(success_url);
                        } else {
                            location.href = success_url;
                        }
                    }
                    if (result.object === 'error') {
                        $("#loadingloginculqi").remove();
                        $('#showresult').show();
                        Culqi.close();
                        showResult('red', result['user_message']);
                    }

                },
                error: function (error, textStatus, xhr) {
                    showResult('red', error['user_message']);
                    $('#showresult').show();
                    console.log('error:::', error);
                    e.preventDefault();
                },
                beforeSend: function () {
                    //run_waitMe();
                }
            });
        }

        if (error) {
            showResult('red', error);
            $('#showresult').show();
            $("#loadingloginculqi").remove();
            Culqi.close();
            console.log(error);
        }
    }
}, false);

Culqi3DS.publicKey = phpData.llave_publica;
//var device = await Culqi3DS.generateDevice();
const device_aux = Promise.resolve(Culqi3DS.generateDevice());
device_aux.then(value => {
    $('#checkout').on('click', '#buyButton', function (e) {
        var vaidate_opc_aux = $("#form_onepagecheckoutps").submit();
        if (validateForm() == null) {
            $('#buyButton').attr('disabled', true);
            $("[data-payment=culqi]").attr('disabled', true);
            generateOrder(e, value);
        } else if (validateForm()) {
            createCustomerCulqiPs(e, value);
        }
    });
}).catch(err => {
    console.log(err);
});
var orderid = '';


$(document).ready(function () {
    var validateButtonOrder = setInterval(function () {
        $('input[type=radio]').each(function () {
            if ($(this).data('module-name') == 'culqi' && $(this).is(':checked')) {
                $('div#payment-confirmation').hide();
            } else {
                $('div#payment-confirmation').show();
            }
        })
    }, 100);

    $('#payment-confirmation > .ps-shown-by-js > button').click(function (e) {
        var myPaymentMethodSelected = $('.payment-options').find("input[data-module-name='culqi']").is(':checked');
        if (myPaymentMethodSelected) {
            Culqi.createToken();
            return false;
        }

    });
});

function setCheckout() {
    const publicKey = setPublicKey();
    const config = setConfig();
    Culqi = new CulqiCheckout(publicKey, config);
    Culqi.culqi = culqi
}

function setConfig() {
    const settings = setSetting();
    const client = setClient();
    const options = setOptions();
    const appearance = setAppearance();


    const config = {
        settings,
        client,
        options,
        appearance
    };

    return config
}

function setPublicKey() {
    return phpData.llave_publica;
}

function setSetting() {
    const setting = {
        title: phpData.commerce,
        currency: phpData.currency,
        amount: Math.ceil(phpData.total),
        culqiclient: 'prestashop',
        culqiclientversion: phpData.psversion,
        culqipluginversion: phpData.CULQI_PLUGIN_VERSION
    }

    // Agregar el orden si existe
    if (orderid != '') {
        setting.order = orderid;
    }

    // Agregar llave publica si existe
    if (phpData.rsa_id && phpData.rsa_pk) {
        setting.xculqirsaid = phpData.rsa_id;
        setting.rsapublickey = phpData.rsa_pk;
    }

    return setting;
}

function setClient() {
    const client = {
        email: phpData.email
    };

    return client;
}

function setPaymentMethods() {
    const paymentMethods = {
        tarjeta: (phpData.tarjeta === "true"),
        yape: (phpData.yape === "true"),
        billetera: (phpData.billetera === "true"),
        bancaMovil: (phpData.banca_movil === "true"),
        agente: (phpData.agente === "true"),
        cuotealo: (phpData.cuetealo === "true"),
    }

    return paymentMethods;
}

function setOptions() {
    const paymentMethods = setPaymentMethods();

    const options = {
        lang: 'auto',
        installments: true,
        modal: true,
        paymentMethods,
        paymentMethodsSort: Object.keys(paymentMethods)
    }
    return options;
}

function validarArrayVacio(array) {
    return array.every(elemento => typeof elemento === 'string' && elemento.trim() === '');
}


function setAppearance() {
    const appearence = {
        theme: "default",
        hiddenCulqiLogo: false,
        hiddenBannerContent: false,
        hiddenBanner: false,
        hiddenToolBarAmount: false,
        menuType: "sidebar", // sidebar / sliderTop / select
        logo: phpData.url_logo
    }


    const colors = phpData.color_pallete;
    if (!validarArrayVacio(colors)) {
        const defaultStyle = {};
        defaultStyle.bannerColor = colors[0];
        defaultStyle.buttonBackground = colors[1];
        defaultStyle.menuColor = colors[1];
        defaultStyle.linksColor = colors[1];
        defaultStyle.priceColor = colors[1];

        appearence.defaultStyle = defaultStyle;
    }

    return appearence;
}

function generateOrder(e, device) {
    window.device = device;
    /*if($("#" + name).length == 0) {
        //it doesn't exist
    }*/
    if ((phpData.banca_movil === "true") || (phpData.agente === "true") || (phpData.billetera === "true") || (phpData.cuetealo === "true")) {
        $.ajax({
            url: fnReplace(phpData.generate_order_url),
            data: {},
            type: "POST",
            dataType: 'json',
            success: function (response) {
                console.log('Se genero una orden:::', response);
                orderid = response;
                setCheckout();
                $('#buyButton').removeAttr('disabled');
                $("[data-payment=culqi]").removeAttr('disabled');
                Culqi.open();
                $('#showresult').hide();
                e.preventDefault();
            },
            error: function (error) {
                console.log('Error al generar orden :::', error);
                $('#showresult').show();
                orderid = '';
                setCheckout();
                $('#buyButton').removeAttr('disabled');
                $("[data-payment=culqi]").removeAttr('disabled');
                Culqi.open();
                $('#showresult').hide();
                e.preventDefault();
            }
        });
    } else {
        $('#showresult').show();
        console.log('No se genero orden');
        orderid = '';
        setCheckout();
        $('#buyButton').removeAttr('disabled');
        Culqi.open();
        $('#showresult').hide();
        e.preventDefault();
    }
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

// Process to Pay
function culqi() {

    var ps_order_id = '';

    if (Culqi.order) {

        $(document).ajaxStart(function () {
        });

        $(document).ajaxComplete(function () {
        });


        var culqi_order_id = Culqi.order.id;

        $.ajax({
            url: fnReplace(phpData.registersale_url),
            data: { order_id: culqi_order_id },
            type: "POST",
            dataType: 'json',
            success: function (response) {
                console.log('response:::', response);
                ps_order_id = response;

            },
            error: function (error) {
                console.log('error:::', error);
                e.preventDefault();
            }
        });

        var id = setInterval(function () {
            if (!Culqi.isOpen) {
                run_waitMe();
                clearInterval(id);
                var orderid = culqi_order_id;
                var url = fnReplace(phpData.postpaymentpending_url);
                location.href = url + '?ps_order_id=' + ps_order_id;
            }
        }, 1000);

    } else if (Culqi.token) {
        Culqi.close();
        run_waitMe();
        var token = Culqi.token.id;
        var email = Culqi.token.email;

        $(document).ajaxStart(function () {
        });
        $(document).ajaxComplete(function () {
        });
        var payloadCharge = {
            ajax: true,
            action: 'displayAjax',
            token_id: Culqi.token.id,
            email: Culqi.token.email,
            device: device
        };
        if (Culqi.token.metadata != undefined && Culqi.token.metadata.installments != undefined){
            payloadCharge.installments = Culqi.token.metadata.installments;
        }

        $.ajax({
            url: fnReplace(phpData.chargeajax_url),
            data: payloadCharge,
            type: "POST",
            dataType: 'json',
            success: function (data, textStatus, xhr) {
                console.log('data:::modificada', data);
                if (data.action_code == 'REVIEW') {
                    $("#loadingloginculqi").remove();
                    Culqi3DS.settings = {
                        charge: {
                            totalAmount: Math.ceil(phpData.total),
                            returnUrl: phpData.BASE_URL //URL DEL CHECKOUT DEL COMERCIO
                        },
                        card: {
                            email: email,
                        }
                    };
                    Culqi3DS.initAuthentication(token);
                } else {

                    var result = "";

                    if (data.constructor == String) {
                        result = JSON.parse(data);
                    }
                    if (data.constructor == Object) {
                        result = JSON.parse(JSON.stringify(data));
                    }
                    console.log('result.object:::', result.object);
                    if (result.object === 'charge') {
                        console.log("Entro al charge");
                        //run_waitMe();
                        var card_number = result['source']['card_number'];
                        var card_brand = result['source']['iin']['card_brand'] + ' ' + result['source']['iin']['card_category'] + ' ' + result['source']['iin']['card_type'];
                        var chargeid = result['id'];
                        var brand = result['source']['iin']['card_brand']
                        showResult('green', result['user_message']);

                        var url = fnReplace(phpData.postpayment_url);
                        var success_url = url + '?card_number=' + card_number + '&card_brand=' + card_brand + '&orderid=' + orderid + '&chargeid=' + chargeid;

                        console.log("Marca de tarjeta: " + result['source']['iin']['card_brand']);

                        if (brand.toUpperCase() == "MASTERCARD") {
                            fnMcSonic(success_url);
                        } else if (brand.toUpperCase() == "VISA") {
                            fnBrandvisa(success_url);
                        } else {
                            location.href = success_url;
                        }
                    }
                    if (result.object === 'error') {
                        $("#loadingloginculqi").remove();
                        Culqi.close();
                        showResult('red', result['user_message']);
                        $('#showresult').show();
                    }
                }
            },
            error: function (error, textStatus, xhr) {
                console.log('error:::', error);
                $("#loadingloginculqi").remove();
                $('#showresult').show();
                Culqi.close();
            }
        });


    } else {

        console.log(Culqi.error);
        $("#loadingloginculqi").remove();
        if (Culqi.error) {
            showResult('red', Culqi.error.user_message);
        }
    }

}

const run_waitMe = (background = 'rgba(0,0,0,0.7)') => {
    jQuery('body').append(`
    <div id="loadingloginculqi" style="background: ${background}">
        <div>Cargando <img width="14" src="${loaderImg}" /></div>
    </div>`);
}

//

$(document).ready(function () {
    // Add class to radio buttons
    $('input[name="payment-option"]').each(function () {
        var container = $(this).closest('.payment-option');
        var labelculqi = container.find('label');
        var paymentText = container.find('label').find('span');
        let tarjeta = (phpData.tarjeta === "true");
        let bancaMovil = (phpData.banca_movil === "true");
        let yape = (phpData.yape === "true");
        let agente = (phpData.agente === "true");
        let billetera = (phpData.billetera === "true");
        let cuotealo = (phpData.cuetealo === "true");
        var txt_general = 'Acepta pagos con ';
        var txt = '';
        var txtPE = '';
        if (paymentText.text() == 'Culqi') {
            container.css({ 'display': 'inline-flex', 'justify-content': 'space-between', 'width': ' 100%' });
            labelculqi.css({ 'display': 'inline-flex', 'justify-content': 'space-between', 'width': ' 100%' });
            paymentText.hide();
            paymentText.next().addClass('culqi-logo');
            var parrafo = document.querySelector('.culqi-checkout-text');
            var logos = '';
            if (tarjeta) {
                logos = logos + '<img  style="margin-right: 5px" src="/modules/culqi/cards.svg" />';
                txt = txt + 'tarjetas de débito y crédito';
            }
            if (yape) {
                logos = logos + '<img style="margin-right: 5px" src="/modules/culqi/yape.svg" />';
                if (tarjeta) {
                    txt = txt + ', ';
                }
                txt = txt + 'Yape';
            }
            if (agente || bancaMovil || billetera || cuotealo) {
                logos = logos + '<img  style="margin-right: 5px" src="/modules/culqi/pagoefectivo.svg" />';
                if (tarjeta || yape) {
                    txt = txt + ', ';
                }
                txt = txt + 'Cuotéalo BCP y PagoEfectivo';
                txtPE = ' (billeteras móviles, agentes y bodegas)';
            }
            txt = '<strong>' + txt + '</strong>';
            txt = txt_general + txt + txtPE;
            txt = txt + '.';

            paymentText.next().after('<span id = "logos_marcas">' + logos + '</span>');
            parrafo.innerHTML = txt;
        }
    });
});