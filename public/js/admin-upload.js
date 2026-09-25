// Chunked file upload handler for admin project edit page
document.addEventListener('DOMContentLoaded', function () {
    const projectData = JSON.parse(document.getElementById('project-data').textContent);
    const csrfToken = projectData.csrf;
    const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunks for reliability
    const MAX_RETRIES = 3;
    var uploading = false;

    // Prevent page navigation while uploading
    window.addEventListener('beforeunload', function (e) {
        if (uploading) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Block all form submissions while uploading
    document.addEventListener('submit', function (e) {
        if (uploading) {
            e.preventDefault();
            alert('Espera a que termine la subida del archivo antes de guardar.');
        }
    }, true);

    document.querySelectorAll('.upload-input').forEach(function (input) {
        input.addEventListener('change', function (e) {
            // Prevent any bubbling that could trigger form submit
            e.stopPropagation();

            var file = this.files[0];
            if (!file) return;

            var fileType = this.dataset.fileType;
            var progressContainer = this.parentElement.querySelector('.upload-progress');
            var progressBar = progressContainer ? progressContainer.querySelector('div > div') : null;

            // Disable input during upload
            this.disabled = true;
            var self = this;
            uploadFile(file, fileType, progressContainer, progressBar).finally(function () {
                self.disabled = false;
            });
        });
    });

    async function sendChunkWithRetry(formData, attempt) {
        attempt = attempt || 1;
        try {
            var res = await fetch(projectData.routes.upload_chunk, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!res.ok) {
                var err = await res.text().catch(function () { return 'Unknown error'; });
                throw new Error('HTTP ' + res.status + ': ' + err);
            }
            return res;
        } catch (err) {
            if (attempt < MAX_RETRIES) {
                // Wait before retry (1s, 2s, 3s)
                await new Promise(function (r) { setTimeout(r, attempt * 1000); });
                return sendChunkWithRetry(formData, attempt + 1);
            }
            throw err;
        }
    }

    async function uploadFile(file, fileType, progressContainer, progressBar) {
        var totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        uploading = true;

        // Show progress
        if (progressContainer) {
            progressContainer.classList.remove('hidden');
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.style.backgroundColor = '';
            }
        }

        try {
            // 1. Init upload
            var initRes = await fetch(projectData.routes.upload_init, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    file_type: fileType,
                    original_name: file.name,
                    total_size: file.size,
                    total_chunks: totalChunks,
                }),
            });

            if (!initRes.ok) {
                var err = await initRes.text();
                throw new Error('Init failed: ' + err);
            }

            var data = await initRes.json();
            var upload_id = data.upload_id;

            // 2. Upload chunks sequentially with retry
            for (var i = 0; i < totalChunks; i++) {
                var start = i * CHUNK_SIZE;
                var chunk = file.slice(start, start + CHUNK_SIZE);
                var formData = new FormData();
                formData.append('upload_id', upload_id);
                formData.append('chunk_index', i);
                formData.append('chunk', chunk, file.name);

                await sendChunkWithRetry(formData);

                // Update progress
                var pct = Math.round(((i + 1) / totalChunks) * 100);
                if (progressBar) progressBar.style.width = pct + '%';
            }

            // 3. Complete upload
            var completeRes = await fetch(projectData.routes.upload_complete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ upload_id: upload_id }),
            });

            if (!completeRes.ok) {
                var errText = await completeRes.text();
                throw new Error('Complete failed: ' + errText);
            }

            uploading = false;
            alert('Archivo subido correctamente: ' + file.name);

            // Reload page to show updated file info
            window.location.reload();

        } catch (err) {
            uploading = false;
            console.error('Upload error:', err);
            alert('Error subiendo archivo: ' + (err.message || err));
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.backgroundColor = '#ef4444';
            }
        }
    }

    // Save settings button
    var saveBtn = document.getElementById('btn-save-settings');
    if (saveBtn) {
        saveBtn.addEventListener('click', async function () {
            if (uploading) {
                alert('Espera a que termine la subida del archivo.');
                return;
            }

            var settings = {
                model_rotation: parseFloat(document.getElementById('s-model-rotation').value),
                model_scale: parseFloat(document.getElementById('s-model-scale').value),
                model_elevation: parseFloat(document.getElementById('s-model-elevation').value),
                ground_height: parseFloat(document.getElementById('s-ground-height').value),
                ground_opacity: parseFloat(document.getElementById('s-ground-opacity').value),
                video_opacity: parseFloat(document.getElementById('s-video-opacity').value),
                background_type: document.getElementById('s-background-type').value,
                ground_texture_type: document.getElementById('s-ground-texture').value,
                lighting_preset: document.getElementById('s-lighting').value,
                ground_visible: document.getElementById('s-ground-visible').checked ? 1 : 0,
                real_scale_enabled: document.getElementById('s-real-scale-enabled').checked ? 1 : 0,
                real_dimension_meters: parseFloat(document.getElementById('s-real-dimension-meters').value) || null,
                reference_axis: document.getElementById('s-reference-axis').value,
            };

            // Include camera position if viewer is active
            if (window.getViewerCameraState) {
                var cam = window.getViewerCameraState();
                if (cam) Object.assign(settings, cam);
            }

            try {
                var res = await fetch(projectData.routes.settings_update, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(settings),
                });

                if (!res.ok) throw new Error(await res.text());

                alert('Settings guardados correctamente.');
            } catch (err) {
                console.error('Settings error:', err);
                alert('Error guardando settings: ' + err.message);
            }
        });
    }
});
