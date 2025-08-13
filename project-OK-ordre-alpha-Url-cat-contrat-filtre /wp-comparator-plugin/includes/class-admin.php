<?php

class WP_Comparator_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('wp_ajax_wp_comparator_create_comparison_page', array($this, 'ajax_create_comparison_page'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'WP Comparator',
            'WP Comparator',
            'manage_comparator',
            'wp-comparator',
            array($this, 'admin_page_overview'),
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'wp-comparator',
            'Vue d\'ensemble',
            'Vue d\'ensemble',
            'manage_comparator',
            'wp-comparator',
            array($this, 'admin_page_overview')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Types',
            'Types',
            'manage_comparator',
            'wp-comparator-types',
            array($this, 'admin_page_types')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Champs',
            'Champs',
            'manage_comparator',
            'wp-comparator-fields',
            array($this, 'admin_page_fields')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Contrats',
            'Contrats',
            'manage_comparator',
            'wp-comparator-items',
            array($this, 'admin_page_items')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Catégories de contrats',
            'Catégories de contrats',
            'manage_comparator',
            'wp-comparator-contract-categories',
            array($this, 'admin_page_contract_categories')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Paramètres',
            'Paramètres',
            'manage_comparator',
            'wp-comparator-settings',
            array($this, 'admin_page_settings')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-comparator') !== false) {
            wp_enqueue_style('wp-comparator-admin', WP_COMPARATOR_PLUGIN_URL . 'assets/css/admin.css', array(), WP_COMPARATOR_VERSION);
            wp_enqueue_script('wp-comparator-admin', WP_COMPARATOR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WP_COMPARATOR_VERSION, true);
            
            wp_localize_script('wp-comparator-admin', 'wpComparator', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_comparator_nonce')
            ));
            
            // Enqueue media uploader
            wp_enqueue_media();
        }
    }
    
    public function handle_admin_actions() {
        if (!isset($_POST['wp_comparator_action']) || !current_user_can('manage_comparator')) {
            return;
        }
        
        // Vérification de sécurité supplémentaire
        if (!wp_verify_nonce($_POST['_wpnonce'], $_POST['wp_comparator_action'])) {
            wp_die('Erreur de sécurité : Token de sécurité invalide ou expiré. Veuillez rafraîchir la page et réessayer.');
        }
        
        $action = sanitize_text_field($_POST['wp_comparator_action']);
        
        switch ($action) {
            case 'add_type':
                $this->handle_add_type();
                break;
            case 'update_type':
                $this->handle_update_type();
                break;
            case 'add_field':
                $this->handle_add_field();
                break;
            case 'update_field':
                $this->handle_update_field();
                break;
            case 'add_item':
                $this->handle_add_item();
                break;
            case 'update_item':
                $this->handle_update_item();
                break;
            case 'save_item_data':
                $this->handle_item_data_save();
                break;
            case 'add_contract_category':
                $this->handle_add_contract_category();
                break;
            case 'update_contract_category':
                $this->handle_update_contract_category();
                break;
        }
    }
    
    private function handle_add_type() {
        global $wpdb;
        $table_types = $wpdb->prefix . 'comparator_types';
        
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        $description = sanitize_textarea_field($_POST['description']);
        $url_prefix = sanitize_text_field($_POST['url_prefix']);
        $intro_text = sanitize_textarea_field($_POST['intro_text']);
        $custom_title = sanitize_textarea_field($_POST['custom_title']);
        $meta_title = sanitize_textarea_field($_POST['meta_title']);
        $meta_description = sanitize_textarea_field($_POST['meta_description']);
        
        // Vérifier que le slug est unique
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_types WHERE slug = %s",
            $slug
        ));
        
        if ($existing > 0) {
            $slug = $slug . '-' . time();
        }
        
        $result = $wpdb->insert(
            $table_types,
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'url_prefix' => $url_prefix,
                'intro_text' => $intro_text,
                'custom_title' => $custom_title,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&message=type_added'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&error=type_not_added'));
        }
        exit;
    }
    
    private function handle_update_type() {
        global $wpdb;
        $table_types = $wpdb->prefix . 'comparator_types';
        
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        $description = sanitize_textarea_field($_POST['description']);
        $url_prefix = sanitize_text_field($_POST['url_prefix']);
        $intro_text = sanitize_textarea_field($_POST['intro_text']);
        $custom_title = sanitize_textarea_field($_POST['custom_title']);
        $meta_title = sanitize_textarea_field($_POST['meta_title']);
        $meta_description = sanitize_textarea_field($_POST['meta_description']);
        
        // Vérifier que le slug est unique (sauf pour ce type)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_types WHERE slug = %s AND id != %d",
            $slug, $type_id
        ));
        
        if ($existing > 0) {
            $slug = $slug . '-' . time();
        }
        
        $result = $wpdb->update(
            $table_types,
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'url_prefix' => $url_prefix,
                'intro_text' => $intro_text,
                'custom_title' => $custom_title,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ),
            array('id' => $type_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&message=type_updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&error=type_not_updated'));
        }
        exit;
    }
    
    private function handle_add_field() {
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $type_id = intval($_POST['type_id']);
        $name = stripslashes(sanitize_text_field($_POST['name']));
        $field_type = sanitize_text_field($_POST['field_type']);
        $parent_category_id = !empty($_POST['parent_category_id']) ? intval($_POST['parent_category_id']) : null;
        $has_info_button = isset($_POST['has_info_button']) ? intval($_POST['has_info_button']) : 0;
        $info_content = sanitize_textarea_field($_POST['info_content']);
        $short_description = sanitize_textarea_field($_POST['short_description']);
        $sort_order = intval($_POST['sort_order']);
        $is_filterable = isset($_POST['is_filterable']) ? 1 : 0;
        $filter_name = sanitize_text_field($_POST['filter_name']);
        
        // Traitement des options de filtre
        $filter_options = '';
        if (isset($_POST['filter_option']) && is_array($_POST['filter_option'])) {
            $options = array_map('sanitize_text_field', $_POST['filter_option']);
            $options = array_filter($options); // Supprimer les valeurs vides
            $filter_options = implode(',', $options);
        }
        
        $result = $wpdb->insert(
            $table_fields,
            array(
                'type_id' => $type_id,
                'name' => $name,
                'field_type' => $field_type,
                'parent_category_id' => $parent_category_id,
                'has_info_button' => $has_info_button,
                'info_content' => $info_content,
                'short_description' => $short_description,
                'sort_order' => $sort_order,
                'is_filterable' => $is_filterable,
                'filter_name' => $filter_name,
                'filter_options' => $filter_options
            ),
            array('%d', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-fields&type_id=' . $type_id . '&message=field_added'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-fields&type_id=' . $type_id . '&error=field_not_added'));
        }
        exit;
    }
    
    private function handle_update_field() {
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $field_id = intval($_POST['field_id']);
        $type_id = intval($_POST['type_id']);
        $name = stripslashes(sanitize_text_field($_POST['name']));
        $field_type = sanitize_text_field($_POST['field_type']);
        $parent_category_id = !empty($_POST['parent_category_id']) ? intval($_POST['parent_category_id']) : null;
        $has_info_button = isset($_POST['has_info_button']) ? intval($_POST['has_info_button']) : 0;
        $info_content = sanitize_textarea_field($_POST['info_content']);
        $short_description = sanitize_textarea_field($_POST['short_description']);
        $sort_order = intval($_POST['sort_order']);
        $is_filterable = isset($_POST['is_filterable']) ? 1 : 0;
        $filter_name = sanitize_text_field($_POST['filter_name']);
        
        // Traitement des options de filtre
        $filter_options = '';
        if (isset($_POST['filter_option']) && is_array($_POST['filter_option'])) {
            $options = array_map('sanitize_text_field', $_POST['filter_option']);
            $options = array_filter($options); // Supprimer les valeurs vides
            $filter_options = implode(',', $options);
        }
        
        $result = $wpdb->update(
            $table_fields,
            array(
                'name' => $name,
                'field_type' => $field_type,
                'parent_category_id' => $parent_category_id,
                'has_info_button' => $has_info_button,
                'info_content' => $info_content,
                'short_description' => $short_description,
                'sort_order' => $sort_order,
                'is_filterable' => $is_filterable,
                'filter_name' => $filter_name,
                'filter_options' => $filter_options
            ),
            array('id' => $field_id),
            array('%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-fields&type_id=' . $type_id . '&message=field_updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-fields&type_id=' . $type_id . '&error=field_not_updated'));
        }
        exit;
    }
    
    private function handle_add_item() {
        global $wpdb;
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        $description = sanitize_textarea_field($_POST['description']);
        $logo_url = esc_url_raw($_POST['logo_url']);
        $contrat = sanitize_text_field($_POST['contrat']);
        $document_url = esc_url_raw($_POST['document_url']);
        $version = sanitize_text_field($_POST['version']);
        $assureur = sanitize_text_field($_POST['assureur']);
        $territorialite = sanitize_textarea_field($_POST['territorialite']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order']);
        
        // Vérifier que le slug est unique pour ce type
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_items WHERE slug = %s AND type_id = %d",
            $slug, $type_id
        ));
        
        if ($existing > 0) {
            $slug = $slug . '-' . time();
        }
        
        $result = $wpdb->insert(
            $table_items,
            array(
                'type_id' => $type_id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'logo_url' => $logo_url,
                'contrat' => $contrat,
                'document_url' => $document_url,
                'version' => $version,
                'assureur' => $assureur,
                'territorialite' => $territorialite,
                'is_active' => $is_active,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            $item_id = $wpdb->insert_id;
            
            // Gérer les catégories de contrats
            if (isset($_POST['contract_categories']) && is_array($_POST['contract_categories'])) {
                $table_item_categories = $wpdb->prefix . 'comparator_item_categories';
                
                foreach ($_POST['contract_categories'] as $category_id) {
                    $category_id = intval($category_id);
                    $wpdb->insert(
                        $table_item_categories,
                        array(
                            'item_id' => $item_id,
                            'category_id' => $category_id
                        ),
                        array('%d', '%d')
                    );
                }
            }
            
            wp_redirect(admin_url('admin.php?page=wp-comparator-items&type_id=' . $type_id . '&message=item_added'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-items&type_id=' . $type_id . '&error=item_not_added'));
        }
        exit;
    }
    
    private function handle_update_item() {
        global $wpdb;
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $item_id = intval($_POST['item_id']);
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        $description = sanitize_textarea_field($_POST['description']);
        $logo_url = esc_url_raw($_POST['logo_url']);
        $contrat = sanitize_text_field($_POST['contrat']);
        $document_url = esc_url_raw($_POST['document_url']);
        $version = sanitize_text_field($_POST['version']);
        $assureur = sanitize_text_field($_POST['assureur']);
        $territorialite = sanitize_textarea_field($_POST['territorialite']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order']);
        
        // Vérifier que le slug est unique pour ce type (sauf pour cet item)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_items WHERE slug = %s AND type_id = %d AND id != %d",
            $slug, $type_id, $item_id
        ));
        
        if ($existing > 0) {
            $slug = $slug . '-' . time();
        }
        
        $result = $wpdb->update(
            $table_items,
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'logo_url' => $logo_url,
                'contrat' => $contrat,
                'document_url' => $document_url,
                'version' => $version,
                'assureur' => $assureur,
                'territorialite' => $territorialite,
                'is_active' => $is_active,
                'sort_order' => $sort_order
            ),
            array('id' => $item_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            // Gérer les catégories de contrats
            $table_item_categories = $wpdb->prefix . 'comparator_item_categories';
            
            // Supprimer les anciennes associations
            $wpdb->delete($table_item_categories, array('item_id' => $item_id), array('%d'));
            
            // Ajouter les nouvelles associations
            if (isset($_POST['contract_categories']) && is_array($_POST['contract_categories'])) {
                foreach ($_POST['contract_categories'] as $category_id) {
                    $category_id = intval($category_id);
                    $wpdb->insert(
                        $table_item_categories,
                        array(
                            'item_id' => $item_id,
                            'category_id' => $category_id
                        ),
                        array('%d', '%d')
                    );
                }
            }
            
            wp_redirect(admin_url('admin.php?page=wp-comparator-items&type_id=' . $type_id . '&message=item_updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-items&type_id=' . $type_id . '&error=item_not_updated'));
        }
        exit;
    }
    
    private function handle_item_data_save() {
        global $wpdb;
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        $item_id = intval($_POST['item_id']);
        $type_id = intval($_POST['type_id']);
        
        // Tables nécessaires
        $table_item_filters = $wpdb->prefix . 'comparator_item_filters';
        
        // Vérifier que la table des descriptions longues existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_field_descriptions'") != $table_field_descriptions) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-items&type_id=' . $type_id . '&error=table_missing'));
            exit;
        }
        
        // Récupérer tous les champs pour ce type
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fields WHERE type_id = %d",
            $type_id
        ));
        
        foreach ($fields as $field) {
            $field_key = 'field_' . $field->id;
            $long_desc_key = 'long_description_' . $field->id;
            $filter_key = 'filter_' . $field->id;
            
            if (isset($_POST[$field_key])) {
                // MODIFICATION: Utiliser wp_kses_post() au lieu de sanitize_text_field() pour conserver le HTML
                $field_value = wp_kses_post($_POST[$field_key]);
                
                // Mettre à jour ou insérer la valeur
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_values WHERE item_id = %d AND field_id = %d",
                    $item_id, $field->id
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $table_values,
                        array('value' => $field_value),
                        array('item_id' => $item_id, 'field_id' => $field->id),
                        array('%s'),
                        array('%d', '%d')
                    );
                } else {
                    $wpdb->insert(
                        $table_values,
                        array(
                            'item_id' => $item_id,
                            'field_id' => $field->id,
                            'value' => $field_value
                        ),
                        array('%d', '%d', '%s')
                    );
                }
            }
            
            // Gérer les descriptions longues
            if (isset($_POST[$long_desc_key])) {
                // MODIFICATION: Utiliser wp_kses_post() au lieu de sanitize_text_field() pour conserver le HTML
                $long_description = wp_kses_post($_POST[$long_desc_key]);
                
                // Mettre à jour ou insérer la description longue
                $existing_desc = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                    $item_id, $field->id
                ));
                
                if ($existing_desc) {
                    $wpdb->update(
                        $table_field_descriptions,
                        array('long_description' => $long_description),
                        array('item_id' => $item_id, 'field_id' => $field->id),
                        array('%s'),
                        array('%d', '%d')
                    );
                } else {
                    $wpdb->insert(
                        $table_field_descriptions,
                        array(
                            'item_id' => $item_id,
                            'field_id' => $field->id,
                            'long_description' => $long_description
                        ),
                        array('%d', '%d', '%s')
                    );
                }
            }
            
            // Gérer les valeurs de filtres
            if (isset($_POST[$filter_key]) && $field->is_filterable) {
                $filter_value = sanitize_text_field($_POST[$filter_key]);
                
                // Mettre à jour ou insérer la valeur de filtre
                $existing_filter = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_item_filters WHERE item_id = %d AND field_id = %d",
                    $item_id, $field->id
                ));
                
                if ($existing_filter) {
                    $wpdb->update(
                        $table_item_filters,
                        array('filter_value' => $filter_value),
                        array('item_id' => $item_id, 'field_id' => $field->id),
                        array('%s'),
                        array('%d', '%d')
                    );
                } else {
                    $wpdb->insert(
                        $table_item_filters,
                        array(
                            'item_id' => $item_id,
                            'field_id' => $field->id,
                            'filter_value' => $filter_value
                        ),
                        array('%d', '%d', '%s')
                    );
                }
            }
        }
        
        wp_redirect(admin_url('admin.php?page=wp-comparator-items&type_id=' . $type_id . '&message=item_data_saved'));
        exit;
    }
    
    private function handle_add_contract_category() {
        global $wpdb;
        $table_contract_categories = $wpdb->prefix . 'comparator_contract_categories';
        
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        $description = sanitize_textarea_field($_POST['description']);
        $sort_order = intval($_POST['sort_order']);
        
        // Vérifier que le slug est unique pour ce type
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_contract_categories WHERE slug = %s AND type_id = %d",
            $slug, $type_id
        ));
        
        if ($existing > 0) {
            $slug = $slug . '-' . time();
        }
        
        $result = $wpdb->insert(
            $table_contract_categories,
            array(
                'type_id' => $type_id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-contract-categories&type_id=' . $type_id . '&message=contract_category_added'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-contract-categories&type_id=' . $type_id . '&error=contract_category_not_added'));
        }
        exit;
    }
    
    private function handle_update_contract_category() {
        global $wpdb;
        $table_contract_categories = $wpdb->prefix . 'comparator_contract_categories';
        
        $category_id = intval($_POST['category_id']);
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        $description = sanitize_textarea_field($_POST['description']);
        $sort_order = intval($_POST['sort_order']);
        
        // Vérifier que le slug est unique pour ce type (sauf pour cette catégorie)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_contract_categories WHERE slug = %s AND type_id = %d AND id != %d",
            $slug, $type_id, $category_id
        ));
        
        if ($existing > 0) {
            $slug = $slug . '-' . time();
        }
        
        $result = $wpdb->update(
            $table_contract_categories,
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sort_order
            ),
            array('id' => $category_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-contract-categories&type_id=' . $type_id . '&message=contract_category_updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-comparator-contract-categories&type_id=' . $type_id . '&error=contract_category_not_updated'));
        }
        exit;
    }
    
    public function admin_page_overview() {
        $stats = wp_comparator_get_stats();
        $types_count = $stats['types'];
        $items_count = $stats['items'];
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/overview.php';
    }
    
    public function admin_page_types() {
        global $wpdb;
        
        // Gestion des actions de debug
        if (isset($_GET['force_create_tables'])) {
            $database = new WP_Comparator_Database();
            $database->create_tables();
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&message=tables_created'));
            exit;
        }
        
        if (isset($_GET['check_tables'])) {
            $database = new WP_Comparator_Database();
            $database->verify_tables_exist();
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&message=tables_checked'));
            exit;
        }
        
        $table_types = $wpdb->prefix . 'comparator_types';
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") != $table_types) {
            wp_redirect(admin_url('admin.php?page=wp-comparator-types&error=table_not_exists'));
            exit;
        }
        
        $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY created_at DESC");
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/types.php';
    }
    
    public function admin_page_fields() {
        global $wpdb;
        
        $selected_type = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
        
        // Récupérer tous les types
        $table_types = $wpdb->prefix . 'comparator_types';
        $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY name");
        
        $fields = array();
        $categories = array();
        
        if ($selected_type) {
            // Récupérer les catégories (champs de type 'category')
            $table_fields = $wpdb->prefix . 'comparator_fields';
            $categories = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
                $selected_type
            ));
            
            // Récupérer tous les champs pour ce type
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE type_id = %d ORDER BY sort_order",
                $selected_type
            ));
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/fields-simple.php';
    }
    
    public function admin_page_items() {
        global $wpdb;
        
        $selected_type = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
        
        // Récupérer tous les types
        $table_types = $wpdb->prefix . 'comparator_types';
        $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY name");
        
        $items = array();
        
        if ($selected_type) {
            // Récupérer les éléments pour ce type
            $table_items = $wpdb->prefix . 'comparator_items';
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_items WHERE type_id = %d ORDER BY sort_order, name",
                $selected_type
            ));
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/items-simple.php';
    }
    
    public function admin_page_contract_categories() {
        global $wpdb;
        
        $selected_type = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
        
        // Récupérer tous les types
        $table_types = $wpdb->prefix . 'comparator_types';
        $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY name");
        
        $contract_categories = array();
        
        if ($selected_type) {
            // Récupérer les catégories de contrats pour ce type
            $table_contract_categories = $wpdb->prefix . 'comparator_contract_categories';
            $contract_categories = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_contract_categories WHERE type_id = %d ORDER BY sort_order, name",
                $selected_type
            ));
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/contract-categories.php';
    }
    
    public function admin_page_settings() {
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Rendu du formulaire de saisie des données d'un élément (version simplifiée)
     */
    public function render_item_data_form_simple($type_id, $item_id) {
        global $wpdb;
        
        // Récupérer l'élément
        $table_items = $wpdb->prefix . 'comparator_items';
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE id = %d",
            $item_id
        ));
        
        if (!$item) {
            echo '<div class="notice notice-error"><p>Élément non trouvé.</p></div>';
            return;
        }
        
        // Récupérer la structure des champs
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        // Récupérer les catégories avec leurs champs
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fields WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
            $type_id
        ));
        
        echo '<div class="admin-section">';
        echo '<h2>Saisir les données pour : ' . esc_html($item->name) . '</h2>';
        
        echo '<form method="post" action="">';
        wp_nonce_field('save_item_data', '_wpnonce');
        echo '<input type="hidden" name="wp_comparator_action" value="save_item_data">';
        echo '<input type="hidden" name="item_id" value="' . $item_id . '">';
        echo '<input type="hidden" name="type_id" value="' . $type_id . '">';
        
        foreach ($categories as $category) {
            echo '<div class="category-section" style="margin-bottom: 30px; background: #f9f9f9; padding: 20px; border-radius: 5px;">';
            echo '<h3 style="margin-top: 0; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">' . esc_html($category->name) . '</h3>';
            
            // Récupérer les champs de cette catégorie
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE parent_category_id = %d AND field_type = 'description' ORDER BY sort_order",
                $category->id
            ));
            
            if (!empty($fields)) {
                echo '<table class="form-table">';
                
                foreach ($fields as $field) {
                    // Récupérer la valeur existante
                    $current_value = $wpdb->get_var($wpdb->prepare(
                        "SELECT value FROM $table_values WHERE item_id = %d AND field_id = %d",
                        $item_id, $field->id
                    ));
                    
                    // Récupérer la description longue existante
                    $current_long_desc = $wpdb->get_var($wpdb->prepare(
                        "SELECT long_description FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                        $item_id, $field->id
                    ));
                    
                    echo '<tr>';
                    echo '<th scope="row">';
                    echo '<label for="field_' . $field->id . '">' . esc_html($field->name) . '</label>';
                    if ($field->short_description) {
                        echo '<br><small style="color: #666; font-weight: normal;">' . esc_html($field->short_description) . '</small>';
                    }
                    echo '</th>';
                    echo '<td>';
                    
                    // Champ principal
                    echo '<textarea id="field_' . $field->id . '" name="field_' . $field->id . '" rows="3" class="large-text" placeholder="Valeur principale...">' . esc_textarea(stripslashes($current_value ?: '')) . '</textarea>';
                    
                    // Select de filtre si le champ est filtrable
                    if ($field->is_filterable && !empty($field->filter_options)) {
                        echo '<div style="margin-top: 10px;">';
                        echo '<label for="filter_' . $field->id . '" style="font-weight: 600; color: #28a745;">Valeur du filtre :</label>';
                        echo '<select id="filter_' . $field->id . '" name="filter_' . $field->id . '" class="regular-text">';
                        echo '<option value="">-- Choisir une valeur --</option>';
                        
                        $filter_options = array_map('trim', explode(',', $field->filter_options));
                        $current_filter_value = $wpdb->get_var($wpdb->prepare(
                            "SELECT filter_value FROM {$wpdb->prefix}comparator_item_filters WHERE item_id = %d AND field_id = %d",
                            $item_id, $field->id
                        ));
                        
                        foreach ($filter_options as $option) {
                            $selected = ($current_filter_value === $option) ? 'selected' : '';
                            echo '<option value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html($option) . '</option>';
                        }
                        
                        echo '</select>';
                        echo '<p class="description">Cette valeur sera utilisée pour le filtrage sur le site</p>';
                        echo '</div>';
                    }
                    
                    // Champ description longue
                    echo '<div style="margin-top: 10px;">';
                    echo '<label for="long_description_' . $field->id . '" style="font-weight: 600; color: #0073aa;">Description longue (optionnelle) :</label>';
                    echo '<textarea id="long_description_' . $field->id . '" name="long_description_' . $field->id . '" rows="4" class="large-text" placeholder="Description détaillée qui apparaîtra dans "En savoir plus"...">' . esc_textarea(stripslashes($current_long_desc ?: '')) . '</textarea>';
                    echo '<p class="description">Cette description apparaîtra quand l\'utilisateur clique sur "En savoir plus"</p>';
                    echo '</div>';
                    
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<p style="color: #666; font-style: italic;">Aucun champ défini pour cette catégorie.</p>';
            }
            
            echo '</div>';
        }
        
        echo '<p class="submit">';
        echo '<input type="submit" class="button-primary" value="Sauvegarder les données">';
        echo '<a href="?page=wp-comparator-items&type_id=' . $type_id . '" class="button" style="margin-left: 10px;">Retour à la liste</a>';
        echo '</p>';
        
        echo '</form>';
        echo '</div>';
        
        // Ajouter des informations sur le HTML autorisé
        echo '<div class="admin-section" style="background: #e7f3ff; border-left: 4px solid #0073aa;">';
        echo '<h3>💡 HTML autorisé dans les champs</h3>';
        echo '<p>Vous pouvez utiliser les balises HTML suivantes dans vos descriptions :</p>';
        echo '<ul>';
        echo '<li><code>&lt;strong&gt;</code> et <code>&lt;b&gt;</code> pour le texte en gras</li>';
        echo '<li><code>&lt;em&gt;</code> et <code>&lt;i&gt;</code> pour le texte en italique</li>';
        echo '<li><code>&lt;ul&gt;</code> et <code>&lt;li&gt;</code> pour les listes à puces</li>';
        echo '<li><code>&lt;ol&gt;</code> et <code>&lt;li&gt;</code> pour les listes numérotées</li>';
        echo '<li><code>&lt;a href="..."&gt;</code> pour les liens</li>';
        echo '<li><code>&lt;div&gt;</code>, <code>&lt;p&gt;</code> pour la structure</li>';
        echo '<li><code>&lt;h3&gt;</code>, <code>&lt;h4&gt;</code> pour les sous-titres</li>';
        echo '</ul>';
        echo '<p><strong>Exemple :</strong> <code>&lt;strong&gt;Important :&lt;/strong&gt; &lt;ul&gt;&lt;li&gt;Point 1&lt;/li&gt;&lt;li&gt;Point 2&lt;/li&gt;&lt;/ul&gt;</code></p>';
        echo '</div>';
    }
    
    /**
     * Rendu du formulaire d'édition d'un contrat
     */
    public function render_edit_contract_form($type_id, $item_id) {
        global $wpdb;
        
        // Récupérer l'élément
        $table_items = $wpdb->prefix . 'comparator_items';
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE id = %d",
            $item_id
        ));
        
        if (!$item) {
            echo '<div class="notice notice-error"><p>Contrat non trouvé.</p></div>';
            return;
        }
        
        // Récupérer les catégories de contrats associées
        $table_item_categories = $wpdb->prefix . 'comparator_item_categories';
        $assigned_categories = $wpdb->get_col($wpdb->prepare(
            "SELECT category_id FROM $table_item_categories WHERE item_id = %d",
            $item_id
        ));
        
        // Récupérer toutes les catégories disponibles
        $table_contract_categories = $wpdb->prefix . 'comparator_contract_categories';
        $available_categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_contract_categories WHERE type_id = %d ORDER BY sort_order, name",
            $type_id
        ));
        
        echo '<div class="admin-section">';
        echo '<h2>Modifier le contrat : ' . esc_html($item->name) . '</h2>';
        
        echo '<form method="post" action="">';
        wp_nonce_field('update_item', '_wpnonce');
        echo '<input type="hidden" name="wp_comparator_action" value="update_item">';
        echo '<input type="hidden" name="item_id" value="' . $item_id . '">';
        echo '<input type="hidden" name="type_id" value="' . $type_id . '">';
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row"><label for="name">Nom du contrat</label></th>';
        echo '<td><input type="text" id="name" name="name" class="regular-text" value="' . esc_attr($item->name) . '" required></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="slug">Slug</label></th>';
        echo '<td><input type="text" id="slug" name="slug" class="regular-text" value="' . esc_attr($item->slug) . '"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="description">Description</label></th>';
        echo '<td><textarea id="description" name="description" rows="4" class="large-text">' . esc_textarea(stripslashes($item->description)) . '</textarea></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="logo_url">URL du logo</label></th>';
        echo '<td><input type="url" id="logo_url" name="logo_url" class="regular-text" value="' . esc_attr($item->logo_url) . '"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="contrat">Contrat</label></th>';
        echo '<td><input type="text" id="contrat" name="contrat" class="regular-text" value="' . esc_attr(stripslashes($item->contrat)) . '"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="document_url">URL du document</label></th>';
        echo '<td><input type="url" id="document_url" name="document_url" class="regular-text" value="' . esc_attr($item->document_url) . '"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="version">Version</label></th>';
        echo '<td><input type="text" id="version" name="version" class="regular-text" value="' . esc_attr(stripslashes($item->version)) . '"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="assureur">Assureur</label></th>';
        echo '<td><input type="text" id="assureur" name="assureur" class="regular-text" value="' . esc_attr(stripslashes($item->assureur)) . '"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="territorialite">Territorialité</label></th>';
        echo '<td><input type="text" id="territorialite" name="territorialite" class="regular-text" value="' . esc_attr(stripslashes($item->territorialite)) . '"></td>';
        echo '</tr>';
        
        // Catégories de contrats
        if (!empty($available_categories)) {
            echo '<tr>';
            echo '<th scope="row"><label>Catégories de contrat</label></th>';
            echo '<td>';
            echo '<div class="contract-categories-selection">';
            foreach ($available_categories as $category) {
                $checked = in_array($category->id, $assigned_categories) ? 'checked' : '';
                echo '<label style="display: block; margin-bottom: 8px;">';
                echo '<input type="checkbox" name="contract_categories[]" value="' . $category->id . '" ' . $checked . '>';
                echo '<span class="category-badge-preview">' . esc_html($category->name) . '</span>';
                if ($category->description) {
                    echo '<small style="color: #666; display: block; margin-left: 20px;">' . esc_html($category->description) . '</small>';
                }
                echo '</label>';
            }
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '<tr>';
        echo '<th scope="row">Statut</th>';
        echo '<td>';
        echo '<label>';
        echo '<input type="checkbox" name="is_active" value="1" ' . ($item->is_active ? 'checked' : '') . '>';
        echo 'Contrat actif (visible sur le site)';
        echo '</label>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="sort_order">Ordre d\'affichage</label></th>';
        echo '<td><input type="number" id="sort_order" name="sort_order" value="' . $item->sort_order . '" min="0" class="small-text"></td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '<p class="submit">';
        echo '<input type="submit" class="button-primary" value="Modifier le contrat">';
        echo '<a href="?page=wp-comparator-items&type_id=' . $type_id . '" class="button" style="margin-left: 10px;">Annuler</a>';
        echo '</p>';
        
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Créer une page de comparaison via AJAX
     */
    public function ajax_create_comparison_page() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_comparator_frontend_nonce')) {
            wp_send_json_error('Erreur de sécurité');
        }
        
        $type_slug = sanitize_text_field($_POST['type_slug']);
        $item1_slug = sanitize_text_field($_POST['item1_slug']);
        $item2_slug = sanitize_text_field($_POST['item2_slug']);
        
        // Créer une nouvelle page WordPress
        $pages_class = new WP_Comparator_Pages();
        $result = $pages_class->create_wordpress_page($type_slug, $item1_slug, $item2_slug);
        
        if ($result && isset($result['page_id'])) {
            $page_url = get_permalink($result['page_id']);
            wp_send_json_success(array(
                'url' => $page_url,
                'page_id' => $result['page_id']
            ));
        } else {
            wp_send_json_error('Erreur lors de la création de la page');
        }
    }
}