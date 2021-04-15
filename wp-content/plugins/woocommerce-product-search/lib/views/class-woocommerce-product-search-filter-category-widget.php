<?php
/**
 * class-woocommerce-product-search-filter-category-widget.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package woocommerce-product-search
 * @since 2.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product filter widget.
 */
class WooCommerce_Product_Search_Filter_Category_Widget extends WP_Widget {

	/**
	 * This widget's name (shown on the admin UI).
	 *
	 * @var string
	 */
	private static $the_name = '';

	/**
	 * Cache identifier for all instances of this widget.
	 * There is one cache entry for all instances of this widget, an array
	 * indexed by the instances' widget_id.
	 *
	 * @var string cache id
	 */
	private static $cache_id = 'woocommerce_product_search_filter_category_widget';

	/**
	 * Cache group identifier for all instances of this widget.
	 *
	 * @var string cache flag
	 */
	private static $cache_group = 'widget';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
		self::$the_name = __( 'Product Filter &ndash; Categories', 'woocommerce-product-search' );
	}

	/**
	 * Registers the widget.
	 */
	public static function widgets_init() {
		register_widget( 'WooCommerce_Product_Search_Filter_Category_Widget' );
	}

	/**
	 * Creates the widget.
	 */
	public function __construct() {
		parent::__construct(
			self::$cache_id,
			self::$the_name,
			array(
				'description' => __( 'WooCommerce Product Search &mdash; The live product category filter for your shop. It instantly updates the products displayed, according to the chosen categories.', 'woocommerce-product-search' )
			)
		);
	}

	/**
	 * Clears cached widget.
	 */
	public static function cache_delete() {
		wp_cache_delete( self::$cache_id, self::$cache_group );
	}

	/**
	 * Renders the widget for display.
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title', 'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 *
	 * @see WP_Widget::widget()
	 * @link http://codex.wordpress.org/Class_Reference/WP_Object_Cache
	 */
	public function widget( $args, $instance ) {

		$value = isset( $instance['shop_only'] ) ? strtolower( $instance['shop_only'] ) : 'yes';
		$shop_only = $value == 'true' || $value == 'yes' || $value == '1';
		if ( $shop_only && !is_shop() && !is_product_taxonomy() ) {
			return;
		}

		WooCommerce_Product_Search_Filter_Category::load_resources();

		$cache = wps_cache_get( self::$cache_id, self::$cache_group );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		$before_title  = isset( $args['before_title'] ) ? $args['before_title'] : '';
		$after_title   = isset( $args['after_title'] ) ? $args['after_title'] : '';
		$before_widget = isset( $args['before_widget'] ) ? $args['before_widget'] : '';
		$after_widget  = isset( $args['after_widget'] ) ? $args['after_widget'] : '';

		$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '' );

		$output = '';

		$output .= $before_widget;
		if ( !empty( $title ) ) {
			$output .= $before_title . $title . $after_title;
		}
		$output .= WooCommerce_Product_Search_Filter_Category::render( $instance );
		$output .= $after_widget;

		echo $output;

		$cache[$args['widget_id']] = $output;
		wps_cache_set( self::$cache_id, $cache, self::$cache_group );
	}

	/**
	 * Save widget options
	 *
	 * @param array $new_instance holds requested settings
	 * @param array $old_instance holds current settings for the widget instance
	 *
	 * @see WP_Widget::update()
	 */
	public function update( $new_instance, $old_instance ) {

		global $wpdb;

		$settings = $old_instance;

		$settings['title'] = trim( strip_tags( $new_instance['title'] ) );

		$settings['show_thumbnails'] = !empty( $new_instance['show_thumbnails'] ) ? 'yes' : 'no';
		$settings['show_names']      = !empty( $new_instance['show_names'] ) ? 'yes' : 'no';

		$settings['show_selected_thumbnails'] = !empty( $new_instance['show_selected_thumbnails'] ) ? 'yes' : 'no';

		$settings['show_parent_thumbnails'] = !empty( $new_instance['show_parent_thumbnails'] ) ? 'yes' : 'no';
		$settings['show_parent_names']      = !empty( $new_instance['show_parent_names'] ) ? 'yes' : 'no';

		$settings['heading']            = !empty( $new_instance['heading'] ) ? trim( strip_tags( $new_instance['heading'] ) ) : null;
		$settings['heading_no_results'] = !empty( $new_instance['heading_no_results'] ) ? trim( strip_tags( $new_instance['heading_no_results'] ) ) : '';
		$settings['heading_class']      = !empty( $new_instance['heading_class'] ) ? trim( $new_instance['heading_class'] ) : '';
		$settings['heading_id']         = !empty( $new_instance['heading_id'] ) ? trim( $new_instance['heading_id'] ) : '';
		$settings['heading_element']    = !empty( $new_instance['heading_element'] ) ? trim( $new_instance['heading_element'] ) : '';
		$settings['show_heading']       = !empty( $new_instance['show_heading'] ) ? 'yes' : 'no';
		$settings['toggle']             = !empty( $new_instance['toggle'] ) ? 'yes' : 'no';
		$settings['toggle_widget']      = !empty( $new_instance['toggle_widget'] ) ? 'yes' : 'no';

		$settings['shop_only'] = !empty( $new_instance['shop_only'] ) ? 'yes' : 'no';

		$style = !empty( $new_instance['style'] ) ? $new_instance['style'] : 'list';
		switch( $style ) {
			case 'list' :
			case 'inline' :
			case 'select' :
			case 'dropdown' :
				break;
			default :
				$style = 'list';
		}
		$settings['style'] = $style;

		$size = !empty( $new_instance['size'] ) ? intval( $new_instance['size'] ) : '';
		if ( is_numeric( $size ) && $size <= 0 ) {
			$size = '';
		}
		$settings['size'] = $size;

		$settings['none_selected'] = !empty( $new_instance['none_selected'] ) ? trim( strip_tags( $new_instance['none_selected'] ) ) : __( 'Any Category', 'woocommerce-product-search' );

		$settings['height'] = !empty( $new_instance['height'] ) ? WooCommerce_Product_Search_Utility::get_css_unit( $new_instance['height'] ) : '';

		$depth = !empty( $new_instance['depth'] ) ? intval( $new_instance['depth'] ) : 0;
		if ( $depth < 0 ) {
			$depth = 0;
		}
		$settings['depth'] = $depth;

		$number = !empty( $new_instance['number'] ) ? intval( $new_instance['number'] ) : null;
		if ( $number !== null && $number < 1 ) {
			$number = 1;
		}
		if ( $number !== null ) {
			$settings['number'] = $number;
		} else {
			unset( $settings['number'] );
		}

		$expandable_from_depth = !empty( $new_instance['expandable_from_depth'] ) ? intval( $new_instance['expandable_from_depth'] ) : 0;
		if ( $expandable_from_depth < 0 ) {
			$expandable_from_depth = 0;
		}
		$settings['expandable_from_depth']  = $expandable_from_depth;
		$settings['auto_expand']            = !empty( $new_instance['auto_expand'] ) ? 'yes' : 'no';
		$settings['auto_retract']           = !empty( $new_instance['auto_retract'] ) ? 'yes' : 'no';
		$settings['expander']               = !empty( $new_instance['expander'] ) ? 'yes' : 'no';
		$settings['show_ancestors']         = !empty( $new_instance['show_ancestors'] ) ? 'yes' : 'no';
		$settings['show_parent_navigation'] = !empty( $new_instance['show_parent_navigation'] ) ? 'yes' : 'no';

		$settings['order']   = !empty( $new_instance['order'] ) ? $new_instance['order'] : 'ASC';
		$settings['orderby'] = !empty( $new_instance['orderby'] ) ? $new_instance['orderby'] : 'name';

		$settings['filter']       = !empty( $new_instance['filter'] ) ? 'yes' : 'no';
		$settings['hide_empty']   = !empty( $new_instance['hide_empty'] ) ? 'yes' : 'no';
		$settings['hierarchical'] = !empty( $new_instance['hierarchical'] ) ? 'yes' : 'no';
		$settings['multiple']     = !empty( $new_instance['multiple'] ) ? 'yes' : 'no';
		$settings['show_count']   = !empty( $new_instance['show_count'] ) ? 'yes' : 'no';

		$show = !empty( $new_instance['show'] ) ? $new_instance['show'] : 'set';
		switch( $show ) {
			case 'all' :
			case 'set' :
				break;
			default :
				$show = 'set';
		}
		$settings['show'] = $show;

		$settings['include'] = !empty( $new_instance['include'] ) ? trim( $new_instance['include'] ) : '';
		$settings['exclude'] = !empty( $new_instance['exclude'] ) ? trim( $new_instance['exclude'] ) : '';

		$child_of = $new_instance['child_of'];
		if ( empty( $child_of ) ) {
			unset( $settings['child_of'] );
		} else if ( ("[current]" == $child_of ) || ("{current}" == $child_of ) )  {
			$settings['child_of'] = "{current}";
		} else {
			$term = get_term_by( 'id', $new_instance['child_of'], 'product_cat' );
			if ( $term ) {
				$settings['child_of'] = $term->term_id;
			} else {
				$term = get_term_by( 'slug', $new_instance['child_of'], 'product_cat' );
				if ( $term ) {
					$settings['child_of'] = $term->slug;
				} else {
					$term = get_term_by( 'name', $new_instance['child_of'], 'product_cat' );
					if ( $term ) {
						$settings['child_of'] = $term->name;
					}
				}
			}
			if ( !$term ) {
				unset( $settings['child_of'] );
			}
		}

		$container_id = !empty( $new_instance['container_id'] ) ? trim( $new_instance['container_id'] ) : '';
		if ( strlen( $container_id ) > 0 ) {
			$settings['container_id'] = $container_id;
		} else {
			unset( $settings['container_id'] );
		}

		$container_class = !empty( $new_instance['container_class'] ) ? trim( $new_instance['container_class'] ) : '';
		if ( strlen( $container_class ) > 0 ) {
			$settings['container_class'] = $container_class;
		} else {
			unset( $settings['container_class'] );
		}

		$this->cache_delete();

		return $settings;
	}

	/**
	 * Output admin widget options form
	 *
	 * @param array $instance current widget settings
	 *
	 * @see WP_Widget::form()
	 */
	public function form( $instance ) {

		echo '<div class="woocommerce-product-search-filter-category-widget-settings">';

		$widget_title = isset( $instance['title'] ) ? $instance['title'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'The widget title.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Title', 'woocommerce-product-search' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" placeholder="%s"/>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $widget_title ),
			esc_attr__( 'None', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		echo '<h4>';
		esc_html_e( 'Heading', 'woocommerce-product-search' );
		echo '</h4>';

		$show_heading = isset( $instance['show_heading'] ) ? $instance['show_heading'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display the heading before the terms.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_heading' ),
			$this->get_field_name( 'show_heading' ),
			$show_heading == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show heading', 'woocommerce-product-search' ) );
		echo '</label>';

		$heading = isset( $instance['heading'] ) ? $instance['heading'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'The text displayed in the heading.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Heading', 'woocommerce-product-search' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" placeholder="%s"/>',
			esc_attr( $this->get_field_id( 'heading' ) ),
			esc_attr( $this->get_field_name( 'heading' ) ),
			esc_attr( $heading ),
			esc_attr__( 'Automatic', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		$heading_no_results = isset( $instance['heading_no_results'] ) ? $instance['heading_no_results'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'The alternative text displayed in the heading when there are no results.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Heading for no results', 'woocommerce-product-search' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s"/>',
			esc_attr( $this->get_field_id( 'heading_no_results' ) ),
			esc_attr( $this->get_field_name( 'heading_no_results' ) ),
			esc_attr( $heading_no_results )
		);
		echo '</label>';
		echo '</p>';

		echo '<h4>';
		esc_html_e( 'Display', 'woocommerce-product-search' );
		echo '</h4>';

		$shop_only = isset( $instance['shop_only'] ) ? $instance['shop_only'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display the widget on relevant shop pages only, including the shop, tag, category pages etc&hellip;', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'shop_only' ),
			$this->get_field_name( 'shop_only' ),
			$shop_only == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show on shop pages only', 'woocommerce-product-search' ) );
		echo '</label>';

		$style = isset( $instance['style'] ) ? $instance['style'] : 'list';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display the categories as a list or inline.', 'woocommerce-product-search' ) ) );
		echo esc_html__( 'Style', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<select id="%s" name="%s" class="woocommerce-product-search-filter-category-widget-style widefat">',
			esc_attr( $this->get_field_id( 'style' ) ),
			esc_attr( $this->get_field_name( 'style' ) )
		);
		$options = array(
			'list'     => __( 'List', 'woocommerce-product-search' ),
			'inline'   => __( 'Inline', 'woocommerce-product-search' ),
			'select'   => __( 'Select', 'woocommerce-product-search' ),
			'dropdown' => __( 'Dropdown', 'woocommerce-product-search' )
		);
		foreach ( $options as $key => $value ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $style == $key ? ' selected="selected" ' : '', esc_html( $value ) );
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		echo '<div class="woocommerce-product-search-filter-category-widget-style-select-excluded">';

		global $wps_show_thumbnails_count;
		$wps_show_thumbnails_count++;

		$show_thumbnails = isset( $instance['show_thumbnails'] ) ? $instance['show_thumbnails'] : 'no';
		$show_parent_thumbnails = isset( $instance['show_parent_thumbnails'] ) ? $instance['show_parent_thumbnails'] : 'no';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display thumbnails for the terms.', 'woocommerce-product-search' ) ) );
		printf(
			'<input class="wps-show-thumbnails-%d" type="checkbox" id="%s" name="%s" %s />',
			intval( $wps_show_thumbnails_count ),
			$this->get_field_id( 'show_thumbnails' ),
			$this->get_field_name( 'show_thumbnails' ),
			$show_thumbnails == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show thumbnails', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '<span class="woocommerce-product-search-filter-category-widget-style-dropdown-excluded">';
		echo ' &mdash; ';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display thumbnails for parent terms.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_parent_thumbnails' ),
			$this->get_field_name( 'show_parent_thumbnails' ),
			$show_parent_thumbnails == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Parents', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</span>';
		echo '</p>';

		echo '<div class="woocommerce-product-search-filter-category-widget-style-dropdown-excluded">';
		$show_names = isset( $instance['show_names'] ) ? $instance['show_names'] : 'yes';
		$show_parent_names = isset( $instance['show_parent_names'] ) ? $instance['show_parent_names'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display the names of the terms.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_names' ),
			$this->get_field_name( 'show_names' ),
			$show_names == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show names', 'woocommerce-product-search' ) );
		echo '</label>';
		echo ' &mdash; ';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display the names of the parent terms.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_parent_names' ),
			$this->get_field_name( 'show_parent_names' ),
			$show_parent_names == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Parents', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';
		echo '</div>';

		echo '</div>';

		echo '<div id="%s" class="woocommerce-product-search-filter-category-widget-style-dropdown-only show-selected-thumbnails-if-show-thumbnails">';
		$show_selected_thumbnails = isset( $instance['show_selected_thumbnails'] ) ? $instance['show_selected_thumbnails'] : 'no';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display thumbnails for the selected terms.', 'woocommerce-product-search' ) ) );
		printf(
			'<input class="wps-show-selected-thumbnails-%d" type="checkbox" id="%s" name="%s" %s />',
			intval( $wps_show_thumbnails_count ),
			$this->get_field_id( 'show_selected_thumbnails' ),
			$this->get_field_name( 'show_selected_thumbnails' ),
			$show_selected_thumbnails == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show thumbnails for selected', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';
		echo '</div>';

		echo '<script type="text/javascript">';
		echo 'document.addEventListener( "DOMContentLoaded", function() {';
		echo 'if ( typeof jQuery !== "undefined" ) {';
		printf(
			'jQuery( document ).on( "change", ".%s", function( e ) {' .
				'var checkbox = jQuery( ".%s" );' .
				'if ( jQuery( this ).is( ":checked" ) ) {' .
					'checkbox.prop( "disabled", false );' .
				' } else {' .
					'checkbox.prop( "checked", false );' .
					'checkbox.prop( "disabled", true );' .
				' }' .
			'} );' .
			'jQuery( ".%s" ).trigger( "change" );',
			'wps-show-thumbnails-' . $wps_show_thumbnails_count,
			'wps-show-selected-thumbnails-' . $wps_show_thumbnails_count,
			'wps-show-thumbnails-' . $wps_show_thumbnails_count
		);
		echo '}';
		echo '} );';
		echo '</script>';

		$show_count= isset( $instance['show_count'] ) ? $instance['show_count'] : 'no';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display the related number of products for each term.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_count' ),
			$this->get_field_name( 'show_count' ),
			$show_count== 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show product counts', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$hide_empty = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Do not display terms that have no related products.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'hide_empty' ),
			$this->get_field_name( 'hide_empty' ),
			$hide_empty == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Hide terms without products', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		echo '<div class="woocommerce-product-search-filter-category-widget-style-select-only">';
		$size = !empty( $instance['size'] ) ? intval( $instance['size'] ) : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'Determines the size of the dropdown.', 'woocommerce-product-search' )
		);
		echo esc_html( __( 'Size', 'woocommerce-product-search' ) );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'size' ) ),
			esc_attr( $this->get_field_name( 'size' ) ),
			esc_attr( $size ),
			__( 'One entry', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';
		echo '</div>';

		echo '<div class="woocommerce-product-search-filter-category-widget-style-select-dropdown-only">';
		$none_selected = !empty( $instance['none_selected'] ) ? $instance['none_selected'] : __( 'Any Category', 'woocommerce-product-search' );
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'The text displayed when no particular entry is selected.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Text for no selection', 'woocommerce-product-search' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" placeholder="%s"/>',
			esc_attr( $this->get_field_id( 'none_selected' ) ),
			esc_attr( $this->get_field_name( 'none_selected' ) ),
			esc_attr( $none_selected ),
			esc_attr__( 'Any Category', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';
		echo '</div>';

		echo '<div class="woocommerce-product-search-filter-category-widget-style-dropdown-only">';
		$height = !empty( $instance['height'] ) ? WooCommerce_Product_Search_Utility::get_css_unit( $instance['height'] ) : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'Determines the height of the dropdown options.', 'woocommerce-product-search' )
		);
		echo esc_html( __( 'Height', 'woocommerce-product-search' ) );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'height' ) ),
			esc_attr( $this->get_field_name( 'height' ) ),
			esc_attr( $height ),
			__( 'Shown when active', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';
		echo '</div>';

		$number = isset( $instance['number'] ) ? intval( $instance['number'] ) : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'Limits the number of terms shown, leave empty for unlimited entries.', 'woocommerce-product-search' )
		);
		echo esc_html( __( 'Number of terms', 'woocommerce-product-search' ) );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'number' ) ),
			esc_attr( $this->get_field_name( 'number' ) ),
			esc_attr( $number ),
			__( 'Unlimited', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		$toggle = isset( $instance['toggle'] ) ? $instance['toggle'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Hide the filter component when it has no options.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'toggle' ),
			$this->get_field_name( 'toggle' ),
			$toggle == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Toggle the component', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$toggle_widget = isset( $instance['toggle_widget'] ) ? $instance['toggle_widget'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Hide the widget when the component has no options.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'toggle_widget' ),
			$this->get_field_name( 'toggle_widget' ),
			$toggle_widget == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Toggle the widget', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		echo '<h4>';
		esc_html_e( 'Navigation', 'woocommerce-product-search' );
		echo '</h4>';

		echo '<div class="woocommerce-product-search-filter-category-widget-style-select-excluded woocommerce-product-search-filter-category-widget-style-dropdown-excluded">';

		$expandable_from_depth = isset( $instance['expandable_from_depth'] ) ? intval( $instance['expandable_from_depth'] ) : 0;
		echo '<p>';
		printf( '<label title="%s">', esc_attr__( 'This determines from which level of nesting the terms are expandable or up to which level they are always already expanded, showing child terms.', 'woocommerce-product-search' ) );
		echo esc_html( __( 'Expand Child Terms', 'woocommerce-product-search' ) );
		echo ' ';
		printf(
			'<select id="%s" name="%s" class="widefat">',
			esc_attr( $this->get_field_id( 'expandable_from_depth' ) ),
			esc_attr( $this->get_field_name( 'expandable_from_depth' ) )
		);
		$options = array(
			'0'  => __( 'Root terms are expandable', 'woocommerce-product-search' ),
			'1'  => __( 'Root children are expandable', 'woocommerce-product-search' ),
			'2'  => __( 'Terms from 2nd level', 'woocommerce-product-search' ),
			'3'  => __( 'Terms from 3rd level', 'woocommerce-product-search' ),
			'4'  => __( 'Terms from 4th level', 'woocommerce-product-search' ),
			'5'  => __( 'Terms from 5th level', 'woocommerce-product-search' ),
			'6'  => __( 'Terms from 6th level', 'woocommerce-product-search' ),
			'7'  => __( 'Terms from 7th level', 'woocommerce-product-search' ),
			'8'  => __( 'Terms from 8th level', 'woocommerce-product-search' ),
			'9'  => __( 'Terms from 9th level', 'woocommerce-product-search' ),
			'10' => __( 'Terms from 10th level', 'woocommerce-product-search' ),
			'25' => __( 'Terms from 25th level', 'woocommerce-product-search' ),
		);
		foreach ( $options as $key => $value ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $expandable_from_depth == $key ? ' selected="selected" ' : '', esc_html( $value ) );
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		$auto_expand = isset( $instance['auto_expand'] ) ? $instance['auto_expand'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Automatically expand children when hovering over parent – on touch devices expansion occurs on tap.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'auto_expand' ),
			$this->get_field_name( 'auto_expand' ),
			$auto_expand == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Expand automatically', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$auto_retract = isset( $instance['auto_retract'] ) ? $instance['auto_retract'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Automatically retract children when leaving the filter container – with touch devices retraction requires a tap.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'auto_retract' ),
			$this->get_field_name( 'auto_retract' ),
			$auto_retract == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Retract automatically', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$expander = isset( $instance['expander'] ) ? $instance['expander'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Whether to show expanders and retractors.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'expander' ),
			$this->get_field_name( 'expander' ),
			$expander == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show expanders', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		echo '</div>';

		$show_ancestors = isset( $instance['show_ancestors'] ) ? $instance['show_ancestors'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Whether to include the full ancestor hierarchy based on the current context or only parents within context.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_ancestors' ),
			$this->get_field_name( 'show_ancestors' ),
			$show_ancestors == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show the ancestor hierarchy', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$show_parent_navigation = isset( $instance['show_parent_navigation'] ) ? $instance['show_parent_navigation'] : 'no';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Whether to display the additional parent items based on the current context – this is displayed outside of the term hierarchy.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'show_parent_navigation' ),
			$this->get_field_name( 'show_parent_navigation' ),
			$show_parent_navigation == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Show the parent navigation', 'woocommerce-product-search' ) );
		echo '</label>';

		echo '<h4>';
		esc_html_e( 'Order', 'woocommerce-product-search' );
		echo '</h4>';

		$orderby = isset( $instance['orderby'] ) ? $instance['orderby'] : 'term_order';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Order the results by the chosen property.', 'woocommerce-product-search' ) ) );
		echo esc_html( __( 'Order by ...', 'woocommerce-product-search' ) );
		echo ' ';
		printf(
			'<select id="%s" name="%s" class="widefat">',
			esc_attr( $this->get_field_id( 'orderby' ) ),
			esc_attr( $this->get_field_name( 'orderby' ) )
		);
		$options = array(
			'name'       => __( 'Name', 'woocommerce-product-search' ),
			'slug'       => __( 'Slug', 'woocommerce-product-search' ),
			'term_order' => __( 'Term order', 'woocommerce-product-search' ),
			'id'         => __( 'ID', 'woocommerce-product-search' ),
			'count'      => __( 'Count', 'woocommerce-product-search' )
		);
		foreach ( $options as $key => $value ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $orderby == $key ? ' selected="selected" ' : '', esc_html( $value ) );
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		$order = isset( $instance['order'] ) ? $instance['order'] : 'ASC';
		echo '<p>';
		echo '<label>';
		printf( '<input type="radio" name="%s" value="ASC" %s />', esc_attr( $this->get_field_name( 'order' ) ), $order == 'ASC' ? ' checked="checked" ' : '' );
		echo ' ';
		echo esc_html( __( 'Ascending', 'woocommerce-product-search' ) );
		echo '</label>';
		echo ' ';
		echo '<label>';
		printf( '<input type="radio" name="%s" value="DESC" %s/>', esc_attr( $this->get_field_name( 'order' ) ), $order == 'DESC' ? ' checked="checked" ' : '' );
		echo ' ';
		echo esc_html( __( 'Descending', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		echo '<h4>';
		_e( 'Terms', 'woocommerce-product-search' );
		echo '</h4>';

		$depth = isset( $instance['depth'] ) ? intval( $instance['depth'] ) : 0;
		echo '<p>';
		printf( '<label title="%s">', esc_attr__( 'The levels included in the hierarchy of terms can be unlimited, include only those at the root or include root terms up to so many levels of child terms.', 'woocommerce-product-search' ) );
		echo esc_html( __( 'Depth', 'woocommerce-product-search' ) );
		echo ' ';
		printf(
			'<select id="%s" name="%s" class="widefat">',
			esc_attr( $this->get_field_id( 'depth' ) ),
			esc_attr( $this->get_field_name( 'depth' ) )
		);
		$options = array(
			'0'  => __( 'Unlimited', 'woocommerce-product-search' ),
			'1'  => __( 'Root terms', 'woocommerce-product-search' ),
			'2'  => __( 'Root and direct children', 'woocommerce-product-search' ),
			'3'  => __( 'Up to 2nd level children', 'woocommerce-product-search' ),
			'4'  => __( 'Up to 3rd level children', 'woocommerce-product-search' ),
			'5'  => __( 'Up to 4th level children', 'woocommerce-product-search' ),
			'6'  => __( 'Up to 5th level children', 'woocommerce-product-search' ),
			'7'  => __( 'Up to 6th level children', 'woocommerce-product-search' ),
			'8'  => __( 'Up to 7th level children', 'woocommerce-product-search' ),
			'9'  => __( 'Up to 8th level children', 'woocommerce-product-search' ),
			'10' => __( 'Up to 9th level children', 'woocommerce-product-search' ),
			'11' => __( 'Up to 10th level children', 'woocommerce-product-search' ),
			'26' => __( 'Up to 25th level children', 'woocommerce-product-search' ),
		);
		foreach ( $options as $key => $value ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $depth == $key ? ' selected="selected" ' : '', esc_html( $value ) );
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		$filter = isset( $instance['filter'] ) ? $instance['filter'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Activate live filtering.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'filter' ),
			$this->get_field_name( 'filter' ),
			$filter == 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Filter', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$hierarchical = isset( $instance['hierarchical'] ) ? $instance['hierarchical'] : 'yes';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Display terms as a hierarchy.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'hierarchical' ),
			$this->get_field_name( 'hierarchical' ),
			$hierarchical== 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Hierarchical', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$multiple = isset( $instance['multiple'] ) ? $instance['multiple'] : 'no';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'Allow multiple choices.', 'woocommerce-product-search' ) ) );
		printf(
			'<input type="checkbox" id="%s" name="%s" %s />',
			$this->get_field_id( 'multiple' ),
			$this->get_field_name( 'multiple' ),
			$multiple== 'yes' ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html( __( 'Multiple', 'woocommerce-product-search' ) );
		echo '</label>';
		echo '</p>';

		$show = isset( $instance['show'] ) ? $instance['show'] : 'set';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'With \'Set\', where a single term restricts the selection to itself or its children, related terms are not included.', 'woocommerce-product-search' ) ) );
		echo esc_html__( 'Show', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<select id="%s" name="%s" class="widefat">',
			esc_attr( $this->get_field_id( 'show' ) ),
			esc_attr( $this->get_field_name( 'show' ) )
		);
		$options = array(
			'set' => __( 'Set', 'woocommerce-product-search' ),
			'all' => __( 'All', 'woocommerce-product-search' )
		);
		foreach ( $options as $key => $value ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $show == $key ? ' selected="selected" ' : '', esc_html( $value ) );
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		$include = isset( $instance['include'] ) ? $instance['include'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'Determines the set of terms to display.', 'woocommerce-product-search' ) .
			' ' .
			esc_attr__( 'Input the ID, slug or name of the terms, separated by comma.', 'woocommerce-product-search' ) .
			' ' .
			esc_attr__( 'Leave this empty to include all terms.', 'woocommerce-product-search' ) .
			' ' .
			esc_attr__( 'The selection indicated here takes precedence over terms to exclude.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Terms to include', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<textarea id="%s" name="%s" class="widefat" placeholder="%s">',
			esc_attr( $this->get_field_id( 'include' ) ),
			esc_attr( $this->get_field_name( 'include' ) ),
			esc_attr__( 'All', 'woocommerce-product-search' )
		);
		echo esc_attr( $include );
		echo  '</textarea>';
		echo '</label>';
		echo '</p>';

		$exclude = isset( $instance['exclude'] ) ? $instance['exclude'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'Used to exclude particular terms.', 'woocommerce-product-search' ) .
			' ' .
			esc_attr__( 'Input the ID, slug or name of the terms, separated by comma.', 'woocommerce-product-search' ) .
			' ' .
			esc_attr__( 'Leave this empty to include all terms.', 'woocommerce-product-search' ) .
			' ' .
			esc_attr__( 'If terms to include are indicated, the selection indicated here is fully overridden.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Terms to exclude', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<textarea id="%s" name="%s" class="widefat" placeholder="%s">',
			esc_attr( $this->get_field_id( 'exclude' ) ),
			esc_attr( $this->get_field_name( 'exclude' ) ),
			esc_attr__( 'None', 'woocommerce-product-search' )
		);
		echo esc_attr( $exclude );
		echo  '</textarea>';
		echo '</label>';
		echo '</p>';

		$child_of = isset( $instance['child_of'] ) ? $instance['child_of'] : '';
		echo '<p>';
		printf( '<label title="%s">',
			esc_attr(
				__( 'Used to show children of the indicated term only.', 'woocommerce-product-search' ) .
				' ' .
				__( 'Input the ID, slug or name of the term or use {current} to show children of the current term.', 'woocommerce-product-search' )
			)
		);
		echo esc_html__( 'Parent term', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" class="widefat" placeholder="%s"/>',
			esc_attr( $this->get_field_id( 'child_of' ) ),
			esc_attr( $this->get_field_name( 'child_of' ) ),
			esc_attr( $child_of ),
			esc_attr__( 'None', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		echo '<h4>';
		esc_html_e( 'Advanced', 'woocommerce-product-search' );
		echo '</h4>';

		$heading_id = isset( $instance['heading_id'] ) ? $instance['heading_id'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'ID of the heading element.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Heading ID', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'heading_id' ) ),
			esc_attr( $this->get_field_name( 'heading_id' ) ),
			esc_attr( $heading_id ),
			esc_attr__( 'Automatic', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		$heading_class = isset( $instance['heading_class'] ) ? $instance['heading_class'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'CSS class of the heading element.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Heading Class', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'heading_class' ) ),
			esc_attr( $this->get_field_name( 'heading_class' ) ),
			esc_attr( $heading_class ),
			esc_attr__( 'Automatic', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		$heading_element = isset( $instance['heading_element'] ) ? $instance['heading_element'] : '';
		echo '<p>';
		echo sprintf( '<label title="%s">', esc_attr( __( 'The HTML element used to contain the heading text.', 'woocommerce-product-search' ) ) );
		echo esc_html__( 'Heading Element', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<select id="%s" name="%s" class="widefat">',
			esc_attr( $this->get_field_id( 'heading_element' ) ),
			esc_attr( $this->get_field_name( 'heading_element' ) )
		);
		$options = WooCommerce_Product_Search_Filter::get_allowed_filter_heading_elements();
		printf( '<option value="" %s>%s</option>', $heading_element == '' ? ' selected="selected" ' : '', esc_html( __( 'Default', 'woocommerce-product-search' ) ) );
		foreach ( $options as $key ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $heading_element == $key ? ' selected="selected" ' : '', esc_html( $key ) );
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		$container_id = isset( $instance['container_id'] ) ? $instance['container_id'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'ID of the filter\'s main div container.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Container ID', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'container_id' ) ),
			esc_attr( $this->get_field_name( 'container_id' ) ),
			esc_attr( $container_id ),
			esc_attr__( 'Automatic', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		$container_class = isset( $instance['container_class'] ) ? $instance['container_class'] : '';
		echo '<p>';
		printf(
			'<label title="%s">',
			esc_attr__( 'CSS class added to the filter\'s main div container.', 'woocommerce-product-search' )
		);
		echo esc_html__( 'Container Class', 'woocommerce-product-search' );
		echo ' ';
		printf(
			'<input id="%s" name="%s" type="text" value="%s" placeholder="%s" class="widefat"/>',
			esc_attr( $this->get_field_id( 'container_class' ) ),
			esc_attr( $this->get_field_name( 'container_class' ) ),
			esc_attr( $container_class ),
			esc_attr__( 'Automatic', 'woocommerce-product-search' )
		);
		echo '</label>';
		echo '</p>';

		echo '</div>';

		global $woocommerce_product_search_filter_category_widget_settings;
		if ( !isset( $woocommerce_product_search_filter_category_widget_settings ) ) {
			$woocommerce_product_search_filter_category_widget_settings = true;

			echo '<script type="text/javascript">';
			echo 'document.addEventListener( "DOMContentLoaded", function() {';
			echo 'if ( typeof jQuery !== "undefined" ) {';
			printf(
				'jQuery( document ).on( "change", ".woocommerce-product-search-filter-category-widget-style", function( e ) {' .
					'var select_only_containers = jQuery( this ).closest( ".woocommerce-product-search-filter-category-widget-settings" ).find( ".woocommerce-product-search-filter-category-widget-style-select-only" ),' .
					'select_excluded_containers = jQuery( this ).closest( ".woocommerce-product-search-filter-category-widget-settings" ).find( ".woocommerce-product-search-filter-category-widget-style-select-excluded" );' .
					'if ( jQuery( this ).val() === "select" ) {' .
						' select_only_containers.show();' .
						' select_excluded_containers.hide();' .
					' } else {' .
						' select_only_containers.hide();' .
						' select_excluded_containers.show();' .
					' }' .
					'var dropdown_only_containers = jQuery( this ).closest( ".woocommerce-product-search-filter-category-widget-settings" ).find( ".woocommerce-product-search-filter-category-widget-style-dropdown-only" ),' .
					'dropdown_excluded_containers = jQuery( this ).closest( ".woocommerce-product-search-filter-category-widget-settings" ).find( ".woocommerce-product-search-filter-category-widget-style-dropdown-excluded" );' .
					'if ( jQuery( this ).val() === "dropdown" ) {' .
						' dropdown_only_containers.show();' .
						' dropdown_excluded_containers.hide();' .
					' } else {' .
						' dropdown_only_containers.hide();' .
						' dropdown_excluded_containers.show();' .
					' }' .
					'var select_dropdown_only_containers = jQuery( this ).closest( ".woocommerce-product-search-filter-category-widget-settings" ).find( ".woocommerce-product-search-filter-category-widget-style-select-dropdown-only" );' .
					'if ( jQuery( this ).val() === "select" || jQuery( this ).val() === "dropdown" ) {' .
						' select_dropdown_only_containers.show();' .
					' } else {' .
						' select_dropdown_only_containers.hide();' .
					' }' .
				'} );' .
				'jQuery( "#%s" ).trigger( "change" );',
				esc_attr( $this->get_field_id( 'style' ) )
			);
			echo '}';
			echo '} );';
			echo '</script>';
		}

		echo '<script type="text/javascript">';
		echo 'document.addEventListener( "DOMContentLoaded", function() {';
		echo 'if ( typeof jQuery !== "undefined" ) {';
		printf( 'jQuery( "#%s" ).trigger( "change" );', esc_attr( $this->get_field_id( 'style' ) ) );
		echo '}';
		echo '} );';
		echo '</script>';
	}

	public function get_default_instance() {
		return array(
			'title' => '',
			'show_heading' => true,
			'heading' => '',
			'heading_no_results' => '',
			'shop_only' => true,
			'style'=> 'list',
			'show_selected_thumbnails' => true,
			'show_thumbnails' => false,
			'show_parent_thumbnails' => false,
			'show_names' => true,
			'show_parent_names' => true,
			'show_count' => false,
			'hide_empty' => true,
			'number' => '',
			'expandable_from_depth' => 0,
			'auto_expand' => true,
			'auto_retract' => true,
			'expander' => true,
			'show_ancestors' => true,
			'show_parent_navigation' => false,
			'size' => '',
			'height' => '',
			'orderby' => 'term_order',
			'order' => 'ASC',
			'depth' => 0,
			'filter' => true,
			'hierarchical' => true,
			'multiple' => false,
			'none_selected' => __( 'Any Category', 'woocommerce-product-search' ),
			'show' => 'set',
			'include' => '',
			'exclude' => '',
			'child_of' => '',
			'heading_id' => '',
			'heading_class' => '',
			'heading_element' => '',
			'container_id' => '',
			'container_class' => '',
			'toggle' => true,
			'toggle_widget' => true
		);
	}
}

WooCommerce_Product_Search_Filter_Category_Widget::init();
