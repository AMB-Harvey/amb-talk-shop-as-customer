<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverMenu' ) ) {
	class WPCleverMenu {
		function __construct() {
			// do nothing, moved to AMBTalkDashboard
		}
	}

	new WPCleverMenu();
}

if ( ! class_exists( 'AMBTalkDashboard' ) ) {
	class AMBTalkDashboard {
		function __construct() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		}

		function enqueue_scripts() {
			wp_enqueue_style( 'wpc-dashboard', WPC_URI . 'includes/dashboard/css/dashboard.css' );
			wp_enqueue_script( 'wpc-dashboard', WPC_URI . 'includes/dashboard/js/backend.js', [ 'jquery' ] );
			wp_localize_script( 'wpc-dashboard', 'wpc_dashboard_vars', [
					'nonce' => wp_create_nonce( 'wpc_dashboard' ),
				]
			);
		}

		function admin_menu() {
			add_menu_page(
				'AMBTalkCreateOrder',
				'AMBTalkCreateOrder',
				'manage_options',
				'AMBTalkCreateOrder',
				[ $this, 'admin_menu_content' ],
				WPC_URI . 'includes/dashboard/images/wpc-icon.svg',
				26
			);
			add_submenu_page( 'AMBTalkCreateOrder', 'WPC About', 'About', 'manage_options', 'AMBTalkCreateOrder' );
		}

		function admin_menu_content() {
			add_thickbox();
			?>
            <div class="wpclever_page wpclever_welcome_page wrap">
                <h1>Amoeba Talk create order as a Customer for WooCommerce</h1>
                <div class="card">
                    <h2 class="title">About</h2>
                    <p>
						Amoeba Talk create order as a Customer for WooCommerce
                        <a href="https://talk.amoeba.site" target="_blank">talk.amoeba.site</a>
                    </p>
                </div>
            </div>
			<?php
		}
	}

	new AMBTalkDashboard();
}