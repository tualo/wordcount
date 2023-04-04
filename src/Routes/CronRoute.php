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
class CronRoute implements IRoute{
    public static function register(){
        BasicRoute::add('/wordcount',function($matches){
            set_time_limit(300);
            
            echo "doneeee";
            exit();
//            gs -dNOPAUSE -dDOPDFMARKS=false -dBATCH -sDEVICE=jpeg -r144 -sOutputFile=%03d.jpg .ht_58cea44e-1de9-4044-9eda-7ed3647f013f
// tesseract -l oci+deu  002.jpg stdout | wc

        },array('get'),true);
    }
}

