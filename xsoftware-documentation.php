<?php
/*
Plugin Name: XSoftware Documentation
Description: Documentation management on WordPress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.it/
*/

if(!defined('ABSPATH')) die;

if (!class_exists('xs_documentation_plugin')) :

include 'xsoftware-documentation-options.php';
include 'RST/autoload.php';

class xs_documentation_plugin
{

        private $options = NULL;

        public function __construct()
        {
                add_action('init', [$this, 'create_post_type']);
                add_action('save_post', [$this,'save'], 10, 2);
                add_filter('single_template', [$this,'single']);
                add_filter('archive_template', [$this,'archive']);
                add_action('add_meta_boxes', [$this, 'metaboxes']);
                add_filter('wp_default_editor', [$this,'html_editor']);
                add_filter('admin_footer', [$this,'remove_editor'], 99);

                $this->options = get_option('xs_options_docs');
        }

        function create_post_type()
        {
                register_post_type(
                        'xs_doc',
                        [
                                'labels' => [
                                        'name' => __( 'Documentations' ),
                                        'singular_name' => __( 'Documentation' )
                                ],
                                'public' => true,
                                'has_archive' => true,
                                'rewrite' => ['slug' => 'docs'],
                                'hierarchical' => true
                        ]
                );
                add_post_type_support('xs_doc', ['title','editor','comments','revisions'] );
        }

        function metaboxes()
        {
                add_meta_box(
                        'xs_documentation_metaboxes',
                        'XSoftware Documentation',
                        [$this,'metaboxes_print'],
                        ['xs_doc'],
                        'advanced',
                        'high'
                );
        }

        function metaboxes_print()
        {
                global $post;
                $values = get_post_custom( $post->ID );
                $selected = isset( $values['xs_documentation_category'][0] ) ?
                        intval($values['xs_documentation_category'][0]) :
                        '';

                foreach($this->options['categories'] as $id => $prop)
                        $categories[$id] = $prop['name'];

                $data = array();

                $data['category'][0] = 'Category:';
                $data['category'][1] = xs_framework::create_select([
                        'class' => 'xs_full_width',
                        'name' => 'xs_documentation_category',
                        'data'=> $categories,
                        'selected' => $selected
                ]);


                xs_framework::create_table([
                        'class' => 'xs_full_width',
                        'data' => $data
                ]);
        }

        function save($post_id, $post)
        {
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_doc' ) return;

                if(isset($_POST['xs_documentation_category']))
                        update_post_meta(
                                $post_id,
                                'xs_documentation_category',
                                $_POST['xs_documentation_category']
                        );

                $parser = new Gregwar\RST\Parser;

                $document = $parser->parse($post->post_content);

                update_post_meta( $post_id, 'xs_documentation_html', $document );
        }
        /*
        * Callback called to show post RST source instead of rendered content
        * inside the editor.
        */
        function html_editor($content)
        {
                global $post;
                $post_type = get_post_type($post->ID);

                if ( $post_type != 'xs_doc' )
                        return $content;

                return 'html';
        }

        function remove_editor()
        {
                global $post;
                if(!isset($post) || empty($post)) return;

                $post_type = get_post_type($post->ID);

                if ( $post_type != 'xs_doc' ) return;

                echo '  <style type="text/css">
                                #content-tmce, #content-tmce:hover, #qt_content_fullscreen{
                                display:none;
                        }
                        </style>';

                echo '  <script type="text/javascript">
                        jQuery(document).ready(function(){
                                jQuery("#content-tmce").attr("onclick", null);
                        });
                        </script>';
        }


        function single($single)
        {
                global $post;

                if(empty($post)) return $single;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_doc' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/template/single.php' ) ) {
                        return  dirname( __FILE__ ) . '/template/single.php';
                        }
                }

                return $single;
        }

        function archive($single)
        {
                global $post;

                if(empty($post)) return $single;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_doc' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/template/archive.php' ) ) {
                        return  dirname( __FILE__ ) . '/template/archive.php';
                        }
                }

                return $single;
        }
}

$xs_documentation_plugin = new xs_documentation_plugin;

endif;

?>
