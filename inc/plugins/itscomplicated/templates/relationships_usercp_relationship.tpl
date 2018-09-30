<tr>
    <td class="trow1" style="text-align: center;">{$username}</td>
    <td class="trow1" style="text-align: center;">{$relationshipTypeTitle}</td>
    <td class="trow1" style="text-align: center;">
        <a href="usercp.php?action=relationships&end_relationship={$userRelationship['id']}&my_post_key={$mybb->post_code}" onclick="return confirm(itscomplicated_relationships_end_confirm)">{$lang->itscomplicated_relationships_end}</a>
    </td>
</tr>