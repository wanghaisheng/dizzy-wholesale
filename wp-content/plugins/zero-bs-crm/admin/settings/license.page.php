<?php 
/*!
 * Admin Page: Settings: Licensing settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit; 

global $wpdb, $zbs;  #} Req

#} Act on any edits!
if (isset($_POST['editwplflicense']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-license' );

    $licenseKeyArr = zeroBSCRM_getSetting('license_key');

    if (isset($_POST['wpzbscrm_license_key'])){

        $licenseKeyArr['key'] = sanitize_text_field( $_POST['wpzbscrm_license_key'] );

    }


    #} This brutally overrides existing!
    $zbs->settings->update('license_key',$licenseKeyArr);
    $sbupdated = true;

    #} Also, should also recheck the validity of the key and show message if not valid
    zeroBSCRM_license_check();

}

// reget
$licenseKeyArr = zeroBSCRM_getSetting('license_key');

// Debug echo 'islocal:'.zeroBSCRM_isLocal(true).'<br>';
// Debug echo 'setting'.($licenseKeyArr['validity']).':<pre>'; print_r($licenseKeyArr); echo '</pre>';
if (!zeroBSCRM_isLocal(true)){

    // check
    if (!is_array($licenseKeyArr) || !isset($licenseKeyArr['key']) || empty($licenseKeyArr['key'])){

        echo "<div class='ui message'><i class='ui icon info'></i>";
        $msg = __('Enter your License Key for updates and support.','zero-bs-crm');
        ##WLREMOVE
        $msg = __('Enter your License Key for updates and support. Please visit','zero-bs-crm')." <a href='". $zbs->urls['account'] ."' target='_blank'>".__('Your Account','zero-bs-crm').'</a> '.__(" for your key and CRM license management.","zero-bs-crm");
        ##/WLREMOVE
        echo $msg;
        echo "</div>";

    } else {

        // simplify following:
        $licenseValid = false; if (isset($licenseKeyArr['validity'])) $licenseValid = ($licenseKeyArr['validity'] === 'true');

        if (!$licenseValid){
            echo "<div class='ui message red'><i class='ui icon info'></i>";
            $msg = __('Your License key is either invalid, expired, or not assigned to this site. Please contact support.','zero-bs-crm');

            ##WLREMOVE
            $msg = __('Your License key is either invalid, expired, or not assigned to this site. Please visit','zero-bs-crm')." <a href='". $zbs->urls['account'] ."' target='_blank'>".__('Your Account','zero-bs-crm').'</a> '.__('for your key and CRM license management.','zero-bs-crm');

            // add debug (from 2.98.1, to help us determine issues)
            $lastErrorMsg = ''; $err = $zbs->DAL->setting('licensingerror',false); if (is_array($err) && isset($err['err'])) $lastErrorMsg = $err['err'];
            if (!empty($lastErrorMsg)){
                $serverIP = zeroBSCRM_getServerIP();
                $msg .= '<br />'.__('If you believe you are seeing this in error, please ','zero-bs-crm')." <a href='". $zbs->urls['support'] ."' target='_blank'>".__('contact support','zero-bs-crm').'</a> '.__('and share the following debug output:','zero-bs-crm');
                $msg .= '<div style="margin:1em;padding:1em;">Server IP:<br />&nbsp;&nbsp;'.$serverIP;
                $msg .= '<br />Last Error:<br />&nbsp;&nbsp;'.$lastErrorMsg;
                $msg .= '</div>';
            }
            ##/WLREMOVE
            echo $msg;

            // got any errs?
            // https://wordpress.stackexchange.com/questions/167898/is-it-safe-to-use-sslverify-true-for-with-wp-remote-get-wp-remote-post
            $hasHitError = $zbs->DAL->setting('licensingerror',false);

            if (is_array($hasHitError)){

                $errorMsg = '<div style="font-size: 12px;padding: 1em;>['.date('F j, Y, g:i a',$hasHitError['time']).'] Reported Error: '.$hasHitError['err'].'</div>';

            }

            echo "</div>";
        } else {


            echo '<div class="ui grid">';
            echo '<div class="twelve wide column">';

            echo "<div class='ui message green'><i class='ui icon check'></i>";
            _e("Your License Key is valid for this site. Thank you.","zero-bs-crm");

            // got updates?
            if (isset($licenseKeyArr['extensions_updated']) && $licenseKeyArr['extensions_updated'] === false){

                echo ' '.__('You have extensions which need updating:','zero-bs-crm');
                echo ' <a href="'.admin_url('update-core.php').'">'.__('Update now','zero-bs-crm').'</a>';

            }

            echo "</div>";
            echo '</div>';

            // view license
            echo '<div class="four wide column" style="text-align:right;padding-top:1.5em;padding-right:2em"><span class="zbs-license-show-deets ui mini blue button" class="ui link"><i class="id card icon"></i> '.__('View License','zero-bs-crm').'</span></div>';
            echo '</div>'; // / grid


            // extra deets (hidden until "view License" clicked)
            echo '<div class="zbs-license-full-info ui segment grid" style="display:none">';
            echo '<div class="three wide column" style="text-align:center"><i class="id card icon" style="font-size: 3em;margin-top: 0.5em;"></i></div>';
            echo '<div class="thirteen wide column">';

            // key
            echo '<strong>'.__('License Key','zero-bs-crm').':</strong> ';
            if (isset($licenseKeyArr['key']))
                echo $licenseKeyArr['key'];
            else
                echo '-';
            echo '<br />';

            // sub deets
            echo '<strong>'.__('Your Subscription','zero-bs-crm').':</strong> ';
            if (isset($licenseKeyArr['access']))
                echo $zbs->getSubscriptionLabel($licenseKeyArr['access']);
            else
                echo '-';
            echo '<br />';

            ##WLREMOVE

            // next renewal
            echo '<strong>'.__('Next Renewal','zero-bs-crm').':</strong> ';
            if (isset($licenseKeyArr['expires']) && $licenseKeyArr['expires'] > 0)
                echo zeroBSCRM_locale_utsToDate($licenseKeyArr['expires']);
            else
                echo '-';
            echo '<br />';

            // links
            echo '<a href="'.$zbs->urls['licensinginfo'].'" target="_blank">'.__('Read about Yearly Subscriptions & Refunds','zero-bs-crm').'</a>';

            ##/WLREMOVE

            echo '</div>'; // / col


            ?><script type="text/javascript">

                jQuery(document).ready(function(){

                    jQuery('.zbs-license-show-deets').click(function(){

                        jQuery('.zbs-license-full-info').show();
                        jQuery('.zbs-license-show-deets').hide();

                    });

                });


            </script><?php

            echo '</div>'; // / grid

            echo '<div style="clear:both" class="ui divider"></div>';
        }

    }

} // if not local

?>

<?php if (isset($sbupdated)) if ($sbupdated) {

    //echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>';
    echo zeroBSCRM_UI2_messageHTML('info','',__('Settings Updated',"zero-bs-crm"));

} ?>
<?php

##WLREMOVE
// claimed api key?
global $zbsLicenseClaimed;
if (isset($zbsLicenseClaimed)){

    echo zeroBSCRM_UI2_messageHTML('info',__('API Key Notice','zero-bs-crm'),__('Thank you for entering your API key. This key has been successfully associated with this install, if you would like to change which domain uses this API key, please visit ','zero-bs-crm').'<a href="'.$zbs->urls['account'].'" target="_blank">'.$zbs->urls['account'].'</a>');
}
##/WLREMOVE


// if on Local server, don't allow entry of license keys, because we will end up with a license key db full
// + it's hard to license properly on local servers as peeps could have many the same
// ... so for v1.0 at least, 'devmode' in effect
if (zeroBSCRM_isLocal(true)){

    $guide = '';
    ##WLREMOVE
    $guide = '<br /><br /><a href="'.$zbs->urls['kbdevmode'].'" class="ui button primary" target="_blank">'.__('Read More','zero-bs-crm').'</a>';
    ##/WLREMOVE

    echo zeroBSCRM_UI2_messageHTML('info',__('Developer Mode','zero-bs-crm'),__('This install appears to be running on a local machine. For this reason your CRM is in Developer Mode. You cannot add a license key to developer mode, nor retrieve automatic-updates.','zero-bs-crm').$guide);

} else {

// normal page

    ?>
    <div id="sbA">
    <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=license" id="zbslicenseform">
        <input type="hidden" name="editwplflicense" id="editwplflicense" value="1" />
        <?php
        // add nonce
        wp_nonce_field( 'zbs-update-settings-license');
        ?>


        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('License',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-addresses-license-key">

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_license_key"><?php _e("License Key","zero-bs-crm"); ?>:</label><br /><?php _e('Enter your License Key.',"zero-bs-crm"); ?></td>
                <td style="width:540px">
                    <input class='form-control' style="padding:10px;" name="wpzbscrm_license_key" id="wpzbscrm_license_key" class="form-control" type="text" value="<?php if (isset($licenseKeyArr['key']) && !empty($licenseKeyArr['key'])) echo $licenseKeyArr['key']; ?>" />
                </td>
            </tr>

            </tbody>

        </table>

        <table class="table table-bordered table-striped wtab">
            <tbody>

            <tr>
                <td colspan="2" class="wmid">
                    <button type="submit" class="ui button primary" id=""><?php _e('Save Settings',"zero-bs-crm"); ?></button>
                </td>
            </tr>

            </tbody>
        </table>

    </form>

    <script type="text/javascript">

    </script>

    </div><?php

} // normal page
