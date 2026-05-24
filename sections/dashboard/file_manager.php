<!-- File Manager -->
<div class="row mt-5" id="file-manager">
    <div class="col-12">
        <div class="dashboard-card file-manager-card">
            <div class="file-manager-header">
                <div>
                    <h4 class="mb-1">File Manager</h4>
                    <p class="text-secondary mb-0" id="fileManagerPath"><?php echo htmlspecialchars($htdocsPath, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="file-manager-actions">
                    <span class="file-permission-pill" id="fileManagerPermission">--</span>
                    <input type="search" id="fileManagerSearch" class="form-control file-manager-search" placeholder="Buscar">
                    <button type="button" class="btn btn-outline-secondary" onclick="fileManagerGoUp()"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-outline-info" onclick="loadFileManager()"><i class="bi bi-arrow-clockwise"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="copyFileManagerPath()"><i class="bi bi-copy"></i></button>
                    <button type="button" class="btn btn-devpanel" onclick="createFileManagerFolder()"><i class="bi bi-folder-plus"></i> Carpeta</button>
                    <button type="button" class="btn btn-devpanel" onclick="createFileManagerFile()"><i class="bi bi-file-earmark-plus"></i> Archivo</button>
                    <label class="btn btn-devpanel mb-0"><i class="bi bi-upload"></i> Subir<input type="file" id="fileManagerUpload" hidden></label>
                </div>
            </div>

            <div class="file-manager-breadcrumbs" id="fileManagerBreadcrumbs"></div>

            <div class="file-manager-layout">
                <aside class="file-manager-tree" id="fileManagerTree">
                    <div class="file-manager-empty">Cargando árbol...</div>
                </aside>

                <div class="file-manager-list" id="fileManagerContent">
                    <div class="file-manager-empty">Cargando archivos...</div>
                </div>

                <aside class="file-preview-panel">
                    <div class="file-preview-header">
                        <div>
                            <h5 class="mb-1" id="filePreviewTitle">Preview</h5>
                            <p class="text-secondary mb-0" id="filePreviewMeta">Selecciona un archivo</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info" id="filePreviewSave" onclick="saveFilePreview()" hidden>
                            <i class="bi bi-save"></i>
                        </button>
                    </div>

                    <div class="file-preview-body" id="filePreviewBody">
                        <div class="file-preview-empty">Selecciona un archivo para ver su contenido.</div>
                    </div>

                    <div class="file-editor-status" id="fileEditorStatus" hidden>
                        <span id="fileEditorCursor">Ln 1, Col 1</span>
                        <span id="fileEditorCount">0 caracteres</span>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>
