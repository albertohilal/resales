<?php
/**
 * Clase para administración y página de diagnóstico del plugin
 * @package Resales_API
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Resales_Admin' ) ) {
	class Resales_Admin {
		private static $instance = null;
		public static function instance() {
			if (self::$instance === null) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		private function __construct() {
			add_action( 'admin_menu', array( $this, 'add_diag_submenu' ) );
		}
		public function add_diag_submenu() {
			add_options_page(
				'Resales Diag',
				'Resales Diag',
				'manage_options',
				'resales-api-diag',
				array( $this, 'render_diag_page' )
			);
		}
		public function render_diag_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'resales-api' ) );
			}
			$p1   = get_option( 'resales_api_p1', '' );
			$p2   = get_option( 'resales_api_p2', '' );
			$lang = get_option( 'resales_api_lang', 'es' );
			$test_lang      = isset( $_POST['resales_test_lang'] )      ? sanitize_text_field( wp_unslash( $_POST['resales_test_lang'] ) )      : $lang;
			$test_pagesize  = isset( $_POST['resales_test_pagesize'] )  ? absint( $_POST['resales_test_pagesize'] ) : 3;
			$test_page      = isset( $_POST['resales_test_page'] )      ? absint( $_POST['resales_test_page'] )     : 1;
			$result  = null;
			$err_msg = '';
			if ( isset( $_POST['resales_diag_submit'] ) ) {
				check_admin_referer( 'resales_api_diag' );
				if ( empty( $p1 ) || empty( $p2 ) ) {
					$err_msg = 'Faltan credenciales (P1/P2). Configúralas en Ajustes → Resales API.';
				} else {
					if ( ! class_exists( 'Resales_Client' ) ) {
						$err_msg = 'No se encontró la clase del cliente (Resales_Client).';
					} else {
						$client = new Resales_Client();
						$args = array(
							'lang'     => $test_lang,
							'page'     => $test_page,
							'pagesize' => $test_pagesize,
						);
						$result = $client->search( $args );
					}
				}
			}
			echo '<div class="wrap">';
			echo '<h1>Diagnóstico – Resales Online (V6)</h1>';
			// ... UI y resultados ...
			echo '</div>';
		}
	}
}
