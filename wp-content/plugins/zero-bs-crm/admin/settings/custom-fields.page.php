<?php 
/*!
 * Admin Page: Settings: Custom field settings
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit; 

global $wpdb, $zbs;  #} Req

$settings = $zbs->settings->getAll();

$acceptableCFTypes = zeroBSCRM_customfields_acceptableCFTypes();//array('text','textarea','date','select','tel','price','numberfloat','numberint','email');

//global $zbsCustomerFields;
//echo '<pre>'; print_r($zbsCustomerFields); echo '</pre>'; exit();

// this is used DAL3+
$keyDrivenCustomFields = array(

    'customers'=>ZBS_TYPE_CONTACT,
    'companies'=>ZBS_TYPE_COMPANY,
    'quotes'=>ZBS_TYPE_QUOTE,
    'transactions'=>ZBS_TYPE_TRANSACTION,
    'invoices'=>ZBS_TYPE_INVOICE,
    'addresses'=>ZBS_TYPE_ADDRESS

);

#} Act on any edits!
if (zeroBSCRM_isZBSAdminOrAdmin() && isset($_POST['editwplf'])){

    // check nonce
    check_admin_referer( 'zbs-update-settings-customfields' );

    #} Retrieve
    $customFields = array(

        'customers'=>array(),
        'customersfiles' => array(), // joc ++
        'companies'=>array(),
        'quotes'=>array(),
        'transactions'=>array(), // borge 2.91+
        'invoices'=>array(),
        'addresses'=>array()

    );

    // standard custom fields processing (not files/any that need special treatment)
    // genericified 20/07/19 2.91
    $customFieldsToProcess = array(
        'customers'=>'zbsCustomerFields',
        'companies'=>'zbsCompanyFields',
        'quotes'=>'zbsCustomerQuoteFields',
        'invoices'=>'zbsCustomerInvoiceFields',
        'transactions'=>'zbsTransactionFields',
        'addresses'=>'zbsAddressFields'
    );

    // this is used to stop dupes
    $customFieldSlugsUsed = array();

    #} Grab the first.... 128 ?
    for ($i = 1; $i <= 128; $i++){

        // WH generic'ified 20/07/18
        foreach ($customFieldsToProcess as $k => $globalVarName){

            // dupe check
            if (!isset($customFieldSlugsUsed[$k])) $customFieldSlugsUsed[$k] = array();

            # _t = type, _n = name, _p = placeholder
            if (isset($_POST['wpzbscrm_cf_'.$k.$i.'_t']) && !empty($_POST['wpzbscrm_cf_'.$k.$i.'_t'])){

                $possType = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_t']);
                $possName = zeroBSCRM_textProcess(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_n']));
                $possPlaceholder = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_p']); #} Placeholder text or csv options

                // 2.98.5 added autonumber, encrypted, radio, checkbox, so save these extras:
                // because it always outputs the inputs, its safe to not isset check etc. they'll just be blank for non-types

                // radio, checkbox have no different/special additions

                // encrypted
                // Removed encrypted (for now), see JIRA-ZBS-738
                // $encryptedPlaceholder = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_enp']);
                // $encryptedPassword = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_enpass']);

                // autonumber
                // because we store them dumbly in db, we don't allow special characters :)
                // allows alphanumeric + - + _

                if( isset( $_POST['wpzbscrm_cf_'.$k.$i.'_anprefix'] ) ) {
                    $autonumberPrefix = trim(zeroBSCRM_strings_stripNonAlphaNumeric_dash(sanitize_text_field($_POST['wpzbscrm_cf_' . $k . $i . '_anprefix'])));
                }
                if( isset( $_POST['wpzbscrm_cf_'.$k.$i.'_annextnumber'] ) ) {
                    $autonumberNextNumber = (int)zeroBSCRM_strings_stripNonNumeric(trim(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_annextnumber'])));
                }
                if( isset( $_POST['wpzbscrm_cf_'.$k.$i.'_ansuffix'] ) ) {
                    $autonumberSuffix = trim(zeroBSCRM_strings_stripNonAlphaNumeric_dash(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_ansuffix'])));
                }
                // roll them into one for storage :)
                // in fact we store them in placeholder for now! not super clean, custom fields needs a fresh rewrite (when can)
                // this overrides anything passed in _p above, also, so isn't messy messy :)
                if ($possType == 'autonumber') {
                    if ($autonumberNextNumber < 1) $autonumberNextNumber = 1;
                    $possPlaceholder = $autonumberPrefix.'#'.$autonumberNextNumber.'#'.$autonumberSuffix;
                }


                #} catch empty names
                if (empty($possName)) $possName = __('Custom Field ','zero-bs-crm'). (count($customFields[$k]) + 1);

                #} if using select, radio, or checkbox, trim ", " peeps
                if ($possType == 'select' || $possType == 'radio' || $possType == 'checkbox') $possPlaceholder = trim(str_replace(' ,',',',str_replace(', ',',',$possPlaceholder)));

                // 2.77+ added slug as a 4th arr item
                $possSlug = $zbs->DAL->makeSlug($possName);

                // 3.0.13 - Chinese characters were being obliterated by the transliterisor here, so this is a fallback gh-503
                $wasNotTransliteratable = false;
                if (empty($possSlug)) {
                    $possSlug = 'custom-field';
                    $wasNotTransliteratable = true;
                }


                //echo 'field: '.$k.'<br>';
                //echo 'isset:'.(isset(${$globalVarName}[$possSlug])).', iscustom: '.(isset(${$globalVarName}[$possSlug]['custom-field'])).', taken: '.(isset($customFieldSlugsUsed[$k][$possSlug])).'<br>';

                // 2.96.7+ CHECK against existing fields + add -1 -2 etc. if already in there
                global ${$globalVarName};
                if (

                (

                    isset(${$globalVarName}[$possSlug]) &&

                    (
                        // this means is a core field already with this name
                        (!isset(${$globalVarName}[$possSlug]['custom-field']))

                        ||

                        // this means it's a custom field, which has been pre-loaded, and this is the SECOND with that key
                        (isset(${$globalVarName}[$possSlug]['custom-field']) && isset($customFieldSlugsUsed[$k][$possSlug]))
                    )

                )
                ){

                    // is already set, try this
                    $c = 1;
                    while ($c <= 10){

                        // try append
                        if (!isset(${$globalVarName}[$possSlug.'-'.$c])){

                            // got one that's okay, set + break
                            if (!$wasNotTransliteratable) $possName .= ' '.$c;
                            $possSlug = $possSlug.'-'.$c;
                            $c=11;

                        }

                        $c++;

                    }

                }

                //'id' here stops ever using that
                if ($possSlug == 'id'){

                    // is already set, try this
                    $c = 1;
                    while ($c <= 10){

                        // try append
                        if (!isset(${$globalVarName}[$possSlug.'-'.$c])){

                            // got one that's okay, set + break
                            if (!$wasNotTransliteratable) $possName .= ' '.$c;
                            $possSlug = $possSlug.'-'.$c;
                            $c=11;

                        }

                        $c++;

                    }

                }

                if (in_array($possType,$acceptableCFTypes)){

                    #} Add it
                    $customFields[$k][] = array($possType,$possName,$possPlaceholder,$possSlug);
                    // dupe check
                    $customFieldSlugsUsed[$k][$possSlug] = 1;

                }

            }



        }

        #} CUSTOMERS FILES ( if using )
        # _t = type, _n = name, _p = placeholder
        if (isset($_POST['wpzbscrm_cf_customersfiles'.$i.'_n']) && !empty($_POST['wpzbscrm_cf_customersfiles'.$i.'_n'])){

            $possName = zeroBSCRM_textProcess(sanitize_text_field($_POST['wpzbscrm_cf_customersfiles'.$i.'_n']));

            #} Add
            if (!empty($possName)) $customFields['customersfiles'][] = array($possName);

        }


    } // end for loop 30 fields

    // update DAL 2 custom fields :) (DAL3 dealt with below)
    if ($zbs->isDAL2() && !$zbs->isDAL3()){

        if (isset($customFields['customers']) && is_array($customFields['customers'])){

            // slight array reconfig
            $db2CustomFields = array();
            foreach ($customFields['customers'] as $cfArr){
                $db2CustomFields[$cfArr[3]] = $cfArr;
            }

            // simple maintain DAL2 (needs to also)
            $zbs->DAL->updateActiveCustomFields(array('objtypeid'=>1,'fields'=>$db2CustomFields));

        }

    }
    // DAL3 they all get this :)
    if ($zbs->isDAL3()){

        foreach ($keyDrivenCustomFields as $key => $objTypeID){

            if (isset($customFields[$key]) && is_array($customFields[$key])){

                // slight array reconfig
                $db2CustomFields = array();
                foreach ($customFields[$key] as $cfArr){
                    $db2CustomFields[$cfArr[3]] = $cfArr;
                }

                // simple maintain DAL2 (needs to also)
                $zbs->DAL->updateActiveCustomFields(array('objtypeid'=>$objTypeID,'fields'=>$db2CustomFields));

            }

        }

    }

    #} Brutal update (note this is on top of updateActiveCustomFields DAL2+ work above)
    $zbs->settings->update('customfields',$customFields);

    #} TODO HERE:
    #} - check that  1) customized fields are already working, 2) the above saves over that properly
    #} add prefix matching save code here.


    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll(true);

}

// load
$fieldOverride = $settings['fieldoverride'];

// Following overloading code is also replicated in Fields.php, search #FIELDOVERLOADINGDAL2+

// This ALWAYS needs to get overwritten by DAL2 for now :)
if (zeroBSCRM_isZBSAdminOrAdmin() && $zbs->isDAL2() && !$zbs->isDAL3() && isset($settings['customfields']) && isset($settings['customfields']['customers'])){

    $settings['customfields']['customers'] = $zbs->DAL->setting('customfields_contact',array());

}
// DAL3 ver (all objs in $keyDrivenCustomFields above)
if ($zbs->isDAL3()){

    foreach ($keyDrivenCustomFields as $key => $objTypeID){

        if (isset($settings['customfields']) && isset($settings['customfields'][$key])){

            // turn ZBS_TYPE_CONTACT (1) into "contact"
            $typeStr = $zbs->DAL->objTypeKey($objTypeID);
            if (!empty($typeStr)) $settings['customfields'][$key] = $zbs->DAL->setting('customfields_'.$typeStr,array());

        }

    }

}

// / field Overloading

?>

<p id="sbDesc"><?php _e('Using this page you can add or edit custom fields for your CRM',"zero-bs-crm"); ?></p>

<?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Custom Fields Updated',"zero-bs-crm")); echo '</div>'; } ?>

<div id="sbA" class="zbs-settings-custom-fields">

    <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=customfields">
        <input type="hidden" name="editwplf" id="editwplf" value="1" />
        <?php

        // loading here is shown until custom fields drawn, then this loader hidden and all .zbs-generic-loaded shown
        echo zeroBSCRM_UI2_loadingSegmentHTML('300px','zbs-generic-loading');

        // add nonce
        wp_nonce_field( 'zbs-update-settings-customfields');

        ?>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Contact Custom Fields',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-customers-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-customer" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">

            <thead>

            <tr>
                <th class="wmid"><?php _e('Contact Custom File Upload Boxes',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-customersfiles-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-customerfiles" class="ui small blue button">+ <?php _e("Add Custom File Box","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e(jpcrm_label_company().' Custom Fields',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-companies-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-company" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Quote Custom Fields',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-quotes-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-quotes" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Invoice Custom Fields',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-invoices-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-invoices" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">

            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Transaction Custom Fields',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-transactions-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-transactions" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>
        <table class="table table-bordered table-striped wtab zbs-generic-loaded">


            <thead>

            <tr>
                <th colspan="2" class="wmid"><?php _e('Address Custom Fields',"zero-bs-crm"); ?>:</th>
            </tr>

            </thead>

            <tbody id="zbscrm-addresses-custom-fields">

            <tr>
                <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-address" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
            </tr>

            </tbody>

        </table>


        <table class="table table-bordered table-striped wtab zbs-generic-loaded">
            <tbody>

            <tr>
                <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Custom Fields',"zero-bs-crm"); ?></button></td>
            </tr>

            </tbody>
        </table>

        <p style="text-align:center" class="zbs-generic-loaded">
            <i class="info icon"></i> <?php _e('Looking for default fields & statuses?','zero-bs-crm'); ?> <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=fieldoptions'); ?>"><?php _e('Click here for Field Options','zero-bs-crm'); ?></a>
        </p>

    </form>

    <script type="text/javascript">

        // all custom js moved to admin.settings.js 12/3/19 :)

        var wpzbscrmCustomFields = <?php echo json_encode($settings['customfields']); ?>;
        var wpzbscrmAcceptableTypes = <?php echo json_encode($acceptableCFTypes); ?>;
        var wpzbscrm_settings_page = 'customfields'; // this fires init js in admin.settings.min.js
        var wpzbscrm_settings_lang = {

            customfield:'<?php zeroBSCRM_slashOut(__('Custom Field','zero-bs-crm')); ?>',
            remove:     '<?php zeroBSCRM_slashOut(__('Remove','zero-bs-crm')); ?>',
            tel:        '<?php zeroBSCRM_slashOut(__('Telephone','zero-bs-crm')); ?>',
            numbdec:    '<?php zeroBSCRM_slashOut(__('Numeric (Decimals)','zero-bs-crm')); ?>',
            numb:       '<?php zeroBSCRM_slashOut(__('Numeric','zero-bs-crm')); ?>',
            placeholder:'<?php zeroBSCRM_slashOut(__('Placeholder','zero-bs-crm')); ?>',
            csvopt:     '<?php zeroBSCRM_slashOut(__("CSV of Options (e.g. 'a,b,c')",'zero-bs-crm')); ?>',
            fieldname:  '<?php zeroBSCRM_slashOut(__('Field Name','zero-bs-crm')); ?>',
            fieldplacehold:'<?php zeroBSCRM_slashOut(__('Field Placeholder Text','zero-bs-crm')); ?>',
            fileboxname: '<?php zeroBSCRM_slashOut(__('File Box Name','zero-bs-crm')); ?>',
            password:   '<?php zeroBSCRM_slashOut(__('Password','zero-bs-crm')); ?>',
            encryptedtext: '<?php zeroBSCRM_slashOut(__('Encrypted Text','zero-bs-crm')); ?>',
            radiobuttons: '<?php zeroBSCRM_slashOut(__('Radio Buttons','zero-bs-crm')); ?>',
            prefix:     '<?php zeroBSCRM_slashOut(__('Prefix','zero-bs-crm')); ?>',
            nextnumber: '<?php zeroBSCRM_slashOut(__('Next Number','zero-bs-crm')); ?>',
            suffix:     '<?php zeroBSCRM_slashOut(__('Suffix','zero-bs-crm')); ?>',
            prefixe:     '<?php zeroBSCRM_slashOut(__('(e.g. ABC-)','zero-bs-crm')); ?>',
            nextnumbere: '<?php zeroBSCRM_slashOut(__('(e.g. 1)','zero-bs-crm')); ?>',
            suffixe:     '<?php zeroBSCRM_slashOut(__('(e.g. -FINI)','zero-bs-crm')); ?>',
            fieldtype:   '<?php zeroBSCRM_slashOut(__('Field Type:','zero-bs-crm')); ?>',
            autonumberformat:   '<?php zeroBSCRM_slashOut(__('Autonumber Format','zero-bs-crm')); ?>',
            autonumberguide:   '<?php zeroBSCRM_slashOut(__('Autonumber Guide','zero-bs-crm')); ?>',

        };
        var wpzbscrm_settings_urls = {

            autonumberhelp: '<?php echo $zbs->urls['autonumberhelp']; ?>'

        };

    </script>

</div>
