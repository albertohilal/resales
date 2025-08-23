<?php
/**
 * Cliente para WebAPI V6 de Resales Online
 * @package Lusso_Resales
 */
if (!defined('ABSPATH')) exit;

class Lusso_Resales_Client {
    /**
     * Realiza búsqueda en Resales Online WebAPI V6
     * @param array $args
     * @return array [ok, code, data, error, raw]
     */
    public static function search(array $args = []) : array {
        // Log temporal para depuración
        $debug_url = '';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $debug_url = $url;
        }
        $p1 = get_option('lusso_resales_p1');
        $p2 = get_option('lusso_resales_p2');
        if (empty($p1) || empty($p2)) {
            return [
                'ok' => false,
                'code' => null,
                'data' => null,
                'error' => 'missing_credentials',
                'raw' => null
            ];
        }
        $defaults = [
            'lang' => 'es',
            'results_per_page' => 6,
            'page' => 1,
            'FilterAgencyId' => 4
        ];
        $params = array_merge($defaults, $args, [
            'p1' => sanitize_text_field($p1),
            'p2' => sanitize_text_field($p2)
        ]);
        $base_url = 'https://webapi.resales-online.com/V6/Search';
        $url = esc_url_raw(add_query_arg($params, $base_url));
        $cache_key = 'lusso_resales_' . md5($url);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return [
                'ok' => true,
                'code' => 200,
                'data' => $cached,
                'error' => null,
                'raw' => null
            ];
        }
        $response = wp_remote_get($url, [
            'timeout' => 20
        ]);
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'code' => null,
                'data' => null,
                'error' => 'WP_Error: ' . $response->get_error_message(),
                'raw' => null
            ];
        }
        $code = wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
        $data = null;
        $error = null;
        if ($code !== 200) {
            $error = 'HTTP ' . $code;
            if ($code == 403 && strpos($raw, 'Missing Authentication Token') !== false) {
                $error .= ': Missing Authentication Token (GET o payload incorrecto)';
            } elseif (in_array($code, [401, 403])) {
                $error .= ': Credenciales incorrectas o IP no whitelisteada';
            } elseif (in_array($code, [502, 504])) {
                $error .= ': Latencia o endpoint incorrecto';
            }
            return [
                'ok' => false,
                'code' => $code,
                'data' => null,
                'error' => $error,
                'raw' => $raw
            ];
        } else {
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'ok' => false,
                    'code' => $code,
                    'data' => null,
                    'error' => 'JSON decode error: ' . json_last_error_msg(),
                    'raw' => $raw
                ];
            }
            set_transient($cache_key, $data, 60);
        }
        return [
            'ok' => $code === 200 && is_array($data),
            'code' => $code,
            'data' => $data,
            'error' => $error,
            'raw' => $raw,
            'debug_url' => $debug_url
        ];
    }
}
