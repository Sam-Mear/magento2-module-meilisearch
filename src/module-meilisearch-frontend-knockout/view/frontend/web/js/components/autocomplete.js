define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/url'
], function(Component, ko, $, urlBuilder) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;
    const endpoint = urlBuilder.build('meilisearch/ajax/autocomplete');

    return Component.extend({
        defaults: {
            searchTerm: ko.observable(''),
            results: ko.observableArray(),
            isActive: ko.observable(false)
        },

        initialize: function() {
            this._super();

            this.baseUrl = meilisearchConfig.baseUrl;

            this.searchTerm.subscribe((v) => {
                this.performSearch(v);
            });

            this.hasResults = ko.pureComputed(() => {
                const resultData = this.results();
                return Object.values(resultData).some(list => Array.isArray(list) && list.length > 0);
            });

            this._bindClickOutside();

            return this;
        },

        performSearch: function(terms) {
            if (!terms) {
                this.results({});
                return;
            }

            $.ajax({
                url: endpoint,
                method: 'GET',
                dataType: 'json',
                data: {
                    q: terms,
                    limit: 5
                }
            }).done((res) => {
                this.results(res && res.results ? res.results : {});
            });
        },

        highlightMatch: function (text) {
            const query = this.searchTerm().toLowerCase();
            if (!query) return text;
            return text.replace(new RegExp(`(${query})`, 'gi'), '<strong>$1</strong>');
        },

        getSearchUrl: function() {
            return urlBuilder.build('catalogsearch/result');
        },

        toggleSearch: function() {
            this.isActive(!this.isActive());
            if (this.isActive()) {
                $('#search').focus();
            }
        },

        _bindClickOutside: function () {
            const self = this;
            $(document).on('click.miniSearch', function (e) {
                if ($(e.target).closest('.block-search').length === 0) {
                    self.isActive(false);
                    self.results([]);
                }
            });
        }
    });
});
