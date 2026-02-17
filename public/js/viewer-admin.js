import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';
import { FBXLoader } from 'three/addons/loaders/FBXLoader.js';

const state = {
    scene: null, camera: null, renderer: null, controls: null, clock: new THREE.Clock(),
    videoElement: null, videoTexture: null, videoSphere: null, videoPlaying: false,
    currentModel: null, modelMixer: null,
    sunLight: null, ambientLight: null, hemisphereLight: null,
    gridHelper: null, groundMesh: null,
};

let pendingSettings = null;

// =============================================================
//  Init
// =============================================================
function init() {
    const container = document.getElementById('viewer-container');
    const placeholder = document.getElementById('viewer-placeholder');
    if (placeholder) placeholder.style.display = 'none';

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
    setup360Sphere();

    // Resize observer for container
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
    state.sunLight.castShadow = true;
    state.sunLight.shadow.mapSize.width = 2048;
    state.sunLight.shadow.mapSize.height = 2048;
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
    const size = 512;
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
    const geo = new THREE.SphereGeometry(500, 64, 32);
    geo.scale(-1, 1, 1);
    const mat = new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0, side: THREE.FrontSide });
    state.videoSphere = new THREE.Mesh(geo, mat);
    state.videoSphere.visible = false;
    state.scene.add(state.videoSphere);
}

function loadVideoFromURL(url) {
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
    });
    video.addEventListener('error', () => { console.error('Error loading admin preview video'); });
}

// =============================================================
//  Model Loading
// =============================================================
function getModelFormat(url) {
    const clean = url.split('?')[0].split('#')[0];
    const ext = clean.split('.').pop().toLowerCase();
    if (ext === 'fbx') return 'fbx';
    return 'gltf';
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

    if (animations?.length > 0) {
        state.modelMixer = new THREE.AnimationMixer(model);
        animations.forEach((clip) => state.modelMixer.clipAction(clip).play());
    }

    if (pendingSettings) {
        applyModelSettings(pendingSettings);
        pendingSettings = null;
    }
}

function loadModelFromURL(url) {
    if (state.currentModel) { state.scene.remove(state.currentModel); state.currentModel = null; state.modelMixer = null; }

    const format = getModelFormat(url);

    if (format === 'fbx') {
        const loader = new FBXLoader();
        loader.load(url, (group) => {
            setupLoadedModel(group, group.animations);
        }, undefined, (err) => { console.error('Error loading model:', err); });
    } else {
        const loader = new GLTFLoader();
        const draco = new DRACOLoader();
        draco.setDecoderPath('https://cdn.jsdelivr.net/npm/three@0.162.0/examples/jsm/libs/draco/');
        loader.setDRACOLoader(draco);
        loader.load(url, (gltf) => {
            setupLoadedModel(gltf.scene, gltf.animations);
        }, undefined, (err) => { console.error('Error loading model:', err); });
    }
}

// =============================================================
//  Apply Settings
// =============================================================
function applySettings(settings) {
    if (!settings) return;

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

    if (state.currentModel) {
        applyModelSettings(settings);
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
//  Bind sliders for real-time preview
// =============================================================
function bindSliders() {
    const sliders = {
        's-model-rotation': 'model_rotation',
        's-model-scale': 'model_scale',
        's-model-elevation': 'model_elevation',
        's-ground-height': 'ground_height',
        's-ground-opacity': 'ground_opacity',
        's-video-opacity': 'video_opacity',
    };

    for (const [id, key] of Object.entries(sliders)) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => {
                applySettings({ [key]: parseFloat(el.value) });
            });
        }
    }

    const texSelect = document.getElementById('s-ground-texture');
    if (texSelect) {
        texSelect.addEventListener('change', () => {
            applySettings({ ground_texture_type: texSelect.value });
        });
    }

    const lightSelect = document.getElementById('s-lighting');
    if (lightSelect) {
        lightSelect.addEventListener('change', () => {
            applySettings({ lighting_preset: lightSelect.value });
        });
    }

    const groundCheck = document.getElementById('s-ground-visible');
    if (groundCheck) {
        groundCheck.addEventListener('change', () => {
            applySettings({ ground_visible: groundCheck.checked });
        });
    }
}

// =============================================================
//  Render Loop
// =============================================================
function animate() {
    requestAnimationFrame(animate);
    const delta = state.clock.getDelta();
    state.controls.update();
    if (state.modelMixer) state.modelMixer.update(delta);
    state.renderer.render(state.scene, state.camera);
}

// =============================================================
//  Boot
// =============================================================
function boot() {
    const dataEl = document.getElementById('project-data');
    if (!dataEl) return;

    const data = JSON.parse(dataEl.textContent);

    // Only init viewer if there are files to show
    if (!data.files?.video_360 && !data.files?.model_3d) return;

    init();
    applySettings(data.settings);
    bindSliders();

    if (data.files.video_360) loadVideoFromURL(data.files.video_360);
    if (data.files.model_3d) loadModelFromURL(data.files.model_3d);
}

boot();
