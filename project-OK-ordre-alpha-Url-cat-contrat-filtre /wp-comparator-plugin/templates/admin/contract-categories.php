<div class="wrap">
    <h1>Catégories de contrats</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'contract_category_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Catégorie de contrat ajoutée avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'contract_category_updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Catégorie de contrat modifiée avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 'contract_category_not_added'): ?>
        <div class="notice notice-error is-dismissible">
            <p>Erreur lors de l'ajout de la catégorie de contrat.</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <!-- Sélecteur de type -->
        <div class="admin-section">
            <h2>Sélectionner un type de comparateur</h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-comparator-contract-categories">
                <select name="type_id" onchange="this.form.submit()">
                    <option value="">-- Choisir un type --</option>
                    <?php if (isset($types) && is_array($types)): ?>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type->id; ?>" <?php selected($selected_type, $type->id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>
        
        <?php if ($selected_type): ?>
            <?php 
            // Gestion de l'édition d'une catégorie
            $edit_category_id = isset($_GET['edit_category']) ? intval($_GET['edit_category']) : 0;
            $edit_category = null;
            if ($edit_category_id && isset($contract_categories)) {
                foreach ($contract_categories as $category) {
                    if ($category->id == $edit_category_id) {
                        $edit_category = $category;
                        break;
                    }
                }
            }
            ?>
            
            <!-- Formulaire d'ajout/modification de catégorie -->
            <div class="admin-section">
                <h2><?php echo $edit_category ? 'Modifier la catégorie de contrat' : 'Ajouter une nouvelle catégorie de contrat'; ?></h2>
                <p>Les catégories permettent de classer vos contrats (ex: Professionnels de santé, TNS, Salariés...)</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field($edit_category ? 'wp_comparator_update_contract_category' : 'wp_comparator_add_contract_category', '_wpnonce'); ?>
                    <input type="hidden" name="wp_comparator_action" value="<?php echo $edit_category ? 'update_contract_category' : 'add_contract_category'; ?>">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    <?php if ($edit_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category->id; ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name">Nom de la catégorie</label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" value="<?php echo $edit_category ? esc_attr($edit_category->name) : ''; ?>" required>
                                <p class="description">Ex: Professionnels de santé, TNS, Salariés</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="slug">Slug (optionnel)</label>
                            </th>
                            <td>
                                <input type="text" id="slug" name="slug" class="regular-text" value="<?php echo $edit_category ? esc_attr($edit_category->slug) : ''; ?>">
                                <p class="description">Laissez vide pour générer automatiquement</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="description">Description</label>
                            </th>
                            <td>
                                <textarea id="description" name="description" rows="3" class="large-text"><?php echo $edit_category ? esc_textarea($edit_category->description) : ''; ?></textarea>
                                <p class="description">Description de cette catégorie de contrats</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sort_order">Ordre d'affichage</label>
                            </th>
                            <td>
                                <input type="number" id="sort_order" name="sort_order" value="<?php echo $edit_category ? $edit_category->sort_order : '0'; ?>" min="0" class="small-text">
                                <p class="description">Plus le nombre est petit, plus la catégorie apparaîtra en haut</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php echo $edit_category ? 'Modifier la catégorie' : 'Ajouter la catégorie'; ?>">
                        <?php if ($edit_category): ?>
                            <a href="?page=wp-comparator-contract-categories&type_id=<?php echo $selected_type; ?>" class="button">Annuler</a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Liste des catégories existantes -->
            <div class="admin-section">
                <h2>Catégories existantes</h2>
                
                <?php if (!isset($contract_categories) || empty($contract_categories)): ?>
                    <p>Aucune catégorie de contrat créée pour ce type.</p>
                    <p><em>Créez des catégories pour pouvoir classer vos contrats (ex: Professionnels de santé, TNS, etc.)</em></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Nom</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Contrats associés</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contract_categories as $category): ?>
                                <?php
                                // Compter les contrats associés
                                global $wpdb;
                                $table_item_categories = $wpdb->prefix . 'comparator_item_categories';
                                $contracts_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM $table_item_categories WHERE category_id = %d",
                                    $category->id
                                ));
                                ?>
                                <tr>
                                    <td><?php echo $category->sort_order; ?></td>
                                    <td>
                                        <strong><?php echo esc_html($category->name); ?></strong>
                                        <div class="category-badge-preview">
                                            <span class="category-badge"><?php echo esc_html($category->name); ?></span>
                                        </div>
                                    </td>
                                    <td><code><?php echo esc_html($category->slug); ?></code></td>
                                    <td><?php echo esc_html($category->description); ?></td>
                                    <td>
                                        <span class="dashicons dashicons-products"></span>
                                        <?php echo $contracts_count; ?> contrat(s)
                                    </td>
                                    <td>
                                        <a href="?page=wp-comparator-contract-categories&type_id=<?php echo $selected_type; ?>&edit_category=<?php echo $category->id; ?>" class="button button-small">
                                            Modifier
                                        </a>
                                        <button class="button button-small button-link-delete" onclick="deleteContractCategory(<?php echo $category->id; ?>)">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #72aee6;">
                        <h4>💡 Comment utiliser les catégories :</h4>
                        <ol>
                            <li><strong>Assignez les catégories</strong> à vos contrats dans la section "Contrats"</li>
                            <li><strong>Utilisez le shortcode</strong> avec filtre : <code>[wp_comparator type="<?php echo esc_attr($types[array_search($selected_type, array_column($types, 'id'))]->slug ?? ''); ?>" category="<?php echo esc_attr($contract_categories[0]->slug ?? ''); ?>"]</code></li>
                            <li><strong>Les utilisateurs pourront filtrer</strong> par catégorie sur le site</li>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="admin-section">
                <p>Veuillez d'abord sélectionner un type de comparateur pour gérer ses catégories de contrats.</p>
                <p><a href="<?php echo admin_url('admin.php?page=wp-comparator-types'); ?>" class="button">Créer un nouveau type</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.category-badge-preview {
    margin-top: 5px;
}

.category-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid #bbdefb;
}
</style>

<script>
function deleteContractCategory(categoryId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie de contrat ? Elle sera retirée de tous les contrats associés.')) {
        jQuery.ajax({
            url: wpComparator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_comparator_delete_contract_category',
                category_id: categoryId,
                nonce: wpComparator.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erreur lors de la suppression: ' + response.data);
                }
            },
            error: function() {
                alert('Erreur de communication avec le serveur');
            }
        });
    }
}
</script>