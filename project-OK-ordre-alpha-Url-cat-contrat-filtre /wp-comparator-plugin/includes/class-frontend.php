<?php

class WP_Comparator_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('wp_comparator', array($this, 'shortcode_comparator_grid'));
        add_shortcode('wp_comparator_compare', array($this, 'shortcode_comparator_compare'));
        add_shortcode('wp_comparator_single', array($this, 'shortcode_comparator_single'));
        
        // Gérer les paramètres de comparaison dans l'URL
        add_action('template_redirect', array($this, 'handle_comparison_redirect'));
    }
    
    /**
     * Gérer la redirection vers les pages de comparaison
     */
    public function handle_comparison_redirect() {
        if (isset($_GET['compare']) && isset($_GET['type'])) {
            $compare_items = sanitize_text_field($_GET['compare']);
            $type_slug = sanitize_text_field($_GET['type']);
            
            // Debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WP Comparator - Redirection demandée: type=$type_slug, items=$compare_items");
            }
            
            $item_slugs = explode(',', $compare_items);
            if (count($item_slugs) === 2) {
                $item1_slug = trim($item_slugs[0]);
                $item2_slug = trim($item_slugs[1]);
                
                // Nettoyer les slugs
                $item1_slug = sanitize_title($item1_slug);
                $item2_slug = sanitize_title($item2_slug);
                
                // SOLUTION ANTI-DUPLICATE : Toujours rediriger vers l'ordre alphabétique
                $sorted_slugs = [$item1_slug, $item2_slug];
                sort($sorted_slugs);
                $canonical_item1_slug = $sorted_slugs[0];
                $canonical_item2_slug = $sorted_slugs[1];
                
                // Créer ou récupérer la page de comparaison
                $pages_class = new WP_Comparator_Pages();
                $result = $pages_class->create_wordpress_page($type_slug, $canonical_item1_slug, $canonical_item2_slug);
                
                if ($result && isset($result['page_id'])) {
                    $page_url = get_permalink($result['page_id']);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("WP Comparator - Redirection vers: $page_url");
                    }
                    wp_redirect($page_url, 301);
                    exit;
                }
            }
        }
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('wp-comparator-frontend', WP_COMPARATOR_PLUGIN_URL . 'assets/css/frontend.css', array(), WP_COMPARATOR_VERSION);
        wp_enqueue_script('wp-comparator-frontend', WP_COMPARATOR_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WP_COMPARATOR_VERSION, true);
        
        wp_localize_script('wp-comparator-frontend', 'wpComparatorFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_comparator_frontend_nonce'),
            'homeUrl' => home_url('/'),
            'currentTypeSlug' => isset($atts['type']) ? $atts['type'] : ''
        ));
    }
    
    /**
     * Shortcode pour afficher la grille de sélection avec vignettes
     * Usage: [wp_comparator type="assurance-prevoyance"]
     */
    public function shortcode_comparator_grid($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'show_filters' => 'true',
            'columns' => '3',
            'category' => ''
        ), $atts);
        
        if (empty($atts['type'])) {
            return '<p>Erreur: Le paramètre "type" est requis.</p>';
        }
        
        global $wpdb;
        
        // Récupérer le type
        $table_types = $wpdb->prefix . 'comparator_types';
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $atts['type']
        ));
        
        if (!$type) {
            return '<p>Erreur: Type de comparateur non trouvé.</p>';
        }
        
        // Récupérer les éléments actifs (avec filtrage par catégorie si spécifié)
        $table_items = $wpdb->prefix . 'comparator_items';
        $table_contract_categories = $wpdb->prefix . 'comparator_contract_categories';
        $table_item_categories = $wpdb->prefix . 'comparator_item_categories';
        
        if (!empty($atts['category'])) {
            // Filtrer par catégorie(s)
            $categories = array_map('trim', explode(',', $atts['category']));
            $placeholders = implode(',', array_fill(0, count($categories), '%s'));
            
            $query = "SELECT DISTINCT i.* FROM $table_items i
                     JOIN $table_item_categories ic ON i.id = ic.item_id
                     JOIN $table_contract_categories cc ON ic.category_id = cc.id
                     WHERE i.type_id = %d AND i.is_active = 1 
                     AND cc.slug IN ($placeholders)
                     ORDER BY i.sort_order, i.name";
            
            $items = $wpdb->get_results($wpdb->prepare($query, array_merge([$type->id], $categories)));
        } else {
            // Tous les éléments actifs
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_items WHERE type_id = %d AND is_active = 1 ORDER BY sort_order, name",
                $type->id
            ));
        }
        
        // Récupérer les catégories de contrats disponibles pour les filtres
        $contract_categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_contract_categories WHERE type_id = %d ORDER BY sort_order, name",
            $type->id
        ));
        
        // Récupérer les champs filtrables si les filtres sont activés
        $filterable_fields = array();
        $filters_by_category = array();
        if ($atts['show_filters'] === 'true') {
            $table_fields = $wpdb->prefix . 'comparator_fields';
            
            // SOLUTION 1 : Pré-trier les catégories puis construire le tableau dans le bon ordre
            
            // 1. Récupérer d'abord les catégories triées par sort_order
            $categories_ordered = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, sort_order 
                FROM $table_fields 
                WHERE type_id = %d AND field_type = 'category' 
                ORDER BY sort_order ASC",
                $type->id
            ));
            
            // 2. Pour chaque catégorie (dans l'ordre), récupérer ses champs filtrables
            foreach ($categories_ordered as $category) {
                $category_fields = $wpdb->get_results($wpdb->prepare(
                    "SELECT f.*, %s as category_name, %d as category_id, %d as category_sort_order
                    FROM $table_fields f
                    WHERE f.parent_category_id = %d AND f.is_filterable = 1 AND f.field_type = 'description'
                    ORDER BY f.sort_order ASC",
                    $category->name,
                    $category->id,
                    $category->sort_order,
                    $category->id
                ));
                
                // 3. Si cette catégorie a des champs filtrables, l'ajouter au tableau
                if (!empty($category_fields)) {
                    $filters_by_category[$category->name] = array(
                        'category_id' => $category->id,
                        'category_sort_order' => $category->sort_order,
                        'fields' => $category_fields
                    );
                    
                    // Ajouter aussi à la liste plate pour compatibilité
                    $filterable_fields = array_merge($filterable_fields, $category_fields);
                }
            }
        }
        
        // Enrichir les items avec leurs valeurs de filtres et catégories
        foreach ($items as $item) {
            $item->filter_values = array();
            $item->categories = array();
            
            // Récupérer les catégories de ce contrat
            $item_categories = $wpdb->get_results($wpdb->prepare(
                "SELECT cc.* FROM $table_contract_categories cc
                 JOIN $table_item_categories ic ON cc.id = ic.category_id
                 WHERE ic.item_id = %d
                 ORDER BY cc.sort_order, cc.name",
                $item->id
            ));
            $item->categories = $item_categories;
            
            // Récupérer les valeurs de filtres
            if (!empty($filterable_fields)) {
                foreach ($filterable_fields as $field) {
                    // Récupérer la valeur depuis la table des filtres
                    $filter_value = $wpdb->get_var($wpdb->prepare(
                        "SELECT filter_value FROM {$wpdb->prefix}comparator_item_filters WHERE item_id = %d AND field_id = %d",
                        $item->id, $field->id
                    ));
                    $item->filter_values[$field->id] = $filter_value;
                }
            }
        }
        
        ob_start();
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/grid.php';
        
        // Passer le type_slug au JavaScript
        wp_add_inline_script('wp-comparator-frontend', 
            'wpComparatorFrontend.currentTypeSlug = "' . esc_js($atts['type']) . '";'
        );
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour comparer deux éléments
     * Usage: [wp_comparator_compare type="assurance-prevoyance" items="aviva-senseo,april-prevoyance"]
     */
    public function shortcode_comparator_compare($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'items' => ''
        ), $atts);
        
        if (empty($atts['type']) || empty($atts['items'])) {
            return '<p>Erreur: Les paramètres "type" et "items" sont requis.</p>';
        }
        
        $item_slugs = explode(',', $atts['items']);
        if (count($item_slugs) !== 2) {
            return '<p>Erreur: Vous devez spécifier exactement 2 éléments à comparer.</p>';
        }
        
        global $wpdb;
        
        // Récupérer le type
        $table_types = $wpdb->prefix . 'comparator_types';
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $atts['type']
        ));
        
        if (!$type) {
            return '<p>Erreur: Type de comparateur non trouvé.</p>';
        }
        
        // Récupérer les éléments
        $table_items = $wpdb->prefix . 'comparator_items';
        $item1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE type_id = %d AND slug = %s AND is_active = 1",
            $type->id, trim($item_slugs[0])
        ));
        
        $item2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE type_id = %d AND slug = %s AND is_active = 1",
            $type->id, trim($item_slugs[1])
        ));
        
        if (!$item1 || !$item2) {
            return '<p>Erreur: Un ou plusieurs éléments non trouvés.</p>';
        }
        
        // Récupérer la structure des champs
        $comparison_data = $this->get_comparison_data($type->id, array($item1->id, $item2->id));
        
        ob_start();
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/compare-page.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour afficher un seul élément
     * Usage: [wp_comparator_single type="assurance-prevoyance" item="aviva-senseo"]
     */
    public function shortcode_comparator_single($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'item' => ''
        ), $atts);
        
        if (empty($atts['type']) || empty($atts['item'])) {
            return '<p>Erreur: Les paramètres "type" et "item" sont requis.</p>';
        }
        
        global $wpdb;
        
        // Récupérer le type
        $table_types = $wpdb->prefix . 'comparator_types';
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $atts['type']
        ));
        
        if (!$type) {
            return '<p>Erreur: Type de comparateur non trouvé.</p>';
        }
        
        // Récupérer l'élément
        $table_items = $wpdb->prefix . 'comparator_items';
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE type_id = %d AND slug = %s AND is_active = 1",
            $type->id, $atts['item']
        ));
        
        if (!$item) {
            return '<p>Erreur: Élément non trouvé.</p>';
        }
        
        // Récupérer les données de l'élément
        $item_data = $this->get_comparison_data($type->id, array($item->id));
        
        ob_start();
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/single.php';
        return ob_get_clean();
    }
    
    /**
     * Récupère les données structurées pour la comparaison
     */
    private function get_comparison_data($type_id, $item_ids) {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'comparator_fields';
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        // Récupérer les catégories avec toutes leurs données
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_categories WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
            $type_id
        ));
        
        $data = array();
        
        foreach ($categories as $category) {
            // Récupérer les champs description de cette catégorie
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE parent_category_id = %d AND field_type = 'description' ORDER BY sort_order",
                $category->id
            ));
            
            $category_data = array(
                'category' => $category,
                'fields' => array()
            );
            
            foreach ($fields as $field) {
                $field_data = array(
                    'field' => $field,
                    'values' => array(),
                    'long_descriptions' => array()
                );
                
                // Récupérer les valeurs pour chaque élément
                foreach ($item_ids as $item_id) {
                    $value = $wpdb->get_var($wpdb->prepare(
                        "SELECT value FROM $table_values WHERE item_id = %d AND field_id = %d",
                        $item_id, $field->id
                    ));
                    
                    $long_description = $wpdb->get_var($wpdb->prepare(
                        "SELECT long_description FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                        $item_id, $field->id
                    ));
                    
                    $field_data['values'][$item_id] = $value;
                    $field_data['long_descriptions'][$item_id] = $long_description;
                }
                
                $category_data['fields'][] = $field_data;
            }
            
            // N'ajouter la catégorie que si elle a des champs
            if (!empty($category_data['fields'])) {
                $data[] = $category_data;
            }
        }
        
        return $data;
    }
}