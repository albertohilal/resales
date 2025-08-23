<?php
/**
 * Shortcodes para el plugin Lusso Resales
 *
 * Provee:
 *  - [lusso_developments ...]   → lista propiedades / developments
 *  - [resales_search_form]      → formulario simple de búsqueda (opcional)
 *
 * @package Lusso_Resales
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Lusso_Resales_Shortcodes' ) ) :

class Lusso_Resales_Shortcodes {

	/**
	 * Inicializador estático (más cómodo desde el bootstrap del plugin).
	 */
	public static function init() : void {
		$instance = new self();
		$instance->register();
	}

	/**
	 * Registro de shortcodes.
	 */
	public function register() : void {
		add_shortcode( 'lusso_developments',       array( $this, 'render_developments' ) );
		add_shortcode( 'resales_search_form',      array( $this, 'render_search_form' ) );
	}

	/* ============================================================
	 * 1) [lusso_developments] — Listado
	 *    Ejemplos:
	 *    - [lusso_developments lang="es" page="1" pagesize="6"]
	 *    - [lusso_developments filter_agency_id="4" per_page="9"]
	 * ============================================================ */

	/**
	 * Render del listado de propiedades/desarrollos.
	 *
	 * @param array $atts Atributos del shortcode.
	 * @return string HTML
	 */
	public function render_developments( $atts = array() ) : string {
		$atts = shortcode_atts(
			array(
				// Aliases comunes en page-builders:
				'filter_agency_id' => '',
				'per_page'         => '',
				// Parámetros directos:
				'agency_id'        => '',
				'lang'             => get_option( 'lusso_resales_lang', 'es' ),
				'page'             => '1',
				'pagesize'         => '',
				'orderby'          => '',
				'order'            => '',
				'minprice'         => '',
				'maxprice'         => '',
				'area'             => '',
				'subarea'          => '',
				'type'             => '',
				'q'                => '',
				// Presentación:
				'class'            => 'lusso-resales-grid',
				'card_class'       => 'lusso-card',
				'show_price'       => '1',
				'show_link'        => '1',
			),
			$atts,
			'lusso_developments'
		);

		// Normaliza aliases → nombres que consume el cliente/API
		if ( $atts['per_page'] && empty( $atts['pagesize'] ) ) {
			$atts['pagesize'] = $atts['per_page'];
		}
		if ( $atts['filter_agency_id'] && empty( $atts['agency_id'] ) ) {
			$atts['agency_id'] = $atts['filter_agency_id'];
		}

		// Construye parámetros para el cliente
		$params = array(
			'lang'      => sanitize_text_field( (string) $atts['lang'] ),
			'page'      => max( 1, absint( $atts['page'] ) ),
			'pagesize'  => $atts['pagesize'] !== '' ? max( 1, absint( $atts['pagesize'] ) ) : 6,
		);

		$optional = array(
			'agency_id','orderby','order','minprice','maxprice','area','subarea','type','q',
		);
		foreach ( $optional as $k ) {
			if ( $atts[ $k ] !== '' ) {
				$params[ $k ] = is_scalar( $atts[ $k ] ) ? sanitize_text_field( (string) $atts[ $k ] ) : '';
			}
		}

		// Llama al cliente
		if ( ! class_exists( 'Lusso_Resales_Client' ) ) {
			return $this->render_notice(
				__( 'No se encontró el cliente de API. Revisa la instalación del plugin.', 'lusso-resales' ),
				true
			);
		}

		$client = new Lusso_Resales_Client();
		$res    = $client->search( $params );

		// Manejo de error / vacío
		if ( empty( $res['ok'] ) ) {
			return $this->render_error_block( $res );
		}

		$data = $res['data'];

		/**
		 * La API de cada cuenta puede variar el envoltorio.
		 * Intentamos detectar el "array de items" en algunas claves comunes
		 * y, si no existen, asumimos que $data YA es una lista.
		 */
		$items = array();
		foreach ( array( 'items', 'results', 'Developments', 'Properties', 'data' ) as $key ) {
			if ( is_array( $data ) && isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				$items = $data[ $key ];
				break;
			}
		}
		if ( empty( $items ) && is_array( $data ) ) {
			// Si $data ya es una lista indexada
			$is_indexed = array_keys( $data ) === range( 0, count( $data ) - 1 );
			if ( $is_indexed ) {
				$items = $data;
			}
		}

		if ( empty( $items ) || ! is_array( $items ) ) {
			return $this->render_notice( __( 'No hay resultados para los filtros aplicados.', 'lusso-resales' ) );
		}

		// Render muy simple / neutro (puedes personalizar markup y clases)
		$container_class = sanitize_html_class( $atts['class'] );
		$card_class      = sanitize_html_class( $atts['card_class'] );
		$show_price      = $atts['show_price'] === '1';
		$show_link       = $atts['show_link'] === '1';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $container_class ); ?>">
			<?php foreach ( $items as $it ) :
				// Campos comunes (ajusta a tu JSON real)
				$title = $this->safe_read( $it, array( 'Title', 'title', 'Name', 'name' ), '' );
				$price = $this->safe_read( $it, array( 'Price', 'price' ), '' );
				$link  = $this->safe_read( $it, array( 'Url', 'URL', 'Permalink', 'permalink', 'link' ), '' );
				$img   = $this->safe_read( $it, array( 'Image', 'MainImage', 'image', 'thumbnail' ), '' );

				// Sanitiza para salida
				$title_out = $title ? wp_kses_post( $title ) : '';
				$price_out = $price ? wp_kses_post( $price ) : '';
				$link_out  = $link  ? esc_url( $link ) : '';
				$img_out   = $img   ? esc_url( $img )  : '';
				?>
				<article class="<?php echo esc_attr( $card_class ); ?>">
					<?php if ( $img_out ) : ?>
						<div class="lusso-thumb">
							<img src="<?php echo $img_out; ?>" alt="" loading="lazy">
						</div>
					<?php endif; ?>
					<div class="lusso-body">
						<?php if ( $title_out ) : ?>
							<h3 class="lusso-title"><?php echo $title_out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
						<?php endif; ?>
						<?php if ( $show_price && $price_out ) : ?>
							<p class="lusso-price"><strong><?php esc_html_e( 'Precio:', 'lusso-resales' ); ?></strong> <?php echo $price_out; // phpcs:ignore ?></p>
						<?php endif; ?>
						<?php if ( $show_link && $link_out ) : ?>
							<p class="lusso-link">
								<a href="<?php echo $link_out; ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'Ver propiedad', 'lusso-resales' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ============================================================
	 * 2) [resales_search_form] — Formulario simple
	 *    Genera un form que reenvía a la misma URL con querystring
	 *    y reusa [lusso_developments] para pintar resultados.
	 * ============================================================ */

	/**
	 * Render del formulario de búsqueda básico.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function render_search_form( $atts = array() ) : string {
		$atts = shortcode_atts(
			array(
				'method'       => 'get',
				'action'       => '',         // vacío = URL actual
				'lang'         => get_option( 'lusso_resales_lang', 'es' ),
				'pagesize'     => 6,
				'show_results' => '1',        // si "1", después del form imprime [lusso_developments] con los params
				'class'        => 'lusso-resales-form',
			),
			$atts,
			'resales_search_form'
		);

		$method   = strtolower( (string) $atts['method'] ) === 'post' ? 'post' : 'get';
		$action   = $atts['action'] ? esc_url( (string) $atts['action'] ) : esc_url( add_query_arg( array() ) );
		$class    = sanitize_html_class( $atts['class'] );

		// Lee filtros mínimos de la query actual
		$q_lang     = isset( $_REQUEST['lang'] )      ? sanitize_text_field( (string) $_REQUEST['lang'] )      : $atts['lang'];
		$q_pagesize = isset( $_REQUEST['pagesize'] )  ? max( 1, absint( $_REQUEST['pagesize'] ) )              : (int) $atts['pagesize'];
		$q_min      = isset( $_REQUEST['minprice'] )  ? sanitize_text_field( (string) $_REQUEST['minprice'] )  : '';
		$q_max      = isset( $_REQUEST['maxprice'] )  ? sanitize_text_field( (string) $_REQUEST['maxprice'] )  : '';
		$q_area     = isset( $_REQUEST['area'] )      ? sanitize_text_field( (string) $_REQUEST['area'] )      : '';
		$q_q        = isset( $_REQUEST['q'] )         ? sanitize_text_field( (string) $_REQUEST['q'] )         : '';

		ob_start();
		?>
		<form class="<?php echo esc_attr( $class ); ?>" method="<?php echo esc_attr( $method ); ?>" action="<?php echo $action; ?>">
			<div class="lusso-field">
				<label><?php esc_html_e( 'Idioma', 'lusso-resales' ); ?></label>
				<input type="text" name="lang" value="<?php echo esc_attr( $q_lang ); ?>" />
			</div>

			<div class="lusso-field">
				<label><?php esc_html_e( 'Resultados por página', 'lusso-resales' ); ?></label>
				<input type="number" name="pagesize" min="1" max="50" value="<?php echo esc_attr( $q_pagesize ); ?>" />
			</div>

			<div class="lusso-field">
				<label><?php esc_html_e( 'Precio mínimo', 'lusso-resales' ); ?></label>
				<input type="text" name="minprice" value="<?php echo esc_attr( $q_min ); ?>" />
			</div>

			<div class="lusso-field">
				<label><?php esc_html_e( 'Precio máximo', 'lusso-resales' ); ?></label>
				<input type="text" name="maxprice" value="<?php echo esc_attr( $q_max ); ?>" />
			</div>

			<div class="lusso-field">
				<label><?php esc_html_e( 'Área', 'lusso-resales' ); ?></label>
				<input type="text" name="area" value="<?php echo esc_attr( $q_area ); ?>" />
			</div>

			<div class="lusso-field">
				<label><?php esc_html_e( 'Búsqueda texto', 'lusso-resales' ); ?></label>
				<input type="text" name="q" value="<?php echo esc_attr( $q_q ); ?>" />
			</div>

			<div class="lusso-actions">
				<button type="submit"><?php esc_html_e( 'Buscar', 'lusso-resales' ); ?></button>
			</div>
		</form>
		<?php

		$html = (string) ob_get_clean();

		// Si show_results=1, reusa el otro shortcode con los parámetros de la request
		if ( $atts['show_results'] === '1' ) {
			$merge = array(
				'lang'     => $q_lang,
				'pagesize' => $q_pagesize,
			);
			if ( $q_min !== '' ) $merge['minprice'] = $q_min;
			if ( $q_max !== '' ) $merge['maxprice'] = $q_max;
			if ( $q_area !== '' ) $merge['area']     = $q_area;
			if ( $q_q   !== '' ) $merge['q']        = $q_q;

			// Construye atributos a string
			$atts_str = '';
			foreach ( $merge as $k => $v ) {
				$atts_str .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
			}
			$html .= do_shortcode( '[lusso_developments' . $atts_str . ']' );
		}

		return $html;
	}

	/* ============================================================
	 * Helpers de salida
	 * ============================================================ */

	/**
	 * Bloque de aviso simple.
	 */
	private function render_notice( string $msg, bool $is_error = false ) : string {
		$cls = $is_error ? 'lusso-resales-error' : 'lusso-resales-notice';
		return '<div class="' . esc_attr( $cls ) . '">' . esc_html( $msg ) . '</div>';
	}

	/**
	 * Bloque de error enriquecido para admins (muestra detalles con WP_DEBUG).
	 *
	 * @param array $res Respuesta del cliente.
	 * @return string
	 */
	private function render_error_block( array $res ) : string {
		$msg_public = __( 'No se encontraron propiedades o hubo un error en la consulta.', 'lusso-resales' );
		$out  = '<div class="lusso-resales-error" style="color:#c00;">' . esc_html( $msg_public ) . '</div>';

		if ( current_user_can( 'manage_options' ) ) {
			$extra = '';

			if ( isset( $res['code'] ) && $res['code'] ) {
				$extra .= '<div><strong>HTTP:</strong> ' . esc_html( (string) $res['code'] ) . '</div>';
			}
			if ( ! empty( $res['url'] ) ) {
				$safe_url = preg_replace( '/([?&])p2=[^&]+/i', '$1p2=***', (string) $res['url'] );
				$extra   .= '<div><strong>URL:</strong> <code style="word-break:break-all;">' . esc_html( $safe_url ) . '</code></div>';
			}
			if ( ! empty( $res['error'] ) ) {
				$extra .= '<pre style="white-space:pre-wrap;max-height:200px;overflow:auto;">' . esc_html( (string) $res['error'] ) . '</pre>';
			} elseif ( ! empty( $res['raw'] ) ) {
				$raw = (string) $res['raw'];
				if ( strlen( $raw ) > 900 ) $raw = substr( $raw, 0, 900 ) . '…';
				$extra .= '<details style="margin-top:6px;"><summary>RAW</summary><pre style="white-space:pre-wrap;max-height:260px;overflow:auto;">' . esc_html( $raw ) . '</pre></details>';
			}

			if ( $extra ) {
				$out .= '<div class="lusso-resales-debug" style="background:#fff3cd;border:1px solid #ffeeba;padding:10px;margin-top:8px;">' . $extra . '</div>';
			}
		}

		return $out;
	}

	/**
	 * Lee la primera clave existente de una lista de posibles nombres.
	 *
	 * @param array        $arr  Array fuente (ítem de resultados).
	 * @param array|string $keys Lista de claves posibles (o una sola).
	 * @param mixed        $default Valor por defecto.
	 * @return mixed
	 */
	private function safe_read( $arr, $keys, $default = '' ) {
		if ( ! is_array( $arr ) ) return $default;
		$keys = (array) $keys;
		foreach ( $keys as $k ) {
			if ( isset( $arr[ $k ] ) && $arr[ $k ] !== '' ) {
				return $arr[ $k ];
			}
		}
		return $default;
	}
}

endif;
