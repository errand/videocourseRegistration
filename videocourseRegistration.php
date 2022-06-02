<?php
/*
Plugin Name: Stadtlabore VideoCourse Registration
Plugin URI:
Description: Ajax register and tracking
Version: 1.0
Author: Aleksandr Shatskikh
*/

use Timber\PostQuery;

include_once 'db.php';
register_activation_hook(__FILE__, 'videocourse_install');

function videocourseRegistration_enqueue_script()
{
    wp_enqueue_script('ajax_videocourseRegistration', plugin_dir_url(__FILE__) . 'videocourseRegistration.js', '', '', true);
    wp_localize_script('ajax_videocourseRegistration', 'videocourseRegistration', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}

add_action('wp_enqueue_scripts', 'videocourseRegistration_enqueue_script');

add_action("wp_ajax_registerUser", "registerUser");
add_action("wp_ajax_nopriv_registerUser", "registerUser");

function registerUser()
{
    $data = stripslashes(html_entity_decode($_POST['inputs']));
    $data = json_decode($data, true);

    $userMetaData = [
        'user_login'            => $data['userEmail'],
        'user_email'            => $data['userEmail'],
        'user_pass'             => $data['userPassword'],
        'first_name'            => $data['userFirstName'],
        'last_name'             => $data['userLastName'],
        'show_admin_bar_front'  => false,
        'meta_input'            => [
            'userStadtKommune'  => $data['userStadtKommune'],
            'videoTracking'     => '',
        ],
    ];

    $user_id = wp_insert_user($userMetaData);
    auto_login_new_user($user_id);
    wp_send_json_success();
    wp_die();
}

function auto_login_new_user($user_id)
{
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    $user = get_user_by('id', $user_id);
    do_action('wp_login', $user->user_login);
}
add_action('user_register', 'auto_login_new_user');

add_action("wp_ajax_logoutUser", "logoutUser");
add_action("wp_ajax_nopriv_logoutUser", "logoutUser");

function logoutUser()
{
    wp_logout();
    ob_clean();
    wp_send_json_success();
}

add_action("wp_ajax_loginUser", "loginUser");
add_action("wp_ajax_nopriv_loginUser", "loginUser");

function loginUser()
{
    $data = array();
    $data['user_login'] = $_POST['login'];
    $data['user_password'] = $_POST['password'];

    $user_signon = wp_signon($data, false);
    if (is_wp_error($user_signon)) {
        wp_send_json(json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.'))));
    } else {
        $data['loggedin'] = true;
        wp_send_json($data);
    }

    die();
}

add_action("wp_ajax_deleteUser", "deleteUser");
add_action("wp_ajax_nopriv_deleteUser", "deleteUser");

function deleteUser()
{
  require_once(ABSPATH.'wp-admin/includes/user.php' );
  global $wpdb;

  $user_id = $_POST['userId'];

  $roles = array();
  $user = get_userdata($user_id);
  $capabilities = $user->{$wpdb->prefix . 'capabilities'};
  if (!isset($wp_roles))
    $wp_roles = new WP_Roles();
  foreach ($wp_roles->role_names as $role => $name) :
    if (array_key_exists($role, $capabilities))
      $roles[] = $role;
  endforeach;
  if (in_array("subscriber", $roles)) {
    if (wp_delete_user($user_id)) {
      wp_send_json_success();
    } else {
      wp_send_json(json_encode(array('loggedin'=>true, 'message'=>__('Some error'))));
    }
  }

  die();
}

add_action("wp_ajax_recoverPassword", "recoverPassword");
add_action("wp_ajax_nopriv_recoverPassword", "recoverPassword");

function recoverPassword()
{
  // First check the nonce, if it fails the function will break
  check_ajax_referer( 'ajax-forgot-nonce', 'security' );

  $account = $_POST['email'];

  if( empty( $account ) ) {
    $error = 'Enter an username or e-mail address.';
  } else {
    if(is_email( $account )) {
      if( email_exists($account) )
        $get_by = 'email';
      else
        $error = 'Stellen Sie sicher, dass die E-Mail richtig geschrieben ist';
    }
    else if (validate_username( $account )) {
      if( username_exists($account) )
        $get_by = 'login';
      else
        $error = 'Stellen Sie sicher, dass die E-Mail richtig geschrieben ist';
    }
    else
      $error = 'Ungültiger Benutzername oder E-Mail-Adresse.';
  }

  if(empty ($error)) {
    // lets generate our new password
    //$random_password = wp_generate_password( 12, false );
    $random_password = wp_generate_password();

    // Get user data by field and data, fields are id, slug, email and login
    $user = get_user_by( $get_by, $account );

    $update_user = wp_update_user( array ( 'ID' => $user->ID, 'user_pass' => $random_password ) );

    // if  update user return true then lets send user an email containing the new password
    if( $update_user ) {
      $to = $user->user_email;
      $subject = 'Dein neues Passwort';
      $message = 'Dein neues Passwort ist: '.$random_password;
      $headers = array('Content-Type: text/html; charset=UTF-8');

      $mail = wp_mail( $to, $subject, $message, $headers );
      if( $mail )
        $success = 'Überprüfen Sie Ihre E-Mail-Adresse für Ihr neues Passwort.';
      else
        $error = 'Das System kann Ihnen keine E-Mail mit Ihrem neuen Passwort senden.';
    } else {
      $error = 'Beim Aktualisieren Ihres Kontos ist etwas schief gelaufen.';
    }
  }

  if( ! empty( $error ) )
    echo json_encode(array('loggedin'=>false, 'message'=>__($error)));

  if( ! empty( $success ) )
    echo json_encode(array('loggedin'=>false, 'message'=>__($success)));

  die();
}

function my_login_logo() { ?>
  <style type="text/css">
    #login h1 a, .login h1 a {
      background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/Components/NavigationMain/Assets/logo.svg);
      height:97px;
      width:320px;
      background-size: 320px 97px;
      background-repeat: no-repeat;
    }
  </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );

function my_login_logo_url() {
  return '/videocourses';
}
add_filter( 'login_headerurl', 'my_login_logo_url' );

add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
  if (!current_user_can('edit_posts')) {
    show_admin_bar(false);
  }
}


// CHANGE PASSWORD

/**
 * Notify the blog admin of a user changing password, normally via email.
 *
 * @since 2.7.0
 *
 * @param WP_User $user User object.
 */
function wp_password_change_notification( $user ) {
  // send a copy of password change notification to the admin
  // but check to see if it's the admin whose password we're changing, and skip this
  if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
    $message = sprintf(__('Password Lost and Changed for user: %s'), $user->user_login) . "\r\n";
    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    wp_mail(get_option('admin_email'), sprintf(__('[%s] Password Lost/Changed'), $blogname), $message);
  }
}

/**
 * Email login credentials to a newly-registered user.
 *
 * A new user registration notification is also sent to admin email.
 *
 * @since 2.0.0
 * @since 4.3.0 The `$plaintext_pass` parameter was changed to `$notify`.
 * @since 4.3.1 The `$plaintext_pass` parameter was deprecated. `$notify` added as a third parameter.
 *
 * @global wpdb         $wpdb      WordPress database object for queries.
 * @global PasswordHash $wp_hasher Portable PHP password hashing framework instance.
 *
 * @param int    $user_id    User ID.
 * @param null   $deprecated Not used (argument deprecated).
 * @param string $notify     Optional. Type of notification that should happen. Accepts 'admin' or an empty
 *                           string (admin only), or 'both' (admin and user). Default empty.
 */
function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
  if ( $deprecated !== null ) {
    _deprecated_argument( __FUNCTION__, '4.3.1' );
  }

  global $wpdb, $wp_hasher;
  $user = get_userdata( $user_id );

  // The blogname option is escaped with esc_html on the way into the database in sanitize_option
  // we want to reverse this for the plain text arena of emails.
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

  $message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
  $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
  $message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";

  @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

  // `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notifcation.
  if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
    return;
  }

  // Generate something random for a password reset key.
  $key = wp_generate_password( 20, false );

  /** This action is documented in wp-login.php */
  do_action( 'retrieve_password_key', $user->user_login, $key );

  // Now insert the key, hashed, into the DB.
  if ( empty( $wp_hasher ) ) {
    require_once ABSPATH . WPINC . '/class-phpass.php';
    $wp_hasher = new PasswordHash( 8, true );
  }
  $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
  $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

  $message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
  $message .= __('To set your password, visit the following address:') . "\r\n\r\n";
  $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

  $message .= wp_login_url() . "\r\n";

  wp_mail($user->user_email, sprintf(__('[%s] Your username and password info'), $blogname), $message);
}

add_filter( 'password_change_email', 'change_password_mail_message', 10, 3 );
function change_password_mail_message( $pass_change_mail, $user, $userdata ){
  $new_message_txt = __( 'Hi ###USERNAME###,

This notice confirms that your email was changed on ###SITENAME###.

If you did not change your email, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###' );

  $pass_change_mail[ 'message' ] = $new_message_txt;

  return $pass_change_mail;
}

add_filter( 'email_change_email', 'email_change_email_message', 10, 3 );
function email_change_email_message( $email_change_email, $user, $userdata ){
  $new_message_txt = __( 'Hi ###USERNAME###,

This notice confirms that your email was changed on ###SITENAME###.

If you did not change your email, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###' );

  $email_change_email[ 'message' ] = $new_message_txt;

  return $email_change_email;
}
