import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';
import { FBXLoader } from 'three/addons/loaders/FBXLoader.js';

// =============================================================
//  Device Detection & Quality Tiers
// =============================================================
const isMobile = /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || ('ontouchstart' in window && window.innerWidth < 1024);

function detectTier() {
    if (!isMobile) return 'high';
    // Check GPU capability via a temp canvas
    try {
        const c = document.createElement('canvas');
        const gl = c.getContext('webgl2') || c.getContext('webgl');
        if (gl) {
            const maxTex = gl.getParameter(gl.MAX_TEXTURE_SIZE);
            const renderer = gl.getParameter(gl.RENDERER) || '';
            // High-end mobile GPUs
            if (maxTex >= 8192 && /adreno 6|apple gpu|mali-g7/i.test(renderer)) return 'medium';
            if (maxTex >= 4096) return 'medium';
        }
    } catch (e) {}
    return 'low';
}

const qualityTier = detectTier();

const QUALITY = {
    high:   { pixelRatio: 2,   shadows: true,  shadowSize: 2048, antialias: true,  sphereW: 64, sphereH: 32, textureSize: 512, toneMapping: THREE.ACESFilmicToneMapping },
    medium: { pixelRatio: 1.5, shadows: true,  shadowSize: 1024, antialias: true,  sphereW: 32, sphereH: 16, textureSize: 256, toneMapping: THREE.ACESFilmicToneMapping },
    low:    { pixelRatio: 1,   shadows: false, shadowSize: 512,  antialias: false, sphereW: 24, sphereH: 12, textureSize: 128, toneMapping: THREE.LinearToneMapping },
};

let currentQuality = QUALITY[qualityTier];

// =============================================================
//  Estado
// =============================================================
const state = {
    scene: null, camera: null, renderer: null, controls: null, clock: new THREE.Clock(),
    videoElement: null, videoTexture: null, videoSphere: null, videoPlaying: false,
    currentModel: null, modelMixer: null,
    sunLight: null, ambientLight: null, hemisphereLight: null,
    gridHelper: null, groundMesh: null,
    // Model bounds (world coords after positioning)
    modelBounds: null,
    // Camera animation
    animTarget: null, animCamera: null, animating: false,
    // Original camera state for clearHighlight
    origCameraPos: null, origTarget: null,
    // Highlight marker
    highlightMarker: null,
    // Unit boxes
    unitData: [],         // raw unit data from registerUnits
    unitBoxes: new Map(), // unitId -> THREE.Mesh
    selectedUnitBox: null, // currently highlighted unit mesh
    pendingUnits: null,    // units registered before model load
    // FPS monitoring
    fpsFrames: 0, fpsTime: 0, fpsValues: [], fpsDowngraded: false,
};

let pendingSettings = null;

// =============================================================
//  Init
// =============================================================
function init() {
    const container = document.getElementById('canvas-container');
    const w = container.clientWidth;
    const h = container.clientHeight;

    const q = currentQuality;
    state.renderer = new THREE.WebGLRenderer({ antialias: q.antialias, alpha: true, powerPreference: 'high-performance' });
    state.renderer.setSize(w, h);
    state.renderer.setPixelRatio(Math.min(window.devicePixelRatio, q.pixelRatio));
    state.renderer.shadowMap.enabled = q.shadows;
    if (q.shadows) state.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    state.renderer.toneMapping = q.toneMapping;
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
    state.controls.maxPolarAngle = Math.PI * 0.45;
    state.controls.minPolarAngle = Math.PI * 0.1;
    state.controls.minDistance = 10;
    state.controls.maxDistance = 80;
    state.controls.target.set(0, 5, 0);

    // Stop animation when user interacts so zoom/rotate works freely
    state.controls.addEventListener('start', () => {
        state.animating = false;
    });

    setupLighting();
    setupGround();
    setup360Sphere();

    // ResizeObserver for container-based sizing
    const ro = new ResizeObserver(() => {
        const cw = container.clientWidth;
        const ch = container.clientHeight;
        state.camera.aspect = cw / ch;
        state.camera.updateProjectionMatrix();
        state.renderer.setSize(cw, ch);
    });
    ro.observe(container);

    animate();
}

// =============================================================
//  Lighting
// =============================================================
function setupLighting() {
    state.ambientLight = new THREE.AmbientLight(0x404040, 0.5);
    state.scene.add(state.ambientLight);

    state.hemisphereLight = new THREE.HemisphereLight(0x87ceeb, 0x362907, 0.6);
    state.scene.add(state.hemisphereLight);

    state.sunLight = new THREE.DirectionalLight(0xfff4e0, 1.5);
    state.sunLight.position.set(50, 80, 30);
    state.sunLight.castShadow = currentQuality.shadows;
    state.sunLight.shadow.mapSize.width = currentQuality.shadowSize;
    state.sunLight.shadow.mapSize.height = currentQuality.shadowSize;
    state.sunLight.shadow.camera.near = 0.5;
    state.sunLight.shadow.camera.far = 300;
    state.sunLight.shadow.camera.left = -80;
    state.sunLight.shadow.camera.right = 80;
    state.sunLight.shadow.camera.top = 80;
    state.sunLight.shadow.camera.bottom = -80;
    state.sunLight.shadow.bias = -0.001;
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
            if (!state.videoSphere?.visible) {
                state.scene.background?.setHex(0xffd4a0);
                if (state.scene.fog) state.scene.fog.color.setHex(0xffd4a0);
            }
            break;
        case 'noon':
            state.sunLight.position.set(10, 100, 10);
            state.sunLight.color.setHex(0xfff4e0);
            state.sunLight.intensity = 1.8;
            state.ambientLight.intensity = 0.6;
            state.renderer.toneMappingExposure = 1.0;
            if (!state.videoSphere?.visible) {
                state.scene.background?.setHex(0x87ceeb);
                if (state.scene.fog) state.scene.fog.color.setHex(0x87ceeb);
            }
            break;
        case 'evening':
            state.sunLight.position.set(-60, 15, -30);
            state.sunLight.color.setHex(0xff7043);
            state.sunLight.intensity = 1.0;
            state.ambientLight.intensity = 0.2;
            state.renderer.toneMappingExposure = 0.7;
            if (!state.videoSphere?.visible) {
                state.scene.background?.setHex(0xff8a65);
                if (state.scene.fog) state.scene.fog.color.setHex(0xff8a65);
            }
            break;
    }
}

// =============================================================
//  Ground
// =============================================================
function setupGround() {
    const geo = new THREE.PlaneGeometry(500, 500, 1, 1);
    const uv = geo.attributes.uv;
    for (let i = 0; i < uv.count; i++) { uv.setXY(i, uv.getX(i) * 40, uv.getY(i) * 40); }

    const mat = new THREE.MeshStandardMaterial({ color: 0x4a7c59, roughness: 0.9, metalness: 0, transparent: true, opacity: 1 });
    state.groundMesh = new THREE.Mesh(geo, mat);
    state.groundMesh.rotation.x = -Math.PI / 2;
    state.groundMesh.position.y = -0.01;
    state.groundMesh.receiveShadow = true;
    state.scene.add(state.groundMesh);

    state.gridHelper = new THREE.GridHelper(200, 40, 0x000000, 0x333333);
    state.gridHelper.material.opacity = 0.15;
    state.gridHelper.material.transparent = true;
    state.scene.add(state.gridHelper);

    applyProceduralTexture('grass');
}

function generateProceduralTexture(type) {
    const size = currentQuality.textureSize;
    const canvas = document.createElement('canvas');
    canvas.width = size; canvas.height = size;
    const ctx = canvas.getContext('2d');

    if (type === 'grass') {
        ctx.fillStyle = '#4a7c3f'; ctx.fillRect(0, 0, size, size);
        for (let i = 0; i < 8000; i++) {
            const x = Math.random() * size, y = Math.random() * size;
            ctx.fillStyle = `rgb(${30 + Math.random() * 40}, ${80 + Math.random() * 80}, ${20 + Math.random() * 30})`;
            ctx.fillRect(x, y, 1 + Math.random() * 2, 3 + Math.random() * 6);
        }
    } else if (type === 'concrete') {
        ctx.fillStyle = '#999'; ctx.fillRect(0, 0, size, size);
        for (let i = 0; i < 20000; i++) {
            const x = Math.random() * size, y = Math.random() * size, v = 130 + Math.random() * 60;
            ctx.fillStyle = `rgb(${v},${v},${v})`; ctx.fillRect(x, y, 1, 1);
        }
    } else if (type === 'dirt') {
        ctx.fillStyle = '#8B6914'; ctx.fillRect(0, 0, size, size);
        for (let i = 0; i < 15000; i++) {
            const x = Math.random() * size, y = Math.random() * size;
            ctx.fillStyle = `rgb(${100 + Math.random() * 70}, ${60 + Math.random() * 50}, ${10 + Math.random() * 30})`;
            ctx.fillRect(x, y, 1 + Math.random() * 2, 1 + Math.random() * 2);
        }
    }
    return canvas;
}

function applyProceduralTexture(type) {
    const canvas = generateProceduralTexture(type);
    const texture = new THREE.CanvasTexture(canvas);
    texture.wrapS = THREE.RepeatWrapping; texture.wrapT = THREE.RepeatWrapping;
    texture.colorSpace = THREE.SRGBColorSpace;
    const mat = state.groundMesh.material;
    if (mat.map) mat.map.dispose();
    mat.map = texture; mat.color.setHex(0xffffff); mat.needsUpdate = true;
}

// =============================================================
//  360 Video
// =============================================================
function setup360Sphere() {
    const geo = new THREE.SphereGeometry(500, currentQuality.sphereW, currentQuality.sphereH);
    geo.scale(-1, 1, 1);
    const mat = new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0, side: THREE.FrontSide });
    state.videoSphere = new THREE.Mesh(geo, mat);
    state.videoSphere.visible = false;
    state.scene.add(state.videoSphere);
}

function loadVideoFromURL(url) {
    setLoading('Cargando video 360...');
    if (state.videoElement) { state.videoElement.pause(); state.videoElement.remove(); }
    if (state.videoTexture) { state.videoTexture.dispose(); }

    const video = document.createElement('video');
    video.src = url;
    video.crossOrigin = 'anonymous';
    video.loop = true; video.muted = true; video.playsInline = true; video.preload = 'auto';
    state.videoElement = video;

    video.addEventListener('loadeddata', () => {
        const texture = new THREE.VideoTexture(video);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.minFilter = THREE.LinearFilter; texture.magFilter = THREE.LinearFilter;
        texture.mapping = THREE.EquirectangularReflectionMapping;
        state.videoTexture = texture;

        state.videoSphere.material.map = texture;
        state.videoSphere.material.color.setHex(0xffffff);
        state.videoSphere.material.opacity = 1;
        state.videoSphere.material.needsUpdate = true;
        state.videoSphere.visible = true;
        state.scene.background = null;
        state.scene.fog = null;

        video.play().then(() => { state.videoPlaying = true; }).catch(() => {});
        checkAllLoaded();
    });
    video.addEventListener('error', () => { setLoading('Error cargando video.'); });
}

// =============================================================
//  Model Loading
// =============================================================
function getModelFormat(url) {
    const clean = url.split('?')[0].split('#')[0];
    const ext = clean.split('.').pop().toLowerCase();
    if (ext === 'fbx') return 'fbx';
    return 'gltf'; // glb and gltf both use GLTFLoader
}

function setupLoadedModel(model, animations) {
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

    // Compute world-space bounds after positioning
    const worldBox = new THREE.Box3().setFromObject(model);
    const worldSize = worldBox.getSize(new THREE.Vector3());
    const worldCenter = worldBox.getCenter(new THREE.Vector3());
    state.modelBounds = {
        min: { x: worldBox.min.x, y: worldBox.min.y, z: worldBox.min.z },
        max: { x: worldBox.max.x, y: worldBox.max.y, z: worldBox.max.z },
        size: { x: worldSize.x, y: worldSize.y, z: worldSize.z },
        center: { x: worldCenter.x, y: worldCenter.y, z: worldCenter.z },
    };

    if (animations?.length > 0) {
        state.modelMixer = new THREE.AnimationMixer(model);
        animations.forEach((clip) => state.modelMixer.clipAction(clip).play());
    }

    // Apply pending settings now that model is loaded
    if (pendingSettings) {
        applyModelSettings(pendingSettings);
        // Recompute bounds after settings (scale/elevation may have changed)
        updateModelBounds();
        pendingSettings = null;
    }

    // Process pending units now that model is loaded
    if (state.pendingUnits) {
        processRegisteredUnits();
        state.pendingUnits = null;
    }

    checkAllLoaded();
}

function onModelProgress(progress) {
    if (progress.lengthComputable) {
        const pct = Math.round((progress.loaded / progress.total) * 100);
        setLoading('Cargando modelo 3D... ' + pct + '%');
        setProgress(pct);
    }
}

function onModelError(err) {
    console.error('Error loading model:', err);
    setLoading('Error cargando modelo.');
}

function loadModelFromURL(url) {
    setLoading('Cargando modelo 3D...');
    if (state.currentModel) { state.scene.remove(state.currentModel); state.currentModel = null; state.modelMixer = null; }

    const format = getModelFormat(url);

    if (format === 'fbx') {
        const loader = new FBXLoader();
        loader.load(url, (group) => {
            setupLoadedModel(group, group.animations);
        }, onModelProgress, onModelError);
    } else {
        const loader = new GLTFLoader();
        const draco = new DRACOLoader();
        draco.setDecoderPath('https://cdn.jsdelivr.net/npm/three@0.162.0/examples/jsm/libs/draco/');
        loader.setDRACOLoader(draco);
        loader.load(url, (gltf) => {
            setupLoadedModel(gltf.scene, gltf.animations);
        }, onModelProgress, onModelError);
    }
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

// =============================================================
//  Apply Settings
// =============================================================
function applySettings(settings) {
    if (!settings) return;

    // Non-model settings (can apply immediately)
    const gh = parseFloat(settings.ground_height || 0) * 0.5;
    if (state.groundMesh) state.groundMesh.position.y = gh - 0.01;
    if (state.gridHelper) state.gridHelper.position.y = gh;

    if (settings.ground_texture_type) applyProceduralTexture(settings.ground_texture_type);
    if (state.groundMesh) state.groundMesh.material.opacity = parseFloat(settings.ground_opacity ?? 100) / 100;

    const gv = settings.ground_visible;
    if (gv !== undefined && gv !== null) {
        const visible = gv === true || gv === 1 || gv === '1';
        state.groundMesh.visible = visible;
        state.gridHelper.visible = visible;
    }

    if (state.videoSphere && settings.video_opacity !== undefined) {
        state.videoSphere.material.opacity = parseFloat(settings.video_opacity) / 100;
    }

    if (settings.lighting_preset) setLightingPreset(settings.lighting_preset);

    // Camera
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

    // Model settings - defer if model not loaded yet
    if (state.currentModel) {
        applyModelSettings(settings);
        updateModelBounds();
    } else {
        pendingSettings = settings;
    }
}

function applyModelSettings(settings) {
    if (!state.currentModel) return;
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
    if (settings.wireframe) {
        model.traverse((child) => { if (child.isMesh) child.material.wireframe = true; });
    }
}

// =============================================================
//  Loading UI
// =============================================================
let loadChecks = { video: null, model: null };

function setLoading(text) {
    const el = document.getElementById('loading-text');
    if (el) el.textContent = text;
}

function setProgress(pct) {
    let bar = document.getElementById('loading-progress');
    if (!bar) {
        // Create progress bar under loading text
        const textEl = document.getElementById('loading-text');
        if (!textEl) return;
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'width: 200px; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-top: 12px; overflow: hidden;';
        bar = document.createElement('div');
        bar.id = 'loading-progress';
        bar.style.cssText = 'height: 100%; background: #4fc3f7; border-radius: 2px; transition: width 0.3s ease; width: 0%;';
        wrapper.appendChild(bar);
        textEl.parentNode.insertBefore(wrapper, textEl.nextSibling);
    }
    bar.style.width = pct + '%';
}

function checkAllLoaded() {
    const overlay = document.getElementById('loading-overlay');
    if (!overlay) return;

    const videoOK = loadChecks.video === null || state.videoSphere?.visible;
    const modelOK = loadChecks.model === null || state.currentModel;

    if (videoOK && modelOK) {
        overlay.style.transition = 'opacity 0.5s';
        overlay.style.opacity = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 500);
        // Start FPS monitoring
        state.fpsTime = performance.now();
        // Analytics: model loaded
        if (window.viewerAnalytics) {
            window.viewerAnalytics.track('model_loaded', { quality: qualityTier });
        }
    }
}

// =============================================================
//  Highlight Marker
// =============================================================
function createHighlightMarker() {
    const geo = new THREE.RingGeometry(1.5, 2, 32);
    const mat = new THREE.MeshBasicMaterial({
        color: 0x4fc3f7, transparent: true, opacity: 0.8, side: THREE.DoubleSide
    });
    const mesh = new THREE.Mesh(geo, mat);
    mesh.rotation.x = -Math.PI / 2; // horizontal
    return mesh;
}

function showHighlightAt(x, y, z) {
    if (!state.highlightMarker) {
        state.highlightMarker = createHighlightMarker();
    }
    state.highlightMarker.position.set(x, y, z);
    state.highlightMarker.scale.set(1, 1, 1);
    state.highlightMarker.material.opacity = 0.8;
    if (!state.highlightMarker.parent) {
        state.scene.add(state.highlightMarker);
    }
}

function removeHighlight() {
    if (state.highlightMarker && state.highlightMarker.parent) {
        state.scene.remove(state.highlightMarker);
    }
}

// =============================================================
//  Model transparency
// =============================================================
function setModelTransparency(opacity) {
    if (!state.currentModel) return;
    state.currentModel.traverse((child) => {
        if (child.isMesh) {
            child.material.transparent = true;
            child.material.opacity = opacity;
            child.material.needsUpdate = true;
        }
    });
}

function restoreModelOpacity() {
    if (!state.currentModel) return;
    state.currentModel.traverse((child) => {
        if (child.isMesh) {
            child.material.opacity = 1.0;
            child.material.transparent = false;
            child.material.needsUpdate = true;
        }
    });
}

// =============================================================
//  Unit Bounding Boxes
// =============================================================
const STATUS_COLORS = {
    available: 0x4caf50,  // green
    reserved: 0xffc107,   // yellow
    sold: 0xf44336,       // red
};

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

function createUnitBoxMesh(unit) {
    const w = bboxToWorld(unit.bbox);
    if (!w) return null;

    const geo = new THREE.BoxGeometry(w.sx, w.sy, w.sz);
    const color = STATUS_COLORS[unit.status] || 0x4caf50;
    const mat = new THREE.MeshBasicMaterial({
        color,
        transparent: true,
        opacity: 0.0,
        depthTest: true,
    });
    const mesh = new THREE.Mesh(geo, mat);
    mesh.position.set(w.cx, w.cy, w.cz);
    mesh.visible = false;
    mesh.userData.unitId = unit.id;
    mesh.userData.status = unit.status;

    // Edge wireframe
    const edges = new THREE.EdgesGeometry(geo);
    const lineMat = new THREE.LineBasicMaterial({ color, transparent: true, opacity: 0.0 });
    const wireframe = new THREE.LineSegments(edges, lineMat);
    mesh.add(wireframe);
    mesh.userData.wireframe = wireframe;

    return mesh;
}

function processRegisteredUnits() {
    const units = state.unitData;
    if (!units.length || !state.modelBounds) return;

    // Clean up old boxes
    state.unitBoxes.forEach(mesh => {
        state.scene.remove(mesh);
        mesh.geometry.dispose();
        mesh.material.dispose();
    });
    state.unitBoxes.clear();

    units.forEach(unit => {
        if (!unit.bbox) return;
        const mesh = createUnitBoxMesh(unit);
        if (mesh) {
            state.scene.add(mesh);
            state.unitBoxes.set(unit.id, mesh);
        }
    });
}

function focusOnUnitBox(unitId) {
    const mesh = state.unitBoxes.get(unitId);
    if (!mesh) return false;

    // Save original camera position on first call
    if (!state.origCameraPos) {
        state.origCameraPos = state.camera.position.clone();
        state.origTarget = state.controls.target.clone();
    }

    // Hide previous selected box
    if (state.selectedUnitBox && state.selectedUnitBox !== mesh) {
        state.selectedUnitBox.visible = false;
        state.selectedUnitBox.material.opacity = 0.0;
        state.selectedUnitBox.userData.wireframe.material.opacity = 0.0;
    }

    // Show and highlight this box
    mesh.visible = true;
    mesh.material.opacity = 0.35;
    mesh.userData.wireframe.material.opacity = 0.9;
    state.selectedUnitBox = mesh;

    // Remove highlight marker if present
    removeHighlight();

    // Animate camera to look directly at the unit from outside the model
    const pos = mesh.position;
    const bounds = state.modelBounds;

    // Direction from model center to the unit (outward)
    const dx = pos.x - bounds.center.x;
    const dz = pos.z - bounds.center.z;
    const len = Math.sqrt(dx * dx + dz * dz) || 1;
    const dirX = dx / len;
    const dirZ = dz / len;

    // Target: the unit itself
    const targetPos = new THREE.Vector3(pos.x, pos.y, pos.z);

    // Camera: outside the model, along the unit's outward direction
    const distance = Math.max(bounds.size.z, bounds.size.x) * 1.8;
    const camX = pos.x + dirX * distance;
    const camZ = pos.z + dirZ * distance;
    const camY = pos.y + bounds.size.y * 0.08;
    const cameraPos = new THREE.Vector3(camX, camY, camZ);

    state.animTarget = targetPos;
    state.animCamera = cameraPos;
    state.animating = true;

    return true;
}

// =============================================================
//  Viewer API (exposed globally)
// =============================================================
window.viewerAPI = {
    focusOnFloor(floor, totalFloors) {
        const bounds = state.modelBounds;
        if (!bounds) return;

        // Save original camera position on first call
        if (!state.origCameraPos) {
            state.origCameraPos = state.camera.position.clone();
            state.origTarget = state.controls.target.clone();
        }

        // Estimate Y position for this floor
        const floorY = bounds.min.y + ((floor + 0.5) / totalFloors) * bounds.size.y;

        // Use current camera angle so view stays natural
        const dx = state.camera.position.x - bounds.center.x;
        const dz = state.camera.position.z - bounds.center.z;
        const angle = Math.atan2(dx, dz);

        // Target: surface of the model facing the camera, at floor height
        const targetX = bounds.center.x + Math.sin(angle) * (bounds.size.x * 0.5);
        const targetZ = bounds.center.z + Math.cos(angle) * (bounds.size.z * 0.5);
        const targetPos = new THREE.Vector3(targetX, floorY, targetZ);

        // Camera: outside the model at floor height, looking at the facade
        const distance = Math.max(bounds.size.z, bounds.size.x) * 1.8;
        const camX = bounds.center.x + Math.sin(angle) * distance;
        const camZ = bounds.center.z + Math.cos(angle) * distance;
        const camY = floorY + bounds.size.y * 0.08;
        const cameraPos = new THREE.Vector3(camX, camY, camZ);

        state.animTarget = targetPos;
        state.animCamera = cameraPos;
        state.animating = true;

        showHighlightAt(targetX, floorY, targetZ);
    },

    focusOnUnit(unitId) {
        return focusOnUnitBox(unitId);
    },

    registerUnits(units) {
        state.unitData = units;
        if (state.modelBounds) {
            processRegisteredUnits();
        } else {
            state.pendingUnits = true;
        }
    },

    clearHighlight() {
        removeHighlight();
        // Hide selected unit box
        if (state.selectedUnitBox) {
            state.selectedUnitBox.visible = false;
            state.selectedUnitBox.material.opacity = 0.0;
            state.selectedUnitBox.userData.wireframe.material.opacity = 0.0;
            state.selectedUnitBox = null;
        }
        // Animate back to original position
        if (state.origCameraPos && state.origTarget) {
            state.animCamera = state.origCameraPos.clone();
            state.animTarget = state.origTarget.clone();
            state.animating = true;
        }
    },

    isReady() {
        return !!state.currentModel;
    }
};

// =============================================================
//  Render Loop
// =============================================================
function animate() {
    requestAnimationFrame(animate);
    const delta = state.clock.getDelta();
    const elapsed = state.clock.elapsedTime;

    // Camera animation lerp
    if (state.animating && state.animTarget && state.animCamera) {
        const lerpFactor = 0.08;

        state.controls.target.lerp(state.animTarget, lerpFactor);
        state.camera.position.lerp(state.animCamera, lerpFactor);

        const distTarget = state.controls.target.distanceTo(state.animTarget);
        const distCamera = state.camera.position.distanceTo(state.animCamera);
        if (distTarget < 0.1 && distCamera < 0.1) {
            state.animating = false;
        }
    }

    // Pulse highlight marker
    if (state.highlightMarker && state.highlightMarker.parent) {
        const pulse = 1 + 0.15 * Math.sin(elapsed * 3);
        state.highlightMarker.scale.set(pulse, pulse, pulse);
        state.highlightMarker.material.opacity = 0.6 + 0.2 * Math.sin(elapsed * 2);
        state.highlightMarker.rotation.z = elapsed * 0.5;
    }

    // Pulse selected unit box
    if (state.selectedUnitBox && state.selectedUnitBox.visible) {
        state.selectedUnitBox.material.opacity = 0.25 + 0.15 * Math.sin(elapsed * 2);
    }

    // Prevent camera from entering the model bounding box
    if (state.modelBounds) {
        const b = state.modelBounds;
        const pad = 2;
        const cam = state.camera.position;
        const cx = cam.x, cy = cam.y, cz = cam.z;

        if (cx > b.min.x - pad && cx < b.max.x + pad &&
            cy > b.min.y - pad && cy < b.max.y + pad &&
            cz > b.min.z - pad && cz < b.max.z + pad) {
            // Camera is inside padded bbox — push it outward
            const dx = cx - b.center.x;
            const dz = cz - b.center.z;
            const len = Math.sqrt(dx * dx + dz * dz) || 1;
            const halfX = b.size.x * 0.5 + pad;
            const halfZ = b.size.z * 0.5 + pad;
            const escapeD = Math.max(halfX, halfZ) + 1;
            cam.x = b.center.x + (dx / len) * escapeD;
            cam.z = b.center.z + (dz / len) * escapeD;
        }

        // Keep camera above ground
        if (cam.y < 1) cam.y = 1;
    }

    // FPS monitoring — auto-downgrade if performance is poor
    if (state.fpsTime && !state.fpsDowngraded) {
        state.fpsFrames++;
        const now = performance.now();
        if (now - state.fpsTime >= 3000) {
            const avgFps = state.fpsFrames / ((now - state.fpsTime) / 1000);
            if (avgFps < 20 && currentQuality.shadows) {
                // Downgrade quality
                state.renderer.shadowMap.enabled = false;
                state.sunLight.castShadow = false;
                state.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1));
                state.fpsDowngraded = true;
                // Notify user briefly
                const note = document.getElementById('quality-note');
                if (note) { note.style.display = 'block'; setTimeout(() => note.style.display = 'none', 4000); }
            }
            state.fpsTime = 0; // stop monitoring
        }
    }

    state.controls.update();
    if (state.modelMixer) state.modelMixer.update(delta);
    state.renderer.render(state.scene, state.camera);
}

// =============================================================
//  Boot
// =============================================================
function boot() {
    init();

    const dataEl = document.getElementById('project-data');
    if (!dataEl) return;

    const data = JSON.parse(dataEl.textContent);

    // Track what needs loading
    if (data.files?.video_360) loadChecks.video = 'pending';
    if (data.files?.model_3d) loadChecks.model = 'pending';

    // Apply settings first (non-model ones apply immediately)
    applySettings(data.settings);

    // Load files
    if (data.files?.video_360) loadVideoFromURL(data.files.video_360);
    if (data.files?.model_3d) loadModelFromURL(data.files.model_3d);

    // If nothing to load, hide overlay
    if (!data.files?.video_360 && !data.files?.model_3d) {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.style.display = 'none';
    }
}

boot();
