/**
 * Viewer Analytics Tracker
 * Collects user interaction events and sends them in batches to the server.
 */
(function() {
    'use strict';

    const CONFIG = {
        endpoint: '/api/viewer-events',
        batchInterval: 10000, // Send every 10 seconds
        maxQueueSize: 50,
    };

    // Session ID (unique per page load)
    const sessionId = 'vs_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 8);

    // Get project ID from meta or project-data
    let projectId = null;
    const metaEl = document.querySelector('meta[name="project-id"]');
    if (metaEl) {
        projectId = parseInt(metaEl.content);
    }
    if (!projectId) {
        const dataEl = document.getElementById('project-data');
        if (dataEl) {
            try {
                const pd = JSON.parse(dataEl.textContent);
                // project-data might have 'id' (admin) or we derive from slug
                projectId = pd.id || null;
            } catch (e) {}
        }
    }

    if (!projectId) return; // No project context, skip analytics

    let queue = [];
    let sessionStart = Date.now();
    let batchTimer = null;

    // GA event category mapping
    const GA_CATEGORIES = {
        'model_loaded': 'viewer_3d',
        'unit_selected': 'unit_interaction',
        'unit_focused': 'unit_interaction',
        'comparison_opened': 'unit_interaction',
        'pdf_downloaded': 'lead_generation',
        'inquiry_sent': 'lead_generation',
        'whatsapp_clicked': 'lead_generation',
        'share_clicked': 'engagement',
        'calculator_used': 'engagement',
        'payment_plan_viewed': 'engagement',
        'gallery_viewed': 'engagement',
        'viewer_3d_opened': 'viewer_3d',
    };

    // Track an event
    function track(type, data, unitId) {
        queue.push({
            type: type,
            data: data || null,
            unit_id: unitId || null,
            timestamp: Date.now(),
        });

        if (queue.length >= CONFIG.maxQueueSize) {
            flush();
        }

        // Bridge to Google Analytics
        if (typeof gtag === 'function' && GA_CATEGORIES[type]) {
            gtag('event', type, {
                event_category: GA_CATEGORIES[type],
                event_label: data ? JSON.stringify(data) : undefined,
                value: unitId || undefined,
            });
        }
    }

    // Send queued events to server
    function flush() {
        if (queue.length === 0) return;

        const events = queue.splice(0, CONFIG.maxQueueSize);
        const payload = {
            project_id: projectId,
            session_id: sessionId,
            events: events,
        };

        // Use sendBeacon for reliability (especially on page unload)
        if (navigator.sendBeacon) {
            navigator.sendBeacon(CONFIG.endpoint, new Blob([JSON.stringify(payload)], { type: 'application/json' }));
        } else {
            fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true,
            }).catch(() => {});
        }
    }

    // Session start
    track('session_start', {
        referrer: document.referrer || null,
        url: window.location.href,
        screen: window.innerWidth + 'x' + window.innerHeight,
    });

    // Periodic flush
    batchTimer = setInterval(flush, CONFIG.batchInterval);

    // Session end on unload
    window.addEventListener('beforeunload', function() {
        const duration = Math.round((Date.now() - sessionStart) / 1000);
        track('session_end', { duration: duration });
        flush();
    });

    // Expose globally for other scripts to track events
    window.viewerAnalytics = {
        track: track,
        flush: flush,
        sessionId: sessionId,
    };

    // ---- Auto-track common interactions ----

    // Unit row clicks (landing page)
    document.addEventListener('click', function(e) {
        const unitRow = e.target.closest('.unit-row');
        if (unitRow) {
            const unitId = parseInt(unitRow.dataset.unitId);
            track('unit_selected', {
                identifier: unitRow.dataset.unitName,
                floor: unitRow.dataset.unitFloor,
            }, unitId);
        }

        // PDF downloads
        const pdfLink = e.target.closest('[href*="/pdf"]');
        if (pdfLink) {
            const row = pdfLink.closest('.unit-row');
            const unitId = row ? parseInt(row.dataset.unitId) : null;
            track('pdf_downloaded', null, unitId);
        }

        // WhatsApp clicks
        const waLink = e.target.closest('a[href*="wa.me"]');
        if (waLink) {
            // Try to extract unit context from modal or row
            const modalTitle = document.getElementById('modal-title');
            const unitContext = modalTitle ? modalTitle.textContent : null;
            track('whatsapp_clicked', { source: waLink.closest('#unit-modal') ? 'unit_modal' : (waLink.closest('.fixed') ? 'floating_button' : 'other'), unit: unitContext });
        }

        // Share buttons
        const shareBtn = e.target.closest('[data-share-network]');
        if (shareBtn) {
            track('share_clicked', { network: shareBtn.dataset.shareNetwork });
        }

        // Gallery thumbs
        const galleryThumb = e.target.closest('.gallery-thumb');
        if (galleryThumb) {
            track('gallery_viewed');
        }

        // Compare button
        const compareBtn = e.target.closest('[x-text*="Comparar"]');
        if (compareBtn || (e.target.textContent && e.target.textContent.trim() === 'Comparar')) {
            track('comparison_opened');
        }

        // 3D viewer open (mobile lazy load)
        const lazyPlaceholder = e.target.closest('#viewer-lazy-placeholder');
        if (lazyPlaceholder) {
            track('viewer_3d_opened', { trigger: 'mobile_tap' });
        }
    });

    // Inquiry form submit
    const inquiryForm = document.querySelector('form[action*="inquiry"]');
    if (inquiryForm) {
        inquiryForm.addEventListener('submit', function() {
            const unitSelect = this.querySelector('[name="unit_id"]');
            const unitId = unitSelect ? parseInt(unitSelect.value) || null : null;
            track('inquiry_sent', null, unitId);
            flush(); // Flush immediately on conversion
        });
    }

    // Calculator interaction (detect changes in calculator inputs)
    const calcContainer = document.querySelector('[x-data*="investCalc"]');
    if (calcContainer) {
        let calcTracked = false;
        calcContainer.addEventListener('input', function() {
            if (!calcTracked) {
                track('calculator_used');
                calcTracked = true;
            }
        });
    }

    // Payment plans tab clicks
    document.addEventListener('click', function(e) {
        const planBtn = e.target.closest('[x-data*="paymentTimeline"] button');
        if (planBtn) {
            track('payment_plan_viewed');
        }
    });

})();
