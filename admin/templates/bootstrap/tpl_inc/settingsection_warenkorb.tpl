<script type="text/javascript">
    {literal}
    $(document).ready(function() {
        let $praefix       = $('#bestellabschluss_bestellnummer_praefix'),
            $anfangsnummer = $('#bestellabschluss_bestellnummer_anfangsnummer'),
            $suffix        = $('#bestellabschluss_bestellnummer_suffix'),
            $all           = $('#bestellabschluss_bestellnummer_praefix, #bestellabschluss_bestellnummer_anfangsnummer, #bestellabschluss_bestellnummer_suffix'),
            force          = false;
        if (!$praefix.hasClass('jsLoaded')) {
        $praefix.on('focus', function(e) {
            this.maxLength = 20 - $anfangsnummer.val().length - $suffix.val().length;
        });
        $anfangsnummer.on('focus', function(e) {
            this.maxLength = 20 - $praefix.val().length - $suffix.val().length;
        });
        $suffix.on('focus', function(e) {
            this.maxLength = 20 - $anfangsnummer.val().length - $praefix.val().length;
        });
        $all.on('blur', function(e) {
            $(this).parent().tooltip('hide');
            let value = $(this).val();
            if (value.length > this.maxLength) {
                $(this).val(value.substr(0, this.maxLength));
            }
        })
        .on('focus keyup', function(e) {
            updateBestellnummer(this);
        });

        $all.closest('form').on('submit', function(e) {
            let praefix       = $praefix.val(),
                anfangsnummer = isNaN(parseInt($anfangsnummer.val())) ? 0 : parseInt($anfangsnummer.val()),
                suffix        = $suffix.val(),
                maxValLength  = 20 - praefix.length - suffix.length,
                maxValStr     = '9'.repeat(maxValLength),
                maxVal        = parseInt(maxValStr);

            if (anfangsnummer > maxVal) {
                $all.parent().addClass('form-error');
                showNotify('warning', {/literal}'{__('modalTitleOrderNumberInvalid')}: '{literal}, {/literal}'{__('modalTextOrderNumberInvalid')}: '{literal});

                return false;
            }
            if (!force && (maxVal - anfangsnummer) < 10000) {
                $anfangsnummer.parent().addClass('form-error');
                let $notify = createNotify({
                    title: {/literal}'{__('modalTitleOrderNumberTooLong')}: '{literal},
                    message: {/literal}'{__('modalTextOrderNumberTooLongOne')}: '{literal} + (maxVal - anfangsnummer)
                    + {/literal}'{__('modalTextOrderNumberTooLongTwo')}: '{literal}  + praefix + maxValStr + suffix
                    + {/literal}'{__('modalTextOrderNumberTooLongThree')}: '{literal} + ' <button id="forceSave" class="btn btn-block btn-warning mt-3"><i class="fa fa-save"></i>'
                    + {/literal}'{__('buttonSaveAnyway')}: '{literal} + '</button>'
                }, {
                    type: 'info',
                    delay: 12000,
                    allow_dismiss: true
                });
                $('#forceSave').on('click', function(e) {
                    $notify.close();
                    force = true;
                    $all.closest('form')[0].submit();
                });

                return false;
            }

            return true;
        });

        function updateBestellnummer(elem) {
            let praefix       = $praefix.val(),
                anfangsnummer = isNaN(parseInt($anfangsnummer.val())) ? 0 : parseInt($anfangsnummer.val()),
                suffix        = $suffix.val(),
                maxValLength  = 20 - praefix.length - suffix.length,
                maxValStr     = '9'.repeat(maxValLength),
                maxVal        = parseInt(maxValStr),
                result        = {/literal}'{__('preview')}: '{literal} + praefix + maxValStr + suffix;

            $(elem).parent().attr('title', result)
                .tooltip('dispose')
                .tooltip({trigger:'manual'})
                .tooltip('show');
            if ((maxVal - anfangsnummer) < 10000) {
                $(elem).parent().addClass('form-error');
            } else {
                $all.parent().removeClass('form-error');
            }
        }
        $praefix.addClass('jsLoaded');
        }
    });
    {/literal}
</script>