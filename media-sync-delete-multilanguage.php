<?php
/*
Plugin Name: Media Sync Delete Multilanguage for WPML
Plugin URI: https://github.com/Reliefcreation/WordPress-plugin-media-sync-delete-multilanguage
Description: Synchronizes media deletion across WPML translations to maintain consistency across languages
Version: 1.0.1
Author: RELIEF Creation
Author URI: https://reliefcreation.com/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 7.4
Text Domain: media-sync-delete-multilanguage
*/

if (!defined('ABSPATH')) {
    exit;
}

class Media_Sync_Delete_Multilanguage {
    private static $instance = null;
    private $error_log = array();
    private $plugin_version = '1.0.1';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'check_dependencies'));
        add_action('delete_attachment', array($this, 'sync_delete_attachment'), 10, 1);
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Ajouter un menu pour voir les logs
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Vérifier la version et effectuer des mises à jour si nécessaire
        $this->check_version();
    }

    private function check_version() {
        $installed_version = get_option('media_sync_delete_version', '1.0.0');
        if (version_compare($installed_version, $this->plugin_version, '<')) {
            // Mettre à jour la version
            update_option('media_sync_delete_version', $this->plugin_version);
        }
    }

    private $notices = array();

    public function check_dependencies() {
        if (!class_exists('SitePress')) {
            $this->notices[] = array(
                'type' => 'error',
                'message' => __('Media Sync Delete Multilanguage requires WPML to be installed and activated.', 'media-sync-delete-multilanguage')
            );
        }
    }

    public function display_admin_notices() {
        foreach ($this->notices as $notice) {
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
        }
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('Media Sync Logs', 'media-sync-delete-multilanguage'),
            __('Media Sync Logs', 'media-sync-delete-multilanguage'),
            'manage_options',
            'media-sync-logs',
            array($this, 'render_logs_page')
        );
    }

    public function render_logs_page() {
        $logs = $this->get_error_logs();
        ?>
        <div class="wrap">
            <h1><?php _e('Media Sync Deletion Logs', 'media-sync-delete-multilanguage'); ?></h1>
            <p class="description">
                <?php printf(
                    __('Plugin Version: %s', 'media-sync-delete-multilanguage'),
                    esc_html($this->plugin_version)
                ); ?>
            </p>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'media-sync-delete-multilanguage'); ?></th>
                        <th><?php _e('Message', 'media-sync-delete-multilanguage'); ?></th>
                        <th><?php _e('Status', 'media-sync-delete-multilanguage'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="3"><?php _e('No logs available.', 'media-sync-delete-multilanguage'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['date']); ?></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                            <td><?php echo esc_html($log['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function sync_delete_attachment($post_id) {
        // Vérifier si WPML est actif
        if (!class_exists('SitePress')) {
            return;
        }

        global $sitepress;
        
        // Obtenir le type MIME du média
        $mime_type = get_post_mime_type($post_id);
        if (!$mime_type) {
            $this->log_error($post_id, 'Invalid media type');
            return;
        }

        // Obtenir la langue originale du média
        $original_language = $sitepress->get_language_for_element($post_id, 'post_attachment');
        
        // Obtenir toutes les traductions du média
        $trid = $sitepress->get_element_trid($post_id, 'post_attachment');
        $translations = $sitepress->get_element_translations($trid, 'post_attachment', true, true);

        $deletion_errors = array();

        // Supprimer les traductions
        foreach ($translations as $translation) {
            // Éviter de supprimer le média en cours de suppression
            if ($translation->element_id != $post_id) {
                try {
                    // Désactiver temporairement le hook pour éviter la récursion
                    remove_action('delete_attachment', array($this, 'sync_delete_attachment'));
                    
                    // Supprimer la traduction
                    $result = wp_delete_attachment($translation->element_id, true);
                    
                    // Réactiver le hook
                    add_action('delete_attachment', array($this, 'sync_delete_attachment'));

                    if (!$result) {
                        throw new Exception("Failed to delete translation {$translation->element_id}");
                    }

                    // Journaliser le succès
                    $this->log_success($translation->element_id, $post_id);
                } catch (Exception $e) {
                    $deletion_errors[] = $e->getMessage();
                    $this->log_error($translation->element_id, $e->getMessage());
                }
            }
        }

        // Notifier l'admin en cas d'erreurs
        if (!empty($deletion_errors)) {
            $this->notify_admin_of_errors($post_id, $deletion_errors);
        }
    }

    private function notify_admin_of_errors($post_id, $errors) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(
            __('[%s] Media Sync Deletion Errors', 'media-sync-delete-multilanguage'),
            get_bloginfo('name')
        );
        
        $message = sprintf(
            __("Errors occurred while deleting translations for media ID: %d\n\nErrors:\n%s", 'media-sync-delete-multilanguage'),
            $post_id,
            implode("\n", $errors)
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    private function log_success($translation_id, $original_id) {
        $log_entry = array(
            'date' => current_time('mysql'),
            'message' => sprintf(
                'Successfully deleted translation %d (original media: %d)',
                $translation_id,
                $original_id
            ),
            'status' => 'success'
        );
        
        $this->save_log_entry($log_entry);
    }

    private function log_error($media_id, $error_message) {
        $log_entry = array(
            'date' => current_time('mysql'),
            'message' => sprintf(
                'Error with media ID %d: %s',
                $media_id,
                $error_message
            ),
            'status' => 'error'
        );
        
        $this->save_log_entry($log_entry);
    }

    private function save_log_entry($entry) {
        $logs = get_option('media_sync_delete_logs', array());
        array_unshift($logs, $entry);
        
        // Garder seulement les 100 dernières entrées
        $logs = array_slice($logs, 0, 100);
        
        update_option('media_sync_delete_logs', $logs);
    }

    private function get_error_logs() {
        return get_option('media_sync_delete_logs', array());
    }
}

// Initialisation du plugin
function media_sync_delete_multilanguage_init() {
    Media_Sync_Delete_Multilanguage::get_instance();
}
add_action('plugins_loaded', 'media_sync_delete_multilanguage_init');