<?php 
/*!
 * Admin Page: Settings: Client Portal settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit; 
global $wpdb, $zbs; #} Req

$confirmAct = false;
$settings = $zbs->settings->getAll();

#} Act on any edits!
if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-clients' );

    $updatedSettings['showportalpoweredby'] = 0; if (isset($_POST['wpzbscrm_showportalpoweredby']) && !empty($_POST['wpzbscrm_showportalpoweredby'])) $updatedSettings['showportalpoweredby'] = 1;

    $updatedSettings['portalusers'] = 0; if (isset($_POST['wpzbscrm_portalusers']) && !empty($_POST['wpzbscrm_portalusers'])) $updatedSettings['portalusers'] = 1;

    $updatedSettings['portalpage'] = 0; if (isset($_POST['wpzbscrm_portalpage']) && !empty($_POST['wpzbscrm_portalpage'])) $updatedSettings['portalpage'] = (int)sanitize_text_field($_POST['wpzbscrm_portalpage']);

    // any extra roles to assign?
    $updatedSettings['portalusers_extrarole'] = ''; if (isset($_POST['wpzbscrm_portalusers_extrarole']) && !empty($_POST['wpzbscrm_portalusers_extrarole'])) $updatedSettings['portalusers_extrarole'] = sanitize_text_field( $_POST['wpzbscrm_portalusers_extrarole'] );


    // status based auto-gen

    /* WH - should this be here? */
    #} retrieve value as simple CSV for now - simplistic at best.
    $zbsStatusStr = '';
    #} stored here: $settings['customisedfields']
    if (isset($settings['customisedfields']['customers']['status']) && is_array($settings['customisedfields']['customers']['status'])) $zbsStatusStr = $settings['customisedfields']['customers']['status'][1];
    if (empty($zbsStatusStr)) {
        #} Defaults:
        global $zbsCustomerFields; if (is_array($zbsCustomerFields)) $zbsStatusStr = implode(',',$zbsCustomerFields['status'][3]);
    }

    // cycle through + check post
    $zbsStatusSetting = 'all'; $zbsStatusSettingPotential = array();
    $zbsStatuses = explode(',', $zbsStatusStr);
    if (is_array($zbsStatuses)) foreach ($zbsStatuses as $statusStr){

        // permify
        $statusKey = strtolower(str_replace(' ','_',str_replace(':','_',$statusStr)));

        // check post
        if (isset($_POST['wpzbscrm_portaluser_group_'.$statusKey])) $zbsStatusSettingPotential[] = $statusKey;

    }

    if (count($zbsStatusSettingPotential) > 0) {

        // set that
        $zbsStatusSetting = $zbsStatusSettingPotential;

    }

    // update
    $updatedSettings['portalusers_status'] = $zbsStatusSetting;



    $updatedSettings['zbs_portal_email_content'] = ''; if (isset($_POST['zbs_portal_email_content']) && !empty($_POST['zbs_portal_email_content'])) $updatedSettings['zbs_portal_email_content'] = wp_kses_post(nl2br($_POST['zbs_portal_email_content']));

    // 2.84 wh
    $updatedSettings['portal_hidefields'] = ''; if (isset($_POST['wpzbscrm_portal_hidefields']) && !empty($_POST['wpzbscrm_portal_hidefields'])) $updatedSettings['portal_hidefields'] = sanitize_text_field( $_POST['wpzbscrm_portal_hidefields']);

    #} 2.86 ms
    $updatedSettings['portalpage'] = 0; if(isset($_POST['wpzbscrm_portalpage']) && !empty($_POST['wpzbscrm_portalpage'])) $updatedSettings['portalpage'] = (int)sanitize_text_field($_POST['wpzbscrm_portalpage']);

    #} 3.0 - Easy Access Links (hash urls)
    $updatedSettings['easyaccesslinks'] = 0; if (isset($_POST['wpzbscrm_easyaccesslinks']) && !empty($_POST['wpzbscrm_easyaccesslinks'])) $updatedSettings['easyaccesslinks'] = 1;

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Allow portal pro to hook into the save routine
    do_action('zbs_portal_settings_save');

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
        $confirmActStr      = __('Reset All Settings?',"zero-bs-crm");
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

    ##WLREMOVE
    if (current_user_can( 'admin_zerobs_manage_options' ) && !zeroBSCRM_isExtensionInstalled('clientportalpro')){

        // upsell button
        ?><a href="<?php echo $zbs->urls['extcpp']; ?>" target="_blank" class="ui button orange right floated"><?php _e('Get Portal PRO','zero-bs-crm'); ?></a><?php

    }
    ##/WLREMOVE

    ?>

    <p id="sbDesc"><?php _e('Setup your Client Portal here. You can do things like edit the email which is sent and choose your portal template.',"zero-bs-crm"); ?></p>
    <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
    <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>

    <div id="sbA">

    <?php

    // check user has permalinks proper
    if (function_exists('zeroBSCRM_portal_plainPermaCheck')) zeroBSCRM_portal_plainPermaCheck();

    ?>
    <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=clients">
        <input type="hidden" name="editwplf" id="editwplf" value="1" />
        <?php
        // add nonce
        wp_nonce_field( 'zbs-update-settings-clients');
        ?>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Client Portal Settings',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody>

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_showportalpoweredby"><?php _e('Show powered by Jetpack CRM',"zero-bs-crm"); ?>:</label><br /><?php _e('Help show us some love by displaying the powered by on your portal',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showportalpoweredby" id="wpzbscrm_showportalpoweredby" value="1"<?php if (isset($settings['showportalpoweredby']) && $settings['showportalpoweredby'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>
            <tr>
                <td class="wfieldname"><label for="wpzbscrm_portalpage"><?php _e('Client Portal Page',"zero-bs-crm"); ?>:</label><br /><?php _e("Select the page with your client portal shortcode","zero-bs-crm");?><br/>
                    <?php ##WLREMOVE ?>
                    <a href="https://kb.jetpackcrm.com/knowledge-base/how-does-the-client-portal-work/#client-portal-shortcode" target="_blank"><?php _e("Learn More", "zero-bs-crm"); ?></a>
                    <?php ##/WLREMOVE ?>
                </td>
                <td>
                    <?php

                    // reget
                    $portalPage = (int)zeroBSCRM_getSetting('portalpage',true);

                    // catch portal recreate
                    if (isset($_GET['recreateportalpage']) && isset($_GET['portalPageNonce']) && wp_verify_nonce($_GET['portalPageNonce'], 'recreate-portal-page')) {

                        // recreate
                        $portalPage = zeroBSCRM_portal_checkCreatePage();

                        if (!empty($portalPage) && $portalPage > 0){

                            // success
                            $newCPPageURL = admin_url('post.php?post='.$portalPage.'&action=edit');
                            echo zeroBSCRM_UI2_messageHTML('info',__('Portal Page Created','zero-bs-crm'),__('Jetpack CRM successfully created a new page for the Client Portal.','zero-bs-crm').'<br /><br /><a href="'.$newCPPageURL.'" class="ui button primary">'.__('View Portal Page','zero-bs-crm').'</a>','info','new-portal-page');

                        } else {

                            // failed
                            echo zeroBSCRM_UI2_messageHTML('warning',__('Portal Page Was Not Created','zero-bs-crm'),__('Jetpack CRM could not create a new page for the Client Portal. If this persists, please contact support.','zero-bs-crm'),'info','new-portal-page');

                        }


                    }


                    $args = array('name' => 'wpzbscrm_portalpage', 'id' => 'wpzbscrm_portalpage','show_option_none' => __('No Portal Page Found!','zero-bs-crm'));
                    if($portalPage != -1){
                        $args['selected'] = (int)$portalPage;
                    }else{
                        $args['selected'] = 0;
                    }
                    wp_dropdown_pages($args);

                    // recreate link
                    $recreatePortalPageURL = wp_nonce_url(admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=clients&recreateportalpage=1'), 'recreate-portal-page', 'portalPageNonce');

                    // detect missing page (e.g. it hasn't autocreated for some reason, or they deleted), and offer a 'make page' button
                    if (zeroBSCRM_portal_getPortalPage() == -1){

                        echo zeroBSCRM_UI2_messageHTML('warning',__('No Portal Page Found!','zero-bs-crm'),__('Jetpack CRM could not find a published WordPress page associated with the Client Portal. Please recreate this page to continue using the Client Portal.','zero-bs-crm').'<br /><br /><a href="'.$recreatePortalPageURL.'" class="ui button primary">'.__('Recreate Portal Page','zero-bs-crm').'</a>','info','no-portal-page');

                    } else {

                        // no need really?

                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_easyaccesslinks"><?php _e('Allow Easy-Access Links',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if want logged-out users to be able to view quotes and invoices, and pay for invoices (via a secure hash URL) on the portal',"zero-bs-crm"); ?></td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_easyaccesslinks" id="wpzbscrm_easyaccesslinks" value="1"<?php if (isset($settings['easyaccesslinks']) && $settings['easyaccesslinks'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>

            </tbody>
        </table>

        <table class="table table-bordered table-striped wtab">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Client Portal User Accounts',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody>

            <tr>
                <td colspan="2" class="wmid">
                    <?php _e('WordPress Users are required for each contact to access to your Client Portal.<br />You can generate these from any contact record, or automatically by selecting "Generate Users for all new contacts" below.',"zero-bs-crm"); ?>
                    <hr />
                    <?php _e('The following options all concern the automatic creation of client portal user accounts.',"zero-bs-crm"); ?>
                    <div class="zbs-explainer-ico"><i class="fa fa-id-card" aria-hidden="true"></i></div>
                </td>
            </tr>


            <tr>
                <td class="wfieldname"><label for="wpzbscrm_portalusers"><?php _e('Generate WordPress Users for new contacts',"zero-bs-crm"); ?>:</label><br /><?php _e('Note: This will automatically email the new contact a welcome email as soon as they\'re added.',"zero-bs-crm"); ?>.</td>
                <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_portalusers" id="wpzbscrm_portalusers" value="1"<?php if (isset($settings['portalusers']) && $settings['portalusers'] == "1") echo ' checked="checked"'; ?> /></td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_portalusers"><?php _e('Only Generate Users for Statuses',"zero-bs-crm"); ?>:</label><br />
                    <br /><?php
                    // reword suggested by omar - https://zerobscrmcommunity.slack.com/archives/C64JJ5B5W/p1544775973033900
                    //_e('If automatically generating users, you can restrict which users automatically get accounts here, (based on contact status).',"zero-bs-crm");
                    //_e('This will automatically disable/enable client portal accounts based on status changes.',"zero-bs-crm");
                    _e('Only users with the following status will have a portal account generated for them. If the status is not checked a user will not be generated. If the contact already has a portal account and they are moved to an unchecked status, their portal account will be disabled until they are moved to another checked status.','zero-bs-crm'); ?>
                    <br /><br /><strong><?php _e('Note: This only applies when Automatic Generation is ticked above.',"zero-bs-crm"); ?></strong></td>
                <td style="width:540px" id="zbs-portal-users-statuses">
                    <?php

                    #} retrieve value as simple CSV for now - simplistic at best.
                    $zbsStatusStr = '';
                    #} stored here: $settings['customisedfields']
                    if (isset($settings['customisedfields']['customers']['status']) && is_array($settings['customisedfields']['customers']['status'])) $zbsStatusStr = $settings['customisedfields']['customers']['status'][1];
                    if (empty($zbsStatusStr)) {
                        #} Defaults:
                        global $zbsCustomerFields; if (is_array($zbsCustomerFields)) $zbsStatusStr = implode(',',$zbsCustomerFields['status'][3]);
                    }

                    // setting - if set this'll be:
                    // "all"
                    // or array of status perms :)
                    $selectedStatuses = 'all';
                    if (isset($settings['portalusers_status'])) $selectedStatuses = $settings['portalusers_status'];

                    $zbsStatuses = explode(',', $zbsStatusStr);
                    if (is_array($zbsStatuses)) {

                        // each status
                        foreach ($zbsStatuses as $statusStr){

                            // permify
                            $statusKey = strtolower(str_replace(' ','_',str_replace(':','_',$statusStr)));

                            // checked?
                            $checked = false;
                            if (
                                (!is_array($selectedStatuses) && $selectedStatuses == 'all')
                                ||
                                (is_array($selectedStatuses) && in_array($statusKey,$selectedStatuses))
                            ) $checked = true;

                            ?><div class="zbs-status">
                            <input type="checkbox" value="1" name="wpzbscrm_portaluser_group_<?php echo $statusKey; ?>" id="wpzbscrm_portaluser_group_<?php echo $statusKey; ?>"<?php if ($checked) echo ' checked="checked"'; ?> />
                            <label for="wpzbscrm_portaluser_group_<?php echo $statusKey; ?>"><?php echo $statusStr; ?></label>
                            </div><?php

                        }

                    } else _e('No Statuses Found',"zero-bs-crm");


                    ?>
                </td>
            </tr>

            <tr>
                <td class="wfieldname"><label for="wpzbscrm_portalusers_extrarole"><?php _e('Assign extra role when generating users',"zero-bs-crm"); ?>:</label><br /><?php _e('If you\'d like to add a secondary role to users which Jetpack CRM creates automatically, you can do so here. (This is useful for integration into other plugins relating to access.)',"zero-bs-crm"); ?>.</td>
                <td style="width:540px">
                    <?php

                    $roles = zeroBSCRM_getWordPressRoles();

                    if (is_array($roles) && count($roles) > 0){

                        ?><select type="checkbox" name="wpzbscrm_portalusers_extrarole" id="wpzbscrm_portalusers_extrarole">
                        <option value=""><?php _e('None',"zero-bs-crm"); ?></option>
                        <option disabled="disabled" value="">====</option>
                        <?php

                        foreach ($roles as $roleKey => $roleArr){

                            // for their protection, gonna NOT include admin roles here..
                            $blockedArr = array('zerobs_admin','administrator');
                            // in fact no other zbs role... either...
                            if (substr($roleKey,0,7) != 'zerobs_' && !in_array($roleKey, $blockedArr)){

                                ?><option value="<?php echo $roleKey; ?>"<?php
                                if (isset($settings['portalusers_extrarole']) && $settings['portalusers_extrarole'] == $roleKey)  echo ' selected="selected"';
                                ?>><?php
                                if (is_array($roleArr) && isset($roleArr['name']))
                                    echo $roleArr['name'];
                                else
                                    echo $roleKey;
                                ?></option><?php

                            }


                        }

                        ?></select><?php

                    } else echo '-';


                    ?>
                </td>
            </tr>

            <tr>
                <td width="94">
                    <label for="zbs-status"><?php _e('Fields to hide on Portal',"zero-bs-crm"); ?></label><br /><?php _e('These fields will not be shown to the client on the client portal under "Your Details" (and so will not be editable).',"zero-bs-crm"); ?>.</td>
                </td>
                <td>
                    <?php

                    #} retrieve value as simple CSV for now - simplistic at best.
                    $portalHiddenFields = 'status,email';
                    if (isset($settings['portal_hidefields'])) $portalHiddenFields = $settings['portal_hidefields'];

                    ?>
                    <input type="text" name="wpzbscrm_portal_hidefields" id="wpzbscrm_portal_hidefields" value="<?php echo $portalHiddenFields; ?>" class="form-control" />
                    <small style="margin-top:4px"><?php _e("Default is","zero-bs-crm");?>:<br /><span style="background:#ceeaea;padding:0 4px">status,email</span></small>
                </td>
            </tr>


            </tbody>

        </table>

        <?php
        #} Hook in for client portal settings additions
        do_action('zbs_portal_after_settings');
        ?>

        <table class="table table-bordered table-striped wtab">
            <tbody>

            <?php

            $portalLink = zeroBS_portal_link();

            ?>

            <tr>
                <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button><a target="_blank" href="<?php echo $portalLink;?>" class="ui button green"><?php _e('Preview Portal',"zero-bs-crm");?></a></td>
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
