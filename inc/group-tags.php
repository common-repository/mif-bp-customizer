<?php

//
// Теги для групп
//
//

defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'group-tags' ) ) 
    add_action( 'bp_init', 'mif_bpc_group_tags_init' );



function mif_bpc_group_tags_init()
{

    class mif_bpc_group_tags {

        public $cloud_len = 36;
        public $forbidden_tags = array();

        function __construct() 
        {
            global $bp;
            if ( ! isset ( $bp->gtags ) ) $bp->gtags = new stdClass();

            $bp->gtags->id = 'gtags';
            $bp->gtags->slug = 'tag';

            add_action( 'bp_before_directory_groups_content', array( $this, 'tag_cloud') );
            add_action( 'init', array( $this, 'rewrite_rule' ) );

            add_filter( 'groups_forbidden_names', array( $this, 'groups_forbidden_names') );
        }


        public function rewrite_rule()
        {
            // add_rewrite_tag( '%tag%', '([^&]+)' );
            // add_rewrite_rule('^groups/tag/([^/]*)/?$','index.php?category_fgos_vo=$matches[1]','top');

            // p('ss');

            // add_rewrite_tag( '%tag%', '([^&]+)' );
            // add_rewrite_rule( '^tag/([^/]*)/?', 'index.php?pagename=$matches[1]&tag=$matches[2]', 'top' );

            // add_rewrite_tag( '%tag%', '([^&]+)' );
            // add_rewrite_rule( 'blog/bp_group_type/tag/([^/]+)/?$', 'index.php?pagename=$matches[1]&tag=$matches[2]', 'top' );


            // flush_rules();
        }


        public function get_tag_cloud()
        {
            $tags = $this->get_tags();

            $args = array(  
                        'number' => $this->cloud_len, 
                        'orderby' => 'count', 
                        'order' => 'DESC', 
                        );

            $out = '';
            $out .= '<div id="gtags-top">
		            <div id="gtags-top-cloud" class="gtags">
                    ' . wp_generate_tag_cloud( $tags, $args ) .'
                    </div>
		            </div>';

            return $out;
	    }

        
        public function tag_cloud()
        {
            echo $this->get_tag_cloud();
            // echo '123';
	    }


        public function get_tags()
        {
            global $bp, $wpdb;
            
            $tags_raw = $wpdb->get_col( "SELECT meta_value FROM " . $bp->groups->table_name_groupmeta . " WHERE meta_key = 'gtags_group_tags'" );

            $tags = array();
            foreach ( (array) $tags_raw as $group_tags_str ) {

                $group_tags_arr = explode( ',', $group_tags_str );

                foreach ( (array) $group_tags_arr as $item ) {
                    $item = trim( strtolower( $item ) );
                    if ( $this->check_forbidden( $item ) ) continue;
                    $tags[$item] = ( isset( $tags[$item] ) ) ? $tags[$item] + 1 : 1;
                }

            }

            arsort( $tags );
    		$tags = array_splice( $tags, 0, $this->cloud_len );

            $arr_out = array();
            foreach( (array) $tags as $tag => $count ) {
                $tag = stripcslashes( $tag );
                // $link = $bp->root_domain . '/' . BP_GROUPS_SLUG . '/tag/' . urlencode( $tag ) ;
                $link = $bp->root_domain . '/' . BP_GROUPS_SLUG . '?tag=' . urlencode( $tag ) ;
                $arr_out[ $tag ] = (object) array( 'name' => $tag, 'count' => $count, 'link' => $link );
            }

            return $arr_out;
        }


        public function check_forbidden( $tag ) 
        {
            if ( empty( $tag ) ) return true;

            $ret = false;
            $forbidden = apply_filters( 'mif_bpc_check_forbidden', $this->forbidden_tags );
            if ( in_array( $tag, $forbidden ) ) $ret = true;

            return apply_filters( 'mif_bpc_check_forbidden', $ret, $tag );
        }


        public function groups_forbidden_names( $arr )
        {
            $arr[] = 'tag';
            return $arr;
        }


    }

    new mif_bpc_group_tags();

}



?>