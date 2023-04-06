<?php
namespace Tualo\Office\Wordcount\Routes;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSFileHelper;
use Ramsey\Uuid\Uuid;

class TextAttributes implements IRoute{
    public static function db() { return App::get('session')->getDB(); }
    public static function getTextAttributes($config,$text):array{
        $result = [];
        $config_default = [
            'min_word_length'=>1,
            'max_word_length'=>100,
            'word_contains'=>"\w",
            'trim_at_first'=>1,
            'remove_double_whitespaces'=>1,
            'remove_carriage_return'=>1
        ];
        $config=array_merge($config_default ,$config);
        $limited_word_regexp = "/(?P<word>[".$config['word_contains']."]){".$config['min_word_length'].",".$config['max_word_length']."}/gim";
        preg_match($limited_word_regexp,$text,$limited_words_found);
        $result['limited_words_found']=$limited_words_found;
        return $result;
    }
    public static function register(){
        BasicRoute::add('/wordcountattributes',function($matches){

            set_time_limit(300);
            /**
             * 
            drop table translations_meassure_type;
            create table translations_meassure_type (
                meassure_type varchar(36) primary key,
                min_word_length integer default 1,
                max_word_length integer default 8,
                word_contains varchar(100) default '\w\,\.',
                trim_at_first tinyint default 1,
                remove_double_whitespaces tinyint default 1,
                remove_carriage_return tinyint default 1
            );
            insert ignore into translations_meassure_type (meassure_type) values ('Standard Messung');

            create table translations_texts_attributes (
                meassure_type varchar(36),
                id varchar(36),
                type varchar(15),
                page integer default 0,
                primary key (id,type,page),
                createat datetime default current_timestamp,

                data JSON,

                key idx_translations_texts_attributes_id_type_page (id,type,page),
                key idx_translations_texts_attributes_meassure_type (meassure_type),
                
                constraint fk_translations_translations_texts 
                foreign key (id,type,page)
                references translations_texts(id,type,page)
                on delete cascade
                on update cascade,

                constraint fk_translations_translations_meassure_type 
                foreign key (meassure_type)
                references translations_meassure_type(meassure_type)
                on delete cascade
                on update cascade
            )
            */



            $sql = ' 
            select 
                translations_texts.*,
                translations_meassure_type.* ,
                translations_texts_attributes.id translations_texts_attributes_id
            from 
                translations_texts 
            join translations_meassure_type
            left join translations_texts_attributes
                on (translations_texts.id,translations_texts.type,translations_texts.page,translations_meassure_type.meassure_type)
                = (translations_texts_attributes.id,translations_texts_attributes.type,translations_texts_attributes.page,translations_texts_attributes.meassure_type)
            having translations_texts_attributes_id is null        
            limit 1;
            ';
            $list = self::db()->direct($sql);
            foreach($list as $item){
                $res = self::getTextAttributes($item,$item['text']);
                print_r($res);
                $sql = 'insert into translations_texts_attributes
                (
                    meassure_type,
                    id,
                    type,
                    page,
                    data
                ) values (
                    {meassure_type},
                    {id},
                    {type},
                    {page},
                    {data}
                )
                ';
                self::db()->direct($sql,$res);
            }
            
        },array('get'),true);
    }
}