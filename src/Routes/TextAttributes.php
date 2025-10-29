<?php

namespace Tualo\Office\Wordcount\Routes;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;


class TextAttributes extends \Tualo\Office\Basic\RouteWrapper
{
    public static function db()
    {
        return App::get('session')->getDB();
    }
    public static function match($expr, $text): array
    {
        preg_match_all($expr, $text, $res);
        if (is_null($res)) return [];
        return $res[0];
    }
    public static function getTextAttributes($config, $text): array
    {
        $result = [];
        $config_default = [
            'min_word_length' => 1,
            'max_word_length' => 100,
            'word_contains' => "\w",
            'trim_at_first' => 1,
            'remove_double_whitespaces' => 1,
            'remove_carriage_return' => 1,
            'remove_double_new_line' => 1
        ];
        $config = array_merge($config_default, $config);
        if ($config['trim_at_first'] == '1') $text = trim($text);

        if ($config['remove_carriage_return'] == '1') $text = preg_replace("/\r/", "", $text);
        if ($config['remove_double_whitespaces'] == '1') $text = preg_replace("/[^\S\r\n]+/", " ", $text);
        if ($config['remove_double_new_line'] == '1') $text = preg_replace("/\n+/", "\n", $text);



        $limited_word_regexp = "/([" . $config['word_contains'] . "]){" . $config['min_word_length'] . "," . $config['max_word_length'] . "}/im";
        $all_word_regexp = "/([" . $config['word_contains'] . "]){" . $config['min_word_length'] . ",}/im";

        $limited_words_found = self::match($limited_word_regexp, $text);
        $all_words_found = self::match($all_word_regexp, $text);

        //$result['limited_words']=$limited_words_found;
        $result['limited_words_count'] = count($limited_words_found);
        //$result['all_words']=$all_words_found;
        $result['all_words_count'] = count($all_words_found);


        $whitespace_regexp = "/\s/im";
        $whitespaces = self::match($whitespace_regexp, $text);
        $result['whitespaces'] = count($whitespaces);

        $newline_regexp = "/\n/im";
        $newlines = self::match($newline_regexp, $text);
        $result['newlines'] = count($newlines);

        $charsInWords = 0;
        foreach ($limited_words_found as $item) {
            $charsInWords += strlen($item);
        }
        $result['limited_words_characters'] = $charsInWords;
        $charsInWords = 0;
        foreach ($all_words_found as $item) {
            $charsInWords += strlen($item);
        }
        $result['all_words_characters'] = $charsInWords;

        $unique = array_unique($all_words_found);
        $list = [];
        foreach ($unique as $item) {
            $elem = [
                'word' => $item,
                'count' => 0
            ];
            foreach ($all_words_found as $aelem) {
                if ($item == $aelem) $elem['count']++;
            }
            $list[] = $elem;
        }
        $result['unique'] = $list;
        return $result;
    }
    public static function register()
    {
        BasicRoute::add('/wordcount/attributes', function ($matches) {
            set_time_limit(300);
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
            limit 30
            ';
            $list = self::db()->direct($sql);
            foreach ($list as $item) {
                $res = self::getTextAttributes($item, $item['data']);
                $sql = 'replace into translations_texts_attributes
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
                    {json}
                )
                ';
                $item['json'] = json_encode($res);
                self::db()->direct($sql, $item);
            }


            App::executeDefferedRoute('/wordcount/newdocument', 'now');
        }, array('get'), true);
    }
}
