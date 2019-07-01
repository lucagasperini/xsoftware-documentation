<?php
        if(!defined("ABSPATH")) die;

        /* If is present the download on URL query */
        if(isset($_GET['download'])) {
                /* Get the post id */
                $id = get_the_ID();
                /* Fetch by post query the post_content */
                $content = get_post_field('post_content', $id);
                /* Fetch by post query the post_content */
                $title = get_post_field('post_title', $id);

                /* Print the header information to download the file */
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.$title.'.txt"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . strlen($content));
                /* Print the RST content to download */
                echo $content;
                /* Exit from the script */
                exit;
        }

        /* Load the documention template style */
        wp_enqueue_style('xs_documentation_style', plugins_url('template.min.css', __FILE__));

        get_header();

        /* Print primary and main elements */
        echo '<div id="primary" class="archive_content_area">
        <main id="main" class="post-wrap" role="main">';

        /* Post loop */
        /* TODO:It's needed? */
        while ( have_posts() ) {
                the_post();
                /* Get the post id */
                $id = get_the_ID();

                /* Print the title of the documentation */
                echo '<h1>'.get_the_title($id).'</h1>';

                /* Print the parsed HTML of the documentation*/
                echo get_post_meta($id, 'xs_documentation_html', true);

                /* If comments are open or it has at least one comment, load the comment template */
                if ( comments_open() || get_comments_number() )
                        comments_template();
        }

        /* Close primary and main elements */
        echo '</main></div>';

        get_footer();
?>

