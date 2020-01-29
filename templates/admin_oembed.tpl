<h3>Embeded Content</h3>

<?php
$umWPEOptions = array_replace_recursive(
    array(
        'oembed' => array(
            'fluid' => 0
        )
    ),
    get_option( 'um_wpe_options' ) ?: array()
);
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row">Fluid Embeds</th>
        <td>
            <input type="radio" id="um_wpe_options--oembed--fluid-1" name="um_wpe_options[oembed][fluid]" value="1" <?=( $umWPEOptions['oembed']['fluid'] == 1 ? ' checked="checked"' : null);?> /> <label for="um_wpe_options--oembed--fluid-1">Yes</label>
            <input type="radio" id="um_wpe_options--oembed--fluid-0" name="um_wpe_options[oembed][fluid]" value="0" <?=( $umWPEOptions['oembed']['fluid'] == 0 ? ' checked="checked"' : null);?> /> <label for="um_wpe_options--oembed--fluid-1">No</label>
            <br/>
            <em>Make videos scale to fit the content area.</em>
        </td>
    </tr>
</table>
