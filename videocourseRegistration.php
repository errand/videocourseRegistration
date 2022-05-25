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
            'userAnrede'        => $data['userAnrede'],
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

add_action("wp_ajax_recoverPassword", "recoverPassword");
add_action("wp_ajax_nopriv_recoverPassword", "recoverPassword");

function recoverPassword()
{
  // First check the nonce, if it fails the function will break
  check_ajax_referer( 'ajax-forgot-nonce', 'security' );

  global $wpdb;

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

      $from = 'info@stadtlabore-deutschland.de'; // Set whatever you want like mail@yourdomain.com

      if(!(isset($from) && is_email($from))) {
        $sitename = strtolower( $_SERVER['SERVER_NAME'] );
        if ( substr( $sitename, 0, 4 ) == 'www.' ) {
          $sitename = substr( $sitename, 4 );
        }
        $from = 'robot@'.$sitename;
      }

      $to = $user->user_email;
      $subject = 'Dein neues Passwort';
      $sender = 'From: '.get_option('name').' <'.$from.'>' . "\r\n";

      $message = 'Dein neues Passwort ist: '.$random_password;

      $headers[] = 'MIME-Version: 1.0' . "\r\n";
      $headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
      $headers[] = "X-Mailer: PHP \r\n";
      $headers[] = $sender;

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

function my_previous_post_where() {

  global $post, $wpdb;

  return $wpdb->prepare( "WHERE p.menu_order < %s AND p.post_type = %s AND p.post_status = 'publish'", $post->menu_order, $post->post_type);
}
add_filter( 'get_previous_post_where', 'my_previous_post_where' );

function my_next_post_where() {

  global $post, $wpdb;

  return $wpdb->prepare( "WHERE p.menu_order > %s AND p.post_type = %s AND p.post_status = 'publish'", $post->menu_order, $post->post_type);
}
add_filter( 'get_next_post_where', 'my_next_post_where' );

function my_previous_post_sort() {

  return "ORDER BY p.menu_order desc LIMIT 1";
}
add_filter( 'get_previous_post_sort', 'my_previous_post_sort' );

function my_next_post_sort() {

  return "ORDER BY p.menu_order asc LIMIT 1";
}
add_filter( 'get_next_post_sort', 'my_next_post_sort' );

add_action( 'admin_init', 'your_custom_post_order_fn' );

function your_custom_post_order_fn()
{
  add_post_type_support( 'video', 'page-attributes' );
}

$MY_POST_TYPE = "video"; // just for a showcase

// the basic support (menu_order is included in the page-attributes)
add_post_type_support($MY_POST_TYPE, 'page-attributes');

// add a column to the post type's admin
// basically registers the column and sets it's title
add_filter('manage_' . $MY_POST_TYPE . '_posts_columns', function ($columns) {
  $columns['menu_order'] = "Order"; //column key => title
  return $columns;
});

// display the column value
add_action( 'manage_' . $MY_POST_TYPE . '_posts_custom_column', function ($column_name, $post_id){
  if ($column_name == 'menu_order') {
    echo get_post($post_id)->menu_order;
  }
}, 10, 2); // priority, number of args - MANDATORY HERE!

// make it sortable
$menu_order_sortable_on_screen = 'edit-' . $MY_POST_TYPE; // screen name of LIST page of posts
add_filter('manage_' . $menu_order_sortable_on_screen . '_sortable_columns', function ($columns){
  // column key => Query variable
  // menu_order is in Query by default so we can just set it
  $columns['menu_order'] = 'menu_order';
  return $columns;
});
