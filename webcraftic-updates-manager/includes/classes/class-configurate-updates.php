<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WUPM_PLUGIN_DIR . '/admin/includes/class-plugin-filters.php';

/**
 * This class configures the parameters seo
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2017 Webraftic Ltd
 */
class WUPM_ConfigUpdates extends WBCR\Factory_Templates_137\Configurate {

	public function registerActionsAndFilters() {
		/**
		 * Plugin updates
		 */
		$plugins_update = $this->getPopulateOption( 'plugin_updates' );
		$theme_update = $this->plugin->getPopulateOption( 'theme_updates' );
		$wp_core_update = $this->plugin->getPopulateOption( 'wp_update_core' );

		switch ( $plugins_update ) {
			case 'disable_plugin_updates':
				// and disable version check
				add_filter( 'site_transient_update_plugins', [ $this, 'lastCheckedNow' ], 50 );
				add_action( 'admin_init', [ $this, 'adminInitForPlugins' ] );
				add_filter( 'auto_update_plugin', '__return_false' );
				break;
			case 'enable_plugin_auto_updates':
				// exclude some plugins in update list
				add_filter( 'auto_update_plugin', [ $this, 'pluginsAutoUpdate' ], 50, 2 );
				break;
		}

		if ( $plugins_update != 'disable_plugin_updates' ) {
			add_filter( 'site_transient_update_plugins', [ $this, 'disablePluginNotifications' ], 50 );
			add_filter( 'http_request_args', [ $this, 'httpRequestArgsRemovePlugins' ], 5, 2 );
		}

		/**
		 * Theme updates
		 */
		switch ( $theme_update ) {
			case 'disable_theme_updates':
				add_filter( 'site_transient_update_themes', [ $this, 'lastCheckedNow' ], 50 );
				add_action( 'admin_init', [ $this, 'adminInitForThemes' ] );
				add_filter( 'auto_update_theme', '__return_false' );
				break;
			case 'enable_theme_auto_updates':
				add_filter( 'auto_update_theme', '__return_true', 1 );
				break;
		}

		/**
		 * disable wp default translation update
		 */

		if ( $this->getPopulateOption( 'auto_tran_update' ) ) {
			add_filter( 'auto_update_translation', '__return_false', 1 );
		}

		/**
		 * control WP Auto core update
		 */

		switch ( $wp_core_update ) {
			case 'disable_core_updates':
				$this->disableAllCoreUpdates();
				break;
			case 'disable_core_auto_updates':
				add_filter( 'allow_major_auto_core_updates', '__return_false' );
				add_filter( 'allow_dev_auto_core_updates', '__return_false' );
				add_filter( 'allow_minor_auto_core_updates', '__return_false' );
				break;
			case 'major':
				add_filter( 'allow_major_auto_core_updates', '__return_true' );
				break;
			case 'development':
				add_filter( 'allow_dev_auto_core_updates', '__return_true' );
				break;
			default:
				add_filter( 'allow_minor_auto_core_updates', '__return_true' );
				break;
		}

		/**
		 * disable wp default translation update
		 */
		if ( $this->getPopulateOption( 'enable_update_vcs' ) ) {
			add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );
		}

		/**
		 * disable updates nags for all users except admin
		 */
		if ( $this->getPopulateOption( 'updates_nags_only_for_admin' ) && ! current_user_can( 'update_core' ) ) {
			remove_action( 'admin_notices', 'update_nag', 3 );
		}

		add_action( 'schedule_event', [ $this, 'filterCronEvents' ] );

		if ( $theme_update != 'disable_theme_updates' ) {
			add_filter( 'site_transient_update_themes', [ $this, 'disableThemeNotifications' ], 50 );
			add_filter( 'http_request_args', [ $this, 'httpRequestArgsRemoveThemes' ], 5, 2 );
		}

		add_filter( 'site_transient_update_plugins', [ $this, 'disablePluginTranslationUpdates' ], 50 );
		add_filter( 'site_transient_update_themes', [ $this, 'disableThemeTranslationUpdates' ], 50, 1 );

		/**
		 * if off email notifications disabled for wp core updates
		 */
		if ( $wp_core_update != 'disable_core_updates' ) {
			if ( $this->plugin->getPopulateOption( 'disable_core_notifications' ) ) {
				add_filter( 'auto_core_update_send_email', '__return_true' );
			} else {
				add_filter( 'auto_core_update_send_email', '__return_false' );
			}
		}

		add_action( 'all_plugins', [ $this, 'hidePlugins' ], 10, 1 );
		add_filter( 'wp_get_update_data', [ $this, 'hidePluginUpdateCount' ], 10, 2 );

		/**
		 * check updates and send mails
		 */
		// is main site of network
		if ( ! is_multisite() or ( function_exists( 'get_network' ) and $_SERVER['SERVER_NAME'] === get_network()->domain ) ) {
			require_once WUPM_PLUGIN_DIR . "/includes/classes/class-update-notification.php";
			add_action( 'wbcr_upmp_mail_updates', [ 'WUPMP_UpdateNotification', 'checkUpdatesMail' ] );
		}
	}

	/**
	 * Filter cron events
	 *
	 * @param $event
	 *
	 * @return bool
	 */
	public function filterCronEvents( $event ) {
		$core_updates = $this->getPopulateOption( 'wp_update_core' ) == 'disable_core_updates';
		//$plugins_updates = $this->getPopulateOption('plugin_updates') == 'disable_plugin_updates';
		$themes_updates = $this->getPopulateOption( 'theme_updates' ) == 'disable_theme_updates';

		if ( ! is_object( $event ) || empty( $event->hook ) ) {
			return $event;
		}

		switch ( $event->hook ) {
			case 'wp_version_check':
				$event = $core_updates ? false : $event;
				break;

			case 'wp_update_themes':
				$event = $themes_updates ? false : $event;
				break;
			case 'wp_maybe_auto_update':
				$event = $core_updates ? false : $event;
				break;
		}

		return $event;
	}

	/**
	 * Enables plugin automatic updates on an individual basis.
	 *
	 * @param bool   $update   Whether the item has automatic updates enabled
	 * @param object $item     Object holding the asset to be updated
	 *
	 * @return bool True of automatic updates enabled, false if not
	 */
	public function pluginsAutoUpdate( $update, $item ) {

		// Fix php warnings UM-29:
		// Some users submit reports on php notifications "Undefined property: stdClass::$plugin"
		if ( ! is_object( $item ) || ! isset( $item->plugin ) ) {
			return false;
		}

		$slug_parts  = explode( '/', $item->plugin );
		$actual_slug = array_shift( $slug_parts );

		$pluginFilters = new WUPM_PluginFilters( $this->plugin );
		$filters       = $pluginFilters->getFilters( [ $actual_slug ] );

		if ( ! empty( $filters ) ) {
			if ( isset( $filters['disable_auto_updates'][ $actual_slug ] ) and $filters['disable_auto_updates'][ $actual_slug ] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Disables plugin updates on an individual basis.
	 *
	 * @param object $plugins   Plugins that may have update notifications
	 *
	 * @return object Updated plugins list with updates
	 */
	public function disablePluginNotifications( $plugins ) {
		if ( ! isset( $plugins->response ) || empty( $plugins->response ) ) {
			return $plugins;
		}

		$pluginFilters = new WUPM_PluginFilters( $this->plugin );

		foreach ( (array) $plugins->response as $slug => $plugin ) {
			$slug_parts  = explode( '/', $slug );
			$actual_slug = array_shift( $slug_parts );

			$filters = $pluginFilters->getPlugins( [ $actual_slug ] );

			if ( isset( $filters['disable_updates'][ $actual_slug ] ) && $filters['disable_updates'][ $actual_slug ] ) {
				unset( $plugins->response[ $slug ] );
			}
		}

		return $plugins;
	}

	/**
	 * Disables plugin http requests on an individual basis.
	 *
	 * @param array  $r     Request array
	 * @param string $url   URL requested
	 *
	 * @return array Updated Request array
	 */
	public function httpRequestArgsRemovePlugins( $r, $url ) {
		if ( ! is_string( $url ) || 0 !== strpos( $url, 'https://api.wordpress.org/plugins/update-check/1.1/' ) ) {
			return $r;
		}

		if ( isset( $r['body']['plugins'] ) ) {
			$r_plugins     = json_decode( $r['body']['plugins'], true );
			$pluginFilters = new WUPM_PluginFilters( $this->plugin );

			if ( isset( $r_plugins['plugins'] ) && ! empty( $r_plugins['plugins'] ) ) {
				foreach ( $r_plugins['plugins'] as $slug => $plugin ) {
					$slug_parts  = explode( '/', $slug );
					$actual_slug = array_shift( $slug_parts );

					$filters = $pluginFilters->getPlugins( [ $actual_slug ] );

					if ( isset( $filters['disable_updates'][ $actual_slug ] ) and $filters['disable_updates'][ $actual_slug ] ) {
						unset( $r_plugins['plugins'][ $slug ] );

						if ( false !== $key = array_search( $slug, $r_plugins['active'] ) ) {
							unset( $r_plugins['active'][ $key ] );
							$r_plugins['active'] = array_values( $r_plugins['active'] );
						}
					}
				}
			}
			$r['body']['plugins'] = json_encode( $r_plugins );
		}

		return $r;
	}


	public function disableAllCoreUpdates() {
		add_action( 'admin_init', [ $this, 'adminInitForCore' ] );
		add_action( 'admin_init', [ $this, 'disableUpdateNag' ] );

		/*
		 * Disable All Automatic Updates
		 * 3.7+
		 *
		 * @author	sLa NGjI's @ slangji.wordpress.com
		 */
		add_filter( 'automatic_updater_disabled', '__return_true' );
		add_filter( 'allow_minor_auto_core_updates', '__return_false' );
		add_filter( 'allow_major_auto_core_updates', '__return_false' );
		add_filter( 'allow_dev_auto_core_updates', '__return_false' );
		add_filter( 'auto_update_core', '__return_false' );
		add_filter( 'wp_auto_update_core', '__return_false' );
		add_filter( 'auto_core_update_send_email', '__return_false' );
		add_filter( 'send_core_update_notification_email', '__return_false' );
		add_filter( 'automatic_updates_send_debug_email', '__return_false' );
		add_filter( 'automatic_updates_is_vcs_checkout', '__return_true' );

		// change update nag
		add_filter( 'wp_get_update_data', [ $this, 'updateCounter' ], 10, 2 );
		add_filter( 'site_transient_update_core', [ $this, 'hideCoreUpdateForm' ], 10, 2 );
	}

	/**
	 * callback for action "admin_init"
	 * remove update nag in admin pages
	 */
	function disableUpdateNag() {
		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'admin_notices', 'maintenance_nag' );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
	}

	/**
	 * Initialize and load the plugin stuff
	 *
	 * @author scripts@schloebe.de
	 */
	function adminInitForPlugins() {
		/*
		 * 2.8 to 3.0
		 */
		remove_action( 'load-plugins.php', 'wp_update_plugins' );
		remove_action( 'load-update.php', 'wp_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_plugins' );

		/*
		 * 3.0
		 */
		remove_action( 'load-update-core.php', 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_plugins' );
	}

	function adminInitForThemes() {
		/*
		 * 2.8 to 3.0
		 */
		remove_action( 'load-themes.php', 'wp_update_themes' );
		remove_action( 'load-update.php', 'wp_update_themes' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'wp_update_themes', 'wp_update_themes' );
		wp_clear_scheduled_hook( 'wp_update_themes' );

		/*
		 * 3.0
		 */
		remove_action( 'load-update-core.php', 'wp_update_themes' );
		wp_clear_scheduled_hook( 'wp_update_themes' );
	}

	/**
	 * Initialize and load the plugin stuff
	 *
	 * @author scripts@schloebe.de
	 */
	function adminInitForCore() {
		/*
		 * 2.8 to 3.0
		 */
		remove_action( 'wp_version_check', 'wp_version_check' );
		remove_action( 'admin_init', '_maybe_update_core' );
		wp_clear_scheduled_hook( 'wp_version_check' );

		/*
		 * 3.7+
		 */
		remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );
		remove_action( 'admin_init', 'wp_maybe_auto_update' );
		remove_action( 'admin_init', 'wp_auto_update_core' );
		wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
	}

	public function lastCheckedNow( $transient ) {
		global $wp_version;

		include ABSPATH . WPINC . '/version.php';
		$current                  = new stdClass;
		$current->updates         = [];
		$current->version_checked = $wp_version;
		$current->last_checked    = time();

		return $current;
	}

	/**
	 * callback for filter wp_get_update_data
	 * If disableAllCoreUpdates recalc the update badge
	 *
	 * @param $update_data
	 * @param $titles
	 *
	 * @return mixed $update_data
	 */
	function updateCounter( $update_data, $titles ) {
		if ( $update_data['counts']['wordpress'] > 0 ) {
			$new_num = $update_data['counts']['total'] = $update_data['counts']['total'] - $update_data['counts']['wordpress'];
			preg_replace( '/[0-9]+/', $new_num, $titles['wordpress'] );
			$update_data['counts']['wordpress'] = 0;
		}

		return $update_data;
	}

	/** Modify data about core available updates
	 * Hide update buttons on update page and dashboard home page
	 *
	 * @param $val
	 * @param $transient
	 *
	 * @return mixed $val
	 */
	function hideCoreUpdateForm( $val, $transient ) {
		if ( is_object( $val ) ) {
			$val->updates = [];
		}

		return $val;
	}

	public function disableThemeNotifications( $themes ) {
		if ( ! isset( $themes->response ) || empty( $themes->response ) ) {
			return $themes;
		}

		$filters = $this->plugin->getPopulateOption( 'themes_update_filters' );

		if ( ! empty( $filters ) && isset( $filters['disable_updates'] ) ) {
			foreach ( (array) $themes->response as $slug => $theme ) {
				$slug_parts  = explode( '/', $slug );
				$actual_slug = array_shift( $slug_parts );
				if ( isset( $filters['disable_updates'][ $actual_slug ] ) ) {
					unset( $themes->response[ $slug ] );
				}
			}
		}

		return $themes;
	}

	/**
	 * Disables theme and plugin http requests on an individual basis.
	 *
	 * @param array  $r     Request array
	 * @param string $url   URL requested
	 *
	 * @return array Updated Request array
	 */
	public function httpRequestArgsRemoveThemes( $r, $url ) {
		if ( 0 !== strpos( $url, 'https://api.wordpress.org/themes/update-check/1.1/' ) ) {
			return $r;
		}

		if ( isset( $r['body']['themes'] ) ) {
			$r_themes = json_decode( $r['body']['themes'], true );
			$filters  = $this->plugin->getPopulateOption( 'themes_update_filters' );

			if ( isset( $r_themes['themes'] ) && ! empty( $r_themes['themes'] ) ) {
				foreach ( $r_themes['themes'] as $actual_slug => $theme ) {

					if ( isset( $filters['disable_updates'] ) && isset( $filters['disable_updates'][ $actual_slug ] ) ) {
						unset( $r_themes['themes'][ $actual_slug ] );
					}
				}
			}
			$r['body']['themes'] = json_encode( $r_themes );
		}

		return $r;
	}


	public function disablePluginTranslationUpdates( $plugins ) {
		if ( ! isset( $plugins->translations ) || empty( $plugins->translations ) ) {
			return $plugins;
		}

		$is_disabled_translation_updates = $this->plugin->getPopulateOption( 'auto_tran_update' );
		if ( $is_disabled_translation_updates ) {
			$plugins->translations = [];

			return $plugins;
		}

		$filters = $this->plugin->getPopulateOption( 'plugins_update_filters' );

		if ( ! empty( $filters ) && isset( $filters['disable_translation_updates'] ) ) {
			foreach ( (array) $plugins->translations as $key => $translation ) {
				if ( $translation['type'] == 'plugin' && array_key_exists( $translation['slug'], $filters['disable_translation_updates'] ) && $filters['disable_translation_updates'][ $translation['slug'] ] ) {
					unset( $plugins->translations[ $key ] );
				}
			}
		}

		return $plugins;
	}

	public function disableThemeTranslationUpdates( $themes ) {
		if ( ! isset( $themes->translations ) || empty( $themes->translations ) ) {
			return $themes;
		}

		$is_disabled_translation_updates = $this->plugin->getPopulateOption( 'auto_tran_update' );
		if ( $is_disabled_translation_updates ) {
			$themes->translations = [];

			return $themes;
		}

		$filters = $this->plugin->getPopulateOption( 'themes_update_filters' );
		if ( ! empty( $filters ) && isset( $filters['disable_translation_updates'] ) ) {
			foreach ( (array) $themes->translations as $key => $translation ) {
				if ( $translation['type'] == 'theme' && array_key_exists( $translation['slug'], $filters['disable_translation_updates'] ) && $filters['disable_translation_updates'][ $translation['slug'] ] ) {
					unset( $themes->translations[ $key ] );
				}
			}
		}
	}

	public function hidePlugins( $plugins ) {
		$filters = $this->plugin->getPopulateOption( 'plugins_update_filters' );
		if ( ! isset( $filters['disable_display'] ) ) {
			return $plugins;
		}
		$filters = (array) $filters['disable_display'];

		foreach ( (array) $plugins as $plugin_path => $plugin ) {
			$slug_parts  = explode( '/', $plugin_path );
			$actual_slug = array_shift( $slug_parts );
			foreach ( $filters as $filter => $filter_value ) {
				if ( $filter_value and $actual_slug == $filter ) {
					unset( $plugins[ $plugin_path ] );
				}
			}
		}

		return $plugins;
	}

	/**
	 * change update counter for plugins in sidebar
	 */
	public function hidePluginUpdateCount( $update_data, $titles ) {
		$filters = $this->plugin->getPopulateOption( 'plugins_update_filters' );
		if ( ! isset( $filters['disable_display'] ) ) {
			return $update_data;
		}
		$filters = (array) $filters['disable_display'];
		if ( count( $filters ) == 0 ) {
			return $update_data;
		}

		// count hidden plugins with available update
		$hidden_updates = 0;

		$update_plugins = get_site_transient( 'update_plugins' );
		if ( $update_plugins === false ) {
			return $update_data;
		}
		foreach ( $update_plugins->response as $plugin_path => $plugin ) {
			$slug_parts  = explode( '/', $plugin_path );
			$actual_slug = array_shift( $slug_parts );
			if ( array_key_exists( $actual_slug, $filters ) ) {
				$hidden_updates ++;
			}
		}
		$new_count = $update_data['counts']['plugins'] - $hidden_updates;
		if ( $hidden_updates > 0 and strpos( $update_data['title'], ',' ) !== false ) {
			$strings              = explode( ',', $update_data['title'] );
			$strings[1]           = str_replace( $update_data['counts']['plugins'], $new_count, $strings[1] );
			$update_data['title'] = implode( ',', $strings );
		}
		$update_data['counts']['plugins'] = $new_count;

		return $update_data;
	}
}