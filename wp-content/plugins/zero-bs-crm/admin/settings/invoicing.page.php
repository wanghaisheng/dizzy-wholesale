<?php 
/*!
 * Admin Page: Settings: Invoicing settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit; 

global $wpdb, $zbs;  #} Req

$confirmAct = false;
$settings = $zbs->settings->getAll();

#} #WH OR - need these lists?

#} load currency list
global $whwpCurrencyList;
if(!isset($whwpCurrencyList)) require_once(ZEROBSCRM_PATH . 'includes/wh.currency.lib.php');
/*
#} load country list
global $whwpCountryList;
if(!isset($whwpCountryList)) require_once(ZEROBSCRM_PATH . 'includes/wh.countrycode.lib.php');

*/

#} Act on any edits!
if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-invbuilder' );

    /* Moved to bizinfo settings page 16/7/18
    #} Invoice Logo
        $updatedSettings['invoicelogourl'] = ''; if (isset($_POST['wpzbscrm_invoicelogourl']) && !empty($_POST['wpzbscrm_invoicelogourl'])) $updatedSettings['invoicelogourl'] = sanitize_text_field($_POST['wpzbscrm_invoicelogourl']);

    #} Invoice Chunks
    $updatedSettings['businessname'] = ''; if (isset($_POST['businessname'])) $updatedSettings['businessname'] = zeroBSCRM_textProcess($_POST['businessname']);
    $updatedSettings['businessyourname'] = ''; if (isset($_POST['businessyourname'])) $updatedSettings['businessyourname'] = zeroBSCRM_textProcess($_POST['businessyourname']);
    $updatedSettings['businessyouremail'] = ''; if (isset($_POST['businessyouremail'])) $updatedSettings['businessyouremail'] = zeroBSCRM_textProcess($_POST['businessyouremail']);
    $updatedSettings['businessyoururl'] = ''; if (isset($_POST['businessyoururl'])) $updatedSettings['businessyoururl'] = zeroBSCRM_textProcess($_POST['businessyoururl']);
    */



    $updatedSettings['reftype'] = '';
    $updatedSettings['defaultref'] = '';
    $updatedSettings['refprefix'] = '';
    $updatedSettings['refnextnum'] = '';
    $updatedSettings['refsuffix'] = '';
    $updatedSettings['reflabel'] = '';

    if ( isset( $_POST['reftype'] ) ) {
        $updatedSettings['reftype'] = zeroBSCRM_textProcess( $_POST['reftype'] );
    }

    if ( isset( $_POST['defaultref'] ) ) {
        $updatedSettings['defaultref'] = zeroBSCRM_textProcess( $_POST['defaultref'] );
    }

    if ( isset( $_POST['refprefix'] ) ) {
        $updatedSettings['refprefix'] = zeroBSCRM_textProcess( $_POST['refprefix'] );
    }
    if ( isset( $_POST['refsuffix'] ) ) {
        $updatedSettings['refsuffix'] = zeroBSCRM_textProcess( $_POST['refsuffix'] );
    }
    if ( isset( $_POST['refnextnum'] ) ) {
        $updatedSettings['refnextnum'] = zeroBSCRM_textProcess( $_POST['refnextnum'] );
    }

    if ( isset( $_POST['reflabel'] ) ) {
        $updatedSettings['reflabel'] = zeroBSCRM_textProcess( $_POST['reflabel'] );
    }

    $updatedSettings['businessextra'] = ''; if (isset($_POST['businessextra'])) $updatedSettings['businessextra'] = zeroBSCRM_textProcess($_POST['businessextra']);
    $updatedSettings['paymentinfo'] = ''; if (isset($_POST['paymentinfo'])) $updatedSettings['paymentinfo'] = zeroBSCRM_textProcess($_POST['paymentinfo']);
    $updatedSettings['paythanks'] = ''; if (isset($_POST['paythanks'])) $updatedSettings['paythanks'] = zeroBSCRM_textProcess($_POST['paythanks']);

    #} Invoice sending settings
    $updatedSettings['invfromemail'] = ''; if (isset($_POST['invfromemail'])) $updatedSettings['invfromemail'] = zeroBSCRM_textProcess($_POST['invfromemail']);
    $updatedSettings['invfromname'] = ''; if (isset($_POST['invfromname'])) $updatedSettings['invfromname'] = zeroBSCRM_textProcess($_POST['invfromname']);

    #} Hide Invoice ID
    $updatedSettings['invid'] = 0; if (isset($_POST['wpzbscrm_invid']) && !empty($_POST['wpzbscrm_invid'])) $updatedSettings['invid'] = 1;

    #} Allow Invoice Hash (view and pay without being logged into the portal)
    // moved to client portal settings 3.0 - $updatedSettings['invhash'] = 0; if (isset($_POST['wpzbscrm_invhash']) && !empty($_POST['wpzbscrm_invhash'])) $updatedSettings['invhash'] = 1;

    #} Tax etc
    $updatedSettings['invtax'] = 0; if (isset($_POST['wpzbscrm_invtax']) && !empty($_POST['wpzbscrm_invtax'])) $updatedSettings['invtax'] = 1;
    $updatedSettings['invdis'] = 0; if (isset($_POST['wpzbscrm_invdis']) && !empty($_POST['wpzbscrm_invdis'])) $updatedSettings['invdis'] = 1;
    $updatedSettings['invpandp'] = 0; if (isset($_POST['wpzbscrm_invpandp']) && !empty($_POST['wpzbscrm_invpandp'])) $updatedSettings['invpandp'] = 1;

    #} Statements
    $updatedSettings['statementextra'] = ''; if (isset($_POST['zbsi_statementextra'])) $updatedSettings['statementextra'] = zeroBSCRM_textProcess($_POST['zbsi_statementextra']);

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();

}

#} catch resets.
if (isset($_GET['resetsettings'])) if ($_GET['resetsettings']==1){

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

    <p id="sbDesc"><?php _e('Setup and control how the invoicing functionality works in your Jetpack CRM. If you have any feedback on our invoicing functionality please do let us know.',"zero-bs-crm"); ?></p>

    <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
    <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>

    <div id="sbA">
    <pre><?php // print_r($settings); ?></pre>

    <form method="post">
        <input type="hidden" name="editwplf" id="editwplf" value="1" />
        <?php
        // add nonce
        wp_nonce_field( 'zbs-update-settings-invbuilder');
        ?>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('General',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>
            <tbody>

            <tr>
                <td class="wfieldname"><label for="defaultref">
                        <?php _e('Reference type',"zero-bs-crm"); ?>:</label><br /><?php _e('Select the default reference system for your invoices',"zero-bs-crm"); ?>
                    <div style="margin-top:10px">
                        <input type="radio" style="margin:0 5px 0 10px" class="winput form-control" name="reftype" id="reftype-manual" value="manual" <?= ( isset( $settings['reftype'] ) && $settings['reftype'] === 'manual' ? 'checked' : ! isset( $settings['reftype'] ) ) ? 'checked' : '' ?>/>
                        <label for="reftype-manual"><?= __( "Manual input", "zero-bs-crm" ) ?></label>
                        <br>
                        <input type="radio" style="margin:0 5px 0 10px" class="winput form-control" name="reftype" id="reftype-autonumber" value="autonumber" <?= isset( $settings['reftype'] ) && $settings['reftype'] === 'autonumber' ? 'checked' : '' ?> /> <label for="reftype-autonumber"><?= __( "Auto-generated reference", "zero-bs-crm" ) ?></label>
                    </div>
                </td>
                <td style="width:540px; vertical-align: middle" class="zbs-settings-custom-fields" >
                    <div id="reftype-manual-block" class="reftype-block <?= ( isset( $settings['reftype'] ) && $settings['reftype'] === 'manual' ? 'reftype-set' : ! isset( $settings['reftype'] ) ) ? 'reftype-set' : '' ?>">
                        <div class="zbs-cf-type-autonumber-input-wrap">
                            <div class="ui labeled input">
                                <div class="ui label"><?= __( 'Default input', 'zero-bs-crm' ) ?></div>
                                <input type="text" class="form-control zbs-generic-hide zbs-cf-type-autonumber" name="defaultref" value="<?= isset( $settings['defaultref'] ) ? $settings['defaultref'] : '' ?>" placeholder="e.g. inv-" style="display: inline-block;">
                            </div>
                        </div>
                    </div>
                    <div id="reftype-autonumber-block" class="zbs-cf-type-autonumber-wrap zbs-generic-hide zbs-cf-type-autonumber reftype-block <?= isset( $settings['reftype'] ) && $settings['reftype'] === 'autonumber' ? 'reftype-set' : '' ?>">

                        <!-- Prefix -->
                        <div class="zbs-cf-type-autonumber-input-wrap">
                            <div class="ui labeled input">
                                <div class="ui label"><?= __( 'Prefix', 'zero-bs-crm' ) ?></div>
                                <input type="text" class="form-control zbs-generic-hide zbs-cf-type-autonumber" name="refprefix" value="<?= isset( $settings['refprefix'] ) ? $settings['refprefix'] : '' ?>" placeholder="Prefix (e.g. ABC-)" style="display: inline-block;">
                            </div>
                        </div>
                        <!-- Next number -->
                        <div class="zbs-cf-type-autonumber-input-wrap">
                            <div class="ui labeled input">
                                <div class="ui label"><?= __( 'Next number', 'zero-bs-crm' ) ?></div>
                                <input type="text" class="form-control zbs-generic-hide zbs-cf-type-autonumber" name="refnextnum" value="<?= isset( $settings['refnextnum'] ) ? $settings['refnextnum'] : '1' ?>" placeholder="Next Number (e.g. 1)" style="display: inline-block;">
                            </div>
                        </div>
                        <!-- Suffix -->
                        <div class="zbs-cf-type-autonumber-input-wrap">
                            <div class="ui labeled input">
                                <div class="ui label"><?= __( 'Suffix', 'zero-bs-crm' ) ?></div>
                                <input type="text" class="form-control zbs-generic-hide zbs-cf-type-autonumber" name="refsuffix" value="<?= isset( $settings['refsuffix'] ) ? $settings['refsuffix'] : '' ?>" placeholder="Suffix (e.g. -FINI)" style="display: inline-block;">
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="defaultref">
                        <?php _e( 'Invoice reference label', 'zero-bs-crm' ) ?>:</label><br />
                    <?php _e( "What should we call this ID? (The default label is 'Reference'.)", 'zero-bs-crm' ) ?>
                </td>
                <td>
                    <input type="text" class="winput form-control" name="reflabel" placeholder="e.g. Ref." value="<?= isset( $settings['reflabel'] ) ? $settings['reflabel'] : __('Reference', 'zero-bs-crm') ?>"/>
                </td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="businessextra"><?php _e('Extra Invoice Info',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your invoice',"zero-bs-crm"); ?></td>
                <td style="width:540px"><textarea class="winput form-control" name="businessextra" id="businessextra"  placeholder="<?php _e('e.g. Your Address','zero-bs-crm'); ?>" ><?php if (isset($settings['businessextra']) && !empty($settings['businessextra'])) echo $settings['businessextra']; ?></textarea></td>
            </tr>


            <tr>
                <td class="wfieldname"><label for="paymentinfo"><?php _e('Payment Info',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your invoice',"zero-bs-crm"); ?></td>
                <td style="width:540px"><textarea class="winput form-control" name="paymentinfo" id="paymentinfo"  placeholder="<?php _e('e.g. BACS details','zero-bs-crm'); ?>" ><?php if (isset($settings['paymentinfo']) && !empty($settings['paymentinfo'])) echo $settings['paymentinfo']; ?></textarea></td>
            </tr>


            <tr>
                <td class="wfieldname"><label for="paythanks"><?php _e('Thank You',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your invoice',"zero-bs-crm"); ?></td>
                <td style="width:540px"><textarea class="winput form-control" name="paythanks" id="paythanks"  placeholder="<?php _e('e.g. Thank you for your custom. If you have any questions let us know','zero-bs-crm'); ?>" ><?php if (isset($settings['paythanks']) && !empty($settings['paythanks'])) echo $settings['paythanks']; ?></textarea></td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="text-align:center"><?php _e('Looking for easy-pay/easy-access invoice links? You can now turn easy-access links on via the client portal settings page','zero-bs-crm'); ?></p>
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

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Statements',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>
            <tbody>

            <tr>
                <td class="wfieldname"><label for="zbsi_statementextra"><?php _e('Extra Statement Info',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your statements (e.g. How to pay)',"zero-bs-crm"); ?></td>
                <td style="width:540px"><textarea class="winput form-control" name="zbsi_statementextra" id="zbsi_statementextra"  placeholder="<?php _e('e.g. BACS details','zero-bs-crm'); ?>" ><?php if (isset($settings['statementextra']) && !empty($settings['statementextra'])) echo $settings['statementextra']; ?></textarea></td>
            </tr>


            </tbody>

        </table>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Additional settings',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_invid"><?php _e('Hide Invoice ID',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if want to hide the invoice ID (invoice ID increments automatically)',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invid" id="wpzbscrm_invid" value="1"<?php if (isset($settings['invid']) && $settings['invid'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_tax"><?php _e('Show tax on invoices',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if you need to charge tax',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invtax" id="wpzbscrm_invtax" value="1"<?php if (isset($settings['invtax']) && $settings['invtax'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_discount"><?php _e('Show discount on invoices',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if you want to add discounts',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invdis" id="wpzbscrm_invdis" value="1"<?php if (isset($settings['invdis']) && $settings['invdis'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_pandp"><?php _e('Show P&P on invoices',"zero-bs-crm"); ?> (<?php _e('Shipping',"zero-bs-crm"); ?>):</label><br /><?php _e('Tick if you want to add postage and packaging',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invpandp" id="wpzbscrm_invpandp" value="1"<?php if (isset($settings['invpandp']) && $settings['invpandp'] == "1") echo ' checked="checked"'; ?> /></td>
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

            jQuery('#wpzbscrm_invpro_pay').change(function(){

                if (jQuery(this).val() == "1"){
                    jQuery('.zbscrmInvProPayPalReq').hide();
                    jQuery('.zbscrmInvProStripeReq').show();
                } else {
                    jQuery('.zbscrmInvProPayPalReq').show();
                    jQuery('.zbscrmInvProStripeReq').hide();
                }


            });

            jQuery( '[name="reftype"]' ).change( function() {
                let reftype = jQuery( '[name="reftype"]:checked' ).val();
                jQuery( '.reftype-set' ).removeClass( 'reftype-set' );
                jQuery( '#reftype-' + reftype + '-block' ).addClass( 'reftype-set' );
            } );

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