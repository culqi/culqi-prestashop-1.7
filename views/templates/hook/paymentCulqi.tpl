<style type="text/css">
	.div-culqi-center {
		justify-content: center;
		text-align: center;
		align-items: center;
	}
	.culqi-payment-logos {
		max-width: 300px;
        width: 98%;
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
</style>

<div class="row culqi_payment">
    <div class="">
        <div class="div-culqi-center">
			<img class="culqi-payment-logos" src="/modules/culqi/logo_cards.png" alt="" />
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