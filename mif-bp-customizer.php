<?php
/*
 * Plugin Name: MIF BP Customizer
 * Description: Плагин расширения возможностей BuddyPress для создания сайта социальной сети.
 * Plugin URI:  https://github.com/alexey-sergeev/mif-bp-customizer
 * Author:      Alexey Sergeev
 * Author URI:  https://github.com/alexey-sergeev
 * Version:     1.0.0
 * Text Domain: mif-bpc
 * Domain Path: /lang/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) || exit;

include_once dirname( __FILE__ ) . '/classes/members-page.php';


include_once dirname( __FILE__ ) . '/inc/profile-as-homepage.php';
include_once dirname( __FILE__ ) . '/inc/profile-privacy.php';
include_once dirname( __FILE__ ) . '/inc/custom-background.php';
include_once dirname( __FILE__ ) . '/inc/edit-group-slug.php';
include_once dirname( __FILE__ ) . '/inc/groups-widget.php';
include_once dirname( __FILE__ ) . '/inc/members-widget.php';

include_once dirname( __FILE__ ) . '/inc/activity-stream.php';
include_once dirname( __FILE__ ) . '/inc/banned-users.php';
include_once dirname( __FILE__ ) . '/inc/activity-exclude.php';
include_once dirname( __FILE__ ) . '/inc/like-button.php';
include_once dirname( __FILE__ ) . '/inc/repost-button.php';
include_once dirname( __FILE__ ) . '/inc/repost-button-template.php';
include_once dirname( __FILE__ ) . '/inc/activity-button-customize.php';
include_once dirname( __FILE__ ) . '/inc/followers.php';
include_once dirname( __FILE__ ) . '/inc/notifications.php';
include_once dirname( __FILE__ ) . '/inc/websocket.php';


include_once dirname( __FILE__ ) . '/inc/dialogues/dialogues-core.php';
include_once dirname( __FILE__ ) . '/inc/dialogues/dialogues-screen.php';
include_once dirname( __FILE__ ) . '/inc/dialogues/dialogues-templates.php';
include_once dirname( __FILE__ ) . '/inc/dialogues/dialogues-ajax.php';
include_once dirname( __FILE__ ) . '/inc/dialogues.php';

include_once dirname( __FILE__ ) . '/inc/docs/docs-core.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-screen.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-templates.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-ajax.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-group.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-activity.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-dialogues.php';
include_once dirname( __FILE__ ) . '/inc/docs/docs-admin.php';
include_once dirname( __FILE__ ) . '/inc/docs.php';

include_once dirname( __FILE__ ) . '/inc/settings-page-admin.php';
include_once dirname( __FILE__ ) . '/inc/banned-users-admin.php';

include_once dirname( __FILE__ ) . '/inc/functions.php';


// Подключение языкового файла

load_plugin_textdomain( 'mif-bpc', false, basename( dirname( __FILE__ ) ) . '/lang' );


 
// Проверка опций
// 
// 

function mif_bpc_options( $key )
{
    $ret = false;
    $args = get_mif_bpc_options();

    if ( isset( $args[$key] ) ) $ret = $args[$key];

    return $ret;
}  

// 
// Получить опции
// 
// 

function get_mif_bpc_options()
{
    $default = array(
                'profile-as-homepage' => true,
                'profile-privacy' => true,
                'custom-background' => false,
                'edit-group-slug' => true,
                'groups-widget' => true,
                'members-widget' => true,
                'group-tags' => true,
                'activity-stream' => true,
                'activity-exclude' => true,
                'banned-users' => true,
                'like-button' => true,
                'repost-button' => true,
                'activity-button-customize' => true,
                'followers' => true,
                'notifications' => true,
                'dialogues' => true,
                'websocket' => false,
                'docs' => true,
            );

    foreach ( $default as $key => $value ) $args[$key] = get_option( $key, $default[$key] );

    return $args;
}



//
// Подключаем свой файл CSS
//
//

add_action( 'wp_enqueue_scripts', 'mif_bp_customizer_styles' );

function mif_bp_customizer_styles() 
{
	wp_register_style( 'mif-bpc-styles', plugins_url( 'mif-bpc-styles.css', __FILE__ ) );
	wp_enqueue_style( 'mif-bpc-styles' );

    wp_register_style( 'font-awesome', plugins_url( '/css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'font-awesome' );
}



//
// Добавляем папку плагина в число папок, где производится поиск шаблонов
//
//

add_filter( 'bp_get_template_stack', 'mif_bpс_template_stack' );

function mif_bpс_template_stack( $stack )
{
    array_unshift( $stack, plugin_dir_path( __FILE__ ) . 'templates' );
    return $stack;
}















//
// Перемещаем кнопку "Add to friends" на третье место
//
//

add_action( 'bp_member_header_actions', 'friends_button_fix', 1 );

function friends_button_fix()
{
    remove_action( 'bp_member_header_actions', 'bp_add_friend_button', 5 );
    add_action( 'bp_member_header_actions', 'bp_add_friend_button', 30 );
}





if ( ! function_exists( 'p' ) ) {

    function p( $data )
    {
        print_r( '<pre>' );
        print_r( $data );
        print_r( '</pre>' );
    }

}


if ( ! function_exists( 'f' ) ) {

    function f( $data )
    {
        file_put_contents( '/tmp/log.txt', print_r( $data, true ) );
    }

}


?>