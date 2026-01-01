<?php
// [google_reviews]
function google_reviews_shortcode($atts, $content = null)
{
	ob_start();

	$apiKey = get_option('google_api_key'); // Assurez-vous que votre clé API est stockée dans les options
	$placeId = get_option('google_place_id'); // Remplacez par votre Place ID

	GoogleReviewsCall($apiKey, $placeId);

	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

// Register shortcode
add_shortcode('google_reviews', 'google_reviews_shortcode');