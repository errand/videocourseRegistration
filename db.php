<?php

use Timber\PostQuery;
use Timber\Timber;

global $videocourse_db_version;
$videocourse_db_version = "1.0";


function videocourse_install()
{
    global $wpdb;
    global $videocourse_db_version;
    $table_name = $wpdb->prefix . "videocourse";

    $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE {$table_name}(
	  id int(11) NOT NULL AUTO_INCREMENT,
	  user_id int(11) NOT NULL,
	  term_id int(11) NOT NULL,
	  post_id int(11) NOT NULL,
	  current float(11) NOT NULL,
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

function addVideo($pid)
{
    //some code to add video to db with user id by post id
    global $wpdb;
    $table_name = $wpdb->prefix . "videocourse";
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;

    if ($_POST['id']) {
        $post_id = $_POST['id'];
    } else {
        $post_id = $pid;
    }
    //get the course id by post id
    $terms = get_the_terms($post_id, 'videocourse');
    //check if recordings exist
    $result = $wpdb->get_results("SELECT * FROM $table_name WHERE `user_id` = $uid AND 'post_id' = $post_id");
    if (!$result) {
        //add video for this uid
        $data = [
            'user_id' => $uid,
            'term_id' => $terms[0]->term_id,
            'post_id' => $post_id,
            'current' => 0,
            'done'    => false
        ];
        $wpdb->insert($table_name, $data);
    }
    wp_send_json_success();
    wp_die();
}

function countTotalTime($pid) //maybe try to combine counting all videos for course and individual timing
{
    if ($_POST['id']) {
        $post_id = $_POST['id'];
    } else {
        $post_id = $pid;
    }

    $length = 0;
    $terms = get_the_terms($post_id, 'videocourse');
    $posts = Timber::get_posts(array(
        'posts_per_page' => -1,
        'post_type' => 'video',
        'orderby' => 'publish_date',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'videocourse',
                'field' => 'term_id',
                'terms' => $terms[0]->term_id,
            )
        )
    ));
    foreach ($posts as $post) {
        $fid = get_post_meta($post->ID, 'mp4', true);
        $current_length = getVideoLength($fid);
        $length = +$current_length;
    }
    wp_send_json($length);
    wp_die();
}

function getVideoLength($fid)
{
    $file_path = get_attached_file($fid);
    $meta = wp_read_video_metadata($file_path);
    $length = $meta['length'];
    return($length);
}

function renewVideoStatus()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "videocourse";
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;

    $post_id = $_POST['id'];
    //get term id ($term_id)
    $current = $_POST['current_time'];
    //renew status of video (current time, done) by id&uid with ajax
    $total = getVideoLength($post_id);
    if ($current == $total) {
        setVideoDone($post_id);
    } else {
        $data = [
            'current' => $current,
        ];
        $where = [
            'user_id' => $uid,
            'post_id' => $post_id,
        ];
        $wpdb->update($table_name, $data, $where);
    }

    wp_send_json_success();
    wp_die();
}

function forAllVideos()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "videocourse";
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
    //get overall progress
    //$result = $wpdb->get_results("SELECT * FROM $table_name WHERE `user_id` = $uid AND ???");
    //here we can think about pdf
}

function getVideoDone($post_id)
{
    //get done for video by id
    global $wpdb;
    $table_name = $wpdb->prefix . "videocourse";
    $current_user = wp_get_current_user();
    //$post_id = $_POST['id'];
    $uid = $current_user->ID;
    $result = $wpdb->get_results("SELECT `done` FROM $table_name WHERE `user_id` = $uid AND `post_id` = $post_id");
    return $result ? true : false;
}

function setVideoDone($pid)
{
    if ($_POST['id']) {
        $post_id = $_POST['id'];
    } else {
        $post_id = $pid;
    }
    //set done for video by id
    global $wpdb;
    $table_name = $wpdb->prefix . "videocourse";
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;

    $data = [
        'user_id' => $uid,
        'post_id' => $post_id,
        'done' => true,
    ];
    $wpdb->insert($table_name, $data);
    wp_send_json_success();
    wp_die();
}

add_action("wp_ajax_addVideo", "addVideo");
add_action("wp_ajax_nopriv_addVideo", "addVideo");
add_action("wp_ajax_countTotalTime", "countTotalTime");
add_action("wp_ajax_nopriv_countTotalTime", "countTotalTime");
add_action("wp_ajax_renewVideoStatus", "renewVideoStatus");
add_action("wp_ajax_nopriv_renewVideoStatus", "renewVideoStatuse");
add_action("wp_ajax_forAllVideos", "forAllVideos");
add_action("wp_ajax_nopriv_forAllVideos", "forAllVideos");
add_action("wp_ajax_getVideoDone", "getVideoDone");
add_action("wp_ajax_nopriv_getVideoDone", "getVideoDone");
add_action("wp_ajax_setVideoDone", "setVideoDone");
add_action("wp_ajax_nopriv_setVideoDone", "setVideoDone");

//maybe combine checkCurrentTime & renewVideoStatus... in javascript we must program right request
//and we can try to shorten code with global variables
