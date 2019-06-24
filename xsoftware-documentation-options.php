<?php

if(!defined('ABSPATH')) die;

if (!class_exists('xs_documentation_options')) :


class xs_documentation_options
{

        private $default = array(
                                'categories' => array(
                                        array(
                                                'name' => 'Common',
                                                'img' => '',
                                                'descr' => ''
                                        )
                                )
                                );

        private $options = NULL;

        public function __construct()
        {
                add_action('admin_menu', [$this, 'admin_menu']);
                add_action('admin_init', [$this, 'section_menu']);

                $this->options = get_option('xs_options_docs', $this->default);
        }

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

        function section_menu()
        {
                register_setting( 'doc_setting', 'xs_options_docs', array($this, 'input') );
                add_settings_section( 'doc_section', 'Settings', array($this, 'show'), 'doc' );
        }

        function input($input)
        {

                $current = $this->options;

                if(isset($input['obj_list']) && !empty($input['obj_list']))
                        $current['categories'] = $input['obj_list'];

                if(isset($input['add']))
                        $current['categories'][] = ['name' => 'New Category', 'descr' => 'This is a description.', 'img' => ''];

                if(isset($input['remove']) && !empty($input['remove']))
                        unset($current['categories'][$input['remove']]);

                return $current;
        }

        function show()
        {


                wp_enqueue_media();

                $tab = xs_framework::create_tabs( array(
                        'href' => '?page=xsoftware_documentation',
                        'tabs' => array(
                                'home' => 'Homepage',
                                'categories' => 'Categories'
                        ),
                        'home' => 'home',
                        'name' => 'main_tab'
                ));

                switch($tab) {
                        case 'home':
                                return;
                        case 'categories':
                                $this->show_categories();
                                return;
                }

        }

        function show_categories()
        {
                $options = $this->options['categories'];

                xs_framework::obj_list_edit([
                        'id' => 'cat',
                        'name' => 'xs_options_docs',
                        'data' => $options
                ]);
        }
}

$xs_documentation_options = new xs_documentation_options;

endif;

?>
