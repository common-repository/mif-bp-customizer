<?php

//
// Функции
//
//


defined( 'ABSPATH' ) || exit;



//
// Выводит всплывающие подсказки с меню по массиву
// $arr = array(
//             array( 'href' => $exclude_url, 'descr' => __( 'Don’t show such posts', 'mif-bpc' ), 'class' => 'ajax', 'data' => array( 'exclude' => $param )  ),
//             array( 'href' => $settings_url, 'descr' => __( 'Configuration', 'mif-bpc' ) ),
//         );
//

function mif_bpc_hint( $arr = NULL )
{
    if ( $arr == NULL ) return;

    $out = '';

    $out .= '<div class="mif-bpc-hint"><div>';

    foreach ( (array) $arr as $item ) {

        $param = '';
        if ( isset( $item['data'] ) && is_array( $item['data'] ) )
            foreach ( $item['data'] as $key => $value )
                $param = ' '. 'data-' . $key . '="' . $value . '"';

        $class = ( isset( $item['class'] ) ) ? ' class="' . $item['class'] . '"' : '';
        $out .= '<a href="' . $item['href'] . '"' . $class . $param . '>' . $item['descr'] . '</a>';

    };

    $out .= '</div></div>';

    return $out;
}


//
// Получить метку времени последней активности пользователя
//

function mif_bpc_get_last_activity_timestamp( $user_id )
{
    if ( ! $timestamp = wp_cache_get( 'last_activity_timestamp', $user_id ) ) {

        $last_activity = bp_get_user_last_activity( $user_id );

        if ( isset( $last_activity ) && $last_activity ) {

            $time_chunks = explode( ':', str_replace( ' ', ':', $last_activity ) );
            $date_chunks = explode( '-', str_replace( ' ', '-', $last_activity ) );
            $timestamp  = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );

        } else {

            $timestamp = 0;

        }
        
        wp_cache_set( 'last_activity_timestamp', $timestamp, $user_id );

    }

    return $timestamp;
}


//
// Вычисляет разницу между меткой времени и текущим моментом
//

function timestamp_to_now( $timestamp, $mode = NULL )
{
    $now = time();
    $res = $now - $timestamp;
    if ( $mode == 'day' ) $res = floor( $res / 86400 );  // 24 * 60 * 60

    return $res;
}


//
// Корректирует ответ о нахождении на странице друзей
//

function no_friends_page( $is_current_component, $component )
{
    if ( $component == 'friends' ) $is_current_component = false;

    remove_filter( 'bp_is_current_component', 'no_friends_page' );
    return $is_current_component;
}


//
// Возвращает fa-иконку для файла указанного типа
//

function mif_bpc_get_file_icon( $file, $class = '' )
{
    $default = 'file-o';
    $icon = $default;

    $arr = explode( ".", $file );
    $ext = end( $arr );

    if ( in_array( $ext, array( 'doc', 'docx', 'odt', 'rtf' ) ) ) $icon = 'file-word-o noext';
    if ( in_array( $ext, array( 'xls', 'xlsx', 'ods' ) ) ) $icon = 'file-excel-o noext';
    if ( in_array( $ext, array( 'ppt', 'pptx', 'odp' ) ) ) $icon = 'file-powerpoint-o noext';
    if ( in_array( $ext, array( 'pdf' ) ) ) $icon = 'file-pdf-o noext';
    if ( in_array( $ext, array( 'txt' ) ) ) $icon = 'file-text-o noext';
    if ( in_array( $ext, array( 'zip', 'rar', '7z' ) ) ) $icon = 'file-archive-o noext';
    if ( in_array( $ext, array( 'png', 'gif', 'jpg', 'jpeg' ) ) ) $icon = 'file-image-o noext';
    if ( in_array( $ext, array( 'mp3', 'ogg', 'wma' ) ) ) $icon = 'file-audio-o noext';
    if ( in_array( $ext, array( 'html', 'htm', 'css', 'cpp', 'pas', 'js' ) ) ) $icon = 'file-code-o noext';

    if ( $icon == $default ) {

        $arr = explode( ':', $file );
        if ( in_array( $arr[0], array( 'http', 'https' ) ) ) $icon = 'globe';

    }

    if ( $class ) $class = ' ' . $class;

    $out = '<i class="fa fa-' . $icon . $class . '"></i>';

    return apply_filters( 'mif_bpc_get_file_icon', $out, $file, $class );
}



//
// Возвращает имя пользователя так, как это делается в цикле BP
//

function mif_bpc_get_member_name( $user_id ) 
{
    if ( empty( $user_id ) ) return false;

    $user = get_user_by( 'id', $user_id );

    $name_stack = array(
                    'display_name' => $user->display_name,
                    'user_nicename' => $user->user_nicename,
                    'user_login' => $user->user_login
                    );

    $name = '';

    foreach ( $name_stack as $source ) {

        if ( ! empty( $source ) ) {

            $name = $source;
            break;

        }

    }

    return apply_filters( 'bp_get_member_name', $name );
}



//
// Возвращает короткую и понятную метку времени
// 
// $time - время в формате MySQL по GMT (2017-05-17 23:02:50)
//

function mif_bpc_time_since( $time, $reverse = false )
{

    $month = array( 
        '01' => __( 'of January', 'mif-bpc' ),
        '02' => __( 'of February', 'mif-bpc' ),
        '03' => __( 'of March', 'mif-bpc' ),
        '04' => __( 'of April', 'mif-bpc' ),
        '05' => __( 'of May', 'mif-bpc' ),
        '06' => __( 'of June', 'mif-bpc' ),
        '07' => __( 'of July', 'mif-bpc' ),
        '08' => __( 'of August', 'mif-bpc' ),
        '09' => __( 'of September', 'mif-bpc' ),
        '10' => __( 'of October', 'mif-bpc' ),
        '11' => __( 'of November', 'mif-bpc' ),
        '12' => __( 'of December', 'mif-bpc' ),
    );

    $out = '';
    $now = date( 'Y-m-d H:i:s' );
    $yesterday = date( 'Y-m-d H:i:s', time() - 86400 );

    if ( get_date_from_gmt( $time, 'Y-m-d' ) == get_date_from_gmt( $now, 'Y-m-d' ) ) {

        // Если сегодня, то вывести время и минуты
        // $out = get_date_from_gmt( $time, 'H:i' );
        $arr[0] = get_date_from_gmt( $time, 'H:i' );

    } elseif ( get_date_from_gmt( $time, 'Y-m-d' ) == get_date_from_gmt( $yesterday, 'Y-m-d' ) ) {

        // Если yesterday, то вывести время, минуты и сообщение, что это yesterday
        // $out = get_date_from_gmt( $time, 'H:i' ) . ', ' . __( 'yesterday', 'mif-bpc' );
        $arr[0] = __( 'yesterday', 'mif-bpc' );
        $arr[1] = get_date_from_gmt( $time, 'H:i' );

    } elseif ( get_date_from_gmt( $time, 'Y' ) == get_date_from_gmt( $now, 'Y' ) ) {

        // Если этом году, то вывести время, минуты, день и месяц
        $arr[0] = get_date_from_gmt( $time, 'j ' ) . $month[get_date_from_gmt( $time, 'm' )];
        $arr[1] = get_date_from_gmt( $time, 'H:i' );
        // $out = get_date_from_gmt( $time, 'H:i, j ' );
        // $out .= $month[get_date_from_gmt( $time, 'm' )];
        
    } else {

        // В остальных случаях вывести время, минуты, день с ведущими нулями, номер месяца и год
        // // $out = get_date_from_gmt( $time, 'H:i, j ' );
        // // $out .= $month[get_date_from_gmt( $time, 'm' )];
        // // $out .= get_date_from_gmt( $time, ' Y ' ) . __( 'year', 'mif-bpc' );
        // $out = get_date_from_gmt( $time, 'H:i, d.m.Y' );
        $arr[0] = get_date_from_gmt( $time, 'd.m.Y' );
        $arr[1] = get_date_from_gmt( $time, 'H:i' );
        
    }

    if ( $reverse ) $arr = array_reverse( $arr );

    return apply_filters( 'bp_get_member_name', implode( ', ', $arr ), $time );
}


//
// Красивое оформление размера файла
//

function mif_bpc_format_file_size( $size = 0 ) 
{
    if ( $size == 0 ) return 0;

    $arr = array(
        __( 'B', 'mif-bpc' ),
        __( 'kB', 'mif-bpc' ),
        __( 'MB', 'mif-bpc' ),
        __( 'Gb', 'mif-bpc' ),
        __( 'TB', 'mif-bpc' ),
        __( 'PB', 'mif-bpc' ),
        );

    $i = 0;

    while ( $size >= 1024 ) {

        $size = $size / 1024;
        $i++;

    }

    $ret = round( $size, 2 ) . ' ' . $arr[$i];

    return apply_filters( 'mif_bpc_format_file_size', $ret, $size );
}



//
// Вывод диалогового окна
//

function mif_bpc_message( $msg, $class = 'info' ) 
{
    $out = '<div id="message" class="message ' . $class . '"><p>' . $msg . '</p></div>';
    return apply_filters( 'mif_bpc_message', $out, $msg, $class );
}
