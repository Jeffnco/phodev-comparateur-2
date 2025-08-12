<div class="wp-comparator-grid-container">
    <div class="comparator-header">
        <h2><?php echo esc_html($type->name); ?></h2>
        <?php if ($type->description): ?>
            <p class="comparator-description"><?php echo esc_html($type->description); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($filterable_fields)): ?>
        <div class="comparator-filters">
            <h3>Filtrer les résultats</h3>
            <div id="comparator-filters-form" class="filters-form">
                <input type="hidden" name="type_slug" value="<?php echo esc_attr($type->slug); ?>">
                
                <!-- Filtre catégorie de contrat en premier -->
                <?php if (!empty($contract_categories)): ?>
                    <div class="filter-group filter-contract-category">
                        <label>🏷️ Catégorie de contrat</label>
                        <select class="filter-select" data-filter-type="contract-category">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($contract_categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <!-- Filtres groupés par catégories de champs -->
                <?php if (!empty($filters_by_category)): ?>
                    <?php foreach ($filters_by_category as $category_name => $category_data): ?>
                        <div class="filter-category-group">
                            <h4 class="filter-category-title">📁 <?php echo esc_html($category_name); ?></h4>
                            <div class="filter-category-fields">
                                <?php foreach ($category_data['fields'] as $field): ?>
                                    <div class="filter-group filter-sub-group">
                                        <label><?php echo esc_html($field->name); ?></label>
                                        
                                        <select class="filter-select" data-field-id="<?php echo $field->id; ?>">
                                            <option value="">Tous</option>
                                            <?php if ($field->filter_options): ?>
                                                <?php $options = explode(',', $field->filter_options); ?>
                                                <?php foreach ($options as $option): ?>
                                                    <option value="<?php echo esc_attr(trim($option)); ?>">
                                                        <?php echo esc_html(trim($option)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="filter-actions">
                    <button type="button" id="reset-filters" class="button-secondary">Réinitialiser</button>
                    <span class="results-count"><?php echo count($items); ?> résultat(s)</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="comparator-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <?php if (empty($items)): ?>
            <p class="no-items">Aucun élément disponible pour ce comparateur.</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="comparator-item" data-item-id="<?php echo $item->id; ?>" data-item-slug="<?php echo esc_attr($item->slug); ?>"
                     <?php 
                     // Ajouter les attributs data pour le filtrage
                     if (!empty($filterable_fields)) {
                         foreach ($filterable_fields as $field) {
                             $filter_value = isset($item->filter_values[$field->id]) ? $item->filter_values[$field->id] : '';
                             echo 'data-filter-' . $field->id . '="' . esc_attr($filter_value) . '" ';
                         }
                     }
                     ?>>
                    <div class="item-card">
                        <div class="item-header">
                            <?php if (!empty($item->categories)): ?>
                                <div class="item-categories">
                                    <?php foreach ($item->categories as $category): ?>
                                        <span class="category-badge" data-category="<?php echo esc_attr($category->slug); ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($item->logo_url): ?>
                                <div class="item-logo">
                                    <img src="<?php echo esc_url($item->logo_url); ?>" alt="<?php echo esc_attr($item->name); ?>">
                                </div>
                            <?php endif; ?>
                            <h3 class="item-title"><?php echo esc_html($item->name); ?></h3>
                        </div>
                        
                        <?php if ($item->description): ?>
                            <div class="item-description">
                                <p><?php echo wp_kses_post(wp_trim_words($item->description, 20)); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-actions">
                            <label class="compare-checkbox">
                                <input type="checkbox" name="compare_items[]" value="<?php echo esc_attr($item->slug); ?>" data-item-name="<?php echo esc_attr($item->name); ?>">
                                <span class="checkmark"></span>
                                Comparer
                            </label>
                            
                            <a href="/<?php echo esc_attr($item->slug); ?>" class="button-view-single">
                                Voir en détail
                            </a>
                            
                            <?php if ($item->document_url): ?>
                                <a href="<?php echo esc_url($item->document_url); ?>" target="_blank" class="button-document">
                                    Documentation
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Zone de comparaison en dessous de la grille -->
    <div class="comparison-section" id="comparison-section" style="display: none;">
        <div class="comparison-content">
            <div class="comparison-info">
                <h3>Comparaison sélectionnée</h3>
                <p class="selected-items-info">
                    <span class="selected-count">0 contrat(s) sélectionné(s)</span>
                    <span class="selection-help">Sélectionnez exactement 2 contrats pour les comparer</span>
                </p>
            </div>
            
            <div class="comparison-actions">
                <button id="compare-selected" class="button-primary button-large" disabled>
                    <span class="dashicons dashicons-analytics"></span>
                    Comparer les contrats sélectionnés
                </button>
                <button id="clear-selection" class="button-secondary">
                    <span class="dashicons dashicons-dismiss"></span>
                    Effacer la sélection
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var selectedItems = [];
    var maxSelection = 2;
    
    // Gestion de la sélection des éléments
    $('.compare-checkbox input[type="checkbox"]').on('change', function() {
        var itemSlug = $(this).val();
        var itemName = $(this).data('item-name');
        
        if ($(this).is(':checked')) {
            if (selectedItems.length >= maxSelection) {
                $(this).prop('checked', false);
                alert('Vous ne pouvez sélectionner que ' + maxSelection + ' éléments maximum.');
                return;
            }
            selectedItems.push({slug: itemSlug, name: itemName});
        } else {
            selectedItems = selectedItems.filter(function(item) {
                return item.slug !== itemSlug;
            });
        }
        
        updateToolbar();
    });
    
    // Mise à jour de la barre d'outils
    function updateToolbar() {
        var section = $('#comparison-section');
        var count = selectedItems.length;
        
        if (count > 0) {
            section.show();
            $('.selected-count').text(count + ' élément(s) sélectionné(s)');
            
            if (count === 2) {
                $('#compare-selected').prop('disabled', false).removeClass('disabled');
                $('.selection-help').text('Parfait ! Vous pouvez maintenant comparer ces 2 contrats');
            } else if (count === 1) {
                $('#compare-selected').prop('disabled', true).addClass('disabled');
                $('.selection-help').text('Sélectionnez 1 contrat supplémentaire pour comparer');
            } else {
                $('#compare-selected').prop('disabled', true).addClass('disabled');
                $('.selection-help').text('Trop de contrats sélectionnés, maximum 2');
            }
        } else {
            section.hide();
        }
    }
    
    // Comparer les éléments sélectionnés
    $('#compare-selected').on('click', function() {
        if (selectedItems.length === 2) {
            var typeSlug = $('input[name="type_slug"]').val() || '<?php echo esc_js($type->slug); ?>';
            var compareUrl = '?compare=' + selectedItems[0].slug + ',' + selectedItems[1].slug + '&type=' + typeSlug;
            window.location.href = compareUrl;
        }
    });
    
    // Effacer la sélection
    $('#clear-selection').on('click', function() {
        $('.compare-checkbox input[type="checkbox"]').prop('checked', false);
        selectedItems = [];
        updateToolbar();
    });
    
    // Gestion des filtres
    $('#comparator-filters-form').on('submit', function(e) {
        e.preventDefault();
        // Implémentation du filtrage via AJAX
        console.log('Filtrage des résultats...');
    });
    
    $('#reset-filters').on('click', function() {
        $('#comparator-filters-form')[0].reset();
        $('.comparator-item').show();
    });
});
</script>