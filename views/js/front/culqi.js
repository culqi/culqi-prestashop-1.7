$(document).ready(function (){
    Culqi.publicKey = data_culqi.llave_publica;
    Culqi.useClasses = true;
    Culqi.init();

    $(".culqi-expm").on("input",function (){
        this.value = this.value.replace(/[^0-9]/g,'');
        var expm = $('.culqi-expm').val().length;
        if (expm == 1){
            $('.culqi-expm').css({"outline-color": "#ff4c4c",
                "outline-style": "solid",
                "outline-width": ".1875rem"
            });
        } else if (expm == 2){
            $('.culqi-expm').css({"outline-color": "white",
                "outline-width": "0"
            });
        }
    });

    $(".culqi-expy").on("input",function (){
        this.value = this.value.replace(/[^0-9]/g,'');
        var expy = $('.culqi-expy').val().length;
        if (expy < 4){
            $('.culqi-expy').css({"outline-color": "#ff4c4c",
                "outline-style": "solid",
                "outline-width": ".1875rem"
            });
        } else if (expy == 4){
            $('.culqi-expy').css({"outline-color": "white",
                "outline-width": "0"
            });
        }
    });

    $('#payment-confirmation > .ps-shown-by-js > button').click(function (e){
        var myPaymentMethodSelected = $('.payment-options').find("input[data-module-name='culqi']").is(':checked');

        if (myPaymentMethodSelected){
            Culqi.createToken();
            return false;
        }
    });
});

// Process to Pay
function culqi(){
    if (Culqi.token){
        $(document).ajaxStart(function (){
            run_waitMe();
        });
        $(document).ajaxComplete(function (){
            $('body').waitMe('hide');
        });
        var installments = (Culqi.token.metadata.installments == undefined) ? 0 : Culqi.token.metadata.installments;
        $.ajax({
            url: fnReplace(data_culqi.url_charge_ajax),
            data: {
                ajax: true,
                action: 'displayAjax',
                token_id: Culqi.token.id,
                installments: installments
            },
            type: "POST",
            dataType: 'json',
            success: function (data){
                if (data === "Imposible conectar a Culqi API"){
                    $('body').waitMe('hide');
                    showResult('red',data + ": aumentar el timeout de la consulta");
                } else if (data === "Error de autenticación"){
                    $('body').waitMe('hide');
                    showResult('red',data + ": verificar si su Llave Secreta es la correcta");
                } else {
                    var result = "";
                    if (data.constructor == String){
                        result = JSON.parse(data);
                    }
                    if (data.constructor == Object){
                        result = JSON.parse(JSON.stringify(data));
                    }
                    if (result.object === 'charge'){
                        showResult('green',result.outcome.user_message);
                        $('#payment-confirmation > .ps-shown-by-js > button').prop("disabled",true);
                        redirect();
                    }
                    if (result.object === 'error'){
                        $('body').waitMe('hide');
                        showResult('red',result.user_message);
                    }
                }
            }
        });
    } else {
        $('body').waitMe('hide');
        if (Culqi.error){
            showResult('red',Culqi.error.user_message);
        }
    }
}

function run_waitMe(){
    $('body').waitMe({
        effect: 'orbit',
        text: 'Procesando pago...',
        bg: 'rgba(255,255,255,0.7)',
        color: '#28d2c8'
    });
}

function showResult(style,message){
    $('#showresult').removeClass('hide');
    $('#showresultcontent').attr('class','');
    $('#showresultcontent').addClass(style);
    $('#showresultcontent').html(message);
}

function redirect(){
    var url = fnReplace(data_culqi.url_postpayment);
    location.href = url;
}

function fnReplace(url){
    return url.replace(/&amp;/g,'&');
}

//$(function (){
//    $("#form-payment").checkout({
//        inputs: [
//            {id: "#input-card",
//                type: "card"},
//            {id: "#input-cvc",
//                type: "cvc"},
//            {id: "#input-email",
//                type: "email"
//            }
//        ]
//    });
//});