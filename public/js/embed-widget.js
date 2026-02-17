/**
 * RealEstate 3D Embed Widget
 *
 * Usage:
 * <script src="https://yoursite.com/js/embed-widget.js"
 *         data-project="project-slug"
 *         data-theme="light"
 *         data-width="400"
 *         data-height="500"></script>
 */
(function () {
    'use strict';

    const script = document.currentScript;
    if (!script) return;

    const slug = script.getAttribute('data-project');
    if (!slug) {
        console.error('[RealEstate3D Widget] Missing data-project attribute');
        return;
    }

    const theme = script.getAttribute('data-theme') || 'light';
    const width = script.getAttribute('data-width') || '100%';
    const height = script.getAttribute('data-height') || '500';
    const baseUrl = script.src.replace(/\/js\/embed-widget\.js.*$/, '');

    // Create container
    const container = document.createElement('div');
    container.style.width = isNaN(width) ? width : width + 'px';
    container.style.maxWidth = '100%';

    // Create iframe
    const iframe = document.createElement('iframe');
    iframe.src = baseUrl + '/embed/' + encodeURIComponent(slug) + '?theme=' + theme;
    iframe.style.width = '100%';
    iframe.style.height = isNaN(height) ? height : height + 'px';
    iframe.style.border = '1px solid #e5e7eb';
    iframe.style.borderRadius = '12px';
    iframe.style.overflow = 'hidden';
    iframe.setAttribute('loading', 'lazy');
    iframe.setAttribute('title', 'RealEstate 3D Project Widget');

    container.appendChild(iframe);
    script.parentNode.insertBefore(container, script.nextSibling);
})();
