<style type="text/css">
	.div-culqi-center {
		justify-content: center;
		text-align: center;
		align-items: center;
	}
	.culqi-payment-logos {
		max-width: 300px;
	}
</style>

<link rel="stylesheet" href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/waitMe.min.css" type="text/css"
      media="all">

<div class="row culqi_payment">
    <div class="row">
		<div class="row div-culqi-center">
			<img class="culqi-payment-logos" src="{$module_dir|escape:'htmlall':'UTF-8'}logo_cards.png" alt="" />
		 </div>
		<br>
		<div class="row div-culqi-center">
			<button id="buyButton" class="btn btn-success"> Pagar</button>
		</div>
    </div>
    <div class="row">
        <p id="showresult" class="text-center" style="margin-top: 2em; text-align: center; display: none;">
            <b id="showresultcontent" class="text-danger" style="color:red; font-size: 13px;"></b>
        </p>
    </div>
</div>

{literal}

<script type="text/javascript">
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
    Culqi3DS.options = {
        closeModalAction: () => window.location.reload(true), // ACTION CUANDO SE CIERRA EL MODAL
    };

    window.addEventListener("message", async function (event) {

        if (event.origin === window.location.origin) {
            const {parameters3DS, error} = event.data;
            if (parameters3DS) {
                var token = Culqi.token.id;
                var email = Culqi.token.email;

                $(document).ajaxStart(function () {
                    run_waitMe();
                });
                $(document).ajaxComplete(function () {
                });

                var installments = (Culqi.token.metadata.installments == undefined) ? 0 : Culqi.token.metadata.installments;

                $.ajax({
                    url: fnReplace("{/literal}{$link->getModuleLink('culqi', 'chargeajax', [], true)|escape:'htmlall':'UTF-8'}{literal}"),
                    data: {
                        ajax: true,
                        action: 'displayAjax',
                        token_id: Culqi.token.id,
                        installments: installments,
                        email: Culqi.token.email,
                        device: device,
                        parameters3DS: parameters3DS
                    },
                    type: "POST",
                    dataType: 'json',
                    success: function (data, textStatus, xhr) {
                        console.log('data:::', data);
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
                            showResult('green', result['user_message']);

                            var url = fnReplace("{/literal}{$link->getModuleLink('culqi', 'postpayment', [], true)|escape:'htmlall':'UTF-8'}{literal}");
                            location.href = url + '?card_number=' + card_number + '&card_brand=' + card_brand + '&orderid=' + orderid + '&chargeid=' + chargeid;
                        }
                        if (result.object === 'error') {
                            $('body').waitMe('hide');
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
                        run_waitMe();
                    }
                });
            }

            if (error) {
                showResult('red', error);
                $('#showresult').show();
                $('body').waitMe('hide');
                Culqi.close();
                console.log(error);
            }
        }
    }, false);

    Culqi3DS.publicKey = "{/literal}{$llave_publica|escape:'htmlall':'UTF-8'}{literal}";
    //var device = await Culqi3DS.generateDevice();
    const device_aux = Promise.resolve(Culqi3DS.generateDevice());
    device_aux.then(value => {
      $('#buyButton').on('click', function (e) {
            $('#buyButton').attr('disabled', true);
            generateOrder(e, value);
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

        Culqi.publicKey = '{/literal}{$llave_publica|escape:'htmlall':'UTF-8'}{literal}';
        Culqi.useClasses = true;
        Culqi.init();
        let tarjeta = ('{/literal}{$tarjeta|escape:'htmlall':'UTF-8'}{literal}' === "true");
        let bancaMovil = ('{/literal}{$banca_movil|escape:'htmlall':'UTF-8'}{literal}' === "true");
        let yape = ('{/literal}{$yape|escape:'htmlall':'UTF-8'}{literal}' === "true");
        let agente = ('{/literal}{$agente|escape:'htmlall':'UTF-8'}{literal}' === "true");
        let billetera = ('{/literal}{$billetera|escape:'htmlall':'UTF-8'}{literal}' === "true");
        let cuotealo = ('{/literal}{$cuetealo|escape:'htmlall':'UTF-8'}{literal}' === "true");

        Culqi.options({
            lang: 'auto',
            paymentMethods: {
                tarjeta: tarjeta,
                bancaMovil: bancaMovil,
                yape: yape,
                agente: agente,
                billetera: billetera,
                cuotealo: cuotealo
            },
            installments: true,
            style: {
                bannerColor: '{/literal}{$color_pallete[0]|escape:'htmlall':'UTF-8'}{literal}',
                imageBanner: '',
                buttonBackground: '{/literal}{$color_pallete[1]|escape:'htmlall':'UTF-8'}{literal}',
                menuColor: '{/literal}{$color_pallete[1]|escape:'htmlall':'UTF-8'}{literal}',
                linksColor: '{/literal}{$color_pallete[1]|escape:'htmlall':'UTF-8'}{literal}',
                buttontext: '{/literal}{$color_pallete[0]|escape:'htmlall':'UTF-8'}{literal}',
                priceColor: '{/literal}{$color_pallete[1]|escape:'htmlall':'UTF-8'}{literal}',
                logo: '{/literal}{$url_logo|escape:'htmlall':'UTF-8'}{literal}'
            }
        });

        $('#payment-confirmation > .ps-shown-by-js > button').click(function (e) {
            var myPaymentMethodSelected = $('.payment-options').find("input[data-module-name='culqi']").is(':checked');
            if (myPaymentMethodSelected) {
                Culqi.createToken();
                return false;
            }

        });
    });

    function generateOrder(e, device) {
        window.device = device;
        if ({/literal}{$banca_movil|escape:'htmlall':'UTF-8'}{literal} || {/literal}{$agente|escape:'htmlall':'UTF-8'}{literal} || {/literal}{$billetera|escape:'htmlall':'UTF-8'}{literal} || {/literal}{$cuetealo|escape:'htmlall':'UTF-8'}{literal}) {
            $.ajax({
                url: fnReplace("{/literal}{$link->getModuleLink('culqi', 'generateorder', [], true)|escape:'htmlall':'UTF-8'}{literal}"),
                data: {},
                type: "POST",
                dataType: 'json',
                success: function (response) {
                    console.log('response:::', response);
                    Culqi.settings({
                        title: '{/literal}{$commerce|escape:'htmlall':'UTF-8'}{literal}',
                        currency: '{/literal}{$currency|escape:'htmlall':'UTF-8'}{literal}',
                        amount: {/literal}{$total|escape:'htmlall':'UTF-8'}{literal},
                        order: response,
                        culqiclient: 'prestashop',
                        culqiclientversion: '{/literal}{$psversion|escape:'htmlall':'UTF-8'}{literal}',
                    });
                    orderid = response;
                    $('#buyButton').removeAttr('disabled');
                    Culqi.open();
                    $('#showresult').hide();
                    e.preventDefault();
                },
                error: function (error) {
                    console.log('error:::', error);
                    $('#showresult').show();
                    Culqi.settings({
                        title: '{/literal}{$commerce|escape:'htmlall':'UTF-8'}{literal}',
                        currency: '{/literal}{$currency|escape:'htmlall':'UTF-8'}{literal}',
                        amount: {/literal}{$total|escape:'htmlall':'UTF-8'}{literal},
                        culqiclient: 'prestashop',
                        culqiclientversion: '{/literal}{$psversion|escape:'htmlall':'UTF-8'}{literal}',
                    });
                    orderid = 'ungenereted';
                    $('#buyButton').removeAttr('disabled');
                    Culqi.open();
                    $('#showresult').hide();
                    e.preventDefault();
                }
            });
        } else {
            $('#showresult').show();
            Culqi.settings({
                title: '{/literal}{$commerce|escape:'htmlall':'UTF-8'}{literal}',
                currency: '{/literal}{$currency|escape:'htmlall':'UTF-8'}{literal}',
                amount: {/literal}{$total|escape:'htmlall':'UTF-8'}{literal},
                culqiclient: 'prestashop',
                culqiclientversion: '{/literal}{$psversion|escape:'htmlall':'UTF-8'}{literal}',
            });
            orderid = 'ungenereted';
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
            var url = fnReplace("{/literal}{$link->getModuleLink('culqi', 'postpaymentpending', [], true)|escape:'htmlall':'UTF-8'}{literal}");
        } else if (Culqi.token) {
            var url = fnReplace("{/literal}{$link->getModuleLink('culqi', 'postpayment', [], true)|escape:'htmlall':'UTF-8'}{literal}");
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
                url: fnReplace("{/literal}{$link->getModuleLink('culqi', 'registersale', [], true)|escape:'htmlall':'UTF-8'}{literal}"),
                data: {order_id: culqi_order_id},
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
                    var orderid = Culqi.order['id'];
                    var url = fnReplace("{/literal}{$link->getModuleLink('culqi', 'postpaymentpending', [], true)|escape:'htmlall':'UTF-8'}{literal}");
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

            var installments = (Culqi.token.metadata.installments == undefined) ? 0 : Culqi.token.metadata.installments;
            $.ajax({
                url: fnReplace("{/literal}{$link->getModuleLink('culqi', 'chargeajax', [], true)|escape:'htmlall':'UTF-8'}{literal}"),
                data: {
                    ajax: true,
                    action: 'displayAjax',
                    token_id: Culqi.token.id,
                    installments: installments,
                    email: Culqi.token.email,
                    device: device
                },
                type: "POST",
                dataType: 'json',
                success: function (data, textStatus, xhr) {
                    console.log('data:::', data);
                    if (data.action_code == 'REVIEW') {
                        $('body').waitMe('hide');
                        Culqi3DS.settings = {
                            charge: {
                                totalAmount: {/literal}{$total|escape:'htmlall':'UTF-8'}{literal},
                                returnUrl: "{/literal}{$BASE_URL|escape:'htmlall':'UTF-8'}{literal}" //URL DEL CHECKOUT DEL COMERCIO
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
                            run_waitMe();
                            var card_number = result['source']['card_number'];
                            var card_brand = result['source']['iin']['card_brand'] + ' ' + result['source']['iin']['card_category'] + ' ' + result['source']['iin']['card_type'];
                            var chargeid = result['id'];
                            showResult('green', result['user_message']);

                            var url = fnReplace("{/literal}{$link->getModuleLink('culqi', 'postpayment', [], true)|escape:'htmlall':'UTF-8'}{literal}");
                            location.href = url + '?card_number=' + card_number + '&card_brand=' + card_brand + '&orderid=' + orderid + '&chargeid=' + chargeid;


                        }
                        if (result.object === 'error') {
                            $('body').waitMe('hide');
                            Culqi.close();
                            showResult('red', result['user_message']);
                            $('#showresult').show();
                        }
                    }
                },
                error: function (error, textStatus, xhr) {
                    console.log('error:::', error);
                    $('body').waitMe('hide');
                    $('#showresult').show();
                    Culqi.close();
                }
            });


        } else {

            console.log(Culqi.error);
            $('body').waitMe('hide');
            if (Culqi.error) {
                showResult('red', Culqi.error.user_message);
            }
        }

    }
    window.culqi = culqi;
    function run_waitMe() {
        $('body').waitMe({
            effect: 'bounce',
            text: 'Cargando. Espere por favor',
            bg: 'rgba(0,0,0, 0.7)',
            color: '#ffffff'
        });
    }
</script>
{/literal}