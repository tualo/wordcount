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

insert
    ignore into translations_meassure_type (meassure_type)
values
    ('Standard Messung');

create table translations_texts_attributes (
    meassure_type varchar(36),
    id varchar(36),
    type varchar(15),
    page integer default 0,
    primary key (id, type, page),
    createat datetime default current_timestamp,
    data JSON,
    key idx_translations_texts_attributes_id_type_page (id, type, page),
    key idx_translations_texts_attributes_meassure_type (meassure_type),
    constraint fk_translations_translations_texts foreign key (id, type, page) references translations_texts(id, type, page) on delete cascade on update cascade,
    constraint fk_translations_translations_meassure_type foreign key (meassure_type) references translations_meassure_type(meassure_type) on delete cascade on update cascade
);

drop table translations_texts;

create table translations_texts (
    id varchar(36),
    type varchar(15),
    page integer default 0,
    primary key (id, type, page),
    createat datetime default current_timestamp,
    data blob,
    constraint fk_translations_id foreign key (id) references translations(id) on delete cascade on update cascade
)
alter table
    translations
add
    is_processing bigint default 0;



CREATE TABLE `translations_mailtemplates` (
`type` varchar(128) NOT NULL,
`send_from` varchar(255) DEFAULT '',
`send_from_name` varchar(255) DEFAULT '',
`subject_template` varchar(255) DEFAULT '',
`reply_to` varchar(255) DEFAULT '',
`reply_to_name` varchar(255) DEFAULT '',
`body` longtext DEFAULT '',
PRIMARY KEY (`type`)
);


create table translations_mail_protcol (
    id varchar(36),
    type varchar(25),
    primary key (id, type),
    createat datetime default current_timestamp
);

create or replace view view_translations_new_customer_document_mail as 
select 
translations_texts.id,
 count(distinct translations_texts.page) translations_texts_pages,
 count(distinct translations_texts_attributes.page) translations_texts_attributes_pages,
 translations.project,
translations.source_language,
translations.destination_language
 
from 
translations
join translations_texts 
	on translations.id = translations_texts.id
            join translations_meassure_type
            join translations_texts_attributes
                on (translations_texts.id,translations_texts.type,translations_texts.page,translations_meassure_type.meassure_type)
                = (translations_texts_attributes.id,translations_texts_attributes.type,translations_texts_attributes.page,translations_texts_attributes.meassure_type)
             	and translations_texts_attributes.meassure_type='Standard Messung'
group by translations_texts.id
having translations_texts_pages=translations_texts_attributes_pages and 
id not in (select id from translations_mail_protcol where type='new_customer_document');


create or replace view view_translations_new_offer_request_document_mail as 
select 
translations_texts.id,
 count(distinct translations_texts.page) translations_texts_pages,
 count(distinct translations_texts_attributes.page) translations_texts_attributes_pages,
 translations.project,
translations.source_language,
translations.destination_language,
 uebersetzer.*
from 
translations
join translations_texts 
	on translations.id = translations_texts.id
            join translations_meassure_type
            join translations_texts_attributes
                on (translations_texts.id,translations_texts.type,translations_texts.page,translations_meassure_type.meassure_type)
                = (translations_texts_attributes.id,translations_texts_attributes.type,translations_texts_attributes.page,translations_texts_attributes.meassure_type)
             	and translations_texts_attributes.meassure_type='Standard Messung'
join translations_uebersetzer
    on translations.id = translations_uebersetzer.translation
    and translations_uebersetzer.offer_mail_id is null
join uebersetzer on 
	(translations_uebersetzer.kundennummer,translations_uebersetzer.kostenstelle)
    = 
  	(uebersetzer.kundennummer,uebersetzer.kostenstelle)

group by translations_texts.id
having translations_texts_pages=translations_texts_attributes_pages and 
id not in (select id from translations_mail_protcol where type='new_offer_request_document')