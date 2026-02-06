# Walkwizus_MeilisearchFrontend

This module provides the backend framework for Meilisearch storefront integrations.

It exposes:
- Public frontend config (safe to embed in HTML or fetch via API).
- Backend search and autocomplete endpoints.
- SSR view models and fragment rendering utilities.

The Knockout UI implementation lives in `Walkwizus_MeilisearchFrontendKnockout` and can be disabled if you want to implement your own frontend (Alpine, PHTML, etc.).

## Modules Overview

- `Walkwizus_MeilisearchFrontend`
  - Backend search endpoints and public config.
  - SSR view models and fragment endpoints.
  - Safe config exposure (no Meilisearch host or API keys).

- `Walkwizus_MeilisearchFrontendKnockout`
  - Default Knockout UI (layouts, templates, JS components).
  - Layout handles that wire the UI into Magento pages.

## Disabling Knockout UI

Disable the Knockout module if you want to provide a custom frontend:

```bash
bin/magento module:disable Walkwizus_MeilisearchFrontendKnockout
bin/magento setup:upgrade
bin/magento cache:flush
```

The `Walkwizus_MeilisearchFrontend` module remains enabled and provides backend APIs and SSR helpers.

## Frontend API Endpoints (GET)

These endpoints are available to build your own frontend.

### Search

`GET /meilisearch/ajax/search`

Query params:
- `q` string
- `page` int (1-based)
- `hitsPerPage` int
- `sortBy` string (must exist in `availableSortBy`)
- `sortDir` string: `asc|desc`
- `filters[facet_code]=value1,value2`
- `categoryId` int (optional)
- `viewMode` string (`grid`/`list`, optional)

Response (subset):
- `hits` array
- `totalHits` int
- `totalPages` int
- `page` int
- `hitsPerPage` int
- `facetDistribution` object

### Autocomplete

`GET /meilisearch/ajax/autocomplete`

Query params:
- `q` string (required)
- `limit` int (default 5, max 20)

Response:
```json
{
  "results": {
    "products": [ ... ],
    "categories": [ ... ]
  }
}
```

### Public Config

`GET /meilisearch/ajax/config`

Returns the same public config used by the Knockout UI. This excludes any sensitive values (Meilisearch host or API keys).

## Public Config Keys

The public config is designed to be safe for the browser. It includes, for example:
- `facets` (list + config)
- `availableSortBy`, `defaultSortBy`
- `gridPerPage`, `listPerPage`, `gridPerPageValues`, `listPerPageValues`
- `defaultViewMode`
- `currentCategoryId`
- `autocompleteIndex`
- `baseUrl`, `mediaBaseUrl`, `productUrlSuffix`, `priceFormat`, `images`

Not included:
- Meilisearch host
- Meilisearch API key
- Server-only category rules

## Building Your Own Frontend

1. Disable `Walkwizus_MeilisearchFrontendKnockout`.
2. Use the public config endpoint or `window.meilisearchFrontendConfig` (if you choose to inject it).
3. Call `GET /meilisearch/ajax/search` for search results.
4. Call `GET /meilisearch/ajax/autocomplete` for quick search.
5. Optionally reuse SSR view models and fragment endpoints for hybrid rendering.

## SSR Helpers

SSR is available via:
- `Walkwizus\MeilisearchFrontend\ViewModel\Ssr`
- `Walkwizus\MeilisearchFrontend\ViewModel\Ssr\Pagination`

These are used by the Knockout templates but can be reused by custom PHTML layouts.

## Fragment Endpoint

`POST /meilisearch/ajax/fragment`

This endpoint returns rendered fragments (price, swatches) for product SKUs. It can be used for post-processing search hits.

## Notes

- This module intentionally avoids exposing any Meilisearch host or API keys in the browser.
- The Knockout module is enabled by default in this repo; disable it if you are building a custom frontend.
