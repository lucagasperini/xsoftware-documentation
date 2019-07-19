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

        get_header();

        $layout = get_theme_mod('page_layout');

        /* Print primary and main elements */
        echo '<div class="wrap">
        <div id="primary" class="content-area '.$layout.'">
        <main id="main" class="post-wrap" role="main">';

        /* Post loop */
        /* TODO:It's needed? */
        while ( have_posts() ) {
                the_post();

                echo apply_filters('xs_documentation_single_show', get_the_ID());

                /* If comments are open or it has at least one comment, load the comment template */
                if ( comments_open() || get_comments_number() )
                        comments_template();
        }

        /* Close primary and main elements */
        echo '</main></div></div>';

        get_footer();
?>

