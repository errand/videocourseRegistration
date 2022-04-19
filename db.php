<?php

global $videocourse_db_version;
$videocourse_db_version = "1.0";
$table_name = $wpdb->prefix . "videocourse";

function videocourse_install()
{
    global $wpdb;
    global $videocourse_db_version;

    $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE {$table_name}(
	  id int(11) NOT NULL AUTO_INCREMENT,
	  user_id int(11) NOT NULL,
	  term_id int(11) NOT NULL,
	  post_id int(11) NOT NULL,
	  current int(11) NOT NULL,
	  total int(11) NOT NULL,
	  done bool,
 	  UNIQUE KEY id (id),
 	  KEY user_id (user_id),
 	  KEY term_id (term_id)
	){$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        //$rows_affected = $wpdb->insert($table_name, array());

        add_option("videocourse_db_version", $videocourse_db_version);
    }
}

function addAllVideos($id)
{
    //some code to add all videos from course to db with user id by course id
    global $wpdb;
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
    //check if recordings exist
    $result = $wpdb->get_results("SELECT * FROM $table_name WHERE `user_id` = $uid AND 'term_id' = $id");
    //if not - add an entry of all videos
    if (!$result) {
        //get posts by term_id
        //foreach ($data as $key => $value) {
        //add videos into table for this uid
        //}
    }
    wp_send_json_success();
    wp_die();
}

function addVideo($id)
{
    //some code to add video to db with user id by video id
    global $wpdb;
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
    //check if recordings exist
    $result = $wpdb->get_results("SELECT * FROM $table_name WHERE `user_id` = $uid AND 'post_id' = $id");
    //if not - add an entry of this
    if (!$result) {
      //add video for this uid
    }
    wp_send_json_success();
    wp_die();
}

function countTotalTime($id)
{
    //some code to count total time of videos from course by course id
    global $wpdb;
    //cycle of videos by course id to count total time
    //ajax
}

function checkCurrentTime($id)
{
    global $wpdb;
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
    //check 'done'
    //total_time - current_time
    wp_send_json_success();
    wp_die();
}

function renewVideoStatus($id)
{
    global $wpdb;
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
    //renew status of video (current time, done) by id with ajax
    wp_send_json_success();
    wp_die();
}

add_action("wp_ajax_addAllVideos", "addAllVideos");
add_action("wp_ajax_nopriv_addAllVideos", "addAllVideos");
add_action("wp_ajax_addVideo", "addVideo");
add_action("wp_ajax_nopriv_addVideo", "addVideo");
add_action("wp_ajax_checkCurrentTime", "checkCurrentTime");
add_action("wp_ajax_nopriv_checkCurrentTime", "checkCurrentTime");
add_action("wp_ajax_renewVideoStatus", "renewVideoStatus");
add_action("wp_ajax_nopriv_renewVideoStatus", "renewVideoStatuse");

//maybe combine checkCurrentTime & renewVideoStatus... in javascript we must programming right request
