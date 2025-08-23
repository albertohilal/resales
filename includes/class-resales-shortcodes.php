<?php
/**
 * Shortcodes para el plugin Resales API
 *
 * Provee:
 *  - [resales_developments ...]   → lista propiedades / developments
 *  - [resales_search_form]        → formulario simple de búsqueda (opcional)
 *
 * @package Resales_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Resales_Shortcodes' ) ) :

class Resales_Shortcodes {
	public static function instance() : self {
		static $instance = null;
		if ($instance === null) {
			$instance = new self();
			$instance->register();
		}
		return $instance;
	}

	public function register() : void {
		add_shortcode( 'resales_developments', array( $this, 'render_developments' ) );
		add_shortcode( 'resales_search_form',  array( $this, 'render_search_form' ) );
	}

	public function render_developments( $atts = array() ) : string {
		$atts = shortcode_atts(
			array(
				'filter_agency_id' => '',
				'per_page'         => '',
				'agency_id'        => '',
				'lang'             => get_option( 'resales_api_lang', 'es' ),
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
				'class'            => 'resales-grid',
				'card_class'       => 'resales-card',
				'show_price'       => '1',
				'show_link'        => '1',
			),
			$atts,
			'resales_developments'
		);
		if ( $atts['per_page'] && empty( $atts['pagesize'] ) ) {
			$atts['pagesize'] = $atts['per_page'];
		}
		if ( $atts['filter_agency_id'] && empty( $atts['agency_id'] ) ) {
			$atts['agency_id'] = $atts['filter_agency_id'];
		}
		$params = array(
			'lang'      => sanitize_text_field( (string) $atts['lang'] ),
			'page'      => max( 1, absint( $atts['page'] ) ),
			'pagesize'  => $atts['pagesize'] !== '' ? max( 1, absint( $atts['pagesize'] ) ) : 6,
		);
		$optional = array('agency_id','orderby','order','minprice','maxprice','area','subarea','type','q');
		foreach ( $optional as $k ) {
			if ( $atts[ $k ] !== '' ) {
				$params[ $k ] = is_scalar( $atts[ $k ] ) ? sanitize_text_field( (string) $atts[ $k ] ) : '';
			}
		}
		if ( ! class_exists( 'Resales_Client' ) ) {
			return $this->render_notice(
				__( 'No se encontró el cliente de API. Revisa la instalación del plugin.', 'resales-api' ),
				true
			);
		}
		$client = new Resales_Client();
		$res    = $client->search( $params );
		if ( empty( $res['ok'] ) ) {
			return $this->render_error_block( $res );
		}
		$data = $res['data'];
		$items = array();
		foreach ( array( 'items', 'results', 'Developments', 'Properties', 'data' ) as $key ) {
			if ( is_array( $data ) && isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				$items = $data[ $key ];
				break;
			}
		}
		if ( empty( $items ) && is_array( $data ) ) {
			$is_indexed = array_keys( $data ) === range( 0, count( $data ) - 1 );
			if ( $is_indexed ) {
				$items = $data;
			}
		}
		if ( empty( $items ) || ! is_array( $items ) ) {
			return $this->render_notice( __( 'No hay resultados para los filtros aplicados.', 'resales-api' ) );
		}
		$container_class = sanitize_html_class( $atts['class'] );
		$card_class      = sanitize_html_class( $atts['card_class'] );
		$show_price      = $atts['show_price'] === '1';
		$show_link       = $atts['show_link'] === '1';
		ob_start();
		?>
		<div class="<?php echo esc_attr( $container_class ); ?>">
			<?php foreach ( $items as $it ) :
				$title = $this->safe_read( $it, array( 'Title', 'title', 'Name', 'name' ), '' );
				$price = $this->safe_read( $it, array( 'Price', 'price' ), '' );
				$link  = $this->safe_read( $it, array( 'Url', 'URL', 'Permalink', 'permalink', 'link' ), '' );
				$img   = $this->safe_read( $it, array( 'Image', 'MainImage', 'image', 'thumbnail' ), '' );
				$title_out = $title ? wp_kses_post( $title ) : '';
				$price_out = $price ? wp_kses_post( $price ) : '';
				$link_out  = $link  ? esc_url( $link ) : '';
				$img_out   = $img   ? esc_url( $img )  : '';
				?>
				<article class="<?php echo esc_attr( $card_class ); ?>">
					<?php if ( $img_out ) : ?>
						<div class="resales-thumb">
							<img src="<?php echo $img_out; ?>" alt="" loading="lazy">
						</div>
					<?php endif; ?>
					<div class="resales-body">
						<?php if ( $title_out ) : ?>
							<h3 class="resales-title"><?php echo $title_out; ?></h3>
						<?php endif; ?>
						<?php if ( $show_price && $price_out ) : ?>
							<p class="resales-price"><strong><?php esc_html_e( 'Precio:', 'resales-api' ); ?></strong> <?php echo $price_out; ?></p>
						<?php endif; ?>
						<?php if ( $show_link && $link_out ) : ?>
							<p class="resales-link">
								<a href="<?php echo $link_out; ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'Ver propiedad', 'resales-api' ); ?>
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

	public function render_search_form( $atts = array() ) : string {
		$atts = shortcode_atts(
			array(
				'method'       => 'get',
				'action'       => '',
				'lang'         => get_option( 'resales_api_lang', 'es' ),
				'pagesize'     => 6,
				'show_results' => '1',
				'class'        => 'resales-form',
			),
			$atts,
			'resales_search_form'
		);
		$method   = strtolower( (string) $atts['method'] ) === 'post' ? 'post' : 'get';
		$action   = $atts['action'] ? esc_url( (string) $atts['action'] ) : esc_url( add_query_arg( array() ) );
		$class    = sanitize_html_class( $atts['class'] );
		$q_lang     = isset( $_REQUEST['lang'] )      ? sanitize_text_field( (string) $_REQUEST['lang'] )      : $atts['lang'];
		$q_pagesize = isset( $_REQUEST['pagesize'] )  ? max( 1, absint( $_REQUEST['pagesize'] ) )              : (int) $atts['pagesize'];
		$q_min      = isset( $_REQUEST['minprice'] )  ? sanitize_text_field( (string) $_REQUEST['minprice'] )  : '';
		$q_max      = isset( $_REQUEST['maxprice'] )  ? sanitize_text_field( (string) $_REQUEST['maxprice'] )  : '';
		$q_area     = isset( $_REQUEST['area'] )      ? sanitize_text_field( (string) $_REQUEST['area'] )      : '';
		$q_q        = isset( $_REQUEST['q'] )         ? sanitize_text_field( (string) $_REQUEST['q'] )         : '';
		ob_start();
		?>
		<form class="<?php echo esc_attr( $class ); ?>" method="<?php echo esc_attr( $method ); ?>" action="<?php echo $action; ?>">
			<div class="resales-field">
				<label><?php esc_html_e( 'Idioma', 'resales-api' ); ?></label>
				<input type="text" name="lang" value="<?php echo esc_attr( $q_lang ); ?>" />
			</div>
			<div class="resales-field">
				<label><?php esc_html_e( 'Resultados por página', 'resales-api' ); ?></label>
				<input type="number" name="pagesize" min="1" max="50" value="<?php echo esc_attr( $q_pagesize ); ?>" />
			</div>
			<div class="resales-field">
				<label><?php esc_html_e( 'Precio mínimo', 'resales-api' ); ?></label>
				<input type="text" name="minprice" value="<?php echo esc_attr( $q_min ); ?>" />
			</div>
			<div class="resales-field">
				<label><?php esc_html_e( 'Precio máximo', 'resales-api' ); ?></label>
				<input type="text" name="maxprice" value="<?php echo esc_attr( $q_max ); ?>" />
			</div>
			<div class="resales-field">
				<label><?php esc_html_e( 'Área', 'resales-api' ); ?></label>
				<input type="text" name="area" value="<?php echo esc_attr( $q_area ); ?>" />
			</div>
			<div class="resales-field">
				<label><?php esc_html_e( 'Búsqueda texto', 'resales-api' ); ?></label>
				<input type="text" name="q" value="<?php echo esc_attr( $q_q ); ?>" />
			</div>
			<div class="resales-actions">
				<button type="submit"><?php esc_html_e( 'Buscar', 'resales-api' ); ?></button>
			</div>
		</form>
		<?php
		$html = (string) ob_get_clean();
		if ( $atts['show_results'] === '1' ) {
			$merge = array(
				'lang'     => $q_lang,
				'pagesize' => $q_pagesize,
			);
			if ( $q_min !== '' ) $merge['minprice'] = $q_min;
			if ( $q_max !== '' ) $merge['maxprice'] = $q_max;
			if ( $q_area !== '' ) $merge['area']     = $q_area;
			if ( $q_q   !== '' ) $merge['q']        = $q_q;
			$atts_str = '';
			foreach ( $merge as $k => $v ) {
				$atts_str .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
			}
			$html .= do_shortcode( '[resales_developments' . $atts_str . ']' );
		}
		return $html;
	}

	private function render_notice( string $msg, bool $is_error = false ) : string {
		$cls = $is_error ? 'resales-error' : 'resales-notice';
		return '<div class="' . esc_attr( $cls ) . '">' . esc_html( $msg ) . '</div>';
	}

	private function render_error_block( array $res ) : string {
		$msg_public = __( 'No se encontraron propiedades o hubo un error en la consulta.', 'resales-api' );
		$out  = '<div class="resales-error" style="color:#c00;">' . esc_html( $msg_public ) . '</div>';
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
				$out .= '<div class="resales-debug" style="background:#fff3cd;border:1px solid #ffeeba;padding:10px;margin-top:8px;">' . $extra . '</div>';
			}
		}
		return $out;
	}

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
