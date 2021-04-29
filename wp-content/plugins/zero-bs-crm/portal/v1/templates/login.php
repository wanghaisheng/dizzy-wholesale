<?php
/**
 * Login Template
 *
 * This is the login page for the Portal
 *
 * @author 		ZeroBSCRM
 * @package 	Templates/Portal/Login
 * @see			https://jetpackcrm.com/kb/
 * @version     3.0
 * 
 */



if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access

// zeroBS_portal_enqueue_stuff();

do_action( 'zbs_enqueue_scrips_and_styles' );

$portalPage = zeroBSCRM_getSetting('portalpage');
$portalLink = get_page_link($portalPage);

?>
<style>
#rememberme{
	box-shadow: none;
	width:20px;
	margin-right:5px;
	float:left;
}
.login-remember label {
    display: inline-block;
    max-width: 100%;
    margin-bottom: 5px;
    font-weight: bold;
    line-height: 35px;
}
.sgr-recaptcha{
	transform:scale(1);
}
.sgr-recaptcha div{
	margin:auto;
}
</style>

<div id="zbs-main" class="zbs-site-main">
	<div class="zbs-client-portal-wrap main site-main zbs-post zbs-hentry">

<?php

$args = array(
	'echo'           => true,
	'remember'       => true,
	'redirect'       => $portalLink,
	'form_id'        => 'loginform',
	'id_username'    => 'user_login',
	'id_password'    => 'user_pass',
	'id_remember'    => 'rememberme',
	'id_submit'      => 'wp-submit',
	'label_username' => __( 'Email Address' ),
	'label_password' => __( 'Password' ),
	'label_remember' => __( 'Remember Me' ),
	'label_log_in'   => __( 'Log In' ),
	'value_username' => '',
	'value_remember' => false
);

// add a filter for now, which adds a hidden field, which lets our redir catcher catch failed logins + bringback
add_filter( 'login_form_bottom', 'zeroBSCRM_portal_loginFooter');
function zeroBSCRM_portal_loginFooter($prev=''){

	return $prev.'<input type="hidden" name="fromzbslogin" value="1" />';
}

// catch fails
if (isset($_GET['login']) && $_GET['login'] == 'failed'){

	echo '<div class="alert alert-info">'.__('Your username or password was incorrect. Please try again','zero-bs-crm').'</div>';

}

echo '<div class="container zbs-portal-login" style="margin-top:20px;text-align:center;">';

?>
<h2><?php esc_html_e(apply_filters('zbs_portal_login_title', __('Welcome to your Client Portal',"zero-bs-crm")),'zero-bs-crm'); ?></h2>
<p><?php esc_html_e(apply_filters('zbs_portal_login_content', __("Please login to your Client Portal to be able to view your documents","zero-bs-crm"),'zero-bs-crm')); ?></p>
<div class="login-form">
<?php
wp_login_form( $args );
do_action('login_form');
?>
<a href="<?php echo wp_lostpassword_url(); ?>" title="Lost Password"><?php _e("Lost Password","zero-bs-crm");?></a>
</div>
<?php
echo '</div>';


?>
	<?php zeroBSCRM_portalFooter(); ?>

	</div>

</div>