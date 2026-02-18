/**
 * Units panel for the 3D viewer - grid layout below canvas
 */
(function() {
    'use strict';

    let allUnits = [];
    let projectData = {};
    let selectedUnitId = null;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        const dataEl = document.getElementById('project-data');
        if (!dataEl) return;
        projectData = JSON.parse(dataEl.textContent);
        fetchUnits();
    }

    function fetchUnits() {
        fetch('/api/projects/' + projectData.slug + '/units')
            .then(r => r.json())
            .then(data => {
                allUnits = data.units || [];
                renderSummary(data.summary);
                populateFloorFilter(data.floors || []);
                renderUnits(allUnits);
                // Register units with viewer for bbox support
                if (window.viewerAPI?.registerUnits) {
                    window.viewerAPI.registerUnits(allUnits);
                }
                // QW2: Auto-select unit from ?unit= query param
                checkUnitParam();
            })
            .catch(() => {
                const grid = document.getElementById('units-grid');
                if (grid) grid.innerHTML = '<p style="padding:20px;color:#888;font-size:13px;">No se pudieron cargar las unidades.</p>';
            });
    }

    function renderSummary(summary) {
        if (!summary) return;
        const el = document.getElementById('units-summary');
        if (el) {
            el.textContent = summary.available + ' disponibles de ' + summary.total + ' unidades';
        }
    }

    function populateFloorFilter(floors) {
        const sel = document.getElementById('filter-floor');
        if (!sel) return;
        sel.innerHTML = '<option value="">Piso</option>';
        floors.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f === 0 ? 'PB' : 'Piso ' + f;
            sel.appendChild(opt);
        });
    }

    function renderUnits(units) {
        const grid = document.getElementById('units-grid');
        if (!grid) return;

        if (units.length === 0) {
            grid.innerHTML = '<p style="padding:20px;color:#888;font-size:13px;">No hay unidades que coincidan.</p>';
            return;
        }

        const statusLabels = { available: 'Disponible', reserved: 'Reservado', sold: 'Vendido' };

        grid.innerHTML = units.map(u => {
            const isSelected = u.id === selectedUnitId;
            return '<div class="unit-card' + (isSelected ? ' selected' : '') + '" data-unit-id="' + u.id + '">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                    '<span class="unit-id">' + escapeHtml(u.identifier) + '</span>' +
                    '<span class="unit-status ' + u.status + '">' + (statusLabels[u.status] || u.status) + '</span>' +
                '</div>' +
                '<div class="unit-price">' + escapeHtml(u.formatted_price) + '</div>' +
                '<div class="unit-meta">' + u.bedrooms + ' dorm. &bull; ' + u.bathrooms + ' ba\u00f1os &bull; ' + u.area_m2 + ' m\u00b2' +
                    (u.typology ? ' &bull; ' + escapeHtml(u.typology) : '') +
                '</div>' +
            '</div>';
        }).join('');

        // Attach click handlers
        grid.querySelectorAll('.unit-card').forEach(card => {
            card.addEventListener('click', () => {
                const unitId = parseInt(card.dataset.unitId, 10);
                selectUnit(unitId);
            });
        });
    }

    function selectUnit(unitId) {
        const unit = allUnits.find(u => u.id === unitId);
        if (!unit) return;

        // Toggle off if clicking same unit
        if (selectedUnitId === unitId) {
            deselectUnit();
            return;
        }

        selectedUnitId = unitId;

        // Update card selection visuals
        document.querySelectorAll('#units-grid .unit-card').forEach(card => {
            card.classList.toggle('selected', parseInt(card.dataset.unitId, 10) === unitId);
        });

        // Scroll to viewer
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Focus camera on this unit's bbox, fallback to floor
        let focused = false;
        if (unit.bbox && window.viewerAPI?.focusOnUnit) {
            focused = window.viewerAPI.focusOnUnit(unit.id);
        }
        if (!focused && window.viewerAPI?.focusOnFloor) {
            const totalFloors = Math.max(...allUnits.map(u => u.floor)) + 1;
            window.viewerAPI.focusOnFloor(unit.floor, totalFloors);
        }

        // Show detail panel
        showDetail(unit);
    }

    function deselectUnit() {
        selectedUnitId = null;
        document.querySelectorAll('#units-grid .unit-card').forEach(card => {
            card.classList.remove('selected');
        });
        hideDetail();
        if (window.viewerAPI?.clearHighlight) {
            window.viewerAPI.clearHighlight();
        }
    }

    function showDetail(unit) {
        const panel = document.getElementById('unit-detail-panel');
        if (!panel) return;

        const statusLabels = { available: 'Disponible', reserved: 'Reservado', sold: 'Vendido' };

        let html = '<span class="unit-detail-back" onclick="backToList()">&larr; Cerrar detalle</span>';
        html += '<h3 class="detail-title">Unidad ' + escapeHtml(unit.identifier) + '</h3>';
        html += '<span class="unit-status ' + unit.status + '">' + (statusLabels[unit.status] || unit.status) + '</span>';
        html += '<div class="detail-price">' + escapeHtml(unit.formatted_price) + '</div>';

        html += '<div class="detail-grid">';
        html += '<div class="detail-item"><label>Dormitorios</label><span>' + unit.bedrooms + '</span></div>';
        html += '<div class="detail-item"><label>Ba\u00f1os</label><span>' + unit.bathrooms + '</span></div>';
        html += '<div class="detail-item"><label>\u00c1rea</label><span>' + unit.area_m2 + ' m\u00b2</span></div>';
        html += '<div class="detail-item"><label>Piso</label><span>' + (unit.floor === 0 ? 'PB' : unit.floor) + '</span></div>';
        if (unit.typology) {
            html += '<div class="detail-item"><label>Tipo</label><span>' + escapeHtml(unit.typology) + '</span></div>';
        }
        html += '</div>';

        if (unit.has_floor_plan && unit.floor_plan_url) {
            html += '<img class="plan-img" src="' + unit.floor_plan_url + '" alt="Plano" loading="lazy">';
        }

        const waNumber = projectData.whatsapp_number ? projectData.whatsapp_number.replace(/[^0-9]/g, '') : '';
        if (waNumber) {
            const waText = encodeURIComponent('Hola, me interesa la unidad ' + unit.identifier + ' del proyecto ' + (projectData.name || ''));
            html += '<a class="action-btn btn-whatsapp" href="https://wa.me/' + waNumber + '?text=' + waText + '" target="_blank" rel="noopener">WhatsApp</a>';
        }

        const landingLink = document.getElementById('section-landing-link');
        if (landingLink) {
            html += '<a class="action-btn btn-info" href="' + landingLink.href + '#contacto">Consultar</a>';
        }

        // Unit detail page link
        html += '<a class="action-btn btn-detail" href="/projects/' + encodeURIComponent(projectData.slug) + '/units/' + unit.id + '">Ver ficha completa</a>';

        // QW2: Share button
        html += '<button class="action-btn btn-share" onclick="shareUnit(' + unit.id + ')">Compartir</button>';

        panel.innerHTML = html;
        panel.classList.add('visible');
    }

    function hideDetail() {
        const panel = document.getElementById('unit-detail-panel');
        if (panel) {
            panel.classList.remove('visible');
            panel.innerHTML = '';
        }
    }

    // QW2: Check ?unit= query param on load
    function checkUnitParam() {
        const urlParams = new URLSearchParams(window.location.search);
        const unitParam = urlParams.get('unit');
        if (unitParam) {
            const unitId = parseInt(unitParam, 10);
            if (unitId && allUnits.find(u => u.id === unitId)) {
                setTimeout(() => selectUnit(unitId), 800);
            }
        }
    }

    // Expose globally for inline onclick and filter selects
    window.backToList = function() {
        deselectUnit();
    };

    // QW2: Share unit - copy link with ?unit= param
    window.shareUnit = function(unitId) {
        const url = new URL(window.location.href.split('#')[0]);
        url.searchParams.set('unit', unitId);
        navigator.clipboard.writeText(url.toString()).then(() => {
            const toast = document.getElementById('share-toast');
            if (toast) {
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2000);
            }
        });
    };

    window.filterUnits = function() {
        const status = document.getElementById('filter-status')?.value || '';
        const floor = document.getElementById('filter-floor')?.value || '';
        const bedrooms = document.getElementById('filter-bedrooms')?.value || '';

        let filtered = allUnits;
        if (status) filtered = filtered.filter(u => u.status === status);
        if (floor !== '') filtered = filtered.filter(u => u.floor === parseInt(floor));
        if (bedrooms) {
            const b = parseInt(bedrooms);
            if (b >= 3) {
                filtered = filtered.filter(u => u.bedrooms >= 3);
            } else {
                filtered = filtered.filter(u => u.bedrooms === b);
            }
        }
        renderUnits(filtered);
    };

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
})();
