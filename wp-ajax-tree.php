<?php
/*
Plugin Name: wp-ajax-tree
Plugin URI: http://webtrance.ru/wordpress-plugins/wp-ajax-tree/
Description: This plugin provide a widget that display a category and pages tree on your sidebar, using ajax to collapse categories.
Version: 0.2
Author: Shehavtcov Nikolay <ayanami.dev@gmail.com>
Author URI: http://webtrance.ru/about/
*/
  $opts = get_option('wp-ajax-tree-widget');
  
  $path_to_plugin = path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ).'/');
  if(wp_script_is('jquery','registered')) {
    $ver = $wp_scripts->registered['jquery']->ver;
    wp_deregister_script('jquery');
    wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/$ver/jquery.min.js"), false, $ver);
    if(!is_admin()) wp_enqueue_script('jquery');
  }
  function init_wp_ajax_tree(){
	register_sidebar_widget(array("Ajax category tree widget.", "Display categories and pages tree."), "wp_ajax_tree_widget");
        register_widget_control('Ajax category tree widget.', 'wp_ajax_tree_widget_configure');
  }

  
  function count_pages($parent_page)
  {
      global $wpdb;
      $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_parent=".intval($parent_page)." AND post_type='page'"));
      return $count;
  }

  function count_cats($parent_cat)
  {
      global $wpdb;
      $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE parent=".intval($parent_cat).""));
      return $count;
  }
  function count_posts($category)
  {
      global $wpdb;
      $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships  WHERE term_taxonomy_id=".intval($category).""));
      return $count;
  }

  function list_posts($category, $echo = true)
  {

      global $wpdb;
      $posts = $wpdb->get_results("
                                    SELECT $wpdb->posts.*
                                    FROM $wpdb->posts
                                    INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
                                    INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
                                    WHERE 1=1
                                    AND $wpdb->term_taxonomy.taxonomy = 'category'
                                    AND $wpdb->term_taxonomy.term_id IN ('{$category}')
                                    AND $wpdb->posts.post_type = 'post'
                                    AND ($wpdb->posts.post_status = 'publish')
                                    GROUP BY $wpdb->posts.ID
                                    ORDER BY $wpdb->posts.post_title ASC");
     $contents = "";
     if($echo)
     {
         foreach($posts as $post)
         {
             $contents .= "<li class='ajax-tree-post-link'>";
             $contents .= "<a href='".get_permalink($post->ID)."'>".wp_ajax_tree_get_title($post->post_title)."</a>";
             $contents .= "</li>";
         }
         return $contents;
     }
     return $posts;
  }
  //
  function wp_ajax_tree_get_title($title, $more = '...')
  {
      global $opts;
      $max_length = $opts['wp-ajax-tree-maxlength'];
      $c_count = mb_strlen($title);
      if($c_count > $max_length)
      {
          $title = mb_substr($title, 0, $max_length).$more;

      }
      return $title;
      

  }
  function wp_ajax_tree_get_sub_pages($pid, $opened)
  {
      $pages = get_pages(array( 'hierarchical' => 0, 'parent' => $pid, 'sort_column' => 'post_title'));
      $result = "<ul id=''>";
      global $path_to_plugin;
      if(count($pages) > 0)
      {
          foreach($pages as $page)
          {
              $result .= "<li class='ajax-tree-cat-link'>";
              if(false !== strpos($opened, $page->ID.', '))
              {
                //Close image
                  $result .= "<img src='{$path_to_plugin}images/minus.gif' onclick='expandPage(this, {$page->ID});'/>";
              }
              elseif(count_pages($page->ID))
              {
                  $result .= "<img src='{$path_to_plugin}images/plus.gif' onclick='expandPage(this, {$page->ID});'/>";
              }
              $result .= '&nbsp;<a href="'.get_permalink($page->ID).'">'.wp_ajax_tree_get_title($page->post_title).'</a>';
              if(false !== strpos($opened, $page->ID.', '))
              {
                  $result .= "<div id='pchilds-{$page->ID}' style='display: block;'>";
                  
                  $result .= wp_ajax_tree_get_sub_pages($page->ID, $opened);
                  $result .= "</div>";
              }
              else
              {
                  $result .= "<div id='pchilds-{$page->ID}' style='display: none;'>";
                  $result .= "<img src='{$path_to_plugin}images/loading.gif'>";
                  $result .= "</div>";
              }
              $result .= "</li>";
          }
      }
      $result .= "</ul>";
      return $result;
  }
  function wp_ajax_tree_get_sub_cats($cid, $opened)
  {
     $cats = get_categories(array("parent" => $cid, 'orderby' => 'name', 'hide_empty' => 0));
     $posts_c = count_posts($cid);
     
     $result = "<ul id=''>";
      global $path_to_plugin;
      if(count($cats) > 0 || $posts_c > 0)
      {
          foreach($cats as $cat)
          {
              $result .= "<li class='ajax-tree-cat-link'>";
              
              if(false !== strpos($opened, $cat->term_id.', '))
              {
                //Close image
                  $result .= "<img src='{$path_to_plugin}images/minus.gif' onclick='expandCategory(this, {$cat->term_id});'/>";
              }
              
              elseif(count_cats($cat->term_id) || $posts_c)
              {
                  $result .= "<img src='{$path_to_plugin}images/plus.gif' onclick='expandCategory(this, {$cat->term_id});'/>";
              }
              $result .= '&nbsp;<a href="'.get_category_link($cat->term_id).'">'.wp_ajax_tree_get_title($cat->name).'</a>';
              //var_dump($cat->term_id);
              if(false !== strpos($opened, $cat->term_id.', '))
              {
                  $opened = str_replace($cat->term_id.', ', '', $opened);
                  $result .= "<div id='childs-{$cat->term_id}' style='display: block;'>";
                  $result .= wp_ajax_tree_get_sub_cats($cat->term_id, $opened);
                  $result .= "</div>";
              }
              else
              {
                  $result .= "<div id='childs-{$cat->term_id}' style='display: none;'>";
                  $result .= "<img src='{$path_to_plugin}images/loading.gif'>";
                  $result .= "</div>";
              }
              
              $result .= "</li>";
              
          }
          //List sub posts
              if($posts_c)
              {
                 $result .= list_posts($cid);
                 
              }
      }
      $result .= "</ul>";
      return $result;
  }
  function wp_ajax_tree_widget($args)
  {
      global $wpdb, $opts, $path_to_plugin;
      //Default options for display widget
      extract($args);
//
//      $before_title = "<h3 class='widget-title'>";
//      $after_title = "</h3>";
//      $before_widget = "<li>";
//      $after_widget = "</li>";
      //Get root category list

      $categories = get_categories(array("parent" => 0, 'orderby' => 'name', 'hide_empty' => 0));
      
          if(isset($_COOKIE['wp-ajax-tree-cats']))
          {
              $cats = $_COOKIE['wp-ajax-tree-cats'];
          }
      $contents .= '';
      $contents .= "<ul id='wp-ajax-tree'>";

      foreach($categories as $category)
      {
          $c_count = count_cats($category->term_id);
          $p_count = count_posts($category->term_id);
          if($c_count == 0 && $p_count == 0)
              continue;
          $contents .= "<li class='ajax-tree-cat-link'>";
          
          if(0 != $c_count || 0 != $p_count)
          {
            if(false !== strpos($cats, $category->term_id.', '))
                {
                    $contents .= "<img src='{$path_to_plugin}images/minus.gif' onclick='expandCategory(this, {$category->term_id});'/>";
                }
                else
                {
                    $contents .= "<img src='{$path_to_plugin}images/plus.gif' onclick='expandCategory(this, {$category->term_id});'/>";
                }

          }
          else
              $contents .= "&nbsp;&nbsp;&nbsp;";
          
          
          $contents .= "&nbsp;<a href=\"".  get_category_link($category->term_id)."\">".wp_ajax_tree_get_title($category->name)."</a>";
          //var_dump($category->term_id);
          if(false !== strpos($cats, $category->term_id.', ') && '' != $category->term_id)
          {
             
              $contents .= "<div id='childs-{$category->term_id}' style='display: block;'>";
              $contents .= wp_ajax_tree_get_sub_cats($category->term_id, $cats);
          }
          else
          {
              $contents .= "<div id='childs-{$category->term_id}' style='display: none;'>";
              $contents .= "<img src='{$path_to_plugin}images/loading.gif'>";
          }
          $contents .= "</div>";
          $contents .= "</li>";
      }
      //Get pages
      $show_pages = false;
      if($show_pages)
      {
          $pages = get_pages(array('child_of' => 0, 'hierarchical' => 0, 'parent' => 0, 'sort_column' => 'post_title'));
          //проверяем открытые страницы
              if(isset($_COOKIE['wp-ajax-tree-pages']))
              {
                  $opages = $_COOKIE['wp-ajax-tree-pages'];
                  //$opages = explode(', ', $opages);

              }
          foreach($pages as $page)
          {
              $contents .= "<li>";

              if(0 != count_pages($page->ID))
              {
                  if(false !== strpos($opages, $page->ID.', '))
                    {
                        $contents .= "<img src='{$path_to_plugin}images/minus.gif' onclick='expandPage(this, {$page->ID});'/>";
                    }
                    else
                    {
                        $contents .= "<img src='{$path_to_plugin}images/plus.gif' onclick='expandPage(this, {$page->ID});'/>";
                    }
              }
                  else {
                      $contents .= "&nbsp;&nbsp;&nbsp;";
                  }


              $contents .= "";
              $contents .= "&nbsp;<a href=\"".  get_permalink($page->ID)."\">".wp_ajax_tree_get_title($page->post_title)."</a>";

              if(false !== strpos($opages, $page->ID.', '))
              {
                  $contents .= "<div id='pchilds-{$page->ID}' style='display: block;'>";
                  $contents .= wp_ajax_tree_get_sub_pages($page->ID, $opages);
              }
              else
              {
                  $contents .= "<div id='pchilds-{$page->ID}' style='display: none;'>";
              }
              $contents .= "</div>";
              $contents .= "</li>";
          }
          
      }
      $contents .= "</ul>";
      $title = $opts["wp-ajax-tree-title"];
      echo $before_widget;
      echo $before_title . $title . $after_title . $contents;
      echo $after_widget;
  }
  function wp_ajax_tree_widget_configure()
  {
    $options = $newoptions = get_option('wp-ajax-tree-widget');
    global $path_to_plugin;
    if ( $_POST["wp-ajax-tree-submit"] )
    {
        $newoptions['wp-ajax-tree-title'] = strip_tags(stripslashes($_POST["wp-ajax-tree-title"]));
        $newoptions['wp-ajax-tree-maxlength'] = intval($_POST["wp-ajax-tree-maxlength"]);

        update_option('wp-ajax-tree-widget', $newoptions);
    }
      ?>
      <p>

      <label for="wp-ajax-tree-title">
        <?php _e('Title:'); ?>
        <input style="width: 250px;" id="ww-title" name="wp-ajax-tree-title" type="text" value="<?php echo $newoptions['wp-ajax-tree-title']; ?>" />
      </label>
      <label for="ww-title">
        <?php _e('Длина названия:'); ?>
        <input style="width: 250px;" id="ww-title" name="wp-ajax-tree-maxlength" type="text" value="<?php echo $newoptions['wp-ajax-tree-maxlength']; ?>" />
      </label>
    </p>
    <input type="hidden" id="wp-ajax-tree" name="wp-ajax-tree-submit" value="1" />
      <?
  }
  add_action("plugins_loaded", "init_wp_ajax_tree");
  function wp_ajax_tree_add_scripts ( ) {
      global $path_to_plugin;
      wp_enqueue_script( "wp-ajax-tree", path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) )."/js/jquery.treeview.js"), array( 'jquery' ) );
        wp_enqueue_script( "tree", path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) )."/js/tree.js"), array( 'jquery' ) );
        echo '<link rel="stylesheet" type="text/css" media="all" href="'.path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) )."/default.css").'" />';
       echo "<script type=\"text/javascript\">
       var ajax_tree_images_url = '{$path_to_plugin}images/'; var wp_ajax_tree_lock = false;
</script>";
// collapsed: true,
// unique: true,
// persist: "location"
//});</script>';
        wp_enqueue_style('my-custom-style', path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) )."/jquery.treeview.css"));
    }

add_action('wp_print_scripts', 'wp_ajax_tree_add_scripts');
function wp_ajax_tree_activate ( ) {
  if ( isset( $_REQUEST["wp_ajax_tree_expand_cat"] ) )
  {
        add_action( 'wp', 'wp_ajax_tree_expand_cat' );
  }
  if(isset($_REQUEST['wp_ajax_tree_expand_page']))
  {
        add_action( 'wp', 'wp_ajax_tree_expand_page' );
  }
  if(isset($_REQUEST['wp_ajax_tree_close_cat']))
  {
      add_action( 'wp', 'wp_ajax_tree_close_cat' );
  }
  if(isset($_REQUEST['wp_ajax_tree_close_page']))
  {
      add_action( 'wp', 'wp_ajax_tree_close_page' );
  }
}
function add_opened_page($pid)
{
    
    $cookie_str = "";
    if(isset($_COOKIE['wp-ajax-tree-pages']))
        $cookie_str = $_COOKIE['wp-ajax-tree-pages'].', ';
    if($cookie_str != '')
    {
        if(false == strpos($cookie_str, $pid.','))
            setcookie('wp-ajax-tree-pages', $cookie_str.$pid.', ');
    }
    else
    {
        setcookie('wp-ajax-tree-pages', $pid.', ');
    }
}
function add_opened_cat($cid)
{
    
    $cookie_str = "";
    if(isset($_COOKIE['wp-ajax-tree-cats']))
        $cookie_str = $_COOKIE['wp-ajax-tree-cats'].', ';
    if($cookie_str != '')
    {
        if(false == strpos($cookie_str, $cid.','))
            setcookie('wp-ajax-tree-cats', $cookie_str.$cid.', ');
    }
    else
    {
        setcookie('wp-ajax-tree-cats', $cid.', ');
    }
    
}
function wp_ajax_tree_expand_cat()
{
    global $path_to_plugin;
    $cid = intval($_GET['wp_ajax_tree_expand_cat']);
    add_opened_cat($cid);
//    die();
    
    $childs = get_categories(array("parent" => $cid, 'orderby' => 'name', 'hide_empty' => 0));
    $categories = $childs;
    $contents .= '';
    $contents .= "<ul id='' class='wp-ajax-tree-children'>";
    
      foreach($categories as $category)
      {
          $contents .= "<li class='ajax-tree-cat-link'>";
    if(count_cats($category->term_id) != 0 || count_posts($category->term_id) != 0)
    {
        $contents .= "<img src='{$path_to_plugin}images/plus.gif' onclick='expandCategory(this, {$category->term_id});'/>";
    }
    else
    {
        ;
    }
          $contents .= "&nbsp;<a href=\"".  get_category_link($category->term_id)."\">".wp_ajax_tree_get_title($category->name)."</a>";
          $contents .= "<div id='childs-{$category->term_id}' style='display: none;'>";
          $contents .= "<img src='{$path_to_plugin}images/loading.gif'>";
          $contents .="</div>";
          $contents .= "</li>";
      }
      if(count_posts($cid) != 0)
      {
          $contents .= list_posts($cid);
      }
      $contents .= "</ul>";
      echo $contents;
        die();
}
function wp_ajax_tree_expand_page()
{
    global $path_to_plugin;
    $pid = intval($_GET['wp_ajax_tree_expand_page']);
    add_opened_page($pid);
//    die();
    $childs = get_pages(array("child_of" => $pid, 'hierarchical' => 0, 'parent' => $pid, 'sort_column' => 'post_title'));
    $pages = $childs;
    $contents .= '';
    $contents .= "<ul id=''>";
    
    
      foreach($pages as $page)
      {
          $contents .= "<li>";
          if(0 != count_pages($page->ID))
        {
            $contents .= "<img src='{$path_to_plugin}images/plus.gif' onclick='expandPage(this, {$page->ID});'/>";
        }
          $contents .= "&nbsp;<a href=\"".  get_permalink($page->ID)."\">".wp_ajax_tree_get_title($page->post_title)."</a>";
          $contents .= "<div id='pchilds-{$page->ID}' style='display: none;'>";
          $contents .= "<img src='{$path_to_plugin}images/loading.gif'>";
          $contents .= "</div>";
          $contents .= "</li>";
      }
      $contents .= "</ul>";
      echo $contents;
        die();
}
function wp_ajax_tree_close_cat()
{
    $cid = intval($_REQUEST['wp_ajax_tree_close_cat']);
    if(isset($_COOKIE['wp-ajax-tree-cats']))
    {
        $cstring = $_COOKIE['wp-ajax-tree-cats'];
        
        if(false !== strpos($cstring, $cid.', '))
        {
            $cstring = str_replace($cid.', ', '', $cstring);
            setcookie('wp-ajax-tree-cats', $cstring);
        }
    }
    die();
}
function wp_ajax_tree_close_page()
{
    $pid = intval($_REQUEST['wp_ajax_tree_close_page']);
    $cstring = "";
    if(isset($_COOKIE['wp-ajax-tree-pages']))
    {
        $cstring = $_COOKIE['wp-ajax-tree-pages'];
        
        if(false !== strpos($cstring, $pid.', '))
        {
            $cstring = str_replace($pid.', ', '', $cstring);
            setcookie('wp-ajax-tree-pages', $cstring);
        }
    }
    die();
}
add_action('init', 'wp_ajax_tree_activate');
?>
