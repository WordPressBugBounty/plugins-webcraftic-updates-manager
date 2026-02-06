<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WUPMP_UpdateNotification
 */
class WUPMP_UpdateNotification {

	/**
	 * Send mails when plugins & themes updated or updates available
	 */
	static public function checkUpdatesMail() {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-admin/includes/plugin.php'; //require for get_plugins()
		$notify_update_available = WUPM_Plugin::app()->getPopulateOption( 'notify_update_available' );
		$notify_updated          = WUPM_Plugin::app()->getPopulateOption( 'notify_updated' );

		if ( $notify_update_available ) {
			// check available updates
			self::listThemeUpdates();
			self::listPluginUpdates();
		}
		// check completed updates
		if ( $notify_updated ) {
			self::listUpdated();
		}
	}


	/**
	 * @return string[] get recipient emails
	 */
	static private function getEmails() {
		$email_array = [];

		$notify_email = WUPM_Plugin::app()->getPopulateOption( 'notify_email' );
		if ( $notify_email == '' ) {
			array_push( $email_array, get_option( 'admin_email' ) );
		} else {
			$list = explode( ", ", $notify_email );
			foreach ( $list as $email ) {
				array_push( $email_array, $email );
			}
		}

		return $email_array;
	}

	/**
	 * message for updates available
	 */
	static private function pendingMessage( $single, $plural ) {

		return sprintf( esc_html__( 'Available updates for follow plugins on your site %2$s.', 'webcraftic-updates-manager' ), $single, get_site_url(), $plural );
	}

	/**
	 * message for updated
	 */
	static private function updatedMessage( $type, $updatedList ) {

		$text = sprintf( esc_html__( 'The following %1$s have been updated:', 'webcraftic-updates-manager' ), $type, get_site_url() );

		$text .= $updatedList;

		return $text;
	}

	/**
	 * send mails if theme updates available
	 */
	static private function listThemeUpdates() {

		$update_mode        = WUPM_Plugin::app()->getPopulateOption( 'theme_updates' );
		$auto_update_themes = 'enable_theme_auto_updates' == $update_mode;

		if ( ! $auto_update_themes ) {

			require_once ABSPATH . '/wp-admin/includes/update.php';
			$themes = get_theme_updates();

			if ( ! empty( $themes ) ) {

				$subject     = '[' . get_bloginfo( 'name' ) . '] ' . __( 'Theme update available.', 'webcraftic-updates-manager' );
				$type        = __( 'theme', 'webcraftic-updates-manager' );
				$type_plural = __( 'themes', 'webcraftic-updates-manager' );
				$message     = self::pendingMessage( $type, $type_plural );

				foreach ( self::getEmails() as $key => $email ) {
					wp_mail( $email, $subject, $message );
				}
			}
		}
	}

	/**
	 * send mails if plugin updates available
	 */
	static private function listPluginUpdates() {
		$update_mode         = WUPM_Plugin::app()->getPopulateOption( 'plugin_updates' );
		$auto_update_plugins = 'enable_plugin_auto_updates' == $update_mode;

		if ( ! $auto_update_plugins ) {

			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			$plugins = get_plugin_updates();

			if ( ! empty( $plugins ) ) {

				$subject     = '[' . get_bloginfo( 'name' ) . '] ' . __( 'Plugin update available.', 'webcraftic-updates-manager' );
				$type        = __( 'plugin', 'webcraftic-updates-manager' );
				$type_plural = __( 'plugins', 'webcraftic-updates-manager' );
				$message     = self::pendingMessage( $type, $type_plural );

				foreach ( self::getEmails() as $key => $email ) {
					wp_mail( $email, $subject, $message );
				}
			}
		}
	}


	/**
	 * send mails if plugin has been updated
	 */
	static private function listUpdated() {

		// Create arrays
		$plugin_names   = [];
		$plugin_dates   = [];
		$plugin_version = [];
		$theme_names    = [];
		$theme_dates    = [];

		// Where to look for plugins
		$plugdir     = WP_PLUGIN_DIR;
		$all_plugins = get_plugins();

		// Where to look for themes
		$themedir   = get_theme_root();
		$all_themes = wp_get_themes();

		// Loop trough all plugins
		foreach ( $all_plugins as $key => $value ) {

			// Get plugin data
			$full_path   = $plugdir . '/' . $key;
			$get_file    = $path_parts = pathinfo( $full_path );
			$plugin_data = get_plugin_data( $full_path );

			// Get last update date
			$file_date  = date( 'YmdHi', filemtime( $full_path ) );
			$mail_sched = wp_get_schedule( 'wbcr_upmp_mail_updates' );

			if ( $mail_sched == 'hourly' ) {
				$lastday = date( 'YmdHi', strtotime( '-1 hour' ) );
			} else if ( $mail_sched == 'twicedaily' ) {
				$lastday = date( 'YmdHi', strtotime( '-12 hours' ) );
			} else if ( $mail_sched == 'daily' ) {
				$lastday = date( 'YmdHi', strtotime( '-1 day' ) );
			}

			if ( $file_date >= $lastday ) {

				// Get plugin name
				foreach ( $plugin_data as $data_key => $data_value ) {
					if ( $data_key == 'Name' ) {
						array_push( $plugin_names, $data_value );
					}
					if ( $data_key == 'Version' ) {
						array_push( $plugin_version, $data_value );
					}
				}

				array_push( $plugin_dates, $file_date );
			}
		}

		foreach ( $all_themes as $key => $value ) {

			$full_path = $themedir . '/' . $key;
			$get_file  = $path_parts = pathinfo( $full_path );

			$date_format = get_option( 'date_format' );
			$file_date   = date( 'YmdHi', filemtime( $full_path ) );
			$mail_sched  = wp_get_schedule( 'wbcr_upmp_mail_updates' );

			if ( $mail_sched == 'hourly' ) {
				$lastday = date( 'YmdHi', strtotime( '-1 hour' ) );
			} else if ( $mail_sched == 'twicedaily' ) {
				$lastday = date( 'YmdHi', strtotime( '-12 hours' ) );
			} else if ( $mail_sched == 'daily' ) {
				$lastday = date( 'YmdHi', strtotime( '-1 day' ) );
			}

			if ( $file_date >= $lastday ) {
				array_push( $theme_names, $path_parts['filename'] );
				array_push( $theme_dates, $file_date );
			}
		}

		$total_num_p    = 0;
		$total_num_t    = 0;
		$updated_list_p = '';
		$updated_list_t = '';

		foreach ( $plugin_dates as $key => $value ) {

			$updated_list_p .= "- " . $plugin_names[ $key ] . " to version " . $plugin_version[ $key ] . "\n";
			$total_num_p ++;
		}
		foreach ( $theme_names as $key => $value ) {

			$updated_list_t .= "- " . $theme_names[ $key ] . "\n";
			$total_num_t ++;
		}

		// If plugins have been updated, send email
		if ( $total_num_p > 0 ) {

			$subject = '[' . get_bloginfo( 'name' ) . '] ' . __( 'Plugins have been updated.', 'webcraftic-updates-manager' );
			$type    = __( 'plugins', 'webcraftic-updates-manager' );
			$message = self::updatedMessage( $type, "\n" . $updated_list_p );

			foreach ( self::getEmails() as $key => $email ) {
				wp_mail( $email, $subject, $message );
			}
		}

		// If themes have been updated, send email
		if ( $total_num_t > 0 ) {

			$subject = '[' . get_bloginfo( 'name' ) . '] ' . __( 'Themes have been updated.', 'webcraftic-updates-manager' );
			$type    = __( 'themes', 'webcraftic-updates-manager' );
			$message = self::updatedMessage( $type, "\n" . $updated_list_t );

			foreach ( self::getEmails() as $key => $email ) {
				wp_mail( $email, $subject, $message );
			}
		}
	}
}