import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';

// =============================================================
//  State
// =============================================================
const state = {
    scene: null, camera: null, renderer: null, controls: null, clock: new THREE.Clock(),
    currentModel: null, modelBounds: null,
    sunLight: null, ambientLight: null, hemisphereLight: null,
    groundMesh: null, gridHelper: null,
    // Mapper state
    selectedUnitId: null,
    unitBoxes: new Map(),   // unitId -> { mesh, wireframe }
    activeBox: null,        // the box being edited (THREE.Mesh)
    raycaster: new THREE.Raycaster(),
    mouse: new THREE.Vector2(),
};

let config = {};

// =============================================================
//  Status colors
// =============================================================
const STATUS_COLORS = {
    available: 0x4caf50,
    reserved: 0xffc107,
    sold: 0xf44336,
};

// =============================================================
//  Init
// =============================================================
function init() {
    const container = document.getElementById('canvas-container');
    const w = container.clientWidth;
    const h = container.clientHeight;

    state.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    state.renderer.setSize(w, h);
    state.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    state.renderer.shadowMap.enabled = true;
    state.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    state.renderer.toneMapping = THREE.ACESFilmicToneMapping;
    state.renderer.toneMappingExposure = 1.0;
    state.renderer.outputColorSpace = THREE.SRGBColorSpace;
    container.appendChild(state.renderer.domElement);

    state.scene = new THREE.Scene();
    state.scene.background = new THREE.Color(0x87ceeb);
    state.scene.fog = new THREE.FogExp2(0x87ceeb, 0.002);

    state.camera = new THREE.PerspectiveCamera(60, w / h, 0.1, 10000);
    state.camera.position.set(30, 20, 30);

    state.controls = new OrbitControls(state.camera, state.renderer.domElement);
    state.controls.enableDamping = true;
    state.controls.dampingFactor = 0.08;
    state.controls.maxPolarAngle = Math.PI * 0.85;
    state.controls.minDistance = 2;
    state.controls.maxDistance = 500;
    state.controls.target.set(0, 5, 0);

    setupLighting();
    setupGround();

    const ro = new ResizeObserver(() => {
        const cw = container.clientWidth;
        const ch = container.clientHeight;
        state.camera.aspect = cw / ch;
        state.camera.updateProjectionMatrix();
        state.renderer.setSize(cw, ch);
    });
    ro.observe(container);

    // Click handler for raycasting
    state.renderer.domElement.addEventListener('click', onCanvasClick);

    animate();
}

// =============================================================
//  Lighting (simplified from viewer-public)
// =============================================================
function setupLighting() {
    state.ambientLight = new THREE.AmbientLight(0x404040, 0.5);
    state.scene.add(state.ambientLight);

    state.hemisphereLight = new THREE.HemisphereLight(0x87ceeb, 0x362907, 0.6);
    state.scene.add(state.hemisphereLight);

    state.sunLight = new THREE.DirectionalLight(0xfff4e0, 1.5);
    state.sunLight.position.set(50, 80, 30);
    state.sunLight.castShadow = true;
    state.sunLight.shadow.mapSize.width = 2048;
    state.sunLight.shadow.mapSize.height = 2048;
    state.sunLight.shadow.camera.near = 0.5;
    state.sunLight.shadow.camera.far = 300;
    state.sunLight.shadow.camera.left = -80;
    state.sunLight.shadow.camera.right = 80;
    state.sunLight.shadow.camera.top = 80;
    state.sunLight.shadow.camera.bottom = -80;
    state.scene.add(state.sunLight);
}

function setLightingPreset(preset) {
    switch (preset) {
        case 'morning':
            state.sunLight.position.set(80, 30, 50);
            state.sunLight.color.setHex(0xffe0b2);
            state.sunLight.intensity = 1.2;
            state.ambientLight.intensity = 0.3;
            state.renderer.toneMappingExposure = 0.9;
            break;
        case 'noon':
            state.sunLight.position.set(10, 100, 10);
            state.sunLight.color.setHex(0xfff4e0);
            state.sunLight.intensity = 1.8;
            state.ambientLight.intensity = 0.6;
            state.renderer.toneMappingExposure = 1.0;
            break;
        case 'evening':
            state.sunLight.position.set(-60, 15, -30);
            state.sunLight.color.setHex(0xff7043);
            state.sunLight.intensity = 1.0;
            state.ambientLight.intensity = 0.2;
            state.renderer.toneMappingExposure = 0.7;
            break;
    }
}

// =============================================================
//  Ground
// =============================================================
function setupGround() {
    const geo = new THREE.PlaneGeometry(500, 500, 1, 1);
    const mat = new THREE.MeshStandardMaterial({ color: 0x4a7c59, roughness: 0.9, metalness: 0, transparent: true, opacity: 0.5 });
    state.groundMesh = new THREE.Mesh(geo, mat);
    state.groundMesh.rotation.x = -Math.PI / 2;
    state.groundMesh.position.y = -0.01;
    state.groundMesh.receiveShadow = true;
    state.scene.add(state.groundMesh);

    state.gridHelper = new THREE.GridHelper(200, 40, 0x000000, 0x333333);
    state.gridHelper.material.opacity = 0.15;
    state.gridHelper.material.transparent = true;
    state.scene.add(state.gridHelper);
}

// =============================================================
//  Model Loading
// =============================================================
function loadModel(url) {
    const loader = new GLTFLoader();
    const draco = new DRACOLoader();
    draco.setDecoderPath('https://cdn.jsdelivr.net/npm/three@0.162.0/examples/jsm/libs/draco/');
    loader.setDRACOLoader(draco);

    loader.load(url, (gltf) => {
        const model = gltf.scene;
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        const maxDim = Math.max(size.x, size.y, size.z);
        const scaleFactor = 20 / maxDim;
        model.scale.setScalar(scaleFactor);
        model.position.x = -center.x * scaleFactor;
        model.position.z = -center.z * scaleFactor;
        model.position.y = -box.min.y * scaleFactor;

        model.traverse((child) => { if (child.isMesh) { child.castShadow = true; child.receiveShadow = true; } });
        model.userData.baseScale = scaleFactor;
        model.userData.baseY = model.position.y;

        state.currentModel = model;
        state.scene.add(model);

        // Apply project settings
        applySettings(config.settings);

        // Compute bounds
        updateModelBounds();

        // Build existing unit boxes
        buildAllUnitBoxes();

        // Hide loading
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.style.display = 'none';
    }, undefined, (err) => {
        console.error('Error loading model:', err);
        document.getElementById('loading-text').textContent = 'Error cargando modelo.';
    });
}

function updateModelBounds() {
    if (!state.currentModel) return;
    const worldBox = new THREE.Box3().setFromObject(state.currentModel);
    const worldSize = worldBox.getSize(new THREE.Vector3());
    const worldCenter = worldBox.getCenter(new THREE.Vector3());
    state.modelBounds = {
        min: { x: worldBox.min.x, y: worldBox.min.y, z: worldBox.min.z },
        max: { x: worldBox.max.x, y: worldBox.max.y, z: worldBox.max.z },
        size: { x: worldSize.x, y: worldSize.y, z: worldSize.z },
        center: { x: worldCenter.x, y: worldCenter.y, z: worldCenter.z },
    };
}

function applySettings(settings) {
    if (!settings || !state.currentModel) return;
    const model = state.currentModel;

    if (settings.model_rotation !== undefined) {
        model.rotation.y = THREE.MathUtils.degToRad(parseFloat(settings.model_rotation));
    }
    if (settings.model_scale !== undefined) {
        const pct = parseFloat(settings.model_scale) / 100;
        model.scale.setScalar(model.userData.baseScale * pct);
    }
    if (settings.model_elevation !== undefined) {
        model.position.y = model.userData.baseY + parseFloat(settings.model_elevation) * 0.5;
    }

    const gh = parseFloat(settings.ground_height || 0) * 0.5;
    if (state.groundMesh) state.groundMesh.position.y = gh - 0.01;
    if (state.gridHelper) state.gridHelper.position.y = gh;

    if (settings.lighting_preset) setLightingPreset(settings.lighting_preset);

    if (settings.camera_position_x !== undefined) {
        state.camera.position.set(
            parseFloat(settings.camera_position_x),
            parseFloat(settings.camera_position_y),
            parseFloat(settings.camera_position_z)
        );
        state.controls.target.set(
            parseFloat(settings.camera_target_x),
            parseFloat(settings.camera_target_y),
            parseFloat(settings.camera_target_z)
        );
    }

    // Recompute after scale/elevation changes
    updateModelBounds();
}

// =============================================================
//  Unit Boxes
// =============================================================
function bboxToWorld(bbox) {
    const b = state.modelBounds;
    if (!b) return null;
    return {
        cx: b.min.x + bbox.cx * b.size.x,
        cy: b.min.y + bbox.cy * b.size.y,
        cz: b.min.z + bbox.cz * b.size.z,
        sx: bbox.sx * b.size.x,
        sy: bbox.sy * b.size.y,
        sz: bbox.sz * b.size.z,
    };
}

function worldToFrac(worldPos) {
    const b = state.modelBounds;
    if (!b) return null;
    return {
        cx: (worldPos.x - b.min.x) / b.size.x,
        cy: (worldPos.y - b.min.y) / b.size.y,
        cz: (worldPos.z - b.min.z) / b.size.z,
    };
}

function createBoxMesh(bbox, status, isActive) {
    const w = bboxToWorld(bbox);
    if (!w) return null;

    const color = STATUS_COLORS[status] || 0x4caf50;
    const geo = new THREE.BoxGeometry(w.sx, w.sy, w.sz);
    const mat = new THREE.MeshBasicMaterial({
        color,
        transparent: true,
        opacity: isActive ? 0.4 : 0.2,
        depthTest: true,
    });
    const mesh = new THREE.Mesh(geo, mat);
    mesh.position.set(w.cx, w.cy, w.cz);

    // Edge wireframe
    const edges = new THREE.EdgesGeometry(geo);
    const lineWidth = isActive ? 1.0 : 0.6;
    const lineMat = new THREE.LineBasicMaterial({
        color,
        transparent: true,
        opacity: isActive ? 1.0 : 0.5,
    });
    const wireframe = new THREE.LineSegments(edges, lineMat);
    mesh.add(wireframe);
    mesh.userData.wireframe = wireframe;

    return mesh;
}

function buildAllUnitBoxes() {
    // Remove old boxes
    state.unitBoxes.forEach(mesh => {
        state.scene.remove(mesh);
        mesh.geometry.dispose();
        mesh.material.dispose();
    });
    state.unitBoxes.clear();

    document.querySelectorAll('.unit-list-item').forEach(item => {
        const unitId = parseInt(item.dataset.unitId);
        const bboxStr = item.dataset.unitBbox;
        if (!bboxStr) return;

        const bbox = JSON.parse(bboxStr);
        const status = item.dataset.unitStatus;
        const isActive = unitId === state.selectedUnitId;
        const mesh = createBoxMesh(bbox, status, isActive);
        if (mesh) {
            mesh.userData.unitId = unitId;
            state.scene.add(mesh);
            state.unitBoxes.set(unitId, mesh);
        }
    });
}

function updateActiveBox(bbox) {
    // Remove existing active box
    if (state.activeBox) {
        state.scene.remove(state.activeBox);
        state.activeBox.geometry.dispose();
        state.activeBox.material.dispose();
        state.activeBox = null;
    }

    if (!state.selectedUnitId || !bbox) return;

    const item = document.querySelector(`.unit-list-item[data-unit-id="${state.selectedUnitId}"]`);
    const status = item ? item.dataset.unitStatus : 'available';

    const mesh = createBoxMesh(bbox, status, true);
    if (mesh) {
        mesh.userData.unitId = state.selectedUnitId;
        state.scene.add(mesh);
        state.activeBox = mesh;

        // Also update in unitBoxes map (replace old)
        const old = state.unitBoxes.get(state.selectedUnitId);
        if (old) {
            state.scene.remove(old);
            old.geometry.dispose();
            old.material.dispose();
        }
        state.unitBoxes.set(state.selectedUnitId, mesh);
    }
}

// =============================================================
//  Raycasting - click on model to place center
// =============================================================
function onCanvasClick(event) {
    if (!state.selectedUnitId || !state.currentModel || !state.modelBounds) return;

    const rect = state.renderer.domElement.getBoundingClientRect();
    state.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    state.mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    state.raycaster.setFromCamera(state.mouse, state.camera);

    // Intersect with model meshes
    const meshes = [];
    state.currentModel.traverse((child) => { if (child.isMesh) meshes.push(child); });
    const intersects = state.raycaster.intersectObjects(meshes, false);

    if (intersects.length > 0) {
        const point = intersects[0].point;
        const frac = worldToFrac(point);
        if (frac) {
            // Clamp to 0-1
            frac.cx = Math.max(0, Math.min(1, frac.cx));
            frac.cy = Math.max(0, Math.min(1, frac.cy));
            frac.cz = Math.max(0, Math.min(1, frac.cz));

            // Update sliders
            setSliderValue('cx', frac.cx);
            setSliderValue('cy', frac.cy);
            setSliderValue('cz', frac.cz);

            // Update box
            syncBoxFromSliders();
        }
    }
}

// =============================================================
//  Sliders
// =============================================================
function getSliderValue(key) {
    return parseFloat(document.getElementById('slider-' + key).value);
}

function setSliderValue(key, val) {
    const slider = document.getElementById('slider-' + key);
    slider.value = val;
    document.getElementById('val-' + key).textContent = val.toFixed(3);
}

function getCurrentBbox() {
    return {
        cx: getSliderValue('cx'),
        cy: getSliderValue('cy'),
        cz: getSliderValue('cz'),
        sx: getSliderValue('sx'),
        sy: getSliderValue('sy'),
        sz: getSliderValue('sz'),
    };
}

function syncBoxFromSliders() {
    if (!state.selectedUnitId) return;
    const bbox = getCurrentBbox();
    updateActiveBox(bbox);
}

function initSliders() {
    ['cx', 'cy', 'cz', 'sx', 'sy', 'sz'].forEach(key => {
        const slider = document.getElementById('slider-' + key);
        slider.addEventListener('input', () => {
            document.getElementById('val-' + key).textContent = parseFloat(slider.value).toFixed(3);
            syncBoxFromSliders();
        });
    });
}

function enableSliders(enable) {
    ['cx', 'cy', 'cz', 'sx', 'sy', 'sz'].forEach(key => {
        document.getElementById('slider-' + key).disabled = !enable;
    });
    document.getElementById('btn-save').disabled = !enable;
    document.getElementById('btn-clear').disabled = !enable;
}

function loadBboxToSliders(bbox) {
    if (bbox) {
        setSliderValue('cx', bbox.cx);
        setSliderValue('cy', bbox.cy);
        setSliderValue('cz', bbox.cz);
        setSliderValue('sx', bbox.sx);
        setSliderValue('sy', bbox.sy);
        setSliderValue('sz', bbox.sz);
    } else {
        setSliderValue('cx', 0.5);
        setSliderValue('cy', 0.5);
        setSliderValue('cz', 0.5);
        setSliderValue('sx', 0.05);
        setSliderValue('sy', 0.05);
        setSliderValue('sz', 0.05);
    }
}

// =============================================================
//  Unit Selection
// =============================================================
function selectUnit(unitId) {
    // Deselect previous
    document.querySelectorAll('.unit-list-item').forEach(item => {
        item.classList.remove('bg-blue-100', 'ring-2', 'ring-blue-400');
    });

    // Dim all non-active boxes
    state.unitBoxes.forEach((mesh, id) => {
        if (id !== unitId) {
            mesh.material.opacity = 0.15;
            mesh.userData.wireframe.material.opacity = 0.4;
        }
    });

    state.selectedUnitId = unitId;
    const item = document.querySelector(`.unit-list-item[data-unit-id="${unitId}"]`);
    if (item) {
        item.classList.add('bg-blue-100', 'ring-2', 'ring-blue-400');
        item.scrollIntoView({ block: 'nearest' });

        const bboxStr = item.dataset.unitBbox;
        const bbox = bboxStr ? JSON.parse(bboxStr) : null;
        loadBboxToSliders(bbox);

        // Show selected info
        document.getElementById('selected-info').classList.remove('hidden');
        document.getElementById('selected-name').textContent = 'Unidad: ' + item.dataset.unitIdentifier;
    }

    enableSliders(true);
    syncBoxFromSliders();
}

// =============================================================
//  Save / Clear
// =============================================================
function saveBbox() {
    if (!state.selectedUnitId) return;
    const bbox = getCurrentBbox();
    const statusEl = document.getElementById('save-status');
    statusEl.textContent = 'Guardando...';

    fetch(`/admin/projects/${config.projectId}/units/${state.selectedUnitId}/bbox`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken,
        },
        body: JSON.stringify({
            bbox_center_x: bbox.cx,
            bbox_center_y: bbox.cy,
            bbox_center_z: bbox.cz,
            bbox_size_x: bbox.sx,
            bbox_size_y: bbox.sy,
            bbox_size_z: bbox.sz,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            statusEl.textContent = 'Guardado!';
            // Update data attribute
            const item = document.querySelector(`.unit-list-item[data-unit-id="${state.selectedUnitId}"]`);
            if (item) {
                item.dataset.unitBbox = JSON.stringify(bbox);
                // Update dot indicator
                const dot = document.getElementById('dot-' + state.selectedUnitId);
                if (dot) { dot.className = 'w-3 h-3 rounded-full flex-shrink-0 bg-green-500'; }
            }
            setTimeout(() => { statusEl.textContent = ''; }, 2000);
        } else {
            statusEl.textContent = 'Error al guardar.';
        }
    })
    .catch(() => { statusEl.textContent = 'Error de red.'; });
}

function clearBbox() {
    if (!state.selectedUnitId) return;
    if (!confirm('Limpiar bounding box de esta unidad?')) return;
    const statusEl = document.getElementById('save-status');
    statusEl.textContent = 'Limpiando...';

    fetch(`/admin/projects/${config.projectId}/units/${state.selectedUnitId}/bbox`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': config.csrfToken,
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            statusEl.textContent = 'Limpiado.';
            // Remove box from scene
            const mesh = state.unitBoxes.get(state.selectedUnitId);
            if (mesh) {
                state.scene.remove(mesh);
                mesh.geometry.dispose();
                mesh.material.dispose();
                state.unitBoxes.delete(state.selectedUnitId);
            }
            if (state.activeBox) {
                state.scene.remove(state.activeBox);
                state.activeBox.geometry.dispose();
                state.activeBox.material.dispose();
                state.activeBox = null;
            }
            // Update data attribute
            const item = document.querySelector(`.unit-list-item[data-unit-id="${state.selectedUnitId}"]`);
            if (item) {
                item.dataset.unitBbox = '';
                const dot = document.getElementById('dot-' + state.selectedUnitId);
                if (dot) { dot.className = 'w-3 h-3 rounded-full flex-shrink-0 bg-gray-300'; }
            }
            loadBboxToSliders(null);
            setTimeout(() => { statusEl.textContent = ''; }, 2000);
        } else {
            statusEl.textContent = 'Error al limpiar.';
        }
    })
    .catch(() => { statusEl.textContent = 'Error de red.'; });
}

// =============================================================
//  Render Loop
// =============================================================
function animate() {
    requestAnimationFrame(animate);
    const elapsed = state.clock.elapsedTime;

    // Pulse active box
    if (state.activeBox) {
        state.activeBox.material.opacity = 0.3 + 0.15 * Math.sin(elapsed * 2.5);
    }

    state.controls.update();
    state.renderer.render(state.scene, state.camera);
}

// =============================================================
//  Boot
// =============================================================
function boot() {
    const dataEl = document.getElementById('mapper-data');
    if (!dataEl) return;
    config = JSON.parse(dataEl.textContent);

    init();
    initSliders();

    // Unit list click handlers
    document.querySelectorAll('.unit-list-item').forEach(item => {
        item.addEventListener('click', () => {
            selectUnit(parseInt(item.dataset.unitId));
        });
    });

    // Save/Clear buttons
    document.getElementById('btn-save').addEventListener('click', saveBbox);
    document.getElementById('btn-clear').addEventListener('click', clearBbox);

    // Load model
    loadModel(config.modelUrl);
}

boot();
