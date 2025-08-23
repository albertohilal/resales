<?php
/**
 * Cliente para Resales Online WebAPI v6 (solo GET)
 *
 * @package Lusso_Resales
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Lusso_Resales_Client' ) ) :

class Lusso_Resales_Client {

	/**
	 * Endpoint base (sin la parte del recurso final).
	 * @var string
	 */
	private $base = 'https://webapi.resales-online.com/V6/Search';

	/**
	 * User-Agent para las peticiones.
	 * @var string
	 */
	private $ua = 'LussoResalesPlugin/1.0';

	/**
	 * Tiempo de caché (segundos).
	 * @var int
	 */
	private $cache_ttl = 60;

	/**
	 * Parámetros permitidos por la API (en minúsculas).
	 * Amplía esta lista según documentación.
	 * @var string[]
	 */
	private $allowed = array(
		'p1', 'p2',
		'lang', 'page', 'pagesize',
		'agency_id', 'country', 'area', 'subarea',
		'orderby', 'order', 'type', 'category', 'minprice', 'maxprice',
		'beds', 'baths',
		'features', 'location', 'q', // por si añades filtros libres
	);

	/**
	 * Valores por defecto comunes.
	 * @var array
	 */
	private $defaults = array(
		'lang'     => 'es',
		'page'     => 1,
		'pagesize' => 6,
	);

	/**
	 * Entrada principal: busca propiedades.
	 *
	 * @param array $args Parámetros de búsqueda (lang, page, pagesize, agency_id, etc).
	 * @return array Estructura: ['ok' => bool, 'code' => int, 'data' => mixed, 'error' => string|null, 'raw' => string|null, 'url' => string]
	 */
	public function search( array $args = array() ) : array {

		// 1) Lee credenciales
		$p1 = get_option( 'lusso_resales_p1' );
		$p2 = get_option( 'lusso_resales_p2' );

		if ( empty( $p1 ) || empty( $p2 ) ) {
			return array(
				'ok'   => false,
				'code' => 0,
				'data' => null,
				'error'=> __( 'Faltan credenciales P1/P2 en Ajustes → Lusso Resales.', 'lusso-resales' ),
				'raw'  => null,
				'url'  => '',
			);
		}

		// 2) Normaliza, sanea y filtra parámetros permitidos
		$params = $this->normalize_args( $args );

		// credenciales obligatorias
		$params['p1'] = sanitize_text_field( $p1 );
		$params['p2'] = sanitize_text_field( $p2 );

		// 3) Construye URL final
		$url = $this->build_url( $params );

		// 4) Caché por URL (opcional)
		$cache_key = 'lusso_resales_' . md5( $url );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return array(
				'ok'   => true,
				'code' => 200,
				'data' => $cached,
				'error'=> null,
				'raw'  => null,
				'url'  => $url,
			);
		}

		// 5) GET
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 20,
				'user-agent' => $this->ua,
			)
		);

		// 6) Errores de transporte
		if ( is_wp_error( $response ) ) {
			$msg = $response->get_error_message();
			$this->log_debug( "LUSSO HTTP (WP_Error): {$msg} | URL: {$url}" );
			return array(
				'ok'   => false,
				'code' => 0,
				'data' => null,
				'error'=> 'HTTP: ' . $msg,
				'raw'  => null,
				'url'  => $url,
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// guarda cuerpo en DEBUG si no es 200 o es JSON inválido
		if ( 200 !== $code ) {
			$this->log_debug( "LUSSO HTTP {$code} | URL: {$url} | BODY (recortado): " . substr( (string) $body, 0, 2000 ) );
			return array(
				'ok'   => false,
				'code' => $code,
				'data' => null,
				'error'=> 'HTTP: ' . $code,
				'raw'  => $body,
				'url'  => $url,
			);
		}

		// 7) Decodifica JSON
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$this->log_debug( "LUSSO JSON inválido | URL: {$url} | BODY (recortado): " . substr( (string) $body, 0, 2000 ) );
			return array(
				'ok'   => false,
				'code' => $code,
				'data' => null,
				'error'=> 'JSON inválido: ' . json_last_error_msg(),
				'raw'  => $body,
				'url'  => $url,
			);
		}

		// 8) Cachea y devuelve
		set_transient( $cache_key, $data, $this->cache_ttl );

		return array(
			'ok'   => true,
			'code' => $code,
			'data' => $data,
			'error'=> null,
			'raw'  => null,
			'url'  => $url,
		);
	}

	/* ============================================================
	 * Helpers
	 * ============================================================ */

	/**
	 * Normaliza y sanea parámetros.
	 *
	 * - Fuerza minúsculas en keys.
	 * - Aplica defaults.
	 * - Filtra solo los permitidos.
	 * - Sanea strings/enteros básicos.
	 *
	 * @param array $args
	 * @return array
	 */
	private function normalize_args( array $args ) : array {
		// keys a minúsculas
		$lower = array();
		foreach ( $args as $k => $v ) {
			$lower[ strtolower( (string) $k ) ] = $v;
		}

		// aplica defaults
		$merged = array_merge( $this->defaults, $lower );

		// filtra por permitidos
		$filtered = array();
		foreach ( $merged as $k => $v ) {
			if ( in_array( $k, $this->allowed, true ) ) {
				$filtered[ $k ] = $v;
			}
		}

		// saneo básico
		foreach ( $filtered as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$filtered[ $k ] = sanitize_text_field( (string) $v );
			} elseif ( is_array( $v ) ) {
				// listas => csv
				$filtered[ $k ] = sanitize_text_field( implode( ',', array_map( 'sanitize_text_field', $v ) ) );
			}
		}

		// tipados básicos
		if ( isset( $filtered['page'] ) ) {
			$filtered['page'] = max( 1, (int) $filtered['page'] );
		}
		if ( isset( $filtered['pagesize'] ) ) {
			$filtered['pagesize'] = max( 1, (int) $filtered['pagesize'] );
		}

		return $filtered;
	}

	/**
	 * Construye URL final, escapada.
	 *
	 * @param array $params
	 * @return string
	 */
	private function build_url( array $params ) : string {
		// Asegura base absoluta
		$base = $this->base;
		$qs   = add_query_arg( $params, $base );
		$url  = esc_url_raw( $qs );

		$this->log_debug( 'LUSSO URL API: ' . $url );

		return $url;
	}

	/**
	 * Log condicional en modo debug.
	 *
	 * @param string $msg
	 * @return void
	 */
	private function log_debug( string $msg ) : void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			error_log( $msg );
		}
	}

}

endif;
