<?php

/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */
defined( 'ABSPATH' ) || exit;
/**
 * Library for WooCommerce Settings
 *
 * Settings in order to sync products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class SYNC_Admin
{
    /**
     * Settings
     *
     * @var array
     */
    private  $sync_settings ;
    /**
     * Label for premium features
     *
     * @var string
     */
    private  $label_premium ;
    /**
     * Construct of class
     */
    public function __construct()
    {
        $this->label_premium = __( '(ONLY PREMIUM VERSION)', 'sync-ecommerce-neo' );
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_head', array( $this, 'custom_css' ) );
    }
    
    /**
     * Adds plugin page.
     *
     * @return void
     */
    public function add_plugin_page()
    {
        add_menu_page(
            __( 'Import from NEO to eCommerce', 'sync-ecommerce-neo' ),
            __( 'Import NEO', 'sync-ecommerce-neo' ),
            'manage_options',
            'import_' . PLUGIN_SLUG,
            array( $this, 'create_admin_page' ),
            'dashicons-index-card',
            99
        );
    }
    
    /**
     * Create admin page.
     *
     * @return void
     */
    public function create_admin_page()
    {
        $this->sync_settings = get_option( PLUGIN_OPTIONS );
        $this->test_connection();
        ?>

		<div class="wrap">
			<h2><?php 
        esc_html_e( 'NEO Product Importing Settings', 'sync-ecommerce-neo' );
        ?></h2>
			<p></p>
			<?php 
        settings_errors();
        ?>

			<?php 
        $active_tab = ( isset( $_GET['tab'] ) ? strval( $_GET['tab'] ) : 'sync' );
        ?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=import_sync-ecommerce-neo&tab=sync" class="nav-tab <?php 
        echo  ( 'sync' === $active_tab ? 'nav-tab-active' : '' ) ;
        ?>"><?php 
        esc_html_e( 'Manual Synchronization', 'sync-ecommerce-neo' );
        ?></a>
				<a href="?page=import_sync-ecommerce-neo&tab=orders" class="nav-tab <?php 
        echo  ( 'orders' === $active_tab ? 'nav-tab-active' : '' ) ;
        ?>"><?php 
        esc_html_e( 'Sync Orders', 'sync-ecommerce-neo' );
        ?></a>
				<a href="?page=import_sync-ecommerce-neo&tab=automate" class="nav-tab <?php 
        echo  ( 'automate' === $active_tab ? 'nav-tab-active' : '' ) ;
        ?>"><?php 
        esc_html_e( 'Automate', 'sync-ecommerce-neo' );
        ?></a>
				<a href="?page=import_sync-ecommerce-neo&tab=settings" class="nav-tab <?php 
        echo  ( 'settings' === $active_tab ? 'nav-tab-active' : '' ) ;
        ?>"><?php 
        esc_html_e( 'Settings', 'sync-ecommerce-neo' );
        ?></a>
			</h2>

			<?php 
        if ( 'sync' === $active_tab ) {
            ?>
				<div id="sync-neo-engine"></div>
			<?php 
        }
        ?>
			<?php 
        
        if ( 'settings' === $active_tab ) {
            ?>
				<form method="post" action="options.php">
					<?php 
            settings_fields( 'import_neo_settings' );
            do_settings_sections( 'import-neo-admin' );
            submit_button( __( 'Save settings', 'sync-ecommerce-neo' ), 'primary', 'submit_settings' );
            ?>
				</form>
			<?php 
        }
        
        ?>
			<?php 
        
        if ( 'automate' === $active_tab ) {
            ?>
				<form method="post" action="options.php">
					<?php 
            settings_fields( 'import_neo_settings' );
            do_settings_sections( 'import-neo-automate' );
            submit_button( __( 'Save automate', 'sync-ecommerce-neo' ), 'primary', 'submit_automate' );
            ?>
				</form>
			<?php 
        }
        
        ?>
			<?php 
        if ( 'orders' === $active_tab ) {
            $this->page_sync_orders();
        }
        ?>
		</div>
		<?php 
    }
    
    /**
     * Test connection
     *
     * @return void
     */
    public function test_connection()
    {
        $token = sync_get_token( true );
    }
    
    /**
     * Init for page
     *
     * @return void
     */
    public function page_init()
    {
        register_setting( 'import_neo_settings', PLUGIN_OPTIONS, array( $this, 'sanitize_fields' ) );
        $settings_title = __( 'Settings for Importing in WooCommerce', 'sync-ecommerce-neo' );
        add_settings_section(
            'import_neo_setting_section',
            $settings_title,
            array( $this, 'import_neo_section_info' ),
            'import-neo-admin'
        );
        add_settings_field(
            PLUGIN_PREFIX . 'idcentre',
            __( 'NEO ID Centre', 'sync-ecommerce-neo' ),
            array( $this, 'idcentre_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        add_settings_field(
            'wcsen_api',
            __( 'NEO API Key', 'sync-ecommerce-neo' ),
            array( $this, 'api_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        add_settings_field(
            'wcsen_stock',
            __( 'Import stock?', 'sync-ecommerce-neo' ),
            array( $this, 'wcsen_stock_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        add_settings_field(
            'wcsen_prodst',
            __( 'Default status for new products?', 'sync-ecommerce-neo' ),
            array( $this, 'wcsen_prodst_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        add_settings_field(
            'wcsen_virtual',
            __( 'Virtual products?', 'sync-ecommerce-neo' ),
            array( $this, 'wcsen_virtual_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        add_settings_field(
            'wcsen_backorders',
            __( 'Allow backorders?', 'sync-ecommerce-neo' ),
            array( $this, 'wcsen_backorders_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        add_settings_field(
            'wcsen_tax',
            __( 'Get prices with Tax?', 'sync-ecommerce-neo' ),
            array( $this, 'wcsen_tax_callback' ),
            'import-neo-admin',
            'import_neo_setting_section'
        );
        $name_nif = __( 'Meta key for Billing NIF?', 'sync-ecommerce-neo' );
        $name_catnp = __( 'Import category only in new products?', 'sync-ecommerce-neo' );
        /**
         * # Automate
         * ---------------------------------------------------------------------------------------------------- */
        add_settings_section(
            'import_neo_setting_automate',
            __( 'Automate', 'sync-ecommerce-neo' ),
            array( $this, 'import_neo_section_automate' ),
            'import-neo-automate'
        );
        $name_sync = __( 'When do you want to sync articles?', 'sync-ecommerce-neo' );
        $name_sync_stk = __( 'When do you want to sync stock?', 'sync-ecommerce-neo' );
    }
    
    public function page_sync_orders()
    {
        echo  '<h2>' . __( 'Synchronize Orders', 'sync-ecommerce-neo' ) . '</h2>' ;
        echo  '<p>' . __( 'Synchronize previous orders in "Completed" status with your NEO ERP.', 'sync-ecommerce-neo' ) . '</p>' ;
        echo  '<button class="button-secondary" type="button" name="wcsen_customize_button" id="wcsen_customize_button" style="" onclick="syncNEOOrders();">Sync</button>' ;
    }
    
    /**
     * Sanitize fiels before saves in DB
     *
     * @param array $input Input fields.
     * @return array
     */
    public function sanitize_fields( $input )
    {
        $sanitary_values = array();
        $sync_settings = get_option( PLUGIN_OPTIONS );
        
        if ( isset( $_POST['submit_settings'] ) ) {
            if ( isset( $input[PLUGIN_PREFIX . 'idcentre'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'idcentre'] = sanitize_text_field( $input[PLUGIN_PREFIX . 'idcentre'] );
            }
            if ( isset( $input[PLUGIN_PREFIX . 'api'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'api'] = sanitize_text_field( $input[PLUGIN_PREFIX . 'api'] );
            }
            if ( isset( $input[PLUGIN_PREFIX . 'stock'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'stock'] = $input[PLUGIN_PREFIX . 'stock'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'prodst'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'prodst'] = $input[PLUGIN_PREFIX . 'prodst'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'virtual'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'virtual'] = $input[PLUGIN_PREFIX . 'virtual'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'backorders'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'backorders'] = $input[PLUGIN_PREFIX . 'backorders'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'tax'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'tax'] = $input[PLUGIN_PREFIX . 'tax'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'filter'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'filter'] = sanitize_text_field( $input[PLUGIN_PREFIX . 'filter'] );
            }
            if ( isset( $input[PLUGIN_PREFIX . 'rates'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'rates'] = $input[PLUGIN_PREFIX . 'rates'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'catnp'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'catnp'] = $input[PLUGIN_PREFIX . 'catnp'];
            }
            if ( isset( $input[PLUGIN_PREFIX . 'billing_key'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'billing_key'] = $input[PLUGIN_PREFIX . 'billing_key'];
            }
            // Other tab.
            $sanitary_values[PLUGIN_PREFIX . 'sync'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'sync'] ) ? $sync_settings[PLUGIN_PREFIX . 'sync'] : 'no' );
            $sanitary_values[PLUGIN_PREFIX . 'sync_stk'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'sync_stk'] ) ? $sync_settings[PLUGIN_PREFIX . 'sync_stk'] : 'no' );
            $sanitary_values[PLUGIN_PREFIX . 'sync_email'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'sync_email'] ) ? $sync_settings[PLUGIN_PREFIX . 'sync_email'] : 'yes' );
            $sanitary_values[PLUGIN_PREFIX . 'billing_key'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'billing_key'] ) ? $sync_settings[PLUGIN_PREFIX . 'billing_key'] : '_billing_vat' );
        } elseif ( isset( $_POST['submit_automate'] ) ) {
            
            if ( isset( $input[PLUGIN_PREFIX . 'sync'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'sync'] = $input[PLUGIN_PREFIX . 'sync'];
                $sanitary_values[PLUGIN_PREFIX . 'sync_stk'] = $input[PLUGIN_PREFIX . 'sync_stk'];
            }
            
            if ( isset( $input[PLUGIN_PREFIX . 'sync_email'] ) ) {
                $sanitary_values[PLUGIN_PREFIX . 'sync_email'] = $input[PLUGIN_PREFIX . 'sync_email'];
            }
            // Other tab.
            $sanitary_values[PLUGIN_PREFIX . 'idcentre'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'idcentre'] ) ? $sync_settings[PLUGIN_PREFIX . 'idcentre'] : '' );
            $sanitary_values[PLUGIN_PREFIX . 'api'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'api'] ) ? $sync_settings[PLUGIN_PREFIX . 'api'] : '' );
            $sanitary_values[PLUGIN_PREFIX . 'stock'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'stock'] ) ? $sync_settings[PLUGIN_PREFIX . 'stock'] : 'no' );
            $sanitary_values[PLUGIN_PREFIX . 'prodst'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'prodst'] ) ? $sync_settings[PLUGIN_PREFIX . 'prodst'] : 'draft' );
            $sanitary_values[PLUGIN_PREFIX . 'virtual'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'virtual'] ) ? $sync_settings[PLUGIN_PREFIX . 'virtual'] : 'no' );
            $sanitary_values[PLUGIN_PREFIX . 'backorders'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'backorders'] ) ? $sync_settings[PLUGIN_PREFIX . 'backorders'] : 'no' );
            $sanitary_values[PLUGIN_PREFIX . 'tax'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'tax'] ) ? $sync_settings[PLUGIN_PREFIX . 'tax'] : 'no' );
            $sanitary_values[PLUGIN_PREFIX . 'filter'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'filter'] ) ? $sync_settings[PLUGIN_PREFIX . 'filter'] : '' );
            $sanitary_values[PLUGIN_PREFIX . 'rates'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'rates'] ) ? $sync_settings[PLUGIN_PREFIX . 'rates'] : 'default' );
            $sanitary_values[PLUGIN_PREFIX . 'catnp'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'catnp'] ) ? $sync_settings[PLUGIN_PREFIX . 'catnp'] : 'yes' );
            $sanitary_values[PLUGIN_PREFIX . 'billing_key'] = ( isset( $sync_settings[PLUGIN_PREFIX . 'billing_key'] ) ? $sync_settings[PLUGIN_PREFIX . 'billing_key'] : '_billing_vat' );
        }
        
        return $sanitary_values;
    }
    
    private function show_get_premium()
    {
        // Purchase notification.
        $purchase_url = 'https://checkout.freemius.com/mode/dialog/plugin/5133/plan/8469/';
        $get_pro = sprintf( wp_kses( __( ' <a href="%s">Get Pro version</a> to enable', 'sync-ecommerce-neo' ), array(
            'a' => array(
            'href'   => array(),
            'target' => array(),
        ),
        ) ), esc_url( $purchase_url ) );
        return $get_pro;
    }
    
    /**
     * Info for neo section.
     *
     * @return void
     */
    public function import_neo_section_automate()
    {
        esc_html_e( 'Section only for Premium version', 'sync-ecommerce-neo' );
        echo  $this->show_get_premium() ;
    }
    
    /**
     * Info for neo section.
     *
     * @return void
     */
    public function import_neo_section_orders()
    {
        esc_html_e( 'Section only for Premium version', 'sync-ecommerce-neo' );
        echo  $this->show_get_premium() ;
    }
    
    /**
     * Info for neo automate section.
     *
     * @return void
     */
    public function import_neo_section_info()
    {
        echo  sprintf( __( 'Put the connection API key settings in order to connect and sync products. You can go here <a href = "%s" target = "_blank">App NEO API</a>. ', 'sync-ecommerce-neo' ), 'https://app.neo.com/api' ) ;
        echo  $this->show_get_premium() ;
    }
    
    public function idcentre_callback()
    {
        printf( '<input class="regular-text" type="password" name="' . PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'idcentre]" id="' . PLUGIN_PREFIX . 'idcentre" value="%s">', ( isset( $this->sync_settings[PLUGIN_PREFIX . 'idcentre'] ) ? esc_attr( $this->sync_settings[PLUGIN_PREFIX . 'idcentre'] ) : '' ) );
    }
    
    public function api_callback()
    {
        printf( '<input class="regular-text" type="password" name="' . PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'api]" id="' . PLUGIN_PREFIX . 'api" value="%s">', ( isset( $this->sync_settings[PLUGIN_PREFIX . 'api'] ) ? esc_attr( $this->sync_settings[PLUGIN_PREFIX . 'api'] ) : '' ) );
    }
    
    public function wcsen_stock_callback()
    {
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX ;
        ?>stock]" id="wcsen_stock">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'stock'] ) && $this->sync_settings[PLUGIN_PREFIX . 'stock'] === 'yes' ? 'selected' : '' );
        ?>
			<option value="yes" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Yes', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'stock'] ) && $this->sync_settings[PLUGIN_PREFIX . 'stock'] === 'no' ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    public function wcsen_prodst_callback()
    {
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX ;
        ?>prodst]" id="wcsen_prodst">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ) && 'draft' === $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ? 'selected' : '' );
        ?>
			<option value="draft" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Draft', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ) && 'publish' === $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ? 'selected' : '' );
        ?>
			<option value="publish" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Publish', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ) && 'pending' === $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ? 'selected' : '' );
        ?>
			<option value="pending" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Pending', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ) && 'private' === $this->sync_settings[PLUGIN_PREFIX . 'prodst'] ? 'selected' : '' );
        ?>
			<option value="private" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Private', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    public function wcsen_virtual_callback()
    {
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX ;
        ?>virtual]" id="wcsen_virtual">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'virtual'] ) && $this->sync_settings[PLUGIN_PREFIX . 'virtual'] === 'no' ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'virtual'] ) && $this->sync_settings[PLUGIN_PREFIX . 'virtual'] === 'yes' ? 'selected' : '' );
        ?>
			<option value="yes" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Yes', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    public function wcsen_backorders_callback()
    {
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX ;
        ?>backorders]" id="wcsen_backorders">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'backorders'] ) && $this->sync_settings[PLUGIN_PREFIX . 'backorders'] === 'no' ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'backorders'] ) && $this->sync_settings[PLUGIN_PREFIX . 'backorders'] === 'yes' ? 'selected' : '' );
        ?>
			<option value="yes" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Yes', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'backorders'] ) && $this->sync_settings[PLUGIN_PREFIX . 'backorders'] === 'notify' ? 'selected' : '' );
        ?>
			<option value="notify" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Notify', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    public function wcsen_tax_callback()
    {
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX ;
        ?>tax]" id="wcsen_tax">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'tax'] ) && $this->sync_settings[PLUGIN_PREFIX . 'tax'] === 'yes' ? 'selected' : '' );
        ?>
			<option value="yes" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Yes, tax included', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'tax'] ) && $this->sync_settings[PLUGIN_PREFIX . 'tax'] === 'notify' ? 'selected' : '' );
        ?>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'tax'] ) && $this->sync_settings[PLUGIN_PREFIX . 'tax'] === 'no' ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No, tax not included', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    public function wcsen_properties_callback()
    {
        $properties_options = sync_get_properties_order();
        if ( false == $properties_options ) {
            return false;
        }
        ?>
		<select name="' . PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'rates]" id="wcsen_rates">
			<?php 
        foreach ( $properties_options as $value => $label ) {
            $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'rates'] ) && $this->sync_settings[PLUGIN_PREFIX . 'rates'] === $value ? 'selected' : '' );
            echo  '<option value="' . esc_html( $value ) . '" ' . esc_html( $selected ) . '>' . esc_html( $label ) . '</option>' ;
        }
        ?>
		</select>
		<?php 
    }
    
    public function wcsen_catnp_callback()
    {
        ?>
		<select name="' . PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'catnp]" id="wcsen_catnp">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'catnp'] ) && $this->sync_settings[PLUGIN_PREFIX . 'catnp'] === 'yes' ? 'selected' : '' );
        ?>
			<option value="yes" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Yes', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'catnp'] ) && $this->sync_settings[PLUGIN_PREFIX . 'catnp'] === 'no' ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    /**
     * Callback sync field.
     *
     * @return void
     */
    public function wcsen_sync_callback()
    {
        global  $cron_options ;
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'sync]' ;
        ?>" id="<?php 
        echo  PLUGIN_PREFIX ;
        ?>sync">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'sync'] ) && 'no' === $this->sync_settings[PLUGIN_PREFIX . 'sync'] ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        foreach ( $cron_options as $cron_option ) {
            $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'sync'] ) && $cron_option['cron'] === $this->sync_settings[PLUGIN_PREFIX . 'sync'] ? 'selected' : '' );
            echo  '<option value="' . esc_html( $cron_option['cron'] ) . '" ' . esc_html( $selected ) . '>' ;
            echo  esc_html( $cron_option['display'] ) . '</option>' ;
        }
        ?>
		</select>
		<?php 
    }
    
    /**
     * Callback sync field.
     *
     * @return void
     */
    public function wcsen_sync_stk_callback()
    {
        global  $cron_options ;
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'sync_stk]' ;
        ?>" id="<?php 
        echo  PLUGIN_PREFIX ;
        ?>sync">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'sync_stk'] ) && 'no' === $this->sync_settings[PLUGIN_PREFIX . 'sync'] ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        foreach ( $cron_options as $cron_option ) {
            $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'sync_stk'] ) && $cron_option['cron'] === $this->sync_settings[PLUGIN_PREFIX . 'sync_stk'] ? 'selected' : '' );
            echo  '<option value="' . esc_html( $cron_option['cron'] ) . '" ' . esc_html( $selected ) . '>' ;
            echo  esc_html( $cron_option['display'] ) . '</option>' ;
        }
        ?>
		</select>
		<?php 
    }
    
    public function wcsen_sync_email_callback()
    {
        ?>
		<select name="<?php 
        echo  PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'sync_email]' ;
        ?>" id="wcsen_sync_email">
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'sync_email'] ) && $this->sync_settings[PLUGIN_PREFIX . 'sync_email'] === 'yes' ? 'selected' : '' );
        ?>
			<option value="yes" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'Yes', 'sync-ecommerce-neo' );
        ?></option>
			<?php 
        $selected = ( isset( $this->sync_settings[PLUGIN_PREFIX . 'sync_email'] ) && $this->sync_settings[PLUGIN_PREFIX . 'sync_email'] === 'no' ? 'selected' : '' );
        ?>
			<option value="no" <?php 
        echo  esc_html( $selected ) ;
        ?>><?php 
        esc_html_e( 'No', 'sync-ecommerce-neo' );
        ?></option>
		</select>
		<?php 
    }
    
    public function wcsen_billing_nif_callback()
    {
        printf( '<input class="regular-text" type="text" name="' . PLUGIN_OPTIONS . '[' . PLUGIN_PREFIX . 'billing_key]" id="' . PLUGIN_PREFIX . 'billing_key" value="%s">', ( isset( $this->sync_settings[PLUGIN_PREFIX . 'billing_key'] ) ? esc_attr( $this->sync_settings[PLUGIN_PREFIX . 'billing_key'] ) : '' ) );
    }
    
    /**
     * Custom CSS for admin
     *
     * @return void
     */
    public function custom_css()
    {
        // Free Version.
        echo  '
			<style>
			.wp-admin .sync-ecommerce-neo-plugin span.wcsen-premium{ 
				color: #b4b9be;
			}
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'catnp,
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'stock {
				width: 70px;
			}
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'idcentre,
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'sync_num {
				width: 50px;
			}
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'prodst {
				width: 150px;
			}
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'api,
			.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'taxinc {
				width: 270px;
			}' ;
        // Not premium version.
        if ( cmk_fs()->is_not_paying() ) {
            echo  '.wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'filter, .wp-admin.sync-ecommerce-neo-plugin #' . PLUGIN_PREFIX . 'sync  {
				pointer-events:none;
			}' ;
        }
        echo  '</style>' ;
    }

}
if ( is_admin() ) {
    $import_sync = new SYNC_Admin();
}