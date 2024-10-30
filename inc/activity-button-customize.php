<?php

//
// Класс исключенной активности
// 
//

defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'activity-button-customize' ) ) {

    global $mif_bpc_activity_button_customize;
    $mif_bpc_activity_button_customize = new mif_bpc_activity_button_customize();

}


class mif_bpc_activity_button_customize {

    //
    // Меняет внешний вид кнопок "Favorite" и "Delete"
    // 

    //
    // Способ удаления старых кнопок
    //

    public $method_of_old_button_remove = 'css';


    function __construct()
    {

        add_action( 'bp_activity_entry_meta', array( $this, 'favorite_button' ), 20 );
        add_action( 'wp_ajax_favorite-button-press', array( $this, 'favorite_button_ajax_helper' ) );

        // add_action( 'bp_activity_entry_meta', array( $this, 'remove_button' ), 30 );
        add_filter( 'mif_bpc_activity_action_menu', array( $this, 'remove_button' ), 30 );
        add_action( 'wp_ajax_remove-button-press', array( $this, 'remove_button_ajax_helper' ) );


        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				

        $method_of_old_button_remove = apply_filters( 'mif_bpc_method_of_old_button_remove', $this->method_of_old_button_remove );
        if ( $method_of_old_button_remove == 'css' ) add_filter( 'wp_head', array( $this, 'add_css' ) );

    }
    
    

    //
    // Показать кнопку "Favorite"
    //

    function favorite_button()
    {
		$url = wp_nonce_url( home_url( bp_get_activity_root_slug() . '/favorite/' . bp_get_activity_id() . '/' ), 'mif_bpc_favorite_button_press' );
        $active = ( bp_get_activity_is_favorite() ) ? ' active' : '';

        $button = '<div class="favorite' . $active . '"><a href="' . $url . '" class="button bp-primary-action favorite"><i class="fa fa-star" aria-hidden="true"></i></a></div>';

        // Здесь можно убрать or скорректировать кнопку "Favorite"

        $button = apply_filters( 'mif_bpc_like_button_favorite_button', $button );

        echo $button;
    }

    public function favorite_button_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_favorite_button_press' );

        if ( ! mif_bpc_options( 'activity-button-customize' ) ) wp_die();

        $activity_id = (int) $_POST['activity_id'];
        $user_id = bp_loggedin_user_id();

        $favorites = bp_activity_get_user_favorites( $user_id );
        
        if ( in_array( $activity_id, (array) $favorites ) ) {

            if ( bp_activity_remove_user_favorite( $activity_id, $user_id ) ) echo 'unfav';

        } else {

            if ( bp_activity_add_user_favorite( $activity_id, $user_id ) ) echo 'fav';

        }

        wp_die();
    }



    //
    // Показать строку "Delete" в меню активности
    //

    function remove_button( $arr )
    {
        if ( ! bp_activity_user_can_delete() ) return;
        $activity_id = bp_get_activity_id();

        $descr = __( 'Delete post', 'mif-bpc' );

        if ( bp_is_single_activity() ) {

            $url   = bp_get_activity_delete_url();
            $arr[] = array( 'href' => $url, 'descr' => $descr );

        } else {

            $url = wp_nonce_url( home_url( bp_get_activity_root_slug() . '/delete/' . $activity_id . '/' ), 'mif_bpc_remove_button_press' );
            $arr[] = array( 'href' => $url, 'descr' => $descr, 'class' => 'ajax activity-remove', 'data' => array( 'aid' => $activity_id ) );

        }

        return $arr;
    }


    public function remove_button_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_remove_button_press' );

        if ( ! mif_bpc_options( 'activity-button-customize' ) ) wp_die();

        $activity_id = (int) $_POST['activity_id'];
        $user_id = bp_loggedin_user_id();

        $ret = false;
    
        $activity = new BP_Activity_Activity( $activity_id );
        
        if ( bp_activity_user_can_delete( $activity ) ) {

            $ret = bp_activity_delete_by_activity_id( $activity_id );
            // $ret = true;

        }

        // if ( $ret ) $out = ( bp_is_single_activity() ) ? 'removed-activity-single' : 'removed-activity-stream';
        // echo $out;

        if ( $ret ) echo 'removed';

        wp_die();
    }


    public function load_js_helper()
    {
        wp_register_script( 'mif_bpc_favorite-button', plugins_url( '../js/activity-button-customize.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_favorite-button' );
    }


    //
    // Добавляет на страницу фрагмент css, удаляющий старые кнопки
    // Альтернатива - удалить кнопки в теме оформления и не вызывать эту функцию
    //

    public function add_css()
    {
        $out = '<style type="text/css">.button.fav, .button.unfav, .button.delete-activity { display: none; }</style>';
        echo $out;
    }

}



?>