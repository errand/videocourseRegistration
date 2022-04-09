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

    wp_create_user($data['userLogin'], $data['userPassword'], $data['userEmail']);
    $user = get_user_by('slug', $data['userLogin']);
    $user_id = $user->ID;
    //updateUserMeta($user_id, $data)
    wp_send_json('done');
    wp_die();
}

/* add meta data to meta fields */
function updateUserMeta($user_id, $data)
{
    //foreach ($metadata as $key => $value) {
      //  update_user_meta($user_id, $key, $value);
    //}
}
