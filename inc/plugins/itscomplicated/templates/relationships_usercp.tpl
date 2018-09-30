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

                <form action="usercp.php?action=relationships" method="post">
                    <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                        <tr>
                            <td class="thead" colspan="2"><strong>{$lang->itscomplicated_relationships_add}</strong></td>
                        </tr>
                        <tr>
                            <td class="trow2" width="40%"><strong>{$lang->itscomplicated_relationships_user}</strong></td>
                            <td class="trow2" width="60%"><input type="text" name="receiving_user_username" id="relationship_add_username" class="textbox" /></td>
                        </tr>
                        <tr>
                            <td class="trow2" width="40%"><strong>{$lang->itscomplicated_relationships_type}</strong></td>
                            <td class="trow2" width="60%">
                                <select name="relationship_type_id">
                                    {$relationshipTypeSelectOptions}
                                </select>
                            </td>
                        </tr>
                    </table>
                    <br />
                    <div align="center">
                        <input type="hidden" name="create_request" value="1" />
                        <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
                        <input type="submit" class="button" name="submit" value="{$lang->itscomplicated_relationships_request}" />
                    </div>
                </form>
                <br />

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

            </td>
        </tr>
	</table>

    <script type="text/javascript">
        <!--
        if (use_xmlhttprequest == "1") {
            MyBB.select2();
            $("#relationship_add_username").select2({
                placeholder: "{$lang->search_user}",
                minimumInputLength: 2,
                maximumSelectionSize: 5,
                multiple: false,
                ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                    url: "xmlhttp.php?action=get_users",
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            query: term, // search term
                        };
                    },
                    results: function (data, page) { // parse the results into the format expected by Select2.
                        // since we are using custom formatting functions we do not need to alter remote JSON data
                        return {results: data};
                    }
                },
                initSelection: function (element, callback) {
                    var query = $(element).val();
                    if (query !== "") {
                        var newqueries = [];
                        exp_queries = query.split(",");
                        $.each(exp_queries, function (index, value) {
                            if (value.replace(/\s/g, '') != "") {
                                var newquery = {
                                    id: value.replace(/,\s?/g, ", "),
                                    text: value.replace(/,\s?/g, ", ")
                                };
                                newqueries.push(newquery);
                            }
                        });
                        callback(newqueries);
                    }
                },
            });
        }

        itscomplicated_relationships_end_confirm = "{$lang->itscomplicated_relationships_end_confirm}";
        itscomplicated_relationships_request_accept_confirm = "{$lang->itscomplicated_relationships_request_accept_confirm}";
        // -->
    </script>

	{$footer}
</body>
</html>