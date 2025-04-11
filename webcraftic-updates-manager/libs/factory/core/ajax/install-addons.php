<?php
/**
 * Ajax plugin configuration
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 2017 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This action allows you to process Ajax requests to activate external components Clearfy
 */
function wfactory_483_install_components( $plugin_instance ) {
	check_ajax_referer( 'updates' );

	$slug    = $plugin_instance->request->post( 'plugin', null, true );
	$action  = $plugin_instance->request->post( 'plugin_action', null, true );
	$storage = $plugin_instance->request->post( 'storage', null, true );

	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_die( __( 'You don\'t have enough capability to edit this information.', 'wbcr_factory_483' ), __( 'Something went wrong.' ), 403 );
	}

	if ( empty( $slug ) || empty( $action ) ) {
		wp_send_json_error( [ 'error_message' => __( 'Required attributes are not passed or empty.', 'wbcr_factory_483' ) ] );
	}
	$success   = false;
	$send_data = [];

	if ( $storage == 'internal' ) {
		if ( $action == 'activate' ) {
			if ( $plugin_instance->activateComponent( $slug ) ) {
				$success = true;
			}
		} else if ( $action == 'deactivate' ) {

			if ( $plugin_instance->deactivateComponent( $slug ) ) {
				$success = true;
			}
		} else {
			wp_send_json_error( [ 'error_message' => __( 'You are trying to perform an invalid action.', 'wbcr_factory_483' ) ] );
		}
	} else if ( $storage == 'wordpress' || $storage == 'creativemotion' ) {
		if ( ! empty( $slug ) ) {
			$network_wide = $plugin_instance->isNetworkActive();

			if ( $action == 'activate' ) {
				$result = activate_plugin( $slug, '', $network_wide );

				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'error_message' => $result->get_error_message() ] );
				}
			} else if ( $action == 'deactivate' ) {
				deactivate_plugins( $slug, false, $network_wide );
			}

			$success = true;
		}
	}

	if ( $action == 'install' || $action == 'deactivate' ) {
		try {
			// Delete button
			$delete_button              = $plugin_instance->get_delete_component_button( $storage, $slug );
			$send_data['delete_button'] = $delete_button->get_button();
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error_message' => $e->getMessage() ] );
		}
	}

	// Если требуется обновить постоянные ссылки, даем сигнал, что пользователю, нужно показать
	// всплывающее уведомление.
	// todo: сделать более красивое решение с передачей текстовых сообщений
	/*if ( $action == 'deactivate' ) {
		$is_need_rewrite_rules = $plugin_instance->getPopulateOption( 'need_rewrite_rules' );
		if ( $is_need_rewrite_rules ) {
			$send_data['need_rewrite_rules'] = sprintf( '<span class="wbcr-clr-need-rewrite-rules-message">' . __( 'When you deactivate some components, permanent links may work incorrectly. If this happens, please, <a href="%s">update the permalinks</a>, so you could complete the deactivation.', 'wbcr_factory_483' ), admin_url( 'options-permalink.php' ) . '</span>' );
		}
	}*/

	if ( $success ) {
		// todo: для совместимости с плагином Clearfy
		if ( "wbcr_clearfy" === $plugin_instance->getPluginName() ) {
			do_action( 'wbcr_clearfy_update_component', $slug, $action, $storage );
		}
		do_action( "wfactory/updated_{$plugin_instance->getPluginName()}_component", $slug, $action, $storage );

		wp_send_json_success( $send_data );
	}

	wp_send_json_error( [ 'error_message' => __( 'An unknown error occurred during the activation of the component.', 'wbcr_factory_483' ) ] );
}

/**
 * Ajax event that calls the wbcr/clearfy/activated_component action,
 * to get the component to work. Usually this is a call to the installation functions,
 * but in some cases, overwriting permanent references or compatibility checks.
 */
function wfactory_483_prepare_component( $plugin_instance ) {
	check_ajax_referer( 'updates' );

	$component_name = $plugin_instance->request->post( 'plugin', null, true );

	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_send_json_error( [ 'error_message' => __( 'You don\'t have enough capability to edit this information.', 'wbcr_factory_483' ) ], 403 );
	}

	if ( empty( $component_name ) ) {
		wp_send_json_error( [ 'error_message' => __( 'Required attribute [component_name] is empty.', 'wbcr_factory_483' ) ] );
	}
	// todo: для совместимости с плагином Clearfy
	if ( "wbcr_clearfy" === $plugin_instance->getPluginName() ) {
		do_action( 'wbcr/clearfy/activated_component', $component_name );
	}
	do_action( "wfactory/activated_{$plugin_instance->getPluginName()}_component", $component_name );

	wp_send_json_success();
}

/**
 * Ajax handler for installing a plugin from GitHub repository.
 *
 * @since 4.6.0
 *
 * @see Plugin_Upgrader
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 */
function wfactory_483_creativemotion_install_plugin( $plugin_instance ) {
	check_ajax_referer( 'updates' );

	if ( empty( $_POST['slug'] ) ) {
		wp_send_json_error( [
			'errorCode'    => 'no_plugin_specified',
			'errorMessage' => __( 'No plugin specified.' ),
		] );
	}

	$slug   = sanitize_key( wp_unslash( $_POST['slug'] ) );
	$status = [
		'install' => 'plugin',
		'slug'    => $slug,
	];

	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( [
			'errorCode'    => 'insufficient_permissions',
			'errorMessage' => __( 'Sorry, you are not allowed to install plugins on this site.' ),
		] );
	}

	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	$github_settings = [
		'github_username'   => 'Creative-Motion-Development',
		'github_repository' => 'wp-plugin-' . $slug,
		'slug'              => $slug,
	];

	try {
		$github_repo  = new \WBCR\Factory_483\Updates\Github_Repository( $plugin_instance, $github_settings );
		$download_url = $github_repo->get_download_url();

		if ( empty( $download_url ) ) {
			throw new \Exception( 'Failed to get download URL from GitHub.' );
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [
				'errorCode'    => $result->get_error_code(),
				'errorMessage' => $result->get_error_message(),
			] );
		}

		if ( $skin->get_errors()->has_errors() ) {
			wp_send_json_error( [
				'errorMessage' => $skin->get_error_messages(),
			] );
		}

		$install_status = install_plugin_install_status( (object) [ 'slug' => $slug ] );
		$pagenow        = isset( $_POST['pagenow'] ) ? sanitize_key( $_POST['pagenow'] ) : '';

		// If installation request is coming from import page, do not return network activation link.
		$plugins_url = ( 'import' === $pagenow ) ? admin_url( 'plugins.php' ) : network_admin_url( 'plugins.php' );

		if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
			$status['activateUrl'] = add_query_arg( [
				'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $install_status['file'] ),
				'action'   => 'activate',
				'plugin'   => $install_status['file'],
			], $plugins_url );
		}

		if ( is_multisite() && current_user_can( 'manage_network_plugins' ) && 'import' !== $pagenow ) {
			$status['activateUrl'] = add_query_arg( [ 'networkwide' => 1 ], $status['activateUrl'] );
		}

		wp_send_json_success( $status );

	} catch ( \Exception $e ) {
		wp_send_json_error( [
			'errorCode'    => 'github_error',
			'errorMessage' => $e->getMessage(),
		] );
	}
}