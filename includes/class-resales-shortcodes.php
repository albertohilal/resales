<?php
/**
 * Shortcodes para el plugin Resales API
 *
 * Provee:
 *  - [lusso_developments ...]   → lista propiedades / developments
 *  - [resales_developments ...] → alias para compatibilidad
 *  - [resales_search_form]      → formulario simple de búsqueda (opcional)
 *
 * @package Resales_API
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Resales_Shortcodes' ) ) :

class Resales_Shortcodes {

    /**
     * Instancia única (Singleton)
     * @var Resales_Shortcodes|null
     */
    private static $instance = null;

    /**
     * Inicializador estático (más cómodo desde el bootstrap del plugin).
     */
    public static function init() : Resales_Shortcodes {
        return self::instance();
    }

    /**
     * Devuelve la instancia del singleton
     */
    public static function instance() : Resales_Shortcodes {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->register();
        }
        return self::$instance;
    }

    /**
     * Constructor público
     */
    public function __construct() {
        // Puedes poner aquí inicializaciones globales si necesitas
    }

    /**
     * Registro de shortcodes.
     */
    public function register() : void {
        add_shortcode( 'lusso_developments',       array( $this, 'render_developments' ) );
        add_shortcode( 'resales_developments',     array( $this, 'render_developments' ) );
        add_shortcode( 'resales_search_form',      array( $this, 'render_search_form' ) );
    }

    /**
     * Renderiza el listado de desarrollos al invocar el shortcode.
     * Debe ser público y devolver un string.
     */
    public function render_developments( $atts = array(), $content = null ) : string {
        // Aquí va el código real para mostrar desarrollos.
        // Ejemplo mínimo:
        return '<div class="resales-developments">Aquí iría el listado de desarrollos…</div>';
    }

    /**
     * Renderiza el formulario de búsqueda (ejemplo mínimo).
     */
    public function render_search_form( $atts = array(), $content = null ) : string {
        return '<form class="resales-search-form"><input type="text" name="q" placeholder="Buscar..."><button type="submit">Buscar</button></form>';
    }

    // Puedes agregar más métodos públicos aquí según tus necesidades.

}

endif;