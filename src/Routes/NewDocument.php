<?php
namespace Tualo\Office\Wordcount\Routes;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSFileHelper;
use Ramsey\Uuid\Uuid;
use Tualo\Office\DS\DSModel;
use Tualo\Office\DS\DataRenderer;
use Tualo\Office\Mail\OutgoingMail;

class NewDocument implements IRoute{
    public static function db() { return App::get('session')->getDB(); }
    
    public static function register(){
        BasicRoute::add('/wordcount/newdocument',function($matches){

            
            
            $rows = self::db()->direct('
            select 
                translations_mailtemplates.send_from,
                translations_mailtemplates.send_from_name,
                translations_mailtemplates.send_to,
                translations_mailtemplates.reply_to,
                translations_mailtemplates.reply_to_name,
                translations_mailtemplates.subject_template,
                translations_mailtemplates.body,
                translations_mailtemplates.type,
                view_translations_new_customer_document_mail.*
                /*
                view_translations_new_customer_document_mail.id,
                view_translations_new_customer_document_mail.translations_texts_pages,
                view_translations_new_customer_document_mail.translations_texts_attributes_pages,
                view_translations_new_customer_document_mail.project,
                view_translations_new_customer_document_mail.source_language,
                view_translations_new_customer_document_mail.destination_language
                */
            from 
                translations_mailtemplates 
                join view_translations_new_customer_document_mail
            where translations_mailtemplates.type="new_customer_document"
            
            ');
            foreach($rows as $row){
                $mailModel = new DSModel('outgoing_mails');
                $mailModel->set('send_from',$row['send_from'])
                    ->set('send_from_name',$row['send_from_name'])
                    ->set('send_to',$row['send_to'])
                    ->set('reply_to',$row['reply_to'])
                    ->set('reply_to_name',$row['reply_to_name'])
                    ->set('subject', DataRenderer::renderTemplate( $row['subject_template'], $row, $runfunction=true, $replaceOnlyMatches=false) )
                    ->set('body',DataRenderer::renderTemplate($row['body'], $row, $runfunction=true, $replaceOnlyMatches=false));
        
                $mail = new OutgoingMail(self::db());
                $res = $mail->add( $mailModel );
                //$mail->send();
                self::db()->direct('insert into translations_mail_protcol (id,type) values ({id},{type})',$row);
            }
            App::executeDefferedRoute('/mail/outgoing','now');
            

        },array('get'),true);
    }
}