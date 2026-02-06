<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Страница общих настроек для этого плагина.
 *
 * Может быть использована только, если этот плагин используется как отдельный плагин, а не как аддон
 * дя плагина Clearfy. Если плагин загружен, как аддон для Clearfy, эта страница не будет подключена.
 *
 * Поддерживает режим работы с мультисаймами. Вы можете увидеть эту страницу в панели настройки сети.
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2018 Webraftic Ltd
 */
class WUPM_UpdatesPage extends WBCR\Factory_Templates_137\Pages\PageBase {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "updates";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-cloud';

	/**
	 * {@inheritDoc}
	 *
	 * @var bool
	 */
	public $available_for_multisite = true;

	/**
	 * {@inheritDoc}
	 *
	 * @since  1.1.0
	 * @var bool
	 */
	public $show_right_sidebar_in_options = true;

	/**
	 * WUPM_UpdatesPage constructor.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 *
	 * @param \Wbcr_Factory483_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory483_Plugin $plugin ) {
		$this->menu_title                  = __( 'Updates manager', 'webcraftic-updates-manager' );
		$this->page_menu_short_description = __( 'Manage all site updates', 'webcraftic-updates-manager' );

		if ( ! defined( 'LOADING_UPDATES_MANAGER_AS_ADDON' ) ) {
			$this->internal                   = false;
			$this->menu_target                = 'options-general.php';
			$this->add_link_to_plugin_actions = true;
			$this->show_search_options_form = false;
		}

		parent::__construct( $plugin );
	}

	/*public function getMenuTitle() {
		return defined( 'LOADING_UPDATES_MANAGER_AS_ADDON' ) ? __( 'Updates', 'webcraftic-updates-manager' ) : __( 'Updates manager', 'webcraftic-updates-manager' );
	}*/

	/**
	 * {@inheritDoc}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @return string|void
	 */
	public function getPageTitle() {
		return defined( 'LOADING_UPDATES_MANAGER_AS_ADDON' ) ? __( 'Updates', 'webcraftic-updates-manager' ) : __( 'General', 'webcraftic-updates-manager' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>     *
	 *
	 * @param \Wbcr_Factory483_ScriptList $scripts
	 * @param \Wbcr_Factory483_StyleList  $styles
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );
		$this->styles->add( WUPM_PLUGIN_URL . '/admin/assets/css/general.css' );
		// Add Clearfy styles for HMWP pages
		if ( defined( 'WCL_PLUGIN_ACTIVE' ) ) {
			$this->styles->add( WCL_PLUGIN_URL . '/admin/assets/css/general.css' );
		}
	}

	/**
	 * Permalinks options.
	 *
	 * @since 1.0.0
	 * @return mixed[]
	 */
	public function getPageOptions() {
		$options    = [];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'General settings for WordPress, plugins and themes updates', 'webcraftic-updates-manager' ) . '</strong><p>' . __( 'This page, you can enable or disable automatic updates. To test the automatic updates, click the "Advanced" tab.', 'webcraftic-updates-manager' ) . '</p></div>'
		];

		$options[] = [
			'type'    => 'dropdown',
			'name'    => 'plugin_updates',
			'way'     => 'buttons',
			'title'   => __( 'Plugin Updates', 'webcraftic-updates-manager' ),
			'data'    => [
				[ 'enable_plugin_monual_updates', __( 'Manual updates', 'webcraftic-updates-manager' ) ],
				[ 'enable_plugin_auto_updates', __( 'Enable auto updates', 'webcraftic-updates-manager' ) ],
				[ 'disable_plugin_updates', __( 'Disable updates', 'webcraftic-updates-manager' ) ]
			],
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'    => __( 'You can disable all plugin updates or choose manual or automatic update mode.', 'webcraftic-updates-manager' ),
			'default' => 'enable_plugin_monual_updates'
		];

		$options[] = [
			'type'    => 'dropdown',
			'name'    => 'theme_updates',
			'way'     => 'buttons',
			'title'   => __( 'Theme Updates', 'webcraftic-updates-manager' ),
			'data'    => [
				[ 'enable_theme_monual_updates', __( 'Manual updates', 'webcraftic-updates-manager' ) ],
				[ 'enable_theme_auto_updates', __( 'Enable auto updates', 'webcraftic-updates-manager' ) ],
				[ 'disable_theme_updates', __( 'Disable updates', 'webcraftic-updates-manager' ) ]
			],
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'    => __( 'You can disable all themes updates or choose manual or automatic update mode.', 'webcraftic-updates-manager' ),
			'default' => 'enable_theme_monual_updates'
		];

		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'auto_tran_update',
			'title'   => __( 'Disable Automatic Translation Updates', 'webcraftic-updates-manager' ),
			//'layout' => array('hint-type' => 'icon', 'hint-icon-color' => 'green'),
			//'hint' => __('', 'webcraftic-updates-manager') . '<br><br><b>Clearfy: </b>' . __('', 'webcraftic-updates-manager'),
			'default' => false,
		];

		$options[] = [
			'type'    => 'dropdown',
			'name'    => 'wp_update_core',
			'title'   => __( 'WordPress Core Updates', 'webcraftic-updates-manager' ),
			'data'    => [
				[ 'disable_core_updates', __( 'Disable updates', 'webcraftic-updates-manager' ) ],
				[ 'disable_core_auto_updates', __( 'Disable auto updates', 'webcraftic-updates-manager' ) ],
				[
					'allow_minor_core_auto_updates',
					__( 'Allow minor auto updates', 'webcraftic-updates-manager' )
				],
				[
					'allow_major_core_auto_updates',
					__( 'Allow major auto updates', 'webcraftic-updates-manager' )
				],
				[
					'allow_dev_core_auto_updates',
					__( 'Allow development auto updates', 'webcraftic-updates-manager' )
				]
			],
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'    => __( 'You can disable all core WordPress updates, or disable only automatic updates. Also you can select the update mode. By default (minor)', 'webcraftic-updates-manager' ) . '<br>-' . __( 'Major - automatically update to major releases (e.g., 4.1, 4.2, 4.3).', 'webcraftic-updates-manager' ) . '<br>-' . __( 'Minor - automatically update to minor releases (e.g., 4.1.1, 4.1.2, 4.1.3).', 'webcraftic-updates-manager' ) . '<br>-' . __( 'Development - update automatically to pre-release development versions.', 'webcraftic-updates-manager' ),
			'default' => 'allow_minor_core_auto_updates',
			'events'  => [
				'disable_core_updates'          => [
					'hide' => '.factory-control-disable_core_notifications'
				],
				'disable_core_auto_updates'     => [
					'show' => '.factory-control-disable_core_notifications'
				],
				'allow_minor_core_auto_updates' => [
					'show' => '.factory-control-disable_core_notifications'
				],
				'allow_major_core_auto_updates' => [
					'show' => '.factory-control-disable_core_notifications'
				],
				'allow_dev_core_auto_updates'   => [
					'show' => '.factory-control-disable_core_notifications'
				],
			]
		];

		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'enable_update_vcs',
			'title'   => __( 'Enable updates for VCS Installations', 'webcraftic-updates-manager' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'    => __( 'Enable Automatic Updates even if a version control system folder (VCS) (.git, .hg, .svn) was found in the WordPress directory', 'webcraftic-updates-manager' ),
			'default' => false,
		];

		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'updates_nags_only_for_admin',
			'title'   => __( 'Show Update Notifications Only to Administrators', 'webcraftic-updates-manager' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'    => __( 'Hide WordPress update notifications from non-administrator users. Useful for multi-user or client-facing CMS sites.', 'webcraftic-updates-manager' ),
			'default' => false,
		];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'Email Notifications', 'webcraftic-updates-manager' ) . '</strong><p>' . __( 'Email notifications are sent once a day, you can choose what notifications to send below.', 'webcraftic-updates-manager' ) . '</p></div>'
		];

		$options[] = [
			'type'     => 'checkbox',
			'way'      => 'buttons',
			'name'     => 'notify_update_available',
			'title'    => __( 'Update available', 'webcraftic-updates-manager' ),
			'hint'     => __( 'Send me emails when an update is available.', 'webcraftic-updates-manager' ),
			'layout'   => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'default'  => false,
			'cssClass' => [],
		];

		$options[] = [
			'type'     => 'checkbox',
			'way'      => 'buttons',
			'name'     => 'notify_updated',
			'title'    => __( 'Successful update', 'webcraftic-updates-manager' ),
			'hint'     => __( 'Send me emails when something has been updated.', 'webcraftic-updates-manager' ),
			'layout'   => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'default'  => false,
			'cssClass' => [],
		];

		$options[] = [
			'type'      => 'textbox',
			'way'       => 'buttons',
			'name'      => 'notify_email',
			'title'     => __( 'Email address', 'webcraftic-updates-manager' ),
			'hint'      => __( 'Separate email addresses using commas.', 'webcraftic-updates-manager' ),
			'default'   => $this->plugin->isNetworkActive() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' ),
			'htmlAttrs' => [],

		];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header factory-control-disable_core_notifications"><strong>' . __( 'Core notifications', 'webcraftic-updates-manager' ) . '</strong><p>' . __( 'Core notifications are handled by WordPress itself. You can only disable them; email address changes above won\'t affect these notifications.', 'webcraftic-updates-manager' ) . '</p></div>'
		];

		$options[] = [
			'type'     => 'checkbox',
			'way'      => 'buttons',
			'name'     => 'disable_core_notifications',
			'title'    => __( 'Core notifications', 'webcraftic-updates-manager' ),
			'hint'     => __( 'By default WordPress sends an email when a core update happened. Uncheck this box to disable these emails.', 'webcraftic-updates-manager' ),
			'layout'   => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'default'  => true,
			'cssClass' => [],
		];

		$formOptions = [];

		$formOptions[] = [
			'type'  => 'form-group',
			'items' => $options,
			//'cssClass' => 'postbox'
		];

		return apply_filters( 'wbcr/upm/updates_form_options', $formOptions );
	}
}

