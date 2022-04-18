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
	  current int(11) NOT NULL,
	  total int(11) NOT NULL,
	  UNIQUE KEY id (id)
	){$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        //$rows_affected = $wpdb->insert($table_name, array());

        add_option("videocourse_db_version", $videocourse_db_version);
    }
}
