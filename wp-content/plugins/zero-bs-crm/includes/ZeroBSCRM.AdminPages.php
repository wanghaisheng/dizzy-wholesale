<?php 
/*!
 * Jetpack CRM
 * https://jetpackcrm.com
 * V1.20
 *
 * Copyright 2020 Automattic
 *
 * Date: 16th June 2020
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

/**
 * Load the page including the page file indicated through the section and the page name
 *
* @param string $section The section name
* @param string $page_name The page file name
* @param string $title The title of the page
*/
function jpcrm_load_page( $section, $page_name, $title = '' ) {

    $target_file = ZEROBSCRM_PATH . "/admin/$section/$page_name.page.php";

    if ( file_exists($target_file) ){

      include_once $target_file;

    } else { 

      echo zeroBSCRM_UI2_messageHTML( 'warning', '', __( 'Could not load the requested page.', 'zero-bs-crm' ) );

    }

}


/* ======================================================
   Page loading
   ====================================================== */

function zeroBSCRM_pages_settings() {
    jpcrm_load_page( 'settings', 'main' );
}

/* ======================================================
   / Page loading
   ====================================================== */


/* ======================================================
   Edit Post - multiform data override (for metaboxes)
   ====================================================== */

  #} Updated 1.2 so that this only fires on OUR post edit pages
  #} https://www.rfmeier.net/allow-file-uploads-to-a-post-with-wordpress-post_edit_form_tag-action/
  function zeroBSCRM_update_edit_form() {

      global $post;
      
      //  if invalid $post object, return
      if(!$post)
          return;
      
      //  get the current post type
      $post_type = get_post_type($post->ID);
      
      //  if post type is not 'post', return
      #if('post' != $post_type)
      if (!in_array($post_type,array('zerobs_customer','zerobs_quote','zerobs_invoice','zerobs_transaction','zerobs_company')))
          return;

      #echo ' enctype="multipart/form-data"';
      printf(' enctype="multipart/form-data" encoding="multipart/form-data" ');

  }
  add_action('post_edit_form_tag', 'zeroBSCRM_update_edit_form');

/* ======================================================
   / Edit Post - multiform data override (for metaboxes)
   ====================================================== */



/* ======================================================
   / Edit Post Messages (i.e. "Post Updated => Event Updated")
   / See: http://ryanwelcher.com/2014/10/change-wordpress-post-updated-messages/
   ====================================================== */

add_filter( 'post_updated_messages', 'zeroBSCRM_post_updated_messages' );
function zeroBSCRM_post_updated_messages( $messages ) {

  $post             = get_post();
  $post_type        = get_post_type( $post );
  $post_type_object = get_post_type_object( $post_type );
  
  $messages['zerobs_event'] = array(
    0  => '', // Unused. Messages start at index 1.
    1  => __( 'Task updated.' ),
    2  => __( 'Custom field updated.' ),
    3  => __( 'Custom field deleted.'),
    4  => __( 'Task updated.' ),
    /* translators: %s: date and time of the revision */
    5  => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s' ), wp_post_revision_title( (int) sanitize_text_field($_GET['revision']), false ) ) : false,
    6  => __( 'Task saved.' ),
    7  => __( 'Task saved.' ),
    8  => __( 'Task submitted.' ),
    9  => sprintf(
      __( 'Task scheduled for: <strong>%1$s</strong>.' ),
      // translators: Publish box date format, see http://php.net/date
      date_i18n(  'M j, Y @ G:i', strtotime( $post->post_date ) )
    ),
    10 => __( 'Task updated.' )
  );

        //you can also access items this way
        // $messages['post'][1] = "I just totally changed the Updated messages for standards posts";

        //return the new messaging 
  return $messages;
}


#} Deactivation error page - show if someone tried to deactivate the core with extensions still installed
function zeroBSCRM_pages_admin_deactivate_error(){
?>
    <div class='ui segment' style='text-align:center;'>
        <div style='font-size:60px;padding:30px;'>⚠️</div>
        <h3><?php _e("Error", "zero-bs-crm"); ?></h3>
        <p style='font-size:18px;'>
          <?php _e("You have tried to deactivate the Core while extensions are still active. Please de-activate extensions first.", "zero-bs-crm"); ?>
        </p>
        <p><a class='ui button blue' href="<?php echo admin_url('plugins.php'); ?>">Back to Plugins</a></p>
    </div>
<?php
}




#} Team UI page - i.e. to guide vs the wp-users.php
#} Added this to be able to make it easier for people to add team members to the CRM
#} Also to control permissions.
#} WHLOOK - is there a way of us finding out from telemetry how many people are actually using 
#} roles that are like the "customer" only role - as discussed I think our CRM has evolved past this
#} and we should have users as "CRMTEAM" members, and then "manage permissions" for them (vs the actual specific "role") 
function zeroBSCRM_pages_admin_team(){

    global $ZBSCRM_t,$wpdb;
    
    #} we can do this via AJAX eventually - but for now lets do it via normal $_POST stuff...
    $searching_users = false;
    
    #} User Search...
    if(isset($_POST['zbs-search-wp-users'])){

      $search = sanitize_text_field($_POST['zbs-search-wp-users']);
      $users = new WP_User_Query( array(
          'search'         => '*'.esc_attr( $search ).'*',
          'search_columns' => array(
              'user_nicename',
              'user_email',
          ),
      ) );
      $wp_users = $users->get_results();
        
      $zbsRoleIDs = array();
      foreach ( $wp_users as $user ) {
            $zbsRoleIDs[] = $user->ID;
      }

      $searching_users = true;

//      zbs_prettyprint($users_found);

    }else{
      // Jetpack CRM team roles.. 

        $role = array('zerobs_customermgr','zerobs_admin','administrator','zerobs_quotemgr','zerobs_invoicemgr','zerobs_transactionmgr','zerobs_mailmgr'); 




        $crm_users = get_users(array('role__in' => $role, 'orderby' => 'ID'));
        foreach ( $crm_users as $user ) {
            $zbsRoleIDs[] = $user->ID;
        }





    }



?>
    <script type="text/javascript">

        jQuery(document).ready(function($){

          jQuery('#zbs-search-wp-users').on("click"){
              jQuery("#zbs-users-search").submit();
          }


        });
    </script>

        



    <div class="ui segment zbs-inner-segment">
    <div id="zbs-team-mechanics">

      <form id="zbs-users-search" action="#" method="POST">
      <div class="ui search left" style="background:white;width:300px;float:left">
        <div class="ui icon input" style="width:100%;">
          <input class="prompt" name="zbs-search-wp-users"  type="text" placeholder="Search WordPress Users...">
          <i class="search icon" id="zbs-search-wp-users"></i>
        </div>
        <div class="results"></div>
      </div>
    </form>


        <a style="margin-left:10px;" class="ui button right" href="<?php echo admin_url('user-new.php?zbsslug=zbs-add-user'); ?>">
        <i class="add icon"></i> 
          <?php _e("Add New Team Member","zero-bs-crm");?>
        </a>

    </div>

    <div class='clear'></div>

    <div class="ui divider"></div>

    <table class="ui fixed single line celled table" id="zbs-team-user-table">
      <tbody>
        <th style="width:40px;"><?php _e("ID", "zero-bs-crm"); ?></th>
        <th><?php _e("Team member", "zero-bs-crm"); ?></th>
        <th><?php _e("Role", "zero-bs-crm"); ?></th>
        <th><?php _e("Last login", "zero-bs-crm"); ?></th>
        <th><?php _e("Manage permissions", "zero-bs-crm"); ?></th>
        <?php
        foreach($zbsRoleIDs as $ID){
            $user = get_user_by('ID', $ID);
            
            // zbs_prettyprint($user);

            $edit_url = admin_url('user-edit.php?user_id=' . $ID . '&zbsslug=zbs-edit-user');

            $caps_output = "";
            foreach($user->caps as $k => $v){
              $caps_output .= " " . zeroBSCRM_caps_to_nicename($k);
            }

            echo "<tr><td>".$ID."</td><td>" . get_avatar( $ID, 30 ) . "<div class='dn'>" . $user->display_name . "</div></td><td>" . $caps_output . "</td>";

            echo "<td>" . zeroBSCRM_wpb_lastlogin($ID) . " " . __("ago","zero-bs-crm") . "</td>";

            echo "<td><a href='".$edit_url."'' data-uid='".$ID."' class='zbs-perm-edit ui button mini blue'>";

            _e("Manage permissions", "zero-bs-crm"); 

            echo "</a></td>";

            echo "</tr>";

          //  zbs_prettyprint($user);
        }



        ?>

      </tbody>
    </table>


      </div>

<?php
}

#} this function turns our caps into a nicename for outputting
function zeroBSCRM_caps_to_nicename($caps = ''){

  $nicename = '';

  switch($caps){
    case 'administrator':
    $nicename = __("Full Jetpack CRM Permissions (WP Admin)", "zero-bs-crm");
    break;

    case 'zerobs_admin':
    $nicename = __("Full Jetpack CRM Permissions (CRM Admin)", "zero-bs-crm");
    break;

    case 'zerobs_customermgr':
    $nicename = __("Manage Customers Only", "zero-bs-crm");
    break;

    case 'zerobs_invoicemgr':
    $nicename = __("Manage Invoices Only", "zero-bs-crm");
    break;

    case 'zerobs_quotemgr':
    $nicename = __("Manage Quotes Only", "zero-bs-crm");
    break;
	
    case 'zerobs_transactionmgr':
    $nicename = __("Manage Transactions Only", "zero-bs-crm");
    break;
	
    case 'zerobs_mailmgr':
    $nicename = __("Manage Mail Only", "zero-bs-crm");
    break;

    default: 
    $nicename = ucfirst($caps);
    break;

  }

  return $nicename;

}


#} This is NOTIFICATIONS UI on the back on FEEDBACK from customers and Google Forms we were having people
#} saying things like "This is GREAT, just wished it integrated with WooCommerce (i.e. unaware it does)"
#} My thoughts here is it a page which detects certain classes etc (e.g. WooCommerce) and displays a notification
#} about it, and the benefits of them getting WooSync :-) 
function zeroBSCRM_pages_admin_notifications(){

    global $zeroBSCRM_notifications;

    #} have a whole plugin here, which does browser notifications etc for Plugin Hunt Theme
    #} have brought it into its own INCLUDE does things like new.comment have replaced it with our
    #} IA actions (new.customer, customer.status.change) 

    ?>



    <?php
    $zeroBSCRM_notifications = get_option('zbs-crm-notifications');
    if($zeroBSCRM_notifications == ''){
      $zeroBSCRM_notifications = 0;
    }
    #} WooCommerce for starters - 

    zeroBSCRM_notifyme_activity();

    #} Store in a notification here, e.g.
    $recipient = get_current_user_id();
    $sender = -999; //in this case...  we can call ZBS our -999 user
    $post_id = 0; //i.e. not a post related activity
    $type = 'woosync.suggestion';   //this is a extension suggestion type
   // notifyme_insert_notification($recipient,$sender,$post_id,$type);



}

#} Tag Manager Page
function zeroBSCRM_pages_admin_tags(){

  #} run some defaults here.. 
  $type      = 'contact';
  

  if(isset($_GET['tagtype']) && !empty($_GET['tagtype'])) $type    = sanitize_text_field($_GET['tagtype']);

  switch ($type){

    case 'contact':

        $upsellBoxHTML = '';

            #} has ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 


                // first build upsell box html
                $upsellBoxHTML = '<!-- Bulk Tagger --><div style="padding-right:1em">';
                $upsellBoxHTML .= '<h4>Tagging Tools:</h4>';

                    $upTitle = __('Bulk Tagger PRO',"zero-bs-crm");
                    $upDesc = __('Did you know that we\'ve made an extension for bulk tagging contacts based on transactions?',"zero-bs-crm");
                    $upButton = __('View Bulk Tagger',"zero-bs-crm");
                    $upTarget = 'https://jetpackcrm.com/product/bulk-tagger/';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Import Tools box -->';

            } else { 

              // later this can point to??? https://kb.jetpackcrm.com/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 

            } 

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_CONTACT, // v3.0 +
                'objType'       => 'contact',
                'singular'      => __('Contact',"zero-bs-crm"),
                'plural'        => __('Contacts',"zero-bs-crm"),
                'postType'      => 'zerobs_customer',
                'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                    // labels
                    //'what' => __('WHAT',"zero-bs-crm"),

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'company': // v3+

        $upsellBoxHTML = '';

            #} has ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 


                // first build upsell box html
                $upsellBoxHTML = '<!-- Bulk Tagger --><div style="padding-right:1em">';
                $upsellBoxHTML .= '<h4>Tagging Tools:</h4>';

                    $upTitle = __('Bulk Tagger PRO',"zero-bs-crm");
                    $upDesc = __('Did you know that we\'ve made an extension for bulk tagging contacts based on transactions?',"zero-bs-crm");
                    $upButton = __('View Bulk Tagger',"zero-bs-crm");
                    $upTarget = 'https://jetpackcrm.com/product/bulk-tagger/';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Import Tools box -->';

            } else { 

              // later this can point to??? https://kb.jetpackcrm.com/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 

            } 

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_COMPANY, // v3.0 +
                'objType'       => 'company',
                'singular'      => jpcrm_label_company(),
                'plural'        => jpcrm_label_company(true),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                    // labels
                    //'what' => __('WHAT',"zero-bs-crm"),

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'quote': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_QUOTE, // v3.0 +
                'objType'       => 'quote',
                'singular'      => __('Quote',"zero-bs-crm"),
                'plural'        => __('Quotes',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'invoice': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_INVOICE, // v3.0 +
                'objType'       => 'invoice',
                'singular'      => __('Invoice',"zero-bs-crm"),
                'plural'        => __('Invoices',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'transaction':

        $upsellBoxHTML = '';

            #} has ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 


                // first build upsell box html
                $upsellBoxHTML = '<!-- Bulk Tagger --><div style="padding-right:1em">';
                $upsellBoxHTML .= '<h4>Tagging Tools:</h4>';

                    $upTitle = __('Bulk Tagger PRO',"zero-bs-crm");
                    $upDesc = __('Did you know that we\'ve made an extension for bulk tagging contacts based on transactions?',"zero-bs-crm");
                    $upButton = __('View Bulk Tagger',"zero-bs-crm");
                    $upTarget = 'https://jetpackcrm.com/product/bulk-tagger/';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Import Tools box -->';

            } else { 

              // later this can point to??? https://kb.jetpackcrm.com/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 

            } 

        $tagView = new zeroBSCRM_TagManager(array(

                'objType'       => 'transaction',
                'objTypeID'     => ZBS_TYPE_TRANSACTION, // v3.0 +
                'singular'      => __('Transaction',"zero-bs-crm"),
                'plural'        => __('Transactions',"zero-bs-crm"),
                'postType'      => 'zerobs_transaction',
                'listViewSlug'      => 'manage-transaction-tags',
                'langLabels'    => array(

                    // labels
                    //'what' => __('WHAT',"zero-bs-crm"),

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'form': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_FORM, // v3.0 +
                'objType'       => 'form',
                'singular'      => __('Form',"zero-bs-crm"),
                'plural'        => __('Forms',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'event': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_EVENT, // v3.0 +
                'objType'       => 'event',
                'singular'      => __('Event',"zero-bs-crm"),
                'plural'        => __('Events',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;



  }

}

/* =======
Edit File UI
========== */

function zeroBSCRM_pages_edit_file(){

  global $zbs;

    $customer = -1; if (isset($_GET['customer'])) $customer = (int)sanitize_text_field($_GET['customer']);
    // or company...
    $company = -1; if (isset($_GET['company'])) $company = (int)sanitize_text_field($_GET['company']);

    if ( isset( $_GET['fileid'] ) ) {
      $fileid = (int)sanitize_text_field($_GET['fileid']);   //file ID is the ID of the file we want .. 
    }

    if ( !isset( $fileid )  || $fileid < 0 ) {
      echo zeroBSCRM_UI2_messageHTML( 'warning', __('File not found','zero-bs-crm'), __('Could not find this file.','zero-bs-crm') );
      return '';
    }

    if ($customer > 0 || $company > 0){

    //customer and file passed as variables. Allow us to edit the file title, description, show on portal, etc.
    
    //IF fileid is blank and newfile = true .. show the drag + drop uploader... if using fileslots, allow this to be chosen in the edit UI too..

    if ($customer > 0)
        $zbsFiles = zeroBSCRM_getCustomerFiles($customer);
    else if ($company > 0)
        $zbsFiles = zeroBSCRM_files_getFiles('company',$company);

    //file to edit is
    $ourFile = $zbsFiles[$fileid];
    $originalSlot = -1; if ($customer > 0) $originalSlot = zeroBSCRM_fileslots_fileSlot($ourFile['file'],$customer,ZBS_TYPE_CONTACT);

    if(isset($_POST['save']) && $_POST['save'] == -1){
      //we are saving down the file details... will add nonce too etc.
      echo "<div class='ui message blue' style='margin-right:20pz'><i class='icon info'></i> ".__("Details saved","zerobscrm") ."</div>";

      $title = sanitize_text_field($_POST['title']);
      $desc = wp_kses_post($_POST['desc']); // WH: should this be sanitized?
      $portal = ""; if(isset($_POST['fileportal']) && !empty($_POST['fileportal'])) $portal = (int)sanitize_text_field($_POST['fileportal']);
      $slot = ""; if(isset($_POST['fileslot']) && !empty($_POST['fileslot'])) $slot = sanitize_text_field($_POST['fileslot']);


      $ourFile['title'] = $title;
      $ourFile['desc'] = $desc;
      $ourFile['portal'] = $portal; // only customer
      // this is logged here, but can basically be ignored (is only logged via meta truthfully)
      //$ourFile['slot'] = $slot; 

      // 2.95 (support for CPP)
      $ourFile = apply_filters('zbs_cpp_fileedit_save',$ourFile,$_POST);

      $zbsFiles[$fileid] = $ourFile;

      if ($customer > 0)
        zeroBSCRM_updateCustomerFiles($customer, $zbsFiles);
      else if ($company > 0)
        zeroBSCRM_files_updateFiles('company',$company,$zbsFiles);

      // if slot, update manually (custs only)
      if (!isset($_POST['noslot']) && $customer > 0){

        // this'll empty the slot, if it previously had one and moved to new, or emptied
        // means 1 slot : 1 file
        if (!empty($originalSlot) && $slot != $originalSlot) zeroBSCRM_fileslots_clearFileSlot($originalSlot,$customer,ZBS_TYPE_CONTACT);

        // some slot
        // this will OVERRITE whatevers in that slot
        if (!empty($slot) && $originalSlot != $slot && $slot !== -1) zeroBSCRM_fileslots_addToSlot($slot,$ourFile['file'],$customer,ZBS_TYPE_CONTACT,true);        

        // reget
        $originalSlot = zeroBSCRM_fileslots_fileSlot($ourFile['file'],$customer,ZBS_TYPE_CONTACT);

      } 


    }

    // zeroBSCRM_updateCustomerFiles($cID, $zbsFiles);

    // get name
    $file = zeroBSCRM_files_baseName($ourFile['file'],isset($ourFile['priv']));


    /* debug 
    echo '<pre>';
    print_r(zeroBSCRM_fileSlots_getFileSlots(ZBS_TYPE_CONTACT));
    print_r(zeroBSCRM_fileslots_allSlots($customer,1));
    echo '</pre>'; */
    /* debug 
    echo '<pre>';
    print_r($ourFile);
    echo '</pre>'; */
    ?>

    <div class = "ui segment zbs-cp-file-edit-page">
      <?php

        // CPP thumb support. If file exists, display here
        if (function_exists('zeroBSCRM_cpp_getThumb')){

            $thumb = zeroBSCRM_cpp_getThumb($ourFile);
            if (!empty($thumb)){

                  // hacky solution to avoid shadow on 'filetype' default imgs
                  $probablyFileType = false; if (strpos($thumb, 'i/filetypes/') > 0) $probablyFileType = true;

                echo '<img src="'.$thumb.'" alt="'.__('File Thumbnail','zero-bs-crm').'" class="zbs-file-thumb';
                if ($probablyFileType) echo ' zbs-cp-file-img-default';
                echo '" />';
            }
        }

      ?>
      <h4><?php _e("Edit File Details", "zerobscrm"); ?></h4>
      <p>
        <?php _e("You are editing details for the following file", "zerobscrm"); ?>
        <br/>
        <em><?php echo $file; ?>
        (<a href="<?php echo $ourFile['url']; ?>" target="_blank"><?php _e("View file","zerobscrm"); ?></a>)</em>
      </p>
      <form class="ui form" method="POST" action="#">

          <label for="title"><?php _e("Title","zerobscrm");?></label>
          <input class="ui field input" id="title" name="title" value="<?php if(isset($ourFile['title'])) echo $ourFile['title'];?>" />

          <label for="desc"><?php _e("Description","zerobscrm"); ?></label>
          <textarea class="ui field textarea" id="desc" name="desc"><?php if(isset($ourFile['desc'])) echo $ourFile['desc'];?></textarea>

          <?php if(defined('ZBS_CLIENTPRO_TEMPLATES') && $customer > 0){ ?>
          <label for="fileportal"><?php _e("Show on Client Portal", "zerobscrm"); ?></label>
          <select class="ui field select" id="fileportal" name="fileportal">
              <option value="0" <?php if(isset($ourFile['portal']) && $ourFile['portal'] == 0 ) echo "selected"; ?>><?php _e("No","zerobscrm"); ?></option>
              <option value="1" <?php if(isset($ourFile['portal']) && $ourFile['portal'] == 1 ) echo "selected"; ?>><?php _e("Yes", "zerobscrm"); ?></option>
          </select>
          <?php } else { 
            
            // no client portal pro, so UPSELL :) 


            ##WLREMOVE 
            // only get admins!
            if (current_user_can('admin_zerobs_manage_options') && $customer > 0){ ?>
              <label><?php _e("Show on Client Portal", "zerobscrm"); ?></label>
              <div style="margin-bottom:1em;line-height: 1.8em"><input type="checkbox" name="fileportal" disabled="disabled" />&nbsp;&nbsp;<a href="<?php echo $zbs->urls['upgrade']; ?>?utm_content=inplugin-fileedit" target="_blank"><?php _e('Upgrade to a Bundle','zero-bs-crm'); ?></a> <?php _e('(and get Client Portal Pro) to enable this','zero-bs-crm'); ?>.</div><?php 
            }
            ##/WLREMOVE 

          } ?>

          <?php
            if ($customer > 0){
              // File slots 

              // Custom file attachment boxes
              //$settings = zeroBSCRM_getSetting('customfields'); $cfbInd = 1;
              //if (isset($settings['customersfiles']) && is_array($settings['customersfiles']) && count($settings['customersfiles']) > 0) {
              $fileSlots = zeroBSCRM_fileSlots_getFileSlots();
              // get all slots (to show 'overrite' warning)
              $allFilesInSlots = zeroBSCRM_fileslots_allSlots($customer,ZBS_TYPE_CONTACT);

              if (count($fileSlots) > 0){

                ?><label for="fileslot"><?php _e("Assign to Custom File Upload Box", "zerobscrm"); ?></label>
                <select class="ui field select" id="fileslot" name="fileslot">
                  <option value="-1"><?php _e("None", "zerobscrm");?></option><?php

                  foreach ($fileSlots as $cfb){

                      $nExtra = '';
                      if ($originalSlot != $cfb['key'] && isset($allFilesInSlots[$cfb['key']]) && !empty($allFilesInSlots[$cfb['key']])) 
                          $nExtra = ' ('.__('Current file','zero-bs-crm').': '.zeroBSCRM_files_baseName($allFilesInSlots[$cfb['key']],true).')';


                      echo '<option value="'.$cfb['key'].'"';
                      if (isset($originalSlot) && $originalSlot == $cfb['key']) echo ' selected="selected"';
                      echo '>'.$cfb['name'].$nExtra.'</option>';

                  }

                ?></select><?php

              } else echo '<input type="hidden" name="noslot" value="noslot" />';

            } ?>

            <?php 
                // Client portal pro integration
                do_action('zbs_cpp_fileedit',$ourFile);
            ?>

          <input type="hidden" value="-1" id="save" name="save"/>

          <input type="submit" class="ui button blue" value="<?php _e("Save details", "zerobscrm"); ?>"/>

      </form>

    </div>



    <?php

  } // if cid
}



/* ======================================================
   Admin Page Funcs (used for all adm pages)
   ====================================================== */

    #} Admin Page header
    function zeroBSCRM_pages_header($subpage=''){

      //global $wpdb, $zbs; #} Req
      // legacy.
   
    }


    #} Admin Page footer
    function zeroBSCRM_pages_footer(){
        
      // no longer needed now we don't wrap within zeroBSCRM_pages_header()
      // echo '</div>';
      // legacy.
      
    }


    #} Gross redir page
    function zeroBSCRM_pages_logout() {

      ?><script type="text/javascript">window.location='<?php echo wp_logout_url(); ?>';</script><h1 style="text-align:center">Logging you out!</h1><?php

    }

/* ======================================================
   / Admin Page Funcs (used for all adm pages)
   ====================================================== */



/* ======================================================
  Pagination functions
  ===================================================== */

  function zeroBSCRM_pagelink($page){
    if($page > 0){
      $pagin = $page;
    }else{
      $pagin = 0;
    }
    $zbsurl = get_admin_url('','admin.php?page=customer-searching') ."&zbs_page=".$pagin;
    return $zbsurl;
  }

  function zeroBSCRM_pagination( $args = array() ) {
      $defaults = array(
          'range'           => 4,
          'previous_string' => __( 'Previous', 'zero-bs-crm' ),
          'next_string'     => __( 'Next', 'zero-bs-crm' ),
          'before_output'   => '<ul class="pagination">',
          'after_output'    => '</ul>',
          'count'       => 0,
          'page'        => 0
      );
      
      $args = wp_parse_args( 
          $args, 
          apply_filters( 'wp_bootstrap_pagination_defaults', $defaults )
      );
      
      $args['range'] = (int) $args['range'] - 1;
      $count = (int)$args['count'];
      $page  = (int)$args['page'];
      $ceil  = ceil( $args['range'] / 2 );
      
      if ( $count <= 1 )
          return FALSE;
      
      if ( !$page )
          $page = 1;
      
      if ( $count > $args['range'] ) {
          if ( $page <= $args['range'] ) {
              $min = 1;
              $max = $args['range'] + 1;
          } elseif ( $page >= ($count - $ceil) ) {
              $min = $count - $args['range'];
              $max = $count;
          } elseif ( $page >= $args['range'] && $page < ($count - $ceil) ) {
              $min = $page - $ceil;
              $max = $page + $ceil;
          }
      } else {
          $min = 1;
          $max = $count;
      }
      
      $echo = '';
      $previous = intval($page) - 1;
      $previous = esc_attr( zeroBSCRM_pagelink($previous) );
      
      $firstpage = esc_attr( zeroBSCRM_pagelink(0) );

      if ( $firstpage && (1 != $page) )
          $echo .= '<li class="previous"><a href="' . $firstpage . '">' . __( 'First', 'text-domain' ) . '</a></li>';
      if ( $previous && (1 != $page) )
          $echo .= '<li><a href="' . $previous . '" title="' . __( 'previous', 'text-domain') . '">' . $args['previous_string'] . '</a></li>';
      
      if ( !empty($min) && !empty($max) ) {
          for( $i = $min; $i <= $max; $i++ ) {
              if ($page == $i) {
                  $echo .= '<li class="active"><span class="active">' . str_pad( (int)$i, 2, '0', STR_PAD_LEFT ) . '</span></li>';
              } else {
                  $echo .= sprintf( '<li><a href="%s">%002d</a></li>', esc_attr( zeroBSCRM_pagelink($i) ), $i );
              }
          }
      }
      
      $next = intval($page) + 1;
      $next = esc_attr( zeroBSCRM_pagelink($next) );
      if ($next && ($count != $page) )
          $echo .= '<li><a href="' . $next . '" title="' . __( 'next', 'text-domain') . '">' . $args['next_string'] . '</a></li>';
      
      $lastpage = esc_attr( zeroBSCRM_pagelink($count) );
      if ( $lastpage ) {
          $echo .= '<li class="next"><a href="' . $lastpage . '">' . __( 'Last', 'text-domain' ) . '</a></li>';
      }
      if ( isset($echo) )
          echo $args['before_output'] . $echo . $args['after_output'];
  }


/* ======================================================
   Admin Pages
   ====================================================== */


#} New Home page
function zeroBSCRM_pages_dash(){
  
  global  $zbs,$zeroBSCRM_paypal_slugs;  //paypal extension slugs... ?>


<div class='zbs-dash-header'>
  <?php ##WLREMOVE ?>
  <div class="ui message compact" style="
    max-width: 400px;
    float: right;
    margin-top: -25px;
    margin-right: 30px;text-align:center;display:none;">
    <div class="header">
    </div>
  </div>
  <?php ##/WLREMOVE ?>


</div>

<?php
      $cid = get_current_user_id();
      $settings_dashboard_total_contacts      = get_user_meta($cid, 'settings_dashboard_total_contacts' ,true);
      $settings_dashboard_total_leads         = get_user_meta($cid, 'settings_dashboard_total_leads' ,true);
      $settings_dashboard_total_customers     = get_user_meta($cid, 'settings_dashboard_total_customers' ,true);
      $settings_dashboard_total_transactions  = get_user_meta($cid, 'settings_dashboard_total_transactions' ,true);

      $settings_dashboard_sales_funnel        = get_user_meta($cid, 'settings_dashboard_sales_funnel' ,true);
      $settings_dashboard_revenue_chart       = get_user_meta($cid, 'settings_dashboard_revenue_chart' ,true);
      $settings_dashboard_recent_activity     = get_user_meta($cid, 'settings_dashboard_recent_activity' ,true);
      $settings_dashboard_latest_contacts     = get_user_meta($cid, 'settings_dashboard_latest_contacts' ,true);

      if($settings_dashboard_total_contacts == ''){
          $settings_dashboard_total_contacts = 'true';
      }
      if($settings_dashboard_total_leads == ''){
          $settings_dashboard_total_leads = 'true';
      }
      if($settings_dashboard_total_customers == ''){
          $settings_dashboard_total_customers = 'true';
      }
      if($settings_dashboard_total_transactions == ''){
          $settings_dashboard_total_transactions = 'true';
      }
      if($settings_dashboard_sales_funnel == ''){
          $settings_dashboard_sales_funnel = 'true';
      }
      if($settings_dashboard_revenue_chart == ''){
          $settings_dashboard_revenue_chart = 'true';
      }
      if($settings_dashboard_recent_activity== ''){
          $settings_dashboard_recent_activity = 'true';
      }
      if($settings_dashboard_latest_contacts == ''){
          $settings_dashboard_latest_contacts = 'true';
      }

?>



<style>


</style>

<?php wp_nonce_field( 'zbs_dash_setting', 'zbs_dash_setting_security' ); ?>
<?php wp_nonce_field( 'zbs_dash_count', 'zbs_dash_count_security' ); ?>

<div class='controls-wrapper'>

  <div id="zbs-date-picker-background">
    <div class='month-selector'>
        <div id="reportrange" class="pull-right" style="cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%;margin-top:-3px;width:220px;">
        <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
        <span></span> <b class="caret"></b>
        </div>
    </div>
  </div>

  <div class='dashboard-customiser'>
      <i class="icon sliders horizontal"></i>
  </div>

  <div class='dashboard-custom-choices'>
    <ul class="ui form">


    <?php 
      //this is to put a control AFTER row 1. i.e. the TOTALS
      do_action('zbs_dashboard_customiser_after_row_1'); 
    ?>


    <li class="item" id="settings_dashboard_sales_funnel_list">
      <label>
        <input type="checkbox" name="settings_dashboard_sales_funnel" id="settings_dashboard_sales_funnel" <?php if($settings_dashboard_sales_funnel == 'true'){ echo 'checked'; } ?>>
          <?php _e("Sales Funnel","zero-bs-crm"); ?>
    </label></li>

    <li class="item"><label>
        <input type="checkbox" name="settings_dashboard_revenue_chart" id="settings_dashboard_revenue_chart" <?php if($settings_dashboard_revenue_chart == 'true'){ echo 'checked'; } ?>>
          <?php _e("Revenue Chart","zero-bs-crm"); ?>
    </label></li>


    <li class="item"><label>
        <input type="checkbox" name="settings_dashboard_recent_activity" id="settings_dashboard_recent_activity" <?php if($settings_dashboard_recent_activity == 'true'){ echo 'checked'; } ?>>
          <?php _e("Recent Activity","zero-bs-crm"); ?>
    </label></li>

    <li class="item"><label>
        <input type="checkbox" name="settings_dashboard_latest_contacts" id="settings_dashboard_latest_contacts" <?php if($settings_dashboard_latest_contacts == 'true'){ echo 'checked'; } ?>>
          <?php _e("Latest Contacts","zero-bs-crm"); ?>
    </label></li>

    <?php do_action('zerobscrm_dashboard_setting'); ?>

  </ul>

  </div>

</div>

<!-- moving this to semantic UI grid layout for easier and future extendibility -->
<div class="ui grid narrow" id="crm_summary_numbers">

  <?php 
    // code for how many columns 
    $cols = 'four';
    $all_count = zeroBS_customerCount();
    $status1 = zeroBS_customerCountByStatus('lead'); 
    $status2 = zeroBS_customerCountByStatus('customer') + zeroBS_customerCountByStatus('upsell') + zeroBS_customerCountByStatus('postsale');
    $trans_count = zeroBS_tranCount();

    $latestLogs = zeroBSCRM_getAllContactLogs(true,10);

  ?>

 






  </div>

  <!--- the contaacts over time comes in next - PHP below is for the funnel -->
  <div class="ui grid narrow">



<?php


$zbsFunnelStr = zeroBSCRM_getSetting('zbsfunnel');

#} Defaults:
$zbsFunnelArr = array(); $zbsFunnelArrN = array();

#} Unpack.. if present
if (!empty($zbsFunnelStr)){

  if (strpos($zbsFunnelStr, ',') > -1) {

    // csv 
    $zbsFunnelArrN = explode(',',$zbsFunnelStr);
    $zbsFunnelArr = array_reverse($zbsFunnelArrN);

  } else {

    // single str 
    $zbsFunnelArr = array($zbsFunnelStr);
    $zbsFunnelArrN = array($zbsFunnelStr);

  }
}





$i = 0;
$tot = 0;
$n = count($zbsFunnelArr); 
// wh added these to stop php notices? 
$func = array(); $func = array();
foreach($zbsFunnelArr as $Funnel){
    //hack for demo site
    $fun[$i] = zeroBS_customerCountByStatus($Funnel);
    $func[$i] = $fun[$i] + $tot;
    $tot = $func[$i];
    $i++;
}

$values = array_reverse($func);

// WH note: added second set of SAME colours here - as was PHP NOTICE for users with more than 6 in setting below
$colors = array("#00a0d2", "#0073aa", "#035d88", "#333", "#222", "#000","#00a0d2", "#0073aa", "#035d88", "#333", "#222", "#000");
$colorsR = array_reverse($colors);

$i=0;
$data = '';
$n = count($zbsFunnelArr) -1;

// WH added - to stop 0 0 funnels
$someDataInData = false;

for($j = $n; $j >= 0;  $j--){

  $val = (int)$func[$j];

  if ($val > 0) $someDataInData = true;

  $data .= '{';
      $data .= "value: ".$val .",";
      $data .= "color: '".$colors[$j] ."',";
      $data .= "labelstr: '". $func[$j] . "'";
  $data .= '},';   
}

?>

<?php

/* Transactions - Revenue Chart data gen */

  #} Default
  $labels = array();

  $labels[0]    = "'". date('F Y') . "'";
  $labelsa[0]   = date('F Y');


for ($i = 0; $i < 12; $i++) {
  $date = date("M y", mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
  $labels[$i] = "'" . $date . "'";
  $labelsa[$i] = $date;
}

$labels = implode(",",array_reverse($labels));

$utsFrom = strtotime( 'first day of ' . date( 'F Y',strtotime('11 month ago')));
$utsNow = time();

$args = array(
  'paidAfter' => $utsFrom,
  'paidBefore' => $utsNow,     
);

//fill with zeros if months aren't present
for($i=11; $i > 0; $i--){
  $key = date("nY", mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
  $t[$key] = 0;
}

$recentTransactions = $zbs->DAL->transactions->getTransactionTotalByMonth($args);
foreach($recentTransactions as $k => $v){
  $trans[$k] = $v['total'];
  $dkey = $v['month'] . $v['year'];
  $t[$dkey] = $v['total'];
}

$i = 0;
foreach($t as $k => $v){
  $trans[$i] = $v;
  $i++;
}

if(is_array($trans)){
  $chartdataStr = implode(",",$t);
}

?>


<script type="text/javascript">
jQuery(document).ready(function(){

  funnel_height = jQuery('#bar-chart').height();
  jQuery('.zbs-funnel').height(funnel_height);

  jQuery('.learn')
    .popup({
      inline: false,
      on:'click',
      lastResort: 'bottom right',
  });

  <?php if(strlen($data) > 0){ ?>
  window.funnelData = [<?php echo $data; ?>];
  <?php }else{  ?>
  window.funnelData = '';
  <?php } ?>

  if (funnelData != '') jQuery('#funnel-container').drawFunnel(funnelData, {


    width: jQuery('.zbs-funnel').width() - 50, 
    height: jQuery('.zbs-funnel').height() -50,  

    // Padding between segments, in pixels
    padding: 1, 

    // Render only a half funnel
    half: false,  

    // Width of a segment can't be smaller than this, in pixels
    minSegmentSize: 30,  

    // label: function () { return "Label!"; } 


    label: function (obj) {
        return obj;
    }
  });


// WH added: don't draw if not there :)
if (jQuery('#bar-chart').length){

  new Chart(document.getElementById("bar-chart"), {
      type: 'bar',
      data: {
        labels: [<?php echo $labels; ?>],
        datasets: [
          {
            label: "",
            backgroundColor: "#222",
            data: [<?php echo $chartdataStr; ?>]
          }
        ]
      },
      options: {
        legend: { display: false },
        title: {
          display: false,
          text: ''
        },

        scales: {
          yAxes: [{
              display: true,
              ticks: {
                  beginAtZero: true   // minimum value will be 0.
              }
          }]
      }


      }
  });

}


});
</script>


<?php 
  do_action('zbs_dashboard_pre_dashbox_post_totals');
?>

</div>

<div class="ui grid narrow">
  <div class="six wide column zbs-funnel"  id="settings_dashboard_sales_funnel_display" <?php if($settings_dashboard_sales_funnel == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
   <div class='panel'>

      <div class="panel-heading" style="text-align:center">
        <h4 class="panel-title text-muted font-light"><?php _e("Sales Funnel","zero-bs-crm");?></h4>
      </div>
      <?php
      if (
          (is_array($data) && count($data) == 0)
          ||
          (is_string($data) && strlen($data) == 0)
          ||
          !$someDataInData
          ){ ?>
        <div class='ui message blue' style="text-align:center;margin-bottom:50px;">
            <?php _e("You do not have any contacts. Make sure you have contacts in each stage of your funnel.","zero-bs-crm");?> 
            <?php ##WLREMOVE ?><br/><br/>
            <a class="button ui blue" href="https://kb.jetpackcrm.com/knowledge-base/zero-bs-crm-dashboard/"><?php _e("Read Guide","zero-bs-crm");?></a>
            <?php ##/WLREMOVE ?>
        </div>
      <?php } else { ?>
      <div id="funnel-container"></div>
      <?php } ?>

      <div class='funnel-legend'>
          <?php
            $i = 0;
            $zbsFunnelArrR = array_reverse($zbsFunnelArr);
            $j = count($zbsFunnelArrR);
            foreach($zbsFunnelArrR as $Funnel){
                echo '<div class="zbs-legend" style="background:'.$colors[$j - $i -1].'"></div><div class="zbs-label">  ' . $Funnel . '</span></div>';
                $i++;
            }
          ?>
      </div>

    </div>
  </div>

  <div class="ten wide column" id="settings_dashboard_revenue_chart_display" <?php if($settings_dashboard_revenue_chart == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class='panel'>

      <div class="panel-heading" style="text-align:center">
      <?php  $currencyChar = zeroBSCRM_getCurrencyChr(); ?>
        <h4 class="panel-title text-muted font-light"><?php _e("Revenue Chart","zero-bs-crm");?> (<?php echo $currencyChar; ?>)</h4>
        <?php ##WLREMOVE ?>
		<?php if (!zeroBSCRM_isExtensionInstalled('salesdash')) {?>
		  <span class='upsell'><a href="https://jetpackcrm.com/product/sales-dashboard/" target="_blank"><?php _e("Want More?","zero-bs-crm");?></a></span>
		<?php } else { ?>
		  <span class='upsell'><a href="<?php echo zbsLink($zbs->slugs['salesdash']); ?>"><?php _e("Sales Dashboard","zero-bs-crm");?></a></span>
		<?php } ?>
		<?php ##/WLREMOVE ?>
      </div>


      <?php
      if(!is_array($trans) || array_sum($trans) == 0){ ?>
        <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;">
            <?php _e("You do not have any transactions that match your chosen settings. You need transactions for your revenue chart to show. If you have transactions check your settings and then transaction statuses to include.","zero-bs-crm");?> 
            <?php ##WLREMOVE ?><br/><br/>
            <a class="button ui blue" href="https://kb.jetpackcrm.com/knowledge-base/revenue-overview-chart/"><?php _e("Read Guide","zero-bs-crm");?></a>
            <?php ##/WLREMOVE ?>
        </div>
      <?php }else{ ?>
        <canvas id="bar-chart" width="800" height="403"></canvas>
      <?php } ?>
      
    </div>
  </div>
</div>




<?php
//changed this from false to 0, so we get all the logs and the functions actually get triggered..
// WH: changed for proper generic func $latestLogs = zeroBSCRM_getContactLogs(0,true,10);
$latestLogs = zeroBSCRM_getAllContactLogs(true,9);

?>


<div class="ui grid narrow">
  <div class="six wide column" id="settings_dashboard_recent_activity_display" <?php if($settings_dashboard_recent_activity == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>>
    <div class="panel">
        <div class="panel-heading" style="text-align:center">
          <h4 class="panel-title text-muted font-light"><?php _e("Recent Activity","zero-bs-crm");?></h4>
        </div>

        <div class="ui list activity-feed" style="padding-left:20px;margin-bottom:20px;">

        <?php  
        
        if(count($latestLogs) == 0){ ?>

          <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;margin-right:20px;">
              <i class="icon info"></i>
              <?php _e("No recent activity.","zero-bs-crm");?> 
          </div>


        <?php } ?>

        <?php if (count($latestLogs) > 0) foreach($latestLogs as $log){

            $em     = zeroBS_customerEmail($log['owner']);
            $avatar = zeroBSCRM_getGravatarURLfromEmail($em,28);
            $unixts =  date('U', strtotime($log['created']));
            $diff   = human_time_diff($unixts, current_time('timestamp'));

          if(isset($log['type'])){ 
            $logmetatype = $log['type']; 
          }else{ 
            $logmetatype = ''; 
          }

          // WH added from contact view:
            
            global $zeroBSCRM_logTypes, $zbs;
            // DAL 2 saves type as permalinked
            if ($zbs->isDAL2()){
              if (isset($zeroBSCRM_logTypes['zerobs_customer'][$logmetatype])) $logmetatype = __($zeroBSCRM_logTypes['zerobs_customer'][$logmetatype]['label'],"zero-bs-crm");
            }


          if(isset($log['shortdesc'])){ 
            $logmetashot = $log['shortdesc'];
          }else{ 
            $logmetashot = ''; 
          }


          $logauthor = ''; 
          if (isset($log['author'])) $logauthor = ' &mdash; ' . $log['author']; 
          

          /*

    <div class="feed-item">
      <div class="date">Sep 25</div>
      <div class="text">Responded to need <a href="single-need.php">â€œVolunteer opportunityâ€</a></div>
    </div>

          */


            echo "<div class='feed-item'>";
              echo "<div class='date'><img class='ui avatar img img-rounded' src='" . $avatar . "'/></div>";
              echo "<div class='content text'>";
                echo "<span class='header'>" . $logmetatype . "<span class='when'> (" . $diff . __(" ago","zero-bs-crm") . ")</span><span class='who'>".$logauthor."</span></span>";
                echo "<div class='description'>";
                  echo $logmetashot;
                echo "<br/>";
                echo "</div>";

            echo "</div>";

            echo "</div>";
        } else {

            echo "<div class='feed-item'>";
              echo "<div class='content text'>";
                echo "<span class='header'>" . __('Contact Log Feed',"zero-bs-crm") . "<span class='when'> (" . __("Just now","zero-bs-crm") . ")</span></span>";
                echo "<div class='description'>";
                  _e('This is where recent Contact actions will show up',"zero-bs-crm");
                  echo "<br/>";
                echo "</div>";
              echo "</div>";
            echo "</div>";
        }
        ?>
        </div>
    </div>
  </div>
  <div class="ten wide column" id="settings_dashboard_latest_contacts_display" <?php if($settings_dashboard_latest_contacts == 'true'){ echo "style='display:block;margin: 0;padding-left: 0;'";}else{ echo "style='display:none;'";} ?>  >
    <div class="panel">
        <div class="panel-heading" style="text-align:center;position:relative">
          <h4 class="panel-title text-muted font-light"><?php _e("Latest Contacts","zero-bs-crm");?></h4>
          <span class='upsell'><a href="<?php echo zbsLink($zbs->slugs['managecontacts']); ?>"><?php _e("View All","zero-bs-crm");?></a></span>
        </div>


      <?php
        $latest_cust = zeroBS_getCustomers(true,10,0);
      ?>

        <?php  if(count($latest_cust) == 0){ ?>

          <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;margin-right:20px;margin-left:20px;">
              <i class="icon info"></i>
              <?php _e("No contacts.","zero-bs-crm");?> 
          </div>


        <?php }else{ ?>

    <div class="panel-body">
      <div class="row">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table class="table table-hover m-b-0">
              <thead>
                <tr>
                  <th><?php _e("ID","zero-bs-crm");?></th>
                  <th><?php _e("Avatar","zero-bs-crm");?></th>
                  <th><?php _e("First Name","zero-bs-crm");?></th>
                  <th><?php _e("Last Name","zero-bs-crm");?></th>
                  <th><?php _e("Status","zero-bs-crm");?></th>
                  <th><?php _e("View","zero-bs-crm");?></th>
                  <th style="text-align:right;"><?php _e("Added","zero-bs-crm");?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php
                    foreach($latest_cust as $cust){
                      $avatar = ''; if (isset($cust) && isset($cust['email'])) $avatar = zeroBSCRM_getGravatarURLfromEmail($cust['email'],25);
                      $fname = ''; if (isset($cust) && isset($cust['fname'])) $fname = $cust['fname'];
                      $lname = ''; if (isset($cust) && isset($cust['lname'])) $lname = $cust['lname'];
                      $status = ''; if (isset($cust) && isset($cust['status'])) $status = $cust['status'];
                      if (empty($status)) $status = __('None',"zero-bs-crm");
                      echo "<tr>";
                        echo "<td>" .esc_html($cust['id']) . "</td>";
                        echo "<td><img class='img-rounded' src='" . esc_attr($avatar) . "'/></td>";
                        echo "<td><div class='mar'>" . esc_html($fname) . "</div></td>";
                        echo "<td><div class='mar'>" . esc_html($lname) . "</div></td>";
                        echo "<td class='zbs-s ".esc_attr('zbs-'.$zbs->DAL->makeSlug($status)) ."'><div>" . esc_html($status) . "</div></td>";
                      
                        echo "<td><div class='mar'><a href='" . zbsLink('view',$cust['id'],'zerobs_customer') . "'>";
                        _e('View',"zero-bs-crm");
                      echo "</a></div></td>";

                      echo "<td style='text-align:right;' class='zbs-datemoment-since' data-zbs-created-uts='" . esc_attr($cust['createduts']) . "'>" . esc_html($cust['created']) . "</td>";


                        echo "</tr>";
                      }
                    ?>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <?php } ?>


      </div>
  </div>
</div>





  <?php

}


function zerobscrm_show_love($url='', $text='Jetpack - The WordPress CRM'){
  #} Quick function to 'show some love'.. called from PayPal Sync and other locale.
  ?>
  <style>
   ul.share-buttons{
    list-style: none;
    padding: 0;
    text-align: center;
  }
  ul.share-buttons li{
    display: inline-block;
    margin-left:4px;
  }
  .logo-wrapper{
    padding:20px;
  }
  .logo-wrapper img{
    width:200px;
  }
  </style>

  <?php $text = htmlentities($text); ?>

  <p style="font-size:16px;text-align:center"><?php _e('Jetpack CRM is the ultimate CRM tool for WordPress.<br/ >Help us get the word out and show some love... You know what to do...',"zero-bs-crm"); ?></p>
  <ul class="share-buttons">
  <li><a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fjetpackcrm.com&t=<?php echo $text;?>" target="_blank"
  ><img src="<?php echo ZEROBSCRM_URL.'i/Facebook.png'; ?>"></a></li>
  <li><a href="https://twitter.com/intent/tweet?source=https%3A%2F%2Fjetpackcrm.com&text=<?php echo $text;?>%20https%3A%2F%2Fjetpackcrm.com&via=zerobscrm" target="_blank" title="Tweet"><img src="<?php echo ZEROBSCRM_URL.'i/Twitter.png'; ?>"></a></li>
  <li><a href="https://plus.google.com/share?url=https%3A%2F%2Fjetpackcrm.com" target="_blank" title="Share on Google+" onclick="window.open('https://plus.google.com/share?url=' + encodeURIComponent(<?php echo $url; ?>)); return false;"><img src="<?php echo ZEROBSCRM_URL.'i/Google+.png'; ?>"></a></li>
  <li><a href="http://www.tumblr.com/share?v=3&u=https%3A%2F%2Fjetpackcrm.com&t=<?php echo $text;?>&s=" target="_blank" title="Post to Tumblr"><img src="<?php echo ZEROBSCRM_URL.'i/Tumblr.png'; ?>"></a></li>
  <li><a href="http://pinterest.com/pin/create/button/?url=https%3A%2F%2Fjetpackcrm.com&description=<?php echo $text;?>" target="_blank" title="Pin it"><img src="<?php echo ZEROBSCRM_URL.'i/Pinterest.png'; ?>"></a></li>
  <li><a href="https://getpocket.com/save?url=https%3A%2F%2Fjetpackcrm.com&title=<?php echo $text;?>" target="_blank" title="Add to Pocket"><img src="<?php echo ZEROBSCRM_URL.'i/Pocket.png'; ?>"></a></li>
  <li><a href="http://www.reddit.com/submit?url=https%3A%2F%2Fjetpackcrm.com&title=<?php echo $text;?>" target="_blank" title="Submit to Reddit"><img src="<?php echo ZEROBSCRM_URL.'i/Reddit.png'; ?>"></a></li>
  <li><a href="http://www.linkedin.com/shareArticle?mini=true&url=https%3A%2F%2Fjetpackcrm.com&title=<?php echo $text;?>&summary=&source=https%3A%2F%2Fjetpackcrm.com" target="_blank" title="Share on LinkedIn"><img src="<?php echo ZEROBSCRM_URL.'i/LinkedIn.png'; ?>"></a></li>
  <li><a href="http://wordpress.com/press-this.php?u=https%3A%2F%2Fjetpackcrm.com&t=<?php echo $text;?>&s=" target="_blank" title="Publish on WordPress"><img src="<?php echo ZEROBSCRM_URL.'i/Wordpress.png'; ?>"></a></li>
  <li><a href="https://pinboard.in/popup_login/?url=https%3A%2F%2Fjetpackcrm.com&title=<?php echo $text;?>&description=" target="_blank" title="Save to Pinboard" <img src="<?php echo ZEROBSCRM_URL.'i/Pinboard.png'; ?>"></a></li>
  <li><a href="mailto:?subject=&body=<?php echo $text;?>:%20https%3A%2F%2Fjetpackcrm.com" target="_blank" title="Email"><img src="<?php echo ZEROBSCRM_URL.'i/Email.png'; ?>"></a></li>
</ul>

  <?php
}


#} Main Config page
function zeroBSCRM_pages_home() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Homepage 
  if (!zeroBSCRM_isWL()){ 
    // Everyday homepage
    zeroBSCRM_html_home2();
  } else {
    // WL Home
    zeroBSCRM_html_wlHome();
  }

  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
}
 

#} Feedback page
function zeroBSCRM_pages_feedback() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header('Send Us Feedback');

  #} page 
  zeroBSCRM_html_feedback(); 

  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
}

#} Extensions page
function zeroBSCRM_pages_extensions() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header('Extensions');

  #} page 
  zeroBSCRM_html_extensions(); 

  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
}


function zeroBSCRM_pages_admin_system_emails(){

  global $zbs;

  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
   
    // discern subpage
    $page = 'recent-activity'; $pageName = __('Recent Email Activity','zero-bs-crm');
    if (isset($_GET['zbs_template_editor']) && !empty($_GET['zbs_template_editor'])) {
      $page = 'template-editor';
      $pageName = __('Template Settings','zero-bs-crm');
    }
    if (isset($_GET['zbs_template_id']) && !empty($_GET['zbs_template_id'])){
      $page = 'email-templates';
      $pageName = __('System Email Templates','zero-bs-crm');
    } 

    zeroBS_genericLearnMenu($pageName,'','',false,'','','','',false,false,'');


    // for now put this here, should probs be stored against template:
    // template id in here = can turn on/off
    $sysEmailsActiveInactive = array(1,2,6);

    // using tracking?
    $trackingEnabled = $zbs->settings->get('emailtracking');

 ?>
  <style>
    .email-stats{
      display: block;
      font-size: .75rem;
      text-transform: uppercase;
      color: #b8b8d9;
      font-weight: 600;
    }
    .email-template-box{
      cursor:pointer;
    }
    .the-templates a{
      color: black;
      font-weight:900;
    }
    time{
      white-space: nowrap;
      text-transform: uppercase;
      font-size: .5625rem;
      margin-left: 5px;
    }
    .hist-label{
      margin-right: 6px !important;
    }
    .email-sending-record{
      padding:10px;
    }
    .template-man-h4{
        font-weight:900;
        margin-bottom:0px;
        padding-top:10px;
    }
    .email-stats-top{
      font-size:13px;
      margin-top:5px;
      margin-bottom:5px;
    }
    .email-template-form label{
      text-transform: uppercase !important;
    }
    .the-templates .active{
    border: 1px solid #3f4347;
    border-left: 3px solid #3f4347;   
    }

    #tinymce{
      margin-left: 12px !important;
    }
    .lead{
      margin-top:5px;
      margin-bottom:5px;
    }
    .email-html-editor-free pre{
      text-align: center;
      padding: 50px;
      background: #f5f5f5;
      border: 2px dotted #ddd;
    }
    .update-nag{
      display:none;
    }
  </style>


  <script type="text/javascript">

      jQuery(document).ready(function(){

          jQuery('#zbs-sys-email-template-editor i.info.popup').popup({
            //boundary: '.boundary.example .segment'
          });

          jQuery('.zbs-turn-inactive').on("click",function(e){
              if(jQuery(this).hasClass('negative')){
                  return false;
              }
              jQuery('#zbs-saving-email-active').addClass('active');
              var theid = jQuery(this).data('emid');
              jQuery('#the-positive-button-' + theid).removeClass('positive');
              jQuery(this).addClass('negative');
              jQuery('.active-to-inactive-' + theid).addClass('negative');

              var t = {
                  action: "zbs_save_email_status",
                  id:  theid,
                  status: 'i',
                  security: jQuery( '#zbs-save-email_active' ).val(),
              }  
              i = jQuery.ajax({
                  url: ajaxurl,
                  type: "POST",
                  data: t,
                  dataType: "json"
              });
              i.done(function(e) {
                console.log(e);
                jQuery('#zbs-saving-email-active').removeClass('active');
                jQuery('#zbs-list-status-' + theid).removeClass('green').addClass('red');
                jQuery('#zbs-list-status-' + theid).html("<?php _e('Inactive','zero-bs-crm'); ?>");
              }),i.fail(function(e) {
              });
          });


          jQuery('#force-email-create').on("click", function(e){
              jQuery('#zbs-saving-email-create').addClass('active');
        
              var t = {
                  action: "zbs_create_email_templates",
                  security: jQuery( '#zbs_create_email_nonce' ).val(),
              }  
              
              i = jQuery.ajax({
                  url: ajaxurl,
                  type: "POST",
                  data: t,
                  dataType: "json"
              });
              i.done(function(e) {
                console.log(e);
                jQuery('#zbs-saving-email-create').removeClass('active');
                jQuery('#zbs-emails-result').html("");
                jQuery('.template-generate-results').show();

                // wh: just force reload it here?
                window.location.reload(false); 
               
              }),i.fail(function(e) {
              });


          });


          jQuery('.zbs-turn-active').on("click",function(e){
              
              jQuery('#zbs-saving-email-active').addClass('active');

              var theid = jQuery(this).data('emid');
              jQuery('#active-to-inactive-' + theid).removeClass('negative');
              jQuery(this).addClass('positive');
              jQuery('.inactive-to-active-' + theid).addClass('positive');

              //we want to AJAX save it using this action
              // zbs_save_email_status
              // with this nonce. 
              var t = {
                  action: "zbs_save_email_status",
                  id:  theid,
                  status: 'a',
                  security: jQuery( '#zbs-save-email_active' ).val(),
              }  
              
              i = jQuery.ajax({
                  url: ajaxurl,
                  type: "POST",
                  data: t,
                  dataType: "json"
              });
              i.done(function(e) {
                console.log(e);
                jQuery('#zbs-saving-email-active').removeClass('active');
                jQuery('#zbs-list-status-' + theid).removeClass('red').addClass('green');
                jQuery('#zbs-list-status-' + theid).html("<?php _e('Active','zero-bs-crm'); ?>");
              }),i.fail(function(e) {
              });


          });

      });

  </script>

  <?php

    $em_templates = '';
    $rec_ac = 'active';
    $template_id = -1;
    $tem_set = '';
    
    if (isset($_GET['zbs_template_id']) && !empty($_GET['zbs_template_id'])){
        $em_templates = 'active';
        $rec_ac = '';
        $template_id = (int)sanitize_text_field($_GET['zbs_template_id']);
        $tem_set = '';
    } else if (isset($_GET['zbs_template_editor']) && !empty($_GET['zbs_template_editor'])){
      $em_templates = '';
      $rec_ac = '';
      $template_id = -1;
      $tem_set = 'active';
    }

    $rec_acc_link = esc_url(admin_url('admin.php?page=zbs-email-templates'));


  ?>

  <div class="ui grid" style="margin-right:20px;">
    <div class="eight wide column"></div>
    <div class="eight wide column">
      <div id="email-template-submenu-admin" class="ui secondary menu pointing" style="float:right;">
          <a class="ui item <?php echo $rec_ac; ?>" href="<?php echo $rec_acc_link;?>"><?php _e("Recent Activity",'zero-bs-crm');?></a>
          <a class="ui item <?php echo $em_templates; ?>" href="<?php echo $rec_acc_link;?>&zbs_template_id=1"><?php _e("Email Templates",'zero-bs-crm');?></a>
          <a class="ui item <?php echo $tem_set; ?>" href="<?php echo $rec_acc_link;?>&zbs_template_editor=1"><?php _e("Template Settings",'zero-bs-crm');?></a>
      </div>
    </div>
  </div>


  <?php //if(isset($_GET['zbs_template_editor']) && !empty($_GET['zbs_template_editor'])){
    if ($page == 'template-editor'){ ?>

      <div class="ui segment" style="margin-right:20px;">
        <h4 class="template-man-h4"><?php _e("HTML Template", 'zero-bs-crm'); ?></h4>
        <p class='lead'><?php _e('This template is used for all outgoing ZBS emails. The <code>###MSGCONTENT###</code> placeholder represents the per-template content and must not be removed.','zero-bs-crm');?></p>
      
        <?php  ##WLREMOVE ?>
        <p class='lead'>
          <?php _e("You can edit this template by modifying ", 'zero-bs-crm'); ?>
          <code>/zero-bs-crm/html/templates/_responsivewrap.html</code> <?php _e(" but it is recommended to leave this template in tact for maximum device support."); ?>
        </p>
        <?php ##/WLREMOVE ?>
      
        <div class="ui divider"></div>
        <textarea cols="70" rows="25" name="zbstemplatehtml" id="zbstemplatehtml"><?php
            echo zeroBSCRM_mail_retrieveDefaultBodyTemplate('maintemplate');
          ?>
        </textarea>
        <div class="ui grid" style="margin-right:-15px;margin-top:20px;">
          <div class="eight wide column">
            <?php
              echo '<a href="' .$rec_acc_link .'" style="text-decoration:underline;font-size:11px;">' . __('Back to Activity','zero-bs-crm') . '</a>';
            ?>
          </div>
          <div class="eight wide column">
            <?php
            echo "<div style='float:right;'>";
              echo '<a href="'.site_url('?zbsmail-template-preview=1') .'"class="ui button inverted blue small" target="_blank">'.__('Preview','zero-bs-crm') .'</a>';
            echo '</div>';
            ?>
          </div>
        </div>
      </div>
    <?php
    } else { 
    ?>
    <div class="ui grid" id="zbs-sys-email-template-editor">

        <div class="five wide column the-templates">
            <?php
              //the template list...
              $zbs_system_emails = zeroBSCRM_mailTemplate_getAll();
              if(count($zbs_system_emails) == 0){

                //something went wrong with the creation of the emails...
                echo "<div class='ui segment' style='text-align:center'>";
                
                echo "<div id ='zbs-emails-result'>";
                    echo "<div class='ui inverted dimmer' id='zbs-saving-email-create'><div class='ui text loader'>".__("Creating templates....", 'zero-bs-crm') . "</div></div>";

                  echo '<h4 class="template-man-h4">' . __('No Email Templates', 'zero-bs-crm') . "</h4>";
                  echo "<p class='lead' style='padding:10px;'>" . __('Something went wrong with the email template creation.<br/>','zero-bs-crm') . "</p>";
                  echo "<div class='button ui large blue' id='force-email-create'>" . __('Create Now', 'zero-bs-crm') . "</div>";

                  echo "</div>";

                echo "<div class='template-generate-results' style='display:none;'>";
                  echo "<h4>" . __("Template Creation Succeeded",'zero-bs-crm') . "</h4>";
                  echo "<a href='".$rec_acc_link."' class='button ui green'>" . __('Reload Page','zero-bs-crm') . "</a>";
                echo "</div>";



                echo "</div>";



                echo '<input type="hidden" name="zbs_create_email_nonce" id="zbs_create_email_nonce" value="' . wp_create_nonce( 'zbs_create_email_nonce' ) . '" />';

              }
              foreach($zbs_system_emails as $sys_email){
                if($sys_email->zbsmail_id > 0){

                  if($template_id == $sys_email->zbsmail_id){
                    $class = 'active';
                  }else{
                    $class = '';
                  }

                  $link = esc_url(admin_url('admin.php?page=zbs-email-templates&zbs_template_id=' . $sys_email->zbsmail_id));

                  echo "<a href='$link'><div class='ui segment email-template-box $class' style='margin-bottom:10px;'>";
                    echo zeroBSCRM_mailTemplate_getSubject($sys_email->zbsmail_id);
  
                    // can be enabled/disabled
                    if (in_array($sys_email->zbsmail_id, $sysEmailsActiveInactive)){

                      if($sys_email->zbsmail_active == 1){
                        echo "<div class='ui label green tiny' id='zbs-list-status-". $sys_email->zbsmail_id."' style='float:right;margin-top:10px;'>" . __("Active",'zero-bs-crm') . "</div>";
                      }else{
                        echo "<div class='ui label red tiny' id='zbs-list-status-". $sys_email->zbsmail_id."' style='float:right;margin-top:10px;'>" . __("Inactive",'zero-bs-crm') . "</div>";     
                      }

                    }
  
                    // if tracking
                    if ($trackingEnabled == "1"){
                      echo "<div class='email-stats'>";
                        zeroBSCRM_mailDelivery_getTemplateStats($sys_email->zbsmail_id);
                      echo "</div>";
                    } else {
                      echo '<div class="email-stats">&nbsp;</div>';
                    }

                  echo "</div></a>";

                  }

              }
            ?>
            <div style="text-align:center;margin-top:1em">
              <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=mail'); ?>" class="ui basic button"><?php _e('Back to Mail Settings','zero-bs-crm'); ?></a>
            </div>
        </div>

        <div class="eleven wide column">
            <div class="segment ui" id="email-segment">
                <?php 
        
                  if(isset($_POST['zbssubject']) && !empty($_POST['zbssubject'])){

                    /* WH switched for mail delivery opts
                    $zbsfromname    = sanitize_text_field($_POST['zbsfromname']);
                    
                    //using sanitize email, can 
                    $zbsfromaddress = sanitize_email($_POST['zbsfromaddress']);
                    $zbsreplyto     = sanitize_email($_POST['zbsreplyto']);
                    $zbsccto        = sanitize_email($_POST['zbsccto']);
                    $zbsbccto       = sanitize_email($_POST['zbsbccto']);
                    */

                    // Mail Delivery
                    $zbsMailDeliveryMethod = sanitize_text_field($_POST['zbs-mail-delivery-acc']);
                    $zbsbccto       = sanitize_email($_POST['zbsbccto']);
                    

                    //this sanitizes the post content..
                    $zbscontent     = wp_kses_post($_POST['zbscontent']);
                    $zbssubject     = sanitize_text_field($_POST['zbssubject']);

                    if(isset($_GET['zbs_template_id'])){

                      $updateID = (int)sanitize_text_field($_GET['zbs_template_id']);

                      // wh simplified for del methods
                      //zeroBSCRM_updateEmailTemplate($updateID,$zbsfromname,$zbsfromaddress,$zbsreplyto, $zbsccto, $zbsbccto,$zbssubject, $zbscontent );
                      zeroBSCRM_updateEmailTemplate($updateID, $zbsMailDeliveryMethod, $zbsbccto,$zbssubject, $zbscontent );

                      echo "<div class='ui message green' style='margin-top:45px;margin-right:15px;'>" . __('Template updated','zerobsscrm') . '</div>';



                    }


                  }



                  if(isset($_GET['zbs_template_id']) && !empty($_GET['zbs_template_id'])){

                    //the tab number matches the template ID.
                    $emailtab = (int)sanitize_text_field($_GET['zbs_template_id']);

                    $form = '';

                    //single template data.
                    $data = zeroBSCRM_mailTemplate_get($emailtab);
                    if (gettype($data) == 'object') $form = $data;

                    if(!empty($form)){ 

                        //will need to nonce this up ... (?)
                        if(isset($_GET['sendtest']) && !empty($_GET['sendtest'])){

                            //we are sending a test...
                            $current_user   = wp_get_current_user();
                            $test_email    = $current_user->user_email;
                            
                            $html = zeroBSCRM_mailTemplate_emailPreview($emailtab);

                            //send it 
                            $subject = $form->zbsmail_subject;
                            $headers = zeroBSCRM_mailTemplate_getHeaders($emailtab);

                          /* old way


                            wp_mail( $test_email, $subject, $html, $headers );

                          */
                          
                          // discern del method
                          $mailDeliveryMethod = zeroBSCRM_mailTemplate_getMailDelMethod($emailtab);
                          if (!isset($mailDeliveryMethod) || empty($mailDeliveryMethod)) $mailDeliveryMethod = -1;

                            // build send array
                            $mailArray = array(
                              'toEmail' => $test_email,
                              'toName' => '',
                              'subject' => $subject,
                              'headers' => $headers,
                              'body' => $html,
                              'textbody' => '',
                              'options' => array(
                                'html' => 1
                              )
                            );

                            // Sends email
                            $sent = zeroBSCRM_mailDelivery_sendMessage($mailDeliveryMethod,$mailArray);

                            echo "<div class='ui message green' style='margin-top:45px;margin-right:15px;'>" . __('Test Email Sent to ','zerobsscrm') . '<b>'. $test_email .'</b></div>';
                        }

                          // if we're showing any email which requires CRON to send it, we show this message to further guide the end user:
                          if (in_array($emailtab, array(ZBSEMAIL_EVENTNOTIFICATION))){

                              ?><div class="ui blue label right floated"><i class="circle info icon link"></i> <?php _e("Note: This email requires cron.","zero-bs-crm"); ?> <a href="<?php echo esc_url($zbs->urls['kbcronlimitations']); ?>"><?php _e('Read about WordPress cron','zero-bs-crm'); ?></a></div><?php

                          }



                          echo "<h4 class='template-man-h4'>". zeroBSCRM_mailTemplate_getSubject($emailtab) . "</h4>";

                          echo "<div class='email-stats email-stats-top'>";
                            zeroBSCRM_mailDelivery_getTemplateStats($emailtab);
                          echo "</div>";

                          echo "<div class='ui inverted dimmer' id='zbs-saving-email-active'><div class='ui text loader'>".__("Saving....", 'zero-bs-crm') . "</div></div>";

                          wp_nonce_field( "zbs-save-email_active" );

                          echo '<input type="hidden" name="zbs-save-email_active" id="zbs-save-email_active" value="' . wp_create_nonce( 'zbs-save-email_active' ) . '" />';


                          // can be enabled/disabled
                          if (in_array($form->zbsmail_id, $sysEmailsActiveInactive)){

                              if($form->zbsmail_active){
                                // 1 = active, 0 = inactive..
                                echo '<div class="ui buttons tiny" style="float: right;
                                        position: absolute;
                                        top: 19px;
                                        right: 20px;">
                                        <button class="ui positive button zbs-turn-active" id="the-positive-button-'.$emailtab.'" data-emid="'.$emailtab.'">Active</button>
                                        <div class="or"></div>
                                        <button class="ui button zbs-turn-inactive" id="active-to-inactive-'.$emailtab.'" data-emid="'.$emailtab.'">Inactive</button>
                                      </div>';
                              }else{  
                                echo '<div class="ui buttons tiny" style="float: right;
                                        position: absolute;
                                        top: 19px;
                                        right: 20px;">
                                        <button class="ui button zbs-turn-active" id="the-positive-button-'.$emailtab.'" data-emid="'.$emailtab.'">Active</button>
                                        <div class="or"></div>
                                        <button class="ui button zbs-turn-inactive negative" id="active-to-inactive-'.$emailtab.'" data-emid="'.$emailtab.'">Inactive</button>
                                      </div>';
                              }

                          }


                          echo "<div class='ui divider'></div>";

                          $formlink = esc_url(admin_url('admin.php?page=zbs-email-templates&zbs_template_id=' . $emailtab));

                          echo "<form class='ui form email-template-form' action='".$formlink."' METHOD='POST'>";

                          echo '<div class="field">';
                            echo '<label>' . __('Subject','zero-bs-crm') .'</label>';
                            echo '<input id="zbssubject" name="zbssubject" type="text" value="'.$form->zbsmail_subject.'">';
                          echo '</div>';

                          // 11/05/18 - delivery methods replace hard-typed opts here
                          echo '<div class="field">';
                            
                            echo '<div class="ui grid" style="margin-bottom:-0.4em"><div class="four wide column">';
                            echo '<label>' . __('Delivery Method','zero-bs-crm') .'</label>';
                            echo '</div><div class="twelve wide column">';
                            ?><div class="ui teal label right floated"><i class="circle info icon link"></i> <?php _e("You can set up different delivery methods in your ","zero-bs-crm"); ?> <a href="<?php echo zbsLink($zbs->slugs['settings']).'&tab=maildelivery'; ?>"><?php _e('Delivery Methods Settings','zero-bs-crm'); ?></a></div><?php
                            echo '</div></div>';

                            zeroBSCRM_mailDelivery_accountDDL($form->zbsmail_deliverymethod);
                            
                          echo '</div>';

                          /* 
                          echo '<div class="field">';
                            echo '<label>' . __('From Name','zero-bs-crm') .'</label>';
                            echo '<input id="zbsfromname" name="zbsfromname" type="text" value="'.$form->zbsmail_fromname.'">';
                          echo '</div>';

                          echo '<div class="field">';
                            echo '<label>' . __('From Email','zero-bs-crm') .'</label>';
                            echo '<input id="zbsfromaddess" name="zbsfromaddress" type="text" value="'.$form->zbsmail_fromaddress.'">';
                          echo '</div>';

                          echo '<div class="field">';
                            echo '<label>' . __('Reply To','zero-bs-crm') .'</label>';
                            echo '<input id="zbsreplyto" name="zbsreplyto" type="text" value="'.$form->zbsmail_replyto.'">';
                          echo '</div>';

                          echo '<div class="field zbs-hide">';
                            echo '<label>' . __('Cc To','zero-bs-crm') .'</label>';
                            echo '<input id="zbsccto" name="zbsccto" type="text" value="'.$form->zbsmail_ccto.'">';
                          echo '</div>';
                          */

                          echo '<div class="field">';
                            echo '<label>' . __('Bcc To','zero-bs-crm') .'</label>';
                            echo '<input id="zbsbccto" name="zbsbccto" type="text" value="'.$form->zbsmail_bccto.'">';
                          echo '</div>';

                          echo '<div class="field">';
                            echo '<label>' . __('Content','zero-bs-crm') .'</label>';
                          $content = esc_html($form->zbsmail_body);
                          $edirotsettings = array(
                                  'media_buttons' => false,
                                  'editor_height' => 350,
                                  'quicktags' => false,
                                  'tinymce'=> false,
                          );
                          wp_editor( htmlspecialchars_decode($content), 'zbscontent',  $edirotsettings); 

                          echo '</div>';
                          ?>

                          <div class="ui grid" style="margin-right:-15px;">
                            <div class="eight wide column">
                              <?php
                                echo '<a href="' .$rec_acc_link. '&zbs_template_editor=1" style="text-decoration:underline;font-size:11px;">' . __('Edit HTML Template','zero-bs-crm') . '</a>';
                              ?>
                            </div>
                            <div class="eight wide column">
                              <?php

                              $sendtestlink = esc_url(admin_url('admin.php?page=zbs-email-templates&zbs_template_id=' . $emailtab . '&sendtest=1'));

                              echo "<div style='float:right;'>";
                                echo '<a href="'.site_url("?zbsmail-template-preview=1&template_id=". $emailtab).'" target="_blank" class="ui button inverted blue small">'.__('Preview','zero-bs-crm') .'</a>';
                                echo '<a href="'.$sendtestlink.'" class="ui button blue small">'.__('Send Test','zero-bs-crm') .'</a>';
                                echo '<input class="ui button green small" type="submit" value="'.__('Save','zero-bs-crm').'">';
                              echo '</div>';
                              ?>
                            </div>



                          </form>

                          <?php


                      }else{
                          echo "<div class='ui message blue'>";
                              echo "<i class='icon info'></i>" . __("No templates. Please generate", 'zero-bs-crm');
                          echo "</div>";
                      }


                //    zbs_prettyprint($data);



                    echo "</div>";


                  }else{
                    ?>

                      <h4 class="template-man-h4"><?php _e("Sent Emails", 'zero-bs-crm'); ?></h4>
                      <p class='lead'><?php _e("Your latest 50 emails are shown here so you can keep track of activity.", 'zero-bs-crm'); ?></p>
                      <div class="ui divider"></div>

                    <?php

                      zeroBSCRM_outputEmailHistory();

                  }

                ?>
            </div>
        </div>
    </div>

  <?php } //end of code for if template setting is being shown... ?>

  <?php
}


#} Data Tools Page
function zeroBSCRM_pages_datatools() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header('Import Tools');
  
  #} Settings
  zeroBSCRM_html_datatools();
  
  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 


#} Install Extensions helper page
function zeroBSCRM_pages_installextensionshelper() {
  
  global $wpdb, $zbs;  #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header(__('Installing Extensions',"zero-bs-crm"));
  
  #} Settings
  zeroBSCRM_html_installextensionshelper();
  
  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 

#} Post(after) deletion Page
function zeroBSCRM_pages_postdelete() {
  
  global $wpdb, $zbs; #} Req
  
  if (
    !zeroBSCRM_permsCustomers()
    && !zeroBSCRM_permsQuotes()
    && !zeroBSCRM_permsInvoices()
    && !zeroBSCRM_permsTransactions()
    )  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    #zeroBSCRM_pages_header('Deleted');

  #} Post Deletion page
  zeroBSCRM_html_deletion();
  
  #} Footer
  #zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 



#} No rights to this (customer/company)
function zeroBSCRM_pages_norights() {
  
  global $wpdb, $zbs;  #} Req
  
  if (
    !zeroBSCRM_permsCustomers()
    && !zeroBSCRM_permsQuotes()
    && !zeroBSCRM_permsInvoices()
    && !zeroBSCRM_permsTransactions()
    )  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    #zeroBSCRM_pages_header('Deleted');

  #} Post Deletion page
  zeroBSCRM_html_norights();
  
  #} Footer
  #zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 


#} System Status Page
function zeroBSCRM_pages_systemstatus() {
  
  global $wpdb, $zbs;  #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
  zeroBSCRM_pages_header('System Status');

  #} page
  zeroBSCRM_html_systemstatus();
  
  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 

// Whitelabel homepage.
function zeroBSCRM_html_wlHome(){

  global $zbs;

    ?>
    <div class="wrap">
    <h1 style="font-size: 34px;margin-left: 50px;color: #e06d17;margin-top: 1em;"><?php _e("Welcome to Jetpack CRM","zero-bs-crm");?></h1>
    <p style="font-size: 16px;margin-left: 50px;padding: 12px 20px 10px 20px;"><?php _e("This CRM Plugin is managed by Jetpack CRM","zero-bs-crm");?>. <?php _e("If you have any questions, please","zero-bs-crm");?> <a href="<?php echo $zbs->urls['support']; ?>"><?php _e('email us',"zero-bs-crm"); ?></a>.</p>
    <?php

    // let wl users add content
    do_action( 'zerobscrm_wl_homepage');

}


#} MS - 3rd Dec 2018 - new function for the home page - function name the same, old function below
function zeroBSCRM_html_home2(){

  global $zbs;

  /*
    to highlight the benefits of Jetpack CRM and going pro. Link into the new fature page
    show "Go Pro" offer and some testimonials :)
    need to remove top menu from this page ... do with ze CSS :-) 
  */

  //$add_new_customer_link = admin_url('admin.php?page=zbs-add-edit&action=edit&zbstype=contact');
  $add_new_customer_link = zbsLink('create',-1,'zerobs_customer');

  //change this to true when ELITE is out
  $isv3 = false;

  // WH added: Is now polite to License-key based settings like 'entrepreneur' doesn't try and upsell
  // this might be a bit easy to "hack out" hmmmm
  $bundle = false; if ($zbs->hasEntrepreneurBundleMin()) $bundle = true;


  // this stops hopscotch ever loading on this page :)
  ?><script type="text/javascript">var zbscrmjs_hopscotch_squash = true;</script>
  
  <div class='top-bar-welcome'></div>

  <div id="zbs-welcome">
    <div class="container">

      <div class="intro">

          <div class="block" style="text-align:center;margin-top:-50px;">
          <?php $bullie = zeroBSCRM_getBullie(); ?>
						<img src="<?php echo $bullie; ?>" alt="Jetpack CRMt" id="jetpack-crm-welcome" style="text-align:center;width:250px;padding:30px;"> 
						<h6><?php _e("Thank you for choosing Jetpack CRM - The Ultimate Entrepreneurs' CRM (for WordPress)","zero-bs-crm");?></h6>
					</div>

      </div>

      <div id="first-customer">
        <a href="https://jetpackcrm.com/learn/" target="_blank"><img src="<?php echo plugins_url('/i/first-customer-welcome-image.png', ZBS_ROOTFILE); ?>" alt="Adding your first customer"></a>
      </div>

      <div id="action-buttons" class='block'>
        <h6><?php _e("Jetpack CRM makes it easy for you to manage your customers using WordPress. To get started, ","zero-bs-crm"); echo '<a href="https://jetpackcrm.com/learn/" target="_blank">'; _e("watch the video tutorial","zero-bs-crm"); echo '</a> '; _e("or read our guide on how create your first customer","zero-bs-crm"); ?>:</h6>
        <div class='zbs-button-wrap'>
          <div class="left">
          <a href="<?php echo esc_url($add_new_customer_link); ?>" class='add-first-customer btn btn-cta'><?php _e("Add Your First Customer","zero-bs-crm");?></a>
          </div>
          <div class="right">
            <a href="https://kb.jetpackcrm.com/knowledge-base/adding-your-first-customer/" target="_blank" class='read-full-guide btn btn-hta'><?php _e("Read The Full Guide","zero-bs-crm");?></a>
          </div>
          <div class="clear"></div>
        </div>
      </div>


    </div><!-- / .container -->

    <div class="container margin-top30">
      <div class="intro zbs-features">

      <div class="block">
  			<h1><?php _e("Jetpack CRM Features and Extensions","zero-bs-crm");?></h1>
  			<h6><?php _e("Made for you, from the ground up. Jetpack CRM is both easy-to-use, and extremely flexible. Whatever your business, Jetpack CRM is the no-nonsense way of keeping a customer database","zero-bs-crm"); ?></h6>
  		</div>


      <div class="feature-list block">

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/crm-dash.png', ZBS_ROOTFILE); ?>">
  					<h5>CRM Dashboard</h5>
  					<p>See at a glance the key areas of your CRM: e.g. Contact Activity, Contact Funnel, and Revenue snapshot.</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/customers.png', ZBS_ROOTFILE); ?>">
  					<h5>Limitless Contacts</h5>
  					<p>Add as many contacts as you like. No limits to the number of contacts you can add to your CRM.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/quotes.png', ZBS_ROOTFILE); ?>">
  					<h5>Quote Builder</h5>
  					<p>Do you find yourself writing similar quotes/proposals over and over? Quote Builder makes it easy for your team.</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/invoices.png', ZBS_ROOTFILE); ?>">
  					<h5>Invoicing</h5>
  					<p>Got clients or people to bill? Easily create invoices, and get paid online (pro). Clients can see all Invoices in one place on the Client Portal.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/transactions.png', ZBS_ROOTFILE); ?>">
  					<h5>Transactions</h5>
  					<p>Log transactions against contacts or companies, and reconcile to invoices. Track payments, ecommerce data, and LTV (lifetime value).</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/b2b.png', ZBS_ROOTFILE); ?>">
  					<h5>B2B Mode</h5>
  					<p>Manage leads working at Companies? B2B mode lets you group contacts under a Company and keep track of sales easier.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/auto.png', ZBS_ROOTFILE); ?>">
  					<h5>Automations<span class='pro'>Entrepreneur</span></h5>
  					<p>Set up rule-based triggers and actions to automate your CRM work. Automatically Email new contacts, Distribute Leads, plus much more.</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/sms.png', ZBS_ROOTFILE); ?>">
  					<h5>Send SMS<span class='pro'>Entrepreneur</span></h5>
  					<p>Want to get in front of your customers, wherever they are? Send SMS messages to your contacts from their CRM record.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/cpp.png', ZBS_ROOTFILE); ?>">
  					<h5>Client Portal Pro<span class='pro'>Entrepreneur</span></h5>
  					<p>Create a powerful 'client portal' in one click! Easily share files with clients via their contact record. Tweak the portal to fit your branding, and more!</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/mail.png', ZBS_ROOTFILE); ?>">
            <?php if($isv3){  ?>
  					  <h5>Mail Campaigns<span class='pro-elite'>Elite</span></h5>
            <?php }else{ ?>
  					  <h5>Mail Campaigns<span class='pro'>Entrepreneur</span></h5>
            <?php } ?>
  					<p>Send Email Broadcasts and Sequences to your CRM contacts using our <strong>powerful</strong> Mail Campaigns v2.0. which is linked directly into your CRM data!</p>
  				</div>

  		</div>

      <div class="clear"></div>

      <div class='zbs-button-wrap'>

          <a href="https://jetpackcrm.com/features/" target="_blank" class='add-first-customer btn btn-hta'><?php _e("See All Features","zero-bs-crm");?></a>

      </div>


      <?php if (!$bundle){ ?>
      <div class="upgrade-cta upgrade">

					<div class="block">

						<div class="left">
							<h2>Upgrade to ENTREPRENEUR</h2>
							<ul>
								<li><span class="dashicons dashicons-yes"></span> PayPal Sync</li>
                <li><span class="dashicons dashicons-yes"></span> Invoicing Pro</li>
								<li><span class="dashicons dashicons-yes"></span> Stripe Sync</li>
                <li><span class="dashicons dashicons-yes"></span> Woo Sync</li>
								<li><span class="dashicons dashicons-yes"></span> User Registration</li>
								<li><span class="dashicons dashicons-yes"></span> Lead Capture</li>
								<li><span class="dashicons dashicons-yes"></span> Client Portal Pro</li>
								<li><span class="dashicons dashicons-yes"></span> Sales Dashboard</li>
								<li><span class="dashicons dashicons-yes"></span> Zapier</li>
                <li><span class="dashicons dashicons-yes"></span> Automations</li>
                <li style="width:100%"><span class="dashicons dashicons-yes"></span> Access to 30+ Extensions</li>
							</ul>
						</div>

						<div class="right">
							<h2><span>ENTREPRENEUR</span></h2>
							<div class="price">
								<span class="amount"><span class="dollar">$</span>199</span><br>
								<span class="term">per year</span>
							</div>
              <div class="zbs-button-wrap">
							<a href="http://bit.ly/2JuKSrY" rel="noopener noreferrer" target="_blank" class="btn btn-cta">
								Upgrade Now</a>
                <?php if($isv3){  ?>
                <div class='elite'>
                  <div class='go-elite'>or get Mail Campaigns too with our Elite package.. </div>
                  <a  class="elite-package" target="_blank" href="http://bit.ly/2JuKSrY">Elite Package</a>
                </div>
                <?php } ?>

              </div>
						</div>
            <div class="clear"></div>

					</div><!-- / .block -->

				</div><!-- / .upgrade-cta -->

        <div class="block" style="padding-bottom:0;">

						<h1>Testimonials</h1>

						<div class="testimonial-block">
							<img src="<?php echo plugins_url('/i/mb.jpg', ZBS_ROOTFILE); ?>">
							<p>My mind is blown away by how much attention has been placed on all the essential details built into Jetpack CRM. It's a polished, professional product that I love being able to bake into my Website as a Service (WaaS), multisite network. It adds true value for my customers and completes my product offering. I've not been able to find any tool quite like it (and trust me, I've looked!) If you're looking to offer true value to your customers, this is worth its weight in gold! </p>
              <p class='who'><strong>Michal Short</strong>
            </div>

						<div class="testimonial-block">
							<img src="<?php echo plugins_url('/i/scribner.png', ZBS_ROOTFILE); ?>">
							<p>We can sit back and relax safe in the knowledge that Jetpack CRM is working tirelessly behind the scenes distributing leads automatically to our clients.</p>
              <p class='who'><strong>Dave Scribner</strong> 
          	</div>

				</div><!-- / .block -->

      </div><!-- / .intro.zbs-features -->

    </div><!-- / .container -->

    <div class="container final-block">
      <div class="block">
        <div class='zbs-button-wrap'>

            <a href="<?php echo $zbs->urls['upgrade']; ?>" target="_blank" class='upgrade-today btn btn-bta'><?php _e("Upgrade your CRM today","zero-bs-crm");?></a>
          
          <div class="clear"></div>
        </div>  
      </div>
    </div>

    <?php } else {

      // bundle owners:
      
      ?>
      </div><!-- / .intro.zbs-features -->

    </div><!-- / .container -->

    <div class="container final-block">
        <div class="block">
          <div class='zbs-button-wrap' style="padding-bottom:2em">

              <h4><?php _e('Your Account:','zero-bs-crm'); ?></h4>

              <a href="<?php echo zbsLink($zbs->slugs['extensions']); ?>" class='btn btn-bta'><?php _e("Manage Extensions","zero-bs-crm");?></a>
              <a href="<?php echo $zbs->urls['account']; ?>" target="_blank" class='btn btn-cta'><?php _e("Download Extensions","zero-bs-crm");?></a>
            
            <div class="clear"></div>
          </div>  
        </div>
      </div><?php

    } ?>

  </div><!-- / zbs-welcome -->

  <?php
}

#} DataTools HTML
#} Only exposed when a data tools plugin is installed:
#} - CSV Importer
function zeroBSCRM_html_datatools(){
  
  global $wpdb, $zbs;  #} Req 
  
  $deleting_data = false;

  if(current_user_can('manage_options')){

      // DELETE ALL DATA (Not Settings)
      if ( isset($_POST['zbs-delete-data']) && $_POST['zbs-delete-data'] == 'DO IT'){  
            $link = admin_url('admin.php?page=' . $zbs->slugs['datatools']);
            $str =  __("REMOVE ALL DATA", "zero-bs-crm");
            echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";

            echo "<h3>" . __('Delete all CRM data', 'zero-bs-crm') . "</h3>";

            echo "<div style='font-size:60px;margin:0.5em;'>⚠️</div>";
            echo "<p class='lead' style='font-size:16px;color:#999;padding-top:15px;'>";

              _e("This Administrator level utility will remove all data in your CRM. This cannot be undone. Proceed with caution.", "zero-bs-crm"); 

            echo "</p>";

            // 
            $del_link   =  $link . '&zbs-delete-data=1';
            $action = 'zbs_delete_data';
            $name   = 'zbs_delete_nonce';

            $nonce_del_link = wp_nonce_url( $del_link, $action, $name );
            echo "<a class='ui button red' href='" . $nonce_del_link ."'>" . $str . "</a>";

            echo "<a class='ui button green inverted' href='" . esc_url($link) . "'>" . __('CANCEL', 'zero-bs-crm') . "</a>";
            echo "</div>";
            $deleting_data = true;

      } else if ( isset( $_GET['zbs-delete-data'] ) && $_GET['zbs-delete-data'] == 1){

              //additional nonce check
              if (!isset($_GET['zbs_delete_nonce']) || !wp_verify_nonce($_GET['zbs_delete_nonce'], 'zbs_delete_data')) {

                   echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message red' style='margin-right:20px;font-size:20px;'><i class='ui icon'></i>" . __("Data not deleted. Invalid permissions", "zero-bs-crm") . "</div>";
                echo "</div>";             


              }else{
                 echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message green' style='margin-right:20px;font-size:20px;'><i class='ui icon check circle'></i>" . __("All CRM data deleted.", "zero-bs-crm") . "</div>";
                echo "</div>";                             

                //run the delete code
                zeroBSCRM_database_reset();


              }

       
      } 

      // DELETE ALL DATA (INCLUDING Settings)
      if ( isset($_POST['zbs-delete-all-data']) && $_POST['zbs-delete-all-data'] == 'FACTORY RESET'){  

            $link = admin_url('admin.php?page=' . $zbs->slugs['datatools']);
            $str =  __("REMOVE ALL DATA", "zero-bs-crm");
            echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";

            echo "<h3>" . __('Factory Reset CRM', 'zero-bs-crm') . "</h3>";

            echo "<div style='font-size:60px;margin:0.5em'>⚠️</div>";
            echo "<p class='lead' style='font-size:16px;color:#999;padding-top:15px;'>";

              _e("This Administrator level utility will remove all data in your CRM, including your CRM settings. This cannot be undone. Proceed with caution.", "zero-bs-crm"); 

            echo "</p>";

            // 
            $del_link   =  $link . '&zbs-delete-all-data=1';
            $action = 'zbs_delete_data';
            $name   = 'zbs_delete_nonce';

            $nonce_del_link = wp_nonce_url( $del_link, $action, $name );
            echo "<a class='ui button red' href='" . $nonce_del_link ."'>" . $str . "</a>";

            echo "<a class='ui button green inverted' href='" . esc_url($link) . "'>" . __('CANCEL', 'zero-bs-crm') . "</a>";
            echo "</div>";
            $deleting_data = true;

      } else if ( isset( $_GET['zbs-delete-all-data'] ) && $_GET['zbs-delete-all-data'] == 1){

              // additional nonce check
              if (!isset($_GET['zbs_delete_nonce']) || !wp_verify_nonce($_GET['zbs_delete_nonce'], 'zbs_delete_data')) {

                   echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message red' style='margin-right:20px;font-size:20px;'><i class='ui icon'></i>" . __("Data not deleted. Invalid permissions", "zero-bs-crm") . "</div>";
                echo "</div>";             


              } else {
                 echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message green' style='margin-right:20px;font-size:20px;'><i class='ui icon check circle'></i>" . __("CRM Factory Reset", "zero-bs-crm") . "</div>";
                echo "</div>";                             

                // run the delete code
                /*
                       ___________________    . , ; .
                      (___________________|~~~~~X.;' .
                                            ' `" ' `
                                  TNT

                */
                zeroBSCRM_database_nuke();

              }

       
      } 

  }

  if ( !$deleting_data ){ ?>
            
        <div id="zero-bs-tools" class="ui segment" style="margin-right:20px;">
          <h2 class="sbhomep"><?php _e("Welcome to","zero-bs-crm");?> Jetpack CRM <?php _e("Tools","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("This is the home for all of the different admin tools for Jetpack CRM which import data (Excluding the","zero-bs-crm");?> <strong><?php _e("Sync","zero-bs-crm");?></strong> <?php _e("Extensions","zero-bs-crm");?>).</p>
          <p class="sbhomep">
            <strong><?php _e("Free Data Tools","zero-bs-crm");?>:</strong><br />
        <?php if(!zeroBSCRM_isExtensionInstalled('csvpro')){ ?>
           <a class="ui button green primary" href="<?php echo admin_url('admin.php?page='.$zbs->slugs['csvlite']);?>"><?php _e("Import from CSV","zero-bs-crm");?></a>
        <?php } ;?>
      </p>
          <p class="sbhomep">
            <strong><?php _e("Data Tool Extensions Installed","zero-bs-crm");?>:</strong><br /><br />
              <?php 

                #} MVP
                $zbsDataToolsInstalled = 0; global $zeroBSCRM_CSVImporterslugs;
                if (zeroBSCRM_isExtensionInstalled('csvpro') && isset($zeroBSCRM_CSVImporterslugs)){

                  ?><button type="button" class="ui button primary" onclick="javascript:window.location='?page=<?php echo $zeroBSCRM_CSVImporterslugs['app']; ?>';" class="ui button primary" style="padding: 7px 16px;font-size: 16px;height: 46px;margin-bottom:8px;"><?php _e('CSV Importer',"zero-bs-crm"); ?></button><br /><?php
                  // tagger post v1.1 
                  if (isset($zeroBSCRM_CSVImporterslugs['tagger'])) { 
                    ?><button type="button" class="ui button primary" onclick="javascript:window.location='?page=<?php echo $zeroBSCRM_CSVImporterslugs['tagger']; ?>';" class="ui button primary" style="padding: 7px 16px;font-size: 16px;height: 46px;margin-bottom:8px;"><?php _e('CSV Tagger',"zero-bs-crm"); ?></button><br /><?php
                  }
                  $zbsDataToolsInstalled++;

                }

                if ($zbsDataToolsInstalled == 0){
                  ##WLREMOVE
                  ?><?php _e("You do not have any Pro Data Tools installed as of yet","zero-bs-crm");?>! <a href="<?php echo $zbs->urls['productsdatatools']; ?>" target="_blank"><?php _e("Get some now","zero-bs-crm");?></a><?php
                  ##/WLREMOVE
                }

              ?>              
            </p><p class="sbhomep">
              <!-- #datatoolsales -->
            <strong><?php _e("Import Tools","zero-bs-crm");?>:</strong><br /><br />             
              <a href="<?php echo $zbs->urls['productsdatatools']; ?>" target="_blank" class="ui button primary"><?php _e('View Available Import Tools',"zero-bs-crm"); ?></a>              
            </p>
            <div class="sbhomep">
              <strong><?php _e("Export Tools","zero-bs-crm");?>:</strong><br/>
              <p><?php _e('Want to use the refined object exporter? ',"zero-bs-crm"); ?></p>
              <p><a class="ui button" href="<?php echo admin_url('admin.php?page='.$zbs->slugs['zbs-export-tools'].'&zbswhat=contacts'); ?>">Export Tools</a></p>
            </div>
    </div>
    <div class="ui grid">
    <div class="eight wide column">
      
        <div class="ui segment" style="margin-right:20px;">
          <div class='mass-delete' style="text-align:center;">
              <h4 style="font-weight:900;"><?php _e("Delete CRM Data", "zero-bs-crm"); ?></h4>
              <p>
                <?php $str = __("To remove all CRM data (e.g. contacts, transactions etc.), type", "zero-bs-crm") . " 'DO IT' " . __(" in the box below and click 'Delete All Data'.", "zero-bs-crm"); ?>
                <?php echo $str; ?>
              </p>
              <div class="zbs-delete-box" style="max-width:70%;margin:auto;">
                <p class='ui message warning'>
                    <i class='ui icon exclamation'></i><b> <?php _e("Warning: This can not be undone", "zero-bs-crm"); ?></b>
                </p>
                <form id="reset-data" class="ui form" action="#" method="POST">
                  <input class="form-control" id="zbs-delete-data" name="zbs-delete-data" type="text" value="" placeholder="DO IT" style="text-align:center;font-size:25px;"/>
                  <input type="submit" class="ui button red" value="<?php _e("DELETE ALL DATA","zero-bs-crm") ;?>" style="margin-top:10px;"/>
                </form>
              </div>            
          </div>
        </div>

    </div>
    <div class="eight wide column">
      
        <div class="ui segment" style="margin-right:20px;">
          <div class='mass-delete' style="text-align:center;">
              <h4 style="font-weight:900;"><?php _e("Factory Reset CRM", "zero-bs-crm"); ?></h4>
              <p>
                <?php $str = __("To delete CRM data and all settings, type", "zero-bs-crm") . " 'FACTORY RESET' " . __(" in the box below and click 'Reset CRM'.", "zero-bs-crm"); ?>
                <?php echo $str; ?>
              </p>
              <div class="zbs-delete-box" style="max-width:70%;margin:auto;">
                <p class='ui message warning'>
                    <i class='ui icon exclamation'></i><b> <?php _e("Warning: This can not be undone", "zero-bs-crm"); ?></b>
                </p>
                <form id="reset-data" class="ui form" action="#" method="POST">
                  <input class="form-control" id="zbs-delete-all-data" name="zbs-delete-all-data" type="text" value="" placeholder="FACTORY RESET" style="text-align:center;font-size:25px;"/>
                  <input type="submit" class="ui button red" value="<?php _e("Reset CRM","zero-bs-crm") ;?>" style="margin-top:10px;"/>
                </form>
              </div>          
          </div>
        </div>

    </div>

    
    
    
    <?php 

              }

}


#} Install Extensions helper page
function zeroBSCRM_html_installextensionshelper(){
  
  global $wpdb, $zbs;  #} Req 
  
  #} 27th Feb 2019 - MS pimp this page a little - but WL remove the salesy bit. bring into semantic UI properly too
  ?>
          <style>
            .intro{
              font-size:18px !important;;
              font-weight:200;
              line-height:20px;
              margin-bottom:10px;
              margin-top:20px;
            }
            .zbs-admin-segment-center{
              text-align:center;
            }
            h2{
              font-weight:900;
              padding-bottom:30px;
            }
            .intro-buttons{
              padding:20px;
            }
          </style>
          <div class="ui segment zbs-admin-segment-center" style="margin-right:15px;">
  <?php
          ##WLREMOVE
            zeroBSCRM_extension_installer_promo();
          ##/WLREMOVE    
  ?>
          <h2><?php _e("Installing Extensions for","zero-bs-crm");?> Jetpack CRM</h2>
          <p class="intro"><?php _e("To control which modules and extensions are active, please go the the ","zero-bs-crm");?> <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['extensions']); ?>"><?php _e("Extension Manager","zero-bs-crm");?></a>.</p>
          <p class="intro"><?php _e("To install premium extensions, purchased in a bundle or individually please go to","zero-bs-crm");?> <a href="<?php echo admin_url('plugins.php'); ?>"><?php _e("Plugins","zero-bs-crm");?></a> <?php _e("and add your new extensions there.","zero-bs-crm");?></p>
          <p class="intro-buttons">
            <a href="<?php echo admin_url('plugins.php'); ?>" class="ui button primary"><i class="fa fa-plug" aria-hidden="true"></i> <?php _e("Upload Purchased Extensions","zero-bs-crm");?></a>    
            <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['extensions']); ?>" class="ui button green"><i class="fa fa-search" aria-hidden="true"></i> <?php _e("Browse Extensions and Modules","zero-bs-crm");?></a>    
            </p>
    </div>
    
  <?php 

}

function zeroBSCRM_extension_installer_promo(){
  //extra function here to output additional bullie type stuff.
  ?>
  <div class="bullie">
    <?php $bullie = zeroBSCRM_getBullie(); ?>
    <img src="<?php echo $bullie; ?>" alt="Jetpack CRMt">
  </div>
  <?php
}

#} Feedback page
function zeroBSCRM_html_feedback(){
  
  global $wpdb, $zbs;  #} Req

    if (zeroBSCRM_isWL()){  #WL - leave old ?>
        <div id="sbSubPage" style="width:600px"><h2 class="sbhomep"><?php _e("Feedback Makes the CRM better","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("We love to hear what you think of our CRM! Your feedback helps us make the CRM even better, even if you're hitting a wall with something, (it's useful so long as it's constructive critisism!)","zero-bs-crm");?></p>
          <p class="sbhomep"><?php _e("If you have a feature you'd like to see, a bug you may have found, or you'd like to suggest an idea for an extension, let us know below:","zero-bs-crm");?></p>
          <p class="sbhomep">
            <a href="<?php echo $zbs->urls['feedback']; ?>" class="ui button primary"><i class="fa fa-envelope-o" aria-hidden="true"></i> <?php _e("Send Feedback","zero-bs-crm");?></a>    
          </p>
    <?php } else { #ZBS ?>
        <div id="sbSubPage" style="width:800px"><h2 class="sbhomep"><?php _e("Feedback Makes Jetpack CRM better","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("We love to hear what you think of Jetpack CRM! Your feedback helps us make the CRM even better, even if you're hitting a wall with something, (it's useful if it's constructive critisism!)","zero-bs-crm");?></p>
          <p class="sbhomep"><a class="ui button green info" href="<?php echo $zbs->urls['feedbackform']; ?>" target="_blank"><?php _e('Send us Feedback',"zero-bs-crm"); ?></a></p>
    <?php } ?>
          <?php ##WLREMOVE ?>
              <p class="sbhomep"><?php _e("What not to send through here:","zero-bs-crm");?>
              <ul class="sbhomep">
                <li><?php _e("Documentation requests","zero-bs-crm");?> (<a href="<?php echo $zbs->urls['docs']; ?>"><?php _e("Click here","zero-bs-crm");?></a> <?php _e("for that","zero-bs-crm");?>)</li>
                <li><?php _e("Support requests","zero-bs-crm");?> (<a href="<?php echo $zbs->urls['feedback']; ?>"><?php _e("Click here","zero-bs-crm");?></a> <?php _e("for that","zero-bs-crm");?>)</li>
              </ul>
              </p>
          <?php ##/WLREMOVE ?>
    </div><?php 

}

function zeroBSCRM_html_extensions_forWelcomeWizard(){

  global $wpdb, $zbs;  #} Req ?>



            
        <div id="sbSubPage" style="width:100%;max-width:1000px"><h2 class="sbhomep"><?php _e("Power Up your CRM","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("We hope that you love using ZBS and that you agree with our mentality of stripping out useless features and keeping things simple. Cool.","zero-bs-crm");?></p>
          <p class="sbhomep"><?php _e("We offer a few extensions which supercharge your ZBS. As is our principle, though, you wont find any bloated products here. These are simple, effective power ups for your ZBS. And compared to pay-monthly costs, they're affordable! Win!","zero-bs-crm");?></p>
          <div style="width:100%"><a href="<?php echo $zbs->urls['products']; ?>" target="_blank"><img style="width:100%;max-width:100%;margin-left:auto;margin-right:auto;" src="<?php echo $zbs->urls['extimgrepo'].'extensions.png'; ?>" alt="" /></a></div>
            <p class="sbhomep">
            <a href="<?php echo $zbs->urls['products']; ?>" class="ui button primary" style="padding: 7px 16px;font-size: 22px;height: 46px;" target="_blank"><?php _e("View More","zero-bs-crm");?></a>    
          </p>
    </div><?php 
  

}

#} helper for extension page (installs/uninstalls at init)
function zeroBSCRM_extensions_init_install(){

    #} Anything to install/uninstall?
    if ( isset( $_GET['zbsinstall'] ) && ! empty( $_GET['zbsinstall'] ) ) {

      #} this is passed to extensions page
      global $zeroBSExtensionMsgs;


      #} Validate
      global $zeroBSCRM_extensionsCompleteList;

      if (
        wp_verify_nonce( $_GET['_wpnonce'], 'zbscrminstallnonce' )
        &&
        #} Ext exists
        array_key_exists( $_GET['zbsinstall'], $zeroBSCRM_extensionsCompleteList ) ) {

        // Extension data
        $toActOn = sanitize_text_field($_GET['zbsinstall']);
        $installingExt = zeroBSCRM_returnExtensionDetails($toActOn);
        $installName = 'Unknown'; if (isset($installingExt['name'])) $installName = $installingExt['name'];

        // Action
        if ( zeroBSCRM_isExtensionInstalled( $toActOn ) ) {
            $act = 'uninstall';
        } else {
            $act = 'install';
        }

        $successfullyInstalled = false;

        #} Try it
        try {

          if ($act == 'install'){

            #} INSTALL

            #} If install func exists
            if (function_exists('zeroBSCRM_extension_install_'.$toActOn)){

              #} try it (returns bool)
              $successfullyInstalled = call_user_func('zeroBSCRM_extension_install_'.$toActOn);

            }

          } else {

            #} UNINSTALL

            #} If install func exists
            if (function_exists('zeroBSCRM_extension_uninstall_'.$toActOn)){

              #} try it (returns bool)
              $successfullyInstalled = call_user_func('zeroBSCRM_extension_uninstall_'.$toActOn);

            }

          }

        } catch (Exception $ex){

          # meh

        }

        #} pass it on
        $zeroBSExtensionMsgs = array($successfullyInstalled,$installName,$act);


      }
    }
}




function zeroBSCRM_html_extensions(){
  
  //globals 
  global $zbs, $zeroBSExtensionMsgs, $zeroBSCRM_extensionsInstalledList;

  // new design - for the fact we are adding new extensions all the time and now won't need to
  // keep on remembering to update this array and it will keep up to date. Also with things 
  // like livestorm "connect" needed an on the flyfix.

  // WH added: Is now polite to License-key based settings like 'entrepreneur' doesn't try and upsell
  // this might be a bit easy to "hack out" hmmmm
  $bundle = false; if ($zbs->hasEntrepreneurBundleMin()) $bundle = true;


  echo "<div class='zbs-extensions-manager' style='margin-top:1em'>";

  #} Install msg
  if (isset($zeroBSExtensionMsgs)){

    echo '<div class="zbs-page-wrap install-message-list">';

    if ($zeroBSExtensionMsgs[0]){

      $msgHTML = '<i class="fa fa-check" aria-hidden="true"></i> Successfully '.$zeroBSExtensionMsgs[2].'ed extension: '.$zeroBSExtensionMsgs[1];

      // if API, catch and give further info (e.g. no key)
      if ($zeroBSExtensionMsgs[2] == 'install' && $zeroBSExtensionMsgs[1] == 'API'){

          // installed API
            // get if set
            $api_key = zeroBSCRM_getAPIKey();
            $api_secret = zeroBSCRM_getAPISecret();
            //$endpoint_url = zeroBSCRM_getAPIEndpoint(); 
            if (empty($api_key)){

              // assume no keys yet, tell em
              $msgHTML .= '<hr />'.__('You can now generate API Keys and send data into your CRM via API:','zero-bs-crm').'<p style="padding:1em"><a href="'.zbsLink($zbs->slugs['settings']).'&tab=api" class="ui button green">'.__('Generate API Keys','zero-bs-crm').'</a></p>';

            }


      }

      #} Show a help url if present
      if ($zeroBSExtensionMsgs[2] == 'install' && isset($installingExt) && isset($installingExt['meta']) && isset($installingExt['meta']['helpurl']) && !empty($installingExt['meta']['helpurl'])){

        $msgHTML .= '<br /><i class="fa fa-info-circle" aria-hidden="true"></i> <a href="'.$installingExt['meta']['helpurl'].'" target="_blank">View '.$zeroBSExtensionMsgs[1].' Help Documentation</a>';

      }
        
      echo zeroBSCRM_html_msg(0,$msgHTML);

    } else {

      global $zbsExtensionInstallError, $zbs;

      $errmsg = 'Unable to install extension: '.$zeroBSExtensionMsgs[1].', please contact <a href="'.$zbs->urls['support'].'" target="_blank">Support</a> if this persists.';

      if (isset($zbsExtensionInstallError)) $errmsg .= '<br />Installer Error: '.$zbsExtensionInstallError;


      echo zeroBSCRM_html_msg(-1,$errmsg);

    }

    echo '</div>';

  }

  //get the products, from our sites JSON custom REST endpoint - that way only need to manage there and not remember to update all the time
  //each product has our extkey so can do the same as the built in array here ;) #progress #woop-da-woop
  if(isset($_GET['extension_id']) && !empty($_GET['extension_id'])){
    ##WLREMOVE
          echo '<div class="zbs-page-wrap thinner" id="error-stuff">';
            $id = (int)sanitize_text_field($_GET['extension_id']);
            $request = wp_safe_remote_get( 'https://jetpackcrm.com/wp-json/zbsextensions/v1/extensions/' . $id );

            if ( is_wp_error( $request ) ) {

            echo '<div class="zbs-page-wrap">';
                echo '<div class="ui message alert warning" style="display:block;margin-bottom: -25px;"><i class="wifi icon"></i> ';
                  _e("You must be connected to the internet to view our live extensions page.", "zero-bs-crm");
              echo '</div>';
            echo '</div>';

              return false;
            } 

            $body = wp_remote_retrieve_body( $request );
            $extension = json_decode( $body );
            $info = $extension->product;

            if($info == 'error'){
                echo '<div class="zbs-page-wrap">';
                  echo '<div class="ui message alert error" style="display:block;margin-bottom: -25px;"><i class="exclamation icon"></i> ';
                    _e("Product does not exist.", "zero-bs-crm");
                    echo ' <a href="'   .  esc_url(admin_url('admin.php?page=' . $zbs->slugs['extensions']))   .  '">' . __('Go Back', 'zero-bs-crm') . '</a>';
                echo '</div>';
              echo '</div>';
              return false;
            }
          echo '</div>';  
          //end of #error-stuff

          echo '<div class="zbs-page-wrap thinner single-info-start">';
          
            echo '<div class="ui segment main-header-img">';
              echo '<div class="back">';
                echo '<a href="'   .  esc_url(admin_url('admin.php?page=' . $zbs->slugs['extensions']))   .  '"><i class="chevron left icon"></i> ' . __('Back', 'zero-bs-crm') . '</a>';
              echo '</div>';

              echo '<div class="main-image full-size-image">';
                echo '<img src="' . $info->image .  '"/>';
              echo '</div>';

              echo '<div class="below-main-image about-author-block">';
                  //start the about block
                  echo '<div class="about-img"><img src="' . $info->by . '"/></a>';
                    echo '<div class="top-info-block">';
                    echo '<h4 class="extension-name">' . $info->name . '</h4>';
                    echo '<div class="who">'. __('by ', 'zero-bs-crm') .'<a class="by-url" href="' . $zbs->urls['home']   .  '" target="_blank">' . __('Jetpack CRM', 'zero-bs-crm') . '</a></div>';            
                    echo '</div>';
                  echo '</div>';
                  //end the about block

                  //action block (installed / not)
                  $extkey = $info->extkey;  
                  $sales_link = $zbs->urls['home']. "/product/" . $info->slug;
                  

                  $installed = zeroBSCRM_isExtensionInstalled($extkey);
                  $docs = $info->docs;
                  echo '<div class="actions-block"><div class="install-ext">';
                    if($installed){
                        echo '<span class="ui label green large"><i class="check circle icon"></i> ' . __('Installed', 'zero-bs-crm') . '</span>';
                    }else{
                        echo '<a href="'.esc_url($sales_link).'" class="ui blue button" target="_blank"><i class="cart icon"></i> ' . __('Buy', 'zero-bs-crm') . '</a>';
                    }
                    if($docs != ''){
                      echo '<a class="docs-url ui button" href="'. esc_url($docs) .'" target="_blank"><i class="book icon"></i>' . __('View Docs', 'zero-bs-crm') . '</a>';
                    }
                    echo '</div>';
                  echo '</div>'; 
                  //end action block
              echo '</div>';  
              //end the about-author-block
              
              echo '<div class="clear"></div>'; // clear stuff

            echo '</div>';  //end the whole header image block



          echo '</div>';  
          //end the start of the info block (top block)

          echo '<div class="zbs-page-wrap thinner single-bundle-wrap">';
            if(!$bundle){
              echo '<div class="bullie-wrap">';
                echo '<div class="bullie">';
                  $bullie = zeroBSCRM_getBullie(); 
                  echo '<img src="'. $bullie . '" alt="Jetpack CRMt">';
                  echo '<div class="upgrade">' . __('Upgrade to our bundle to get access to all CRM extensions', 'zero-bs-crm') . '</div>';
                  echo '<a class = "ui button green mini upgrade-bullie-box" href="https://jetpackcrm.com/extension-bundles/" target = "_blank"><i class="cart plus icon"></i> ' . __('Upgrade Now', 'zero-bs-crm') . '</a>';
                echo '</div>';
              echo '</div>';
              echo '<div class="clear"></div>';
            }  
          echo '</div>';

          echo '<div class="zbs-page-wrap thinner" id="single-ext-desc">';
            echo '<div class="ui segment main-talk">';
              echo '<div class="extension-description">';

                  // semantic ui switch html from bootstrap ones (grids basically)
                  $desc = str_replace('class="row"', 'class="ui grid"', $info->description);
                  $desc = str_replace(' row"', ' ui grid"', $desc);
                  $desc = str_replace('col-md-6', 'eight wide column', $desc);
                  $desc = str_replace('col-sm-8', 'ten wide column', $desc);
                  $desc = str_replace('col-lg-1', '', $desc);
                  $desc = str_replace('col-lg-2', 'four wide column', $desc);

                  echo $desc;
              echo '</div>';
              // buy
              if(!$installed) echo '<hr /><div style="margin:2em;text-align:center"><a href="'.esc_url($sales_link).'" class = "ui large blue button" target="_blank"><i class="cart icon"></i> ' . __('Buy', 'zero-bs-crm') . ' ' . __('Extension', 'zero-bs-crm') . '</a></div>';                    
            echo '</div>';
          echo "</div>"; 
          //id="single-ext-desc"

    ##/WLREMOVE
  }else{

        ##WLREMOVE
          $showLinkButton = true;

          //get the JSON response from woocommerce REST endpoint.
          $request = wp_safe_remote_get( $zbs->urls['checkoutapi'] );
          if ( is_wp_error( $request ) ) {
              //if there's an error, server the JSON in the function 
            $extensions = json_decode(zeroBSCRM_serve_cached_extension_block());
            echo '<div class="zbs-page-wrap">';
                echo '<div class="ui message alert warning" style="display:block;margin-bottom: -25px;"><i class="wifi icon"></i> ';
                  _e("You must be connected to the internet to view our live extensions page. You are being shown an offline version.", "zero-bs-crm");
              echo '</div>';
            echo '</div>';
            $showLinkButton = false;
          }else{
            $body = wp_remote_retrieve_body( $request );
            $extensions = json_decode( $body );
          }

          // if we somehow still haven't got actual obj, use cached:
          // .. This was happening when our mainsite json endpoint is down
          if (!is_array($extensions->paid)) $extensions = json_decode(zeroBSCRM_serve_cached_extension_block());

          echo '<div class="zbs-page-wrap">';
            if(!$bundle){
            echo '<div class="bullie-wrap">';
              echo '<div class="bullie">';
                $bullie = zeroBSCRM_getBullie(); 
                echo '<img src="'. $bullie . '" alt="Jetpack CRMt" style="width:150px;padding:10px;height:auto;gi">';
                echo '<div class="upgrade">' . __('Upgrade to our Entrepreneur Bundle or higher to get access to all CRM extensions and save.', 'zero-bs-crm') . '</div>';
                echo '<a class = "ui button green mini upgrade-bullie-box" href="https://jetpackcrm.com/extension-bundles/" target = "_blank"><i class="cart plus icon"></i> ' . __('Upgrade Now', 'zero-bs-crm') . '</a>';
              echo '</div>';
            echo '</div>';
            echo '<div class="clear"></div>';
            }  
            echo '<div class="ui top attached header premium-box"><h3 class="box-title">' . __('Premium Extensions', 'zero-bs-crm').'</h3>   <a class="guides ui button blue mini" href="'.  $zbs->urls['docs']  .'" target="_blank"><i class="book icon"></i> '. __('Knowledge-base', 'zero-bs-crm')  .'</a> <a class="guides ui button blue basic mini" href="#core-modules"><i class="puzzle piece icon"></i> '. __('Core Extensions', 'zero-bs-crm')  .'</a>   </div>';
            echo '<div class="clear"></div>';
            echo '<div class="ui segment attached">';
              echo '<div class="ui internally celled grid">';

              $e = 0; $count = 0; $idsToHide = array(17121,17119);
              if (is_array($extensions->paid)) foreach($extensions->paid as $extension){

                // hide bundles
                if (!in_array($extension->id, $idsToHide)){

                    $more_url = admin_url('admin.php?page=' . $zbs->slugs['extensions'] . '&extension_id=' . $extension->id);

                    $extkey = $extension->extkey;
                    $installed = zeroBSCRM_isExtensionInstalled($extkey);
                    if($e == 0){
                      echo '<div class="row">';
                    }

                    echo "<div class='two wide column'>";
                      echo "<img src='" . $extension->image  ."'/>";
                    echo "</div>";

                    echo "<div class='six wide column ext-desc'>";
                      if($installed){
                        echo '<div class="ui green right corner label"><i class="check icon"></i></div>';
                      }
                      echo "<div class='title'>" . $extension->name . '</div>';
                      echo "<div class='content'>" . $extension->short_desc  . '</div>';

                      if($showLinkButton){
                        echo '<div class="hover"></div><div class="hover-link">';


                        $sales_link = $zbs->urls['home']. "/product/" . $extension->slug;
                      

                        // api connector skips these
                        if ($extkey == 'apiconnector'){

                            // api connector
                            
                              // view
                              echo "<a href='". esc_url($zbs->urls['apiconnectorsales'])  ."' target='_blank'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";                      
                              
                              // download or buy
                              if ($bundle)
                                echo "<a href='". esc_url($zbs->urls['account'])  ."' target='_blank'><button class='ui button green mini'>" . __('Download', 'zero-bs-crm') . "</button></a>";
                              else
                                echo "<a href='". esc_url($sales_link)  ."' target='_blank'><button class='ui button green mini'>" . __('Buy', 'zero-bs-crm') . "</button></a>";

                          } else {

                            // non api connector
                            echo "<a href='". esc_url($more_url)  ."'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";
                            
                            if (!$installed){
                              
                              if ($bundle)
                                echo "<a href='". esc_url($zbs->urls['account'])  ."' target='_blank'><button class='ui button green mini'>" . __('Download', 'zero-bs-crm') . "</button></a>";
                              else
                                echo "<a href='". esc_url($sales_link)  ."' target='_blank'><button class='ui button green mini'>" . __('Buy', 'zero-bs-crm') . "</button></a>";

                            } else
                              if (isset($extension->docs) && !empty($extension->docs)) echo "<a href='". esc_url($extension->docs)  ."' target='_blank'><button class='ui button blue mini'>" . __('Docs', 'zero-bs-crm') . "</button></a>";
                          }
                        echo '</div>';
                      }

                    echo "</div>";

              
                    $e++;
                    $count++;
                    if($e > 1){
                      echo '</div>';
                      $e = 0;
                    }


                  } // / if not hidde

              }

                //add on the coming soon block     
                if ($e == 1){

                    // End of row

                      echo "<div class='two wide column'>";
                        echo "<img src='" . plugins_url('i/soon.png', ZBS_ROOTFILE)  ."'/>";
                      echo "</div>";

                      echo "<div class='six wide column ext-desc'>";
                        echo "<div class='title'>" . __('Coming soon', 'zero-bs-crm'). '</div>';
                        echo "<div class='content'>" . __('See and vote for what extensions we release next')  . '</div>';
                  
                        echo '<div class="hover"></div>';
                        echo "<a class='hover-link' href='". esc_url($zbs->urls['soon'])  ."' target='_blank'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";
                      echo "</div>";

                } else {

                  // Row to itself

                    echo '<div class="row">';

                    echo "<div class='two wide column'>";
                      echo "<img src='" . plugins_url('i/soon.png' , ZBS_ROOTFILE)  ."'/>";
                    echo "</div>";

                    echo "<div class='six wide column ext-desc'>";;
                      echo "<div class='title'>" . __('Coming soon', 'zero-bs-crm'). '</div>';
                      echo "<div class='content'>" . __('See and vote for what extensions we release next')  . '</div>';
              
                      echo '<div class="hover"></div>';
                      echo "<a class='hover-link' href='". esc_url($zbs->urls['soon'])  ."' target='_blank'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";
                    echo "</div>";

                }

                // coming soon end row
                echo '</div>'; //end the row (as it will be adding on)

              echo '</div>';
            echo '</div>';
          echo '</div>';  //end page wrap.

        ##/WLREMOVE

        //this block should be in here for rebranded people who want to turn on or off features.
        echo '<div class="zbs-page-wrap free-block-wrap">';
          echo '<h3 class="ui top attached header free-box" id="core-modules">' . __('Core Extensions', 'zero-bs-crm').'</h3>';
          echo '<div class="ui segment attached free-ext-area">';
            echo '<div class="ui internally celled grid">';
              
              //output the free stuff :-) with turn on / off.
              $e = 0;
              foreach(zeroBSCRM_extensions_free() as $k => $v){

                if (is_array($v)){

                    $modify_url = wp_nonce_url('admin.php?page='.$zbs->slugs['extensions'].'&zbsinstall='.$k,'zbscrminstallnonce');

                    $installed = zeroBSCRM_isExtensionInstalled($k);
                    if($e == 0){
                      echo '<div class="row">';
                    }

                    echo "<div class='two wide column free-ext-img'>";
                      echo "<img src='" .  plugins_url('i/' . $v['i'], ZBS_ROOTFILE)  ."'/>";
                    echo "</div>";

                    echo "<div class='six wide column ext-desc'>";
                      $amend = __('Activate', 'zero-bs-crm');
                      $amend_color = 'green';
                      if($installed){
                        echo '<div class="ui green right corner label"><i class="check icon"></i></div>';
                        $amend = __('Deactivate', 'zero-bs-crm');
                        $amend_color = 'red';
                      }else{
                        echo '<div class="ui red right corner label"><i class="times icon"></i></div>';                  
                      }
                      echo "<div class='title'>" . $v['name'] . '</div>';
                      echo "<div class='content'>" . $v['short_desc']  . '</div>';

                      echo '<div class="hover"></div>';
                      echo "<a class='hover-link' href='". esc_url($modify_url)  ."'><button class='ui button ". $amend_color  ." mini'>" . $amend . "</button></a>";



                    echo "</div>";

                    $e++;
                    if($e > 1){
                      echo '</div>';
                      $e = 0;
                    }


                  } // / if is array (csvimporterlite = false so won't show here)


              } // /foreach 
          
            echo '</div>';
          echo '</div>';
        echo '</div>'; 



  }  


  echo "</div>";

}



#} post-deletion page
function zeroBSCRM_html_deletion(){

  global $wpdb, $zbs;  #} Req

  #} Discern type of deletion:
  $delType = '?'; # Customer
  $delStr = '?'; # Mary Jones ID 123
  $delID = -1;
  $delIDVar = '';
  $isRestore = false;
  $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';

  #} Perhaps this needs nonce?
  if (isset($_GET['restoreplz']) && $_GET['restoreplz'] == 'kthx') $isRestore = true;

    #} Discern type
    if (isset($_GET['cid']) && !empty($_GET['cid'])){

      $delID = (int)sanitize_text_field($_GET['cid']);
      $delIDVar = 'cid';
      $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';

      #} Fill out
      $delType = __('Contact','zero-bs-crm');
      $delStr = zeroBS_getCustomerName($delID);

    } else if (isset($_GET['qid']) && !empty($_GET['qid'])){

      #} Quote
      $delID = (int)sanitize_text_field($_GET['qid']);
      $delIDVar = 'qid';
      $backToPage = 'edit.php?post_type=zerobs_quote&page=manage-quotes';

      #} Fill out
      $delType = 'Quote';
      $delStr = 'Quote ID: '.$delID; # TODO - these probably need offset

    } else if (isset($_GET['qtid']) && !empty($_GET['qtid'])){

      #} Quote
      $delID = (int)sanitize_text_field($_GET['qtid']);
      $delIDVar = 'qtid';
      $backToPage = 'edit.php?post_type=zerobs_quote&page=manage-quote-templates';

      #} Fill out
      $delType = 'Quote Template';
      $delStr = 'Quote Template ID: '.$delID; # TODO - these probably need offset

    } else if (isset($_GET['iid']) && !empty($_GET['iid'])){

      #} Invoice
      $delID = (int)sanitize_text_field($_GET['iid']);
      $delIDVar = 'iid';
      $backToPage = 'admin.php?page=manage-invoices';

      #} Fill out
      $delType = 'Invoice';
      $delStr = 'Invoice ID: '.$delID; # TODO - these probably need offset

    } else if (isset($_GET['tid']) && !empty($_GET['tid'])){

      #} Transaction
      $delID = (int)sanitize_text_field($_GET['tid']);
      $delIDVar = 'tid';
      $backToPage = 'edit.php?post_type=zerobs_transaction&page=manage-transactions';

      #} Fill out
      $delType = 'Transaction';
      $delStr = 'Transaction ID: '.$delID;

    } else if (isset($_GET['eid']) && !empty($_GET['eid'])){

      #} Transaction
      $delID = (int)sanitize_text_field($_GET['eid']);
      $delIDVar = 'eid';
      $backToPage = 'edit.php?post_type=zerobs_event&page=manage-events';

      #} Fill out
      $delType = __('Task',"zero-bs-crm");
      $delStr = 'Event ID: '.$delID;

    }

    $perm = 0;
    if (isset($_GET['perm']) && !empty($_GET['perm'])){

      // wh added - mediocre last min check :/
      if (zeroBSCRM_permsEvents()){

          //only for events for now
         if (isset($_GET['eid']) && !empty($_GET['eid'])){
          
            $perm = (int)sanitize_text_field($_GET['perm']);
              if($perm == 1){
                  wp_delete_post($delID);
              }
            }

          }

    }

    #} Actual restore
    if ($isRestore && !empty($delID)){

      wp_untrash_post($delID);

    }

  if($perm == 1){ ?>

    <div id="zbsDeletionPage">
      <div id="zbsDeletionMsgWrap">
        <div id="zbsDeletionIco"><i class="fa fa-trash" aria-hidden="true"></i></div>
        <div class="zbsDeletionMsg">
            <?php echo $delStr . __(' Successfully deleted', 'zero-bs-crm'); ?>
        </div>
        <div class="zbsDeletionAction">
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage); ?>'">Back to <?php echo $delType; ?>s</button>
        </div>
      </div>
    </div> 


  <?php

  }else{


  ?>
    <div id="zbsDeletionPage">
      <div id="zbsDeletionMsgWrap">
        <div id="zbsDeletionIco"><i class="fa <?php if ($isRestore){ ?>fa-undo<?php } else { ?>fa-trash<?php } ?>" aria-hidden="true"></i></div>
        <div class="zbsDeletionMsg"><?php echo $delStr; ?> <?php _e("successfully","zero-bs-crm");?> 
          <?php if ($isRestore){ ?>
          <?php _e("retrieved from Trash","zero-bs-crm");?>
          <?php } else { #trashed ?>
          <?php _e("moved to Trash","zero-bs-crm");?>
          <?php } ?>
        </div>
        <div class="zbsDeletionAction">
          <?php if ($isRestore){ ?>
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage); ?>'">Back to <?php echo $delType; ?>s</button>
          <?php } else { #trashed ?>
          <button type="button" class="ui button green" onclick="javascript:window.location='admin.php?page=zbs-deletion&<?php echo $delIDVar; ?>=<?php echo $delID; ?>&restoreplz=kthx'">Undo (Restore <?php echo $delType; ?>)</button>
          &nbsp;&nbsp;
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage ); ?>'">Back to <?php echo $delType; ?>s</button>
          <?php } ?>


          
          <?php 
          if (isset($_GET['eid']) && !empty($_GET['eid'])){
            //right now, we only ever "trash" things without the ability to fully delete...
            //WHLOOK - won't work with new DB2.0 data objects will need our own process for
            // 1.) Trash
            // 2.) Permanently Delete
            // Might already be there, but MS not familiar. Events currently in old DB1.0 layout.
            // to discuss.
            $delID = (int)sanitize_text_field($_GET['eid']);
            $delete_link = admin_url('admin.php?page=zbs-deletion&eid=' . $delID . '&perm=1');
            ?>
            <br/>
            <?php _e("or", "zero-bs-crm"); ?>
            <br/>
            <a href='<?php echo $delete_link; ?>'><?php _e("Delete Permanently", "zero-bs-crm"); ?></a>
            <?php
            //this allows me to hook in and say "deleting permanently also deletes the outlook event permanently"
            do_action('zbs-delete-event-permanently');
          }
          ?>

        </div>
      </div>
    </div>        
    <?php 

  }

}


#} post-deletion page
function zeroBSCRM_html_norights(){

  global $wpdb, $zbs;  #} Req

  #} Discern type of norights:
  $noaccessType = '?'; # Customer
  $noaccessstr = '?'; # Mary Jones ID 123
  $noaccessID = -1;
  $isRestore = false;
  $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';

  #} Discern type + set back to page
  $noAccessType = '';

    // DAL3 switch
    if ($zbs->isDAL3()){

      // DAL 3
      $objID = $zbs->zbsvar('zbsid'); // -1 or 123 ID
      $objTypeStr = $zbs->zbsvar('zbstype'); // -1 or 'contact'

      // if objtypestr is -1, assume contact (default)
      if ($objTypeStr == -1)
        $objType = ZBS_TYPE_CONTACT;
      else
        $objType = $this->DAL->objTypeID($objTypeStr);

      // if got type, link to list view
      // else give dash link
      $slugToSend = ''; $noAccessTypeStr = '';

        // back to page
        if ($objType > 0) $slugToSend = $zbs->DAL->listViewSlugFromObjID($objType);
        if (empty($slugToSend)) $slugToSend = $zbs->slugs['dash'];
        $backToPage = 'admin.php?page='.$slugToSend;

        // obj type str
        if ($objType > 0) $noAccessTypeStr = $zbs->DAL->typeStr($objType);
        if (empty($noAccessTypeStr)) $noAccessTypeStr = __('Object','zero-bs-crm');


    } else {

      // PRE DAL3:

          if (isset($_GET['post_type']) && !empty($_GET['post_type']))
            $noAccessType = $_GET['post_type'];
          else {
            if (isset( $_GET['id'] )) $noAccessType = get_post_type( $_GET['id'] );
          }

          switch ($noAccessType){

              case 'zerobs_customer':
                
                  $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';
                  $noAccessTypeStr = __('Contact',"zero-bs-crm");

                  break;

              case 'zerobs_company':
                
                  $backToPage = 'edit.php?post_type=zerobs_company&page=manage-companies';
                  $noAccessTypeStr = __(jpcrm_label_company(),"zero-bs-crm");

                  break;

              default:
                
                  // Dash
                  $backToPage = 'admin.php?page='.$zbs->slugs['dash'];
                  $noAccessTypeStr = __('Resource',"zero-bs-crm");

                  break;

          }

      }

  ?>
    <div id="zbsNoAccessPage">
      <div id="zbsNoAccessMsgWrap">
        <div id="zbsNoAccessIco"><i class="fa fa-archive" aria-hidden="true"></i></div>
        <div class="zbsNoAccessMsg">
          <h2><?php echo _e('Access Restricted'); ?></h2>
          <p><?php echo _e('You do not have access to this '.$noAccessTypeStr.'.'); ?></p>
        </div>
        <div class="zbsNoAccessAction">
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage ); ?>'"><?php _e("Back","zero-bs-crm");?></button>

        </div>
      </div>
    </div>        
    <?php 
 

}

function zeroBSCRM_html_systemstatus(){

  global $wpdb, $zbs;  #} Req

  $normalSystemStatusPage = true;

  // catch v3 migration notes
  if (isset($_GET['v3migrationlog'])){

    // kill normal page
    $normalSystemStatusPage = false;

    if ($zbs->isDAL3()){

        // check for any migration 'errors' + also expose here.
        $errors = get_option('zbs_db_migration_300_errstack', array());
          
        $bodyStr = '<h2>'.__('Migration Completion Report','zero-bs-crm').'</h2>';

        if (is_array($errors) && count($errors) > 0){
            
            // this is a clone of what gets sent to them by email, but reusing the html gen here

            // build report
            $bodyStr = '<h2>'.__('Migration Completion Report','zero-bs-crm').'</h2>';
            $bodyStr .= '<p style="font-size:1.3em">'.__('Unfortunately there were some migration errors, which are shown below. The error messages should explain any conflicts found when merging, (this has also been emailed to you for your records).','zero-bs-crm').' '.__('Please visit the migration support page','zero-bs-crm').' <a href="'.$zbs->urls['db3migrate'].'" target="_blank">'.__('here','zero-bs-crm').'</a> '.__('if you require any further information.','zero-bs-crm').'</p>';            
            $bodyStr .= '<div style="position: relative;background: #FFFFFF;box-shadow: 0px 1px 2px 0 rgba(34,36,38,0.15);margin: 1rem 0em;padding: 1em 1em;border-radius: 0.28571429rem;border: 1px solid rgba(34,36,38,0.15);margin-right:1em !important"><h3>'.__('Non-critical Errors:','zero-bs-crm').'</h3>';

            // expose Timeouts
            $timeoutIssues = zeroBSCRM_getSetting('migration300_timeout_issues'); 
            if (isset($timeoutIssues) && $timeoutIssues == 1) echo zeroBSCRM_UI2_messageHTML('warning',__('Timeout','zero-bs-crm'),__('While this migration ran it hit one or more timeouts. This indicates that your server may be unperformant at scale with Jetpack CRM','zero-bs-crm'));

              // list errors
              foreach ($errors as $error){

                $bodyStr .= '<div class="ui vertical segment">';
                  $bodyStr .= '<div class="ui grid">';
                    $bodyStr .= '<div class="two wide column right aligned"><span class="ui orange horizontal label">['.$error[0].']</span></div>';
                    $bodyStr .= '<div class="fourteen wide column"><p style="font-size: 1.1em;">'.$error[1].'</p></div>';
                  $bodyStr .= '</div>';
                $bodyStr .= '</div>';
                
              }

            $bodyStr .= '</div>';


        } else {

          $bodyStr .= zeroBSCRM_UI2_messageHTML('info',__('V3.0 Migration Completed Successfully','zero-bs-crm'),__('There were no errors when migrating your CRM install to v3.0','zero-bs-crm'),'','zbs-succcessfulyv3');

        }

        echo $bodyStr;

        ?><p style="text-align:center;margin:2em">
            <?php if (zeroBSCRM_isZBSAdminOrAdmin()){ ?><a href="<?php echo esc_url(zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']).'&cacheCheck=1'); ?>" class="ui button teal"><?php _e('View Migration Cache','zero-bs-crm'); ?></a><?php } ?>
            <a href="<?php echo zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']); ?>" class="ui button blue"><?php _e('Back to System Status',"zero-bs-crm"); ?></a>            
        </p><?php

    } else {

        // Not migrated yet? What?
        echo '<p>'.__('You have not yet migrated to v3.0','zero-bs-crm').'</p>';

    }

  } elseif (isset($_GET['cacheCheck']) && zeroBSCRM_isZBSAdminOrAdmin()){


      $normalSystemStatusPage = false;

      global $ZBSCRM_t;

      $zbsCPTs = array(
        'zerobs_customer' => _x('Contact','Contact Info (not the verb)','zero-bs-crm'),
        'zerobs_company' => _x(jpcrm_label_company(),'A '.jpcrm_label_company().', e.g. incorporated organisation','zero-bs-crm'),
        'zerobs_invoice' => _x('Invoice','Invoice object (not the verb)','zero-bs-crm'),
        'zerobs_quote' => _x('Quote','Quote object (not the verb) (proposal)','zero-bs-crm'),
        'zerobs_quo_template' => _x('Quote Template','Quote template object (not the verb)','zero-bs-crm'),
        'zerobs_transaction' => _x('Transaction','Transaction object (not the verb)','zero-bs-crm'),
        'zerobs_form' => _x('Form','Website Form object (not shape)','zero-bs-crm')
      );

      echo '<div style="margin:1em;">';
      echo '<h2>'.__('Migration Cache','zero-bs-crm').'</h2>';

      if (isset($_GET['clearCache'])){

        // dump cache

          if  (!isset($_GET['imsure'])){

              // sure you want to clear cache?
              $message = '<p>'.__('Are you sure you want to delete the migration object cache?','zero-bs-crm').'</p>';
              $message .= '<p>'.__('Clearing this cache will remove all backups ZBS has kept of previous data','zero-bs-crm').'</p>';
              $message .= '<p>'.__('(This will free up database space and will not affect your current ZBS data, but please note this cannot be undone)','zero-bs-crm').'</p>';              
              $message .= '<p>';
                $message .= '<a href="'.wp_nonce_url('?page='.$zbs->slugs['systemstatus'].'&cacheCheck=1&clearCache=1&imsure=1','pleaseremovemigrationcache').'" class="ui button orange">'.__('Clear Migration Cache','zero-bs-crm').'</a>';
                $message .= '<a href="'.zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']).'" class="ui button blue">'.__('Back to System Status',"zero-bs-crm").'</a>';
              $message .= '</p>';

              echo zeroBSCRM_UI2_messageHTML('warning',__('Clear Migration Object Cache?','zero-bs-crm'),$message,'warning','clearObjCache');
            

          } else {

            // if sure, clear cache
            if (wp_verify_nonce( $_GET['_wpnonce'], 'pleaseremovemigrationcache' )){

                // is admin, passed 'I'm Sure' nonce check... clear the cache   
                $objCount = $zbs->DAL->truncate('dbmigrationbkposts');
                $objMetaCount = $zbs->DAL->truncate('dbmigrationbkmeta');

                // and store a log as audit trail
                $log = get_option( 'zbs_dbmig_cacheclear' );
                if (!is_array($log)) $log = array();
                $log[] = time();
                update_option('zbs_dbmig_cacheclear',$log, false);

                // cleared
                $message = '<p>'.__('You have cleared the migration object cache','zero-bs-crm').'</p>';
                $message .= '<p>'.zeroBSCRM_prettifyLongInts($objCount).' x '.__('Object','zero-bs-crm').' & '.zeroBSCRM_prettifyLongInts($objMetaCount).' x '.__('Meta','zero-bs-crm').'</p>';
                $message .= '<p>';
                  $message .= '<a href="'.zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']).'" class="ui button blue">'.__('Back to System Status',"zero-bs-crm").'</a>';
                $message .= '</p>';

                echo zeroBSCRM_UI2_messageHTML('info',__('Cleared Migration Object Cache','zero-bs-crm'),$message,'warning','clearObjCache');

            } else {

                // nonce not verified, spoof attempt
                exit();

            }

          }


      } else {

        // show cache
          
          ?><table class="table table-bordered table-striped wtab">
             
                 <thead>
                  
                      <tr>
                          <th colspan="2" class="wmid"><?php _e('Pre-Migration Object Cache',"zero-bs-crm"); ?>:</th>
                      </tr>

                  </thead>
                    
                  <tbody><?php              

          foreach ($zbsCPTs as $cpt => $label){

                $count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(ID) FROM '.$ZBSCRM_t['dbmigrationbkposts'].' WHERE post_type = %s',$cpt));

                 ?>
                 <tr>
                    <td class="wfieldname">
                      <label for="cpt_<?php esc_attr_e( $cpt ); ?>">
                        <?php printf( _x( '%s Objects:', 'table field label', 'zero-bs-crm' ), $label ); ?>
                      </label>
                    </td>
                    <td><?php echo zeroBSCRM_prettifyLongInts($count); ?></td>
                  </tr>
                  <?php

          }

          ?></tbody></table><?php

            ?><p style="text-align:center;margin:2em">
                <?php if (zeroBSCRM_isZBSAdminOrAdmin()){ ?><a href="<?php echo wp_nonce_url('?page='.$zbs->slugs['systemstatus'].'&cacheCheck=1&clearCache=1','clearmigrationcache'); ?>" class="ui button orange"><?php _e('Clear Migration Cache','zero-bs-crm'); ?></a><?php } ?>
                <a href="<?php echo zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']); ?>" class="ui button blue"><?php _e('Back to System Status',"zero-bs-crm"); ?></a>            
            </p><?php

      }

      echo '</div>';

  }

  if ($normalSystemStatusPage){

    $settings = $zbs->settings->getAll();

    // catch tools:
    if (current_user_can('admin_zerobs_manage_options') && isset($_GET['resetuserroles']) && wp_verify_nonce( $_GET['_wpnonce'], 'resetuserroleszerobscrm' ) ){

          // roles
        zeroBSCRM_clearUserRoles();

        // roles + 
        zeroBSCRM_addUserRoles();

        // flag
        $userRolesRebuilt = true;
    }

    // check for, and prep any general sys status errs:
    $generalErrors = array();

      // migration blocker (failed migrations looping)
      $migBlocks = get_option( 'zbsmigrationblockerrors', false);
      if ($migBlocks !== false && !empty($migBlocks)) {
        $generalErrors['migrationblock'] = __('A migration has been blocked from completing. Please contact support.','zero-bs-crm').' (#'.$migBlocks.')';

        // add ability to 'reset migration block'
        $generalErrors['migrationblock'] .= '<br /><a href="'.wp_nonce_url('?page='.$zbs->slugs['systemstatus'].'&resetmigrationblock=1','resetmigrationblock').'">'.__('Retry the Migration','zero-bs-crm').'</a>';

      }

      // hard-check database tables & report

        global $ZBSCRM_t,$wpdb;
        $missingTables = array();
        $tablesExist = $wpdb->get_results("SHOW TABLES LIKE '".$ZBSCRM_t['keys']."'");
        if (count($tablesExist) < 1) $missingTables[] = $ZBSCRM_t['keys'];

        // then we cycle through our tables :) - means all keys NEED to be kept up to date :) 
        foreach ($ZBSCRM_t as $tableKey => $tableName){
            $tablesExist = $wpdb->get_results("SHOW TABLES LIKE '".$tableName."'");
            if (count($tablesExist) < 1) {
              $missingTables[] = $tableName;
            }

        }

        // missing tables?
        if (count($missingTables) > 0){

            $generalErrors['missingtables'] = __('Jetpack CRM has failed creating the tables it needs to operate. Please contact support.','zero-bs-crm').' (#306)';
            $generalErrors['missingtables'] .= '<br />'.__('The following tables could not be created:','zero-bs-crm').' ('.implode(', ',$missingTables).')';

        }

        // Got any persisitent SQL errors on db table creation?
        $creationErrors = get_option('zbs_db_creation_errors');
        if (is_array($creationErrors) && isset($creationErrors['lasttried'])){

            // has persistent SQL creation errors
            $generalErrors['creationsql'] = __('Jetpack CRM experienced errors while trying to build it\'s database tables. Please contact support sharing the following errors:','zero-bs-crm').' (#306sql)';
            if (is_array($creationErrors['errors'])) foreach ($creationErrors['errors'] as $err){

                $generalErrors['creationsql'] .= '<br />&nbsp;&nbsp;'.$err;

            }

        }

  ?>
    
        <p id="sbDesc"><?php _e('This page allows easy access for the various system status variables related to your WordPress install and Jetpack CRM.',"zero-bs-crm"); ?></p>

        <?php if (isset($userRolesRebuilt) && $userRolesRebuilt) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('User Roles Rebuilt',"zero-bs-crm")); echo '</div>'; } ?>
        
        <?php if (count($generalErrors) > 0){

          foreach ($generalErrors as $err) echo zeroBSCRM_UI2_messageHTML('warning','',$err,'','');

        } ?>

        <div id="sbA" style="margin-right:1em">


                  <?php 

                  #CLEARS OUT MIGRATION HISTORY :o $zbs->settings->update('migrations',array());

                  #================================================================
                  #== ZBS relative
                  #================================================================

                      $zbsEnvList = array(

                          'corever' => 'CRM Core Version',
                          'dbver' => 'Database Version',
                          'dalver' => 'DAL Version',
                          'mysql' => 'MySQL Version',
                          'innodb' => 'InnoDB Storage Engine',
                          'sqlrights' => 'SQL Permissions',
                          # clear auto-draft
                          'autodraftgarbagecollect' => 'Auto-draft Garbage Collection',
                          'locale' => 'Locale',
                          'assetdir' => 'Asset Upload Directory',
                          'wordpressver'  => 'WordPress Version',
                          'local' => 'Server Connectivity',
                          'localtime' => 'DateTime Setting',
                          'devmode' => 'Dev/Production Mode',
                          'permalinks'  => 'Pretty Permalinks'
                        ); 

                      if (count($zbsEnvList)){ 
                  ?>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('CRM Environment',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($zbsEnvList as $envCheckKey => $envCheckName){ 

                          #} Retrieve
                          $result = zeroBSCRM_checkSystemFeat($envCheckKey,true);

                          ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_env_<?php echo $envCheckKey; ?>"><?php _e($envCheckName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php 
                          if (!$result[0] && $envCheckKey != 'devmode') echo '<div class="ui yellow label">'.__('Warning','zero-bs-crm').'</div>&nbsp;&nbsp;';
                          ?><?php echo $result[1]; ?></td>
                        </tr>

                        <?php } ?>
      
                      </tbody>

                  </table>


                  <?php } ?>


                  <?php 

                  #================================================================
                  #== Server relative
                  #================================================================

                      $servEnvList = array(
                        'serverdefaulttime' => 'Server Default Timezone',
                        'curl'    => 'CURL',
                        'zlib'    =>'zlib (Zip Library)',
                        'dompdf'  =>'PDF Engine',
                        'pdffonts'  =>'PDF Font Set',
                        'phpver'  => 'PHP Version',
                        'memorylimit'  => 'Memory Limit',
                        'executiontime'  => 'Max Execution Time',
                        'postmaxsize'  => 'Max File POST',
                        'uploadmaxfilesize' => 'Max File Upload Size',
                        'wpuploadmaxfilesize' => 'WordPress Max File Upload Size'
                        );

                      if (count($servEnvList)){ 
                  ?>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Server Environment',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($servEnvList as $envCheckKey => $envCheckName){ 

                          #} Retrieve
                          $result = zeroBSCRM_checkSystemFeat($envCheckKey,true);

                          ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_env_<?php echo $envCheckKey; ?>"><?php _e($envCheckName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php echo $result[1]; ?></td>
                        </tr>

                        <?php } ?>

                        <?php do_action('zbs_server_checks'); ?>
      
                      </tbody>

                  </table>

                  <?php } ?>


                  <?php 

                  #================================================================
                  #== WordPress relative
                  #================================================================

                      $wpEnvList = array(); #none yet :)

                      if (count($wpEnvList)){ 
                  ?>
              <table class="table table-bordered table-striped wtab" >
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('WordPress Environment',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($wpEnvList as $envCheckKey => $envCheckName){ 

                          #} Retrieve
                          $result = zeroBSCRM_checkSystemFeat($envCheckKey,true);

                          ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_env_<?php echo $envCheckKey; ?>"><?php _e($envCheckName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php echo $result[1]; ?></td>
                        </tr>

                        <?php } ?>
      
                      </tbody>

                  </table>

                  <?php } ?>


                  <?php 

                  #================================================================
                  #== ZBS relative: Migrations
                  #================================================================ 

                      // 2.88 moved this to show all migrations, completed or failed.
                  
                      global $zeroBSCRM_migrations;
                      $migratedAlreadyArr = zeroBSCRM_migrations_getCompleted(); // from 2.88 $zbs->settings->get('migrations');
                      
                      # temp
                      // n/a, fixed $migrationVers = array('123'=>'1.2.3','1119' => '1.1.19','127'=>'1.2.7','2531'=>'2.53.1','2943'=>'2.94.3','2952' => '2.95.2');
                      $migrationVers = array();

                      if (is_array($zeroBSCRM_migrations) && count($zeroBSCRM_migrations) > 0){ 
                  ?>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Jetpack CRM Migrations Completed',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($zeroBSCRM_migrations as $migrationkey){ 

                          //$migrationDetail = get_option('zbsmigration'.$migrationkey);
                          $migrationDetails = zeroBSCRM_migrations_geMigration($migrationkey);
                          $migrationDetail = $migrationDetails[1];
                          #array('completed'=>time(),'meta'=>array('updated'=>'['.$quotesUpdated.','.$invsUpdated.']')));

                          $migrationName = $migrationkey; if (isset($migrationVers[$migrationkey])) $migrationName = $migrationVers[$migrationkey];

                          // 29999 => 2.99.99
                          $migrationName = zeroBSCRM_format_migrationVersion($migrationName);

                        ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_mig_<?php echo $migrationkey; ?>"><?php _e('Migration: '.$migrationName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php  

                              if (isset($migrationDetail['completed'])) {
                                
                                echo __('Completed','zero-bs-crm').' '.date('F j, Y, g:i a',$migrationDetail['completed']); 
                                if (isset($migrationDetail['meta']) && isset($migrationDetail['meta']['updated'])) {
                                  
                                  // pretty up
                                  $md = $migrationDetail['meta']['updated'];
                                  if ($migrationDetail['meta']['updated'] == 1) $md = __('Success','zero-bs-crm');
                                  if ($migrationDetail['meta']['updated'] == -1) $md = __('Fail/NA','zero-bs-crm');
                                  if ($migrationDetail['meta']['updated'] == 0) $md = __('Success','zero-bs-crm'); // basically
                                  
                                  echo ' ('.$md.')';
                                  
                                }

                              } else echo __('Not Yet Ran','zero-bs-crm');

                              ?></td>
                        </tr>

                        <?php } ?>

                        <?php

                          // expose migration Timeouts
                          $timeoutIssues = zeroBSCRM_getSetting('migration300_timeout_issues'); 
                          if (isset($timeoutIssues) && $timeoutIssues == 1) echo '<tr><td colspan="2" style="text-align:center"><strong>'.__('Timeouts','zero-bs-crm').'</strong>: '.__('One or more migrations experienced timeouts while running. This may indicate that your server is not performing very well.','zero-bs-crm').'</td></tr>';

                        ?>
      
                      </tbody>

                  </table>


                  <?php } ?>

              <?php 
              /* LOADS OF WH DEBUG FROM LICENSING PUSH, leaving until that's validated fini

                // detected ext 
                //
                echo 'Ext:<br><pre>'; print_r(zeroBSCRM_installedProExt()); echo '</pre>';
                global $zbs;
                $settings = $zbs->settings->get('license_key');
                echo 'Settings:<br><pre>'; print_r($settings); echo '</pre>';

                // set_site_transient('update_plugins', null);

                // this should force an update check (and update keys)
                $pluginUpdater = new zeroBSCRM_Plugin_Updater($zbs->urls['api'], $zbs->api_ver, 'zero-bs-crm');
                $zbs_transient = '';
                $x = $pluginUpdater->check_update($zbs_transient);
                echo 'updater:<br><pre>'; print_r($x); echo '</pre>';


                  // Check plugins https://stackoverflow.com/questions/22137814/wordpress-shows-i-have-1-plugin-update-when-all-plugins-are-already-updated
                $output = '';
                  $plugin_updates = get_site_transient( 'update_plugins' );
                  if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
                      foreach ( $plugin_updates->response as $plugin => $details ) {
                          echo "<p><strong>Plugin</strong> <u>$plugin</u> is reporting an available update.</p>";
                          print_r($details);
                      }
                  }

                echo 'updater:<br><pre>'; print_r(get_plugin_updates()); echo '</pre>'; */
                
               ?>
              <table class="table table-bordered table-striped wtab">
                 
                   <thead>
                    
                        <tr>
                            <th colspan="2" class="wmid"><?php _e('Extensions',"zero-bs-crm"); ?>:</th>
                        </tr>

                    </thead>
                    
                    <tbody>

                      <?php $exts = zeroBSCRM_installedProExt(); 
                      if (is_array($exts) && count($exts) > 0){

                        // simple list em (not complex like connect page)
                        foreach ($exts as $shortName => $e){

                          ?><tr><td><?php echo $e['name']; ?></td><td><?php echo $e['ver']; ?></td></tr><?php

                        }


                      } else {

                        ?><tr><td colspan="2"><div style=""><?php
                        
                        $message = __('No Extensions Detected','zero-bs-crm');
                        // upsell/connect if not wl
                        ##WLREMOVE 
                        $message .= '<br /><a href="'.$zbs->urls['products'].'">'.__('Purchase Extensions','zero-bs-crm').'</a> or <a href="'.$zbs->slugs['settings'].'&tab=license">'.__('Add License Key','zero-bs-crm').'</a>';
                        ##/WLREMOVE

                        ?></div></td></tr><?php

                      } ?>



                    </tbody>

              </table>
              <div id="zbs-licensing-debug" style="display:none;border:1px solid #ccc;margin:1em;padding:1em;background:#FFF">
                <?php if (zeroBSCRM_isZBSAdminOrAdmin()){
                      $l = $zbs->DAL->setting('licensingcount',0);
                      $err = $zbs->DAL->setting('licensingerror',false);
                      $key = $zbs->settings->get('license_key');

                      echo 'Attempts:'.$l.'</br>Err:<pre>'.print_r($err,1).'</pre></br>key:<pre>'.print_r($key,1).'</pre>';

                    } ?>
              </div>

               <?php 
              /* Debug for external sources

                  echo 'src:<pre>'.print_r($zbs->external_sources,1).'</pre>'; 
              */
                
               ?>
              <table class="table table-bordered table-striped wtab">
                 
                   <thead>
                    
                        <tr>
                            <th colspan="2" class="wmid"><?php _e('External Source Register',"zero-bs-crm"); ?>:</th>
                        </tr>

                    </thead>
                    
                    <tbody>

                      <?php
                      if (is_array($zbs->external_sources) && count($zbs->external_sources) > 0){

                        // simple list em
                        foreach ($zbs->external_sources as $key => $extsource){

                          ?><tr><td><?php echo $extsource[0].' ('.$key.')'; ?></td><td><?php if (isset($extsource['ico']) && !empty($extsource['ico'])) echo '<i class="fa '.$extsource['ico'].'"></i>'; else echo '???'; ?></td></tr><?php

                        }


                      } else {

                        ?><tr><td colspan="2"><div style=""><?php
                        
                        $message = __('No External Sources Registered. Please contact support!','zero-bs-crm');
                        
                        ?></div></td></tr><?php

                      } ?>



                    </tbody>

              </table>

               <?php 

                // if admin + has perf logs to show
                if (zeroBSCRM_isWPAdmin()){
                  $zbsPerfTestOpt = get_option( 'zbs-global-perf-test', array());

                  if (is_array($zbsPerfTestOpt) && count($zbsPerfTestOpt) > 0){

                     ?>
                    <table class="table table-bordered table-striped wtab">
                       
                         <thead>
                          
                              <tr>
                                  <th colspan="3" class="wmid"><?php _e('Performance Tests',"zero-bs-crm"); ?>:</th>
                              </tr>
                          
                              <tr>
                                  <th class=""><?php _e('Started',"zero-bs-crm"); ?>:</th>
                                  <th class="wmid"><?php _e('Get',"zero-bs-crm"); ?>:</th>
                                  <th class=""><?php _e('Results',"zero-bs-crm"); ?>:</th>
                              </tr>

                          </thead>
                          
                          <tbody>

                            <?php

                              // simple list em
                              foreach ($zbsPerfTestOpt as $perfTest){

                                ?><tr>

                                <td><?php 

                                  if (isset($perfTest['init'])) echo date('F j, Y, g:i a',$perfTest['init']);

                                ?></td>

                                <td><?php 

                                  if (isset($perfTest['get']) && is_array($perfTest['get'])) echo '<pre>'.print_r($perfTest['get'],1).'</pre>';

                                ?></td>

                                <td><?php 

                                  if (isset($perfTest['results']) && is_array($perfTest['results'])) echo '<pre>'.print_r($perfTest['results'],1).'</pre>';

                                ?></td>

                                </tr><?php 

                              }

                            ?>

                          </tbody>

                    </table>
                    <?php } // / has perf tests

                  } // / admin ?>

              <div class="ui segment">
                <h3><?php _e('Administrator Tools',"zero-bs-crm"); ?></h3>
                <a href="<?php echo wp_nonce_url('?page='.$zbs->slugs['systemstatus'].'&resetuserroles=1','resetuserroleszerobscrm'); ?>" class="ui button blue"><?php _e('Re-build User Roles',"zero-bs-crm"); ?></a>
                <?php if ($zbs->isDAL3()){ ?><a href="<?php echo zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']).'&v3migrationlog=1'; ?>" class="ui button blue"><?php _e('v3 Migration Logs',"zero-bs-crm"); ?></a><?php } ?>
              </div>


              <script type="text/javascript">

                jQuery(document).ready(function(){



                });


              </script>
              
      </div><?php 


    } // / if normal page load

}



/* ======================================================
   / Admin Pages
   ====================================================== */


/* ======================================================
  HTML Output Msg (alerts)
   ====================================================== */

   #} wrapper here for lib
  function whStyles_html_msg($flag,$msg,$includeExclaim=false){

    zeroBSCRM_html_msg($flag,$msg,$includeExclaim);

  }

  #} Outputs HTML message - 27th Feb 2019 - modified for Semantic UI (still had sgExclaim!)
  function zeroBSCRM_html_msg($flag,$msg,$includeExclaim=false){
    
      if ($includeExclaim){ $msg = '<div id="sgExclaim">!</div>'.$msg.''; }
      if ($flag == -1){
        echo '<div class="ui message alert danger">'.$msg.'</div>';
      } 
      if ($flag == 0){
        echo '<div class="ui message alert success">'.$msg.'</div>';  
      }
      if ($flag == 1){
        echo '<div class="ui message alert warning">'.$msg.'</div>'; 
      }
        if ($flag == 2){
            echo '<div class="ui message alert info">'.$msg.'</div>';
        }

      
  }

/* ======================================================
  / HTML Output Msg (alerts)
   ====================================================== */
