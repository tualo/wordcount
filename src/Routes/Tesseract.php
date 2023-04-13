<?php
namespace Tualo\Office\Wordcount\Routes;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSFileHelper;
use Ramsey\Uuid\Uuid;

class Tesseract implements IRoute{
    public static function db() { return App::get('session')->getDB(); }
    
    public static function register(){
        BasicRoute::add('/wordcount/tesseract',function($matches){

            set_time_limit(3000);

            // $sql = 'select * from translations where document>0 and id not in (select id from translations_texts) and is_processing=0 limit 1';
            $sql="select 
                    translations.*, 
                    if (languages.tesseract='','eng',languages.tesseract) tesseractSource 
                from translations,languages 
                    where translations.document>0 and 
                    translations.id not in (select id from translations_texts) and  
                    translations.is_processing=0 and 
                    languages.id=translations.source_language 
                limit 1";
            $list = self::db()->direct($sql);
            $procid = time();
            foreach($list as $item){
                $item['procid']=$procid;
                self::db()->direct('update translations set is_processing={procid} where id={id}',$item);
            }

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
                        //$tesseractSource = 'eng';
                        $tesseractSource = $item['tesseractSource'];

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
                                $params[] = "oci+$tesseractSource";
                                //$params[] = "$tesseractSource";
                                $params[] = $image;
                                $params[] = "stdout";
                                $data = [];
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
                    self::db()->direct('update translations set is_processing=0 where id={id}',$item);
                    App::executeDefferedRoute('/wordcount/attributes','now');
                }   

                
            }
            
        },array('get'),true);
    }
}