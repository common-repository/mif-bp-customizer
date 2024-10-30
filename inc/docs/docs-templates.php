<?php

//
// Documents (функции шаблона текущего документа)
// 
//


defined( 'ABSPATH' ) || exit;



//
// Выводит форму загрузки
//

function mif_bpc_the_docs_upload_form()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_upload_form();
}



//
// Выводит список папок
//

function mif_bpc_the_folders()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folders();
}



//
// Выводит форму создания or настройки папки
//

function mif_bpc_the_folder_settings()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folder_settings();
}



//
// Выводит содержимое страницы документов
//

function mif_bpc_the_docs_content()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_docs_content();
}



//
// Выводит статусную строку документа
//

function mif_bpc_the_doc_statusbar()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_doc_statusbar();
}



//
// Выводит статусную строку папки
//

function mif_bpc_the_folder_statusbar()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folder_statusbar();
}



//
// Выводит документ на страницу документа
//

function mif_bpc_docs_the_doc()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_doc();
}



//
// Выводит мета-информацию на страницу документа
//

function mif_bpc_docs_the_meta()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_doc_meta();
}



//
// Выводит имя документа
//

function mif_bpc_docs_the_name()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_name();
}



//
// Выводит владельца документа
//

function mif_bpc_docs_the_owner()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_owner();
}



//
// Выводит папку документа
//

function mif_bpc_docs_the_folder()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folder();
}



//
// Выводит группу документа
//

function mif_bpc_docs_the_group()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_group();
}



//
// Выводит время размещения документа
//

function mif_bpc_docs_the_date()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_date();
}



//
// Выводит ссылку на следующий документ
//

function mif_bpc_docs_the_next()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_next();
}



//
// Выводит ссылку на предыдущий документ
//

function mif_bpc_docs_the_prev()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_prev();
}


?>