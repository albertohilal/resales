<?php
// Encolar CSS personalizado para el grid y filtros
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('lusso-resales', plugins_url('assets/css/lusso-resales.css', __FILE__), [], '1.0');
});
/**
 * Plugin Name: Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes, diagnóstico y cliente HTTP).
 * Version: 3.2.0
 * Author: Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

/* ─────────────────────────────
 * Helpers de carga segura
 * ───────────────────────────── */
function resales_api_require($rel_path){
    $path = plugin_dir_path(__FILE__) . ltrim($rel_path, '/');
    if (file_exists($path)) { require_once $path; return true; }
    error_log('[Resales API] No se pudo cargar: ' . $rel_path);
    return false;
}

/* ─────────────────────────────
 * Bootstrap
 * ───────────────────────────── */
add_action('plugins_loaded', function () {
    // Si colocas los archivos en /includes/, ajusta las rutas aquí:
    resales_api_require('includes/class-resales-client.php');
    resales_api_require('includes/class-resales-settings.php');
    resales_api_require('includes/class-resales-shortcodes.php');
    resales_api_require('includes/class-resales-admin.php');

    // Inicializar
    if (class_exists('Resales_Settings'))  Resales_Settings::instance();
    if (class_exists('Resales_Shortcodes')) Resales_Shortcodes::instance();
    if (is_admin() && class_exists('Resales_Admin')) Resales_Admin::instance();
});

/* ─────────────────────────────
 * Activación / Desactivación
 * ───────────────────────────── */
register_activation_hook(__FILE__, function () {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die('Resales API requiere PHP 7.4 o superior.');
    }
});
