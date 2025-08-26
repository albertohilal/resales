<?php
if (!defined('ABSPATH')) exit;

class Resales_Settings {
    private static $instance = null;

    // Singleton: inicialización segura
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            'Resales API',
            'Resales API',
            'manage_options',
            'resales-api',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
    register_setting('resales_api', 'resales_api_p1');
    register_setting('resales_api', 'resales_api_p2');
    register_setting('resales_api', 'resales_api_agency_id');
    register_setting('resales_api', 'resales_api_lang');
    register_setting('resales_api', 'resales_api_pagesize');
    register_setting('resales_api', 'resales_api_user_agent');
    register_setting('resales_api', 'resales_api_timeout');
    register_setting('resales_api', 'resales_api_cache_ttl');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Resales API – Ajustes</h1>
            <form method="post" action="options.php">
                <?php settings_fields('resales_api'); ?>
                <?php do_settings_sections('resales_api'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="resales_api_p1">P1</label></th>
                        <td><input type="text" id="resales_api_p1" name="resales_api_p1" value="<?php echo esc_attr(get_option('resales_api_p1', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_p2">P2</label></th>
                        <td><input type="text" id="resales_api_p2" name="resales_api_p2" value="<?php echo esc_attr(get_option('resales_api_p2', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_agency_id">Agency ID</label></th>
                        <td><input type="text" id="resales_api_agency_id" name="resales_api_agency_id" value="<?php echo esc_attr(get_option('resales_api_agency_id', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_lang">Lang</label></th>
                        <td><input type="text" id="resales_api_lang" name="resales_api_lang" value="<?php echo esc_attr(get_option('resales_api_lang', 'es')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_pagesize">PageSize</label></th>
                        <td><input type="number" min="1" id="resales_api_pagesize" name="resales_api_pagesize" value="<?php echo esc_attr(get_option('resales_api_pagesize', '12')); ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_user_agent">User-Agent</label></th>
                        <td><input type="text" id="resales_api_user_agent" name="resales_api_user_agent" value="<?php echo esc_attr(get_option('resales_api_user_agent', 'ResalesAPIPlugin/1.0')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_timeout">Timeout (segundos)</label></th>
                        <td><input type="number" min="1" id="resales_api_timeout" name="resales_api_timeout" value="<?php echo esc_attr(get_option('resales_api_timeout', '20')); ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resales_api_cache_ttl">Cache TTL (segundos)</label></th>
                        <td><input type="number" min="1" id="resales_api_cache_ttl" name="resales_api_cache_ttl" value="<?php echo esc_attr(get_option('resales_api_cache_ttl', '60')); ?>" class="small-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
