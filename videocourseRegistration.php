<?php
/*
Plugin Name: Stadtlabore VideoCourse Registration
Plugin URI:
Description: Ajax register and tracking
Version: 1.0
Author: Aleksandr Shatskikh
*/

use Timber\PostQuery;

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
        'user_login'           => $data['userLogin'],
        'user_email'           => $data['userEmail'],
        'user_password'        => $data['userPassword'],
        'first_name'           => $data['userFirstName'],
        'last_name'            => $data['userLastName'],
        'meta_input'           => [
            'userGender' => $data['userGender'],
            'userKommune' => $data['userKommune'],
            'Unternehmen' => '',
            'videoTracking' => '',
        ],
    ];

    wp_insert_user($userMetaData);
    wp_send_json($data['userKommune']);
    wp_die();
}

add_action("wp_ajax_logoutUser", "logoutUser");
add_action("wp_ajax_nopriv_logoutUser", "logoutUser");

function logoutUser(){
  wp_logout();
  ob_clean();
  wp_send_json_success();
}

add_action("wp_ajax_loginUser", "loginUser");
add_action("wp_ajax_nopriv_loginUser", "loginUser");

function loginUser(){

  $data = array();
  $data['user_login'] = $_POST['login'];
  $data['user_password'] = $_POST['password'];

  $user_signon = wp_signon( $data, false );
  if ( is_wp_error($user_signon) ){
    wp_send_json(json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.'))));
  } else {
    wp_send_json(json_encode(array('loggedin'=>true, 'message'=>__('Good to go'))));
  }

  die();
}
