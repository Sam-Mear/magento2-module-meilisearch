define([
    'jquery',
    'mage/url',
    'Walkwizus_MeilisearchFrontendKnockout/js/model/facets-state',
    'Walkwizus_MeilisearchFrontendKnockout/js/model/search-state',
    'Walkwizus_MeilisearchFrontendKnockout/js/model/sorter-state',
    'Walkwizus_MeilisearchFrontendKnockout/js/model/limiter-state'
], function (
    $,
    urlBuilder,
    facetsState,
    searchState,
    sorterState,
    limiterState
) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;
    const endpoint = urlBuilder.build('meilisearch/ajax/search');

    function init(initialState) {
        updateResults(initialState);
        initSubscription();

        searchState.isInitializing(false);
    }

    function initSubscription() {
        let lastFilters = JSON.stringify(facetsState.selectedFacets());

        facetsState.selectedFacets.subscribe((f) => {
            const newFilters = JSON.stringify(f);
            if (!searchState.isInitializing() && newFilters !== lastFilters) {
                lastFilters = newFilters;
                facetsState.currentPage(1);
                performSearch();
            }
        });

        facetsState.currentPage.subscribe(() => {
            if (!searchState.isInitializing()) {
                performSearch();
            }
        });

        sorterState.sortBy.subscribe(() => {
            if (!searchState.isInitializing()) {
                performSearch();
            }
        });

        sorterState.isDescending.subscribe(() => {
            if (!searchState.isInitializing()) {
                performSearch();
            }
        });

        limiterState.currentLimit.subscribe(() => {
            if (!searchState.isInitializing()) {
                facetsState.currentPage(1);
                performSearch();
            }
        });
    }

    function performSearch() {
        searchState.isLoading(true);

        const searchQuery = facetsState.searchQuery();
        const sortField = sorterState.sortBy() ?? meilisearchConfig.defaultSortBy;
        const sortDirection = sorterState.isDescending() ? 'desc' : 'asc';
        const selectedFilters = facetsState.selectedFacets();
        const currentPage = facetsState.currentPage();
        const hitsPerPage = limiterState.currentLimit();

        const params = {
            q: searchQuery,
            page: currentPage,
            hitsPerPage: hitsPerPage,
            sortBy: sortField || undefined,
            sortDir: sortField ? sortDirection : undefined,
            filters: selectedFilters
        };

        if (meilisearchConfig.currentCategoryId) {
            params.categoryId = meilisearchConfig.currentCategoryId;
        }

        if (meilisearchConfig.defaultViewMode) {
            params.viewMode = meilisearchConfig.defaultViewMode;
        }

        $.ajax({
            url: endpoint,
            method: 'GET',
            dataType: 'json',
            data: params
        })
            .done(updateResults)
            .always(() => {
                searchState.isLoading(false);
            });
    }

    function updateResults(results) {
        if (!results) {
            return;
        }

        searchState.searchResults(results);
        searchState.totalHits(results.totalHits || 0);
        searchState.hitsPerPage(results.hitsPerPage || 0);
    }

    return {
        init: init
    };
});
