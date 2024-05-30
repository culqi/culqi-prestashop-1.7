
/*MASTERCARD*/
const fnMcSonic = (success_url) => {
    $('#loadingloginculqi').html(`<div id="brandMastercard">
    <mc-sonic id="mc-sonic" style="height: 40%;" type="default" clear-background></mc-sonic> </div>`);
    const time = 2000;
    document.addEventListener('sonicCompletion', onCompletion(success_url, time));
    let mc_component = document.getElementById("mc-sonic");
    mc_component.play();
};

const onCompletion = (success_url, time) => {
    setTimeout(() => {
        location.href = success_url;
    }, time);
};

/*VISA*/
const fnBrandvisa = (success_url) => {
    $('body').html(`<div id="brand-wrapper">
        <div id="visa-sensory-branding"></div>
    </div>`);

    VisaSensoryBranding.init({}, 
    `${modulePath}views/brands/visa/VisaSensoryBrandingSDK`);

    document.getElementById('visa-sensory-branding').addEventListener('visa-sensory-branding-end', function(e) {
        const time = 10;
        run_waitMe();
        onCompletion(success_url, time);
    });

    $('body').addClass('showVisa');
    setTimeout(function() { 
        VisaSensoryBranding.show();
    }, 100);
};