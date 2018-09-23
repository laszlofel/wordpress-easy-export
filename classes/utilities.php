<?php

class LFBackupUtilities {

	public static function compress( $source, $destination = false ) {

		$source = realpath( $source );
		if ( false === $source ) {
			return false;
		}

		if ( false === $destination ) {
			$destination = basename( $source ) . '.zip';
		}

		if ( !is_dir( dirname( $destination ) ) ) {
			mkdir( dirname( $destination ), null, true );
		}

		$destination = dirname( $destination ) . '/' . basename( $destination );

		$zip = new \ZipArchive();
		if ( $zip->open( $destination, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE ) ) {

			if ( is_file( $source ) ) {
				$zip->addFromString( basename( $source ), file_get_contents( $source ) );
			} else if ( is_dir( $source ) ) {

				$iterator = new \RecursiveDirectoryIterator( $source );
				$iterator->setFlags( \RecursiveDirectoryIterator::SKIP_DOTS );
				$files = new \RecursiveIteratorIterator( $iterator, \RecursiveIteratorIterator::SELF_FIRST );
				
				foreach ( $files as $file ) {

				    $file = realpath( $file );
				    if ( is_dir( $file ) ) {
				        $zip->addEmptyDir( str_replace( $source . DIRECTORY_SEPARATOR, '', $file . '/' ) );
				    } else if ( is_file( $file ) ) {
				        $zip->addFromString( str_replace( $source . DIRECTORY_SEPARATOR, '', $file ), file_get_contents( $file ) );
				    }

				}

			}

		}

		return $zip->close();

	}

	public static function export( $destination = false ) {

		$tables = self::tables();

		$sql = '';
		foreach( $tables as $table ) {

			$table = $table[0];

			$create = str_replace( 'CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', self::createStatement( $table ) );
			$sql .=  "$create;\n\n";

			$rows = self::rows( $table );

			if ( count( $rows ) > 0 ) {

				$sql .= "INSERT IGNORE INTO `$table` (";
				
				$fields = self::fields( $table );			
				$fieldsArray = [];
				foreach( $fields as $field ) {
					$fieldsArray[] = "`" . $field->Field . "`";
				}
				$sql .= implode( ', ', $fieldsArray ) . ") VALUES";

				$rowsArray = [];
				foreach( $rows as $i => $row ) {

					if ( $i > 0 && $i%50 == 0 ) {
						$sql .= implode( ', ', $rowsArray ) . ";\n";
						$sql .= "INSERT IGNORE INTO `$table` (" . implode( ', ', $fieldsArray ) . ") VALUES";
						$rowsArray = [];
					}
					
					$values = [];
					foreach( $row as $key => $value ) {
						$values[] = is_numeric( $value ) ? $value : "'" . esc_sql( $value ) . "'";
					}
					$rowsArray[] = "\n(" . implode( ', ', $values ) . ")";
				} 

				$sql .= implode( ', ', $rowsArray ) . ";\n\n";

			}

		}

		if ( false === $destination ) {
			$destination = 'export.sql';
		}
		$destination = dirname( $destination ) . '/' . basename( $destination );

		if ( !is_dir( dirname( $destination ) ) ) {
			mkdir( dirname( $destination ), null, true );
		}

		file_put_contents( $destination, $sql );
		return $destination;

	}

	private static function tables() {

		global $wpdb;
		return $wpdb->get_results( "SHOW TABLES;", ARRAY_N );		

	}

	private static function createStatement( $table ) {

		global $wpdb;
		return $wpdb->get_results( "SHOW CREATE TABLE $table;", ARRAY_N )[0][1];

	}

	private static function rows( $table ) {

		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM $table;", ARRAY_N );

	}

	private static function fields( $table ) {

		global $wpdb;
		return $wpdb->get_results( "SHOW COLUMNS FROM $table" );

	}

	public static function removeDirectory( $path ) {

		$path = realpath( $path );
		if ( false === $path ) return false;

		$files = glob( $path . '/*' );
		foreach ( $files as $file ) {
			is_dir( $file ) ? removeDirectory( $file ) : unlink( $file );
		}
		rmdir( $path );
	 	return true;
	}

}