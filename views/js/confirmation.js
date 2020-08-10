const stateDescription = $('#stateDescription').val()
const stateIcon = $('#stateIcon').val()
const CIP = $('#stateCIP').val()
let msg_cip = '';

if (CIP && CIP !== '') {
  msg_cip = ' - CIP: ' + CIP
}

$('.h1.card-title').html('<i class="material-icons rtl-no-flip done">'+ stateIcon +'</i>' + stateDescription + msg_cip)
