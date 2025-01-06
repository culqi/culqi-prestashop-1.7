<style type="text/css">
	.div-culqi-center {
		justify-content: center;
		text-align: center;
		align-items: center;
	}
	.culqi-payment-logos {
		max-width: 300px;
        width: 98%;
        display: none;
        height: 24px;
	}
    .custom_btn_onepage_culqi {
        margin-top: 6.4px;
        background: #005cb9;
        color: white;
        padding: 0px 20px;
        height: 40px;
        font-size: 20px;
        border: 0;
    }
    .custom_btn_onepage_culqi:hover {
        opacity: 0.8;
        background-color: #005cb9;
    }
    .culqi-img-cards {
        position: absolute;
        right: 30px;
    }
    .culqi-logo {
        height: 20px;
    }
    .culqi-checkout-text {
        color: #000;
        padding: 8px;
        text-align: left;
        font-size: 12px;
        margin-bottom: 0;
    }
    #onepagecheckoutps .culqi-payment-logos {
        display: inline;
    }
    body#checkout .additional-information {
        margin-top: 8px !important;
    }
    @media only screen and (max-width: 480px) {
        .culqi-img-cards {
            height: 20px;
        }
    }
    #order-created-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: transparent;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
    }
    
    .no-scroll {
        overflow: hidden !important;
    }
    
    .culqi-modal-content {
        width: 100%;
        height: 100%;
        background: transparent;
    }
    
    #order-created-modal iframe {
        width: 100%;
        height: 100%;
        border: 0;
        border-radius: 4px;
    }
    
    .woocommerce-loader {
        display: none;
        background-color: rgb(0 0 0 / .6);
        place-items: center;
        z-index: 100;
        inset: 0px;
        position: fixed;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        gap: 7px;
        color: #fff;
        font-size: 12px;
    }
    
    .flex {
        display: flex !important;
    }
</style>

<div class="row culqi_payment">
    <div class="">
        <div class="div-culqi-center">
			<img class="culqi-payment-logos" alt="" src ="/modules/culqi/cards.svg" />
            <p class="culqi-checkout-text">Acepta pagos con tarjetas de <strong>débito y crédito; Yape, Cuotéalo BCP y PagoEfectivo</strong> (billeteras móviles, agentes y bodegas).</p>
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

<div id="order-created-modal" style="display:none;">
    <div class="culqi-modal-content">
        <iframe allowtransparency="true" style="background: transparent" src="#"></iframe>
    </div>
</div>