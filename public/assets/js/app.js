const _S=window.BF_STRINGS||{};const state={beaches:window.BeachFinder?.beaches||[],filteredBeaches:[],userLocation:null,geolocationStatus:"idle",selectedTags:window.BeachFinder?.selectedTags||[],selectedMunicipality:window.BeachFinder?.selectedMunicipality||"",selectedCollection:window.BeachFinder?.selectedCollection||"",includeAll:window.BeachFinder?.includeAll||!1,hasLifeguard:window.BeachFinder?.hasLifeguard||!1,maxDistance:50,sortBy:window.BeachFinder?.sortBy||"name",viewMode:window.BeachFinder?.viewMode||"list",userFavorites:window.BeachFinder?.userFavorites||[],map:null,markers:[],userMarker:null,selectedBeachId:null,focusTrap:null};function showToast(e,t="info",a=4e3){let s=document.querySelector(".toast-container");s||(s=document.createElement("div"),s.className="toast-container",s.setAttribute("aria-live","polite"),s.setAttribute("aria-atomic","true"),document.body.appendChild(s));const n=document.createElement("div");n.className=`toast toast-${t}`,n.setAttribute("role","alert");const i={success:"✓",error:"✕",warning:"⚠",info:"ℹ"};return n.innerHTML=`\n        <span class="toast-icon">${i[t]||i.info}</span>\n        <span class="toast-message">${escapeHtml(e)}</span>\n        <button class="toast-close" aria-label="${_S.close_notification || 'Close notification'}">✕</button>\n    `,n.querySelector(".toast-close").addEventListener("click",()=>{removeToast(n)}),s.appendChild(n),requestAnimationFrame(()=>{n.classList.add("show")}),a>0&&setTimeout(()=>{removeToast(n)},a),n}function removeToast(e){e.classList.remove("show"),e.addEventListener("transitionend",()=>{e.remove()},{once:!0})}function escapeHtml(e){const t=document.createElement("div");return t.textContent=e,t.innerHTML}function showGridSkeleton(e=6){const t=document.getElementById("beach-grid");t&&(t.innerHTML=Array(e).fill(0).map(()=>'\n        <div class="beach-card skeleton-card">\n            <div class="skeleton skeleton-image"></div>\n            <div class="p-4">\n                <div class="skeleton skeleton-title"></div>\n                <div class="skeleton skeleton-text"></div>\n                <div class="skeleton skeleton-text" style="width: 60%"></div>\n            </div>\n        </div>\n    ').join(""))}function showLoading(e,t="Loading..."){e&&(e.innerHTML=`\n        <div class="loading-state">\n            <div class="loading-spinner" aria-hidden="true"></div>\n            <p class="text-stone-600 mt-3">${escapeHtml(t)}</p>\n        </div>\n    `)}function setButtonLoading(e,t=!0){e&&(t?(e.classList.add("btn-loading"),e.disabled=!0):(e.classList.remove("btn-loading"),e.disabled=!1))}function createFocusTrap(e){const t=["button:not([disabled])","a[href]","input:not([disabled])","select:not([disabled])","textarea:not([disabled])",'[tabindex]:not([tabindex="-1"])'].join(", "),a=e.querySelectorAll(t),s=a[0],n=a[a.length-1];function i(e){"Tab"===e.key&&(e.shiftKey?document.activeElement===s&&(e.preventDefault(),n?.focus()):document.activeElement===n&&(e.preventDefault(),s?.focus()))}return e.addEventListener("keydown",i),s?.focus(),()=>{e.removeEventListener("keydown",i)}}function setupHtmxListeners(){document.body.addEventListener("htmx:beforeRequest",e=>{const t=e.detail.target,a=e.detail.elt;if("beach-grid"===t?.id){const e=a?.getAttribute("hx-swap");"beforeend"!==e&&showGridSkeleton()}else"drawer-content-inner"===t?.id&&showLoading(t,_S.loading_details||"Loading beach details...")}),document.body.addEventListener("htmx:afterSwap",e=>{const t=e.detail.target;"drawer-content-inner"===t?.id&&(state.focusTrap=createFocusTrap(t.closest(".drawer-content")));if(typeof lucide!=="undefined")lucide.createIcons()}),document.body.addEventListener("htmx:responseError",e=>{showToast(_S.error_generic||"Something went wrong. Please try again.","error")}),document.body.addEventListener("htmx:afterRequest",e=>{if(e.detail.pathInfo?.requestPath?.includes("toggle-favorite")){const t=e.detail.xhr?.response;t?.includes("❤️")?showToast(_S.added_favorite||"Added to favorites!","success",2e3):t?.includes("🤍")&&showToast(_S.removed_favorite||"Removed from favorites","info",2e3)}})}function initMunicipalitySelect(){const e=document.getElementById("municipality-filter");e&&e.addEventListener("change",()=>applyFiltersWithHtmx())}function updateResultsCount(){const e=document.getElementById("results-count");if(e){const t=state.filteredBeaches.length,a=state.userLocation?(" "+(_S.near_you||"near you")):"";e.textContent=`${t} ${1!==t?(_S.beaches_plural||"beaches"):(_S.beach_singular||"beach")} ${_S.found||"found"}${a}`}}function renderFilterChips(){const e=document.getElementById("applied-filters");if(!e)return;const t=[];state.selectedMunicipality&&t.push(`\n            <span class="filter-chip">\n                📍 ${escapeHtml(state.selectedMunicipality)}\n                <button class="remove-btn" data-action="removeFilter" data-action-args='["municipality"]' aria-label="Remove municipality filter">✕</button>\n            </span>\n        `),state.selectedTags.forEach(e=>{const a=window.BeachFinder?.tagLabels?.[e]||e;t.push(`\n            <span class="filter-chip">\n                🏷️ ${escapeHtml(a)}\n                <button class="remove-btn" data-action="removeFilter" data-action-args='["tag","${escapeHtml(e)}"]' aria-label="Remove ${escapeHtml(a)} filter">✕</button>\n            </span>\n        `)}),state.hasLifeguard&&t.push(`\n            <span class="filter-chip">\n                🛟 ${escapeHtml(_S.lifeguard||"Lifeguard")}\n                <button class="remove-btn" data-action="removeFilter" data-action-args='["lifeguard"]' aria-label="Remove lifeguard filter">✕</button>\n            </span>\n        `),state.userLocation&&state.maxDistance<50&&t.push(`\n            <span class="filter-chip">\n                📏 ${escapeHtml((_S.within_km||"Within :distancekm").replace(":distance",state.maxDistance))}\n                <button class="remove-btn" data-action="removeFilter" data-action-args='["distance"]' aria-label="Remove distance filter">✕</button>\n            </span>\n        `),t.length>0&&t.push(`\n            <button class="filter-chip clear-all" data-action="clearFilters">\n                ${escapeHtml(_S.clear_all||"Clear all")}\n            </button>\n        `),e.innerHTML=t.join(""),e.style.display=t.length>0?"flex":"none"}function removeFilter(e,t=null){switch(e){case"municipality":state.selectedMunicipality="";const e=document.getElementById("municipality-filter");e&&(e.value="");break;case"tag":if(t)return void toggleTag(t);break;case"lifeguard":state.hasLifeguard=!1;break;case"distance":state.maxDistance=50;const a=document.getElementById("distance-filter");a&&(a.value=50);const s=document.getElementById("distance-value");s&&(s.textContent="50km")}applyFiltersWithHtmx()}function setViewMode(e){state.viewMode=e;const t=document.getElementById("list-view"),a=document.getElementById("map-view"),s=document.getElementById("view-list-btn"),n=document.getElementById("view-map-btn");"map"===e?(t?.classList.add("hidden"),a?.classList.remove("hidden"),s?.classList.remove("bg-brand-yellow","text-white"),s?.classList.add("bg-white","text-white/80","hover:bg-stone-50"),n?.classList.add("bg-brand-yellow","text-white"),n?.classList.remove("bg-white","text-white/80","hover:bg-stone-50"),state.map||"function"!=typeof initializeMap?"function"==typeof updateMapMarkers&&updateMapMarkers():initializeMap()):(a?.classList.add("hidden"),t?.classList.remove("hidden"),n?.classList.remove("bg-brand-yellow","text-white"),n?.classList.add("bg-white","text-white/80","hover:bg-stone-50"),s?.classList.add("bg-brand-yellow","text-white"),s?.classList.remove("bg-white","text-white/80","hover:bg-stone-50")),updateURL()}function showMapView(){setViewMode("map")}function toggleTag(e){const t=state.selectedTags.indexOf(e);-1===t?state.selectedTags.push(e):state.selectedTags.splice(t,1);document.querySelectorAll(`[data-tag="${e}"]`).forEach(a=>{state.selectedTags.includes(e)?(a.classList.add("bg-brand-yellow","text-brand-darker"),a.classList.remove("bg-white/5","bg-white/10","text-white/80","border-white/20"),a.setAttribute("aria-pressed","true")):(a.classList.remove("bg-brand-yellow","text-brand-darker"),a.classList.add("bg-white/5","text-white/80"),a.setAttribute("aria-pressed","false"))});const s=document.getElementById("clear-filters-btn");s&&(state.selectedTags.length>0||state.selectedMunicipality||state.selectedCollection||state.includeAll||state.hasLifeguard?s.classList.remove("hidden"):s.classList.add("hidden")),applyFiltersWithHtmx()}function clearFilters(){state.selectedTags=[],state.selectedMunicipality="",state.selectedCollection="",state.includeAll=!1,state.hasLifeguard=!1,state.maxDistance=50,document.querySelectorAll("[data-tag]").forEach(e=>{e.classList.remove("bg-brand-yellow","text-brand-darker"),e.classList.add("bg-white/5","text-white/80"),e.setAttribute("aria-pressed","false")});const e=document.getElementById("municipality-filter");e&&(e.value="");const t=document.getElementById("distance-filter");t&&(t.value=50);const a=document.getElementById("clear-filters-btn");a&&a.classList.add("hidden"),showToast(_S.filters_cleared||"All filters cleared","info",2e3),applyFiltersWithHtmx()}function applyFiltersWithHtmx(){const e=document.getElementById("municipality-filter"),t=document.getElementById("sort-filter"),a=document.getElementById("distance-filter");state.selectedMunicipality=e?.value||"",state.sortBy=t?.value||"name",state.maxDistance=parseInt(a?.value||50);const s=document.getElementById("distance-value");s&&(s.textContent=state.maxDistance+"km"),filterBeachesClientSide(),updateResultsCount(),renderFilterChips(),updateURL(),"map"===state.viewMode&&"function"==typeof updateMapMarkers&&updateMapMarkers();const n=buildQueryParams();document.getElementById("beach-grid")&&"undefined"!=typeof htmx&&htmx.ajax("GET",`/api/beaches.php?${n.toString()}`,{target:"#beach-grid",swap:"innerHTML"})}function applyFilters(){applyFiltersWithHtmx()}function filterBeachesClientSide(){let e=[...state.beaches];if(state.selectedTags.length>0&&(e=e.filter(e=>{const t=e.tags||[];return state.selectedTags.some(e=>t.includes(e))})),state.selectedMunicipality&&(e=e.filter(e=>e.municipality===state.selectedMunicipality)),state.hasLifeguard&&(e=e.filter(e=>1===e.has_lifeguard||"1"===String(e.has_lifeguard))),state.userLocation){const t=1e3*state.maxDistance;e=e.filter(e=>{const a=calculateDistance(state.userLocation.lat,state.userLocation.lng,parseFloat(e.lat),parseFloat(e.lng));return e._distance=a,a<=t})}switch(state.sortBy){case"distance":state.userLocation&&e.sort((e,t)=>(e._distance||0)-(t._distance||0));break;case"rating":e.sort((e,t)=>(t.google_rating||0)-(e.google_rating||0));break;default:e.sort((e,t)=>e.name.localeCompare(t.name))}state.filteredBeaches=e}function buildQueryParams(){const e=new URLSearchParams;return state.selectedTags.length>0&&state.selectedTags.forEach(t=>e.append("tags[]",t)),state.selectedMunicipality&&e.set("municipality",state.selectedMunicipality),state.selectedCollection&&e.set("collection",state.selectedCollection),state.includeAll&&e.set("include_all","1"),state.hasLifeguard&&e.set("has_lifeguard","1"),"name"!==state.sortBy&&e.set("sort",state.sortBy),"list"!==state.viewMode&&e.set("view",state.viewMode),e}function updateURL(){const e=buildQueryParams(),t=e.toString()?"?"+e.toString():window.location.pathname;history.replaceState(null,"",t)}function reloadBeachGrid(){const e=buildQueryParams();window.location.search=e.toString()}function openBeachDrawer(e){state.selectedBeachId=e;const t=document.getElementById("beach-drawer"),a=document.getElementById("drawer-content-inner");t&&a&&(state.previousFocus=document.activeElement,a.innerHTML='\n        <div class="drawer-loading">\n            <div class="drawer-handle" aria-hidden="true"></div>\n            <div class="skeleton skeleton-image" style="height: 200px; margin-bottom: 1rem;"></div>\n            <div class="p-6">\n                <div class="skeleton skeleton-title" style="width: 70%; margin-bottom: 1rem;"></div>\n                <div class="skeleton skeleton-text" style="margin-bottom: 0.5rem;"></div>\n                <div class="skeleton skeleton-text" style="width: 80%; margin-bottom: 0.5rem;"></div>\n                <div class="skeleton skeleton-text" style="width: 60%;"></div>\n            </div>\n        </div>\n    ',t.classList.add("open"),document.body.style.overflow="hidden",htmx.ajax("GET",`/api/beach-detail.php?id=${e}&lang=${(document.documentElement.lang||"en").substring(0,2)}`,{target:"#drawer-content-inner",swap:"innerHTML"}))}function closeBeachDrawer(e){if(e&&e.target!==document.getElementById("beach-drawer"))return;const t=document.getElementById("beach-drawer");t&&(t.classList.remove("open"),document.body.style.overflow="",state.selectedBeachId=null,state.focusTrap&&(state.focusTrap(),state.focusTrap=null),state.previousFocus&&(state.previousFocus.focus(),state.previousFocus=null))}function onLocationGranted(){const e=document.getElementById("location-btn"),t=document.getElementById("location-icon"),a=document.getElementById("location-text"),s=document.getElementById("distance-filter-container"),n=document.getElementById("sort-distance-option");e&&(e.classList.add("bg-green-50","border-green-300","text-green-700"),e.classList.remove("border-stone-300")),t&&(t.textContent="✓"),a&&(a.textContent=_S.geo_enabled||"Location enabled"),s&&s.classList.remove("hidden"),n&&(n.disabled=!1),updateDistanceBadges(),showToast(_S.location_enabled||"Location enabled! Distances are now shown.","success",3e3)}function updateDistanceBadges(){state.userLocation&&document.querySelectorAll(".beach-card").forEach(e=>{const t=parseFloat(e.dataset.lat),a=parseFloat(e.dataset.lng);if(t&&a){const s=calculateDistance(state.userLocation.lat,state.userLocation.lng,t,a);let n=e.querySelector(".distance-badge");n||(n=document.createElement("div"),n.className="distance-badge absolute top-3 right-3 bg-brand-yellow text-white text-xs font-semibold px-2 py-1 rounded-full shadow",e.querySelector(".relative")?.appendChild(n)),n.textContent=formatDistance(s)}})}document.addEventListener("DOMContentLoaded",()=>{state.filteredBeaches=[...state.beaches],updateResultsCount(),renderFilterChips();const e=localStorage.getItem("userLocation");if(e)try{const t=JSON.parse(e);t.lat&&t.lng&&(state.userLocation=t,state.geolocationStatus="granted",onLocationGranted())}catch(e){localStorage.removeItem("userLocation")}"map"===state.viewMode&&showMapView(),document.addEventListener("keydown",e=>{"Escape"===e.key&&(closeBeachDrawer(),closeShareModal())}),setupHtmxListeners(),initMunicipalitySelect(),initNavbarScroll()});function initNavbarScroll(){const navbar=document.getElementById("main-nav");if(navbar){window.addEventListener("scroll",function(){if(window.scrollY>20){navbar.classList.add("scrolled")}else{navbar.classList.remove("scrolled")}})}}function toggleMapView(){const mapView=document.getElementById("map-view");const listView=document.getElementById("list-view");if(mapView&&listView){if(mapView.classList.contains("hidden")){setViewMode("map")}else{setViewMode("list")}}else{setViewMode("map")}}

// Weather loading functionality - loads asynchronously after page render
function loadWeatherForCards() {
    const weatherBadges = document.querySelectorAll('.weather-badge[data-beach-id]');
    if (weatherBadges.length === 0) return;

    // Collect unique beach IDs that don't have weather yet
    const beachIds = [];
    weatherBadges.forEach(badge => {
        if (badge.classList.contains('hidden')) {
            const id = badge.dataset.beachId;
            if (id && !beachIds.includes(id)) {
                beachIds.push(id);
            }
        }
    });

    if (beachIds.length === 0) return;

    // Fetch weather in batches of 20
    const batchSize = 20;
    for (let i = 0; i < beachIds.length; i += batchSize) {
        const batch = beachIds.slice(i, i + batchSize);
        fetchWeatherBatch(batch);
    }
}

function fetchWeatherBatch(beachIds) {
    fetch(`/api/weather-batch.php?beaches=${beachIds.join(',')}`)
        .then(response => response.json())
        .then(data => {
            Object.entries(data).forEach(([beachId, weather]) => {
                updateWeatherBadge(beachId, weather);
            });
        })
        .catch(err => {
            console.warn('Weather fetch failed:', err);
        });
}

function updateWeatherBadge(beachId, weather) {
    const badge = document.querySelector(`.weather-badge[data-beach-id="${beachId}"]`);
    if (!badge || !weather) return;

    const iconEl = badge.querySelector('.weather-icon');
    const tempEl = badge.querySelector('.weather-temp');

    if (iconEl) iconEl.textContent = weather.icon || '🌤️';
    if (tempEl) tempEl.textContent = `${weather.temp}°F`;
    badge.title = weather.description || 'Weather';
    badge.classList.remove('hidden');

    // Remove placeholder class from parent row if it exists
    const row = badge.closest('.weather-row-placeholder');
    if (row) row.classList.remove('weather-row-placeholder');
}

// Load weather after page load and after HTMX swaps
document.addEventListener('DOMContentLoaded', () => {
    // Delay weather load to prioritize page render
    setTimeout(loadWeatherForCards, 500);
});

// Also load weather after HTMX updates the grid
document.body.addEventListener('htmx:afterSwap', (e) => {
    if (e.detail.target?.id === 'beach-grid') {
        setTimeout(loadWeatherForCards, 100);
    }
});

// Search query support and count fix - initialize from server
(function() {
    // Add searchQuery to state
    state.searchQuery = window.BeachFinder?.searchQuery || '';

    // Fix: Don't overwrite server-rendered count if beaches haven't been loaded
    const originalUpdateResultsCount = updateResultsCount;
    window.updateResultsCount = function() {
        const resultsEl = document.getElementById('results-count');
        if (!resultsEl) return;

        // If beaches aren't loaded yet and no filters applied, keep server-rendered count
        if (state.beaches.length === 0 && !state.selectedTags.length && !state.selectedMunicipality && !state.searchQuery) {
            // Keep existing server-rendered content
            return;
        }

        // Otherwise use client-side count
        const count = state.filteredBeaches.length || window.BeachFinder?.totalBeaches || 0;
        const locationSuffix = state.userLocation ? (' ' + (_S.near_you || 'near you')) : '';
        const searchSuffix = state.searchQuery ? ` ${_S.found_for || 'found for'} "${state.searchQuery}"` : '';
        const beachWord = count !== 1 ? (_S.beaches_plural || 'beaches') : (_S.beach_singular || 'beach');
        resultsEl.textContent = searchSuffix
            ? `${count} ${beachWord} ${searchSuffix}${locationSuffix}`
            : `${count} ${beachWord} ${_S.found || 'found'}${locationSuffix}`;
    };

    // Override buildQueryParams to include search
    const originalBuildQueryParams = buildQueryParams;
    window.buildQueryParams = function() {
        const params = originalBuildQueryParams();
        if (state.searchQuery) {
            params.set('q', state.searchQuery);
        }
        return params;
    };

    // Override clearFilters to also clear search
    const originalClearFilters = clearFilters;
    window.clearFilters = function() {
        state.searchQuery = '';
        const searchInput = document.getElementById('hero-search-input');
        if (searchInput) searchInput.value = '';
        originalClearFilters();
    };

    // Add search chip to filter chips
    const originalRenderFilterChips = renderFilterChips;
    window.renderFilterChips = function() {
        originalRenderFilterChips();

        // Add search chip if search is active
        if (state.searchQuery) {
            const container = document.getElementById('applied-filters');
            if (container) {
                const searchChip = document.createElement('span');
                searchChip.className = 'filter-chip';
                searchChip.innerHTML = `
                    🔍 "${escapeHtml(state.searchQuery)}"
                    <button class="remove-btn" data-action="clearSearch" aria-label="${_S.clear_search || 'Clear search'}">✕</button>
                `;
                container.insertBefore(searchChip, container.firstChild);
                container.style.display = 'flex';
            }
        }
    };

    // Clear search helper
    window.clearSearch = function() {
        state.searchQuery = '';
        const searchInput = document.getElementById('hero-search-input');
        if (searchInput) searchInput.value = '';
        applyFiltersWithHtmx();
    };

    // Re-render chips on load if search is active
    if (state.searchQuery) {
        setTimeout(renderFilterChips, 100);
    }
})();