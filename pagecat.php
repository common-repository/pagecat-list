<?php
class UnifiedPageCatListWidget extends WP_Widget {
/*
Plugin Name: Page-Cat-List
Plugin URI: http://www.ja-nee.net/page-cat-list/
Description: Integrates a widget that displays categories and pages in one list
Author: Konrad Mohrfeldt
Version: 0.1.3
Author URI: http://www.ja-nee.net/
*/
    
    function UnifiedPageCatListWidget() {
        $widget_ops = array('classname' => 'widget_pagecat_list',
            'description' => __( "Integrates a widget that displays categories and pages in one list") );
        $control_ops = array('width' => 300, 'height' => 300);
        $this->WP_Widget('pagecat', __('Page-Cat-List'), $widget_ops, $control_ops);
    }


    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? NULL : $instance['title']);
        $order = empty($instance['order']) ? "" : $instance['order'];
        $depth = empty($instance['depth']) ? 0 : intval($instance['depth']);
        $cat_args = empty($instance['cat_args']) ? "" : $instance['cat_args'];
        $page_args = empty($instance['page_args']) ? "" : $instance['page_args'];
        $ul_id = empty($instance['ul_id']) ? "" : $instance['ul_id'];

        # Before the widget, title
        if ( $title ) {
            echo $before_widget;
            echo $before_title . $title . $after_title;
        }

        echo $this->theme_pagecat_list($this->get_pandc($order,
                                       $page_args, $cat_args), "", $ul_id, $depth);

        # After the widget
        if ( $title )
            echo $after_widget;
    }


    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(stripslashes($new_instance['title']));
        $instance['order'] = strip_tags(stripslashes($new_instance['order']));
        $instance['depth'] = strip_tags(stripslashes($new_instance['depth']));
        $instance['cat_args'] = strip_tags(stripslashes($new_instance['cat_args']));
        $instance['page_args'] = strip_tags(stripslashes($new_instance['page_args']));
        $instance['ul_id'] = strip_tags(stripslashes($new_instance['ul_id']));

        return $instance;
    }


    function form($instance) {
        //Defaults
        $instance = wp_parse_args( (array) $instance, array('title'=>'Navigation',
                                   'order'=>'', 'cat_args'=>'', 'page_args'=> '',
                                   'depth'=> '') );

        $title = htmlspecialchars($instance['title']);
        $order = htmlspecialchars($instance['order']);
        $depth = htmlspecialchars($instance['depth']);
        $cat_args = htmlspecialchars($instance['cat_args']);
        $page_args = htmlspecialchars($instance['page_args']);
        $ul_id = htmlspecialchars($instance['ul_id']);

        # Output the options
        # Title
        echo '<p style="text-align:right;"><label for="' . $this->get_field_name('title') . '">' . __('Title:') . ' <input style="width: 250px;" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';
        # Order
        echo '<p style="text-align:right;"><label for="' . $this->get_field_name('order') . '">' . __('Order:') . ' <input style="width: 200px;" id="' . $this->get_field_id('order') . '" name="' . $this->get_field_name('order') . '" type="text" value="' . $order . '" /></label></p>';
        # Depth
        echo '<p style="text-align:right;"><label for="' . $this->get_field_name('depth') . '">' . __('Depth:') . ' <input style="width: 200px;" id="' . $this->get_field_id('depth') . '" name="' . $this->get_field_name('depth') . '" type="text" value="' . $depth . '" /></label></p>';
        # Category Arguments
        echo '<p style="text-align:right;"><label for="' . $this->get_field_name('cat_args') . '">' . __('Category Args:') . ' <input style="width: 200px;" id="' . $this->get_field_id('cat_args') . '" name="' . $this->get_field_name('cat_args') . '" type="text" value="' . $cat_args . '" /></label></p>';
        # Page Arguments
        echo '<p style="text-align:right;"><label for="' . $this->get_field_name('page_args') . '">' . __('Page Args:') . ' <input style="width: 200px;" id="' . $this->get_field_id('page_args') . '" name="' . $this->get_field_name('page_args') . '" type="text" value="' . $page_args . '" /></label></p>';
        # id for first ul
        echo '<p style="text-align:right;"><label for="' . $this->get_field_name('ul_id') . '">' . __('First ul Id:') . ' <input style="width: 200px;" id="' . $this->get_field_id('ul_id') . '" name="' . $this->get_field_name('ul_id') . '" type="text" value="' . $ul_id . '" /></label></p>';
    }


    function get_pandc($order = "", $page_args = "", $cat_args = "") {
        //get pages and categories via wordpress functions
        $pages = $this->buildStrippedList(get_pages($page_args), "page");
        $categories = $this->buildStrippedList(get_categories($cat_args), "cats");

        //if user defined an order, create an array out of it
        if(order != "") $order = explode(",", $order);

        //build the temporary tree
        $list = $this->buildTree($pages, array());
        $list += $this->buildTree($categories, array());

        $final_list = array();

        //apply order to list and save it to final_list
        if(is_array($order)) {
            foreach($order as $item) {
                $final_list[] = $list[$item];
                unset($list[$item]);
            }
        }

        //if there are elements left in list add them to the final_list
        foreach($list as $item) {
            $final_list[] = $item;
        }

        return $final_list;
    }


    //build stripped lists with unified identifiers
    //so there's not so much content that is pushed around
    function buildStrippedList($bloatedList, $type) {
        $temp = array();

        foreach($bloatedList as $item) {
            $info = ($type == "page") ? $this->get_page_info($item) : $this->get_cat_info($item);
            $temp[$info['id']] = $info;
        }

        return $temp;
    }

    //get relevant information from category
    function get_cat_info($cat) {
        $temp = array();
        $temp['id'] = $cat->cat_ID;
        $temp['title'] = $cat->name;
        $temp['url'] = get_category_link($cat->cat_ID);
        $temp['parent'] = $cat->category_parent;
        $temp['children'] = array();

        return $temp;
    }

    //get relevant information from page
    function get_page_info($page) {
        if ($page->post_status == "publish") {
            $temp = array();
            $temp['id'] = $page->ID."p"; //as ids from pages are only unique in
                                         //page list we have to make them unique
                                         //again by adding the p ;)
            $temp['title'] = $page->post_title;
            $temp['url'] = get_page_link($page->ID);
            $temp['parent'] = $page->post_parent."p";
            $temp['children'] = array();

            return $temp;
        }

        return NULL;
    }

    //builds a tree structure from the flat structure that's provided by wordpress
    //first step: add items that are children but have no children to their parent
    //second step: add every item that has no parent and no children (in flat) to tree
    function buildTree($flat, $tree) {
        if (count($flat) == 0) return $tree;

        $delete = array();

        foreach($flat as $item) {
            if(($item['parent'] != "0" && $item['parent'] != "0p") && (!$this->hasChild($flat, $item['id']))) {
                $flat[$item['parent']]['children'][] = $item;
                $delete[] = $item['id'];
            }
            else if (!$this->hasChild($flat, $item['id'])) {
                $tree[$item['id']] = $item;
                $delete[] = $item['id'];
            }
        }

        foreach($delete as $id) {
            unset($flat[$id]);
        }

        return $this->buildTree($flat, $tree);
    }


    //look for children of id in flat
    function hasChild($flat, $id) {
        foreach($flat as $item) {
            if ($item['parent'] == $id) return true;
        }
        return false;
    }


    function theme_pagecat_list($list, $class = "", $id = "", $maxdepth = 0) {
        $class_h = ($class == "") ? "" : ' class="'.$class.'"';
        $id_h = ($id == "") ? "" : ' id="'.$id.'"';
        $output .= '<ul'.$class_h.$id_h.'>';
        foreach($list as $item) {
            $output .= '<li class="nav-item nav-item-'.$item['id'].'"><a href="'.$item['url'].'" title="">'.$item['title'].'</a>';
            if(count($item['children']) > 0 && ($maxdepth - 1 != 0)) {
                $output .= $this->theme_pagecat_list($item['children'], "children", "", $maxdepth - 1);
            }
            $output .= '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

}

function UnifiedPageCatListWidgetInit() {
    register_widget('UnifiedPageCatListWidget');
}
add_action('widgets_init', 'UnifiedPageCatListWidgetInit');

?>
