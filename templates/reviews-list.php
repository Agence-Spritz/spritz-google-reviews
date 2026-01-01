<?php

/**
 * Template for Google Reviews list
 * 
 * Available variables:
 * @var array $data The full API response
 * @var int   $reviews_count Total ratings
 * @var float $rating_global Global rating
 */

if (!defined('ABSPATH')) exit;

// --- SECTION EN-TÊTE ---
echo '<div class="google-reviews-section">';

// --- GRILLE DES AVIS ---
echo '<div class="google-reviews-grid">';

if ($reviews_count && !empty($data['result']['reviews'])) {
    $index = 0; // compteur d’avis

    foreach ($data['result']['reviews'] as $review) {
        $bg_class = ($index % 2 === 0) ? 'review-dark' : 'review-light';
        $id = 'review_' . $index;

        echo '<div class="review-card ' . $bg_class . '" itemscope itemtype="https://schema.org/Review">';

        // ✅ Élément évalué (Agence Spritz + adresse depuis options)
        echo '<div itemprop="itemReviewed" itemscope itemtype="https://schema.org/LocalBusiness">';
        echo '<meta itemprop="name" content="' . esc_attr(get_option('org_legal_name')) . '">';
        // ✅ Bloc adresse obligatoire
        echo '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
        echo '<meta itemprop="streetAddress" content="' . esc_attr(get_option('adresse')) . '">';
        echo '<meta itemprop="postalCode" content="' . esc_attr(get_option('cp')) . '">';
        echo '<meta itemprop="addressLocality" content="' . esc_attr(get_option('ville')) . '">';
        echo '<meta itemprop="addressCountry" content="' . esc_attr(get_option('pays')) . '">';
        echo '</div>'; // fin address
        echo '</div>'; // fin itemReviewed

        // ✅ Auteur + date
        echo '<div class="review-header d-flex justify-content-between">';
        echo '<span class="review-author" itemprop="author" itemscope itemtype="https://schema.org/Person">';
        echo '<meta itemprop="name" content="' . htmlspecialchars($review['author_name']) . '">';
        echo htmlspecialchars($review['author_name']);
        echo '</span>';
        echo '<span class="review-date">' . date("F Y", $review['time']) . '</span>';
        echo '</div>';

        // ✅ Étoiles + note
        echo '<div class="review-rating d-flex align-items-center" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
        for ($i = 1; $i <= 5; $i++) {
            $star_class = ($i <= $review['rating']) ? 'star-filled' : 'star-empty';
            echo '<i class="fas fa-star ' . $star_class . '"></i>';
        }
        echo '<span class="rating-value">' . htmlspecialchars($review['rating']) . '/5</span>';
        echo '<meta itemprop="ratingValue" content="' . htmlspecialchars($review['rating']) . '">';
        echo '<meta itemprop="bestRating" content="5">';
        echo '</div>';

        // ✅ Texte avec clamp
        echo '<p id="' . $id . '_text" class="review-text clamp" itemprop="reviewBody">'
            . nl2br(htmlspecialchars($review['text']))
            . '</p>';

        // ✅ Bouton Lire la suite
        echo '<button class="toggle-review" data-target="' . $id . '_text">Lire la suite</button>';

        echo '</div>'; // end review-card
        $index++;
    }
}

// Card "note globale" dans le même grid
if (isset($data['result'])) {
    $rating        = $data['result']['rating'] ?? 0;
    $reviews_count = $data['result']['user_ratings_total'] ?? 0;
    $reviews_link  = $data['result']['url'] ?? '#';

    // On ajoute une card claire (fond blanc)
    echo '<div class="review-card review-light global-review-card text-center" itemscope itemtype="https://schema.org/AggregateRating">';

    // ✅ Élément évalué (Agence Spritz + adresse depuis options)
    echo '<div itemprop="itemReviewed" itemscope itemtype="https://schema.org/LocalBusiness">';
    echo '<meta itemprop="name" content="' . esc_attr(get_option('org_legal_name')) . '">';
    // ✅ Bloc adresse obligatoire
    echo '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
    echo '<meta itemprop="streetAddress" content="' . esc_attr(get_option('adresse')) . '">';
    echo '<meta itemprop="postalCode" content="' . esc_attr(get_option('cp')) . '">';
    echo '<meta itemprop="addressLocality" content="' . esc_attr(get_option('ville')) . '">';
    echo '<meta itemprop="addressCountry" content="' . esc_attr(get_option('pays')) . '">';
    echo '</div>'; // fin address
    echo '</div>'; // fin itemReviewed

    // Logo Google
    echo '<div class="global-logo margin-bottom-10">';
    echo '<img width="40" height="40" src="' . SPRITZ_GOOGLE_REVIEWS_IMG . '/logo-google.svg" alt="Logo Google" />';
    echo '</div>';

    // Étoiles + note
    echo '<div class="review-rating margin-bottom-10">';
    for ($i = 1; $i <= 5; $i++) {
        $star_class = ($i <= round($rating)) ? 'star-filled' : 'star-empty';
        echo '<i class="fas fa-star ' . $star_class . '"></i>';
    }
    echo '<span class="rating-value">' . number_format($rating, 1) . '/5</span>';
    echo '<meta itemprop="ratingValue" content="' . number_format($rating, 1) . '">';
    echo '<meta itemprop="bestRating" content="5">';
    echo '<meta itemprop="ratingCount" content="' . (int)$reviews_count . '">';
    echo '</div>';

    // Texte synthèse
    echo '<p>Basé sur <strong>' . (int)$reviews_count . ' avis</strong> vérifiés sur Google</p>';

    // Bouton
    echo '<button onclick="window.open(\'' . htmlspecialchars($reviews_link) . '\', \'_blank\')" class="btn" style="margin-top:12px;">Laissez votre avis</button>';

    echo '</div>';
}

echo '</div>'; // end google-reviews-grid

echo '</div>'; // end google-reviews-section
