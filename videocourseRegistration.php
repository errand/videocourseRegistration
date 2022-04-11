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
        'user_login'    => $data['userLogin'],
        'user_email'    => $data['userEmail'],
        'user_password' => $data['userPassword'],
        'first_name'    => $data['userFirstName'],
        'last_name'     => $data['userLastName'],
        'meta_input'           => [
            'userGender'       => $data['userGender'],
            'userKommune'      => $data['userKommune'],
            'userUnternehmen'  => $data['userCompany'],
            'userPrivatperson' => $data['userIndividual'],
            'videoTracking'    => '',
        ],
    ];

    wp_insert_user($userMetaData);
    wp_send_json($data['userKommune']);
    wp_die();
}
