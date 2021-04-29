<?php 
/*!
 * Admin Page: Settings: Forms settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit; 

global $wpdb, $zbs;  #} Req

$confirmAct = false;
$settings = $zbs->settings->getAll();

#} Act on any edits!
if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-forms' );

    #} 1.1.17 - gcaptcha (should be moved into a "Forms" tab later)
    $updatedSettings['showformspoweredby'] = 0; if (isset($_POST['wpzbscrm_showformspoweredby']) && !empty($_POST['wpzbscrm_showformspoweredby'])) $updatedSettings['showformspoweredby'] = 1;
    $updatedSettings['usegcaptcha'] = 0; if (isset($_POST['wpzbscrm_usegcaptcha']) && !empty($_POST['wpzbscrm_usegcaptcha'])) $updatedSettings['usegcaptcha'] = 1;
    $updatedSettings['gcaptchasitekey'] = 0; if (isset($_POST['wpzbscrm_gcaptchasitekey']) && !empty($_POST['wpzbscrm_gcaptchasitekey'])) $updatedSettings['gcaptchasitekey'] = sanitize_text_field($_POST['wpzbscrm_gcaptchasitekey']);
    $updatedSettings['gcaptchasitesecret'] = 0; if (isset($_POST['wpzbscrm_gcaptchasitesecret']) && !empty($_POST['wpzbscrm_gcaptchasitesecret'])) $updatedSettings['gcaptchasitesecret'] = sanitize_text_field($_POST['wpzbscrm_gcaptchasitesecret']);

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


if (!$confirmAct && !isset($rebuildCustomerNames)){

    ?>

    <p id="sbDesc"><?php _e('From this page you can modify the settings for Jetpack CRM Front-end Forms. Want to use other forms like Contact Form 7? Check out our ',"zero-bs-crm"); ?> <a href="<?php echo $zbs->urls['products']; ?>" target="_blank"><?php _e("Form Extensions","zero-bs-crm");?></a></p>

    <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
    <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>

    <div id="sbA">
    <pre><?php // print_r($settings); ?></pre>

    <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=forms">
        <input type="hidden" name="editwplf" id="editwplf" value="1" />
        <?php
        // add nonce
        wp_nonce_field( 'zbs-update-settings-forms');
        ?>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Forms Settings',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody>

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_showformspoweredby"><?php _e('Show powered by Jetpack CRM',"zero-bs-crm"); ?>:</label><br /><?php _e('Help show us some love by displaying the powered by on your forms',"zero-bs-crm"); ?>.</td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showformspoweredby" id="wpzbscrm_showformspoweredby" value="1"<?php if (isset($settings['showformspoweredby']) && $settings['showformspoweredby'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_usegcaptcha"><?php _e('Enable reCaptcha',"zero-bs-crm"); ?>:</label><br /><?php _e('This setting enabled reCaptcha for your front end forms. If you\'d like to use this to avoid spam, please sign up for a site key and secret',"zero-bs-crm"); ?> <a href="https://www.google.com/recaptcha/admin#list" target="_blank">here</a>.</td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_usegcaptcha" id="wpzbscrm_usegcaptcha" value="1"<?php if (isset($settings['usegcaptcha']) && $settings['usegcaptcha'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_gcaptchasitekey"><?php _e('reCaptcha Site Key',"zero-bs-crm"); ?>:</label><br /></td>
                <td><input type="text" class="winput form-control" name="wpzbscrm_gcaptchasitekey" id="wpzbscrm_gcaptchasitekey" value="<?php if (isset($settings['gcaptchasitekey']) && !empty($settings['gcaptchasitekey'])) echo $settings['gcaptchasitekey']; ?>" placeholder="e.g. 6LekCyoTAPPPALWpHONFsRO5RQPOqoHfehdb4iqG" /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_gcaptchasitesecret"><?php _e('reCaptcha Site Secret',"zero-bs-crm"); ?>:</label><br /></td>
                <td><input type="text" class="winput form-control" name="wpzbscrm_gcaptchasitesecret" id="wpzbscrm_gcaptchasitesecret" value="<?php if (isset($settings['gcaptchasitesecret']) && !empty($settings['gcaptchasitesecret'])) echo $settings['gcaptchasitesecret']; ?>" placeholder="e.g. 6LekCyoTAAPPAJbQ1rq81117nMoo9y45fB3OLJVx" /></td>
            </tr>

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
            jQuery('#wpzbscrm_loginlogourlAdd').click(function(e) {
                e.preventDefault();
                var image = wp.media({
                    title: 'Upload Image',
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
                        jQuery('#wpzbscrm_loginlogourl').val(image_url);

                    });
            });




        });


    </script>

    </div><?php

}else {

    ?><div id="clpSubPage" class="whclpActionMsg six">
    <p><strong><?php echo $confirmActStr; ?></strong></p>
    <h3><?php echo $confirmActStrShort; ?></h3>
    <?php echo $confirmActStrLong; ?><br /><br />
    <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
    <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
    <br />
    </div><?php
}
