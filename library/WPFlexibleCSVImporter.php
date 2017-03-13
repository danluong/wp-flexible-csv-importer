<?php

class WPFlexibleCSVImporter {

	private $action = 'admin.php?import=wp-flexible-csv-importer';

	public function renderMainScreen()
	{
        ?>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="<?php echo esc_url( add_query_arg( array( 'upload' => 1 ), $this->getAction() ) ); ?>">
			<p>
				<label for="upload"><?php _e( 'Choose a .csv file from your computer.', 'wp-flexible-csv-importer' ); ?></label><br />(<?php printf( __('Maximum size: %s' ), $size ); ?>)
			</p>
			<p>
				<input type="file" id="upload" name="import" size="25" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
				<?php wp_nonce_field( 'acsv-import-upload' ); ?>
			</p>
			<?php submit_button( __('Upload file and import'), 'wp-flexible-csv-importer' ); ?>
		</form>
        <?php

        // perform upload
		if ( isset( $_GET['upload'] ) ) {
            $this->doUpload();
        }

	}

    private function getAction() {
        return $this->action;
    }

    private function doUpload() { 
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo print_r($file['error']);
		} else if ( ! file_exists( $file['file'] ) ) {
			echo print_r('couldn\'t find export file - permissions?');
		}

		$csv_file = get_attached_file( $file['id'] );
        echo file_get_contents( $csv_file );
    }
}
