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

    if ( !is_wp_error($user_id)) {
      wp_send_json_success();
    } else {
      wp_send_json_error($user_id->get_error_message());
    }
    //auto_login_new_user($user_id);
    wp_die();
}

/*function auto_login_new_user($user_id)
{
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    $user = get_user_by('id', $user_id);
    do_action('wp_login', $user->user_login);
}
add_action('user_register', 'auto_login_new_user');*/

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


add_action("wp_ajax_setUserAccept", "setUserAccept");
add_action("wp_ajax_nopriv_setUserAccept", "setUserAccept");

function setUserAccept()
{

  $user_id = $_POST['user_id'];

  update_field( 'acceptedNewsletter', true, 'user_'.$user_id);

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
    return '/videokurs';
}
add_filter( 'login_headerurl', 'my_login_logo_url' );

add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
  if (!current_user_can('edit_posts')) {
    show_admin_bar(false);
  }
}

/*
 * Add columns to video post list
 */
function add_acf_columns ( $columns ) {
  $columns['order'] = __( 'Order' );
   return $columns;
}
add_filter ( 'manage_video_posts_columns', 'add_acf_columns' );

/*
* Add columns to video post list
*/
function video_custom_column ( $column, $post_id ) {
  switch ( $column ) {
    case 'order':
      echo get_post_meta ( $post_id, 'order', true );
      break;
  }
}
add_action ( 'manage_video_posts_custom_column', 'video_custom_column', 10, 2 );


// Add info to the new columns
add_action( 'manage_videocourse_custom_column', 'show_videocourse_order_in_columns', 10, 3 );

function show_videocourse_order_in_columns( $string, $columns, $term_id ) {
    switch ( $columns ) {
        // in this example, we had saved some term meta as "genre-characterization"
        case 'order' :
            echo esc_html( get_term_meta( $term_id, 'order', true ) );
        break;
    }
}
add_filter( 'manage_edit-videocourse_columns', 'add_new_videocourse_columns' );

function add_new_videocourse_columns( $columns ) {
  $columns['order'] = __( 'order' );
  return $columns;
}

add_filter( 'manage_edit-videocourse_sortable_columns', 'add_new_videocourse_columns' );

add_filter("um_email_template_body_attrs", function( $css_atts ){
  return 'style="background: #fff;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;"';
});

/**
 * Add short tag {user_id}
 */
add_filter( 'um_template_tags_patterns_hook', 'my_template_tags_patterns', 10, 1 );
add_filter( 'um_template_tags_replaces_hook', 'my_template_tags_replaces', 10, 1 );

function my_template_tags_patterns( $search ) {
  $search[] = '{user_id}';
  return $search;
}
function my_template_tags_replaces( $replace ) {
  $replace[] = um_user( 'ID' );
  return $replace;
}

// Add acceptance column
function modify_user_columns($column_headers) {
  $column_headers['acceptedNewsletter'] = 'Accepted the newsletter';
  return $column_headers;
}
add_action('manage_users_columns', 'modify_user_columns');

function custom_admin_css() {
  echo '<style>
    .column-custom_field {width: 8%}
    </style>';
}
add_action('admin_head', 'custom_admin_css');

function user_accepted_newsletter($value, $column_name, $user_id) {
  if ( 'acceptedNewsletter' == $column_name ) {
    return get_field('acceptedNewsletter', 'user_'.$user_id) ? 'Yes' : '';
  }

  return $value;
}
add_action('manage_users_custom_column', 'user_accepted_newsletter', 10, 3);
add_filter('manage_users_sortable_columns', 'modify_user_columns');

add_action( 'um_after_email_confirmation', 'send_welcome', 10, 1 );
function send_welcome( $user_id ) {
  um_fetch_user($user_id);
  UM()->mail()->send( um_user( 'user_email' ), 'approved_email' );
}

function filter_acf_relationship ($args, $field, $post_id) {
  $args['post_status'] = 'publish'; return $args;
}
add_filter('acf/fields/post_object/query', 'filter_acf_relationship', 10, 3);


// CATEGORY TEXT EDITOR --- START
if( is_admin() ) {
// LETS REMOVE THE HTML FILTERING
    remove_filter( 'pre_term_description', 'wp_filter_kses' );
    remove_filter( 'term_description', 'wp_kses_data' );

// LETS ADD OUR NEW CAT DESCRIPTION BOX
    add_filter('edit_tag_form_fields', 'filter_wordpress_category_editor');
    function filter_wordpress_category_editor($tag) {
        ?>
        <table class="form-table">
            <tr class="form-field">
                <th scope="row" valign="top"><label for="description"><?php _ex('Description', 'Taxonomy Description'); ?></label></th>
                <td>
                    <?php
                    $settings = array('wpautop' => true, 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => '15', 'textarea_name' => 'description' );
                    wp_editor(html_entity_decode($tag->description , ENT_QUOTES, 'UTF-8'), 'description1', $settings);
                    ?>
                    <br />
                    <span class="description"><?php _e('The description is not prominent by default; however, some themes may show it.'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

// HIDE THE DEFAULT CAT DESCRIPTION BOX USING JQUERY
    add_action('admin_head', 'remove_default_category_description');
    function remove_default_category_description()
    {
        global $current_screen;
        if ( $current_screen->id == 'edit-tags' )
        {
            ?>
            <script type="text/javascript">
                jQuery(function($) {
                    $('textarea#description').closest('tr.form-field').remove();
                });
            </script>
            <?php
        }
    }
}
// CATEGORY TEXT EDITOR --- END
