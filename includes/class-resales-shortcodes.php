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
     * Inicializador estático.
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
        // Inicializaciones globales si necesitas
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
     * Consulta la API y muestra los resultados.
     */
    public function render_developments( $atts = array(), $content = null ) : string {
        // Instanciar el cliente de la API
        if (class_exists('Resales_Client')) {
            $client = new Resales_Client();
        } else {
            return '<div class="resales-developments">No se encuentra el cliente de la API.</div>';
        }

        // Puedes pasar filtros desde $atts si quieres (ejemplo: ['type' => 'promotion'])
        $result = $client->search();

        // Verifica el resultado
        if ( empty($result['ok']) || empty($result['data']) ) {
            return '<div class="resales-developments">No hay desarrollos disponibles o hubo un error en la API.</div>';
        }

        $developments = $result['data'];

        // Para depuración: ver estructura en el log de WP
        error_log(print_r($developments, true));

        // Si la API devuelve un array con la clave 'results', úsala (adáptalo según tu API)
        if (isset($developments['results']) && is_array($developments['results'])) {
            $developments = $developments['results'];
        }

        if (empty($developments) || !is_array($developments)) {
            return '<div class="resales-developments">No se encontraron desarrollos.</div>';
        }

        // Renderizado básico, adapta los campos según la estructura real de tu API
        $html = '<div class="resales-developments-list" style="display:flex;flex-wrap:wrap;gap:24px;">';

        foreach ($developments as $dev) {
            // Buscar el campo de nombre correcto
            $name = '';
            if (isset($dev['name']) && $dev['name']) {
                $name = esc_html($dev['name']);
            } elseif (isset($dev['title']) && $dev['title']) {
                $name = esc_html($dev['title']);
            } elseif (isset($dev['development_name']) && $dev['development_name']) {
                $name = esc_html($dev['development_name']);
            } elseif (isset($dev['property_name']) && $dev['property_name']) {
                $name = esc_html($dev['property_name']);
            } else {
                $name = 'Sin nombre';
            }
            $location = isset($dev['location']) ? esc_html($dev['location']) : '';
            $image    = isset($dev['image']) ? esc_url($dev['image']) : '';
            $url      = isset($dev['url']) ? esc_url($dev['url']) : '#';

            $html .= '<div class="development" style="width:300px;border:1px solid #ccc;padding:16px;">';

            if ($image) {
                $html .= '<a href="'.$url.'" target="_blank"><img src="'.$image.'" alt="'.$name.'" style="width:100%;height:auto;"></a>';
            }

            $html .= '<h3><a href="'.$url.'" target="_blank">'.$name.'</a></h3>';
            $html .= '<p>'.$location.'</p>';
            $html .= '<a href="'.$url.'" target="_blank" style="display:inline-block;margin-top:8px;">Ver más</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza el formulario de búsqueda (ejemplo mínimo).
     */
    public function render_search_form( $atts = array(), $content = null ) : string {
    $html = '<form class="resales-search-form" method="get" action="">';
    $html .= '<input type="text" name="q" placeholder="Buscar por palabra clave..." value="'.esc_attr($_GET['q'] ?? '').'" /> ';
    $html .= '<select name="type"><option value="">Tipo</option><option value="apartment"'.(($_GET['type'] ?? '')=='apartment'?' selected':'').'>Apartamento</option><option value="villa"'.(($_GET['type'] ?? '')=='villa'?' selected':'').'>Villa</option></select> ';
    $html .= '<input type="number" name="minprice" placeholder="Precio mínimo" min="0" value="'.esc_attr($_GET['minprice'] ?? '').'" /> ';
    $html .= '<input type="number" name="maxprice" placeholder="Precio máximo" min="0" value="'.esc_attr($_GET['maxprice'] ?? '').'" /> ';
    $html .= '<button type="submit">Buscar</button>';
    $html .= '</form>';

    // Mostrar todas las propiedades si no hay filtros
    $filters = array();
    if (!empty($_GET['q'])) $filters['q'] = sanitize_text_field($_GET['q']);
    if (!empty($_GET['type'])) $filters['type'] = sanitize_text_field($_GET['type']);
    if (!empty($_GET['minprice'])) $filters['minprice'] = intval($_GET['minprice']);
    if (!empty($_GET['maxprice'])) $filters['maxprice'] = intval($_GET['maxprice']);
    $html .= $this->render_developments($filters);
    return $html;
    }

}

endif;