define([
    'uiElement',
    'ko',
    'fuse',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state'
], function (Element, ko, Fuse, facetsState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig || {};
    if (!window.meilisearchFrontendConfig) {
        console.warn(
            '[MeilisearchFrontend] Missing window.meilisearchFrontendConfig; ' +
            'facet searchbox will initialize with empty config.'
        );
    }

    return Element.extend({
        initialize: function () {
            this._super();
            this.facetConfig = meilisearchConfig.facets && meilisearchConfig.facets.facetConfig
                ? meilisearchConfig.facets.facetConfig
                : {};
            this.fuseInstances = {};
            this.observeFacetChanges();
            return this;
        },

        observeFacetChanges: function() {
            facetsState.computedFacets.subscribe(facets => {
                if (!Array.isArray(facets)) {
                    return;
                }
                facets.forEach(facet => {
                    if (facet.options && !facet.allOptionsRaw) {
                        facet.allOptionsRaw = facet.options();
                    }

                    let facetConfig = this.facetConfig[facet.code];
                    if (facetConfig && facetConfig.searchboxFuzzyEnabled && facet.allOptionsRaw) {
                        this.fuseInstances[facet.code] = new Fuse(facet.allOptionsRaw, {
                            keys: ['label'],
                            threshold: 0.3,
                            minMatchCharLength: 1
                        });
                    }
                });
            });
        },

        search: function(facetCode, inputValue) {
            const facet = facetsState.computedFacets().find(f => f.code === facetCode);

            if (!facet || !facet.options || !facet.allOptionsRaw) return;

            const facetConfig = this.facetConfig[facetCode];
            const searchTerm = inputValue ? inputValue.trim() : '';

            if (!searchTerm) {
                facet.options(facet.allOptionsRaw);
                return;
            }

            let results;
            if (facetConfig && facetConfig.searchboxFuzzyEnabled && this.fuseInstances[facetCode]) {
                results = this.fuseInstances[facetCode].search(searchTerm).map(r => r.item);
            } else {
                results = facet.allOptionsRaw.filter(opt =>
                    opt.label?.toLowerCase().includes(searchTerm.toLowerCase())
                );
            }

            facet.options(results);
        }
    });
});
