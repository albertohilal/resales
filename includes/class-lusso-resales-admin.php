<?php
/**
 * Clase para administración y página de diagnóstico del plugin
 * @package Lusso_Resales
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Lusso_Resales_Admin' ) ) {

	class Lusso_Resales_Admin {

		public function __construct() {
			// Agrega la página de diagnóstico bajo "Ajustes"
			add_action( 'admin_menu', array( $this, 'add_diag_submenu' ) );
		}

		/**
		 * Añadir submenú en Ajustes → Resales Diag.
		 */
		public function add_diag_submenu() {
			add_options_page(
				'Resales Diag',
				'Resales Diag',
				'manage_options',
				'lusso-resales-diag',
				array( $this, 'render_diag_page' )
			);
		}

		/**
		 * Render de la página de diagnóstico
		 */
		public function render_diag_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'lusso-resales' ) );
			}

			// Cargar ajustes actuales
			$p1   = get_option( 'lusso_resales_p1', '' );
			$p2   = get_option( 'lusso_resales_p2', '' );
			$lang = get_option( 'lusso_resales_lang', 'es' );

			// Valores por defecto para el formulario de prueba
			$test_lang      = isset( $_POST['lusso_test_lang'] )      ? sanitize_text_field( wp_unslash( $_POST['lusso_test_lang'] ) )      : $lang;
			$test_pagesize  = isset( $_POST['lusso_test_pagesize'] )  ? absint( $_POST['lusso_test_pagesize'] ) : 3;
			$test_page      = isset( $_POST['lusso_test_page'] )      ? absint( $_POST['lusso_test_page'] )     : 1;

			$result  = null;
			$err_msg = '';

			// ¿Se envió el formulario de prueba?
			if ( isset( $_POST['lusso_diag_submit'] ) ) {
				check_admin_referer( 'lusso_resales_diag' );

				// Validaciones mínimas
				if ( empty( $p1 ) || empty( $p2 ) ) {
					$err_msg = 'Faltan credenciales (P1/P2). Configúralas en Ajustes → Resales API.';
				} else {
					// Ejecutar prueba usando el cliente del plugin
					if ( ! class_exists( 'Lusso_Resales_Client' ) ) {
						$err_msg = 'No se encontró la clase del cliente (Lusso_Resales_Client).';
					} else {
						$client = new Lusso_Resales_Client();

						$args = array(
							'lang'     => $test_lang,
							'page'     => $test_page,
							'pagesize' => $test_pagesize,
						);

						$result = $client->search( $args );
					}
				}
			}

			// UI
			echo '<div class="wrap">';
			echo '<h1>Diagnóstico – ReSales Online (V6)</h1>';

			echo '<p>Usa este panel para comprobar rápidamente la conectividad con la WebAPI V6 y la validez de tus credenciales.</p>';

			// Resumen de ajustes actuales
			echo '<h2 class="title">Ajustes detectados</h2>';
			echo '<table class="widefat striped" style="max-width:800px">';
			echo '<tbody>';
			echo '<tr><th style="width:220px">P1</th><td><code>' . ( $p1 ? esc_html( substr( $p1, 0, 4 ) . '•••' ) : '<em>no configurado</em>' ) . '</code></td></tr>';
			echo '<tr><th>P2</th><td><code>' . ( $p2 ? esc_html( substr( $p2, 0, 4 ) . '•••' ) : '<em>no configurado</em>' ) . '</code></td></tr>';
			echo '<tr><th>Idioma por defecto</th><td>' . esc_html( $lang ) . '</td></tr>';
			echo '</tbody>';
			echo '</table>';

			// Formulario de prueba
			echo '<h2 class="title" style="margin-top:24px">Prueba rápida de búsqueda</h2>';
			echo '<form method="post">';
			wp_nonce_field( 'lusso_resales_diag' );

			echo '<table class="form-table" role="presentation">';
			echo '<tr>';
			echo '<th scope="row"><label for="lusso_test_lang">Idioma (lang)</label></th>';
			echo '<td><input type="text" id="lusso_test_lang" name="lusso_test_lang" value="' . esc_attr( $test_lang ) . '" class="regular-text" /></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row"><label for="lusso_test_pagesize">PageSize</label></th>';
			echo '<td><input type="number" min="1" max="50" id="lusso_test_pagesize" name="lusso_test_pagesize" value="' . esc_attr( $test_pagesize ) . '" class="small-text" /></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row"><label for="lusso_test_page">Page</label></th>';
			echo '<td><input type="number" min="1" id="lusso_test_page" name="lusso_test_page" value="' . esc_attr( $test_page ) . '" class="small-text" /></td>';
			echo '</tr>';
			echo '</table>';

			submit_button( 'Ejecutar prueba', 'primary', 'lusso_diag_submit' );
			echo '</form>';

			// Resultado de prueba
			if ( $err_msg ) {
				echo '<div class="notice notice-error" style="margin-top:16px"><p><strong>Error:</strong> ' . esc_html( $err_msg ) . '</p></div>';
			} elseif ( is_array( $result ) ) {
				echo '<h2 class="title" style="margin-top:24px">Resultado</h2>';

				$ok    = isset( $result['ok'] )   ? ( $result['ok'] ? 'true' : 'false' ) : '—';
				$code  = isset( $result['code'] ) ? intval( $result['code'] ) : '—';

				echo '<table class="widefat striped" style="max-width:800px">';
				echo '<tbody>';
				echo '<tr><th style="width:220px">OK</th><td><code>' . esc_html( $ok ) . '</code></td></tr>';
				echo '<tr><th>Código</th><td><code>' . esc_html( $code ) . '</code></td></tr>';

				// Datos (resumen)
				if ( isset( $result['data'] ) && ! is_null( $result['data'] ) ) {
					$sample = $result['data'];
					if ( is_array( $sample ) ) {
						$preview = wp_json_encode( array_slice( $sample, 0, 1 ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
						echo '<tr><th>Muestra (primer ítem)</th><td><pre style="white-space:pre-wrap;max-height:260px;overflow:auto;">' . esc_html( $preview ) . '</pre></td></tr>';
					} else {
						echo '<tr><th>Data</th><td><code>(no-array)</code></td></tr>';
					}
				}

				// Error y RAW si existieran (solo una vista corta)
				if ( ! empty( $result['error'] ) ) {
					echo '<tr><th>Error</th><td><pre style="white-space:pre-wrap;max-height:180px;overflow:auto;">' . esc_html( (string) $result['error'] ) . '</pre></td></tr>';
				}
				if ( isset( $result['raw'] ) && ! is_null( $result['raw'] ) ) {
					$raw = (string) $result['raw'];
					if ( strlen( $raw ) > 900 ) $raw = substr( $raw, 0, 900 ) . '…';
					echo '<tr><th>RAW (recortado)</th><td><pre style="white-space:pre-wrap;max-height:220px;overflow:auto;">' . esc_html( $raw ) . '</pre></td></tr>';
				}

				echo '</tbody>';
				echo '</table>';

				echo '<p style="margin-top:8px"><em>Nota:</em> este test realiza una llamada real con tus credenciales P1/P2 en el idioma y tamaño de página indicados.</p>';
			}

			// Utilidades: limpiar caché transitorio
			if ( isset( $_POST['lusso_diag_flush'] ) ) {
				check_admin_referer( 'lusso_resales_diag_flush' );
				global $wpdb;
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
					$wpdb->esc_like( 'lusso_resales_' ) . '%'
				) );
				echo '<div class="notice notice-success" style="margin-top:16px"><p>Se limpiaron transients/entradas con prefijo <code>lusso_resales_*</code>.</p></div>';
			}

			echo '<h2 class="title" style="margin-top:28px">Mantenimiento</h2>';
			echo '<form method="post" style="display:inline-block">';
			wp_nonce_field( 'lusso_resales_diag_flush' );
			submit_button( 'Limpiar cachés del plugin', 'secondary', 'lusso_diag_flush', false );
			echo '</form>';

			echo '</div>'; // .wrap
		}
	}
}
