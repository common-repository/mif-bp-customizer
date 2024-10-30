<?php

//
// Configuration профиля как домашней страницы
//
//


defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'profile-as-homepage' ) ) 
    add_action( 'wp', 'mif_bpc_profile_as_homepage' );

function mif_bpc_profile_as_homepage()
{
	global $bp;

    if ( ! is_main_site() ) return;

    if ( is_user_logged_in() && is_front_page() ) {
        wp_redirect( $bp->loggedin_user->domain );
    }

}


if ( mif_bpc_options( 'profile-as-homepage' ) ) 
    add_action( 'wp_logout', 'mif_bpc_logout_redirection' );

function mif_bpc_logout_redirection()
{
	global $bp;
	$redirect = $bp->root_domain;
	wp_logout_url( $redirect );	
}

