$(function () {


    let pass = $('#registration_form_plainPassword_first');
    let confirmpass = $('#registration_form_plainPassword_second');
    let pattern = /(?=.*[A-Z])(?=.*[0-9])(?=.*[ !\"\#\$%&\'\(\)*+,\-.\/:;<=>?@[\\^\]_`\{|\}~])^.{8,4096}$/;

    confirmpass.on('input', () => {
       validate(confirmpass, pass)
    })


    pass.on('input', () => {

        validate(pass, confirmpass)
    });

    // FONCTION QUI MET LA CLASS IS-VALID OU IS-INVALID
    function validate(one, two){
        if (one.val() === two.val()) {
            two.addClass('is-valid');
            one.addClass('is-valid');
        } else {
            two.removeClass('is-valid');
            one.removeClass('is-valid');
        }
        if (!one.val().match(pattern)){
            one.addClass('is-invalid');
            one.removeClass('is-valid');
        }else{
            one.addClass('is-valid');
            one.removeClass('is-invalid');
        }
    }

    // AFICHAGE DU MOT DE PASSE
    $('#afficher').on('change', ()=>{
        if ($('#afficher').prop('checked')){
            pass.attr('type', 'text');
            confirmpass.attr('type', 'text');
        }else{
            pass.attr('type', 'password');
            confirmpass.attr('type', 'password');

        }
    })


})
;


