$(document).ready(function () {
    var authtypeField = $('input[name="form_authtype"]');

    if (authtypeField.length) {
        showFieldsForAuthtype($('input[name="form_authtype"]:checked'));
        authtypeField.on('change', function () { showFieldsForAuthtype($('input[name="form_authtype"]:checked')) });
    }
});

function showFieldsForAuthtype(field) {
    if (typeof field === 'object' && typeof field.val === 'function' && (field.val() === "1" || field.val() === "2")) {
        $('#wrap_form_expires_in, #wrap_form_token_secret').show();
    } else {
        $('#wrap_form_expires_in, #wrap_form_token_secret').hide();
    }

    if (typeof field === 'object' && typeof field.val === 'function' && field.val() === "2") {
        $('#wrap_form_accesstoken_secret').show();
    } else {
        $('#wrap_form_accesstoken_secret').hide();
    }
}