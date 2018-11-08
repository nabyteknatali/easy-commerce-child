<?php
function my_theme_enqueue_styles() {

    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );

    /**
     * Needed for image/select
     */
    wp_enqueue_style( 'image-picker', get_stylesheet_directory_uri()
        . '/bower_components/image-picker/image-picker/image-picker.css');
    wp_enqueue_script( 'image-picker-script', get_stylesheet_directory_uri()
        . '/bower_components/image-picker/image-picker/image-picker.js');
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

add_filter( 'woocommerce_sale_flash', 'tcore_percentage_sale', 10, 3 );
function tcore_percentage_sale( $text, $post, $product ) {

    $text = '<span class="onsale">';

    if ( $product->is_type( 'variable' ) ) {
        $product_variations = $product->get_available_variations();
        $variation_product_id = $product_variations [0]['variation_id'];
        $variation_product = new WC_Product_Variation( $variation_product_id );
        $regular = $variation_product->regular_price;
        $sale = $variation_product->sale_price;
    }
    else{
        $regular = $product->regular_price;
        $sale = $product->sale_price;
    }
    if ( isset( $sale ) ) {
        $discount = ceil( ( ($regular - $sale) / $regular ) * 100 );
    }

    $text .= $discount . '%';

    $text .= '</span>';
    return $text;

}

/**
 * Function for generation product variations
 */
function wc_dropdown_variation_attribute_options_custom( $args = array() ) {
    $args = wp_parse_args( apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ), array(
        'options' => false,
        'attribute' => false,
        'product' => false,
        'selected' => false,
        'name' => '',
        'id' => '',
        'class' => '',
        'show_option_none' => __( 'Choose an option', 'woocommerce' ),
    ) );

    $options = $args['options'];
    $product = $args['product'];
    $attribute = $args['attribute'];
    $name = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
    $id = $args['id'] ? $args['id'] : sanitize_title( $attribute );
    $class = $args['class'];
    $show_option_none = $args['show_option_none'] ? true : false;
    $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

    if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
        $attributes = $product->get_variation_attributes();
        $options = $attributes[ $attribute ];
    }

    /**
     * Add term image
     */
    $html = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . 'image-select" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
    /**
     * /Add term image
     */

    $html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

    if ( ! empty( $options ) ) {
        if ( $product && taxonomy_exists( $attribute ) ) {
            // Get terms if this is a taxonomy - ordered. We need the names too.
            $terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

            foreach ( $terms as $term ) {
                if ( in_array( $term->slug, $options ) ) {

                    /**
                     * Add term image
                     */
                    $termTemp = explode('|', $term->name);
                    $termPrice = (integer) $termTemp[2];
                    if($termTemp[2] <> 0){
                        if($termTemp > 0){
                            $termPriceSign = '+';
                        }
                        else{
                            $termPriceSign = '';
                        }
                        $termName = $termTemp[0] . ' ' . $termPriceSign . $termPrice . ' ' . get_woocommerce_currency_symbol();
                    }
                    else{
                        $termName = $termTemp[0];
                    }
                    $termImage = 'https://www.nabytek-natali.cz/get-attribute-image/?id=' . $termTemp[1];

                    $html .= '<option data-img-src="' . $termImage .'" value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $termName ) ) . '</option>';

                    /**
                     * /Add term image
                     */
                }
            }
        } else {
            foreach ( $options as $option ) {
                // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                $selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
                $html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
            }
        }
    }

    $html .= '</select>';

    echo apply_filters( 'woocommerce_dropdown_variation_attribute_options_html', $html, $args );
}

if (!is_admin()) {
    wp_enqueue_script('start_image_select', get_stylesheet_directory_uri() . '/js/start-image-select.js', array('jquery'), '', true);
}