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
        'user_login'            => $data['userLogin'],
        'user_email'            => $data['userEmail'],
        'user_pass'             => $data['userPassword'],
        'first_name'            => $data['userFirstName'],
        'last_name'             => $data['userLastName'],
        'show_admin_bar_front'  => false,
        'meta_input'            => [
            'userGender'       => $data['userAnrede'],
            'userKommune'      => $data['userKommune'],
            'userUnternehmen'  => $data['userCompany'],
            'userPrivatperson' => $data['userIndividual'],
            'videoTracking'    => '',
        ],
    ];

    $user_id = wp_insert_user($userMetaData);
    auto_login_new_user($user_id);
    wp_send_json($user_id);
    wp_die();
}

function auto_login_new_user($user_id)
{
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    $user = get_user_by('id', $user_id);
    do_action('wp_login', $user->user_login);
    return;
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
        wp_send_json_success();
    }

    die();
}
