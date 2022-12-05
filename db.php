<?php

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

function getVideoPostsIds() {
    $ids = get_posts(array(
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_type' => 'video',
    ));
    set_transient( 'videoIds', $ids, 60 * 24 );
    return $ids;
}

function getVideoPostsIdsInTerm($term_id) {
    $ids = get_posts(array(
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_type' => 'video',
        'tax_query' => array(
            array(
                'taxonomy' => 'videocourse',
                'field' => 'term_id',
                'terms' => $term_id,
            )
        )
    ));
    set_transient( 'getVideoPostsIdsInTerm', $ids, 60 * 24 );
    return $ids;
}

function countTotalTimeInTerm($term_id)
{
  $length = 0;

  $posts = getVideoPostsIdsInTerm($term_id);;
  foreach ($posts as $id) {
    $fid = get_post_meta($id, 'mp4', true);
    $length += getVideoLength($fid);
  }
    return $length;
}

/**
* Mass update posts number in Videocourse taxonomy per term
 * @return void
 */
function countPostsInVideoCourseTerms(): void {
    $terms = get_terms( array(
        'taxonomy' => 'videocourse',
        'hide_empty' => false,
    ) );

    foreach ($terms as $term) {
        $number = 0;
        $posts = getVideoPostsIdsInTerm($term->term_id);
        foreach ($posts as $id) {
            $number += 1;
        }
        update_field( 'numberOfVideosInTerm', $number, 'videocourse_'.$term->term_id);
    }
}

/**
* Count and save all posts video lenght in Videocourse taxonomy
 */

/**
 * Count and update length of videos in Videocourse terms and save it to ACF
 * @return void
 * */
function updateTotalTimeInVideocourseTaxonomy(): void
{
    $terms = get_terms( array(
        'taxonomy' => 'videocourse',
        'hide_empty' => false,
    ) );

    foreach ($terms as $term) {
        $length = 0;
        $posts = getVideoPostsIdsInTerm($term->term_id);
        foreach ($posts as $id) {

            $fid = get_post_meta($id, 'mp4', true);
            $length += getVideoLength($fid);
        }
        update_field( 'timeOfVideosInTerm', $length, 'videocourse_'.$term->term_id);
    }
}

function countCurrentTimeInTerm($tid) {
	global $wpdb;
	$count = 0;
	$table_name = $wpdb->prefix . "videocourse";
	$current_user = wp_get_current_user();
	$uid = $current_user->ID;
	$result = $wpdb->get_results("SELECT `current` FROM $table_name WHERE `user_id` = $uid AND `term_id` = $tid");
	foreach ($result as $row) {
		$count += $row->current;
	}
	return $count;
}

/**
 * Get video file length
 * @param int $fid
 * @return mixed|string
 */
function getVideoLength(int $fid)
{
  $meta = '';
  require_once( ABSPATH . 'wp-admin/includes/media.php' );

  if( function_exists( 'wp_read_video_metadata' ) ) {
    $file_path = get_attached_file( $fid ); // example attachment ID
    $meta = wp_read_video_metadata( $file_path );
  }
  return $meta['length'];
}

function getDoneVideoPerTerm($tid) {
  $count = 0;
  $posts = getVideoPostsIdsInTerm($tid);

  foreach ($posts as $id) {
    if(getVideoDone($id, 0)) {
      $count += 1;
    }
  }
  return $count;
}
// Should be deprecated
function getDoneVideoTotal() {
    $count = 0;
    $posts = Timber::get_posts(array(
        'posts_per_page' => -1,
        'post_type' => 'video'
    ));
    foreach ($posts as $post) {
        if(getVideoDone($post->id, 0)) {
            $count += 1;
        }
    }
    return $count;
}

/*function countPercentage() {
    $totalVideos = get_field('global_Video Course_totalVideos', 'option');
    return getDoneVideoTotal() * 100 / $totalVideos;
}*/

/*function updateUsersVideoDoneStats() {

}*/


/**
* Count Video post type
 * @return int
 */
function getVideoPostsTotal(): int {
    $posts = getVideoPostsIds();
    return count($posts);
}

/**
 * Count all videos length
 * @return int
 */
function countTotalTime(): int
{
    $length = 0;

    $posts = getVideoPostsIds();
    foreach ($posts as $id) {
        $fid = get_post_meta($id, 'mp4', true);
        $postLength = getVideoLength($fid);
        $length += $postLength;
        update_field( 'videoLength', $postLength, 'post_'.$id);
    }
    return $length;
}

/**
 * Count all current watched time for each user
 */

function countAllCurrentTime() {
    global $wpdb;
    $users = get_users( array( 'fields' => array( 'ID' ) ) );
    foreach($users as $user){
        $count = 0;
        $table_name = $wpdb->prefix . "videocourse";
        $uid = $user->ID;

        $result = $wpdb->get_results("SELECT `current` FROM $table_name WHERE `user_id` = $uid");
        foreach ($result as $row) {
            $count += $row->current;
        }
        update_field( 'timeOfWatchedVideos', $count, 'user_'.$uid);
    }
}

/**
 * Mass update all users ACF watched videos with aggregated data
 * @return void
 */

function updateUsersWatchedVideosACF(): void {
    $users = get_users( array( 'fields' => array( 'ID' ) ) );
    $posts = getVideoPostsIds();
    foreach($users as $user){
        $count = 0;
        foreach ($posts as $id) {
            if(getVideoDone($id, $user->ID)) {
                $count += 1;
            }
        }
        update_field( 'numberOfWatchedVideos', $count, 'user_'.$user->ID);
    }
}

/**
 * Count Video post type
 */
function countAllVideosAndUpdateACF() {
    $number = getVideoPostsTotal();
    $totalTime = countTotalTime();
    update_field('global_Video Course_totalVideos', $number, 'option');
    update_field('global_Video Course_totalVideoSeconds', $totalTime, 'option');
}

/**
 * Add WP crontab 5 minutes interval
 * */
add_filter( 'cron_schedules', 'add_five_minutes_cron_interval' );
function add_five_minutes_cron_interval( $schedules ) {
    $schedules['five_minutes'] = array(
        'interval' => 300,
        'display'  => esc_html__( 'Every Five Minutes' ), );
    return $schedules;
}

/**
 * Cron jobs to update Videocourse stats every 5 minutes
 */

add_action( 'videostats_fiveminutes_cron_hook', 'videostats_cron_fiveminutes_exec' );
function videostats_cron_fiveminutes_exec() {
    updateUsersWatchedVideosACF();
    countAllCurrentTime();
}

/**
 * Cron jobs to update Videocourse stats
 */
add_action( 'videostats_cron_hook', 'videostats_cron_exec' );
function videostats_cron_exec() {
    countAllVideosAndUpdateACF();
    updateTotalTimeInVideocourseTaxonomy();
    countPostsInVideoCourseTerms();
}

if ( ! wp_next_scheduled( 'videostats_cron_hook' ) ) {
    wp_schedule_event( time(), 'daily', 'videostats_cron_hook' );
}

if ( ! wp_next_scheduled( 'videostats_fiveminutes_cron_hook' ) ) {
    wp_schedule_event( time(), 'five_minutes', 'videostats_fiveminutes_cron_hook' );
}

/**
 * Check if Video(PID) has been watched by the user (UID)
 * @param int $post_id
 * @param int $uid
 * @return bool
*/

function getVideoDone(int $post_id, int $uid): bool
{
  //get done for video by id
  global $wpdb;
  $table_name = $wpdb->prefix . "videocourse";
  if($uid === 0) {
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
  }
  $result = $wpdb->get_results("SELECT `done` FROM $table_name WHERE `user_id` = $uid AND `post_id` = $post_id");
  if($result && $result[0]->done == 1) {
    return true;
  } else {
    return false;
  }
}

/**
 * Get list of users who watched current video
 * */
function getUsersDone($post_id)
{
  //get users who watched video by id
  global $wpdb;
  $table_name = $wpdb->prefix . "videocourse";
  $result = $wpdb->get_results("SELECT `user_id` FROM $table_name WHERE `post_id` = $post_id AND `done` = '1'");
  return $result;
}

function getVideoCurrentProgress($post_id, $uid = null)
{
  //get done for video by id
  global $wpdb;
  $table_name = $wpdb->prefix . "videocourse";
  if(is_null($uid)) {
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
  }
  $result = $wpdb->get_results("SELECT `current` FROM $table_name WHERE `user_id` = $uid AND `post_id` = $post_id");
  if($result) {
    return $result[0]->current;
  } else {
    return 0;
  }
}

/**
 * Ajax call to set Video done flag
 * */

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

  $wpdb->update($table_name, array('done' => 1), array('user_id' => $uid,'post_id' => $post_id));
  wp_send_json_success();
  wp_die();
}

/**
 * Update video status on Ajax calls
 * */

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


/**
 * Ajax helper function to add Video id for Current User in DB
 **/

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
    $result = $wpdb->get_results("SELECT `id` FROM $table_name WHERE `user_id` = $uid AND `post_id` = $post_id");
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
    } else {
        $data = [
            'term_id' => $terms[0]->term_id,
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

/**
 * Remove video from videocourse table when trash the post
 * */
function video_trash_action( $post_id ) {
  if ( 'video' != get_post_type( $post_id )) {
    return;
  }
  global $wpdb;
  $table = 'wp_videocourse';
  $wpdb->delete( $table, array( 'post_id' => $post_id ) );
}
add_action( 'trashed_post', 'video_trash_action' );


add_action("wp_ajax_addVideo", "addVideo");
add_action("wp_ajax_nopriv_addVideo", "addVideo");
add_action("wp_ajax_countTotalTimeInTerm", "countTotalTimeInTerm");
add_action("wp_ajax_nopriv_countTotalTimeInTerm", "countTotalTimeInTerm");
add_action("wp_ajax_renewVideoStatus", "renewVideoStatus");
add_action("wp_ajax_nopriv_renewVideoStatus", "renewVideoStatuse");
add_action("wp_ajax_getVideoDone", "getVideoDone");
add_action("wp_ajax_nopriv_getVideoDone", "getVideoDone");
add_action("wp_ajax_setVideoDone", "setVideoDone");
add_action("wp_ajax_nopriv_setVideoDone", "setVideoDone");

//maybe combine checkCurrentTime & renewVideoStatus... in javascript we must program right request
//and we can try to shorten code with global variables
