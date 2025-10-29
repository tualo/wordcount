<?php

namespace Tualo\Office\Wordcount\Routes;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;

/*
    >>> GET / POST  URI
    >>> SERVER

    >>> PHP 
        >>> Middlewares
            >> 1-n
            >> ROUTEN MW
                >>> ROUTE (BASIS URI)

*/

class CronRoute extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {
        BasicRoute::add('/wordcount', function ($matches) {
            set_time_limit(300);
            $transPath = 'temp/bke446s44sarm3oh35o90hg2c9';

            $translation = '.ht12345678';
            // shell_exec('rm '.$transPath.'/*.jpg');
            // shell_exec('gs -dNOPAUSE -dDOPDFMARKS=false -dBATCH -sDEVICE=jpeg -r144 -sOutputFile='.$transPath.'/%03d.jpg '.$transPath.'/'.$translation);
            /*
            $output = shell_exec('ls -lisah '.$transPath."/*.jpg | awk '{print $11}'");
            $fileARR=explode(PHP_EOL,$output);
            echo "<pre>$output";
            print_r($fileARR);
            if (isset($fileARR) && count($fileARR)>1){
                foreach($fileARR as $file){
                    if(strlen($file)>1 ){
                        $c=shell_exec('tesseract -l oci+deu '.$file.' stdout | wc -l');
                        echo $c.PHP_EOL;
                    }
                }
            }
                echo '</pre>';
            */
            shell_exec('pdftotxt ' . $transPath . '/' . $translation);
            $c =  intval(shell_exec('less ' . $transPath . '/' . $translation . ".txt | wc -w | awk '{print $1}'"));
            echo 'Wörter ges: ' . $c . PHP_EOL;
            echo "doneeee";
            exit();
            //            gs -dNOPAUSE -dDOPDFMARKS=false -dBATCH -sDEVICE=jpeg -r144 -sOutputFile=%03d.jpg .ht_58cea44e-1de9-4044-9eda-7ed3647f013f
            // tesseract -l oci+deu  002.jpg stdout | wc

        }, array('get'), true);
    }
}
