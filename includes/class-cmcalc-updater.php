<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plugin auto-updater via GitHub Releases (supports private repos)
 *
 * HOW TO RELEASE A NEW VERSION:
 * 1. Bump version in cleanmasterzz-calculator.php (both header and CMCALC_VERSION)
 * 2. Commit & push to GitHub
 * 3. Create a new GitHub Release with tag matching the version (e.g. "1.0.2")
 * 4. Attach a ZIP file named "cleanmasterzz-calculator.zip" as release asset
 * 5. WordPress sites will auto-detect the update within 6 hours (or on manual check)
 *
 * SETUP:
 * - Token is stored in wp_options as 'cmcalc_github_token'
 * - Set via: CleanMasterzz → Instellingen → GitHub Token
 * - Or via WP-CLI: wp option update cmcalc_github_token "ghp_yourtoken"
 */
class CMCalc_Updater {

    private static $plugin_file;
    private static $plugin_slug = 'cleanmasterzz-calculator';
    private static $github_owner = 'Damjann-dev';
    private static $github_repo = 'Cleanmasterzz';
    private static $cache_key = 'cmcalc_update_check';
    private static $cache_ttl = 21600; // 6 hours

    /**
     * Initialize the updater
     */
    public static function init( $plugin_file ) {
        self::$plugin_file = $plugin_file;

        add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
        add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_post_install', array( __CLASS__, 'post_install' ), 10, 3 );

        // Allow download from GitHub (private repo needs auth header)
        add_filter( 'http_request_args', array( __CLASS__, 'inject_download_auth' ), 10, 2 );

        // Clear cache when admin manually clicks "Check Again"
        add_action( 'admin_init', function() {
            if ( isset( $_GET['force-check'] ) && $_GET['force-check'] === '1' ) {
                delete_transient( self::$cache_key );
            }
        } );
    }

    /**
     * Get the GitHub token from wp_options
     */
    private static function get_token() {
        return get_option( 'cmcalc_github_token', '' );
    }

    /**
     * Build auth headers for GitHub API
     */
    private static function get_github_headers() {
        $headers = array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'CleanmasterzzCalculator/' . CMCALC_VERSION,
        );

        $token = self::get_token();
        if ( $token ) {
            $headers['Authorization'] = 'token ' . $token;
        }

        return $headers;
    }

    /**
     * Inject auth header when WordPress downloads the ZIP from GitHub
     */
    public static function inject_download_auth( $args, $url ) {
        // Only inject for GitHub downloads from our repo
        if ( strpos( $url, 'github.com/' . self::$github_owner . '/' . self::$github_repo ) === false
            && strpos( $url, 'api.github.com/repos/' . self::$github_owner . '/' . self::$github_repo ) === false ) {
            return $args;
        }

        $token = self::get_token();
        if ( $token ) {
            $args['headers']['Authorization'] = 'token ' . $token;
            $args['headers']['Accept'] = 'application/octet-stream';
        }

        return $args;
    }

    /**
     * Check GitHub for a new release
     */
    public static function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = self::get_remote_info();
        if ( ! $remote ) {
            return $transient;
        }

        $plugin_basename = plugin_basename( self::$plugin_file );

        if ( version_compare( CMCALC_VERSION, $remote['version'], '<' ) ) {
            $transient->response[ $plugin_basename ] = (object) array(
                'slug'        => self::$plugin_slug,
                'plugin'      => $plugin_basename,
                'new_version' => $remote['version'],
                'url'         => $remote['url'],
                'package'     => $remote['download_url'],
                'icons'       => array(),
                'banners'     => array(),
                'tested'      => '',
                'requires'    => '6.0',
                'requires_php'=> '7.4',
            );
        } else {
            $transient->no_update[ $plugin_basename ] = (object) array(
                'slug'        => self::$plugin_slug,
                'plugin'      => $plugin_basename,
                'new_version' => CMCALC_VERSION,
                'url'         => 'https://github.com/' . self::$github_owner . '/' . self::$github_repo,
            );
        }

        return $transient;
    }

    /**
     * Provide plugin info for the "View Details" popup
     */
    public static function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || ! isset( $args->slug ) || $args->slug !== self::$plugin_slug ) {
            return $result;
        }

        $remote = self::get_remote_info();
        if ( ! $remote ) {
            return $result;
        }

        return (object) array(
            'name'            => 'Cleanmasterzz Calculator',
            'slug'            => self::$plugin_slug,
            'version'         => $remote['version'],
            'author'          => '<a href="https://cleanmasterzz.nl">CleanMasterzz</a>',
            'homepage'        => 'https://cleanmasterzz.nl',
            'requires'        => '6.0',
            'requires_php'    => '7.4',
            'downloaded'      => 0,
            'last_updated'    => $remote['published_at'] ?? '',
            'sections'        => array(
                'description' => 'Prijscalculator met multi-dienst selectie, werkgebieden, sub-opties en boekingen.',
                'changelog'   => nl2br( esc_html( $remote['changelog'] ?? 'Geen changelog beschikbaar.' ) ),
            ),
            'download_link'   => $remote['download_url'],
        );
    }

    /**
     * After install: rename the extracted folder to the correct plugin slug
     */
    public static function post_install( $response, $hook_extra, $result ) {
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== plugin_basename( self::$plugin_file ) ) {
            return $result;
        }

        global $wp_filesystem;

        $proper_destination = WP_PLUGIN_DIR . '/' . self::$plugin_slug;
        $installed_dir = $result['destination'];

        if ( $installed_dir !== $proper_destination && $installed_dir !== $proper_destination . '/' ) {
            // Remove existing destination if it exists
            if ( $wp_filesystem->exists( $proper_destination ) ) {
                $wp_filesystem->delete( $proper_destination, true );
            }
            $wp_filesystem->move( $installed_dir, $proper_destination );
            $result['destination'] = $proper_destination;
            $result['destination_name'] = self::$plugin_slug;
        }

        // Re-activate the plugin
        activate_plugin( plugin_basename( self::$plugin_file ) );

        return $result;
    }

    /**
     * Fetch release info from GitHub (cached)
     */
    private static function get_remote_info() {
        $cached = get_transient( self::$cache_key );
        if ( $cached !== false ) {
            return $cached;
        }

        $info = self::fetch_github_release();

        if ( ! $info ) {
            $info = self::fetch_custom_server();
        }

        if ( $info ) {
            set_transient( self::$cache_key, $info, self::$cache_ttl );
        }

        return $info;
    }

    /**
     * Fetch latest release from GitHub API
     * Uses /releases (all) instead of /releases/latest to handle prereleases correctly
     */
    private static function fetch_github_release() {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases',
            self::$github_owner,
            self::$github_repo
        );

        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => self::get_github_headers(),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        $releases = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $releases ) || ! is_array( $releases ) ) {
            return null;
        }

        // Find the release with the highest version number
        $best = null;
        $best_version = '0.0.0';

        foreach ( $releases as $release ) {
            if ( empty( $release['tag_name'] ) || ! empty( $release['draft'] ) ) {
                continue;
            }

            $version = ltrim( $release['tag_name'], 'vV' );

            if ( version_compare( $version, $best_version, '>' ) ) {
                $best_version = $version;
                $best = $release;
            }
        }

        if ( ! $best ) {
            return null;
        }

        $version = ltrim( $best['tag_name'], 'vV' );

        // Find the ZIP asset — prefer uploaded assets, then use API download for private repos
        $download_url = '';
        if ( ! empty( $best['assets'] ) ) {
            foreach ( $best['assets'] as $asset ) {
                if ( strpos( $asset['name'], '.zip' ) !== false ) {
                    // For private repos: use the API URL (not browser_download_url)
                    // This allows the auth header injection to work
                    $token = self::get_token();
                    if ( $token ) {
                        $download_url = $asset['url']; // API URL, needs Accept: application/octet-stream
                    } else {
                        $download_url = $asset['browser_download_url'];
                    }
                    break;
                }
            }
        }

        // Fallback to GitHub's auto-generated source ZIP
        if ( ! $download_url ) {
            $download_url = $best['zipball_url'] ?? '';
        }

        if ( ! $download_url ) {
            return null;
        }

        return array(
            'version'      => $version,
            'download_url' => $download_url,
            'url'          => $best['html_url'] ?? '',
            'changelog'    => $best['body'] ?? '',
            'published_at' => $best['published_at'] ?? '',
        );
    }

    /**
     * Fallback: fetch update info from custom server
     */
    private static function fetch_custom_server() {
        $url = 'https://cleanmasterzz.nl/update-check.json';

        $response = wp_remote_get( $url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'CleanmasterzzCalculator/' . CMCALC_VERSION,
            ),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data ) || ! isset( $data['version'] ) || ! isset( $data['download_url'] ) ) {
            return null;
        }

        return array(
            'version'      => $data['version'],
            'download_url' => $data['download_url'],
            'url'          => $data['url'] ?? 'https://cleanmasterzz.nl',
            'changelog'    => $data['changelog'] ?? '',
            'published_at' => $data['published_at'] ?? '',
        );
    }
}
