<?php
class RescanUploadTools {
	
	public function traverse_directory($serverroot, $target, &$results) {
		//echo $target . '<br>';
		if (is_file($serverroot.$target)) {
			array_push($results, $target);
		}
		if (is_dir ( $serverroot.$target )) {
			$files = glob ( $serverroot.$target . '*', GLOB_MARK ); // GLOB_MARK adds a slash to directories returned
			foreach ( $files as $file ) {
				$relpath = str_replace($serverroot, '', $file);
				$this->traverse_directory($serverroot, $relpath, $results );
			}
		}
	}
	
	public function find_orphans($files) {
		global $wpdb;
		$uploads = wp_upload_dir();
		$upload_url = $uploads['baseurl'];
		$orphans = array ();
		foreach ($files as $file) {
			// ignore files
			if (preg_match('/-[0-9]+x[0-9]+/', basename($file))) {
				echo "Ignoring file: " . $file ."<br>";
				continue;
			}
			//echo $file . "<br>";
			$guid = $upload_url . $file;
			//echo $guid . "<br>";
			$sql = "SELECT ID FROM $wpdb->posts AS posts WHERE guid='$guid';";
			$query_result = $wpdb->get_results ( $sql );
			if (empty ( $query_result)) {
				//echo "No post found for: " . $guid . "<br>";
				array_push($orphans,  $file);
			}
		}
		return $orphans;
	}
	
	public function create_attachment($orphan) {
		$uploads = wp_upload_dir();
		$upload_path = $uploads['basedir'];
		$upload_base_url = $uploads['baseurl'];
		
		$filename = $upload_path . $orphan;
		//echo "$filename<br>";
		// The ID of the post this attachment is for.
		$parent_post_id = 0;
		$filetype = wp_check_filetype( basename( $filename ), null );
		
		// Prepare an array of post data for the attachment.
		$attachment = array(
				'guid'           => $upload_base_url . $orphan,
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
		);
		
		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
		
		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		
		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		// Set thumbnail
		set_post_thumbnail( $parent_post_id, $attach_id );
	}
	
}
?>