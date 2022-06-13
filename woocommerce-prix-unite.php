<?php
/*
  Plugin Name: Woocommerce Prix à l'unité
  Description: Permet de rajouter le prix au kg ou la pce pour se conformer à la législation française sur la vente de produits alimentaires
  Author: hafed Benchellali
  Author URI: https://github.com/idress-b/wp-woo-prix-a-l-unite
  Plugin URI: https://github.com/idress-b/wp-woo-prix-a-l-unite
  Version: 1.0
 
 */





function add_custom_price_box($post_id)
{
	// cette fonction est appelée trois fois car elle va rajouter 3 champs à la suite du prix : untité, conditionnement, coefficient
	woocommerce_wp_text_input(
		array(
			'id' => 'price_unit_info',
			'placeholder' => "mettre une unité (ex: kg )",
			'value' => get_post_meta(get_the_ID(), '_price_unit_info', true),
			'label' => __('Unité de mesure', 'woocommerce')
		)
	);
	woocommerce_wp_text_input(
		array(
			'id' => 'unit_label',
			'placeholder' => "mettre le conditionnement (ex: 500gr)",
			'value' => get_post_meta(get_the_ID(), '_unit_label', true),
			'label' => __('label de conditionnement', 'woocommerce'),
                         'custom_attibutes' => array('step' =>'any','min' => '0'),
			'description' => __('sera affiché à la suite du titre', 'woocommerce')
		)
	);
	woocommerce_wp_text_input(
		array(
			'id' => 'price_unit_multiplicator',
			'placeholder' => "mettre un chiffre",
			'value' => get_post_meta(get_the_ID(), '_price_unit_multiplicator', true),
			'type' => 'number',

			'label' => __('coefficient', 'woocommerce'),
			'description' => __('Coefficient multiplicateur, par ex, si vous vendez 500gr avec kg comme unité, mettez 2 (1 est choisi par défaut)', 'woocommerce')
		)
	);
}

//add_action('woocommerce_product_options_advanced', 'add_custom_price_box');
//add_action('woocommerce_product_options_pricing', 'add_custom_price_box');
//add_action('woocommerce_product_options_advanced', 'add_custom_price_box');


// j'ai choisi d'ajouter les champs dans l'onglet général decommenter pour switcher
add_action('woocommerce_product_options_general_product_data', 'add_custom_price_box');



// gestion des données meta produits 
function custom_woocommerce_process_product_meta($post_id)
{


	$price_unit_info =  stripslashes($_POST['price_unit_info']);
	$price_unit_multiplicator = $_POST['price_unit_multiplicator'];
	$unit_label = stripslashes($_POST['unit_label']);

	update_post_meta($post_id, '_price_unit_info', esc_attr($price_unit_info));
	update_post_meta($post_id, '_price_unit_multiplicator', esc_attr($price_unit_multiplicator));
	update_post_meta($post_id, '_unit_label', esc_attr($unit_label));
}

add_action('woocommerce_process_product_meta', 'custom_woocommerce_process_product_meta', 2);
add_action('woocommerce_process_product_meta_variable', 'custom_woocommerce_process_product_meta', 2);


// on récupère les données en base de données , on transforme le prix actuel en lui ajoutant le prix à l'unité
function add_custom_price_front($p, $obj)
{

	if (is_object($obj)) {
		$post_id = $obj->get_id();
	} else {
		$post_id = $obj;
	}
	$price_unit_info = get_post_meta($post_id, '_price_unit_info', true);
	$price_unit_multiplicator = get_post_meta($post_id, '_price_unit_multiplicator', true);


	if (!empty($price_unit_info)) {
		if (!empty($price_unit_multiplicator)) {
			$new_price = $obj->get_price() * $price_unit_multiplicator;
		} else {
			$new_price = $obj->get_price();
		}
		$new_price = number_format($new_price, 2, ',', '');
		$price_with_unit = $p . "<br><div class='price-per-unit'><small>" . $new_price . "€ /" . $price_unit_info . "</small></div>";

		return $price_with_unit;
	} else {
		return $p;
	}
}

// on track la ou l'on a besoin de modifier le prix avec les hooks appropriés
add_filter('woocommerce_get_price_html', 'add_custom_price_front', 10, 2);
add_filter('woocommerce_get_variation_price_html', 'add_custom_price_front', 10, 2);

add_action('woocommerce_shop_loop_item_title', 'custom_label_unit', 20, 0);

function custom_label_unit()
{
	$label = get_post_meta(get_the_ID(), '_unit_label', true);
	if ($label) {
		echo '<span class="unit-label">' . $label . "</span>";
	}
}

// ajout du style 
function add_my_custom_style()
{

	wp_enqueue_style('wp_woo_price_unit_style', 'style.css');
}

add_action('wp_enqueue_scripts', 'add_my_custom_style');
