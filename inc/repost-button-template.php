<?php

//
// Функции для шаблона repost-записей
// Сделано по аналогии с функциями шаблона обычных записей анктивности
// (сохранены фильтры стандартного шаблона)
//

defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'repost-button' ) ) {


    // Глобальная переменная. Определяется перед вызовом шаблона. Содержит все сведения repost_записи

    global $reposted_activity;


    //
    // Показать ссылку на автора repost-записи
    //

    function mif_bpc_repost_activity_user_link()
    {
        global $reposted_activity;

		if ( empty( $reposted_activity->user_id ) || empty( $reposted_activity->user_nicename ) || empty( $reposted_activity->user_login ) ) {
			$link = $reposted_activity->primary_link;
		} else {
			$link = bp_core_get_user_domain( $reposted_activity->user_id, $reposted_activity->user_nicename, $activities_template->activity->user_login );
		}

        $link =  apply_filters( 'bp_get_activity_user_link', $link );
		$link =  apply_filters( 'mif_bpc_repost_activity_user_link', $link );

        echo $link;
    }


    //
    // Показать аватар автора repost-записи
    //

    function mif_bpc_repost_activity_avatar()
    {
        global $reposted_activity;

        $avatar = apply_filters( 'bp_get_activity_avatar', bp_core_fetch_avatar( array(
			'item_id' => $reposted_activity->user_id,
			'object'  => 'user',
			'type'    => 'thumb',
			'class'   => 'avatar',
			// 'width'   => $width,
			// 'height'  => $height,
		) ) );

        $avatar = apply_filters( 'mif_bpc_repost_activity_avatar', $avatar );

        echo $avatar;
    }


    //
    // Показать верхнюю строку-комментарий repost-записи
    //

    function mif_bpc_repost_activity_action()
    {
        global $reposted_activity;

		$action = apply_filters_ref_array( 'bp_get_activity_action_pre_meta', array(
			$reposted_activity->action,
			&$reposted_activity,
			array( 'no_timestamp' => false )
		) );

		if ( ! empty( $action ) ) {
			$action = mif_bpc_insert_activity_meta( $action );
		}

		$action = apply_filters_ref_array( 'bp_get_activity_action', array(
			$action,
			&$reposted_activity,
			array( 'no_timestamp' => false )
		) );

		$action = apply_filters( 'mif_bpc_repost_activity_action', $action );

        echo $action;
    }


    //
    // Проверить наличие содержимого repost-записи
    //

    function mif_bpc_repost_activity_has_content()
    {
        global $reposted_activity;

        if ( ! empty( $reposted_activity->content ) ) {

            return true;

        }

        return false;
    }


    //
    // Показать содержимое repost-записи
    //

    function mif_bpc_repost_activity_content_body()
    {
        global $reposted_activity;

        $content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $reposted_activity->content, &$reposted_activity ) );
		$content = apply_filters( 'mif_bpc_repost_activity_content_body', $content );

        echo $content;
    }


    //
    // Сформировать мета-информацию строки-комментария repost-записи
    //

    function mif_bpc_insert_activity_meta( $content = '' ) 
    {
        global $reposted_activity;

        $new_content = str_replace( '<span class="time-since">%s</span>', '', $content );

        $date_recorded  = bp_core_time_since( $reposted_activity->date_recorded );

        $time_since = sprintf(
            '<span class="time-since" data-livestamp="%1$s">%2$s</span>',
            bp_core_get_iso8601_date( $reposted_activity->date_recorded ),
            $date_recorded
        );

        $time_since = apply_filters_ref_array( 'bp_activity_time_since', array(
            $time_since,
            &$reposted_activity
        ) );

        $activity_permalink = bp_activity_get_permalink( $reposted_activity->id, $reposted_activity );
        $activity_meta      = sprintf( '%1$s <a href="%2$s" class="view activity-time-since" title="%3$s">%4$s</a>',
            $new_content,
            $activity_permalink,
            esc_attr__( 'View Discussion', 'buddypress' ),
            $time_since
        );

        $new_content = apply_filters_ref_array( 'bp_activity_permalink', array(
            $activity_meta,
            &$reposted_activity
        ) );

        return apply_filters( 'bp_insert_activity_meta', $new_content, $content );
    }



}


?>