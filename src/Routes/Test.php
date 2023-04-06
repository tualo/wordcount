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
            /**
             * 
             drop table translations_texts;
            create table translations_texts (
                id varchar(36),
                type varchar(15),
                page integer default 0,
                primary key (id,type,page),
                createat datetime default current_timestamp,

                data blob,

                constraint fk_translations_id 
                foreign key (id)
                references translations(id)
                on delete cascade
                on update cascade
            )            

             */

            $sql = 'select * from translations where document>0 and id not in (select id from translations_texts) limit 1';
            $list = self::db()->direct($sql);
            foreach($list as $item){
                $path = App::get('tempPath').'/'.(Uuid::uuid4())->toString();
                $file = $path.'/original.pdf';
                
                if (mkdir($path)){
                    $res = DSFileHelper::getFile(self::db(),'translations',$item['document'],true);
                    if($res['success']===true){
                        file_put_contents($file,$res['data']);
                        $extension = 'png';
                        $gs_device = 'pngmono';
                        $resolution = '300';
                        if (isset($_REQUEST['resolution'])) $resolution = intval($_REQUEST['resolution']);
                        if (isset($_REQUEST['gs_device'])) $gs_device = intval($_REQUEST['gs_device']);
                        if (isset($_REQUEST['extension'])) $extension = intval($_REQUEST['extension']);
                        
                        $params = ['gs'];
                        $params[] =  '-q';
                        $params[] =  '-dNOPAUSE';
                        $params[] =  '-dDOPDFMARKS=false';
                        $params[] =  '-dBATCH';
                        $params[] =  '-sDEVICE='.$gs_device;
                        $params[] =  '-r'.$resolution;
                        $params[] =  '-sOutputFile='.$path.'/%05d.'.$extension;
                        $params[] =  $file;
                        exec( implode(' ',$params),$gsresult,$returnCode);
                        unlink($file);
                        if ($returnCode==0){
                            $images = glob($path.'/*.'.$extension);
                            $pageNum = 0;
                            foreach($images as $image){
                                $pageNum++;
                                $params = ['tesseract'];
                                $params[] = "-l";
                                $params[] = "oci";
                                //$params[] = "$tesseractSource";
                                $params[] = $image;
                                $params[] = "stdout";
                                exec(implode(' ',$params),$data,$returnCode);
                                if ($returnCode==0){
                                    $sql = 'insert into translations_texts (
                                        id,
                                        type,
                                        page,
                                        data
                                    ) values (
                                        {id},
                                        {type},
                                        {page},
                                        {data}
                                    )';
                                    self::db()->direct($sql,[
                                        'id'=>$item['id'],
                                        'page'=>$pageNum,
                                        'type'=>'source',
                                        'data'=>implode("\n",$data)
                                    ]);
                                }
                                unlink($image);
                            }
                        }

                    }
                    rmdir($path);
                }

                
            }
            
        },array('get'),true);
    }
}