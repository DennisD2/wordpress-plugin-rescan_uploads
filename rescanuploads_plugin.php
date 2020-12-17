<?php
/*
 * Plugin Name: RescanUpload
 * Plugin URI: http://spurtikus.de/
 * Description: Rescan Uploads Folder
 * Version: 1.0
 * Author: Spurtikus
 * Author URI: http://spurtikus.de/
 * Update Server: http://spurtikus.de/wp-content/download/wp/
 * Min WP Version: 1.5
 * Max WP Version: 2.0.4
 */
include 'rescanuploads_tools.php';

/**
 * register action
 */
add_action ( 'admin_menu', 'create_rescanuploads_page' );

/**
 * register page creation function
 */
function create_rescanuploads_page() {
	add_management_page ( 'Rescan Uploads', 'Rescan Uploads', 'edit_published_posts', 'slug', 'rescanuploads_execute' );
}

/**
 * page creation
 */
function rescanuploads_execute() {
	if (! current_user_can ( 'edit_published_posts' )) {
		wp_die ( __ ( 'You do not have sufficient permissions to access this page.' ) );
	}
	$uploads = wp_upload_dir();
	$upload_path = $uploads['basedir']; 
	$upload_url = $uploads['baseurl'];
	
	echo '<div class="wrap">';
	
	// Check whether the button has been pressed AND also check the nonce
	if (isset($_POST['find_orphans_button']) && check_admin_referer('find_orphans_button_clicked')) {
		// the button has been pressed AND we've passed the security check
		find_orphans_button_action();
	} else {
		echo "<h2>Rescan Uploads</h2>";
		echo "<p>This plugin rescans your uploads directory. For new media files found there, ";
		echo "a post is created in the database. This means that the new media files can be seen ";
		echo "in your media library.<br>";
		echo "This plugin does not check if these media files are needed (referenced) by any posts. ";
		echo "It simply adds all newly found media files to the media library.</p>";
		echo "<p>Because every file in the uploads folder is checked, the plugin may require to ";
		echo "run longer than the timeout limit of your wordpress server, which may be as low as 30 seconds. ";
		echo "In these cases, the execution ";
		echo "is interrupted after the timeout value. If you experience that, just restart the ";
		echo "plugin as long as it found new media files.</p>";
		echo "Upload path: $upload_path<br>";
		echo "Upload URL: $upload_url<br>";
		
		echo '<form action="tools.php?page=slug" method="post">';
		// this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
		wp_nonce_field('find_orphans_button_clicked');
		echo '<input type="hidden" value="true" name="find_orphans_button" />';
		submit_button('Find New Media In Uploads Folder');
		echo '</form>';
	}
	echo '</div>';
}

function find_orphans_button_action() {
	$uploads = wp_upload_dir();
	$upload_path = $uploads['basedir'];
	$upload_url = $uploads['baseurl'];
	
	echo '<div id="message" class="updated fade"><p>'
			.'Scanning for new media ...' . '</p></div>';

	echo '<div class="wrap">';
	echo '<p>Execute!</p>';
	$orphans = find_orphans( $upload_path, '' );
	foreach ($orphans as $orphan) {
		//echo "No post found for: " . $orphan . "<br>";
		$guid = $upload_url . $orphan;
		echo "No post found for: " . $guid . "<br>";
	}
	
	create_attachments($orphans);
	
	echo '<p>Bang!</p>';
	echo '</div>';
}  

function find_orphans($serverroot, $target) {
	$tools = new RescanUploadTools();
	$results = array ();
	echo "Scanning directory: " . $serverroot.$target."<br>";
	$tools->traverse_directory($serverroot, $target, $results);
	echo "Files found: ". sizeof($results) ."<br><br>";	
	$orphans = $tools->find_orphans($results);
	echo "<br>New media files found: ". sizeof($orphans) ."<br>";
	return $orphans;
}

function create_attachments($orphans) {
	$tools = new RescanUploadTools();
	foreach ($orphans as $orphan) {
		echo "Adding media: $orphan<br>";
		$tools->create_attachment($orphan);
	}
}

?>
