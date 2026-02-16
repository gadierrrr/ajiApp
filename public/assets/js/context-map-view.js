(function() {
    if (window.BFContextMapView) {
        return;
    }

    const MAP_STYLE_URL = "https://basemaps.cartocdn.com/gl/positron-gl-style/style.json";
    const DEFAULT_CENTER = { lat: 18.2208, lng: -66.5901 };
    const DEFAULT_ZOOM = 9;
    const DEFAULT_MAX_IDS = 200;

    let activeMap = null;
    let activeContainerId = "";
    let currentContext = null;

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = String(value ?? "");
        return div.innerHTML;
    }

    function waitForMapLibre(timeoutMs = 10000) {
        if (typeof window.maplibregl !== "undefined") {
            return Promise.resolve(window.maplibregl);
        }

        return new Promise((resolve, reject) => {
            const started = Date.now();
            const timer = window.setInterval(() => {
                if (typeof window.maplibregl !== "undefined") {
                    window.clearInterval(timer);
                    resolve(window.maplibregl);
                    return;
                }
                if (Date.now() - started > timeoutMs) {
                    window.clearInterval(timer);
                    reject(new Error("Map library failed to load."));
                }
            }, 100);
        });
    }

    function parseIds(ids) {
        if (!Array.isArray(ids)) {
            return [];
        }
        const normalized = [];
        ids.forEach((id) => {
            if (typeof id !== "string") {
                return;
            }
            const trimmed = id.trim();
            if (!trimmed || !/^[A-Za-z0-9_-]{1,64}$/.test(trimmed)) {
                return;
            }
            if (!normalized.includes(trimmed)) {
                normalized.push(trimmed);
            }
        });
        return normalized.slice(0, DEFAULT_MAX_IDS);
    }

    function normalizeFilters(filters) {
        const source = (filters && typeof filters === "object") ? filters : {};
        return {
            q: typeof source.q === "string" ? source.q.trim() : "",
            tags: Array.isArray(source.tags) ? source.tags.filter((tag) => typeof tag === "string" && tag.trim() !== "") : [],
            municipality: typeof source.municipality === "string" ? source.municipality : "",
            sort: typeof source.sort === "string" ? source.sort : "",
            include_all: source.include_all === true || source.include_all === "1" || source.include_all === 1
        };
    }

    function normalizeContext(rawContext) {
        if (!rawContext || typeof rawContext !== "object") {
            return null;
        }

        const mode = rawContext.mode === "ids" ? "ids" : (rawContext.mode === "filters" ? "filters" : "collection");
        const context = {
            mode: mode,
            collection: typeof rawContext.collection === "string" ? rawContext.collection : "",
            ids: parseIds(rawContext.ids),
            filters: normalizeFilters(rawContext.filters),
            listViewId: typeof rawContext.listViewId === "string" ? rawContext.listViewId : "",
            mapViewId: typeof rawContext.mapViewId === "string" ? rawContext.mapViewId : "",
            mapContainerId: typeof rawContext.mapContainerId === "string" ? rawContext.mapContainerId : "",
            mapEmptyId: typeof rawContext.mapEmptyId === "string" ? rawContext.mapEmptyId : "",
            mapErrorId: typeof rawContext.mapErrorId === "string" ? rawContext.mapErrorId : "",
            mapLoadingId: typeof rawContext.mapLoadingId === "string" ? rawContext.mapLoadingId : "",
            mapTitle: typeof rawContext.mapTitle === "string" ? rawContext.mapTitle : "Beach Map",
            autoScroll: rawContext.autoScroll === true,
            updateUrl: rawContext.updateUrl === true
        };

        if (!context.mapViewId || !context.mapContainerId) {
            return null;
        }
        if (context.mode === "ids" && context.ids.length === 0) {
            return null;
        }
        return context;
    }

    function getContextFromWindow() {
        return normalizeContext(window.BF_MAP_CONTEXT);
    }

    function setVisibility(id, hidden) {
        if (!id) return;
        const el = document.getElementById(id);
        if (!el) return;
        if (hidden) {
            el.classList.add("hidden");
        } else {
            el.classList.remove("hidden");
        }
    }

    function clearMap() {
        if (activeMap) {
            try {
                activeMap.remove();
            } catch (error) {
                console.warn("Failed to remove existing map instance:", error);
            }
        }
        activeMap = null;
        activeContainerId = "";
    }

    function updateUrlForView(viewMode) {
        const params = new URLSearchParams(window.location.search);
        if (viewMode === "map") {
            params.set("view", "map");
        } else {
            params.delete("view");
        }
        const nextQuery = params.toString();
        const nextUrl = nextQuery ? `${window.location.pathname}?${nextQuery}${window.location.hash || ""}` : `${window.location.pathname}${window.location.hash || ""}`;
        window.history.replaceState({}, "", nextUrl);
    }

    function mapViewHrefForCurrentPage() {
        const params = new URLSearchParams(window.location.search);
        params.set("view", "map");
        const query = params.toString();
        return query ? `${window.location.pathname}?${query}` : `${window.location.pathname}?view=map`;
    }

    function syncNavMapLinks(context) {
        if (!context) {
            return;
        }
        const href = mapViewHrefForCurrentPage();
        document.querySelectorAll("[data-context-map-link]").forEach((link) => {
            link.setAttribute("href", href);
        });
    }

    function buildApiUrl(context) {
        const params = new URLSearchParams();

        if (context.mode === "ids") {
            context.ids.forEach((id) => {
                params.append("ids[]", String(id));
            });
            params.set("strict_ids", "1");
        } else {
            if (context.mode === "collection" && context.collection) {
                params.set("collection", context.collection);
            }
            if (context.filters.q) {
                params.set("q", context.filters.q);
            }
            if (context.filters.municipality) {
                params.set("municipality", context.filters.municipality);
            }
            if (context.filters.sort) {
                params.set("sort", context.filters.sort);
            }
            if (context.filters.include_all) {
                params.set("include_all", "1");
            }
            context.filters.tags.forEach((tag) => {
                params.append("tags[]", tag);
            });
        }

        return "/api/beaches-map.php" + (params.toString() ? `?${params.toString()}` : "");
    }

    function getDirectionsUrl(beach) {
        return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(`${beach.lat},${beach.lng}`)}`;
    }

    function buildPopupHtml(beach) {
        const detailLink = beach.slug
            ? `<a href="/beach/${encodeURIComponent(beach.slug)}" class="popup-btn popup-btn-primary">View Details</a>`
            : `<span class="popup-btn popup-btn-primary opacity-60" aria-disabled="true">View Details</span>`;
        const rating = beach.google_rating ? `
            <span class="popup-rating">
                <span class="text-yellow-500">★</span> ${Number(beach.google_rating).toFixed(1)}
            </span>
        ` : "";
        return `
            <div class="beach-popup">
                <div class="popup-header">
                    <h3 class="popup-title">${escapeHtml(beach.name)}</h3>
                    ${rating}
                </div>
                <p class="popup-municipality">${escapeHtml(beach.municipality || "")}</p>
                <div class="popup-actions">
                    <a href="${getDirectionsUrl(beach)}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="popup-btn popup-btn-secondary">
                       Directions
                    </a>
                    ${detailLink}
                </div>
            </div>
        `;
    }

    async function renderMap(context) {
        const container = document.getElementById(context.mapContainerId);
        if (!container) {
            return;
        }

        setVisibility(context.mapErrorId, true);
        setVisibility(context.mapEmptyId, true);
        setVisibility(context.mapLoadingId, false);
        container.innerHTML = "";

        let payload;
        try {
            const response = await fetch(buildApiUrl(context), {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            if (!response.ok) {
                throw new Error(`Map data request failed with status ${response.status}`);
            }
            payload = await response.json();
        } catch (error) {
            console.error("Failed to fetch map data:", error);
            setVisibility(context.mapLoadingId, true);
            setVisibility(context.mapErrorId, false);
            return;
        }

        const beaches = Array.isArray(payload.beaches) ? payload.beaches : [];
        const validBeaches = beaches.filter((beach) => {
            const lat = parseFloat(beach.lat);
            const lng = parseFloat(beach.lng);
            return Number.isFinite(lat) && Number.isFinite(lng);
        }).map((beach) => ({
            ...beach,
            lat: parseFloat(beach.lat),
            lng: parseFloat(beach.lng)
        }));

        if (validBeaches.length === 0) {
            setVisibility(context.mapLoadingId, true);
            setVisibility(context.mapEmptyId, false);
            return;
        }

        try {
            const maplibregl = await waitForMapLibre();
            if (activeMap && activeContainerId === context.mapContainerId) {
                clearMap();
            }

            const center = window.BeachFinder?.mapCenter || DEFAULT_CENTER;
            const map = new maplibregl.Map({
                container: context.mapContainerId,
                style: MAP_STYLE_URL,
                center: [center.lng, center.lat],
                zoom: DEFAULT_ZOOM,
                minZoom: 7,
                maxZoom: 18,
                attributionControl: false
            });
            map.addControl(new maplibregl.AttributionControl({ compact: true }), "bottom-right");
            map.addControl(new maplibregl.NavigationControl(), "top-right");
            map.addControl(new maplibregl.ScaleControl({ maxWidth: 100, unit: "metric" }), "bottom-left");

            const bounds = new maplibregl.LngLatBounds();
            validBeaches.forEach((beach) => {
                const markerEl = document.createElement("div");
                markerEl.className = "beach-marker";
                markerEl.innerHTML = "🏖️";
                const popup = new maplibregl.Popup({
                    offset: 25,
                    closeButton: true,
                    maxWidth: "280px",
                    className: "beach-popup-container"
                }).setHTML(buildPopupHtml(beach));

                new maplibregl.Marker({ element: markerEl })
                    .setLngLat([beach.lng, beach.lat])
                    .setPopup(popup)
                    .addTo(map);
                bounds.extend([beach.lng, beach.lat]);
            });

            map.once("load", () => {
                map.fitBounds(bounds, { padding: 50, maxZoom: 13, duration: 0 });
                setVisibility(context.mapLoadingId, true);
            });
            map.on("error", (error) => {
                console.error("Map render error:", error);
                setVisibility(context.mapErrorId, false);
            });

            activeMap = map;
            activeContainerId = context.mapContainerId;
        } catch (error) {
            console.error("Failed to render map:", error);
            setVisibility(context.mapLoadingId, true);
            setVisibility(context.mapErrorId, false);
        }
    }

    async function showMapView(options = {}) {
        const context = normalizeContext(options.context || getContextFromWindow());
        if (!context) {
            return false;
        }
        const mapViewEl = document.getElementById(context.mapViewId);
        const mapContainerEl = document.getElementById(context.mapContainerId);
        if (!mapViewEl || !mapContainerEl) {
            return false;
        }
        currentContext = context;
        window.BF_MAP_CONTEXT = context;
        syncNavMapLinks(context);

        setVisibility(context.listViewId, true);
        setVisibility(context.mapViewId, false);

        if (options.updateUrl !== false && context.updateUrl) {
            updateUrlForView("map");
        }

        if (options.scroll !== false && context.autoScroll) {
            const mapView = document.getElementById(context.mapViewId);
            if (mapView) {
                mapView.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }

        await renderMap(context);
        return true;
    }

    function showListView(options = {}) {
        const context = normalizeContext(options.context || currentContext || getContextFromWindow());
        if (!context) {
            return false;
        }
        const listViewEl = context.listViewId ? document.getElementById(context.listViewId) : null;
        const mapViewEl = document.getElementById(context.mapViewId);
        if (!mapViewEl || (context.listViewId && !listViewEl)) {
            return false;
        }
        currentContext = context;
        window.BF_MAP_CONTEXT = context;
        syncNavMapLinks(context);

        setVisibility(context.mapViewId, true);
        setVisibility(context.listViewId, false);

        if (options.updateUrl !== false && context.updateUrl) {
            updateUrlForView("list");
        }

        if (options.scroll !== false && context.autoScroll) {
            const listView = document.getElementById(context.listViewId);
            if (listView) {
                listView.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }
        return true;
    }

    function refreshMap(context = null) {
        const normalized = normalizeContext(context || currentContext || getContextFromWindow());
        if (!normalized) {
            return false;
        }
        currentContext = normalized;
        window.BF_MAP_CONTEXT = normalized;
        renderMap(normalized);
        return true;
    }

    function setContext(context) {
        const normalized = normalizeContext(context);
        if (!normalized) {
            return false;
        }
        currentContext = normalized;
        window.BF_MAP_CONTEXT = normalized;
        syncNavMapLinks(normalized);
        return true;
    }

    document.addEventListener("click", (event) => {
        const actionEl = event.target.closest("[data-context-map-action]");
        if (actionEl) {
            const action = actionEl.getAttribute("data-context-map-action");
            if (action === "show-map") {
                event.preventDefault();
                showMapView();
                return;
            }
            if (action === "show-list") {
                event.preventDefault();
                showListView();
                return;
            }
        }

        const navMapLink = event.target.closest("[data-context-map-link]");
        if (!navMapLink) {
            return;
        }
        const context = getContextFromWindow();
        if (!context) {
            return;
        }
        const mapViewEl = document.getElementById(context.mapViewId);
        const mapContainerEl = document.getElementById(context.mapContainerId);
        if (!mapViewEl || !mapContainerEl) {
            return;
        }
        event.preventDefault();
        showMapView();
    });

    document.addEventListener("DOMContentLoaded", () => {
        const context = getContextFromWindow();
        if (!context) {
            return;
        }
        syncNavMapLinks(context);
        const params = new URLSearchParams(window.location.search);
        if (params.get("view") === "map") {
            showMapView({ updateUrl: false, scroll: false });
        }
    });

    window.BFContextMapView = {
        showMapView,
        showListView,
        refreshMap,
        setContext
    };
})();
