<?php 
/*!
 * Admin Page: Settings: Business Info Settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit; 

global $wpdb, $zbs;  #} Req

$confirmAct = false;
$settings = $zbs->settings->getAll();

#} Act on any edits!
if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-bizinfo' );

    // moved from invoice builder settings -> biz info 16/7/18

    #} Invoice Chunks
    $updatedSettings['businessname'] = ''; if (isset($_POST['businessname'])) $updatedSettings['businessname'] = zeroBSCRM_textProcess($_POST['businessname']);
    $updatedSettings['businessyourname'] = ''; if (isset($_POST['businessyourname'])) $updatedSettings['businessyourname'] = zeroBSCRM_textProcess($_POST['businessyourname']);
    $updatedSettings['businessyouremail'] = ''; if (isset($_POST['businessyouremail'])) $updatedSettings['businessyouremail'] = zeroBSCRM_textProcess($_POST['businessyouremail']);
    $updatedSettings['businessyoururl'] = ''; if (isset($_POST['businessyoururl'])) $updatedSettings['businessyoururl'] = zeroBSCRM_textProcess($_POST['businessyoururl']);
    $updatedSettings['businesstel'] = ''; if (isset($_POST['businesstel'])) $updatedSettings['businesstel'] = zeroBSCRM_textProcess($_POST['businesstel']);

    #} Invoice Logo
    $updatedSettings['invoicelogourl'] = ''; if (isset($_POST['wpzbscrm_invoicelogourl']) && !empty($_POST['wpzbscrm_invoicelogourl'])) $updatedSettings['invoicelogourl'] = sanitize_text_field($_POST['wpzbscrm_invoicelogourl']);

    #} Social
    $updatedSettings['twitter'] = ''; if (isset($_POST['wpzbs_twitter'])) {
        $updatedSettings['twitter'] = sanitize_text_field( $_POST['wpzbs_twitter']);
        if (substr($updatedSettings['twitter'],0,1) == '@') $updatedSettings['twitter'] = substr($updatedSettings['twitter'],1);
    }
    $updatedSettings['facebook'] = ''; if (isset($_POST['wpzbs_facebook'])) $updatedSettings['facebook'] = sanitize_text_field( $_POST['wpzbs_facebook']);
    $updatedSettings['linkedin'] = ''; if (isset($_POST['wpzbs_linkedin'])) $updatedSettings['linkedin'] = sanitize_text_field( $_POST['wpzbs_linkedin']);

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();

}

#} catch resets.
if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Jetpack CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

    } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

    }

}


if (!$confirmAct){

    ?>

    <p id="sbDesc"><?php _e('Set up your general business information. This is used across Jetpack CRM, in features such as invoicing, mail campaigns, and email notifications.',"zero-bs-crm"); ?></p>

    <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
    <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>

    <div id="sbA">
    <pre><?php // print_r($settings); ?></pre>

    <form method="post">
        <input type="hidden" name="editwplf" id="editwplf" value="1" />
        <?php
        // add nonce
        wp_nonce_field( 'zbs-update-settings-bizinfo');
        ?>
        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Your Business Vitals',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody>

            <tr>
                <td class="wfieldname"><label for="businessname"><?php _e('Your Business Name',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="businessname" id="businessname" value="<?php if (isset($settings['businessname']) && !empty($settings['businessname'])) echo $settings['businessname']; ?>" placeholder="e.g. Widget Co Ltd." /></td>
            </tr>


            <tr>
                <td class="wfieldname"><label for="wpzbscrm_invoicelogourl"><?php _e('Your Business Logo',"zero-bs-crm"); ?>:</label><br /><?php _e('Enter an URL here, or upload a default logo to use on your invoices etc.',"zero-bs-crm"); ?></td>
                <td style="width:540px">
                    <input style="width:90%;padding:10px;" name="wpzbscrm_invoicelogourl" id="wpzbscrm_invoicelogourl" class="form-control link" type="text" value="<?php if (isset($settings['invoicelogourl']) && !empty($settings['invoicelogourl'])) echo $settings['invoicelogourl']; ?>" />
                    <button id="wpzbscrm_invoicelogourlAdd" class="button" type="button"><?php _e("Upload Image","zero-bs-crm");?></button>
                </td>
            </tr>

            </tbody>
        </table>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Your Full Business Information',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>


            <tr>
                <td class="wfieldname"><label for="businessyourname"><?php _e('Your Name',"zero-bs-crm"); ?>:</label><br /><?php _e('The business proprietor (Useful for freelancers), e.g. "John Doe (optionally - added to your invoice)" ',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="businessyourname" id="businessyourname" value="<?php if (isset($settings['businessyourname']) && !empty($settings['businessyourname'])) echo $settings['businessyourname']; ?>" placeholder="e.g. John Doe" /></td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="businessyouremail"><?php _e('Business Contact Email',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="businessyouremail" id="businessyouremail" value="<?php if (isset($settings['businessyouremail']) && !empty($settings['businessyouremail'])) echo $settings['businessyouremail']; ?>" placeholder="e.g. email@domain.com" /></td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="businessyoururl"><?php _e('Business Website URL',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="businessyoururl" id="businessyoururl" value="<?php if (isset($settings['businessyoururl']) && !empty($settings['businessyoururl'])) echo $settings['businessyoururl']; ?>" placeholder="e.g. https://example.com" /></td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="businesstel"><?php _e('Business Telephone Number',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="businesstel" id="businesstel" value="<?php if (isset($settings['businesstel']) && !empty($settings['businesstel'])) echo $settings['businesstel']; ?>" placeholder="" /></td>
            </tr>


            </tbody>

        </table>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" style="text-align:center">
                    <strong><?php _e('Your Business Social Info',"zero-bs-crm"); ?>:</strong><br />
                    <?php _e('Add your social accounts to (optionally) show them on your mail campaigns etc.',"zero-bs-crm"); ?>
                </th>
            </tr>

            </thead>


            <tr>
                <td class="wfieldname"><label for="wpzbs_twitter"><?php _e('Twitter Handle',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_twitter" id="wpzbs_twitter" value="<?php if (isset($settings['twitter']) && !empty($settings['twitter'])) echo $settings['twitter']; ?>" placeholder="e.g. twitter (no @)" /></td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="wpzbs_facebook"><?php _e('Facebook Page',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_facebook" id="wpzbs_facebook" value="<?php if (isset($settings['facebook']) && !empty($settings['facebook'])) echo $settings['facebook']; ?>" placeholder="e.g. facebookpagename" /></td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="wpzbs_linkedin"><?php _e('Linked In ID',"zero-bs-crm"); ?>:</label></td>
                <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_linkedin" id="wpzbs_linkedin" value="<?php if (isset($settings['linkedin']) && !empty($settings['linkedin'])) echo $settings['linkedin']; ?>" placeholder="e.g. linkedinco" /></td>
            </tr>
            <?php ##WLREMOVE ?>
            <tr>
                <th colspan="2" style="text-align:center;padding:1em">
                    <strong><?php _e('... and don\'t forget to follow Jetpack CRM (we\'re active on Twitter!)',"zero-bs-crm"); ?> <i class="twitter icon"></i>:</strong><br />
                    <a href="<?php echo $zbs->urls['twitter']; ?>" class="ui green button" target="_blank">@jetpackcrm</a><br /><br />
                    <strong><?php _e('Founders',"zero-bs-crm"); ?>:</strong><br />
                    <a href="<?php echo $zbs->urls['twitterwh']; ?>" target="_blank">@woodyhayday</a> and
                    <a href="<?php echo $zbs->urls['twitterms']; ?>" target="_blank">@mikemayhem3030</a>
                </th>
            </tr>
            <?php ##/WLREMOVE ?>

            </tbody>

        </table>

        <table class="table table-bordered table-striped wtab">
            <tbody>

            <tr>
                <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
            </tr>

            </tbody>
        </table>

    </form>


    <script type="text/javascript">

        jQuery(document).ready(function(){


            // Uploader
            // http://stackoverflow.com/questions/17668899/how-to-add-the-media-uploader-in-wordpress-plugin (3rd answer)
            jQuery('#wpzbscrm_invoicelogourlAdd').click(function(e) {
                e.preventDefault();
                var image = wp.media({
                    title: '<?php _e("Upload Image","zero-bs-crm");?>',
                    // mutiple: true if you want to upload multiple files at once
                    multiple: false
                }).open()
                    .on('select', function(e){

                        // This will return the selected image from the Media Uploader, the result is an object
                        var uploaded_image = image.state().get('selection').first();
                        // We convert uploaded_image to a JSON object to make accessing it easier
                        // Output to the console uploaded_image
                        //console.log(uploaded_image);
                        var image_url = uploaded_image.toJSON().url;
                        // Let's assign the url value to the input field
                        jQuery('#wpzbscrm_invoicelogourl').val(image_url);

                    });
            });




        });


    </script>

    </div><?php

} else {

    ?><div id="clpSubPage" class="whclpActionMsg six">
    <p><strong><?php echo $confirmActStr; ?></strong></p>
    <h3><?php echo $confirmActStrShort; ?></h3>
    <?php echo $confirmActStrLong; ?><br /><br />
    <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
    <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
    <br />
    </div><?php
}
