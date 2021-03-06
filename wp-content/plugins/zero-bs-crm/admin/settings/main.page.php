<?php 
/*!
 * Main Settings Page file: This is the main file which controls the different pages in the setting section and render the layout
 * Jetpack CRM - https://jetpackcrm.com
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

// permissions check
if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }

// global header
zeroBSCRM_pages_header();

// required
global $zbs;

// Tab - Page mapping
/*

    Example:
    '_GET KEY' => [
        'page'  => 'FILE TO LOAD', // required
        'title' => __( 'TITLE', "zero-bs-crm" ), // required
        'title-notice' => array(
                                'colour' => 'blue',
                                'icon' => 'circle info',
                                'body' => __('This area is part of the B2B Extension','zero-bs-crm').' <a href="'.esc_url(zbsLink($zbs->slugs['extensions'].'#core-modules')).'" target="_blank">'.__('Manage Extensions','zero-bs-crm').'</a>'
                            ) // NOT required - array to display top-right label
    ],

*/
$tab_page_map = [
    'settings' => [
        'page'              => 'general',
        'title'             => __( 'General Settings', "zero-bs-crm" ),
    ],
    'customfields' => [
        'page'              => 'custom-fields',
        'title'             => __( 'Custom Fields', "zero-bs-crm" ),
    ],
    'quotes' => [
        'page'              => 'customers',
        'title'             => __( 'Custom Fields', "zero-bs-crm" ),
    ],
    'fieldoptions' => [
        'page'              => 'field-options',
        'title'             => __( 'Field Options', "zero-bs-crm" ),
    ],
    'listview' => [
        'page'              => 'list-view',
        'title'             => __( 'List View', "zero-bs-crm" ),
    ],
    'license' => [
        'page'              => 'license',
        'title'             => __( 'License Key', "zero-bs-crm" ),
    ],
    'clients' => [
        'page'              => 'client-portal',
        'title'             => __( 'Client Portal', "zero-bs-crm" ),
    ],
    'transactions' => [
        'page'              => 'transactions',
        'title'             => __( 'Transactions', "zero-bs-crm" ),
    ],
    'forms' => [
        'page'              => 'forms',
        'title'             => __( 'Forms', "zero-bs-crm" ),
    ],
    'fieldsorts' => [
        'page'              => 'field-sorts',
        'title'             => __( 'Field Sorts', "zero-bs-crm" ),
    ],
    'api' => [
        'page'              => 'api-settings',
        'title'             => __( 'API Settings', "zero-bs-crm" ),
    ],
    'mail' => [
        'page'              => 'mail',
        'title'             => __( 'Mail Settings', "zero-bs-crm" ),
    ],
    'maildelivery' => [
        'page'              => 'mail-delivery',
        'title'             => null,
    ],
    'bizinfo' => [
        'page'              => 'business-info',
        'title'             => __( 'Your Business Info', "zero-bs-crm" ),
    ],
    'tax' => [
        'page'              => 'tax',
        'title'             => __( 'Tax Settings', "zero-bs-crm" ),
    ],
    'companies' => [
        'page'              => 'companies',
        'title'             => __( 'Companies Settings', "zero-bs-crm" ),
        'title-notice'      => array(
                                'colour' => 'blue',
                                'icon' => 'circle info',
                                'body' => __('This area is part of the B2B Extension','zero-bs-crm').' <a href="'.esc_url(zbsLink($zbs->slugs['extensions'].'#core-modules')).'" target="_blank">'.__('Manage Extensions','zero-bs-crm').'</a>'
                            )
    ],
];

/**
 * Render the menu settings
 *
 * @param string $current
 */
function jpcrm_render_settings_menu( $current = 'homepage' ) {
    include_once "partials/menu.block.php";
}

/**
 * Render the title of the settings page
 *
 * @param string $title
 */
function jpcrm_render_setting_title( $title = '', $settings_rightfloated_notice = false ) {
    if ( ! empty( $title ) ) {
        include_once 'partials/title.block.php';
    }
}

/**
 * Invoice settings page
 */
function zeroBSCRM_extensionhtml_settings_invbuilder(){
    jpcrm_load_page( 'settings', 'invoicing');
}

/**
 * Quotes settings page
 */
function zeroBSCRM_extensionhtml_settings_quotebuilder(){
    jpcrm_load_page( 'settings', 'quotes');
}

/**
 * Settings sub pages
 */
function jpcrm_render_extension_settings( $current_tab ) {
    
    // attempt to find zeroBSCRM_extensionhtml_settings_*()
    if ( function_exists('zeroBSCRM_extensionhtml_settings_'.$current_tab ) ) {

        // if name function exists, render title using zeroBSCRM_extension_name_*()
        if (function_exists('zeroBSCRM_extension_name_'.$current_tab)){
            $settingPageName = call_user_func('zeroBSCRM_extension_name_'.$current_tab);
            jpcrm_render_setting_title( $settingPageName );
        }

        // render page
        $settingsPageFunc = 'zeroBSCRM_extensionhtml_settings_'.$current_tab;
        call_user_func( $settingsPageFunc );

    } else {

        // failed to find page
        zeroBSCRM_html_msg(-1,'There was an error loading this settings page ' . $current_tab);

    }

}



// $zbs allows filtering of the extensionsInstalled list so extensions add to here.
global $zeroBSCRM_extensionsInstalledList, $pagenow;
$zeroBSCRM_extensionsInstalledList = $zbs->extensions;

// Attempt to retrieve any legitimate tab
$current_tab = 'settings'; $getTab = ''; if (isset($_GET['tab'])) $getTab = sanitize_text_field($_GET['tab']);

// If from installed extensions:
if ( !empty ( $getTab  )  && in_array( $getTab, $zeroBSCRM_extensionsInstalledList ) ) {
    $current_tab = $getTab;
}

// Generic check if legitimate from the map
// We used to check these manually (e.g. if ($getTab == 'quotes') $current_tab = 'quotes';) but there was no need. Genericifying this excluded some old pages: customers, quotes, invoices, whlang, mailcampaigns (from 4.0.9)
if ( array_key_exists($getTab, $tab_page_map) ) $current_tab = $getTab;

// if our current tab has a title notice, add it to the global
$settings_rightfloated_notice = false; if ( isset( $tab_page_map[$current_tab] ) && isset( $tab_page_map[$current_tab]['title-notice'] ) ) $settings_rightfloated_notice = $tab_page_map[$current_tab]['title-notice'];

// check if settings updated
$setting_updated = isset($_GET['updated']) && 'true' == esc_attr( $_GET['updated'] );

// V3 Migration Interaction
$v3InProgress = get_option( 'zbs_db_migration_300_inprog', false );
$migration_msg = __( 'There is currently a CRM Migration in progress, until that migration has finished you will not be able to change any settings, as these may intefere with a safe migration. These will be back up shortly.', 'zero-bs-crm' );

?>

<?php if ( $setting_updated ): ?>
    <?php echo zeroBSCRM_UI2_messageHTML( 'info', '', __( 'Settings updated', 'zero-bs-crm' ) ); ?>
<?php endif ?>

<div class="ui grid zbs-page-wrap" style="margin-top:0em">

    <?php 
    // show blocker if mid-migration
    if ( $v3InProgress ): ?>

        <div id="zbs-migration-blocker"></div><div id="zbs-migration-settings-notice">
            <?= zeroBSCRM_UI2_messageHTML( 'warning', __( 'Migration in Progress', 'zero-bs-crm' ), $migration_msg, 'hourglass half', 'zbs-migration-settings-msg' ) ?>
        </div>

    <?php endif ?>

    <div class="four wide column">
        <?php jpcrm_render_settings_menu( $current_tab ) ?>
    </div>
    <div class="twelve wide stretched column" style="padding-left:0;">
        <div class="ui segment">
            <div id="poststuff" class="pusher zbs-settings-page">

                <?php wp_nonce_field( "ilc-settings-page" ) ?>

                <?php if ( $pagenow == 'admin.php' && $_GET['page'] == $zbs->slugs['settings'] ): ?>

                    <?php if ( array_key_exists( $current_tab, $tab_page_map ) ): ?>

                        <?php jpcrm_render_setting_title( $tab_page_map[ $current_tab ]['title'], $settings_rightfloated_notice ) ?>

                        <?php jpcrm_load_page( 'settings', $tab_page_map[ $current_tab ]['page']  ) ?>

                    <?php else: ?>

                        <?php jpcrm_render_extension_settings( $current_tab ) ?>

                    <?php endif ?>

                <?php endif ?>

            </div>
        </div>
    </div>
</div>

<?php zeroBSCRM_pages_footer() ?>
