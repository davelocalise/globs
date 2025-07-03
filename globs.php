<?php
/**
 * Plugin Name: Globs
 * Plugin URI:  https://https://da.vebrig.gs/plugins
 * Description: Globs are repeatable bits of content that you edit once and place anywhere on your site, as many times as you like.
 * Version:     2.0
 * Author:      Dave Briggs
 * Author URI:  https://da.vebrig.gs/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: globs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit accessed directly.
}

/**
 * Class Globs_Plugin
 * Handles registration of custom post type and shortcode for Globs.
 */
class Globs_Plugin {

    /**
     * Constructor.
     * Registers hooks for initializing the plugin.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_glob_post_type' ) );
        add_action( 'init', array( $this, 'register_glob_shortcode' ) );
        // Removed the admin notice hook as requested.
        // add_action( 'admin_notices', array( $this, 'glob_admin_notice' ) );

        // Add filters for custom columns in the 'All Globs' screen
        add_filter( 'manage_glob_posts_columns', array( $this, 'add_glob_shortcode_column' ) );
        add_action( 'manage_glob_posts_custom_column', array( $this, 'display_glob_shortcode_column' ), 10, 2 );
    }

    /**
     * Registers the 'Glob' custom post type.
     */
    public function register_glob_post_type() {
        $labels = array(
            'name'                  => _x( 'Globs', 'Post Type General Name', 'globs' ),
            'singular_name'         => _x( 'Glob', 'Post Type Singular Name', 'globs' ),
            'menu_name'             => __( 'Globs', 'globs' ),
            'name_admin_bar'        => __( 'Glob', 'globs' ),
            'archives'              => __( 'Glob Archives', 'globs' ),
            'attributes'            => __( 'Glob Attributes', 'globs' ),
            'parent_item_colon'     => __( 'Parent Glob:', 'globs' ),
            'all_items'             => __( 'All Globs', 'globs' ),
            'add_new_item'          => __( 'Add New Glob', 'globs' ),
            'add_new'               => __( 'Add New', 'globs' ),
            'new_item'              => __( 'New Glob', 'globs' ),
            'edit_item'             => __( 'Edit Glob', 'globs' ),
            'update_item'           => __( 'Update Glob', 'globs' ),
            'view_item'             => __( 'View Glob', 'globs' ),
            'view_items'            => __( 'View Globs', 'globs' ),
            'search_items'          => __( 'Search Globs', 'globs' ),
            'not_found'             => __( 'No Globs found', 'globs' ),
            'not_found_in_trash'    => __( 'No Globs found in Trash', 'globs' ),
            'insert_into_item'      => __( 'Insert into Glob', 'globs' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Glob', 'globs' ),
            'filter_items_list'     => __( 'Filter Globs list', 'globs' ),
            'items_list_navigation' => __( 'Globs list navigation', 'globs' ),
            'items_list'            => __( 'Globs list', 'globs' ),
        );
        $args = array(
            'label'                 => __( 'Glob', 'globs' ),
            'description'           => __( 'Reusable content chunks for your site.', 'globs' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor' ), // Globs will have a title and content editor.
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20, // Position in the admin menu.
            'menu_icon'             => 'dashicons-pets', // Icon for the menu item.
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false, // We don't need an archive page for Globs.
            'exclude_from_search'   => true, // Globs should not appear in site search results.
            'publicly_queryable'    => true, // Allows access by ID/slug, but not via an archive.
            'capability_type'       => 'post',
            'show_in_rest'          => true, // Enable Gutenberg editor and REST API access.
        );
        register_post_type( 'glob', $args );
    }

    /**
     * Registers the shortcode for embedding Globs.
     * Usage: [glob id="123"] or [glob slug="my-glob-slug"]
     */
    public function register_glob_shortcode() {
        add_shortcode( 'glob', array( $this, 'glob_shortcode_callback' ) );
    }

    /**
     * Shortcode callback function.
     * Fetches and returns the content of a specified Glob.
     *
     * @param array $atts Shortcode attributes.
     * @return string The content of the Glob, or an empty string if not found.
     */
    public function glob_shortcode_callback( $atts ) {
        $atts = shortcode_atts(
            array(
                'id'   => 0,
                'slug' => '',
            ),
            $atts,
            'glob'
        );

        $glob_content = '';
        $glob_post    = null;

        if ( ! empty( $atts['id'] ) ) {
            // Try to get glob by ID.
            $glob_post = get_post( (int) $atts['id'] );
        } elseif ( ! empty( $atts['slug'] ) ) {
            // Try to get glob by slug.
            $glob_post = get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, 'glob' );
        }

        // Check if the post exists and is of the 'glob' post type.
        if ( $glob_post && 'glob' === $glob_post->post_type && 'publish' === $glob_post->post_status ) {
            // Apply content filters to ensure shortcodes and other formatting work within the glob.
            $glob_content = apply_filters( 'the_content', $glob_post->post_content );
        }

        return $glob_content;
    }

    /**
     * Adds the 'Shortcode' column to the 'All Globs' screen.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns array.
     */
    public function add_glob_shortcode_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( 'title' === $key ) {
                $new_columns['glob_shortcode'] = __( 'Shortcode', 'globs' );
            }
        }
        return $new_columns;
    }

    /**
     * Displays the content for the 'Shortcode' column.
     *
     * @param string $column_name The name of the column being displayed.
     * @param int    $post_id     The ID of the current post.
     */
    public function display_glob_shortcode_column( $column_name, $post_id ) {
        if ( 'glob_shortcode' === $column_name ) {
            $glob_post = get_post( $post_id );
            if ( $glob_post ) {
                $glob_slug = $glob_post->post_name; // Get the post slug.
                $shortcode_id = '[glob id="' . esc_attr( $post_id ) . '"]';
                $shortcode_slug = '[glob slug="' . esc_attr( $glob_slug ) . '"]';

                // JavaScript to copy text and provide visual feedback.
                // Using a more robust copy method and visual feedback instead of alert().
                $js_copy_script = "
                    function copyGlobShortcode(element, textToCopy) {
                        navigator.clipboard.writeText(textToCopy).then(function() {
                            var originalText = element.innerHTML;
                            element.innerHTML = 'Copied!';
                            setTimeout(function() {
                                element.innerHTML = originalText;
                            }, 1500); // Revert text after 1.5 seconds
                        }).catch(function(err) {
                            console.error('Could not copy text: ', err);
                            // Fallback for older browsers or restricted environments
                            var tempInput = document.createElement('textarea');
                            tempInput.value = textToCopy;
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);

                            var originalText = element.innerHTML;
                            element.innerHTML = 'Copied!';
                            setTimeout(function() {
                                element.innerHTML = originalText;
                            }, 1500);
                        });
                    }
                ";

                // Ensure the script is only added once.
                static $script_added = false;
                if ( ! $script_added ) {
                    echo '<script type="text/javascript">' . $js_copy_script . '</script>';
                    $script_added = true;
                }

                echo '<code style="display: block; margin-bottom: 5px; background-color: #f0f0f0; padding: 5px; border-radius: 3px; cursor: copy;" onclick="copyGlobShortcode(this, \'' . esc_js( $shortcode_id ) . '\');">' . esc_html( $shortcode_id ) . '</code>';
                echo '<code style="display: block; background-color: #f0f0f0; padding: 5px; border-radius: 3px; cursor: copy;" onclick="copyGlobShortcode(this, \'' . esc_js( $shortcode_slug ) . '\');">' . esc_html( $shortcode_slug ) . '</code>';
                echo '<p style="font-size: 0.8em; color: #666; margin-top: 5px;">Click to copy</p>';
            }
        }
    }

    /**
     * Displays an admin notice with instructions on how to use the plugin.
     * This method is now empty as the "Globs Plugin Activated!" message has been removed.
     */
    public function glob_admin_notice() {
        // The admin notice has been removed as requested.
    }
}

// Initialize the plugin.
new Globs_Plugin();
