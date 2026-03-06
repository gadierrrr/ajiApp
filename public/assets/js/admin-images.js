/**
 * Admin Beach Image Management
 *
 * Handles drag-drop uploads, gallery management, and reordering
 */

(function () {
  'use strict';

  // State
  let beachId = null;
  let csrfToken = null;
  let images = [];
  let draggedItem = null;

  /**
   * Initialize the image manager
   */
  function init() {
    const container = document.getElementById('image-manager');
    if (!container) return;

    beachId = container.dataset.beachId;
    csrfToken = container.dataset.csrfToken;

    if (!beachId) {
      console.warn('Beach ID not set for image manager');
      return;
    }

    setupDropZone();
    setupFileInput();
    loadImages();
  }

  /**
   * Setup drag and drop zone
   */
  function setupDropZone() {
    const dropZone = document.getElementById('image-drop-zone');
    if (!dropZone) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
      dropZone.addEventListener(eventName, preventDefaults);
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
      dropZone.addEventListener(eventName, () => {
        dropZone.classList.add('border-blue-500', 'bg-blue-50', 'a11y-on-light-blue');
      });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
      dropZone.addEventListener(eventName, () => {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'a11y-on-light-blue');
      });
    });

    dropZone.addEventListener('drop', handleDrop);
    dropZone.addEventListener('click', () => {
      document.getElementById('image-file-input')?.click();
    });
  }

  /**
   * Setup file input
   */
  function setupFileInput() {
    const input = document.getElementById('image-file-input');
    if (!input) return;

    input.addEventListener('change', (e) => {
      if (e.target.files?.length) {
        uploadFiles(Array.from(e.target.files));
        e.target.value = '';
      }
    });
  }

  /**
   * Prevent default drag behaviors
   */
  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  /**
   * Handle file drop
   */
  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = Array.from(dt.files).filter((f) => f.type.startsWith('image/'));

    if (files.length) {
      uploadFiles(files);
    }
  }

  /**
   * Upload multiple files
   */
  async function uploadFiles(files) {
    const progressContainer = document.getElementById('upload-progress');
    const gallery = document.getElementById('image-gallery');

    for (const file of files) {
      // Validate size
      if (file.size > 10 * 1024 * 1024) {
        showToast(`${file.name} is too large (max 10MB)`, 'error');
        continue;
      }

      // Validate type
      if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) {
        showToast(`${file.name} is not a valid image type`, 'error');
        continue;
      }

      // Show progress
      const progressEl = createProgressElement(file.name);
      progressContainer?.appendChild(progressEl);

      try {
        const result = await uploadFile(file, progressEl);

        if (result.success) {
          images.push(result.image);
          appendImageToGallery(result.image);
          updateStats();
          updateCoverImageInput();
          showToast(
            `Uploaded ${file.name} (saved ${result.image.optimization.savings_percent}%)`,
            'success'
          );
        } else {
          showToast(result.error || 'Upload failed', 'error');
        }
      } catch (err) {
        showToast(`Failed to upload ${file.name}`, 'error');
        console.error(err);
      }

      // Remove progress element
      progressEl.remove();
    }
  }

  /**
   * Upload a single file
   */
  function uploadFile(file, progressEl) {
    return new Promise((resolve, reject) => {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('beach_id', beachId);
      formData.append('csrf_token', csrfToken);
      formData.append('action', 'upload');

      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percent = Math.round((e.loaded / e.total) * 100);
          const bar = progressEl.querySelector('.progress-bar');
          if (bar) bar.style.width = percent + '%';
        }
      });

      xhr.addEventListener('load', () => {
        try {
          const response = JSON.parse(xhr.responseText);
          resolve(response);
        } catch {
          reject(new Error('Invalid response'));
        }
      });

      xhr.addEventListener('error', () => reject(new Error('Upload failed')));

      xhr.open('POST', '/api/admin/upload-image.php');
      xhr.send(formData);
    });
  }

  /**
   * Create progress element
   */
  function createProgressElement(filename) {
    const el = document.createElement('div');
    el.className = 'bg-gray-100 rounded-lg p-3 mb-2';
    el.innerHTML = `
      <div class="flex justify-between text-sm mb-1">
        <span class="truncate">${escapeHtml(filename)}</span>
        <span class="text-gray-500">Uploading...</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="progress-bar bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
      </div>
    `;
    return el;
  }

  /**
   * Load existing images
   */
  async function loadImages() {
    if (!beachId) return;

    try {
      const response = await fetch(`/api/admin/upload-image.php?beach_id=${beachId}`);
      const data = await response.json();

      if (data.images) {
        images = data.images;
        renderGallery();
        updateStats();
      }
    } catch (err) {
      console.error('Failed to load images:', err);
    }
  }

  /**
   * Render the image gallery
   */
  function renderGallery() {
    const gallery = document.getElementById('image-gallery');
    if (!gallery) return;

    gallery.innerHTML = '';

    if (images.length === 0) {
      gallery.innerHTML =
        '<p class="text-gray-500 text-sm col-span-full">No images uploaded yet</p>';
      return;
    }

    images.forEach((image) => {
      appendImageToGallery(image);
    });

    setupDragReorder();
  }

  /**
   * Append a single image to the gallery
   */
  function appendImageToGallery(image) {
    const gallery = document.getElementById('image-gallery');
    if (!gallery) return;

    // Remove "no images" message if present
    const noImages = gallery.querySelector('p.text-gray-500');
    if (noImages) noImages.remove();

    const el = document.createElement('div');
    el.className =
      'image-item relative group bg-white border rounded-lg overflow-hidden cursor-move';
    el.dataset.imageId = image.id;
    el.draggable = true;

    const thumbUrl = image.urls?.thumb || `/uploads/admin/beaches/${image.filename}_400.webp`;
    const sizeFormatted = image.file_size_formatted || formatBytes(image.file_size);

    el.innerHTML = `
      <div class="aspect-square relative">
        <img src="${thumbUrl}" alt="${escapeHtml(image.alt_text || '')}"
             class="w-full h-full object-cover" loading="lazy">
        ${
          image.is_cover
            ? `
          <div class="absolute top-2 left-2 bg-yellow-400 text-yellow-900 px-2 py-0.5 rounded text-xs font-medium flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            Cover
          </div>
        `
            : ''
        }
        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs px-2 py-1">
          ${sizeFormatted}
        </div>
      </div>
      <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
        ${
          !image.is_cover
            ? `
          <button data-action="AdminImages.setCover" data-action-args='[${image.id}]'
                  class="bg-yellow-400 hover:bg-yellow-500 text-yellow-900 p-2 rounded-lg" title="Set as cover">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          </button>
        `
            : ''
        }
        <button data-action="AdminImages.deleteImage" data-action-args='[${image.id}]'
                class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg" title="Delete">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    `;

    gallery.appendChild(el);
  }

  /**
   * Setup drag reorder functionality
   */
  function setupDragReorder() {
    const gallery = document.getElementById('image-gallery');
    if (!gallery) return;

    const items = gallery.querySelectorAll('.image-item');

    items.forEach((item) => {
      item.addEventListener('dragstart', handleDragStart);
      item.addEventListener('dragend', handleDragEnd);
      item.addEventListener('dragover', handleDragOver);
      item.addEventListener('drop', handleReorderDrop);
    });
  }

  function handleDragStart(e) {
    draggedItem = e.target.closest('.image-item');
    e.dataTransfer.effectAllowed = 'move';
    setTimeout(() => draggedItem?.classList.add('opacity-50'), 0);
  }

  function handleDragEnd() {
    draggedItem?.classList.remove('opacity-50');
    draggedItem = null;
  }

  function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }

  function handleReorderDrop(e) {
    e.preventDefault();
    const target = e.target.closest('.image-item');

    if (draggedItem && target && draggedItem !== target) {
      const gallery = document.getElementById('image-gallery');
      const items = Array.from(gallery.querySelectorAll('.image-item'));
      const draggedIdx = items.indexOf(draggedItem);
      const targetIdx = items.indexOf(target);

      if (draggedIdx < targetIdx) {
        target.after(draggedItem);
      } else {
        target.before(draggedItem);
      }

      saveOrder();
    }
  }

  /**
   * Save the current order
   */
  async function saveOrder() {
    const gallery = document.getElementById('image-gallery');
    const items = gallery.querySelectorAll('.image-item');
    const order = Array.from(items)
      .map((item) => item.dataset.imageId)
      .join(',');

    try {
      const formData = new FormData();
      formData.append('action', 'reorder');
      formData.append('beach_id', beachId);
      formData.append('order', order);
      formData.append('csrf_token', csrfToken);

      await fetch('/api/admin/upload-image.php', {
        method: 'POST',
        body: formData,
      });

      // Update local order
      const newOrder = order.split(',').map(Number);
      images.sort((a, b) => newOrder.indexOf(a.id) - newOrder.indexOf(b.id));
    } catch (err) {
      console.error('Failed to save order:', err);
      showToast('Failed to save order', 'error');
    }
  }

  /**
   * Set an image as cover
   */
  async function setCover(imageId) {
    try {
      const formData = new FormData();
      formData.append('action', 'set-cover');
      formData.append('image_id', imageId);
      formData.append('csrf_token', csrfToken);

      const response = await fetch('/api/admin/upload-image.php', {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        // Update local state
        images.forEach((img) => {
          img.is_cover = img.id === imageId ? 1 : 0;
        });
        renderGallery();
        updateCoverImageInput();
        showToast('Cover image updated', 'success');
      } else {
        showToast(data.error || 'Failed to update cover', 'error');
      }
    } catch (err) {
      console.error('Failed to set cover:', err);
      showToast('Failed to update cover', 'error');
    }
  }

  /**
   * Delete an image
   */
  async function deleteImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) {
      return;
    }

    try {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('image_id', imageId);
      formData.append('csrf_token', csrfToken);

      const response = await fetch('/api/admin/upload-image.php', {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        images = images.filter((img) => img.id !== imageId);
        renderGallery();
        updateStats();
        updateCoverImageInput();
        showToast('Image deleted', 'success');
      } else {
        showToast(data.error || 'Failed to delete image', 'error');
      }
    } catch (err) {
      console.error('Failed to delete image:', err);
      showToast('Failed to delete image', 'error');
    }
  }

  /**
   * Update the hidden cover_image input with current cover URL
   */
  function updateCoverImageInput() {
    const coverImage = images.find((img) => img.is_cover);
    const input = document.querySelector('input[name="cover_image"]');

    if (input && coverImage) {
      const url = coverImage.urls?.medium || `/uploads/admin/beaches/${coverImage.filename}_800.webp`;
      input.value = url;
    } else if (input && images.length === 0) {
      input.value = '/images/beaches/placeholder-beach.webp';
    }
  }

  /**
   * Update statistics display
   */
  function updateStats() {
    const statsEl = document.getElementById('image-stats');
    if (!statsEl) return;

    if (images.length === 0) {
      statsEl.innerHTML = '';
      return;
    }

    const totalSize = images.reduce((sum, img) => sum + (img.file_size || 0), 0);
    const totalSavings = images.reduce((sum, img) => sum + (img.optimization_savings || 0), 0);
    const totalOriginal = totalSize + totalSavings;
    const savingsPercent = totalOriginal > 0 ? Math.round((totalSavings / totalOriginal) * 100) : 0;

    statsEl.innerHTML = `
      <div class="text-sm text-gray-600 flex items-center gap-4">
        <span>${images.length} image${images.length !== 1 ? 's' : ''}</span>
        <span>Total: ${formatBytes(totalSize)}</span>
        ${
          totalSavings > 0
            ? `<span class="text-green-600">Saved ${formatBytes(totalSavings)} (${savingsPercent}%)</span>`
            : ''
        }
      </div>
    `;
  }

  /**
   * Format bytes to human readable
   */
  function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
    return bytes + ' B';
  }

  /**
   * Escape HTML
   */
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  /**
   * Show toast notification
   */
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor =
      type === 'success'
        ? 'bg-green-500'
        : type === 'error'
          ? 'bg-red-500'
          : 'bg-blue-500';

    toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity`;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
      toast.classList.add('opacity-0');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose functions for inline handlers
  window.AdminImages = {
    setCover,
    deleteImage,
  };
})();
