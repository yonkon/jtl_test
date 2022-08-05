<script>
    var vLicenses = {if isset($szLicenses)}{$szLicenses}{else}[]{/if};
    var pluginName;
    $(document).ready(function() {
        var token = $('input[name="jtl_token"]').val();
        $('.tab-content').on('click', '#verfuegbar .plugin-license-check', function (e) {
            var oTemp = $(e.currentTarget);
            pluginName = oTemp.val();
            var licensePath = vLicenses[pluginName];
            if (this.checked && typeof licensePath === 'string') { // it's checked yet, right after the click was fired
                var modal = $('#licenseModal');
                $('input[id="plugin-check-' + pluginName + '"]').attr('disabled', 'disabled'); // block the checkbox!
                modal.modal({ backdrop : 'static' }).one('hide.bs.modal', function (e) {
                    $('input[id=plugin-check-' + pluginName + ']').removeAttr('disabled');
                });
                $('#licenseModal button[name=cancel], #licenseModal .close').one('click', function(event) {
                    $('input[id=plugin-check-' + pluginName + ']').prop('checked', false);
                });
                $('#licenseModal button[name=ok]').one('click', function(event) {
                    $('input[id=plugin-check-' + pluginName + ']').prop('checked', true);
                });
                startSpinner();
                modal.find('.modal-body').load(
                    'getMarkdownAsHTML.php',
                    { 'jtl_token' : '{$smarty.Session.jtl_token}', 'path': vLicenses[pluginName] },
                    function () {
                        stopSpinner();
                    }
                );
                modal.modal('show');
            }
        });
    });
</script>
