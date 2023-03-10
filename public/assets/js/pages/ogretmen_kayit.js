/*!
 * dashmix - v5.4.0
 * @author pixelcave - https://pixelcave.com
 * Copyright (c) 2022
 */
Dashmix.onLoad((() => class {
    static initValidation() {
        Dashmix.helpers("jq-validation"),
            jQuery(".js-validation-signup").validate({
                rules: {
                    "tc_kimlik": {
                        required: !0,
                        minlength: 11,
                        maxlength: 11,
                    },

                    "email": {
                        required: !0,
                        emailWithDot: !0
                    },
                    "ad": {
                        required: !0,
                    },
                    "soyad": {
                        required: !0,
                    },
                    "photo": {
                        required: !0,
                    },

                    "dogum_tarihi": {
                        required: !0,
                    },
                    "gsm_no": {
                        minlength: 10,
                        maxlength: 10,
                        required: !0
                    },
                    "password": {
                        required: !0,
                        minlength: 8,
                        maxlength: 8
                    },
                    "password_again": {
                        required: !0,
                        equalTo: '#password'
                    },
                    "signup-terms": {
                        required: !0,
                    },
                    "okul": {
                        required: !0,
                    },
                    "bolum": {
                        required: !0,
                    },
                    "mezun_tarihi": {
                        required: !0,
                    },
                    "sertifikalar": {
                        required: !0,
                    },
                    "oncekiisler": {
                        required: !0,
                    },

                },
                messages: {
                    "tc_kimlik": {
                        required: "L??tfen T.C Kimlik numaran??z?? girin",
                        minlength: "L??tfen 11 haneli T.C Kimlik numaran??z?? girin",
                        maxlength: "L??tfen 11 haneli T.C Kimlik numaran??z?? girin",
                    },
                    "password": {
                        required: "L??tfen parola belirleyin",
                        minlength: "Parolan??z 8 haneli ve rakamlardan olu??mal??d??r",
                        maxlength: "Parolan??z 8 haneli ve rakamlardan olu??mal??d??r",
                        number: "L??tfen sadece rakamlardan olu??an bir parola belirleyin",
                    },
                    "password_again": {
                        required: "L??tfen parolan??z?? tekrar girin",
                        equalTo: "Parolan??z uyu??muyor",
                        number: "L??tfen sadece rakamlardan olu??an bir parola belirleyin",
                    },
                    "ad": "L??tfen isminizi girin",
                    "soyad": "L??tfen soyisminizi girin",
                    "email": "L??tfen ge??erli bir e-posta adresi girin",
                    "gsm_no": "10 Haneli telefon numaran??z?? ba????nda 0 olmadan girin",
                    "dogum_tarihi": "L??tfen do??um tarihinizi girin",
                    "photo": "L??tfen profil foto??raf?? y??kleyin",
                    "signup-terms": "??ye olmak i??in ??artlar?? ve ko??ullar?? kabul etmek zorundas??n??z!",
                    "okul": "L??tfen bu alan?? doldurun",
                    "bolum": "L??tfen bu alan?? doldurun",
                    "mezun_tarihi": "L??tfen bu alan?? doldurun",
                    "sertifikalar": "L??tfen bu alan?? doldurun",
                    "oncekiisler": "L??tfen bu alan?? doldurun",

                }
            })
    }
    static init() { this.initValidation() }
}.init()));

function showPass(e) {
    if ($(e).prev().css('-webkit-text-security') == "disc") {
        $(e).children().removeClass();
        $(e).children().addClass('fa fa-eye-slash');
        $(e).prev().css('-webkit-text-security', 'none');
    } else {
        $(e).children().removeClass();
        $(e).children().addClass('fa fa-eye');
        $(e).prev().css('-webkit-text-security', 'disc');
    }
}