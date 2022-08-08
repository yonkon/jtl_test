<form id="landswitcher_add_form">
    <div><label>
            <span>Country</span>
            <select type="text" name="country_iso" required>
                <option></option>
                {foreach $countries as $country}
                    <option value="{$country['value']}">{$country['label']}</option>
                {/foreach}
            </select>
        </label></div>

    <div><label>
            <span>Redirect URL</span>
            <input type="text" name="url" placeholder="https://example.com" required/>
        </label></div>

    <button type="submit">Save</button>
</form>
{inline_script}
    <script>
        {literal}
        $(function () {
            const adminPath = {/literal}'{$PFAD_ADMIN}'{literal};

            $('#landswitcher_add_form').on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $this = $(this);
                ioManagedCall(adminPath, 'Landswitcher.create', [$this.find('[name=country_iso]').val(), $this.find('[name=url]').val()], (res, err) => {
                    if (err) {
                        showNotify('danger', 'Redirect creation', err.message);
                        return;
                    }
                    showNotify('success', 'Redirect creation', res.message);

                    $this[0].reset();
                    updateList();
                });
            })

            function updateList() {
                const $table = $('#landswitcher-list-table');
                if (!$table.length)
                    return;
                ioManagedCall(adminPath, 'Landswitcher.getList', [], (res, err) => {
                    if (err) {
                        showNotify('danger', 'Redirect creation', err.message);
                        return;
                    }
                    const $tbody = $table.find('tbody');
                    $tbody.empty();
                    res.items.forEach((item) => {
                        $(`<tr>
            <td>
<input type="hidden" readonly name="country_iso" value="${item.country_iso}">
<span>${item.name}</span>
</td>
            <td><input type="text" name="url" value="${item.url}"></td>
            <td>
                <button type="button" class="js-update-redirect">Update</button>
            </td>
            <td>
                <button type="button" class="js-delete-redirect">Delete</button>
            </td>
        </tr>
        `).appendTo($tbody);
                    });


                });
            }


        });
        {/literal}
    </script>
{/inline_script}
