<table id="landswitcher-list-table">
    <thead>
    <tr>
        <th>Country</th>
        <th>Redirect Url</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>
    </thead>
    <tbody>
    {foreach $redirects as $redirect}
        <tr>
            <td>
                <input type="hidden" readonly name="country_iso" value="{$redirect->country_iso}">
                <span>{$redirect->name}</span>
            </td>
            <td><input type="text" name="url" value="{$redirect->url}"></td>
            <td>
                <button type="button" class="js-update-redirect">Update</button>
            </td>
            <td>
                <button type="button" class="js-delete-redirect">Delete</button>
            </td>
        </tr>
        {foreachelse}
        <tr>
            <td colspan="4">
                No redirects stored
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>

{inline_script}
    <script>
        {literal}
        $(function () {
            const adminPath = {/literal}'{$PFAD_ADMIN}'{literal};
            $(document).on('click', '.js-delete-redirect', function () {
                const $this = $(this);
                const $row = $this.closest('tr');
                ioManagedCall(adminPath, 'Landswitcher.delete', [$row.find('[name=country_iso]').val()], (res, err) => {
                    if (err) {
                        showNotify('danger', 'URL deletion', err.message);
                        return;
                    }
                    showNotify('success', 'URL deletion', res.message);
                    $row.remove()

                });
            })

            $(document).on('click', '.js-update-redirect', function () {
                const $this = $(this);
                const $row = $this.closest('tr');
                ioManagedCall(adminPath, 'Landswitcher.update', [$row.find('[name=country_iso]').val(), $row.find('[name=url]').val()], (res, err) => {
                    if (err) {
                        showNotify('danger', 'URL update', err.message);
                        return;
                    }
                    showNotify('success', 'URL update', res.message);

                });
            })
        });


        {/literal}
    </script>
{/inline_script}
