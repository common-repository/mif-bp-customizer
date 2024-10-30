<?php

//
// Configuration режима "вся лента" ленты активности
// 
//

defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'activity-stream' ) ) 
    new mif_bpc_activity_stream();


class mif_bpc_activity_stream {

    //
    // Пользователи, которых нельзя блокировать
    //

    public $unbanned_users = array( 'admin' );
    
    //
    // Типы активности, которые нельзя блокировать
    //

    public $unexcluded_types = array( 'activity_update' );


    function __construct()
    {

        add_action( 'bp_activity_setup_nav', array( $this, 'activity_nav' ) );
        add_filter( 'bp_activity_get_where_conditions', array( $this, 'where_conditions' ), 2, 2 );
        add_action( 'bp_before_member_activity_post_form', array( $this,'show_post_form' ) );

        add_action( 'bp_activity_entry_meta', array( $this, 'action_menu' ), 20 );


    }
    
    
    //
    // Configuration вкладок активности на странице пользователя
    //
    //

    function activity_nav()
    {
        global $bp;

        $activity_link = bp_core_get_user_domain( bp_displayed_user_id() ) . $bp->activity->slug . '/';

        if ( bp_is_my_profile() ) {

            // Whole feed

            $sub_nav = array(  
                    'name' => __( 'Whole feed', 'mif-bpc' ), 
                    'slug' => 'all-stream', 
                    'subnav_slug' => 'all-stream',
                    'parent_url' => $activity_link, 
                    'parent_slug' => $bp->activity->slug, 
                    'screen_function' => array( $this, 'activity_screen' ), 
                    'position' => 0,
                    'user_has_access'=>  bp_is_my_profile() 
                );

            bp_core_new_subnav_item( $sub_nav );
            bp_core_new_nav_default( $sub_nav );


            // Личное - сделать второй вкладкой

            $aaa = $bp->members->nav->get_secondary( array( 'parent_slug' => 'activity', 'slug' => 'just-me' ), false );
            $name = $aaa['activity/just-me']['name'];

            bp_core_remove_subnav_item( 'activity', 'just-me' );

            $sub_nav = array(
                    'name'            => $name,
                    'slug'            => 'personal',
                    'parent_url'      => $activity_link,
                    'parent_slug'     => $bp->activity->slug,
                    'screen_function' => 'bp_activity_screen_my_activity',
                    'position'        => 10
                );

            bp_core_new_subnav_item( $sub_nav );
            
        }

        // Убрать упоминания

        bp_core_remove_subnav_item( 'activity', 'mentions' );

        // Add сайты

        if ( is_multisite() ) {

            $sub_nav = array(  
                    'name' => __( 'Sites', 'mif-bpc' ), 
                    'slug' => 'sites', 
                    'parent_url' => $activity_link, 
                    'parent_slug' => $bp->activity->slug, 
                    'screen_function' => array( $this, 'activity_screen' ), 
                    'position' => 60,
                    // 'user_has_access'=>  bp_is_my_profile() 
                );

            bp_core_new_subnav_item( $sub_nav );

        }

        // Add курсы

        if ( function_exists( 'lms_get_mycourses' ) ) {

            $sub_nav = array(  
                    'name' => __( 'Courses', 'mif-bpc' ), 
                    'slug' => 'courses', 
                    'parent_url' => $activity_link, 
                    'parent_slug' => $bp->activity->slug, 
                    'screen_function' => array( $this, 'activity_screen' ), 
                    'position' => 60,
                    // 'user_has_access'=>  bp_is_my_profile() 
                );

            bp_core_new_subnav_item( $sub_nav );

        }

    }


    //  
    // Показывает ленту активности в новых вкладках
    //  

    public function activity_screen()
    {
        bp_core_load_template( apply_filters( 'bp_activity_template_mystream_activity', 'members/single/home' ) ); 
    }



    //  
    // Показывает форму публикации статуса на странице профиля пользователя
    //  
  
    public function show_post_form()
    {
        if ( is_user_logged_in() && bp_is_my_profile() &&  bp_is_activity_component() && bp_is_current_action( 'all-stream' ) ) {

            // locate_template( array( 'activity/post-form.php'), true ) ;

            bp_get_template_part( 'activity/post-form' );

        } 
    }



    //  
    // Configuration правил отображения элементов активности в лентах пользователей
    //  

    public function where_conditions( $where, $r )
    {
        global $bp;
        
        $current_user_id = bp_displayed_user_id();
        $filter_sql = '';

        if ( bp_is_my_profile() || ( isset( $r['my_profile'] ) && $r['my_profile'] == true ) ) {

            // Whole feed (моя страница)
            
            if ( $r['scope'] == 'all-stream' ) {

                $filter_sql = '(';

                // Я и мои друзья

                $friends = (array) friends_get_friend_user_ids( $current_user_id );
                $friends[] = $current_user_id;
                $friends = apply_filters( 'mif_bpc_activity_stream_friends', $friends, $current_user_id );
                $filter_sql .= '(a.user_id IN (' . implode( ',', $friends ) . ') AND a.hide_sitewide = 0)';

                // Мои группы

                $groups = groups_get_user_groups( $current_user_id );
                $component = $bp->groups->id;
                $groups_ids = implode( ',', (array) $groups['groups'] );
                if ( $groups_ids ) $filter_sql .= ' OR ((a.component=\'' . $component . '\') AND (a.item_id IN (' . $groups_ids . ')))';

                // Мои курсы

                if ( function_exists( 'lms_get_mycourses' ) ) {
                    $courses = lms_get_mycourses( $current_user_id );
                    if ( $courses ) $filter_sql .= ' OR ((a.component=\'course\') AND (a.item_id IN (' . $courses . ')))';
                } 

                // Мои сайты

                if ( is_multisite() ) {
                    $blogs = get_blogs_of_user( $current_user_id );
                    if ( $blogs ) $filter_sql .= ' OR ((a.component=\'blogs\') AND (a.item_id IN (' . implode( ',', array_keys( $blogs ) ) . ')))';
                } 

                // Мои упоминания

                $nicename = bp_core_get_username( $current_user_id );
                $filter_sql .= ' OR (a.content LIKE \'%@' . $nicename . '<%\')';

                // Favorite

                $favorites = bp_get_user_meta( $current_user_id, 'bp_favorite_activities', true );
                if ( $favorites ) $filter_sql .= ' OR (a.id IN (' . implode( ',', array_keys( $favorites ) ) . '))';

                $filter_sql .= ')';

                $where['filter_sql'] = $filter_sql;
                unset( $where['hidden_sql'] );

            }

            // Sites (моя страница)

            if ( $r['scope'] == 'sites' ) {

                $blogs = get_blogs_of_user( $current_user_id );
                if ( $blogs ) $filter_sql = '(a.component=\'blogs\') AND (a.item_id IN (' . implode( ',', array_keys( $blogs ) ) . '))';
                $where['filter_sql'] = $filter_sql;
                unset( $where['hidden_sql'] );

            }

            // Courses (моя страница)

            if ( $r['scope'] == 'courses' ) {

                if ( function_exists( 'lms_get_mycourses' ) ) {

                    $courses = lms_get_mycourses( $current_user_id );
                    if ( $courses ) $filter_sql = '(a.component=\'course\') AND (a.item_id IN (' . $courses . '))';
                    $where['filter_sql'] = $filter_sql;

                    unset( $where['hidden_sql'] );

                } 

            }

            
            // Убрать на странице "Вся лена" лишние типы активности, если такая возможность включена
            
            if ( mif_bpc_options( 'activity-exclude' ) && bp_is_current_action( 'all-stream' ) ) {

                global $mif_bpc_activity_exclude;

                $activity_exclude = $mif_bpc_activity_exclude->get_activity_exclude();
                foreach ( $activity_exclude as $key => $item ) $activity_exclude[$key] = '\'' . trim( $item ) . '\'';

                if ( $activity_exclude ) $where['activity_exclude'] = 'a.type NOT IN (' . implode( ',', $activity_exclude ) . ')';

            }
            
            // Убрать заблокированных пользователей, если такая возможность включена

            if ( mif_bpc_options( 'banned-users' ) ) {
                
                global $mif_bpc_banned_users;

                $banned_users = $mif_bpc_banned_users->get_banned_users();
                // if ( $banned_users ) $where['banned_users'] = 'a.user_id NOT IN (' . $banned_users . ')';
                if ( $banned_users ) $where['banned_users'] = 'a.user_id NOT IN (' . $banned_users . ') AND NOT ( a.type = \'activity_repost\'  AND a.secondary_item_id IN (' . $banned_users . ') )';
                // if ( $banned_users ) $where['banned_users'] = 'a.user_id NOT IN (' . $banned_users . ') AND NOT ( a.type = \'activity_repost\'  AND a.secondary_item_id IN (' . $banned_users . ')';

            }

        } else {

            // Favorite (чужая страница)

            if ( $r['scope'] == 'favorites' ) {

            	
                $favs = bp_activity_get_user_favorites( $current_user_id );
            	$fav_ids = ( ! empty( $favs ) ) ? implode( ',', (array) $favs ) : 0;

                $or = '';
              
                $groups = groups_get_user_groups( bp_loggedin_user_id() );
                $component = $bp->groups->id;
                $groups_ids = implode( ',', (array) $groups['groups'] );
                if ( $groups_ids ) $or .= ' OR (a.component=\'' . $component . '\' AND a.item_id IN (' . $groups_ids . '))';

                if ( function_exists( 'lms_get_mycourses' ) ) {
                    $courses = lms_get_mycourses( bp_loggedin_user_id() );
                    if ( $courses ) $or .= ' OR (a.component=\'course\' AND a.item_id IN (' . $courses . '))';
                }

                $filter_sql = '(a.id IN (' . $fav_ids . ')) AND (a.hide_sitewide = 0' . $or . ')';

            }

            // Groups (чужая страница)

            if ( $r['scope'] == 'groups' ) {

                $groups = groups_get_user_groups( bp_loggedin_user_id() );
                $component = $bp->groups->id;
                $groups_ids = implode( ',', (array) $groups['groups'] );
                if ( bp_loggedin_user_id() && $groups_ids ) {
                    $filter_sql = '(a.component=\'' . $component . '\') AND (a.user_id=\'' . $current_user_id . '\') AND ((a.hide_sitewide = 0) OR (a.item_id IN (' . implode( ',', $groups['groups'] ) . ')))';
                } else {
                    $filter_sql = '(a.component=\'' . $component . '\') AND (a.user_id=\'' . $current_user_id . '\') AND (a.hide_sitewide = 0)';
                }

                $where['filter_sql'] = $filter_sql;
                unset( $where['scope_query_sql'] );

            }

            // Sites (чужая страница)

            if ( $r['scope'] == 'sites' ) {

                $filter_sql = '(a.component=\'blogs\') AND (a.user_id=\'' . $current_user_id . '\')';
                $where['filter_sql'] = $filter_sql;

            }

            // Courses (чужая страница)
            
            if ( $r['scope'] == 'courses' ) {
                
                if ( function_exists( 'lms_get_mycourses' ) ) {

                    $courses = lms_get_mycourses( $current_user_id );

                }

                if ( bp_loggedin_user_id() && $courses ) {
                    $filter_sql = '(a.component=\'course\') AND (a.user_id=\'' . $current_user_id . '\') AND ((a.hide_sitewide = 0) OR (a.item_id IN (' . $courses . ')))';
                } else {
                    $filter_sql = '(a.component=\'course\') AND (a.user_id=\'' . $current_user_id . '\') AND (a.hide_sitewide = 0)';
                }

                $where['filter_sql'] = $filter_sql;
                unset( $where['hidden_sql'] );

            } 

        }

        //
        // Здесь можно уточнить правила отображения элементов
        //

        return apply_filters( 'mif_bpc_activity_stream_where_conditions', $where, $r );
    }



    // 
    // Добавляет кнопку с меню различных действий для элемента активности
    // 
    // 

    public function action_menu()
    {
        $arr = array();

        // Через этот фильтр происходит добавление элементов в меню

        $arr = apply_filters( 'mif_bpc_activity_action_menu', $arr );

        // $arr = array(
        //             array( 'href' => $exclude_url, 'descr' => __( 'Don’t show posts of this type', 'mif-bpc' ), 'class' => 'ajax', 'data' => array( 'exclude' => $activity_type ) ),
        //             array( 'href' => $settings_url, 'descr' => __( 'Configuration', 'mif-bpc' ) ),
        //         );

        if ( ! $arr ) return;

        echo '<div class="right relative disable-activity-type"><a href="" class="button bp-secondary-action disable-activity-type"><strong>&middot;&middot;&middot;</strong></a>' . mif_bpc_hint( $arr ) . '</div>';

        // echo '<a href="" class="button bp-secondary-action disable-activity-type" title="' . __( 'Don’t show posts of this type', 'mif-bpc' ) . '"><strong>&middot;&middot;&middot;</strong></a>';
        // echo '<a href="" class="button bp-secondary-action disable-activity-type"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></a>';
    }


}



?>