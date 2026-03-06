/**
 * Share functionality for Beach Finder
 */
const S = window.BF_STRINGS || {};

// Share beach using Web Share API or fallback to modal
async function shareBeach(slug, name) {
    const url = `${window.location.origin}/beach/${slug}`;
    const title = `${name} - ${S.share_brand || 'Puerto Rico Beach Finder'}`;
    const text = (S.share_check_out || 'Check out :name in Puerto Rico!').replace(':name', name);

    if (typeof window.bfTrack === 'function') {
        window.bfTrack('share_click', { beach_slug: slug, source: 'share' });
    }

    // Try native share first
    if (navigator.share) {
        try {
            await navigator.share({ title, text, url });
            console.log('Shared successfully');
            return;
        } catch (e) {
            if (e.name === 'AbortError') return;
            console.warn('Web Share failed, using fallback:', e);
        }
    }

    // Fallback to modal
    openShareModal(slug, name, url);
}

// Open share modal with social links
function openShareModal(slug, name, url) {
    const modal = document.getElementById('share-modal');
    const body = document.getElementById('share-modal-body');
    if (!modal || !body) return;

    const shareUrl = url || `${window.location.origin}/beach/${slug}`;
    const encodedUrl = encodeURIComponent(shareUrl);
    const encodedText = encodeURIComponent((S.share_check_out || 'Check out :name in Puerto Rico!').replace(':name', name));
    const encodedTitle = encodeURIComponent(`${name} - ${S.share_brand || 'Beach Finder'}`);

    body.innerHTML = `
        <p class="text-gray-600 mb-4">${S.share_label || 'Share'} <strong>${escapeHtml(name)}</strong></p>

        <!-- Social Share Buttons -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <a href="https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}"
               target="_blank"
               rel="noopener noreferrer"
               class="flex items-center justify-center gap-2 bg-[#1DA1F2] hover:bg-[#1a8cd8] text-white py-2 px-4 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                ${escapeHtml(S.share_twitter || 'X (Twitter)')}
            </a>

            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}"
               target="_blank"
               rel="noopener noreferrer"
               class="flex items-center justify-center gap-2 bg-[#1877F2] hover:bg-[#166fe5] text-white py-2 px-4 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                ${escapeHtml(S.share_facebook || 'Facebook')}
            </a>

            <a href="https://wa.me/?text=${encodedText}%20${encodedUrl}"
               target="_blank"
               rel="noopener noreferrer"
               class="flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#22c55e] text-white py-2 px-4 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                ${escapeHtml(S.share_whatsapp || 'WhatsApp')}
            </a>

            <a href="mailto:?subject=${encodedTitle}&body=${encodedText}%0A%0A${encodedUrl}"
               class="flex items-center justify-center gap-2 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                ${escapeHtml(S.share_email || 'Email')}
            </a>
        </div>

        <!-- Copy Link -->
        <div class="flex gap-2">
            <input type="text"
                   id="share-url-input"
                   value="${escapeHtml(shareUrl)}"
                   readonly
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 bg-gray-50">
            <button data-action="copyShareLink"
                    id="copy-link-btn"
                    class="px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg text-sm font-medium transition-colors">
                ${escapeHtml(S.share_copy_link || 'Copy Link')}
            </button>
        </div>
    `;

    modal.classList.add('open');
}

// Close share modal
function closeShareModal() {
    const modal = document.getElementById('share-modal');
    if (modal) modal.classList.remove('open');
}

// Copy link from share modal
async function copyShareLink() {
    const input = document.getElementById('share-url-input');
    const btn = document.getElementById('copy-link-btn');
    if (!input) return;

    try {
        await navigator.clipboard.writeText(input.value);
        if (typeof window.bfTrack === 'function') {
            window.bfTrack('share_copy_link', { source: 'share_modal' });
        }
        if (btn) {
            const originalText = btn.textContent;
            btn.textContent = S.share_copied_short || 'Copied!';
            btn.classList.remove('bg-cyan-500', 'hover:bg-cyan-600');
            btn.classList.add('bg-green-600');
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.add('bg-cyan-500', 'hover:bg-cyan-600');
                btn.classList.remove('bg-green-600');
            }, 2000);
        }
    } catch (e) {
        // Fallback for older browsers
        input.select();
        document.execCommand('copy');
        if (typeof window.bfTrack === 'function') {
            window.bfTrack('share_copy_link', { source: 'share_modal_fallback' });
        }
        if (btn) btn.textContent = S.share_copied_short || 'Copied!';
    }
}

// Direct copy link button (one-click copy without modal)
async function copyBeachLink(slug, btn) {
    const url = `${window.location.origin}/beach/${slug}`;

    try {
        await navigator.clipboard.writeText(url);
        if (typeof window.bfTrack === 'function') {
            window.bfTrack('share_copy_link', { beach_slug: slug, source: 'copy_button' });
        }

        // Update button state
        const icon = btn.querySelector('[data-lucide="link"]');
        const text = btn.querySelector('span');
        const originalText = text ? text.textContent : (S.share_copy_link || 'Copy Link');

        if (text) text.textContent = S.share_copied_short || 'Copied!';
        btn.classList.remove('bg-stone-100', 'hover:bg-stone-200', 'text-stone-700');
        btn.classList.add('bg-green-100', 'text-green-700');

        // Show toast
        if (typeof showToast === 'function') {
            showToast(S.share_copied || 'Link copied to clipboard!', 'success', 2000);
        }

        setTimeout(() => {
            if (text) text.textContent = originalText;
            btn.classList.add('bg-stone-100', 'hover:bg-stone-200', 'text-stone-700');
            btn.classList.remove('bg-green-100', 'text-green-700');
        }, 2000);
    } catch (e) {
        // Fallback: create temporary input
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);

        if (typeof window.bfTrack === 'function') {
            window.bfTrack('share_copy_link', { beach_slug: slug, source: 'copy_button_fallback' });
        }

        if (typeof showToast === 'function') {
            showToast(S.share_copied || 'Link copied to clipboard!', 'success', 2000);
        }
    }
}

// Escape HTML to prevent XSS
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
