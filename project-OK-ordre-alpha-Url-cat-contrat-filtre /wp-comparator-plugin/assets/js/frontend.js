jQuery(document).ready(function($) {
    console.log('🚀 WP Comparator Frontend JS chargé');
    
    // Variables globales
    var selectedItems = [];
    var maxSelection = 2;
    
    // ========================================
    // GESTION DES FILTRES
    // ========================================
    
    // Fonction de filtrage côté client
    function filterItems() {
        console.log('🔍 Fonction filterItems() appelée');
        var activeFilters = {};
        var activeCategoryFilter = '';
        var visibleCount = 0;
        
        // Récupérer tous les filtres actifs
        $('.filter-select').each(function() {
            var fieldId = $(this).data('field-id');
            var filterType = $(this).data('filter-type');
            var value = $(this).val();
            
            console.log('🔍 Filtre:', {fieldId: fieldId, filterType: filterType, value: value});
            
            if (value && fieldId) {
                activeFilters[fieldId] = value;
            } else if (value && filterType === 'contract-category') {
                activeCategoryFilter = value;
            }
        });
        
        console.log('🔍 Filtres actifs:', activeFilters);
        console.log('🔍 Catégorie active:', activeCategoryFilter);
        
        // Filtrer chaque contrat
        $('.comparator-item').each(function() {
            var item = $(this);
            var showItem = true;
            
            // Vérifier chaque filtre actif
            $.each(activeFilters, function(fieldId, filterValue) {
                var itemValue = item.data('filter-' + fieldId);
                
                if (itemValue && filterValue) {
                    var itemValueClean = String(itemValue).trim().toLowerCase();
                    var filterValueClean = String(filterValue).trim().toLowerCase();
                    
                    if (itemValueClean !== filterValueClean) {
                        showItem = false;
                        return false;
                    }
                }
            });
            
            // Vérifier le filtre de catégorie de contrat
            if (showItem && activeCategoryFilter) {
                var itemCategories = item.find('.category-badge');
                var hasCategory = false;
                
                itemCategories.each(function() {
                    if ($(this).data('category') === activeCategoryFilter) {
                        hasCategory = true;
                        return false;
                    }
                });
                
                if (!hasCategory) {
                    showItem = false;
                }
            }
            
            // Afficher ou masquer l'item
            if (showItem) {
                item.show();
                visibleCount++;
            } else {
                item.hide();
            }
        });
        
        console.log('👁️ Items visibles:', visibleCount);
        updateResultsCount(visibleCount);
    }
    
    // Fonction pour mettre à jour le compteur de résultats
    function updateResultsCount(count) {
        $('.results-count').text(count + ' résultat(s)');
        console.log('📊 Compteur mis à jour:', count);
    }
    
    // Événement de changement des filtres
    $('.filter-select').on('change', function() {
        console.log('🔄 Filtre changé:', $(this).val());
        filterItems();
    });
    
    // ========================================
    // BOUTON RÉINITIALISER - VERSION FINALE
    // ========================================
    
    // SUPPRESSION DE TOUS LES ÉVÉNEMENTS EXISTANTS ET CRÉATION D'UN NOUVEAU
    $(document).off('click', '#reset-filters');
    
    $(document).on('click', '#reset-filters', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🔄 BOUTON RÉINITIALISER CLIQUÉ !');
        
        // Réinitialiser MANUELLEMENT chaque select UN PAR UN
        $('.filter-select').each(function(index) {
            console.log('🔄 Réinitialisation select #' + index + ':', $(this).attr('id') || $(this).attr('class'));
            $(this).val('');
        });
        
        // Déclencher le filtrage
        filterItems();
        
        // Réinitialiser la sélection de comparaison
        $('.compare-checkbox input[type="checkbox"]').prop('checked', false);
        selectedItems = [];
        updateComparisonToolbar();
        
        console.log('✅ Réinitialisation terminée !');
        
        return false;
    });
    
    // ========================================
    // GESTION DE LA COMPARAISON
    // ========================================
    
    // Fonction de mise à jour de la barre d'outils de comparaison
    function updateComparisonToolbar() {
        var section = $('#comparison-section');
        var count = selectedItems.length;
        
        console.log('🔧 Mise à jour toolbar, items sélectionnés:', count);
        
        if (count > 0) {
            section.show();
            $('.selected-count').text(count + ' contrat(s) sélectionné(s)');
            
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
    
    // Gestion de la sélection des éléments pour comparaison
    $('.compare-checkbox input[type="checkbox"]').on('change', function() {
        var itemSlug = $(this).val();
        var itemName = $(this).data('item-name');
        
        console.log('☑️ Checkbox changée:', itemSlug, $(this).is(':checked'));
        
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
        
        updateComparisonToolbar();
    });
    
    // Comparer les éléments sélectionnés
    $('#compare-selected').on('click', function() {
        console.log('🔍 Bouton comparer cliqué, items:', selectedItems.length);
        
        if (selectedItems.length === 2) {
            var typeSlug = wpComparatorFrontend.currentTypeSlug || 'default';
            var compareUrl = '?compare=' + selectedItems[0].slug + ',' + selectedItems[1].slug + '&type=' + typeSlug;
            console.log('🔗 Redirection vers:', compareUrl);
            window.location.href = compareUrl;
        } else {
            alert('Veuillez sélectionner exactement 2 contrats pour les comparer.');
        }
    });
    
    // Effacer la sélection
    $('#clear-selection').on('click', function() {
        console.log('🗑️ Effacement de la sélection');
        $('.compare-checkbox input[type="checkbox"]').prop('checked', false);
        selectedItems = [];
        updateComparisonToolbar();
    });
    
    // ========================================
    // GESTION DES INFOBULLES
    // ========================================
    
    // Infobulles principales
    $(document).on('mouseenter click', '.info-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var tooltip = $(this).next('.info-tooltip');
        $('.info-tooltip').not(tooltip).hide();
        tooltip.show();
    });
    
    // Fermer l'infobulle avec le bouton fermer
    $(document).on('click', '.info-tooltip-close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).closest('.info-tooltip').hide();
    });
    
    // Fermer les infobulles en cliquant à l'extérieur
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.info-btn, .info-tooltip').length) {
            $('.info-tooltip').hide();
        }
    });
    
    // Empêcher la fermeture quand on clique dans l'infobulle
    $(document).on('click', '.info-tooltip', function(e) {
        e.stopPropagation();
    });
    
    // Infobulles petites au survol
    $('.info-btn-small').hover(
        function() {
            var title = $(this).attr('title');
            if (title) {
                var tooltip = $('<div class="temp-tooltip">' + title + '</div>');
                tooltip.css({
                    position: 'absolute',
                    background: '#333',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '4px',
                    fontSize: '12px',
                    zIndex: 1000,
                    whiteSpace: 'nowrap',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.3)'
                });
                
                $('body').append(tooltip);
                
                var offset = $(this).offset();
                tooltip.css({
                    top: offset.top - tooltip.outerHeight() - 5,
                    left: offset.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
                });
            }
        },
        function() {
            $('.temp-tooltip').remove();
        }
    );
    
    // ========================================
    // GESTION DES DESCRIPTIONS LONGUES
    // ========================================
    
    // Boutons "En savoir plus"
    $(document).on('click', '.btn-more-info', function(e) {
        e.preventDefault();
        console.log('📖 Bouton En savoir plus cliqué');
        
        var moreInfoContent = $(this).siblings('.more-info-content');
        console.log('📖 Contenu trouvé:', moreInfoContent.length);
        
        if (moreInfoContent.hasClass('show')) {
            moreInfoContent.removeClass('show');
            $(this).text('En savoir plus');
        } else {
            moreInfoContent.addClass('show');
            $(this).text('Réduire');
        }
    });
    
    // ========================================
    // INITIALISATION
    // ========================================
    
    console.log('✅ WP Comparator Frontend initialisé');
    
    // Initialiser le compteur de résultats
    var initialCount = $('.comparator-item:visible').length;
    updateResultsCount(initialCount);
    
    // Initialiser la barre d'outils de comparaison
    updateComparisonToolbar();
});

// ========================================
// FONCTIONS UTILITAIRES GLOBALES
// ========================================
window.wpComparatorUtils = {
    formatValue: function(value, type) {
        if (!value || value === '-') return '-';
        
        switch (type) {
            case 'url':
                return '<a href="' + value + '" target="_blank">' + value + '</a>';
            case 'checkbox':
                return value === '1' || value === 'on' ? 'Oui' : 'Non';
            case 'number':
                return parseFloat(value).toLocaleString();
            default:
                return value;
        }
    },
    
    generateSlug: function(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
    },
    
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};