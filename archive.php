<?php
        if(!defined("ABSPATH")) die;

        get_header();

        /* Get the options from documention plugin */
        $option = get_option('xs_options_docs');

        $layout = get_theme_mod('page_layout');

        /* Print primary and main elements */
        echo '<div class="wrap">
        <div id="primary" class="content-area '.$layout.'">
        <main id="main" class="post-wrap" role="main">';

        if ( have_posts() ) {
                /* Print the header */
                echo '<header class="page-header">';
                the_archive_title( '<h3 class="archive-title">', '</h3>' );
                the_archive_description( '<div class="taxonomy-description">', '</div>' );
                echo '</header>';

                /* Get all post in $html_list */
                $info['post'] = array();
                while ( have_posts() ) {
                        the_post();
                        /* Get the post id */
                        $id = get_the_ID();
                        /* Get the post category */
                        $category = get_post_meta( $id, 'xs_documentation_category', true );
                        /* Create the matrix by categories with all important values */
                        $info['post'][$category][] = $id;
                }
                $info['categories'] = $option['categories'];

                echo apply_filters('xs_documentation_archive_show', $info);

        } else {
                get_template_part( 'content', 'none' );
        }
        /* Close primary and main elements */
        echo '</main></div></div>';

        get_footer();
?>
