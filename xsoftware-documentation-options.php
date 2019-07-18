<?php

if(!defined('ABSPATH')) die;

if (!class_exists('xs_documentation_options')) :

/*
*  XSoftware Documentation Options Class
*  The following class is used to set the plugin options
*  Below is a description of the fields used
*  $ categories : matrix
*  It is a matrix containing all the categories that use the documentation system
*  default : matrix
*/
class xs_documentation_options
{

        private $default = [
                'categories' => [
                        [
                                'name' => 'Common',
                                'img' => '',
                                'descr' => ''
                        ]
                ]
        ];

        private $options = NULL;

        /*
        *  __construct : void
        *  The class constructor does not require any parameters and
        *  initializes the options and hooks for the administration panel
        */
        public function __construct()
        {
                add_action('admin_menu', [$this, 'admin_menu']);
                add_action('admin_init', [$this, 'section_menu']);

                $this->options = get_option('xs_options_docs', $this->default);
        }

        /*
        *  void : admin_menu : void
        *  This method is used to create the entry in the XSoftware submenu
        */
        function admin_menu()
        {
                add_submenu_page(
                        'xsoftware',
                        'XSoftware Documentation',
                        'Documentation',
                        'manage_options',
                        'xsoftware_documentation',
                        [$this, 'menu_page']
                );
        }

        /*
        *  void : menu_page : void
        *  This method is used to create the page template in the administration panel
        */
        function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }

                echo '<div class="wrap">';
                echo '<h2>Documentation configuration</h2>';

                echo '<form action="options.php" method="post">';

                settings_fields('doc_setting');
                do_settings_sections('doc');

                submit_button( '', 'primary', 'submit', true, NULL );
                echo '</form>';

                echo '</div>';
        }

        /*
        *  void : section_menu : void
        *  This method is used to create references to the two most important
        *  methods of the options which are 'input' and 'show'
        */
        function section_menu()
        {
                register_setting( 'doc_setting', 'xs_options_docs', array($this, 'input') );
                add_settings_section( 'doc_section', 'Settings', array($this, 'show'), 'doc' );
        }

        /*
        *  array : input : array
        *  This method is used to validate and control the values
        *  passed from the administration page
        *  $input are the values of the administration panel
        */
        function input($input)
        {

                $current = $this->options;

                /* Reload categories from the obj_list */
                if(isset($input['obj_list']) && !empty($input['obj_list']))
                        $current['categories'] = $input['obj_list'];

                /* Add new categories with button */
                if(isset($input['add']))
                        $current['categories'][] = ['name' => 'New Category', 'descr' => 'This is a description.', 'img' => ''];

                /* Remove the categories with button */
                if(isset($input['remove']) && !empty($input['remove']))
                        unset($current['categories'][$input['remove']]);

                return $current;
        }

        /*
        *  void : show : void
        *  This method is used to show and manage the various sections of the options
        */
        function show()
        {
                wp_enqueue_media();

                /*
                *  Create tabs for the various sections and put the current one in $tab
                */
                $tab = xs_framework::create_tabs([
                        'href' => '?page=xsoftware_documentation',
                        'tabs' => [
                                'categories' => 'Categories'
                        ],
                        'home' => 'categories',
                        'name' => 'main_tab'
                ]);

                /*
                *  Switch for the current tab value and call the right method
                */
                switch($tab) {
                        case 'categories':
                                $this->show_categories();
                                return;
                }

        }

        /*
        *  void : show_categories : void
        *  This method is used to show categories options
        */
        function show_categories()
        {
                /*
                *  Display products using the html-utils of xsoftware {obj_list_edit}
                */
                xs_framework::obj_list_edit([
                        'id' => 'cat',
                        'name' => 'xs_options_docs',
                        'data' => $this->options['categories']
                ]);
        }
}

$xs_documentation_options = new xs_documentation_options;

endif;

?>
