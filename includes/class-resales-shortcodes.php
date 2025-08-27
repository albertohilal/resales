<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('Resales_Shortcodes')):

class Resales_Shortcodes {
    private static $instance = null;
    public static function instance(){ return self::$instance ?: (self::$instance = new self()); }
    private function __construct(){
        add_shortcode('lusso_developments', [$this,'sc_developments']);
        add_shortcode('resales_developments', [$this,'sc_developments']); // alias
        add_shortcode('resales_search_form', [$this,'sc_search_form']);
    }

    /** [lusso_developments api_id="65503" new_devs="include" per_page="10" page="1"] */
    public function sc_developments($atts = []){
        $a = shortcode_atts([
            'api_id'            => get_option('resales_api_apiid', ''),     // P_ApiId
            'agency_filter_id'  => get_option('resales_api_agency_filterid',''), // alternativo
            'new_devs'          => get_option('resales_api_newdevs','include'),
            'per_page'          => 10,
            'page'              => isset($_GET['page']) ? (int)$_GET['page'] : 1,
            'query_id'          => isset($_GET['qid'])  ? sanitize_text_field($_GET['qid']) : '',
            // filtros simples vía GET opcional:
            'min'               => isset($_GET['min']) ? (int)$_GET['min'] : '',
            'max'               => isset($_GET['max']) ? (int)$_GET['max'] : '',
            'beds'              => isset($_GET['beds'])? sanitize_text_field($_GET['beds']) : '',
            'baths'             => isset($_GET['baths'])? sanitize_text_field($_GET['baths']) : '',
            'location'          => isset($_GET['loc']) ? sanitize_text_field($_GET['loc']) : '',
        ], $atts, 'lusso_developments');

        $client = Resales_Client::instance();

        $args = [
            'p_PageSize' => (int)$a['per_page'],
            'p_PageNo'   => (int)$a['page'],
            'p_new_devs' => $a['new_devs'],
        ];
        if (!empty($a['api_id']))           $args['P_ApiId'] = (int)$a['api_id'];
        elseif (!empty($a['agency_filter_id'])) $args['P_Agency_FilterId'] = (int)$a['agency_filter_id'];

        if ($a['min']!=='')   $args['P_Min'] = (int)$a['min'];
        if ($a['max']!=='')   $args['P_Max'] = (int)$a['max'];
        if ($a['beds']!=='')  $args['P_Beds'] = $a['beds'];
        if ($a['baths']!=='') $args['P_Baths'] = $a['baths'];
        if ($a['location']!=='') $args['P_Location'] = $a['location'];

        if (!empty($a['query_id'])) $args['P_QueryId'] = $a['query_id'];

        $res = $client->search($args);

        if (!$res['ok']) {
            return '<div class="resales-error">Error al consultar la API: '.esc_html($res['error']).'</div>';
        }

        $data = $res['data'];
        $qi   = $data['QueryInfo'] ?? [];
        $items= $data['Property'] ?? [];

        ob_start();
        echo '<div class="resales-grid">';
        foreach ($items as $p){
            $title = $client->build_title($p);
            $img   = $p['MainImage'] ?? '';
            $isDev = !empty($p['NewDevelopment']); // si el feed lo marca
            ?>
            <article class="resales-card" style="border:1px solid #eee;padding:12px;margin:10px;max-width:340px;">
                <?php if ($img): ?>
                    <img loading="lazy" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" style="width:100%;height:auto;">
                <?php endif; ?>
                <h3 style="margin:.6em 0;"><?php echo esc_html($title ?: 'Propiedad'); ?></h3>
                <?php if ($isDev): ?><span style="display:inline-block;background:#eee;border-radius:4px;padding:2px 6px;font-size:.85em;">Nueva promoción</span><?php endif; ?>
                <p style="font-size:.9em;color:#555;"><?php echo esc_html(wp_strip_all_tags($p['Description'] ?? '')); ?></p>
                <p style="font-weight:600;"><?php echo esc_html(($p['Currency'] ?? '').' '.number_format((float)($p['Price'] ?? 0),0,',','.')); ?></p>
            </article>
            <?php
        }
        echo '</div>';

        // Paginación simple: añade qid a los enlaces (recomendación V6) :contentReference[oaicite:11]{index=11}
        if (!empty($qi)){
            $qid   = urlencode($qi['QueryId'] ?? ($a['query_id'] ?? ''));
            $page  = (int)$a['page'];
            $per   = (int)$a['per_page'];
            $base  = remove_query_arg(['page','qid']);
            echo '<nav class="resales-pager" style="margin:10px 0;">';
            if ($page > 1){
                $prev = add_query_arg(['page'=>$page-1,'qid'=>$qid], $base);
                echo '<a href="'.esc_url($prev).'">« Anterior</a> ';
            }
            $next = add_query_arg(['page'=>$page+1,'qid'=>$qid], $base);
            echo '<a href="'.esc_url($next).'">Siguiente »</a>';
            echo '</nav>';
        }

        return ob_get_clean();
    }

    /** Formulario mínimo para demos */
    public function sc_search_form($atts = []){
        ob_start(); ?>
        <form method="get" class="resales-form" style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin:12px 0;">
            <input name="loc"  placeholder="Ubicación" value="<?php echo esc_attr($_GET['loc'] ?? ''); ?>">
            <input name="beds" placeholder="Dorm." value="<?php echo esc_attr($_GET['beds'] ?? ''); ?>">
            <input name="baths" placeholder="Baños" value="<?php echo esc_attr($_GET['baths'] ?? ''); ?>">
            <input name="min"  placeholder="Precio mín." value="<?php echo esc_attr($_GET['min'] ?? ''); ?>">
            <input name="max"  placeholder="Precio máx." value="<?php echo esc_attr($_GET['max'] ?? ''); ?>">
            <button type="submit">Buscar</button>
        </form>
        <?php
        return ob_get_clean();
    }
}

endif;
