<?php
/**
 * Plugin Name: Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes y cliente HTTP).
 * Version: 3.1.0
 * Author: Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/** ───────────────────────
 *  Requisitos mínimos
 *  ─────────────────────── */
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
    echo '<div class="notice notice-error"><p><strong>Resales API</strong>: requiere PHP 7.4 o superior.</p></div>';
    });
    return;
}
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    // No arrancar lógica durante instalación/actualización de WP.
    return;
}

/** ───────────────────────
 *  Constantes de ruta/URL
 *  ─────────────────────── */
define('RESALES_API_PATH', plugin_dir_path(__FILE__));
define('RESALES_API_URL', plugin_dir_url(__FILE__));
define('RESALES_API_VERSION', '3.1.0');

/** ───────────────────────
 *  Helper: require seguro
 *  ─────────────────────── */
if (!function_exists('resales_api_safe_require')) {
    function resales_api_safe_require(string $relative): bool {
        $path = RESALES_API_PATH . ltrim($relative, '/');
        if (is_readable($path)) {
            require_once $path;
            return true;
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[RESALES] No se pudo cargar: ' . $path);
        }
        return false;
    }
}

/** ───────────────────────
 *  Bootstrap diferido
 *  ─────────────────────── */
add_action('plugins_loaded', function () {
    try {
        // 1) Cargar dependencias (ajusta los nombres si tus archivos difieren)
        $ok  = resales_api_safe_require('includes/class-resales-client.php');
        $ok &= resales_api_safe_require('includes/class-resales-settings.php');
        $ok &= resales_api_safe_require('includes/class-resales-shortcodes.php');
        // Admin es opcional si no estás en admin
        if (is_admin()) {
            resales_api_safe_require('includes/class-resales-admin.php');
        }

        // Si algún include falló, registra y aborta sin fatal.
        if (!$ok) {
            throw new RuntimeException('Faltan archivos requeridos en /includes/. Revisa el log para rutas ausentes.');
        }

        // 2) Inicializar componentes solo si existen las clases
        if (class_exists('Resales_Settings')) {
            Resales_Settings::instance();
        } else {
            throw new RuntimeException('Clase Resales_Settings no disponible (¿nombre de clase/archivo distinto?).');
        }

        if (class_exists('Resales_Shortcodes')) {
            Resales_Shortcodes::instance();
        } else {
            throw new RuntimeException('Clase Resales_Shortcodes no disponible.');
        }

        if (is_admin() && class_exists('Resales_Admin')) {
            Resales_Admin::instance();
        }

    } catch (Throwable $e) {
        // No tumbes el sitio, deja diagnóstico claro:
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[RESALES BOOT ERROR] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
        add_action('admin_notices', function () use ($e) {
            echo '<div class="notice notice-error"><p><strong>Resales API</strong>: error al iniciar. '
               . 'Revisa <code>wp-content/debug.log</code>.<br>'
               . esc_html($e->getMessage()) . '</p></div>';
        });
    }
});

/** ───────────────────────
 *  Activación / Desactivación
 *  ─────────────────────── */
register_activation_hook(__FILE__, function () {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die('Resales API requiere PHP 7.4 o superior.');
    }
    // No hagas llamadas HTTP aquí.
});

register_deactivation_hook(__FILE__, function () {
    // Limpiezas ligeras si las necesitas
});
