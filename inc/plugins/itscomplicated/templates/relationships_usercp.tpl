<html>
<head>
	<title>{$mybb->settings['bbname']} - {$lang->itscomplicated_relationships}</title>
	{$headerinclude}
    <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
</head>
<body>
	{$header}

    <table width="100%" border="0" align="center">
        <tr>
            {$usercpnav}
            <td valign="top">
                {$errorMessage}

                <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                    <tr>
                        <td class="thead" colspan="3"><strong>{$currentRelationships}</strong></td>
                    </tr>
                    <tr>
                        <td class="tcat" style="text-align: center;"><strong>{$lang->itscomplicated_relationships_user}</strong></td>
                        <td class="tcat" style="text-align: center;"><strong>{$lang->itscomplicated_relationships_type}</strong></td>
                        <td class="tcat" style="text-align: center;"><strong>{$lang->options}</strong></td>
                    </tr>
                    {$relationshipsList}
                </table>
                <br />

                {$createRelationship}
                {$relationshipRequests}

                <p style="text-align: center;">{$message}</p>
            </td>
        </tr>
	</table>

	{$footer}
</body>
</html>