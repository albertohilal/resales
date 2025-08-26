<?php
/**
 * Cliente para Resales Online WebAPI v6 (solo GET)
 *
 * @package Resales_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Resales_Client' ) ) :

class Resales_Client {

	/**
	 * Endpoint base (sin la parte del recurso final).
	 * @var string
	 */
	private $base = 'https://webapi.resales-online.com/V6/Search';

	/**
	 * User-Agent para las peticiones (configurable).
	 * @var string
	 */
	private $ua;

	/**
	 * Tiempo de caché (segundos, configurable).
	 * @var int
	 */
	private $cache_ttl;

	/**
	 * Timeout para peticiones (configurable).
	 * @var int
	 */
	private $timeout;

	/**
	 * Reintentos en caso de error temporal.
	 * @var int
	 */
	private $max_retries = 2;

	/**
	 * Parámetros permitidos por la API (en minúsculas).
	 * @var string[]
	 */
	private $allowed = array(
		'p1', 'p2',
		'lang', 'page', 'pagesize',
		'agency_id', 'country', 'area', 'subarea',
		'orderby', 'order', 'type', 'category', 'minprice', 'maxprice',
		'beds', 'baths',
		'features', 'location', 'q'
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

	public function __construct() {
		// Permitir configuración desde opciones
		$this->ua        = get_option('resales_api_user_agent', 'ResalesAPIPlugin/1.0');
		$this->cache_ttl = absint(get_option('resales_api_cache_ttl', 60));
		$this->timeout   = absint(get_option('resales_api_timeout', 20));
	}

	/**
	 * Entrada principal: busca propiedades.
	 * @param array $args
	 * @return array
	 */
	public function search( array $args = array() ) : array {
		$p1 = get_option( 'resales_api_p1' );
		$p2 = get_option( 'resales_api_p2' );
		$agency_id = get_option( 'resales_api_agency_id' );
		if ( empty( $p1 ) || empty( $p2 ) || empty( $agency_id ) ) {
			return array(
				'ok'   => false,
				'code' => 0,
				'data' => null,
				'error'=> __( 'Faltan credenciales P1/P2/agency_id en Ajustes → Resales API.', 'resales-api' ),
				'raw'  => null,
				'url'  => '',
			);
		}
		$params = $this->normalize_args( $args );
		$params['p1'] = sanitize_text_field( $p1 );
		$params['p2'] = sanitize_text_field( $p2 );
		$params['agency_id'] = sanitize_text_field( $agency_id );
		$url = $this->build_url( $params );
		// Log explícito para soporte: URL y parámetros
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[RESALES API] URL generada: ' . $url);
			error_log('[RESALES API] Parámetros: ' . print_r($params, true));
		}
		$cache_key = 'resales_api_' . md5( $url );
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
		$response = $this->do_request_with_retries($url);
		if ( is_wp_error( $response ) ) {
			$msg = $response->get_error_message();
			$this->log_debug( "RESALES HTTP (WP_Error): {$msg} | URL: {$url}" );
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
		$headers = wp_remote_retrieve_headers( $response );
		$this->log_debug( "RESALES HTTP {$code} | URL: {$url} | HEADERS: " . print_r($headers, true) . " | BODY: " . substr( (string) $body, 0, 2000 ) );
		if ( 200 !== $code ) {
			return array(
				'ok'   => false,
				'code' => $code,
				'data' => null,
				'error'=> 'HTTP: ' . $code,
				'raw'  => $body,
				'url'  => $url,
			);
		}
		$data = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$this->log_debug( "RESALES JSON inválido | URL: {$url} | BODY: " . substr( (string) $body, 0, 2000 ) );
			return array(
				'ok'   => false,
				'code' => $code,
				'data' => null,
				'error'=> 'JSON inválido: ' . json_last_error_msg(),
				'raw'  => $body,
				'url'  => $url,
			);
		}
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

	private function do_request_with_retries($url) {
		$attempts = 0;
		while ($attempts <= $this->max_retries) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'    => $this->timeout,
					'user-agent' => $this->ua,
				)
			);
			if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
				return $response;
			}
			$attempts++;
		}
		return $response;
	}

	private function normalize_args( array $args ) : array {
		$lower = array();
		foreach ( $args as $k => $v ) {
			$lower[ strtolower( (string) $k ) ] = $v;
		}
		$merged = array_merge( $this->defaults, $lower );
		$filtered = array();
		foreach ( $merged as $k => $v ) {
			if ( in_array( $k, $this->allowed, true ) ) {
				$filtered[ $k ] = $v;
			}
		}
		foreach ( $filtered as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$filtered[ $k ] = sanitize_text_field( (string) $v );
			} elseif ( is_array( $v ) ) {
				$filtered[ $k ] = sanitize_text_field( implode( ',', array_map( 'sanitize_text_field', $v ) ) );
			}
		}
		if ( isset( $filtered['page'] ) ) {
			$filtered['page'] = max( 1, (int) $filtered['page'] );
		}
		if ( isset( $filtered['pagesize'] ) ) {
			$filtered['pagesize'] = max( 1, (int) $filtered['pagesize'] );
		}
		return $filtered;
	}

	private function build_url( array $params ) : string {
		$base = $this->base;
		$qs   = add_query_arg( $params, $base );
		$url  = esc_url_raw( $qs );
		$this->log_debug( 'RESALES URL API: ' . $url );
		return $url;
	}

	private function log_debug( string $msg ) : void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $msg );
		}
	}
}
endif;
