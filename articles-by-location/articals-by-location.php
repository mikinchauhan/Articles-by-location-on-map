<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://#
 * @since             1.0.0
 * @package           Articals0_By_Location
 *
 * @wordpress-plugin
 * Plugin Name:       Articles by location
 * Plugin URI:        https://#
 * Description:       Display articles on maps by the locations.
 * Version:           1.0.0
 * Author:            Mikin Chauhan
 * Author URI:        https://#
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       articals-by-location
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ARTICALS_BY_LOCATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-articals-by-location-activator.php
 */
function activate_articals_by_location() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-articals-by-location-activator.php';
	Articals_By_Location_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-articals-by-location-deactivator.php
 */
function deactivate_articals_by_location() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-articals-by-location-deactivator.php';
	Articals_By_Location_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_articals_by_location' );
register_deactivation_hook( __FILE__, 'deactivate_articals_by_location' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-articals-by-location.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_articals_by_location() {

	$plugin = new Articals_By_Location();
	$plugin->run();

}
run_articals_by_location();


/**
 * Register meta boxes.
 */
function articals_by_location_register_meta_boxes() {
    add_meta_box( 'lat_long', __( 'Latitude & Longitude', 'articals-by-location' ), 'articals_by_location_display_callback', 'post' );
}
add_action( 'add_meta_boxes', 'articals_by_location_register_meta_boxes' );

/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function articals_by_location_display_callback( $post ) {
    ?>
    <p>
        <label>Latitude</label>
        <input type="text" name="latitude" value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'latitude', true ) ); ?>">
    </p>
    <p>
        <label>Longitude</label>
        <input type="text" name="longitude" value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'longitude', true ) ); ?>">
    </p>
    <?php
}

/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */

function articals_by_location_save_meta_box( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( $parent_id = wp_is_post_revision( $post_id ) ) {
        $post_id = $parent_id;
    }
    $fields = [
        'latitude',
        'longitude'
    ];
    foreach ( $fields as $field ) {
        if ( array_key_exists( $field, $_POST ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
        }
     }
}
add_action( 'save_post', 'articals_by_location_save_meta_box' );


function articals_by_location_shortcode() {
    // Initialize variable.
    $allposts = [];
    
    // Enter the name of your blog here followed by /wp-json/wp/v2/posts and add filters like this one that limits the result to 2 posts.
    $response = wp_remote_get( site_url().'/wp-json/wp/v2/posts' );

    // Exit if error.
    if ( is_wp_error( $response ) ) {
        return;
    }

    // Get the body.
    $posts = json_decode( wp_remote_retrieve_body( $response ) );

    // Exit if nothing is returned.
    if ( empty( $posts ) ) {
        return;
    }

    // If there are posts.
    if ( ! empty( $posts ) ) {

        // For each post.
        foreach ( $posts as $post ) {

            // Use print_r($post); to get the details of the post and all available fields
            // Format the date.
            $fordate = date( 'n/j/Y', strtotime( $post->modified ) );

            // Show a linked title and post date.
            $allposts[] = [
                'title' => $post->title->rendered, 
                'link' => $post->link,
                'latitude' => get_post_meta($post->id, 'latitude', true ),
                'longitude' => get_post_meta($post->id, 'longitude', true ),
            ];
        }


    }
    ?>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    
    <script>
        const map = L.map('map').setView([<?php echo $allposts[0]['latitude']; ?>, <?php echo $allposts[0]['longitude']; ?>], 5);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const LeafIcon = L.Icon.extend({
            options: {
                shadowUrl: 'leaf-shadow.png',
                iconSize:     [38, 95],
                shadowSize:   [50, 64],
                iconAnchor:   [22, 94],
                shadowAnchor: [4, 62],
                popupAnchor:  [-3, -76]
            }
        });

        const greenIcon = new LeafIcon({iconUrl: 'https://leafletjs.com/examples/custom-icons/leaf-green.png'});
        
        <?php  foreach ($allposts as $key => $value) { ?>
            const mGreen<?php echo $key; ?> = L.marker(["<?php echo $value['latitude']; ?>", "<?php echo $value['longitude']; ?>"], {icon: greenIcon}).bindPopup('<a href="<?php echo $value['link']; ?>" target="_blank"><?php echo $value['title']; ?>').addTo(map);
        <?php } ?>
        
    </script>
    <?php

}
add_action('wp_footer', 'articals_by_location_shortcode');

function abl_map(){
    $output = "<style>
        html, body {
            height: 100%;
            margin: 0;
        }
        .leaflet-container {
            height: 100vh;
            width: 100%;
            max-width: 100%;
            max-height: 100%;
        }
    </style>
    <div id='map'></div>";
    return $output;
}

add_shortcode('abl', 'abl_map');