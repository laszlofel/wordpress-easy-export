<?php

/**
 * Plugin name: LF Backup
 * Description: Plugin for backing up the system.
 * Author: Laszlo Felfoldi
 * Version: 0.0.1
 */

require_once( __DIR__ . '/classes/rewrites.php' );

class LFBackup {

	private static $instance;
	public static function getInstance() {
		if ( !( self::$instance instanceof LFBackup ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		$this->apiKey();
		
		LFBackupRewrites::getInstance();

	}

	public function adminMenu() {
		add_management_page( 'Backup API', 'Backup API', 'manage_options', 'lf-backup', [ $this, 'settingsPage' ] );
	}

	public function settingsPage() {
		
		$key = get_option( 'lf_backup_api_key', false );

		?>
			<div class="wrap">
				<h2>LF Backup</h2>
				<div id="poststuff">
					<p>Use this API key when calling endpoints.</p>
					<input class="widefat" type="text" readonly value="<?php echo $key ?>" />
				</div>
			</div>
		<?php
	}

	private function apiKey() {

		require_once( ABSPATH . '/wp-includes/pluggable.php' );

		if ( empty( get_option( 'lf_backup_api_key', false ) ) ) {
			update_option( 'lf_backup_api_key', wp_generate_password( 64, false ) );
		}

	}

}

LFBackup::getInstance();