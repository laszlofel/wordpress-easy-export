<?php

require_once( __DIR__ . '/utilities.php' );

class LFBackupRewrites {

	private static $instance;
	public static function getInstance() {
		if ( !( self::$instance instanceof LFBackupRewrites ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action( 'query_vars', [ $this, 'queryVars' ] );
		add_action( 'init', [ $this, 'rewrites' ] );
		add_action( 'parse_request', [ $this, 'request' ] );

	}

	public function queryVars( $vars ) {

		$vars[] = 'lf-backup';
		return $vars;

	}

	public function rewrites() {
		add_rewrite_rule( '^lf-backup/?', 'index.php?lf-backup=1', 'top' );
	}

	public function request( $request ) {

		if ( $request->query_vars['lf-backup'] == 1 ) {
			
			/*if ( !( isset( $_POST['key'] ) && $_POST['key'] === $key ) ) {

				http_response_code(500);
				exit;

			}*/

			$backupPath =  __DIR__ . '/../backup-' . date( 'Y-m-d' ) . '.zip';

			LFBackupUtilities::compress( ABSPATH . '/wp-content', __DIR__ . '/../tmp/wp-content.zip' );
			LFBackupUtilities::export( __DIR__ . '/../tmp/export.sql' );
			LFBackupUtilities::compress( __DIR__ . '/../tmp', $backupPath );

			LFBackupUtilities::removeDirectory( __DIR__ . '/../tmp' );

			header( "Content-type: application/zip" ); 
		    header( "Content-Disposition: attachment; filename=" . basename( $backupPath ) ); 
		    header( "Pragma: no-cache" ); 
		    header( "Expires: 0" ); 
		    readfile( $backupPath );

		    unlink( $backupPath );

			exit;

		}

	}

}