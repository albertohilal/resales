<?php
/**
 * Plugin Name: Lusso Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes y cliente HTTP).
 * Version: 3.1.0
 * Author: Lusso Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lusso-resales
 */

if (!defined('ABSPATH')) {
    exit;
}

/** ───────────────────────
 *  Requisitos mínimos
 *  ─────────────────────── */
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Lusso Resales</strong>: requiere PHP 7.4 o superior.</p></div>';
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
define('LUSSO_RESALES_PATH', plugin_dir_path(__FILE__));
define('LUSSO_RESALES_URL', plugin_dir_url(__FILE__));
define('LUSSO_RESALES_VERSION', '3.1.0');

/** ───────────────────────
 *  Helper: require seguro
 *  ─────────────────────── */
if (!function_exists('lusso_resales_safe_require')) {
    function lusso_resales_safe_require(string $relative): bool {
        $path = LUSSO_RESALES_PATH . ltrim($relative, '/');
        if (is_readable($path)) {
            require_once $path;
            return true;
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[LUSSO] No se pudo cargar: ' . $path);
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
        $ok  = lusso_resales_safe_require('includes/class-lusso-resales-client.php');
        $ok &= lusso_resales_safe_require('includes/class-lusso-resales-settings.php');
        $ok &= lusso_resales_safe_require('includes/class-lusso-resales-shortcodes.php');
        // Admin es opcional si no estás en admin
        if (is_admin()) {
            lusso_resales_safe_require('includes/class-lusso-resales-admin.php');
        }

        // Si algún include falló, registra y aborta sin fatal.
        if (!$ok) {
            throw new RuntimeException('Faltan archivos requeridos en /includes/. Revisa el log para rutas ausentes.');
        }

        // 2) Inicializar componentes solo si existen las clases
        if (class_exists('Lusso_Resales_Settings')) {
            Lusso_Resales_Settings::init();
        } else {
            throw new RuntimeException('Clase Lusso_Resales_Settings no disponible (¿nombre de clase/archivo distinto?).');
        }

        if (class_exists('Lusso_Resales_Shortcodes')) {
            Lusso_Resales_Shortcodes::init();
        } else {
            throw new RuntimeException('Clase Lusso_Resales_Shortcodes no disponible.');
        }

        if (is_admin() && class_exists('Lusso_Resales_Admin')) {
            Lusso_Resales_Admin::init();
        }

    } catch (Throwable $e) {
        // No tumbes el sitio, deja diagnóstico claro:
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[LUSSO BOOT ERROR] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
        add_action('admin_notices', function () use ($e) {
            echo '<div class="notice notice-error"><p><strong>Lusso Resales</strong>: error al iniciar. '
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
        wp_die('Lusso Resales requiere PHP 7.4 o superior.');
    }
    // No hagas llamadas HTTP aquí.
});

register_deactivation_hook(__FILE__, function () {
    // Limpiezas ligeras si las necesitas
});
