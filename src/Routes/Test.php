<?php
namespace Tualo\Office\Wordcount\Routes;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSFileHelper;
use Ramsey\Uuid\Uuid;

class Test implements IRoute{
    public static function db() { return App::get('session')->getDB(); }
    
    public static function register(){
        BasicRoute::add('/wordcounttest',function($matches){

            set_time_limit(300);
            $sql = 'select * from translations where id not in (select translation_id from translations_source_informations) limit 1';
            $list = self::db()->direct($sql);
            foreach($list as $item){
                $path = App::get('tempPath').'/'.(Uuid::uuid4())->toString();
                $file = $path.'/original.pdf';
                if (mkdir($path)){
                    $res = DSFileHelper::getFile(self::db(),'translations',$item['document'],true);
                    if($res['success']===true){
                        file_put_contents($file,$res['data']);

                        $params = ['gs'];
                        $params[] =  '-q';
                        $params[] =  '-dNOPAUSE';
                        $params[] =  '-dDOPDFMARKS=false';
                        $params[] =  '-dBATCH';
                        $params[] =  '-sDEVICE=jpeg';
                        $params[] =  '-r144';
                        $params[] =  '-sOutputFile='.$path.'/%05d.jpg';
                        $params[] =  $file;
                        exec( implode(' ',$params),$gsresult,$returnCode);
                        print_r($gsresult);
                        var_dump($returnCode);
                        exit();
                    }
                }

                
            }
            
        },array('get'),true);
    }
}