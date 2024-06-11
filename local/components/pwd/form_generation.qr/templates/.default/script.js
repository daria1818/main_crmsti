'use strict';
(function () {
    $(window).on('load', function () {

        let modal = jQuery('div#okay');
        /**
         * Open modal
         * @private
         */
        let _openModal = function () {
            modal.open();
        };

        /**
         * Show modal
         * @private
         */
        let _showModal = function (text) {
            _openModal();
        };

        /**
         * APPLICATION
         */
        var FormQrGenerationComponent = BX.namespace('__pwd.FormQrGenerationComponent');

        FormQrGenerationComponent.runComponentAction = function (action, data) {
            if (typeof data !== 'object') {
                data = {};
            }
            return BX.ajax.runComponentAction('pwd:form_generation.qr', action, {
                mode: 'class',
                data: data,
            });
        };

        const formGeneration = 'form[name=form-qr-generation]';
        jQuery(document).on('submit', formGeneration, function (e) {
            e.preventDefault();
            let formData = new FormData($(formGeneration)[0]);
            FormQrGenerationComponent.runComponentAction('addPage', formData)
                .then((response) => {
                    if (response.status === 'success' && response.data) {
                        jQuery(formGeneration).trigger("reset");
                        jQuery('.output-src-generation').html('Форма сгенерирована по адресу <a href="' + response.data.src + '">' + response.data.src + '</a>' +
                            '<br>' +
                            '<div style="display:flex; justify-content:center; align-items:center;"><img src="' + response.data.qrSrc + '"></div>');
                    }
                });
        });
console.log('sdfsdf');
        const formContact = 'form[name=form-contact]';
        const error = '.error-form';
        jQuery(document).on('submit', formContact, function (e) {
            e.preventDefault();
            let formData = new FormData($(formContact)[0]);
            FormQrGenerationComponent.runComponentAction('makeContact', formData)
                .then((response) => {
console.log(response);
                    if (response.status === 'success' && response.data) {
                        if (response.data.break == true) {
                            jQuery(error).html('Заполните все поля, отмеченные символом *');
                        } else {
                            $.fancybox.open('<div class="message" style="padding: 20px 30px"><h2 style="text-align: center">Вы успешно зарегистрированы!</p></div>');
                            $(this).trigger("reset");
                        }
                    }
                });
        });


        if (arNextOptions['THEME']['PHONE_MASK']) {
            $('input.phone').inputmask('mask', {'mask': arNextOptions['THEME']['PHONE_MASK']});
        }

        $('#list').select2({
            placeholder: "Выберите специализацию"
        });

        function showCurrentDate() {
            var d = new Date(),
                new_value = d.toISOString().slice(0, 10);
            if (document.getElementById("date-input")) {
                document.getElementById("date-input").value = new_value;
            }
        }

        showCurrentDate();
    });
})();