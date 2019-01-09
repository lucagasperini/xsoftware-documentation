<?php

if ( ! function_exists( 'rest2html' ) ) :
function rest2html($source)
{
        //Changing new lines to UNIX if they aren't
        $text = str_replace("\r\n", "\n", $source);
        
        //Adding double newline at end
        $text .= "\n\n";
        
        //Changing tab with a 8 space as standard
        //$text = str_replace("\t", '        ', $text);
        
        //Split all memory in lines
        $lines = explode("\n", $text);
        
        //clear the memory
        unset($text);
        $text = '';
        
        $line_count = count($lines);
        
        $comment = FALSE;
        $code = FALSE;
        
        for($i = $line_count ; $i >= 0; $i--) {
                if(empty($lines[$i])) {
                        $text = '</p><p>' . $text;
                        $code = FALSE;
                }
                else if(strpos($lines[$i], '=') === 0) {
                        if(substr_count($lines[$i], '=') == strlen($lines[$i])) {
                                $text = '<h1>' .$lines[$i - 1] . '</h1>' . $text;
                                $lines[$i - 1] = '';
                        }
                }
                else if(strpos($lines[$i], '-') === 0) {
                        if(substr_count($lines[$i], '-') == strlen($lines[$i])) {
                                $text = '<h3>' .$lines[$i - 1] . '</h3>' . $text;
                                $lines[$i - 1] = '';
                        }
                }
                else if(strpos($lines[$i], '.. ') === 0) {
                        if(strpos($lines[$i], '.. code-block::') === 0) {
                                $code = TRUE;
                        } else {
                                $comment = TRUE;
                        }
                }
                else {
                        if($comment == FALSE && $code == FALSE) {
                                $text = ' ' . $lines[$i] . $text; //preappend string
                        }
                        else if ($comment == FALSE && $code == TRUE) {
                                $text = '</div>' . $lines[$i] . '<div class="highlight">' . $text; //preappend string
                        }

                }
        }
        
        return $text;
}
endif;

?>
