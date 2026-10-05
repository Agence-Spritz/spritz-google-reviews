<?php
/*
Plugin Name: Spritz Google Reviews
Plugin URI: https://github.com/Agence-Spritz/spritz-google-reviews
Description: Plugin permettant la récupération du flux des avis Google
Version: 1.2.0
Author: Agence Spritz
Author URI: https://www.agence-spritz.com/
License: GPLv2
*/

if (! defined('ABSPATH')) {
    exit;
}

// Mises à jour automatiques depuis les releases GitHub (asset spritz-google-reviews.zip).
// Désactivé sur une copie de travail git (dev local, plugin en symlink) : une mise à jour
// lancée depuis l'admin écraserait les sources du dépôt.
if (!is_dir(__DIR__ . '/.git')) {
	require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';
	\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/Agence-Spritz/spritz-google-reviews/',
		__FILE__,
		'spritz-google-reviews'
	)->getVcsApi()->enableReleaseAssets('/spritz\-google\-reviews\.zip$/');
}

define('SPRITZ_GOOGLE_REVIEWS_VERSION', '1.2.0');
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
    register_setting('plugin_options', 'google_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('plugin_options', 'google_place_id', ['sanitize_callback' => 'sanitize_text_field']);
}

// Déclaration des Scripts
function add_google_scripts()
{
    // Charger jQuery
    wp_enqueue_script("jquery");

    // Feuille de style du plugin (Désactivée car gérée par Webpack dans le thème)
    /*
    wp_enqueue_style(
        'plugin_options_styles',
        plugin_dir_url(__FILE__) . 'src/css/styles.css',
        array(),
        null,
        'all'
    );
    */

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
                    <td><input type="text" id="google_api_key" name="google_api_key" class="small-text" value="<?php echo esc_attr(get_option('google_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="google_place_id">Google Place ID<br /> <span style="font-size: 10px;"></span></label></th>
                    <td><input type="text" id="google_place_id" name="google_place_id" class="small-text" value="<?php echo esc_attr(get_option('google_place_id')); ?>" /></td>
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
    if (empty($apiKey) || empty($placeId)) {
        return;
    }

    // Réponse mise en cache : évite un appel API (facturé) à chaque affichage
    $cache_key = 'spritz_google_reviews_' . md5($placeId);
    $data = get_transient($cache_key);

    if (false === $data) {
        $url = add_query_arg([
            'placeid'  => rawurlencode($placeId),
            'key'      => rawurlencode($apiKey),
            'language' => 'fr',
        ], 'https://maps.googleapis.com/maps/api/place/details/json');

        $response = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            error_log('Spritz Google Reviews : échec de la récupération des avis.');
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'OK') {
            error_log('Spritz Google Reviews : réponse API invalide (' . ($data['status'] ?? 'inconnue') . ').');
            return;
        }

        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
    }

    $reviews_count = $data['result']['user_ratings_total'] ?? 0;
    $rating_global = $data['result']['rating'] ?? 0;

    // Appel du template avec les données
    spritz_get_template('spritz-google-reviews', 'reviews-list.php', [
        'data'          => $data,
        'reviews_count' => $reviews_count,
        'rating_global' => $rating_global
    ]);
}
