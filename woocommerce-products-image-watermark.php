<?php
/**
 * Plugin Name: Product Watermark for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/product-watermark-for-woocommerce/
 * Description: Allows you to add watermark to images that applied to products
 * Version: 1.0.6
 * Author: BeRocket
 * Requires at least: 4.0
 * Author URI: http://berocket.com
 * Text Domain: BeRocket_image_watermark_domain
 * Domain Path: /languages/
 */
define( "BeRocket_image_watermark_version", '1.0.6' );
define( "BeRocket_image_watermark_domain", 'BeRocket_image_watermark_domain'); 
define( "image_watermark_TEMPLATE_PATH", plugin_dir_path( __FILE__ ) . "templates/" );
load_plugin_textdomain('BeRocket_image_watermark_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
require_once(plugin_dir_path( __FILE__ ).'includes/admin_notices.php');
require_once(plugin_dir_path( __FILE__ ).'includes/functions.php');
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class BeRocket_image_watermark {

    /**
     * Defaults values
     */
    public static $defaults = array(
        'disable_image'     => '0',
        'shop_single'       => array(
            'text'              => '',
            'text_alpha'        => '30',
            'text_angle'        => '0',
            'font_size'         => '20',
            'image'             => array(0 => ''),
            'top'               => array(0 => '25'),
            'left'              => array(0 => '25'),
        ),
        'custom_css'        => '',
        'plugin_key'        => '',
    );
    public static $values = array(
        'settings_name' => 'br-image_watermark-options',
        'option_page'   => 'br-image_watermark',
        'premium_slug'  => 'woocommerce-products-image-watermark',
    );
    
    function __construct () {
        global $br_watermark_size;
        $br_watermark_size = false;
        register_activation_hook(__FILE__, array( __CLASS__, 'activation' ) );
        register_deactivation_hook(__FILE__, array( __CLASS__, 'deactivation' ) );
        register_uninstall_hook(__FILE__, array( __CLASS__, 'uninstall' ) );

        if ( ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) && 
            br_get_woocommerce_version() >= 2.1 ) {
            $options = self::get_option();
            
            add_action ( 'init', array( __CLASS__, 'init' ) );
            add_action ( 'wp_head', array( __CLASS__, 'set_styles' ) );
            add_action ( 'admin_init', array( __CLASS__, 'admin_init' ) );
            add_action ( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
            add_action ( 'admin_menu', array( __CLASS__, 'options' ) );
            add_action( 'current_screen', array( __CLASS__, 'current_screen' ) );
            add_action( "wp_ajax_br_image_watermark_settings_save", array ( __CLASS__, 'save_settings' ) );
            add_action( "wp_ajax_br_image_watermark_restore", array ( __CLASS__, 'restore_images' ) );
            if( @ ! $options['disable_image'] ) {
                add_filter( 'image_downsize', array( __CLASS__, 'replace_image' ), 200, 3 );
            }
            add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
            $plugin_base_slug = plugin_basename( __FILE__ );
            add_filter( 'plugin_action_links_' . $plugin_base_slug, array( __CLASS__, 'plugin_action_links' ) );
            add_filter( 'is_berocket_settings_page', array( __CLASS__, 'is_settings_page' ) );
        }
    }
    public static function is_settings_page($settings_page) {
        if( ! empty($_GET['page']) && $_GET['page'] == self::$values[ 'option_page' ] ) {
            $settings_page = true;
        }
        return $settings_page;
    }
    public static function plugin_action_links($links) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page='.self::$values['option_page'] ) . '" title="' . __( 'View Plugin Settings', 'BeRocket_products_label_domain' ) . '">' . __( 'Settings', 'BeRocket_products_label_domain' ) . '</a>',
		);
		return array_merge( $action_links, $links );
    }
    public static function plugin_row_meta($links, $file) {
        $plugin_base_slug = plugin_basename( __FILE__ );
        if ( $file == $plugin_base_slug ) {
			$row_meta = array(
				'docs'    => '<a href="http://berocket.com/docs/plugin/'.self::$values['premium_slug'].'" title="' . __( 'View Plugin Documentation', 'BeRocket_products_label_domain' ) . '" target="_blank">' . __( 'Docs', 'BeRocket_products_label_domain' ) . '</a>',
				'premium'    => '<a href="http://berocket.com/product/'.self::$values['premium_slug'].'" title="' . __( 'View Premium Version Page', 'BeRocket_products_label_domain' ) . '" target="_blank">' . __( 'Premium Version', 'BeRocket_products_label_domain' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}
		return (array) $links;
    }
    public static function init () {
        wp_register_style( 'font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
        wp_enqueue_style( 'font-awesome' );
    }
    /**
     * Function set styles in wp_head WordPress action
     *
     * @return void
     */
    public static function set_styles () {
        $options = self::get_option();
        echo '<style>'.$options['custom_css'].'</style>';
    }
    /**
     * Load template
     *
     * @access public
     *
     * @param string $name template name
     *
     * @return void
     */
    public static function br_get_template_part( $name = '' ) {
        $template = '';

        // Look in your_child_theme/woocommerce-image_watermark/name.php
        if ( $name ) {
            $template = locate_template( "woocommerce-image_watermark/{$name}.php" );
        }

        // Get default slug-name.php
        if ( ! $template && $name && file_exists( image_watermark_TEMPLATE_PATH . "{$name}.php" ) ) {
            $template = image_watermark_TEMPLATE_PATH . "{$name}.php";
        }

        // Allow 3rd party plugin filter template file from their plugin
        $template = apply_filters( 'image_watermark_get_template_part', $template, $name );

        if ( $template ) {
            load_template( $template, false );
        }
    }

    public static function admin_enqueue_scripts() {
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        } else {
            wp_enqueue_style( 'thickbox' );
            wp_enqueue_script( 'media-upload' );
            wp_enqueue_script( 'thickbox' );
        }
    }

    /**
     * Function adding styles/scripts and settings to admin_init WordPress action
     *
     * @access public
     *
     * @return void
     */
    public static function admin_init () {
        wp_enqueue_script( 'berocket_image_watermark_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), BeRocket_image_watermark_version );
        wp_register_style( 'berocket_image_watermark_admin_style', plugins_url( 'css/admin.css', __FILE__ ), "", BeRocket_image_watermark_version );
        wp_enqueue_style( 'berocket_image_watermark_admin_style' );
    }
    public static function replace_image ($status, $post_id, $size) {
        $post_ready = get_option('br_watermarked');
        $post_to_restore = get_option('br_watermarked_to_restore');
        if( ! isset( $post_ready ) || ! is_array( $post_ready ) ) {
            $post_ready = array();
        }
        if( ! isset( $post_to_restore ) || ! is_array( $post_to_restore ) ) {
            $post_to_restore = array();
        }
        remove_filter( 'image_downsize', array( __CLASS__, 'replace_image' ), 200, 3 );
        $types = array('shop_catalog', 'shop_single', 'shop_thumbnail');
        $types = apply_filters('br_watermark_check_types', $types);
        if( in_array( $size, $types ) && ! in_array( $post_id, $post_ready ) ) {
            $post_ready[] = $post_id;
            self::add_watermark_to_images($post_id);
            update_option('br_watermarked', $post_ready);
            if( !in_array($post_id, $post_to_restore) ) {
                $post_to_restore[] = $post_id;
                update_option('br_watermarked_to_restore', $post_to_restore);
            }
        }
        $options = self::get_option();
        if( @ ! $options['disable_image'] ) {
            add_filter( 'image_downsize', array( __CLASS__, 'replace_image' ), 200, 3 );
        }
        return $status;
    }
    public static function add_watermark_to_images($post_id, $generation = 'create') {
        $options = self::get_option();
        $default_types = array( 'shop_catalog', 'shop_thumbnail', 'shop_single' );
        $types = array('shop_catalog', 'shop_thumbnail', 'thumbnail', 'shop_single', 'full');
        $upload_dir = wp_upload_dir();
        foreach( $types as $type ) {
            $data = wp_get_attachment_image_src( $post_id, $type );
            if( $data[3] || $type == 'full' ) {
                if( $type == 'full' ) {
                    $type = 'shop_single';
                } elseif( $type == 'thumbnail' ) {
                    $type = 'shop_thumbnail';
                } elseif(! in_array($type, $default_types)) {
                    $type = 'shop_single';
                }
                $data['path'] = str_replace($upload_dir['baseurl'], '', $data[0]);
                $img_url_concat = $upload_dir['basedir'].( (! empty($data['path']) && $data['path'][0] != '/' && substr($upload_dir['basedir'], -1) != '/') ? '/' : '' ).$data['path'];
                if( file_exists($img_url_concat) && ! empty($data['path']) ) {
                    self::backup_image($img_url_concat, $generation);
                    if( $generation == 'create' && 
                        isset( $options[$type] ) && 
                        isset($data['path']) 
                    ) {
                        $watermark = $options['shop_single'];
                        $mime_type = pathinfo($data['path'], PATHINFO_EXTENSION);
                        if( $mime_type == 'jpg' ) {
                            $mime_type = 'jpeg';
                        }
                        $create_function_data = 'imagecreatefrom' . $mime_type;
                        $image_content = $create_function_data($img_url_concat);
                        imagealphablending($image_content, true);
                        imagesavealpha($image_content, true);
                        $image_width = imagesx($image_content);
                        $image_height = imagesy($image_content);
                        $truecolor = imagecreatetruecolor($image_width, $image_height);
                        $transparent = imagecolorallocatealpha($truecolor, 0, 0, 0, 127);
                        imagefill($truecolor, 0, 0, $transparent);
                        imagecopyresampled($truecolor,$image_content,0,0,0,0, $image_width,$image_height,$image_width,$image_height);
                        $image_content = $truecolor;
                        $i = 0;
                        if( isset( $watermark['image'][$i] ) && $watermark['image'][$i] != '' ) {
                            $watermark_type = pathinfo($watermark['image'][$i], PATHINFO_EXTENSION);
                            if( $watermark_type == 'jpg' ) {
                                $watermark_type = 'jpeg';
                            }
                            $create_function_watermark = 'imagecreatefrom' . $watermark_type;
                            $watermark_content = $create_function_watermark($watermark['image'][$i]);
                            $watermark_width = imagesx($watermark_content);
                            $watermark_height = imagesy($watermark_content);
                            $ratio_w = $watermark_width / ($image_width / 100 * 50);
                            $ratio_h = $watermark_height / ($image_height / 100 * 50);
                            $ratio = max( $ratio_w, $ratio_h );
                            $weight_dif = 0;
                            $height_dif = 0;
                            $width = $watermark_width / $ratio_w;
                            $height = $watermark_height / $ratio_h;
                            $top = $image_height / 100 * $watermark['top'][$i] + $height_dif;
                            $left = $image_width / 100 * $watermark['left'][$i] + $weight_dif;
                            imagesavealpha($watermark_content, true);
                            imagealphablending($watermark_content, true);
                            imagecopyresampled( $image_content, $watermark_content, $left, $top, 0, 0, $width, $height, $watermark_width, $watermark_height );
                        }
                        imagesavealpha($image_content, true);
                        $function_save = 'image' . $mime_type;
                        if ( $mime_type=='jpeg' ) {
                            $function_save( $image_content, $upload_dir['basedir'].'/'.$data['path'], 100 );
                        } else {
                            $function_save( $image_content, $upload_dir['basedir'].'/'.$data['path'] );
                        }
                    }
                }
            }
        }
    }
    public static function backup_image($image_path, $generation = 'create') {
        $file_name = basename($image_path);
        $path = str_replace( $file_name, '', $image_path );
        $pattern = '/(\.\w+?$)/i';
        $replacement = '_br_backup$1';
        $new_file_name = preg_replace( $pattern, $replacement, $file_name);
        $new_path = $path.$new_file_name;
        if ( $generation == 'restore' && is_file( $new_path ) ) {
            rename( $new_path, $image_path );
        } elseif ( $generation == 'create' && is_file( $new_path ) ) {
            copy( $new_path, $image_path );
        } elseif ( $generation == 'create' && is_file( $image_path ) ) {
            copy( $image_path, $new_path );
        }
    }
    public static function watermark_call($generation = 'create') {
        if( $generation == 'restore' ) {
            $post_ready = get_option('br_watermarked_to_restore');
        } else {
            $post_ready = get_option('br_watermarked');
        }
        remove_filter( 'image_downsize', array( __CLASS__, 'replace_image' ), 200, 3 );
        if( isset($post_ready) && is_array($post_ready) ) {
            foreach( $post_ready as $array_i => $post_id ) {
                self::add_watermark_to_images($post_id, $generation);
                if( $generation == 'restore' ) {
                    unset($post_ready[$array_i]);
                    update_option('br_watermarked_to_restore', $post_ready);
                    update_option('br_watermarked', array());
                }
            }
        }
        $options = self::get_option();
        if( @ ! $options['disable_image'] ) {
            add_filter( 'image_downsize', array( __CLASS__, 'replace_image' ), 200, 3 );
        }
    }
    public static function restore_images() {
        if( current_user_can( 'manage_options' ) ) {
            self::watermark_call('restore');
        }
        wp_die();
    }
    /**
     * Function add options button to admin panel
     *
     * @access public
     *
     * @return void
     */
    public static function options() {
        add_submenu_page( 'woocommerce', __('Products Image Watermark settings', 'BeRocket_image_watermark_domain'), __('Products Image Watermark', 'BeRocket_image_watermark_domain'), 'manage_options', 'br-image_watermark', array(
            __CLASS__,
            'option_form'
        ) );
    }
    /**
     * Function add options form to settings page
     *
     * @access public
     *
     * @return void
     */
    public static function option_form() {
        $plugin_info = get_plugin_data(__FILE__, false, true);
        include image_watermark_TEMPLATE_PATH . "settings.php";
    }
    /**
     * Function set default settings to database
     *
     * @return void
     */
    public static function activation () {
        self::watermark_call();
    }
    /**
     * Function remove settings from database
     *
     * @return void
     */
    public static function deactivation () {
        self::watermark_call('restore');
    }
    public static function uninstall () {
        delete_option( self::$values['settings_name'] );
        self::watermark_call('restore');
    }
    public static function save_settings () {
        if( current_user_can( 'manage_options' ) ) {
            if( isset($_POST[self::$values['settings_name']]) ) {
                update_option( self::$values['settings_name'], self::sanitize_option($_POST[self::$values['settings_name']]) );
                echo json_encode($_POST[self::$values['settings_name']]);
            }
            update_option('br_watermarked', array());
        }
        wp_die();
    }

    public static function current_screen() {
        $screen = get_current_screen();
        if(strpos($screen->id, 'br-image_watermark') !== FALSE) {
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-resizable' );
            wp_enqueue_script( 'berocket_watermark-colorpicker', plugins_url( 'js/colpick.js', __FILE__ ), array( 'jquery' ) );
            wp_register_style( 'berocket_watermark-colorpicker-css', plugins_url( 'css/colpick.css', __FILE__ ), "", BeRocket_image_watermark_version );
            wp_enqueue_style('berocket_watermark-colorpicker-css');
            wp_register_style( 'jquery-ui-smoothness', plugins_url( 'css/jquery-ui.min.css', __FILE__ ), "", BeRocket_image_watermark_version );
            wp_enqueue_style('jquery-ui-smoothness');
        }
    }

    public static function sanitize_option( $input ) {
        $default = self::$defaults;
        $result = self::recursive_array_set( $default, $input );
        return $result;
    }
    public static function recursive_array_set( $default, $options ) {
        $result = array();
        foreach( $default as $key => $value ) {
            if( array_key_exists( $key, $options ) ) {
                if( is_array( $value ) ) {
                    if( is_array( $options[$key] ) ) {
                        $result[$key] = self::recursive_array_set( $value, $options[$key] );
                    } else {
                        $result[$key] = self::recursive_array_set( $value, array() );
                    }
                } else {
                    $result[$key] = $options[$key];
                }
            } else {
                if( is_array( $value ) ) {
                    $result[$key] = self::recursive_array_set( $value, array() );
                } else {
                    $result[$key] = '';
                }
            }
        }
        foreach( $options as $key => $value ) {
            if( ! array_key_exists( $key, $result ) ) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    public static function get_option() {
        $options = get_option( self::$values['settings_name'] );
        if ( @ $options && is_array ( $options ) ) {
            $options = array_merge( self::$defaults, $options );
        } else {
            $options = self::$defaults;
        }
        return $options;
    }
}

new BeRocket_image_watermark;

berocket_admin_notices::generate_subscribe_notice();
new berocket_admin_notices(array(
    'start' => 1498413376, // timestamp when notice start
    'end'   => 1504223940, // timestamp when notice end
    'name'  => 'name', //notice name must be unique for this time period
    'html'  => 'Only <strong>$10</strong> for <strong>Premium</strong> WooCommerce Load More Products plugin!
        <a class="berocket_button" href="http://berocket.com/product/woocommerce-load-more-products" target="_blank">Buy Now</a>
         &nbsp; <span>Get your <strong class="red">50% discount</strong> and save <strong>$10</strong> today</span>
        ', //text or html code as content of notice
    'righthtml'  => '<a class="berocket_no_thanks">No thanks</a>', //content in the right block, this is default value. This html code must be added to all notices
    'rightwidth'  => 80, //width of right content is static and will be as this value. berocket_no_thanks block is 60px and 20px is additional
    'nothankswidth'  => 60, //berocket_no_thanks width. set to 0 if block doesn't uses. Or set to any other value if uses other text inside berocket_no_thanks
    'contentwidth'  => 400, //width that uses for mediaquery is image_width + contentwidth + rightwidth
    'subscribe'  => false, //add subscribe form to the righthtml
    'priority'  => 10, //priority of notice. 1-5 is main priority and displays on settings page always
    'height'  => 50, //height of notice. image will be scaled
    'repeat'  => false, //repeat notice after some time. time can use any values that accept function strtotime
    'repeatcount'  => 1, //repeat count. how many times notice will be displayed after close
    'image'  => array(
        'local' => plugin_dir_url( __FILE__ ) . 'images/ad_white_on_orange.png', //notice will be used this image directly
    ),
));
