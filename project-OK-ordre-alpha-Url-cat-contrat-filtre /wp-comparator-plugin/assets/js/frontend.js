jQuery(document).ready(function($) {
    
    // Variables globales
    var selectedItems = [];
    var maxSelection = parseInt(wpComparatorFrontend.maxSelection) || 2;
    
    // Gestion de la sélection des éléments pour comparaison
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
        
        updateComparisonToolbar();
    });
    
    // Mise à jour de la barre d'outils de comparaison
    function updateComparisonToolbar() {
        var toolbar = $('#comparison-toolbar');
        var count = selectedItems.length;
        
        if (count > 0) {
            toolbar.show();
            $('.selected-count').text(count + ' élément(s) sélectionné(s)');
            $('#compare-selected').prop('disabled', count < 2);
        } else {
            toolbar.hide();
        }
    }
    
    // Comparer les éléments sélectionnés
    $('#compare-selected').on('click', function() {
        if (selectedItems.length >= 2) {
            var typeSlug = wpComparatorFrontend.currentTypeSlug;
            var compareUrl = '?compare=' + selectedItems[0].slug + ',' + selectedItems[1].slug + '&type=' + typeSlug;
            window.location.href = compareUrl;
        } else {
            alert('Veuillez sélectionner exactement 2 contrats pour les comparer.');
        }
    });
    
    // Effacer la sélection
    $('#clear-selection').on('click', function() {
        $('.compare-checkbox input[type="checkbox"]').prop('checked', false);
        selectedItems = [];
        updateComparisonToolbar();
    });
    
    // Gestion des filtres - Filtrage côté client
    $('.filter-select').on('change', function() {
        filterItems();
    });
    
    // Réinitialiser les filtres
    $('#reset-filters').on('click', function() {
        $('.filter-select').val('');
        $('.comparator-item').show();
        updateResultsCount($('.comparator-item').length);
        // Réinitialiser la sélection de comparaison
        $('.compare-checkbox input[type="checkbox"]').prop('checked', false);
        selectedItems = [];
        updateComparisonToolbar();
    });
    
    // Fonction de filtrage côté client
    function filterItems() {
        var activeFilters = {};
        var activeCategoryFilter = '';
        
        // Récupérer tous les filtres actifs
        $('.filter-select').each(function() {
            var fieldId = $(this).data('field-id');
            var filterType = $(this).data('filter-type');
            var value = $(this).val();
            
            if (value && fieldId) {
                activeFilters[fieldId] = value;
            } else if (value && filterType === 'contract-category') {
                activeCategoryFilter = value;
            }
        });
        
        var visibleCount = 0;
        
        // Filtrer chaque contrat
        $('.comparator-item').each(function() {
            var item = $(this);
            var showItem = true;
            
            // Vérifier chaque filtre actif
            $.each(activeFilters, function(fieldId, filterValue) {
                var itemValue = item.data('filter-' + fieldId);
                
                // Comparer les valeurs (insensible à la casse et aux espaces)
                if (itemValue && filterValue) {
                    var itemValueClean = String(itemValue).trim().toLowerCase();
                    var filterValueClean = String(filterValue).trim().toLowerCase();
                    
                    if (itemValueClean !== filterValueClean) {
                        showItem = false;
                        return false; // Sortir de la boucle
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
                        return false; // Sortir de la boucle
                    }
                });
                
                if (!hasCategory) {
                    showItem = false;
                }
    // Gestion des infobulles - SOLUTION SIMPLE
    $('.info-btn').on('mouseenter', function() {
        $(this).next('.info-tooltip').css('display', 'block');
        // Mettre à jour le compteur
        updateResultsCount(visibleCount);
    $('.info-btn').on('mouseleave', function() {
        $(this).next('.info-tooltip').css('display', 'none');
    });
    
    // Gestion des infobulles petites
    $('.info-btn-small').hover(
        function() {
            var title = $(this).attr('title');
            if (title) {
                // Créer une infobulle temporaire
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
    
    // Gestion des boutons "En savoir plus" pour les descriptions longues - COMME LE BOUTON i
    $(document).on('click', '.btn-more-info', function(e) {
        e.preventDefault();
        console.log('Bouton En savoir plus cliqué (délégué)');
        var moreInfoContent = $(this).siblings('.more-info-content');
        console.log('Contenu trouvé:', moreInfoContent.length);
        
        if (moreInfoContent.hasClass('show')) {
            console.log('Masquer le contenu');
            moreInfoContent.removeClass('show');
            $(this).text('En savoir plus');
        } else {
            console.log('Afficher le contenu');
            moreInfoContent.addClass('show');
            $(this).text('Réduire');
        }
    });
    
    // Gestion des contenus déroulants
    $(document).on('click', '.btnDeroulant', function(e) {
        e.preventDefault();
        $(this).siblings('.deroulant').slideToggle();
        $(this).text($(this).text() === 'En savoir plus' ? 'Réduire' : 'En savoir plus');
    });
    
    $(document).on('click', '.fermerDeroulant', function(e) {
        e.preventDefault();
        $(this).closest('.deroulant').slideUp();
        $(this).closest('td').find('.btnDeroulant').text('En savoir plus');
    });
    
    // Gestion du responsive pour les tableaux
    function makeTablesResponsive() {
        $('.ComparSeul table').each(function() {
            if (!$(this).parent().hasClass('table-responsive')) {
                $(this).wrap('<div class="table-responsive"></div>');
            }
        });
    }
    
    // Appliquer le responsive au chargement
    makeTablesResponsive();
    
    // Gestion du scroll pour la barre d'outils fixe
    // Animation smooth scroll vers la section de comparaison
    function scrollToComparison() {
        var section = $('#comparison-section');
        if (section.is(':visible')) {
            $('html, body').animate({
                scrollTop: section.offset().top - 20
            }, 500);
        }
    }
    
    // Déclencher le scroll quand on sélectionne le premier contrat
    $('.compare-checkbox input[type="checkbox"]').on('change', function() {
        setTimeout(function() {
            if ($('#comparison-section').is(':visible')) {
                scrollToComparison();
            }
        }, 100);
    });
    
    // Animation d'apparition des éléments
    function animateItems() {
        $('.comparator-item').each(function(index) {
            $(this).delay(index * 100).fadeIn();
        });
    }
    
    // Recherche en temps réel
    $('#search-items').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.comparator-item').each(function() {
            var itemText = $(this).find('.item-title, .item-description').text().toLowerCase();
            if (itemText.indexOf(searchTerm) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
        
        updateResultsCount($('.comparator-item:visible').length);
    });
    
    // Gestion des liens externes
    $('a[target="_blank"]').on('click', function() {
        // Ajouter un petit délai pour permettre l'ouverture
        setTimeout(function() {
            console.log('Lien externe ouvert');
        }, 100);
    });
    
    // Lazy loading des images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
}
)
// Gestion des infobulles - VERSION AMÉLIORÉE
$(document).on('mouseenter click', '.info-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var tooltip = $(this).next('.info-tooltip');
    
    // Fermer toutes les autres infobulles
    $('.info-tooltip').not(tooltip).hide();
    
    // Toggle ou afficher cette infobulle
    if (e.type === 'click') {
        tooltip.toggle();
    } else {
        tooltip.show();
    }
});

// Fermer l'infobulle en cliquant sur le bouton fermer
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

// Fonctions utilitaires globales
window.wpComparatorUtils = {
    
    // Formater une valeur pour l'affichage
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
    
    // Générer un slug à partir d'un texte
    generateSlug: function(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
    },
    
    // Débounce pour les recherches
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