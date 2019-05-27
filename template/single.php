<?php
        if(!defined("ABSPATH")) die;
        
        if(isset($_GET['download'])) {
                $id = get_the_ID();
                $post = get_post($id);
               
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.$post->post_title.'.txt"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . strlen($post->post_content));
                echo $post->post_content;
                exit;
        }
        
        wp_enqueue_style('xs_documentation_style', plugins_url('template.css', __FILE__));
        
        get_header(); 
        $layout = get_theme_mod('page_layout');
        
        echo '<div id="primary" class="single_content_area '.$layout.'">';

        echo '<main id="main" class="post-wrap" role="main">';
        while ( have_posts() ) {
                the_post();
                $id = get_the_ID();
                
                echo '<h1>'.get_the_title($id).'</h1>';
                
                echo get_post_meta($id, 'xs_documentation_html', true);
                
                // If comments are open or we have at least one comment, load up the comment template
                if ( comments_open() || get_comments_number() )
                        comments_template();
        }

        echo '</main></div>';

        if ( $layout !== 'fullscreen' )
                get_sidebar();
                
        get_footer(); 
?>

