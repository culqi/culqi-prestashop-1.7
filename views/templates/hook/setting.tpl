<style>
    .iframe-container {
        display: flex;
        flex-direction: column;
        position: absolute;
        inset: 0px;
        width: calc(100% + 20px);
        height: 100svh;
        left: -20px;
        right: -20px;
        z-index: 100;
    }
    .iframe-container iframe {
        position: relative;
        border: none;
        width: 100%;
        flex: 1 1 0%;
        display: flex;
    }
    .wrap {
        position: relative;
    }
</style>

<div class="wrap">
    <div class="iframe-container">
        <iframe 
            src="{$culqi_config_url}?platform=prestashop&status={$fields_value.status|escape:'url'}&pk={$fields_value.pk|escape:'url'}&merchant={$fields_value.merchant|escape:'url'}&activePaymentMethods={$fields_value.payment_methods|escape:'url'}&shop={$fields_value.shop_url|escape:'url'}" 
            width="100%">
        </iframe>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        window.addEventListener('message', function(event) {
            if (event.data.action === 'saveConfig') {
                const data = event.data.data;
                console.log(data);
                $.ajax({
                    url: "{$save_config_ajax_url}",
                    type: 'POST',
                    data: {
                        action: 'saveConfig',
                        pluginStatus: data.pluginStatus,
                        publicKey: data.publicKey,
                        merchant: data.merchant,
                        rsa_pk: data.rsaPkPlugin,
                        payment_methods: data.paymentMethods
                    },
                    success: function(response) {
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error: ' + status + error);
                    }
                });
            }
            //if (event.origin !== 'http://localhost:5173') return;
            if (event.data.action === 'reload') {
                location.reload();
            }
        }, false);
    });
        
</script>