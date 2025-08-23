<?php
if (!defined('ABSPATH')) exit;

class Lusso_Resales_Settings {
    private static $instance = null;

    // NUEVO: método que reclama el bootstrap
    public static function init() {
        return self::instance();
    }

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks de settings/menús, etc.
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            'Lusso Resales',
            'Lusso Resales',
            'manage_options',
            'lusso-resales',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('lusso_resales', 'lusso_resales_p1');
        register_setting('lusso_resales', 'lusso_resales_p2');
        register_setting('lusso_resales', 'lusso_resales_lang');
        register_setting('lusso_resales', 'lusso_resales_pagesize');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Lusso Resales – Ajustes</h1>
            <form method="post" action="options.php">
                <?php settings_fields('lusso_resales'); ?>
                <?php do_settings_sections('lusso_resales'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="lusso_resales_p1">P1</label></th>
                        <td><input type="text" id="lusso_resales_p1" name="lusso_resales_p1" value="<?php echo esc_attr(get_option('lusso_resales_p1', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lusso_resales_p2">P2</label></th>
                        <td><input type="text" id="lusso_resales_p2" name="lusso_resales_p2" value="<?php echo esc_attr(get_option('lusso_resales_p2', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lusso_resales_lang">Lang</label></th>
                        <td><input type="text" id="lusso_resales_lang" name="lusso_resales_lang" value="<?php echo esc_attr(get_option('lusso_resales_lang', 'es')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lusso_resales_pagesize">PageSize</label></th>
                        <td><input type="number" min="1" id="lusso_resales_pagesize" name="lusso_resales_pagesize" value="<?php echo esc_attr(get_option('lusso_resales_pagesize', '12')); ?>" class="small-text"></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
