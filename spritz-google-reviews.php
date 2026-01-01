<?php
/*
Plugin Name: Spritz Google Reviews
Plugin URI: http://www.agence-spritz.com.com/
Description: Plugin permettant la récupération du flux des avis Google
Version: 1.0
Author: Agence Spritz
Author URI: http://www.agence-spritz.com.com/
License: GPLv2
*/

if (! defined('ABSPATH')) {
    exit;
}

define('SPRITZ_GOOGLE_REVIEWS_VERSION', '1.1');
define('SPRITZ_GOOGLE_REVIEWS_PLUGIN_ABSPATH', dirname(__FILE__));
define('SPRITZ_GOOGLE_REVIEWS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPRITZ_GOOGLE_REVIEWS_IMG', plugin_dir_url(__FILE__) . '/src/img');

define('THEME_DIRECTORY_URI', get_template_directory_uri());

require_once(SPRITZ_GOOGLE_REVIEWS_PLUGIN_ABSPATH . '/shortcodes/google-shortcode.php');

/* ========================================= 
	Administration : Options générales	
========================================= */

// à l'initialisation de l'administration
// on informe WordPress des options de notre thème
add_action('admin_init', 'PluginRegisterSettings');

// Enregistrement des options
function PluginRegisterSettings()
{
    register_setting('plugin_options', 'google_api_key');
    register_setting('plugin_options', 'google_place_id');
}

// Déclaration des Scripts
function add_google_scripts()
{
    // Charger jQuery
    wp_enqueue_script("jquery");

    // Feuille de style du plugin
    wp_enqueue_style(
        'plugin_options_styles',
        plugin_dir_url(__FILE__) . 'src/css/styles.css',
        array(),
        null,
        'all'
    );

    // Charger le script JS du plugin
    wp_enqueue_script(
        'my-plugin-main-js',
        plugin_dir_url(__FILE__) . 'src/js/main-plugin.js',
        array('jquery', 'main-script'),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'add_google_scripts');

// On Crée le menu, on ajoute le lien dans la sidebar
add_action('admin_menu', 'Menu');

function Menu()
{
    add_menu_page(
        'Configuration générale', // le titre de la page
        'Spritz Google Reviews',            // le nom de la page dans le menu d'admin
        'edit_theme_options',        // le rôle d'utilisateur requis pour voir cette page
        'spritz-google-reviews-feed',        // un identifiant unique de la page
        'PluginSettingsPage'   // le nom d'une fonction qui affichera la page
    );
}

// Création HTML de la page de configuration
function PluginSettingsPage()
{
?>
    <div class="wrap">
        <h2>Spritz Google Reviews - Configuration générale</h2>
        <hr>

        <form method="post" action="options.php">
            <?php
            settings_fields('plugin_options');
            ?>

            <!-- Instagram -->
            <hr />
            <h3>Connexion au compte Google</h3>
            <hr />
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="google_api_key">Clé API Google<br /> <span style="font-size: 10px;"></span></label></th>
                    <td><input type="text" id="google_api_key" name="google_api_key" class="small-text" value="<?php echo get_option('google_api_key'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="google_place_id">Google Place ID<br /> <span style="font-size: 10px;"></span></label></th>
                    <td><input type="text" id="google_place_id" name="google_place_id" class="small-text" value="<?php echo get_option('google_place_id'); ?>" /></td>
                </tr>
                <tr>
                    <div class="alert alert-primary" role="alert">
                        <strong>Etape 1</strong> - obtenir la clé API + Google place ID : <br />
                        <br />
                        - Rendez-vous sur <a href="https://console.cloud.google.com/welcome" target="_blank">Google Cloud Console</a>.<br />
                        - Créez un nouveau projet.<br />
                        - Dans votre projet, allez dans la bibliothèque d'API et recherchez "Places API".<br />
                        - Activez l'API.<br />
                        - Allez dans la section "Identifiants" de votre projet.<br />
                        - Créez des identifiants pour obtenir une clé API. Assurez-vous de configurer les restrictions appropriées pour cette clé pour éviter les abus.<br />
                        <br />
                        - Rendez-vous sur l'outil <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank">Place ID Finder</a><br />
                        - Renseinez le nom de la société<br />
                        <br />
                        <strong>Etape 2</strong> - utiliser le shortcode [google_reviews] dans votre page
                    </div>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="Enregistrer" />
            </p>
        </form>
    </div>
<?php
}

// Récupération et affichage des avis
function GoogleReviewsCall($apiKey, $placeId)
{
    $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=$placeId&key=$apiKey&language=fr";

    // Récupération API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    if (!$response) {
        die('Erreur lors de la récupération des avis: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);

    $reviews_count = $data['result']['user_ratings_total'] ?? 0;
    $rating_global = $data['result']['rating'] ?? 0;

    // --- SECTION EN-TÊTE ---
    echo '<div class="google-reviews-section">';

    // --- GRILLE DES AVIS ---
    echo '<div class="google-reviews-grid">';

    if ($reviews_count && !empty($data['result']['reviews'])) {
        $index = 0; // compteur d’avis

        $index = 0;

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
}
