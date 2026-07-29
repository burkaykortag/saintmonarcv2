<!-- SaintMonarc Advanced Media Picker Modal -->
<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-labelledby="mediaPickerModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content text-white" style="background: rgba(15, 10, 25, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
            
            <!-- Modal Header -->
            <div class="modal-header border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center p-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-photo-video text-warning fs-4"></i>
                    <h5 class="modal-title font-weight-700 m-0 text-white" id="mediaPickerModalLabel">Medya Yönetim Paneli 2.0</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body p-0 d-flex flex-column flex-lg-row overflow-hidden" style="height: calc(85vh - 120px);">
                
                <!-- Left Sidebar: Folders Tree & File Types -->
                <div class="col-12 col-lg-3 p-4 border-end border-light border-opacity-10 overflow-y-auto" style="background: rgba(255,255,255,0.01);">
                    
                    <button class="btn btn-warning w-100 border-0 mb-4 py-2 font-weight-600 shadow-sm" onclick="triggerPickerUpload()" style="background: linear-gradient(135deg, #D4AF37, #AA7C11); color: #000;">
                        <i class="fas fa-cloud-arrow-up me-2"></i> Dosya Yükle
                    </button>
                    
                    <h6 class="text-warning font-weight-700 mb-3 uppercase tracking-wider fs-7">Klasörler</h6>
                    <div id="pickerFoldersList" class="d-flex flex-column gap-2 mb-4">
                        <!-- Folders dynamically listed here -->
                    </div>
                    
                    <h6 class="text-warning font-weight-700 mb-3 uppercase tracking-wider fs-7">Dosya Tipleri</h6>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type active" onclick="setPickerTypeFilter('')" id="type-all">
                            <i class="fas fa-images me-2 text-warning"></i> Tüm Dosyalar
                        </button>
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type" onclick="setPickerTypeFilter('image')" id="type-image">
                            <i class="fas fa-image me-2 text-muted"></i> Resimler
                        </button>
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type" onclick="setPickerTypeFilter('video')" id="type-video">
                            <i class="fas fa-video me-2 text-muted"></i> Videolar
                        </button>
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type" onclick="setPickerTypeFilter('pdf')" id="type-pdf">
                            <i class="fas fa-file-pdf me-2 text-muted"></i> Belgeler (PDF)
                        </button>
                    </div>
                </div>

                <!-- Middle Content Area: Grid/List and Toolbar -->
                <div class="col-12 col-lg-6 d-flex flex-column border-end border-light border-opacity-10" style="background: rgba(0,0,0,0.15);">
                    
                    <!-- Search and Toolbar -->
                    <div class="p-3 border-bottom border-light border-opacity-10 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <div class="position-relative flex-grow-1" style="max-width: 280px;">
                            <input type="text" id="pickerSearch" class="form-control border-0 text-white rounded-3 fs-7 py-2" placeholder="Medya veya etiket ara..." style="background: rgba(255,255,255,0.05); padding-left: 36px;">
                            <i class="fas fa-search position-absolute text-muted" style="left: 12px; top: 12px; font-size: 13px;"></i>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2">
                            <!-- Grid / List Switcher -->
                            <div class="btn-group" role="group">
                                <button class="btn btn-secondary border-0 btn-sm active" id="btnPickerGrid" onclick="setPickerViewMode('grid')">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button class="btn btn-secondary border-0 btn-sm" id="btnPickerList" onclick="setPickerViewMode('list')">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                            
                            <!-- Sort Order -->
                            <select id="pickerSortBy" class="form-select form-select-sm border-0 text-white" style="background: rgba(255,255,255,0.05); width: 120px;" onchange="loadPickerMedia()">
                                <option value="date">Tarih</option>
                                <option value="name">İsim</option>
                                <option value="size">Boyut</option>
                            </select>
                        </div>
                    </div>

                    <!-- Breadcrumbs Bar -->
                    <div class="px-3 py-2 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center fs-7" style="background: rgba(255,255,255,0.02);">
                        <nav aria-label="breadcrumb" class="m-0">
                            <ol class="breadcrumb m-0" id="pickerBreadcrumbs">
                                <!-- Dynamic breadcrumbs -->
                            </ol>
                        </nav>
                        
                        <button class="btn btn-link text-decoration-none text-muted btn-sm p-0" onclick="triggerCreateFolderPicker()">
                            <i class="fas fa-folder-plus text-warning me-1"></i> Klasör Ekle
                        </button>
                    </div>

                    <!-- Upload Area Progress (Drag & Drop zone overlay inside modal) -->
                    <div id="pickerUploadDropzone" class="p-4 d-none" style="border-bottom: 1px dashed rgba(212,175,55,0.3); background: rgba(212,175,55,0.02);">
                        <div class="border border-secondary border-dashed p-4 text-center rounded-3 cursor-pointer" onclick="document.getElementById('pickerFileInput').click()">
                            <input type="file" id="pickerFileInput" multiple class="d-none" onchange="handlePickerFilesSelect(event)">
                            <i class="fas fa-cloud-arrow-up text-muted mb-2" style="font-size: 32px;"></i>
                            <h6 class="text-white mb-1">Dosyaları Sürükleyin veya Dosya Seçin</h6>
                            <p class="text-muted fs-8 mb-0">PNG, JPG, WEBP, GIF, PDF, SVG, MP4 (Maks. 50MB)</p>
                        </div>
                    </div>

                    <!-- AJAX Upload Metric status -->
                    <div id="pickerUploadProgressBlock" class="p-3 border-bottom border-light border-opacity-10 d-none" style="background: rgba(0,0,0,0.3);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-8 text-white font-weight-500" id="pickerUploadStatusText">Dosyalar Yükleniyor...</span>
                            <button class="btn btn-sm btn-link text-danger p-0 fs-8 text-decoration-none" id="btnAbortPickerUpload">İptal Et</button>
                        </div>
                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05);">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="pickerUploadProgressBar" role="progressbar" style="width: 0%; background: #D4AF37;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1 fs-8 text-muted">
                            <span id="pickerUploadRemaining">Kalan Süre: --</span>
                            <span id="pickerUploadPercent">0%</span>
                        </div>
                    </div>

                    <!-- Media Grid/List View Content (Lazy loading & infinite scroll) -->
                    <div class="flex-grow-1 overflow-y-auto p-3" id="pickerMediaScrollContainer" style="height: 100%;">
                        <div class="row g-2" id="pickerMediaItemsContainer">
                            <!-- Dynamic media items -->
                        </div>
                        <!-- Loader Spinner -->
                        <div id="pickerLoader" class="text-center py-4 d-none">
                            <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                        </div>
                    </div>

                    <!-- Bulk action overlay inside modal -->
                    <div id="pickerBulkBar" class="p-3 border-top border-light border-opacity-10 d-none d-flex justify-content-between align-items-center" style="background: rgba(212, 175, 55, 0.1);">
                        <span class="fs-8 font-weight-600"><span id="pickerSelectedCount">0</span> dosya seçildi</span>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-secondary text-white border-0 py-1" onclick="triggerPickerBulkAction('copy')">Kopyala</button>
                            <button class="btn btn-sm btn-secondary text-white border-0 py-1" onclick="triggerPickerBulkAction('webp')">WebP Yap</button>
                            <button class="btn btn-sm btn-secondary text-white border-0 py-1" onclick="triggerPickerBulkAction('download')">İndir (ZIP)</button>
                            <button class="btn btn-sm btn-danger border-0 py-1" onclick="triggerPickerBulkAction('delete')">Sil</button>
                        </div>
                    </div>
                </div>

                <!-- Right Content Area: Information Details & SEO Form -->
                <div class="col-12 col-lg-3 p-4 overflow-y-auto" style="background: rgba(255,255,255,0.01);">
                    <div id="pickerDetailsEmpty">
                        <h6 class="text-warning font-weight-700 mb-3 uppercase tracking-wider fs-7">Medya Detayları</h6>
                        <div class="text-center py-5 text-muted fs-7">
                            <i class="fas fa-info-circle fs-3 mb-2 d-block text-secondary"></i>
                            Önizleme ve detayları görmek için bir dosya seçin.
                        </div>
                    </div>
                    
                    <div id="pickerDetailsPanel" class="d-none">
                        <h6 class="text-warning font-weight-700 mb-3 uppercase tracking-wider fs-7">Medya Detayları</h6>
                        
                        <!-- Media Preview Box -->
                        <div class="text-center p-2 rounded-3 mb-3" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); min-height: 140px; display: flex; align-items: center; justify-content: center;">
                            <img id="pickerPreviewImg" class="img-fluid rounded d-none" style="max-height: 140px; object-fit: contain;">
                            <video id="pickerPreviewVideo" class="img-fluid rounded d-none" controls style="max-height: 140px; width: 100%;"></video>
                            <div id="pickerPreviewDoc" class="d-none text-center p-3">
                                <i class="fas fa-file-pdf text-danger mb-2" style="font-size: 40px;"></i>
                                <span class="d-block fs-8 text-white text-truncate" id="pickerDocLabel"></span>
                            </div>
                        </div>

                        <!-- Technical Specs -->
                        <table class="table table-sm table-borderless text-white fs-8 mb-4">
                            <tbody>
                                <tr><td class="text-muted p-1">Dosya Adı:</td><td class="p-1 font-weight-600 text-truncate text-end" style="max-width: 140px;" id="specName"></td></tr>
                                <tr><td class="text-muted p-1">MIME:</td><td class="p-1 font-weight-600 text-end" id="specMime"></td></tr>
                                <tr><td class="text-muted p-1">Boyut:</td><td class="p-1 font-weight-600 text-end" id="specSize"></td></tr>
                                <tr><td class="text-muted p-1">Çözünürlük:</td><td class="p-1 font-weight-600 text-end" id="specDimensions">-</td></tr>
                                <tr><td class="text-muted p-1">SHA256:</td><td class="p-1 font-weight-600 text-end text-truncate" style="max-width: 140px;" id="specHash"></td></tr>
                                <tr><td class="text-muted p-1">Yükleyen:</td><td class="p-1 font-weight-600 text-end" id="specUser">-</td></tr>
                            </tbody>
                        </table>

                        <!-- SEO Metadata Input Form -->
                        <form id="pickerSeoForm" class="d-flex flex-column gap-2">
                            <input type="hidden" id="seoFileId">
                            <div>
                                <label class="form-label text-muted fs-8 font-weight-500 mb-1">Görsel Başlığı (Title)</label>
                                <input type="text" id="seoTitle" class="form-control form-control-sm border-0 text-white rounded" style="background: rgba(255,255,255,0.04);">
                            </div>
                            <div>
                                <label class="form-label text-muted fs-8 font-weight-500 mb-1">Alt Metin (Alt Text)</label>
                                <input type="text" id="seoAltText" class="form-control form-control-sm border-0 text-white rounded" style="background: rgba(255,255,255,0.04);">
                            </div>
                            <div>
                                <label class="form-label text-muted fs-8 font-weight-500 mb-1">Altyazı (Caption)</label>
                                <input type="text" id="seoCaption" class="form-control form-control-sm border-0 text-white rounded" style="background: rgba(255,255,255,0.04);">
                            </div>
                            <div>
                                <label class="form-label text-muted fs-8 font-weight-500 mb-1">Açıklama (Description)</label>
                                <textarea id="seoDescription" class="form-control form-control-sm border-0 text-white rounded" rows="2" style="background: rgba(255,255,255,0.04); resize: none;"></textarea>
                            </div>
                            
                            <!-- Tags inline selection -->
                            <div>
                                <label class="form-label text-muted fs-8 font-weight-500 mb-1 d-block">Etiketler</label>
                                <div class="d-flex flex-wrap gap-1 p-2 rounded" style="background: rgba(255,255,255,0.02); max-height: 80px; overflow-y: auto;" id="seoTagsContainer">
                                    <!-- Dynamic list of tags checkboxes -->
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-warning btn-sm border-0 py-1.5 w-100 mt-2 font-weight-600" onclick="savePickerSeoMetadata()" style="background: #D4AF37; color: #000;">
                                Meta Verileri Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top border-light border-opacity-10 p-3 d-flex justify-content-between align-items-center">
                <span class="fs-8 text-muted">Açıklama: Çift tıklama veya Seç butonuyla medyayı doğrudan forma aktarabilirsiniz.</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary border-0 px-4" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning border-0 px-4 font-weight-600" id="btnPickerConfirmSelection" onclick="confirmPickerSelection()" style="background: linear-gradient(135deg, #D4AF37, #AA7C11); color: #000;">Medya Seç</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Folder Creation Submodal inside Modal -->
<div class="modal fade" id="pickerFolderCreateModal" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-white" style="background: #15102a; border: 1px solid var(--sm-border); border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title font-weight-600">Yeni Klasör Oluştur</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="pickerNewFolderName" class="form-control form-control-sm border-0 text-white rounded mb-3" style="background: rgba(255,255,255,0.05);" placeholder="Klasör ismi...">
                <button class="btn btn-warning w-100 border-0 btn-sm font-weight-600" onclick="submitPickerCreateFolder()" style="background: #D4AF37; color:#000;">Klasör Oluştur</button>
            </div>
        </div>
    </div>
</div>

<!-- Shared JavaScript Engine for the Advanced Media Picker -->
<script>
    // Singleton State Controller for the Media Picker
    const SM_MediaPicker = {
        options: {
            singleSelect: true,
            allowedTypes: [],
            callback: null
        },
        state: {
            currentFolderId: null,
            typeFilter: '',
            searchQuery: '',
            sortBy: 'date',
            viewMode: 'grid',
            selectedItems: [],
            page: 1,
            loading: false,
            hasMore: true,
            xhrUpload: null
        },
        
        init(options) {
            this.options = { ...this.options, ...options };
            this.state.selectedItems = [];
            this.state.page = 1;
            this.state.hasMore = true;
            this.state.currentFolderId = null;
            this.state.typeFilter = '';
            this.state.searchQuery = '';
            
            // Adjust confirm button state
            document.getElementById('pickerSearch').value = '';
            document.getElementById('pickerDetailsEmpty').classList.remove('d-none');
            document.getElementById('pickerDetailsPanel').classList.add('d-none');
            document.getElementById('pickerBulkBar').classList.add('d-none');
            
            // Load tags list
            this.loadTagsCheckboxList();
            
            // Load directories and files
            this.loadMedia();
            
            // Show modal
            const myModal = new bootstrap.Modal(document.getElementById('mediaPickerModal'));
            myModal.show();
        },

        loadTagsCheckboxList() {
            fetch('<?= url("/admin/media") ?>')
            .then(() => {
                // Pre-populate tags array
                const tagsList = <?= json_encode($all_tags ?? []) ?>;
                const container = document.getElementById('seoTagsContainer');
                container.innerHTML = '';
                tagsList.forEach(t => {
                    const wrap = document.createElement('div');
                    wrap.className = 'form-check m-0 me-2';
                    wrap.innerHTML = `
                        <input class="form-check-input picker-tag-cb" type="checkbox" value="${t.id}" id="picker-tag-cb-${t.id}">
                        <label class="form-check-label text-white fs-8" for="picker-tag-cb-${t.id}">${t.name}</label>
                    `;
                    container.appendChild(wrap);
                });
            });
        },

        loadMedia(append = false) {
            if (this.state.loading) return;
            this.state.loading = true;
            
            const loader = document.getElementById('pickerLoader');
            loader.classList.remove('d-none');

            if (!append) {
                this.state.page = 1;
                this.state.hasMore = true;
                document.getElementById('pickerMediaItemsContainer').innerHTML = '';
            }

            const url = `<?= url("/admin/media/list-ajax") ?>?folder_id=${this.state.currentFolderId || ''}&q=${this.state.searchQuery}&extension=${this.state.typeFilter}&sort_by=${this.state.sortBy}&page=${this.state.page}&limit=24`;

            fetch(url)
            .then(res => res.json())
            .then(data => {
                this.state.loading = false;
                loader.classList.add('d-none');

                if (data.success) {
                    // 1. Render folders tree
                    if (!append) {
                        this.renderFolders(data.folders);
                        this.renderBreadcrumbs(data.breadcrumbs);
                    }

                    // 2. Render files grid or list
                    this.renderFiles(data.files, append);
                    
                    this.state.hasMore = data.has_more;
                }
            })
            .catch(err => {
                this.state.loading = false;
                loader.classList.add('d-none');
                console.error(err);
            });
        },

        renderFolders(folders) {
            const list = document.getElementById('pickerFoldersList');
            list.innerHTML = '';
            
            // Add Root directory if current folder exists
            if (this.state.currentFolderId) {
                const rootBtn = document.createElement('button');
                rootBtn.className = 'btn btn-link text-start text-white text-decoration-none p-2 rounded fs-7';
                rootBtn.innerHTML = `<i class="fas fa-chevron-left text-warning me-2"></i> Üst Klasör (Geri)`;
                rootBtn.onclick = () => {
                    this.state.currentFolderId = null;
                    this.loadMedia();
                };
                list.appendChild(rootBtn);
            }

            if (folders.length === 0) {
                list.innerHTML += `<div class="text-muted fs-8 p-2">Klasör bulunamadı.</div>`;
                return;
            }

            folders.forEach(f => {
                const fBtn = document.createElement('div');
                fBtn.className = 'd-flex align-items-center justify-content-between p-1 rounded hover-bg';
                fBtn.innerHTML = `
                    <button class="btn btn-link text-start text-white text-decoration-none p-1 fs-7 text-truncate flex-grow-1" onclick="SM_MediaPicker.navigateToFolder(${f.id})">
                        <i class="fas fa-folder text-warning me-2"></i> ${f.name}
                    </button>
                    <button class="btn btn-sm btn-link text-danger p-0" onclick="SM_MediaPicker.deleteFolder(${f.id})"><i class="fas fa-trash"></i></button>
                `;
                list.appendChild(fBtn);
            });
        },

        renderBreadcrumbs(crumbs) {
            const list = document.getElementById('pickerBreadcrumbs');
            list.innerHTML = `<li class="breadcrumb-item"><a href="#" onclick="SM_MediaPicker.navigateToFolder(null)" class="text-warning text-decoration-none">Kök</a></li>`;
            
            crumbs.forEach((c, idx) => {
                const isLast = idx === crumbs.length - 1;
                if (isLast) {
                    list.innerHTML += `<li class="breadcrumb-item active text-muted" aria-current="page">${c.name}</li>`;
                } else {
                    list.innerHTML += `<li class="breadcrumb-item"><a href="#" onclick="SM_MediaPicker.navigateToFolder(${c.id})" class="text-warning text-decoration-none">${c.name}</a></li>`;
                }
            });
        },

        renderFiles(files, append) {
            const container = document.getElementById('pickerMediaItemsContainer');
            if (!append && files.length === 0) {
                container.innerHTML = `<div class="col-12 text-center text-muted py-5 fs-7"><i class="fas fa-folder-open fs-3 d-block mb-2"></i>Klasörde dosya bulunmuyor.</div>`;
                return;
            }

            files.forEach(f => {
                const isChecked = this.state.selectedItems.some(i => i.id === f.id);
                
                let mediaPreview = '';
                if (f.mime_type.startsWith('image/')) {
                    mediaPreview = `<img src="<?= url("/") ?>/uploads/thumbnails/${f.filename}" alt="${f.alt_text || ''}" onerror="this.src='<?= url("/") ?>/${f.filepath}'" class="img-fluid rounded" style="max-height: 80px; object-fit: contain;">`;
                } else if (f.mime_type.startsWith('video/')) {
                    mediaPreview = `
                        <div class="video-preview-wrapper position-relative w-100 h-100" style="min-height: 80px;" onmouseenter="this.querySelector('video').play()" onmouseleave="this.querySelector('video').pause()">
                            <video src="<?= url("/") ?>/${f.filepath}" muted loop class="w-100 h-100" style="object-fit: cover; max-height:80px;"></video>
                            <i class="fas fa-play position-absolute text-white" style="left:50%; top:50%; transform:translate(-50%,-50%); opacity:0.7;"></i>
                        </div>
                    `;
                } else {
                    mediaPreview = `<i class="fas fa-file-pdf text-danger" style="font-size: 32px;"></i>`;
                }

                if (this.state.viewMode === 'grid') {
                    const card = document.createElement('div');
                    card.className = `col-6 col-sm-4 col-md-3 picker-item-card`;
                    card.innerHTML = `
                        <div class="p-2 rounded-3 text-center position-relative ${isChecked ? 'border border-warning' : 'border border-transparent'}" id="picker-media-${f.id}" onclick="SM_MediaPicker.toggleSelection(${f.id}, ${JSON.stringify(f).replace(/"/g, '&quot;')})" style="background: rgba(255,255,255,0.03); cursor: pointer; transition: all 0.2s;">
                            <input type="checkbox" class="position-absolute picker-checkbox-overlay" style="top: 8px; left: 8px; accent-color: #D4AF37;" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation(); SM_MediaPicker.toggleCheckbox(${f.id}, ${JSON.stringify(f).replace(/"/g, '&quot;')})">
                            <div style="height: 80px; display:flex; align-items:center; justify-content:center; overflow:hidden;" class="mb-2">
                                ${mediaPreview}
                            </div>
                            <div class="fs-8 text-truncate text-white px-1">${f.original_name}</div>
                        </div>
                    `;
                    container.appendChild(card);
                } else {
                    // List View Row
                    const row = document.createElement('div');
                    row.className = `col-12 picker-item-card`;
                    row.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between p-2 rounded mb-1 ${isChecked ? 'bg-warning bg-opacity-10 border border-warning' : 'border border-transparent'}" id="picker-media-${f.id}" onclick="SM_MediaPicker.toggleSelection(${f.id}, ${JSON.stringify(f).replace(/"/g, '&quot;')})" style="background: rgba(255,255,255,0.02); cursor: pointer;">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 text-truncate">
                                <input type="checkbox" class="picker-checkbox-overlay" style="accent-color: #D4AF37;" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation(); SM_MediaPicker.toggleCheckbox(${f.id}, ${JSON.stringify(f).replace(/"/g, '&quot;')})">
                                <div style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                    ${mediaPreview}
                                </div>
                                <span class="fs-8 text-white text-truncate">${f.original_name}</span>
                            </div>
                            <div class="d-flex align-items-center gap-4 text-muted fs-8">
                                <span>${f.formatted_size}</span>
                                <span>${f.extension.toUpperCase()}</span>
                            </div>
                        </div>
                    `;
                    container.appendChild(row);
                }
            });
        },

        navigateToFolder(id) {
            this.state.currentFolderId = id;
            this.loadMedia();
        },

        setPickerTypeFilter(type) {
            this.state.typeFilter = type;
            document.querySelectorAll('.picker-filter-type').forEach(b => b.classList.remove('active'));
            document.getElementById('type-' + (type || 'all')).classList.add('active');
            this.loadMedia();
        },

        setPickerViewMode(mode) {
            this.state.viewMode = mode;
            document.getElementById('btnPickerGrid').classList.toggle('active', mode === 'grid');
            document.getElementById('btnPickerList').classList.toggle('active', mode === 'list');
            this.loadMedia();
        },

        toggleSelection(id, item) {
            if (this.options.singleSelect) {
                // Clear selection and select this item only
                this.state.selectedItems = [item];
                document.querySelectorAll('.picker-item-card > div').forEach(d => {
                    d.classList.remove('border-warning');
                    d.classList.add('border-transparent');
                });
                document.querySelectorAll('.picker-checkbox-overlay').forEach(cb => cb.checked = false);

                const cardDiv = document.getElementById('picker-media-' + id);
                if (cardDiv) {
                    cardDiv.classList.remove('border-transparent');
                    cardDiv.classList.add('border border-warning');
                    const cb = cardDiv.querySelector('.picker-checkbox-overlay');
                    if (cb) cb.checked = true;
                }
            } else {
                // Multi select
                const idx = this.state.selectedItems.findIndex(i => i.id === id);
                if (idx > -1) {
                    this.state.selectedItems.splice(idx, 1);
                    const cardDiv = document.getElementById('picker-media-' + id);
                    if (cardDiv) {
                        cardDiv.classList.remove('border-warning');
                        cardDiv.classList.add('border-transparent');
                        const cb = cardDiv.querySelector('.picker-checkbox-overlay');
                        if (cb) cb.checked = false;
                    }
                } else {
                    this.state.selectedItems.push(item);
                    const cardDiv = document.getElementById('picker-media-' + id);
                    if (cardDiv) {
                        cardDiv.classList.remove('border-transparent');
                        cardDiv.classList.add('border border-warning');
                        const cb = cardDiv.querySelector('.picker-checkbox-overlay');
                        if (cb) cb.checked = true;
                    }
                }
            }
            this.updateDetailsPanel(item);
            this.updateBulkActionBar();
        },

        toggleCheckbox(id, item) {
            this.toggleSelection(id, item);
        },

        updateDetailsPanel(item) {
            document.getElementById('pickerDetailsEmpty').classList.add('d-none');
            document.getElementById('pickerDetailsPanel').classList.remove('d-none');

            // Render Preview based on mime type
            const previewImg = document.getElementById('pickerPreviewImg');
            const previewVid = document.getElementById('pickerPreviewVideo');
            const previewDoc = document.getElementById('pickerPreviewDoc');

            previewImg.classList.add('d-none');
            previewVid.classList.add('d-none');
            previewDoc.classList.add('d-none');

            if (item.mime_type.startsWith('image/')) {
                previewImg.src = '<?= url("/") ?>/' + item.filepath;
                previewImg.classList.remove('d-none');
            } else if (item.mime_type.startsWith('video/')) {
                previewVid.src = '<?= url("/") ?>/' + item.filepath;
                previewVid.classList.remove('d-none');
            } else {
                document.getElementById('pickerDocLabel').textContent = item.original_name;
                previewDoc.classList.remove('d-none');
            }

            // Specs
            document.getElementById('specName').textContent = item.original_name;
            document.getElementById('specMime').textContent = item.mime_type;
            document.getElementById('specSize').textContent = item.formatted_size;
            document.getElementById('specDimensions').textContent = item.width ? (item.width + 'x' + item.height + 'px') : '-';
            document.getElementById('specHash').textContent = item.file_hash;
            document.getElementById('specUser').textContent = item.uploaded_by_admin || 'System';

            // SEO inputs
            document.getElementById('seoFileId').value = item.id;
            document.getElementById('seoTitle').value = item.title || '';
            document.getElementById('seoAltText').value = item.alt_text || '';
            document.getElementById('seoCaption').value = item.caption || '';
            document.getElementById('seoDescription').value = item.description || '';

            // Check tags
            document.querySelectorAll('.picker-tag-cb').forEach(cb => cb.checked = false);
            if (item.tags_list) {
                const activeTags = item.tags_list.split(',');
                document.querySelectorAll('.picker-tag-cb').forEach(cb => {
                    const label = document.querySelector(`label[for="${cb.id}"]`);
                    if (label && activeTags.includes(label.textContent.trim())) {
                        cb.checked = true;
                    }
                });
            }
        },

        updateBulkActionBar() {
            const bar = document.getElementById('pickerBulkBar');
            const cnt = document.getElementById('pickerSelectedCount');
            if (this.state.selectedItems.length > 1) {
                bar.classList.remove('d-none');
                cnt.textContent = this.state.selectedItems.length;
            } else {
                bar.classList.add('d-none');
            }
        }
    };

    // Lazy load infinite scroll support inside picker modal scroll area
    document.getElementById('pickerMediaScrollContainer').addEventListener('scroll', function() {
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - 50) {
            if (SM_MediaPicker.state.hasMore && !SM_MediaPicker.state.loading) {
                SM_MediaPicker.state.page++;
                SM_MediaPicker.loadMedia(true);
            }
        }
    });

    // Keyboard clipboard paste event (Ctrl+V) listener
    window.addEventListener('paste', function(e) {
        if (!document.getElementById('mediaPickerModal').classList.contains('show')) return;
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        const files = [];
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') === 0) {
                files.push(items[i].getAsFile());
            }
        }
        if (files.length > 0) {
            handlePickerFilesUpload(files);
        }
    });

    // Input handlers
    function triggerPickerUpload() {
        document.getElementById('pickerUploadDropzone').classList.toggle('d-none');
    }

    function handlePickerFilesSelect(e) {
        handlePickerFilesUpload(e.target.files);
    }

    function handlePickerFilesUpload(files) {
        if (files.length === 0) return;
        
        const fd = new FormData();
        for(let i=0; i<files.length; i++) {
            fd.append('files[]', files[i]);
        }
        if (SM_MediaPicker.state.currentFolderId) {
            fd.append('folder_id', SM_MediaPicker.state.currentFolderId);
        }

        const progressBlock = document.getElementById('pickerUploadProgressBlock');
        const progressBar = document.getElementById('pickerUploadProgressBar');
        const statusText = document.getElementById('pickerUploadStatusText');
        const percentText = document.getElementById('pickerUploadPercent');
        const remainingText = document.getElementById('pickerUploadRemaining');

        progressBlock.classList.remove('d-none');
        progressBar.style.width = '0%';
        percentText.textContent = '0%';
        remainingText.textContent = 'Hesaplanıyor...';

        const startTime = Date.now();
        const xhr = new XMLHttpRequest();
        SM_MediaPicker.state.xhrUpload = xhr;

        xhr.open('POST', '<?= url("/admin/media/upload-ajax") ?>', true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const pct = (e.loaded / e.total) * 100;
                progressBar.style.width = pct + '%';
                percentText.textContent = Math.round(pct) + '%';
                
                // Time calculations
                const elapsed = (Date.now() - startTime) / 1000;
                const bps = e.loaded / elapsed;
                const remainingBytes = e.total - e.loaded;
                const remainingSeconds = remainingBytes / bps;
                
                if (remainingSeconds < 60) {
                    remainingText.textContent = `Kalan Süre: ${Math.round(remainingSeconds)} sn`;
                } else {
                    remainingText.textContent = `Kalan Süre: ${Math.round(remainingSeconds / 60)} dk`;
                }
            }
        };

        xhr.onload = function() {
            const res = JSON.parse(xhr.responseText);
            progressBlock.classList.add('d-none');
            document.getElementById('pickerUploadDropzone').classList.add('d-none');
            
            if (xhr.status === 200) {
                SM_MediaPicker.loadMedia();
            } else {
                alert(res.message || "Yükleme hatası oluştu.");
                if (res.errors) alert(res.errors.join("\n"));
            }
        };

        xhr.send(fd);
    }

    document.getElementById('btnAbortPickerUpload').addEventListener('click', function(e) {
        e.preventDefault();
        if (SM_MediaPicker.state.xhrUpload) {
            SM_MediaPicker.state.xhrUpload.abort();
            document.getElementById('pickerUploadProgressBlock').classList.add('d-none');
            alert("Dosya yüklemesi iptal edildi.");
        }
    });

    // Save SEO metadata via AJAX
    function savePickerSeoMetadata() {
        const id = document.getElementById('seoFileId').value;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('title', document.getElementById('seoTitle').value);
        fd.append('alt_text', document.getElementById('seoAltText').value);
        fd.append('caption', document.getElementById('seoCaption').value);
        fd.append('description', document.getElementById('seoDescription').value);

        // Tags checkboxes collection
        document.querySelectorAll('.picker-tag-cb:checked').forEach(cb => {
            fd.append('tags[]', cb.value);
        });

        fetch('<?= url("/admin/media/save-seo") ?>', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                SM_MediaPicker.loadMedia(true);
            } else {
                alert(res.message);
            }
        });
    }

    // Dynamic search filtering
    let searchTimeout = null;
    document.getElementById('pickerSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            SM_MediaPicker.state.searchQuery = this.value;
            SM_MediaPicker.loadMedia();
        }, 300);
    });

    // Folder create trigger
    function triggerCreateFolderPicker() {
        const modal = new bootstrap.Modal(document.getElementById('pickerFolderCreateModal'));
        document.getElementById('pickerNewFolderName').value = '';
        modal.show();
    }

    function submitPickerCreateFolder() {
        const name = document.getElementById('pickerNewFolderName').value;
        if (!name) return;

        const fd = new FormData();
        fd.append('name', name);
        if (SM_MediaPicker.state.currentFolderId) {
            fd.append('parent_id', SM_MediaPicker.state.currentFolderId);
        }

        fetch('<?= url("/admin/media/folder/create-ajax") ?>', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            bootstrap.Modal.getInstance(document.getElementById('pickerFolderCreateModal')).hide();
            if (res.success) {
                SM_MediaPicker.loadMedia();
            } else {
                alert(res.message);
            }
        });
    }

    function deleteFolder(id) {
        if (confirm('Bu klasörü ve içindeki tüm medyaları kalıcı olarak silmek istediğinize emin misiniz?')) {
            const fd = new FormData();
            fd.append('folder_id', id);

            fetch('<?= url("/admin/media/folder/delete-ajax") ?>', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    SM_MediaPicker.loadMedia();
                } else {
                    alert(res.message);
                }
            });
        }
    }

    // Bulk actions
    function triggerPickerBulkAction(action) {
        if (confirm('Seçilen dosyalara bu toplu işlemi uygulamak istediğinize emin misiniz?')) {
            const fd = new FormData();
            fd.append('action', action);
            SM_MediaPicker.state.selectedItems.forEach(i => {
                fd.append('media_ids[]', i.id);
            });

            fetch('<?= url("/admin/media/bulk-ajax") ?>', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (action === 'download' && res.zip_url) {
                        window.location.href = '<?= url("/") ?>/' + res.zip_url;
                    } else {
                        alert(res.message);
                    }
                    SM_MediaPicker.loadMedia();
                } else {
                    alert(res.message);
                }
            });
        }
    }

    // Confirm selection and fire callback
    function confirmPickerSelection() {
        if (SM_MediaPicker.state.selectedItems.length === 0) {
            alert("Lütfen en az bir dosya seçin.");
            return;
        }

        if (SM_MediaPicker.options.callback) {
            SM_MediaPicker.options.callback(SM_MediaPicker.state.selectedItems);
        }

        bootstrap.Modal.getInstance(document.getElementById('mediaPickerModal')).hide();
    }
</script>
