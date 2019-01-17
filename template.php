<?php
        function docs_single($single)
        {
                echo $single['text'];
                echo $single['create_by'];
                echo $single['create_date'];
                echo $single['modify_date'];
        }
        
        function docs_main($array)
        {
                echo '<div class="product_list">';
                foreach($array as $single)
                        echo '<div class="product_list_item"><a href="?id='.$single['id'].'"><span>'.$single['title'].'</span></a></div>';
                echo '</div>';
        }
?>
