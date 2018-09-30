<table border="0" style="width: 100%">
    <tr>
        <td style="width: 50%; vertical-align: top">
            <table class="tborder" border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}">
                <tr>
                    <td class="thead" colspan="3"><strong>{$lang->itscomplicated_relationships_requests_received}</strong></td>
                </tr>
                <tr>
                    <td class="tcat" width="33%"><strong>{$lang->from}</strong></td>
                    <td class="tcat" width="33%"><strong>{$lang->itscomplicated_relationships_type}</strong></td>
                    <td class="tcat" width="33%"><strong>{$lang->options}</strong></td>
                </tr>
                {$receivedRequests}
            </table>
        </td>
        <td style="width: 50%; vertical-align: top">
            <table class="tborder" border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}">
                <tr>
                    <td class="thead" colspan="3"><strong>{$lang->itscomplicated_relationships_requests_sent}</strong></td>
                </tr>
                <tr>
                    <td class="tcat" width="33%"><strong>{$lang->to}</strong></td>
                    <td class="tcat" width="33%"><strong>{$lang->itscomplicated_relationships_type}</strong></td>
                    <td class="tcat" width="33%"><strong>{$lang->options}</strong></td>
                </tr>
                {$sentRequests}
            </table>
        </td>
    </tr>
</table>