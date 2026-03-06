(function() {
    const root = document.getElementById('collection-explorer-root');
    if (!root) return;

    const searchInput = document.getElementById('ce-search');
    const sortSelect = document.getElementById('ce-sort');
    const mapLink = document.getElementById('ce-map-link');
    const resultsContainer = document.getElementById('collection-results');
    const clearFiltersBtn = root.querySelector('[data-ce-action="clear-filters"]');
    const filterCountBadge = root.querySelector('[data-ce-filter-count]');
    const defaultSort = root.dataset.defaultSort || 'rating';
    const defaultLimit = parseInt(root.dataset.defaultLimit || '15', 10);
    const csrfToken = root.dataset.csrf || '';
    const isAuthenticated = root.dataset.authenticated === '1';
    const collectionKey = root.dataset.collection || '';

    if (!resultsContainer || !collectionKey) {
        return;
    }

    const state = {
        collection: collectionKey,
        q: '',
        tags: [],
        municipality: '',
        sort: defaultSort,
        view: 'cards',
        page: 1,
        limit: defaultLimit,
        includeAll: false,
        loading: false
    };

    function parseStateFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const collection = params.get('collection');
        if (collection && collection === collectionKey) {
            state.collection = collection;
        }

        state.q = (params.get('q') || '').trim();
        state.tags = [];
        params.getAll('tags[]').forEach((tag) => {
            if (tag && !state.tags.includes(tag)) {
                state.tags.push(tag);
            }
        });
        params.getAll('tags').forEach((tag) => {
            if (tag && !state.tags.includes(tag)) {
                state.tags.push(tag);
            }
        });
        params.forEach((value, key) => {
            if (key.startsWith('tags[') && value && !state.tags.includes(value)) {
                state.tags.push(value);
            }
        });

        state.municipality = params.get('municipality') || '';

        const sort = params.get('sort');
        if (sort && ['rating', 'reviews', 'name', 'distance'].includes(sort)) {
            state.sort = sort;
        }

        const view = params.get('view');
        if (view && ['cards', 'list', 'grid', 'map'].includes(view)) {
            state.view = view;
        }

        const page = parseInt(params.get('page') || '1', 10);
        if (!Number.isNaN(page) && page > 0) {
            state.page = page;
        }

        const limit = parseInt(params.get('limit') || String(defaultLimit), 10);
        if (!Number.isNaN(limit) && limit > 0) {
            state.limit = limit;
        }

        const includeAll = params.get('include_all');
        state.includeAll = includeAll === '1' || includeAll === 'true';
    }

    const htmlLang = document.documentElement.lang || 'en';
    const langParam = htmlLang.startsWith('es') ? 'es' : 'en';

    function paramsForExplorer() {
        const params = new URLSearchParams();
        params.set('collection', state.collection);
        params.set('include_all', state.includeAll ? '1' : '0');
        params.set('view', state.view);
        params.set('sort', state.sort);
        params.set('page', String(state.page));
        params.set('limit', String(state.limit));
        params.set('lang', langParam);

        if (state.q) {
            params.set('q', state.q);
        }
        if (state.municipality) {
            params.set('municipality', state.municipality);
        }
        state.tags.forEach((tag) => {
            params.append('tags[]', tag);
        });
        return params;
    }

    function paramsForMap() {
        const params = paramsForExplorer();
        // Map view should show the full matching set, not paged list slices.
        params.delete('page');
        params.delete('limit');
        params.set('view', 'map');
        return params;
    }

    function getMapContext() {
        const initialCtx = window.BF_MAP_CONTEXT || {};
        return {
            mode: 'collection',
            collection: state.collection,
            filters: {
                q: state.q,
                tags: [...state.tags],
                municipality: state.municipality,
                sort: state.sort,
                include_all: state.includeAll
            },
            listViewId: 'collection-list-view',
            mapViewId: 'collection-map-view',
            mapContainerId: 'collection-map-container',
            mapEmptyId: 'collection-map-empty',
            mapErrorId: 'collection-map-error',
            mapLoadingId: 'collection-map-loading',
            autoScroll: false,
            updateUrl: false,
            i18n: initialCtx.i18n || {},
            beachUrlPrefix: initialCtx.beachUrlPrefix || '/beach/'
        };
    }

    function syncMapUi() {
        if (!window.BFContextMapView || typeof window.BFContextMapView.setContext !== 'function') {
            return;
        }
        const context = getMapContext();
        window.BFContextMapView.setContext(context);
        if (state.view === 'map') {
            window.BFContextMapView.showMapView({ context, updateUrl: false, scroll: false });
        } else {
            window.BFContextMapView.showListView({ context, updateUrl: false, scroll: false });
        }
    }

    function syncControls() {
        if (searchInput) {
            searchInput.value = state.q;
        }
        if (sortSelect) {
            sortSelect.value = state.sort;
        }

        const allBtn = root.querySelector('[data-ce-action="toggle-all"]');
        if (allBtn) {
            allBtn.classList.toggle('is-active', state.includeAll);
            allBtn.setAttribute('aria-pressed', state.includeAll ? 'true' : 'false');
        }

        root.querySelectorAll('[data-ce-action="toggle-tag"]').forEach((button) => {
            const tag = button.dataset.ceTag || '';
            const active = state.tags.includes(tag);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (mapLink) {
            mapLink.href = window.location.pathname + '?' + paramsForMap().toString();
        }

        const activeFilterCount = state.tags.length + (state.includeAll ? 1 : 0);
        if (filterCountBadge) {
            if (activeFilterCount > 0) {
                const tpl = filterCountBadge.dataset.activeTpl || '__COUNT__ active';
                filterCountBadge.textContent = tpl.replace('__COUNT__', activeFilterCount);
                filterCountBadge.hidden = false;
            } else {
                filterCountBadge.hidden = true;
            }
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.disabled = activeFilterCount === 0;
        }

        if (window.BFContextMapView && typeof window.BFContextMapView.setContext === 'function') {
            window.BFContextMapView.setContext(getMapContext());
        }
    }

    function syncUrl() {
        const params = paramsForExplorer();
        const nextUrl = window.location.pathname + '?' + params.toString();
        window.history.replaceState({}, '', nextUrl);
    }

    async function fetchResults() {
        if (state.loading) return;
        state.loading = true;
        root.classList.add('is-loading');

        try {
            const params = paramsForExplorer();
            params.set('format', 'html');

            const response = await fetch('/api/collection-beaches.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch collection results.');
            }

            const html = await response.text();
            resultsContainer.innerHTML = html;
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
            syncMapUi();
        } catch (error) {
            console.error(error);
        } finally {
            state.loading = false;
            root.classList.remove('is-loading');
        }
    }

    function refresh() {
        syncControls();
        syncUrl();
        fetchResults();
    }

    function clearTagFiltersOnly() {
        state.tags = [];
        state.includeAll = false;
        state.page = 1;
        refresh();
    }

    function debounce(fn, wait) {
        let timer = null;
        return function(...args) {
            if (timer) window.clearTimeout(timer);
            timer = window.setTimeout(() => fn(...args), wait);
        };
    }

    async function toggleFavorite(button) {
        if (!isAuthenticated) {
            if (typeof window.showSignupPrompt === 'function') {
                window.showSignupPrompt('favorites');
                return;
            }
            window.location.href = '/login.php';
            return;
        }

        if (button.dataset.loading === '1') return;
        const beachId = button.dataset.beachId;
        if (!beachId || !csrfToken) return;

        button.dataset.loading = '1';
        try {
            const response = await fetch('/api/toggle-favorite.php?format=json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    beach_id: beachId,
                    csrf_token: csrfToken
                }).toString()
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.error || 'Unable to update favorite.');
            }

            const isFavorite = payload.is_favorite === true;
            button.dataset.favorite = isFavorite ? '1' : '0';
            button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
            const ctx = window.BF_MAP_CONTEXT;
            const removeLabel = (ctx && ctx.i18n && ctx.i18n.removeFavorite) || BF_STRINGS?.fav_remove_aria || 'Remove from favorites';
            const addLabel = (ctx && ctx.i18n && ctx.i18n.addFavorite) || BF_STRINGS?.fav_add_aria || 'Add to favorites';
            button.setAttribute('aria-label', isFavorite ? removeLabel : addLabel);
            button.textContent = isFavorite ? '❤️' : '🤍';
        } catch (error) {
            console.error(error);
        } finally {
            delete button.dataset.loading;
        }
    }

    const debouncedSearch = debounce(function(value) {
        state.q = value.trim();
        state.page = 1;
        refresh();
    }, 250);

    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            debouncedSearch(event.target.value || '');
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            state.sort = sortSelect.value || defaultSort;
            state.page = 1;
            refresh();
        });
    }

    root.addEventListener('click', (event) => {
        const target = event.target.closest('[data-ce-action]');
        if (!target) return;

        const action = target.dataset.ceAction;
        if (action === 'toggle-tag') {
            const tag = target.dataset.ceTag || '';
            if (!tag) return;
            const exists = state.tags.includes(tag);
            state.tags = exists ? state.tags.filter((item) => item !== tag) : state.tags.concat(tag);
            state.page = 1;
            refresh();
            return;
        }

        if (action === 'toggle-all') {
            state.includeAll = !state.includeAll;
            state.page = 1;
            refresh();
            return;
        }

        if (action === 'set-view') {
            event.preventDefault();
            const view = target.dataset.ceView || 'cards';
            if (!['cards', 'list', 'grid', 'map'].includes(view)) return;
            state.view = view;
            state.page = 1;
            refresh();
            return;
        }

        if (action === 'clear-all') {
            state.q = '';
            state.tags = [];
            state.municipality = '';
            state.sort = defaultSort;
            state.view = 'cards';
            state.includeAll = false;
            state.page = 1;
            refresh();
            return;
        }

        if (action === 'clear-filters') {
            clearTagFiltersOnly();
            return;
        }

        if (action === 'favorite') {
            event.preventDefault();
            toggleFavorite(target);
            return;
        }

        if (action === 'favorite-login') {
            event.preventDefault();
            if (typeof window.showSignupPrompt === 'function') {
                window.showSignupPrompt('favorites');
            } else {
                window.location.href = '/login.php';
            }
        }
    });

    parseStateFromUrl();
    syncControls();
    syncMapUi();
})();
