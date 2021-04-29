<?php 
/*!
 * Admin Page: Settings: Quotes settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;

global $wpdb, $zbs;  #} Req

$confirmAct = false;
$settings = $zbs->settings->getAll();

#} Act on any edits!
if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-quotebuilder' );

    #} 1.1.17 - gcaptcha (should be moved into a "Forms" tab later)


    // taken out 2.96.7 //$updatedSettings['showpoweredbyquotes'] = 0; if (isset($_POST['wpzbscrm_showpoweredbyquotes']) && !empty($_POST['wpzbscrm_showpoweredbyquotes'])) $updatedSettings['showpoweredbyquotes'] = 1;
    $updatedSettings['usequotebuilder'] = 0; if (isset($_POST['wpzbscrm_usequotebuilder']) && !empty($_POST['wpzbscrm_usequotebuilder'])) $updatedSettings['usequotebuilder'] = 1;

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();

}

#} catch resets.
if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings'] ==1){

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
    <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
    <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>

    <div id="sbA">

    <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=quotebuilder">
        <input type="hidden" name="editwplf" id="editwplf" value="1" />
        <?php
        // add nonce
        wp_nonce_field( 'zbs-update-settings-quotebuilder');
        ?>
        <style>td{width:50%;}</style>
        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Quotes Settings',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody>

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_usequotebuilder"><?php _e('Enable Quote Builder',"zero-bs-crm"); ?>:</label><br /><?php _e('Disabling this will remove the quote-writing element of Quotes. This is useful if you\'re only logging quotes, not writing them.',"zero-bs-crm"); ?>.</td>
                <td style=""><input type="checkbox" class="winput form-control" name="wpzbscrm_usequotebuilder" id="wpzbscrm_usequotebuilder" value="1"<?php if (isset($settings['usequotebuilder']) && $settings['usequotebuilder'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="text-align:center"><?php _e('Looking for easy-access quote links? You can now turn easy-access links on via the client portal settings page','zero-bs-crm'); ?></p>
                    <p style="text-align:center">
                        <a href="<?php echo zbsLink($zbs->slugs['settings']); echo '&tab=clients'; ?>" class="ui mini button blue"><?php _e('View Client Portal Settings','zero-bs-crm'); ?></a>
                        <?php ##WLREMOVE ?>
                        <a href="<?php echo $zbs->urls['easyaccessguide']; ?>" target="_blank" class="ui mini button green"><?php _e('View Easy-Access Links Guide','zero-bs-crm'); ?></a>
                        <?php ##/WLREMOVE ?>
                    </p>
                </td>
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


    <div style="text-align: center;margin-top:2.5em">
        <span class="ui label"><?php _e('Other Tools:','zero-bs-crm'); ?></span> <a href="<?php echo esc_url(zbsLink($zbs->slugs['settings'].'&tab=customfields#zbscrm-quotes-custom-fields')); ?>"><?php _e('Manage Custom Fields','zero-bs-crm'); ?></a>
    </div>

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
