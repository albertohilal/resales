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
        // Filtros GET
        $get = $_GET;
        $a = shortcode_atts([
            'api_id'            => get_option('resales_api_apiid', ''),
            'agency_filter_id'  => get_option('resales_api_agency_filterid',''),
            'new_devs'          => get_option('resales_api_newdevs','include'),
            'per_page'          => isset($get['per_page']) && in_array((int)$get['per_page'],[15,30,45]) ? (int)$get['per_page'] : 15,
            'page'              => isset($get['page']) ? max(1,(int)$get['page']) : 1,
            'order'             => isset($get['order']) && in_array($get['order'],['recent','price_asc','price_desc']) ? $get['order'] : 'recent',
            'loc'               => isset($get['loc']) ? sanitize_text_field($get['loc']) : '',
            'type'              => isset($get['type']) ? sanitize_text_field($get['type']) : '',
            'price_max'         => isset($get['price_max']) ? (int)$get['price_max'] : '',
            'query_id'          => isset($get['qid'])  ? sanitize_text_field($get['qid']) : '',
        ], $atts, 'lusso_developments');

        $client = Resales_Client::instance();

        $args = [
            'p_PageSize' => (int)$a['per_page'],
            'p_PageNo'   => (int)$a['page'],
            'p_new_devs' => $a['new_devs'],
        ];
    if (!empty($a['api_id']))           $args['P_ApiId'] = (int)$a['api_id'];
    elseif (!empty($a['agency_filter_id'])) $args['P_Agency_FilterId'] = (int)$a['agency_filter_id'];

    if ($a['loc']!=='')      $args['P_Location'] = $a['loc'];
    if ($a['type']!=='')     $args['P_PropertyTypes'] = $a['type'];
    if ($a['price_max']!=='')$args['P_Max'] = (int)$a['price_max'];
    if (!empty($a['query_id'])) $args['P_QueryId'] = $a['query_id'];
    // Orden
    if ($a['order']==='price_asc')   $args['P_SortType']='price_asc';
    elseif ($a['order']==='price_desc') $args['P_SortType']='price_desc';
    else $args['P_SortType']='recent';

        $res = $client->search($args);

        if (!$res['ok']) {
            return '<div class="resales-error">Error al consultar la API: '.esc_html($res['error']).'</div>';
        }

        $data = $res['data'];
        $qi   = $data['QueryInfo'] ?? [];
    $items= $data['Property'] ?? [];

        ob_start();
        // Filtros: barra superior
        ?>
        <form method="get" class="lr-filters" style="margin-bottom:24px;">
            <select name="loc">
                <option value="">Elija una ubicación</option>
                <option value="Benahavis"<?= $a['loc']==='Benahavis'?' selected':''; ?>>Benahavis</option>
                <option value="Casares"<?= $a['loc']==='Casares'?' selected':''; ?>>Casares</option>
                <option value="Estepona"<?= $a['loc']==='Estepona'?' selected':''; ?>>Estepona</option>
                <option value="Marbella"<?= $a['loc']==='Marbella'?' selected':''; ?>>Marbella</option>
                <option value="Sotogrande"<?= $a['loc']==='Sotogrande'?' selected':''; ?>>Sotogrande</option>
            </select>
            <select name="type">
                <option value="">Todos los tipos</option>
                <option value="Villa"<?= $a['type']==='Villa'?' selected':''; ?>>Villa</option>
                <option value="Apartment"<?= $a['type']==='Apartment'?' selected':''; ?>>Apartamento</option>
                <option value="Townhouse"<?= $a['type']==='Townhouse'?' selected':''; ?>>Adosado</option>
                <option value="Penthouse"<?= $a['type']==='Penthouse'?' selected':''; ?>>Ático</option>
            </select>
            <input name="price_max" type="number" min="0" step="1000" placeholder="Precio hasta" value="<?= esc_attr($a['price_max']); ?>">
            <select name="order">
                <option value="recent"<?= $a['order']==='recent'?' selected':''; ?>>Más recientes</option>
                <option value="price_asc"<?= $a['order']==='price_asc'?' selected':''; ?>>Precio ascendente</option>
                <option value="price_desc"<?= $a['order']==='price_desc'?' selected':''; ?>>Precio descendente</option>
            </select>
            <select name="per_page">
                <option value="15"<?= $a['per_page']==15?' selected':''; ?>>15 props. por página</option>
                <option value="30"<?= $a['per_page']==30?' selected':''; ?>>30 props. por página</option>
                <option value="45"<?= $a['per_page']==45?' selected':''; ?>>45 props. por página</option>
            </select>
            <button type="submit">Buscar</button>
            <button type="reset" onclick="window.location.href=window.location.pathname;return false;">Reset</button>
        </form>
        <?php
        echo '<div class="lr-grid">';
        foreach ($items as $p){
            $title = $client->build_title($p);
            $img   = !empty($p['first_image_url']) ? $p['first_image_url'] : ($p['MainImage'] ?? '');
            $desc  = wp_strip_all_tags($p['Description'] ?? '');
            $desc  = mb_substr($desc,0,180).(mb_strlen($desc)>180?'':'');
            $loc   = $p['Location'] ?? ($p['Area'] ?? ($p['Province'] ?? ''));
            $price = isset($p['Price']) && $p['Price']>0 ? esc_html(($p['Currency'] ?? '').' '.number_format((float)$p['Price'],0,',','.')) : 'Consultar precio';
            $details_url = !empty($p['DetailUrl']) ? esc_url($p['DetailUrl']) : '#';
            ?>
            <article class="lr-card" style="border:1px solid #eee;padding:12px;margin:10px;max-width:340px;">
                <?php if ($img): ?>
                    <figure class="lr-card__media">
                        <img loading="lazy" decoding="async" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title . ' – ' . $loc); ?>" style="width:100%;height:auto;">
                    </figure>
                <?php else: ?>
                    <div class="lr-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
                <h3 style="margin:.6em 0;"><?php echo esc_html($title ?: 'Propiedad'); ?></h3>
                <?php if ($loc): ?><div class="lr-card__loc" style="font-size:.95em;color:#888;"><?php echo esc_html($loc); ?></div><?php endif; ?>
                <p style="font-size:.9em;color:#555;"><?php echo esc_html($desc); ?></p>
                <div class="lr-card__price" style="font-weight:600;">Desde <?php echo $price; ?></div>
                <a class="lr-card__cta" href="<?php echo $details_url; ?>" style="display:inline-block;margin-top:8px;padding:6px 16px;background:#0073aa;color:#fff;border-radius:4px;text-decoration:none;">Ver detalles</a>
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
