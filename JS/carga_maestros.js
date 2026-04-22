        // ───────────────────────────────────────────── php
        //  CONFIGURACIÓN
        // ─────────────────────────────────────────────
        const config = {
            uset: {
                title: 'Maestros USET',
                subtitle: 'Carga de datos del sistema USET',
                icon: 'fa-server',
                color: '#1976d2',
                steps: 4,
                names: ['Centro de Trabajo', 'Cheque Concepto', 'Empleado', 'Empleado Plaza'],
                endpoint: 'Servicios/upload_uset.php',
                fileKeys: ['centro_trabajo', 'cheque_cpto', 'empleado', 'empleado_plaza']
            },
            sepe: {
                title: 'Maestros SEPE',
                subtitle: 'Carga de datos del sistema SEPE',
                icon: 'fa-building-columns',
                color: '#7b1fa2',
                steps: 5,
                names: ['Catálogo Centro de Trabajo', 'Cheque Concepto', 'Empleado', 'Nivel Desconcentrados', 'Empleado Plaza'],
                endpoint: 'Servicios/upload_sepe.php',
                fileKeys: ['cg_ct', 'cheque_cpto', 'empleado', 'niv_desconcentrados', 'vemp_plaza']
            },
            autos: {
                title: 'Ganadores de Autos',
                subtitle: 'Lista de ganadores del sorteo',
                icon: 'fa-car',
                color: '#388e3c',
                steps: 1,
                names: ['Lista de Ganadores'],
                endpoint: 'Servicios/upload_autos.php',
                fileKeys: ['ganadores']
            },
            comisionados: {
                title: 'Comisionados',
                subtitle: 'Lista de comisionados',
                icon: 'fa-user-tie',
                color: '#f57c00',
                steps: 1,
                names: ['Lista de Comisionados'],
                endpoint: 'Servicios/upload_comisionados.php',
                fileKeys: ['comisionados']
            }
        };

        const state = {
            currentType: null,
            files: {
                uset: [null, null, null, null],
                sepe: [null, null, null, null, null],
                autos: [null],
                comisionados: [null]
            },
            datosPrueba1: {
                uset: false,
                sepe: false,
                autos: false,
                comisionados: false
            },
            verificado: false
        };

        let currentDrag = null;
        let pendingSection = null;

        // ─────────────────────────────────────────────
        //  VERIFICACIÓN DE DATOS
        // ─────────────────────────────────────────────php
        async function verificarDatosPrueba1() {
            try {
                const response = await fetch('Servicios/verificar_datos.php');
                const data = await response.json();
                if (data.success) {
                    state.datosPrueba1 = data.tablas || {};
                    state.verificado = true;
                    actualizarEstadoGenerar();
                }
                return data;
            } catch (error) {
                console.error('Error verificando datos:', error);
                return { success: false, error: error.message };
            }
        }

        // Llamado desde el botón "Verificar Datos" dentro de la sección generar
        async function verificarYActualizar() {
            const btn = document.getElementById('btn-verificar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';

            // Mostrar estado de carga en el panel
            document.getElementById('lista-verificacion').innerHTML = `
                    <div class="verif-loading">
                        <i class="fa-solid fa-spinner fa-spin text-purple-600 text-xl"></i>
                        <span class="text-gray-600 text-sm">Consultando base de datos...</span>
                    </div>`;

            await verificarDatosPrueba1();
            cargarPanelVerificacion();

            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Verificar Datos';
        }

        function actualizarEstadoGenerar() {
            const puedeGenerar = state.datosPrueba1.uset && state.datosPrueba1.sepe;

            const generarItem = document.getElementById('generar-item');
            const statusIndicator = document.getElementById('status-generar');

            if (puedeGenerar) {
                generarItem.classList.remove('disabled');
                generarItem.setAttribute('data-tooltip', 'Click para generar tabla final');
                generarItem.onclick = function () { mostrarGenerar(); return false; };
                statusIndicator.className = 'status-indicator status-ready';
                statusIndicator.title = 'Datos listos';
            } else {
                generarItem.classList.add('disabled');
                generarItem.setAttribute('data-tooltip', 'Faltan datos de USET o SEPE');
                generarItem.onclick = function () {
                    showToast('Debe cargar los datos de USET y SEPE primero', 'error');
                    return false;
                };
                statusIndicator.className = 'status-indicator status-pending';
                statusIndicator.title = 'Faltan datos';
            }

            // Mostrar/ocultar botón generar dentro de la sección
            const btnGenerar = document.getElementById('btn-generar-final');
            if (btnGenerar) {
                if (puedeGenerar && state.verificado) {
                    btnGenerar.classList.remove('hidden');
                } else {
                    btnGenerar.classList.add('hidden');
                }
            }

            return puedeGenerar;
        }

        // ─────────────────────────────────────────────
        //  NAVEGACIÓN
        // ─────────────────────────────────────────────
        function mostrarCarga() {
            document.getElementById('main-carga').classList.remove('hidden');
            document.getElementById('main-generar').classList.add('hidden');
        }

        function mostrarGenerar() {
            document.getElementById('main-carga').classList.add('hidden');
            document.getElementById('main-generar').classList.remove('hidden');

            // Resetear panel y botón generar al entrar
            document.getElementById('btn-generar-final').classList.add('hidden');
            document.getElementById('panel-progreso').classList.add('hidden');
            document.getElementById('lista-verificacion').innerHTML = `
                    <div class="verif-loading">
                        <i class="fa-solid fa-circle-info text-purple-600 text-xl"></i>
                        <span class="text-gray-600 text-sm">Haga clic en <strong>Verificar Datos</strong> para comprobar el estado de las tablas.</span>
                    </div>`;
            state.verificado = false;
        }

        // ─────────────────────────────────────────────
        //  PANEL DE VERIFICACIÓN
        // ─────────────────────────────────────────────
        function cargarPanelVerificacion() {
            const lista = document.getElementById('lista-verificacion');
            const tablas = [
                { key: 'uset', nombre: 'Maestros USET', icon: 'fa-server', color: 'blue' },
                { key: 'sepe', nombre: 'Maestros SEPE', icon: 'fa-building-columns', color: 'purple' },
                { key: 'autos', nombre: 'Ganadores Autos', icon: 'fa-car', color: 'green' },
                { key: 'comisionados', nombre: 'Comisionados', icon: 'fa-user-tie', color: 'orange' }
            ];

            let html = '';
            tablas.forEach(t => {
                const ok = state.datosPrueba1[t.key];
                const rowClass = ok ? 'ok' : 'fail';
                const icon = ok ? 'fa-check-circle' : 'fa-circle-xmark';
                const textColor = ok ? 'text-green-600' : 'text-red-600';
                const label = ok ? 'Datos cargados' : 'Sin datos';

                html += `
                        <div class="verif-row ${rowClass}">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid ${t.icon} text-${t.color}-600"></i>
                                <span class="font-medium text-gray-700">${t.nombre}</span>
                            </div>
                            <div class="flex items-center gap-2 ${textColor}">
                                <i class="fa-solid ${icon}"></i>
                                <span class="text-sm font-medium">${label}</span>
                            </div>
                        </div>`;
            });

            // Mensaje resumen — una sola vez al final
            const puedeGenerar = state.datosPrueba1.uset && state.datosPrueba1.sepe;
            if (!puedeGenerar) {
                html += `
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
                            <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                            <strong>Atención:</strong> Se requieren datos de USET y SEPE para generar la tabla final.
                        </div>`;
            } else {
                html += `
                        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded text-green-800 text-sm">
                            <i class="fa-solid fa-check-circle mr-2"></i>
                            <strong>Listo:</strong> Puede proceder a generar la tabla final de sorteo.
                        </div>`;
            }

            lista.innerHTML = html;
        }

        // ─────────────────────────────────────────────
        //  MODAL GENERAR TABLA FINAL
        // ─────────────────────────────────────────────
        function verificarYGenerar() {
            showToast('Debe cargar los datos de USET y SEPE antes de generar la tabla final', 'error');
        }

        function mostrarModalGenerar() {
            const modal = document.getElementById('generar-modal');
            const statusSpan = document.getElementById('verificacion-status');
            const btnIniciar = document.getElementById('btn-iniciar-generar');

            const ok = state.datosPrueba1.uset && state.datosPrueba1.sepe;

            if (ok) {
                statusSpan.innerHTML = '<i class="fa-solid fa-check-circle text-green-600 mr-1"></i> Datos verificados correctamente';
                btnIniciar.disabled = false;
            } else {
                statusSpan.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-red-600 mr-1"></i> Faltan datos requeridos (USET y/o SEPE)';
                btnIniciar.disabled = true;
            }

            modal.style.display = 'flex';
        }

        function cerrarModalGenerar() {
            document.getElementById('generar-modal').style.display = 'none';
        }

        async function iniciarGeneracion() {
            cerrarModalGenerar();

            const panelProgreso = document.getElementById('panel-progreso');
            const pasosContainer = document.getElementById('pasos-generacion');

            panelProgreso.classList.remove('hidden');
            pasosContainer.innerHTML = `
                    <div class="text-center py-6">
                        <i class="fa-solid fa-spinner fa-spin text-2xl text-purple-700"></i>
                        <p class="mt-2 text-gray-600 text-sm">Generando tabla final, por favor espere...</p>
                    </div>`;

            try {
                const response = await fetch('Servicios/generar_sorteo.php');
                const data = await response.json();

                if (data.success) {
                    let html = '';
                    (data.steps || []).forEach((step, idx) => {
                        const icon = step.success ? 'fa-check text-green-500' : 'fa-xmark text-red-500';
                        const bg = step.success ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                        html += `
                                <div class="flex items-center gap-3 p-3 ${bg} border rounded">
                                    <i class="fa-solid ${icon}"></i>
                                    <span class="text-sm text-gray-700 flex-1">Paso ${idx + 1}: ${step.message}</span>
                                </div>`;
                    });

                    html += `
                            <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded text-center">
                                <h4 class="font-bold text-purple-800 text-lg">¡Proceso Completado!</h4>
                                <p class="text-purple-700 mt-1">
                                    Total de participantes en sorteo:
                                    <strong>${data.total_final ?? 'N/A'}</strong>
                                </p>
                            </div>`;

                    pasosContainer.innerHTML = html;
                    showToast('Tabla final generada correctamente', 'success');
                } else {
                    pasosContainer.innerHTML = `
                            <div class="p-4 bg-red-50 border border-red-200 rounded text-red-700">
                                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                                Error: ${data.message || 'Error desconocido'}
                            </div>`;
                    showToast('Error al generar tabla final', 'error');
                }
            } catch (error) {
                pasosContainer.innerHTML = `
                        <div class="p-4 bg-red-50 border border-red-200 rounded text-red-700">
                            <i class="fa-solid fa-circle-exclamation mr-2"></i>
                            Error de conexión: ${error.message}
                        </div>`;
                showToast('Error de conexión', 'error');
                
            }
        }

        // ─────────────────────────────────────────────
        //  SELECCIÓN DE TIPO (Carga)
        // ─────────────────────────────────────────────
        function selectType(type) {
            document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
            document.querySelector(`.type-card[data-type="${type}"]`).classList.add('active');
            state.currentType = type;

            document.getElementById('empty-state').style.display = 'none';
            const uploadArea = document.getElementById('upload-area');
            uploadArea.style.display = 'block';
            uploadArea.classList.add('fade-in');

            renderUploadArea(type);
        }

        function renderUploadArea(type) {
            const cfg = config[type];
            const container = document.getElementById('upload-area');

            let stepsHtml = '';
            for (let i = 0; i < cfg.steps; i++) {
                const hasFile = state.files[type][i] !== null;
                const isFirst = i === 0;
                let stateClass = hasFile ? 'completed' : (isFirst ? 'active' : 'disabled');
                const fileName = hasFile ? state.files[type][i].name : 'Pendiente...';

                stepsHtml += `
                        <div class="step-item ${stateClass}" data-step="${i}" onclick="triggerFile('${type}', ${i})">
                            <div class="step-number">${i + 1}</div>
                            <div class="step-info">
                                <div class="step-title">${cfg.names[i]}</div>
                                <div class="step-file" id="file-name-${type}-${i}">
                                    ${hasFile ? '<i class="fa-solid fa-file-lines"></i>' : ''}
                                    ${fileName}
                                </div>
                                <div class="error-detail" id="error-${type}-${i}"></div>
                            </div>
                            <div class="step-status">
                                <i class="fa-solid fa-check-circle step-check"></i>
                                <i class="fa-solid fa-spinner fa-spin step-loading"></i>
                                <i class="fa-regular fa-circle step-pending"></i>
                            </div>
                            <input type="file" class="file-input-hidden" id="input-${type}-${i}"
                                   accept="${(type === 'uset' || type === 'sepe') ? '.txt' : '.txt,.csv'}"
                                   onchange="handleFile('${type}', ${i}, this)"
                                   style="display:none;">
                        </div>`;
            }

            const completed = state.files[type].filter(f => f !== null).length;
            const progress = (completed / cfg.steps) * 100;

            container.innerHTML = `
                    <div class="upload-header">
                        <div class="upload-header-icon" style="background:${cfg.color}15; color:${cfg.color};">
                            <i class="fa-solid ${cfg.icon}"></i>
                        </div>
                        <div class="upload-header-text">
                            <h2>${cfg.title}</h2>
                            <p>${cfg.subtitle}</p>
                        </div>
                    </div>

                    <div class="progress-section">
                        <div class="progress-header">
                            <span class="progress-title">Progreso de carga</span>
                            <span class="progress-value" id="progress-text-${type}">${completed} de ${cfg.steps}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-bar-${type}" style="width:${progress}%"></div>
                        </div>
                    </div>

                    <div class="steps-container" id="steps-container">
                        ${stepsHtml}
                    </div>

                    <button onclick="confirmProcess('${type}')" id="btn-${type}"
                            class="btn-primary" ${completed !== cfg.steps ? 'disabled' : ''}>
                        <i class="fa-solid fa-database"></i>
                        Cargar a Base de Datos
                    </button>`;
        }

        // ─────────────────────────────────────────────
        //  DRAG & DROP
        // ─────────────────────────────────────────────
        document.addEventListener('dragover', e => {
            e.preventDefault();
            if (state.currentType) document.getElementById('drop-overlay').classList.add('active');
        });

        document.addEventListener('dragleave', e => {
            if (e.relatedTarget === null) document.getElementById('drop-overlay').classList.remove('active');
        });

        document.addEventListener('drop', e => {
            e.preventDefault();
            document.getElementById('drop-overlay').classList.remove('active');
            if (!state.currentType || !currentDrag) return;
            if (e.dataTransfer.files.length > 0) {
                const { step } = currentDrag;
                const input = document.getElementById(`input-${state.currentType}-${step}`);
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                input.files = dt.files;
                handleFile(state.currentType, step, input);
            }
            currentDrag = null;
        });

        // ─────────────────────────────────────────────
        //  MANEJO DE ARCHIVOS
        // ─────────────────────────────────────────────
        function triggerFile(type, step) {
            const stepEl = document.querySelector(`#upload-area .step-item[data-step="${step}"]`);
            if (stepEl.classList.contains('disabled') || stepEl.classList.contains('completed')) return;
            currentDrag = { type, step };
            document.getElementById(`input-${type}-${step}`).click();
        }

        function handleFile(type, step, input) {
            const file = input.files[0];
            if (!file) return;

            const ext = file.name.split('.').pop().toLowerCase();
            const valid = (type === 'uset' || type === 'sepe') ? ['txt'] : ['txt', 'csv'];

            if (!valid.includes(ext)) {
                showToast(`Formato no válido. Use: ${valid.join(', ')}`, 'error');
                input.value = '';
                return;
            }

            state.files[type][step] = file;
            updateStepUI(type, step, file.name);

            if (step + 1 < config[type].steps) {
                const next = document.querySelector(`#upload-area .step-item[data-step="${step + 1}"]`);
                if (next) { next.classList.remove('disabled'); next.classList.add('active'); }
            }

            updateProgress(type);
            showToast(`${config[type].names[step]} seleccionado`, 'success');
        }

        function updateStepUI(type, step, fileName) {
            const el = document.querySelector(`#upload-area .step-item[data-step="${step}"]`);
            el.classList.remove('active', 'error-state');
            el.classList.add('completed');
            document.getElementById(`file-name-${type}-${step}`).innerHTML =
                `<i class="fa-solid fa-file-lines"></i> ${fileName}`;
            document.getElementById(`error-${type}-${step}`).textContent = '';
        }

        function updateProgress(type) {
            const completed = state.files[type].filter(f => f !== null).length;
            const pct = (completed / config[type].steps) * 100;
            document.getElementById(`progress-bar-${type}`).style.width = `${pct}%`;
            document.getElementById(`progress-text-${type}`).textContent = `${completed} de ${config[type].steps}`;
            const btn = document.getElementById(`btn-${type}`);
            if (btn) btn.disabled = completed !== config[type].steps;
        }

        // ─────────────────────────────────────────────
        //  PROCESAMIENTO DE CARGA
        // ─────────────────────────────────────────────
        function confirmProcess(type) {
            pendingSection = type;
            const cfg = config[type];
            document.getElementById('modal-title').textContent = `Cargar ${cfg.title}`;
            document.getElementById('modal-msg').textContent =
                `Esta acción truncará las tablas de ${cfg.title} y recargará todos los datos desde los archivos seleccionados. ¿Desea continuar?`;
            document.getElementById('confirm-modal').style.display = 'flex';
        }

        document.getElementById('modal-cancel').addEventListener('click', () => {
            document.getElementById('confirm-modal').style.display = 'none';
            pendingSection = null;
        });

        document.getElementById('modal-confirm').addEventListener('click', async () => {
            document.getElementById('confirm-modal').style.display = 'none';
            if (pendingSection) {
                await processSection(pendingSection);
                pendingSection = null;
            }
        });

        async function processSection(type) {
            const cfg = config[type];
            const btn = document.getElementById(`btn-${type}`);

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

            const formData = new FormData();
            state.files[type].forEach((file, idx) => {
                if (file) formData.append(cfg.fileKeys[idx], file);
            });

            try {
                const response = await fetch('Servicios/generar_sorteo.php');

                const text = await response.text();
                console.log("RAW RESPONSE:", text);

                const data = JSON.parse(text); // aquí truena si no es JSON

                if (data.success) {
                    console.log("OK:", data);
                }

            } catch (error) {
                console.error("💥 ERROR CAPTURADO:");
                console.error(error);
            }finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-database"></i> Cargar a Base de Datos';
            }
        }

        function showResultSummary(type, results) {
            const msg = results.map(r => `${r.tabla}: ${r.filas || r.filas_insertadas || 0} filas`).join(' | ');
            showToast(msg, 'success', 6000);
        }

        function markErrors(type, errors) {
            Object.entries(errors).forEach(([step, msg]) => {
                const el = document.querySelector(`#upload-area .step-item[data-step="${step}"]`);
                if (el) { el.classList.remove('completed'); el.classList.add('active', 'error-state'); }
                const errDiv = document.getElementById(`error-${type}-${step}`);
                if (errDiv) errDiv.textContent = msg;
            });
        }

        function resetSection(type) {
            state.files[type] = new Array(config[type].steps).fill(null);
            renderUploadArea(type);
        }

        // ─────────────────────────────────────────────
        //  TOAST
        // ─────────────────────────────────────────────
        let toastTimer = null;
        function showToast(message, type = 'success', duration = 4000) {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const title = document.getElementById('toast-title');
            const msg = document.getElementById('toast-msg');

            if (!toast || !icon || !title || !msg) {
                console.error("Toast elements missing in HTML");
                return;
            }

            toast.className = `toast ${type}`;
            toast.style.display = 'block';

            icon.className =
                type === 'success'
                    ? 'fa-solid fa-circle-check toast-icon'
                    : 'fa-solid fa-circle-exclamation toast-icon';

            title.textContent = type === 'success' ? 'Éxito' : 'Error';
            msg.textContent = message;

            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.style.display = 'none';
            }, duration);
        }

        // ─────────────────────────────────────────────
        //  INICIALIZACIÓN
        // ─────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            // Verificar silenciosamente al cargar (solo actualiza el indicador del menú)
            verificarDatosPrueba1();

            window.onclick = function (event) {
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    if (event.target === modal) modal.style.display = 'none';
                });
            };
        });