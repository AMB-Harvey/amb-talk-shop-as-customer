<?php

class WPCleverWpcsa
{
    protected static $settings = [];
    protected static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function __construct()
    {
        self::$settings = (array)get_option('wpcsa_settings', []);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_footer', [$this, 'footer']);

        // search
        add_action('wp_ajax_wpcsa_search', [$this, 'ajax_search']);
        add_action('wp_ajax_nopriv_wpcsa_search', [$this, 'ajax_search']);

        // login
        add_action('wp_ajax_wpcsa_login', [$this, 'ajax_login']);
        add_action('wp_ajax_nopriv_wpcsa_login', [$this, 'ajax_login']);

        // back
        add_action('wp_ajax_wpcsa_back', [$this, 'ajax_back']);
        add_action('wp_ajax_nopriv_wpcsa_back', [$this, 'ajax_back']);

        // logout
        add_action('wp_logout', [$this, 'wp_logout']);

        // settings
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'admin_menu']);

        // links
        add_filter('plugin_action_links', [$this, 'action_links'], 10, 2);
        add_filter('plugin_row_meta', [$this, 'row_meta'], 10, 2);
    }

    function enqueue_scripts()
    {
        wp_enqueue_style('wpcsa-frontend', WPCSA_URI . 'assets/css/frontend.css');
        wp_enqueue_script('wpcsa-frontend', WPCSA_URI . 'assets/js/frontend.js', ['jquery'], WPCSA_VERSION, true);
        wp_localize_script('wpcsa-frontend', 'wpcsa_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpcsa-security'),
            ]
        );
    }

    function is_usable()
    {
        $current_user = wp_get_current_user();
        $usable_roles = (array)self::get_setting('usable_roles', []);

        if (empty($usable_roles)) {
            $usable_roles = ['administrator'];
        }

        foreach ($current_user->roles as $role) {
            if (in_array($role, $usable_roles)) {
                return true;
            }
        }

        return false;
    }

    function footer()
    {
        if (self::is_usable() || isset($_COOKIE['wpcsa_user'])) {
            $current_user = wp_get_current_user();

            if ($current_user->ID) {
                ?>
                <div class="wpcsa-bar">
                                <span>
                                <?php echo sprintf( /* translators: username */ esc_html__('Logged in as %s', 'amb-talk-shop-as-customer'), '<strong>' . esc_html($current_user->user_nicename) . '</strong>'); ?> (<a
                                            href="<?php echo wp_logout_url(); ?>"><?php esc_html_e('Logout', 'amb-talk-shop-as-customer'); ?></a>)
                                </span>

                    <?php if (isset($_COOKIE['wpcsa_user'], $_COOKIE['wpcsa_key'])) {
                        $back_user = unserialize(stripcslashes($_COOKIE['wpcsa_user']));
                        ?>
                        <span>
                                        <a href="#" class="wpcsa-back"
                                           data-id="<?php echo esc_attr($back_user['ID']); ?>"
                                           data-key="<?php echo esc_attr($_COOKIE['wpcsa_key']); ?>">
                                            <?php echo sprintf( /* translators: username */ esc_html__('Back to %s', 'amb-talk-shop-as-customer'), '<strong>' . esc_html($back_user['user_nicename']) . '</strong>'); ?>
                                            </a>
                                    </span>
                    <?php }

                    if (self::is_usable()) { ?>
                        <span>
                                        <a href="#" class="wpcsa-choose">
                                                <?php esc_html_e('Switch user', 'amb-talk-shop-as-customer'); ?>
                                            </a>
                                    </span>
                    <?php } ?>
                </div>
                <div class="wpcsa-search-wrap">
                    <div class="wpcsa-search-inner">
                        <div class="wpcsa-search-form">
                            <div class="wpcsa-search-close"></div>
                            <div class="wpcsa-search-input">
                                <label for="wpcsa_search_input"></label><input type="search"
                                                                               id="wpcsa_search_input"
                                                                               placeholder="<?php esc_attr_e('Type any keyword to search...', 'amb-talk-shop-as-customer'); ?>"/>
                            </div>
                            <div class="wpcsa-search-result"></div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }

    function ajax_search()
    {
        if (!self::is_usable() || !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wpcsa-security')) {
            die('Permissions check failed!');
        }

        $switch_roles = (array)self::get_setting('switch_roles', []);

        if (empty($switch_roles)) {
            $switch_roles = ['wpcsa_all'];
        }

        $cid = get_current_user_id();
        $keyword = trim(sanitize_text_field($_POST['keyword']));
        $users_args = [
            'search' => '*' . $keyword . '*'
        ];

        if (!in_array('wpcsa_all', $switch_roles)) {
            $users_args['role__in'] = $switch_roles;
        }

        $users_query = new WP_User_Query($users_args);
        $users = $users_query->get_results();

        if (!empty($users)) {
            echo '<ul>';

            foreach ($users as $user) {
                $author_info = get_userdata($user->ID);

                echo '<li>';
                echo '<div class="item-inner">';
                echo '<div class="item-image">' . get_avatar($user->ID) . '</div>';
                echo '<div class="item-name"><span>' . esc_html($author_info->user_nicename) . '</span> <span>' . esc_html($author_info->user_email) . '</span></div>';

                if ($cid !== $user->ID) {
                    echo '<div class="item-login wpcsa-login" data-id="' . esc_attr($user->ID) . '">→</div>';
                }

                echo '</div>';
                echo '</li>';
            }

            echo '</ul>';
        } else {
            echo '<ul><span>' . sprintf( /* translators: keyword */ esc_html__('No results found for "%s"', 'amb-talk-shop-as-customer'), esc_html($keyword)) . '</span></ul>';
        }

        wp_die();
    }

    function ajax_login()
    {

        if ($_POST['ambtalk']) {

        } else if (!self::is_usable() || !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wpcsa-security')) {
            die('Permissions check failed!');
        }

        $switch_user_id = absint(sanitize_text_field($_POST['uid']));

        if ($switch_user_id) {
            $switch_user = get_user_by('id', $switch_user_id);

            if ($switch_user) {
                // save current user data before sign out
                $current_user = wp_get_current_user();
                $current_user_data = [
                    'ID' => $current_user->ID,
                    'user_nicename' => $current_user->user_nicename,
                    'display_name' => $current_user->display_name
                ];

                wp_clear_auth_cookie();
                wp_set_current_user($switch_user_id);
                wp_set_auth_cookie($switch_user_id, true, is_ssl());
                do_action('wp_login', $switch_user->user_login, $switch_user);
                add_filter('wc_session_use_secure_cookie', '__return_true');

                $secure = apply_filters('wpcsa_cookie_secure', wc_site_is_https() && is_ssl());
                $httponly = apply_filters('wpcsa_cookie_httponly', true);

                if (!isset($_COOKIE['wpcsa_user'])) {
                    $user_key = self::generate_key();

                    update_user_meta($current_user->ID, 'wpcsa_key', $user_key);
                    wc_setcookie('wpcsa_user', serialize($current_user_data), time() + 604800, $secure, $httponly);
                    wc_setcookie('wpcsa_key', $user_key, time() + 604800, $secure, $httponly);
                }

                // using shop as customer
                wc_setcookie('wpcsa_ambtalk', 1);
                echo 'success';
                wp_die();
            }
        }

        echo 'error';
        wp_die();
    }

    function ajax_back()
    {
        if (!is_user_logged_in() || !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wpcsa-security')) {
            die('Permissions check failed!');
        }

        $user_id = absint(sanitize_text_field($_POST['uid']));
        $user_key = sanitize_text_field($_POST['key']);

        if ($user_id && !empty($user_key)) {
            $switch_user = get_user_by('id', $user_id);
            $switch_user_key = get_user_meta($user_id, 'wpcsa_key', true);

            if ($switch_user && !empty($switch_user_key) && ($switch_user_key === $user_key)) {
                // remove saved data
                self::wp_logout();

                wp_clear_auth_cookie();
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id, true, is_ssl());
                do_action('wp_login', $switch_user->user_login, $switch_user);
                add_filter('wc_session_use_secure_cookie', '__return_true');

                echo 'success';
                wp_die();
            }
        }

        echo 'error';
        wp_die();
    }

    function wp_logout()
    {
        $secure = apply_filters('wpcsa_cookie_secure', wc_site_is_https() && is_ssl());
        $httponly = apply_filters('wpcsa_cookie_httponly', true);
        wc_setcookie('wpcsa_user', '', time() + 604800, $secure, $httponly);
        wc_setcookie('wpcsa_key', '', time() + 604800, $secure, $httponly);
        unset($_COOKIE['wpcsa_user'], $_COOKIE['wpcsa_key']);
    }

    function generate_key()
    {
        $key = '';
        $key_str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key_str_len = strlen($key_str);

        for ($i = 0; $i < 6; $i++) {
            $key .= $key_str[random_int(0, $key_str_len - 1)];
        }

        return $key;
    }

    function register_settings()
    {
        register_setting('wpcsa_settings', 'wpcsa_settings');
    }

    function admin_menu()
    {
        add_submenu_page('AMBTalkCreateOrder', 'Shop as a Customer', 'Shop as a Customer', 'manage_options', 'wpclever-wpcsa', [
            $this,
            'admin_menu_content'
        ]);
    }

    function admin_menu_content()
    {
        $active_tab = sanitize_key($_GET['tab'] ?? 'settings');
        ?>
        <div class="wpclever_settings_page wrap">
            <h1 class="wpclever_settings_page_title"><?php echo esc_html__('Amoeba Talk create order as a Customer for WooCommerce', 'amb-talk-shop-as-customer') . ' ' . esc_html(WPCSA_VERSION) . ' ' . (defined('WPCSA_PREMIUM') ? '<span class="premium" style="display: none">' . esc_html__('Premium', 'amb-talk-shop-as-customer') . '</span>' : ''); ?></h1>

            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) { ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings updated.', 'amb-talk-shop-as-customer'); ?></p>
                </div>
            <?php } ?>
            <div class="wpclever_settings_page_nav">
                <h2 class="nav-tab-wrapper">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpclever-wpcsa&tab=settings')); ?>"
                       class="<?php echo esc_attr($active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab'); ?>">
                        <?php esc_html_e('Settings', 'amb-talk-shop-as-customer'); ?>
                    </a>
                </h2>
            </div>
            <div class="wpclever_settings_page_content">
                <?php if ($active_tab === 'settings') {
                    global $wp_roles;
                    $usable_roles = (array)self::get_setting('usable_roles', []);
                    $switch_roles = (array)self::get_setting('switch_roles', []);

                    if (empty($usable_roles)) {
                        $usable_roles = ['administrator'];
                    }

                    if (empty($switch_roles)) {
                        $switch_roles = ['wpcsa_all'];
                    }
                    ?>
                    <form method="post" action="options.php">
                        <table class="form-table">
                            <tr class="heading">
                                <th colspan="2">
                                    <?php esc_html_e('General', 'amb-talk-shop-as-customer'); ?>
                                </th>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Who can use it?', 'amb-talk-shop-as-customer'); ?></th>
                                <td>
                                    <p class="description"><?php esc_html_e('Hold down the Ctrl (or Command) button to select multiple options.', 'amb-talk-shop-as-customer'); ?></p>
                                    <label>
                                        <select name="wpcsa_settings[usable_roles][]" multiple
                                                style="height: 200px; margin-top: 10px; margin-bottom: 10px">
                                            <?php foreach ($wp_roles->roles as $role => $details) {
                                                echo '<option value="' . esc_attr($role) . '" ' . (in_array($role, $usable_roles) ? 'selected' : '') . '>' . esc_html($details['name']) . '</option>';
                                            } ?>
                                        </select> </label>
                                    <p class="description"><?php esc_html_e('Choose the role(s) that are allowed to access and use the user switching functionality.', 'amb-talk-shop-as-customer'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Who they can switch to?', 'amb-talk-shop-as-customer'); ?></th>
                                <td>
                                    <p class="description"><?php esc_html_e('Hold down the Ctrl (or Command) button to select multiple options.', 'amb-talk-shop-as-customer'); ?></p>
                                    <label>
                                        <select name="wpcsa_settings[switch_roles][]" multiple
                                                style="height: 200px; margin-top: 10px; margin-bottom: 10px">
                                            <option value="wpcsa_all" <?php echo(in_array('wpcsa_all', $switch_roles) ? 'selected' : ''); ?>><?php esc_html_e('All', 'amb-talk-shop-as-customer'); ?></option>
                                            <?php foreach ($wp_roles->roles as $role => $details) {
                                                echo '<option value="' . esc_attr($role) . '" ' . (in_array($role, $switch_roles) ? 'selected' : '') . '>' . esc_html($details['name']) . '</option>';
                                            } ?>
                                        </select> </label>
                                    <p class="description"><?php esc_html_e('Choose the role(s) that can be searched for and switched to without the need to log out then log in again. Only users with the selected roles will appear in the search box. This allows shop owners to easily view their shop and interact in a different role.', 'amb-talk-shop-as-customer'); ?>
                                        <span style="color: #c9356e"><?php esc_html_e('*Warning: Be cautious when assigning the Administrator role to other users.*', 'amb-talk-shop-as-customer'); ?></span>
                                    </p>
                                </td>
                            </tr>
                            <tr class="submit">
                                <th colspan="2">
                                    <?php settings_fields('wpcsa_settings'); ?><?php submit_button(); ?>
                                </th>
                            </tr>
                        </table>
                    </form>
                <?php } ?>
            </div><!-- /.wpclever_settings_page_content -->

        </div>
        <?php
    }

    function action_links($links, $file)
    {
        static $plugin;

        if (!isset($plugin)) {
            $plugin = plugin_basename(__FILE__);
        }

        if ($plugin === $file) {
            $settings = '<a href="' . esc_url(admin_url('admin.php?page=wpclever-wpcsa&tab=settings')) . '">' . esc_html__('Settings', 'amb-talk-shop-as-customer') . '</a>';
            array_unshift($links, $settings);
        }

        return (array)$links;
    }

    function row_meta($links, $file)
    {
        static $plugin;

        if (!isset($plugin)) {
            $plugin = plugin_basename(__FILE__);
        }

        if ($plugin === $file) {
            $row_meta = [
                'support' => '',
            ];

            return array_merge($links, $row_meta);
        }

        return (array)$links;
    }

    public static function get_settings()
    {
        return apply_filters('wpcsa_get_settings', self::$settings);
    }

    public static function get_setting($name, $default = false)
    {
        if (!empty(self::$settings) && isset(self::$settings[$name])) {
            $setting = self::$settings[$name];
        } else {
            $setting = get_option('wpcsa_' . $name, $default);
        }

        return apply_filters('wpcsa_get_setting', $setting, $name, $default);
    }
}