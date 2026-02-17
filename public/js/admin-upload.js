// Chunked file upload handler for admin project edit page
document.addEventListener('DOMContentLoaded', function () {
    const projectData = JSON.parse(document.getElementById('project-data').textContent);
    const csrfToken = projectData.csrf;
    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB

    document.querySelectorAll('.upload-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const fileType = this.dataset.fileType;
            const progressContainer = this.parentElement.querySelector('.upload-progress');
            const progressBar = progressContainer ? progressContainer.querySelector('div > div') : null;

            uploadFile(file, fileType, progressContainer, progressBar);
        });
    });

    async function uploadFile(file, fileType, progressContainer, progressBar) {
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

        // Show progress
        if (progressContainer) {
            progressContainer.classList.remove('hidden');
            if (progressBar) progressBar.style.width = '0%';
        }

        try {
            // 1. Init upload
            const initRes = await fetch(projectData.routes.upload_init, {
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
                const err = await initRes.text();
                throw new Error('Init failed: ' + err);
            }

            const { upload_id } = await initRes.json();

            // 2. Upload chunks
            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const chunk = file.slice(start, start + CHUNK_SIZE);
                const formData = new FormData();
                formData.append('upload_id', upload_id);
                formData.append('chunk_index', i);
                formData.append('chunk', chunk);

                const chunkRes = await fetch(projectData.routes.upload_chunk, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!chunkRes.ok) {
                    const err = await chunkRes.text();
                    throw new Error('Chunk ' + i + ' failed: ' + err);
                }

                // Update progress
                const pct = Math.round(((i + 1) / totalChunks) * 100);
                if (progressBar) progressBar.style.width = pct + '%';
            }

            // 3. Complete upload
            const completeRes = await fetch(projectData.routes.upload_complete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ upload_id: upload_id }),
            });

            if (!completeRes.ok) {
                const err = await completeRes.text();
                throw new Error('Complete failed: ' + err);
            }

            const result = await completeRes.json();
            alert('Archivo subido correctamente: ' + file.name);

            // Reload page to show updated file info
            window.location.reload();

        } catch (err) {
            console.error('Upload error:', err);
            alert('Error subiendo archivo: ' + err.message);
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.backgroundColor = '#ef4444';
            }
        }
    }

    // Save settings button
    const saveBtn = document.getElementById('btn-save-settings');
    if (saveBtn) {
        saveBtn.addEventListener('click', async function () {
            const settings = {
                model_rotation: parseFloat(document.getElementById('s-model-rotation').value),
                model_scale: parseFloat(document.getElementById('s-model-scale').value),
                model_elevation: parseFloat(document.getElementById('s-model-elevation').value),
                ground_height: parseFloat(document.getElementById('s-ground-height').value),
                ground_opacity: parseFloat(document.getElementById('s-ground-opacity').value),
                video_opacity: parseFloat(document.getElementById('s-video-opacity').value),
                ground_texture_type: document.getElementById('s-ground-texture').value,
                lighting_preset: document.getElementById('s-lighting').value,
                ground_visible: document.getElementById('s-ground-visible').checked ? 1 : 0,
            };

            try {
                const res = await fetch(projectData.routes.settings_update, {
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
