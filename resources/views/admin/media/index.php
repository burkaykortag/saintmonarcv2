<?php
use App\Helpers\ComponentHelper;

$title = "Medya Kütüphanesi 2.0 - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<!-- Style Overrides for Media Library 2.0 -->
<style>
    .hover-bg:hover {
        background: rgba(255,255,255,0.03);
    }
    .picker-filter-type.active {
        background: rgba(212, 175, 55, 0.15);
        color: var(--sm-gold) !important;
        font-weight: 600;
    }
    .picker-filter-type:hover {
        background: rgba(255,255,255,0.03);
    }
    .video-preview-wrapper:hover video {
        opacity: 1;
    }
    .video-preview-wrapper video {
        opacity: 0.8;
        transition: opacity 0.2s;
    }
</style>

<div class="row g-0 m-n5">
    <!-- Main Media Component Wrapper (Renders visually identical to modal container) -->
    <div class="col-12 col-xl-9 p-5">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <?php
                echo ComponentHelper::breadcrumb([
                    'Yönetim Paneli' => url('/admin'),
                    'Medya Kütüphanesi 2.0' => url('/admin/media')
                ]);
                ?>
                <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Gelişmiş Medya Kütüphanesi 2.0</h2>
            </div>
            
            <div class="d-flex gap-2">
                <button class="btn btn-secondary border-0" onclick="triggerPickerUpload()">
                    <i class="fas fa-cloud-arrow-up me-2"></i> Dosya Yükle
                </button>
                <button class="btn btn-warning border-0 font-weight-600" onclick="triggerCreateFolderPicker()" style="background: #D4AF37; color:#000;">
                    <i class="fas fa-folder-plus me-2"></i> Klasör Oluştur
                </button>
            </div>
        </div>

        <!-- Search and Filter Toolbar -->
        <div class="p-3 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="position-relative">
                        <input type="text" id="pickerSearch" class="search-input w-100 py-2 fs-7" placeholder="Medya veya etiket ara...">
                        <i class="fas fa-search position-absolute text-muted" style="right: 16px; top: 12px;"></i>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select id="pickerSortBy" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="SM_MediaPicker.state.sortBy = this.value; SM_MediaPicker.loadMedia()">
                        <option value="date">Tarih</option>
                        <option value="name">İsim</option>
                        <option value="size">Boyut</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-secondary border-0 btn-sm active py-2" id="btnPickerGrid" onclick="SM_MediaPicker.setPickerViewMode('grid')">
                            <i class="fas fa-th-large me-2"></i> Grid Görünüm
                        </button>
                        <button class="btn btn-secondary border-0 btn-sm py-2" id="btnPickerList" onclick="SM_MediaPicker.setPickerViewMode('list')">
                            <i class="fas fa-list me-2"></i> Liste Görünüm
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-3 d-flex justify-content-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 fs-7" id="pickerBreadcrumbs">
                            <!-- Breadcrumbs will load here -->
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Sürükle Bırak Yükleme Alanı -->
        <div id="pickerUploadDropzone" class="mb-4 d-none">
            <div class="p-5 text-center rounded-4 cursor-pointer" onclick="document.getElementById('pickerFileInput').click()" style="border: 2px dashed rgba(212,175,55,0.4); background: rgba(212,175,55,0.02);">
                <input type="file" id="pickerFileInput" multiple class="d-none" onchange="handlePickerFilesSelect(event)">
                <i class="fas fa-cloud-arrow-up text-warning fs-1 mb-2 d-block"></i>
                <h5 class="text-white font-weight-600">Yüklemek istediğiniz dosyaları sürükleyip bırakın</h5>
                <p class="text-muted fs-7 mb-0">veya tıklayarak dosya seçin (PNG, JPG, WEBP, GIF, PDF, SVG, MP4 max 50MB)</p>
            </div>
        </div>

        <!-- AJAX Upload Progress -->
        <div id="pickerUploadProgressBlock" class="p-3 rounded-4 mb-4 d-none" style="background: rgba(0,0,0,0.3); border: 1px solid var(--sm-border);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fs-7 text-white font-weight-500" id="pickerUploadStatusText">Dosyalar Yükleniyor...</span>
                <button class="btn btn-sm btn-link text-danger p-0 fs-7 text-decoration-none" id="btnAbortPickerUpload">İptal Et</button>
            </div>
            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05);">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="pickerUploadProgressBar" role="progressbar" style="width: 0%; background: #D4AF37;"></div>
            </div>
            <div class="d-flex justify-content-between mt-1 fs-7 text-muted">
                <span id="pickerUploadRemaining">Kalan Süre: Hesaplanıyor...</span>
                <span id="pickerUploadPercent">0%</span>
            </div>
        </div>

        <!-- Bulk Action Action Bar -->
        <div id="pickerBulkBar" class="d-none p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center" style="background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3);">
            <span class="fs-7 font-weight-600 text-white"><span id="pickerSelectedCount">0</span> dosya seçildi</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-secondary text-white border-0 py-1.5 px-3 fs-7" onclick="triggerPickerBulkAction('copy')">Kopyala</button>
                <button class="btn btn-sm btn-secondary text-white border-0 py-1.5 px-3 fs-7" onclick="triggerPickerBulkAction('webp')">WebP Yap</button>
                <button class="btn btn-sm btn-secondary text-white border-0 py-1.5 px-3 fs-7" onclick="triggerPickerBulkAction('download')">İndir (ZIP)</button>
                <button class="btn btn-sm btn-danger border-0 py-1.5 px-3 fs-7" onclick="triggerPickerBulkAction('delete')">Toplu Sil</button>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left directories block -->
            <div class="col-12 col-md-3">
                <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                    <h6 class="text-warning font-weight-700 mb-3 uppercase tracking-wider fs-7">Klasörler</h6>
                    <div id="pickerFoldersList" class="d-flex flex-column gap-2 mb-4">
                        <!-- Folders listed dynamically -->
                    </div>

                    <h6 class="text-warning font-weight-700 mb-3 uppercase tracking-wider fs-7">Dosya Tipleri</h6>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type active" onclick="SM_MediaPicker.setPickerTypeFilter('')" id="type-all">
                            <i class="fas fa-images me-2 text-warning"></i> Tüm Dosyalar
                        </button>
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type" onclick="SM_MediaPicker.setPickerTypeFilter('image')" id="type-image">
                            <i class="fas fa-image me-2 text-muted"></i> Resimler
                        </button>
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type" onclick="SM_MediaPicker.setPickerTypeFilter('video')" id="type-video">
                            <i class="fas fa-video me-2 text-muted"></i> Videolar
                        </button>
                        <button class="btn btn-link text-start text-white text-decoration-none p-2 rounded picker-filter-type" onclick="SM_MediaPicker.setPickerTypeFilter('pdf')" id="type-pdf">
                            <i class="fas fa-file-pdf me-2 text-muted"></i> Belgeler (PDF)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Central files grid view area -->
            <div class="col-12 col-md-9">
                <div class="p-3 rounded-4 overflow-y-auto" id="pickerMediaScrollContainer" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border); min-height: 480px; max-height: 80vh;">
                    <div class="row g-2" id="pickerMediaItemsContainer">
                        <!-- Media items dynamically populated -->
                    </div>
                    <div id="pickerLoader" class="text-center py-4 d-none">
                        <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Sidebar detail & editing SEO panel -->
    <div class="col-12 col-xl-3 details-panel border-start border-light border-opacity-10 p-5">
        <div id="pickerDetailsEmpty">
            <h5 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Dosya Detayları</h5>
            <?= ComponentHelper::emptyState('Detayları görüntülemek için bir dosya seçin.') ?>
        </div>

        <div id="pickerDetailsPanel" class="d-none">
            <h5 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Dosya Detayları</h5>
            
            <div class="text-center p-3 rounded-4 mb-4" style="background: rgba(0,0,0,0.2); border:1px solid var(--sm-border); min-height: 160px; display: flex; align-items: center; justify-content: center;">
                <img id="pickerPreviewImg" class="img-fluid rounded d-none" style="max-height: 160px; object-fit: contain;">
                <video id="pickerPreviewVideo" class="img-fluid rounded d-none" controls style="max-height: 160px; width:100%;"></video>
                <div id="pickerPreviewDoc" class="d-none text-center p-3">
                    <i class="fas fa-file-pdf text-danger mb-2" style="font-size: 50px;"></i>
                    <span class="d-block fs-8 text-white text-truncate" id="pickerDocLabel"></span>
                </div>
            </div>

            <div class="d-flex flex-column gap-3 mb-4" style="font-size: 13px;">
                <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--sm-border) !important;">
                    <span class="text-muted">Dosya Adı:</span>
                    <span class="text-white font-weight-500 text-truncate text-end" id="specName" style="max-width: 160px;"></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--sm-border) !important;">
                    <span class="text-muted">MIME Türü:</span>
                    <span class="text-white font-weight-500 text-end" id="specMime"></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--sm-border) !important;">
                    <span class="text-muted">Dosya Boyutu:</span>
                    <span class="text-white font-weight-500 text-end" id="specSize"></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--sm-border) !important;">
                    <span class="text-muted">Çözünürlük:</span>
                    <span class="text-white font-weight-500 text-end" id="specDimensions">-</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--sm-border) !important;">
                    <span class="text-muted">SHA256:</span>
                    <span class="text-white font-weight-500 text-truncate text-end" id="specHash" style="max-width: 160px;"></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--sm-border) !important;">
                    <span class="text-muted">Kullanım:</span>
                    <span class="font-weight-600 text-warning text-end" id="specUsages">Kullanılmıyor</span>
                </div>
            </div>

            <!-- Meta SEO Form -->
            <form id="pickerSeoForm" class="d-flex flex-column gap-3">
                <input type="hidden" id="seoFileId">
                
                <div>
                    <label class="form-label text-muted fs-7 font-weight-500 mb-1">Görsel Başlığı</label>
                    <input type="text" id="seoTitle" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border:1px solid var(--sm-border) !important;">
                </div>
                <div>
                    <label class="form-label text-muted fs-7 font-weight-500 mb-1">Alt Text (Alternatif Metin)</label>
                    <input type="text" id="seoAltText" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border:1px solid var(--sm-border) !important;">
                </div>
                <div>
                    <label class="form-label text-muted fs-7 font-weight-500 mb-1">Altyazı (Caption)</label>
                    <input type="text" id="seoCaption" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border:1px solid var(--sm-border) !important;">
                </div>
                <div>
                    <label class="form-label text-muted fs-7 font-weight-500 mb-1">Açıklama (Description)</label>
                    <textarea id="seoDescription" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border:1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>

                <!-- Tags Mapping checkboxes -->
                <div>
                    <label class="form-label text-muted fs-7 font-weight-500 mb-2 d-block">Etiketler</label>
                    <div class="d-flex flex-wrap gap-2 p-2 rounded" style="background: rgba(255,255,255,0.02); max-height: 120px; overflow-y: auto;" id="seoTagsContainer">
                        <!-- Dynamic checkboxes -->
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn py-2 w-100 fs-7" onclick="savePickerSeoMetadata()">Bilgileri Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add submodal folders create form -->
<div class="modal fade" id="pickerFolderCreateModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
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

<!-- Re-use the identical Javascript engine code -->
<script>
    const SM_MediaPicker = {
        options: {
            singleSelect: false,
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
        
        init() {
            this.loadTagsCheckboxList();
            this.loadMedia();
        },

        loadTagsCheckboxList() {
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
                    if (!append) {
                        this.renderFolders(data.folders);
                        this.renderBreadcrumbs(data.breadcrumbs);
                    }
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
            
            if (this.state.currentFolderId) {
                const rootBtn = document.createElement('button');
                rootBtn.className = 'btn btn-link text-start text-white text-decoration-none p-2 rounded fs-7 w-100 hover-bg';
                rootBtn.innerHTML = `<i class="fas fa-chevron-left text-warning me-2"></i> Üst Klasör (Geri)`;
                rootBtn.onclick = () => {
                    this.state.currentFolderId = null;
                    this.loadMedia();
                };
                list.appendChild(rootBtn);
            }

            if (folders.length === 0 && !this.state.currentFolderId) {
                list.innerHTML = `<div class="text-muted fs-8 p-2">Klasör bulunamadı.</div>`;
                return;
            }

            folders.forEach(f => {
                const fBtn = document.createElement('div');
                fBtn.className = 'd-flex align-items-center justify-content-between p-1 rounded hover-bg';
                fBtn.innerHTML = `
                    <button class="btn btn-link text-start text-white text-decoration-none p-1 fs-7 text-truncate flex-grow-1" onclick="SM_MediaPicker.navigateToFolder(${f.id})">
                        <i class="fas fa-folder text-warning me-2"></i> ${f.name}
                    </button>
                    <button class="btn btn-sm btn-link text-danger p-0" onclick="deleteFolder(${f.id})"><i class="fas fa-trash"></i></button>
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
                container.innerHTML = `<div class="col-12 text-center text-muted py-5 fs-7"><i class="fas fa-folder-open fs-3 d-block mb-2"></i>Bu klasörde dosya bulunmamaktadır.</div>`;
                return;
            }

            files.forEach(f => {
                const isChecked = this.state.selectedItems.some(i => i.id === f.id);
                
                let mediaPreview = '';
                if (f.mime_type.startsWith('image/')) {
                    mediaPreview = `<img src="<?= url("/") ?>/uploads/thumbnails/${f.filename}" alt="${f.alt_text || ''}" onerror="this.src='<?= url("/") ?>/${f.filepath}'" class="img-fluid rounded" style="max-height: 100px; object-fit: contain;">`;
                } else if (f.mime_type.startsWith('video/')) {
                    mediaPreview = `
                        <div class="video-preview-wrapper position-relative w-100 h-100" style="min-height: 100px;" onmouseenter="this.querySelector('video').play()" onmouseleave="this.querySelector('video').pause()">
                            <video src="<?= url("/") ?>/${f.filepath}" muted loop class="w-100 h-100" style="object-fit: cover; max-height: 100px;"></video>
                            <i class="fas fa-play position-absolute text-white" style="left:50%; top:50%; transform:translate(-50%,-50%); opacity:0.7;"></i>
                        </div>
                    `;
                } else {
                    mediaPreview = `<i class="fas fa-file-pdf text-danger" style="font-size: 36px;"></i>`;
                }

                if (this.state.viewMode === 'grid') {
                    const card = document.createElement('div');
                    card.className = `col-6 col-sm-4 col-md-3 picker-item-card`;
                    card.innerHTML = `
                        <div class="p-2 rounded-3 text-center position-relative ${isChecked ? 'border border-warning' : 'border border-transparent'}" id="picker-media-${f.id}" onclick="SM_MediaPicker.toggleSelection(${f.id}, ${JSON.stringify(f).replace(/"/g, '&quot;')})" style="background: rgba(255,255,255,0.03); cursor: pointer; transition: all 0.2s;">
                            <input type="checkbox" class="position-absolute picker-checkbox-overlay" style="top: 8px; left: 8px; accent-color: #D4AF37;" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation(); SM_MediaPicker.toggleCheckbox(${f.id}, ${JSON.stringify(f).replace(/"/g, '&quot;')})">
                            <div style="height: 100px; display:flex; align-items:center; justify-content:center; overflow:hidden;" class="mb-2">
                                ${mediaPreview}
                            </div>
                            <div class="fs-8 text-truncate text-white px-1">${f.original_name}</div>
                        </div>
                    `;
                    container.appendChild(card);
                } else {
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
            this.updateDetailsPanel(item);
            this.updateBulkActionBar();
        },

        toggleCheckbox(id, item) {
            this.toggleSelection(id, item);
        },

        updateDetailsPanel(item) {
            document.getElementById('pickerDetailsEmpty').classList.add('d-none');
            document.getElementById('pickerDetailsPanel').classList.remove('d-none');

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

            document.getElementById('specName').textContent = item.original_name;
            document.getElementById('specMime').textContent = item.mime_type;
            document.getElementById('specSize').textContent = item.formatted_size;
            document.getElementById('specDimensions').textContent = item.width ? (item.width + 'x' + item.height + 'px') : '-';
            document.getElementById('specHash').textContent = item.file_hash;
            
            // Map usages
            const usageSpan = document.getElementById('specUsages');
            if (item.usages && item.usages.length > 0) {
                usageSpan.textContent = item.usages.join(', ');
                usageSpan.className = 'font-weight-600 text-success text-end';
            } else {
                usageSpan.textContent = 'Kullanılmıyor';
                usageSpan.className = 'font-weight-600 text-warning text-end';
            }

            document.getElementById('seoFileId').value = item.id;
            document.getElementById('seoTitle').value = item.title || '';
            document.getElementById('seoAltText').value = item.alt_text || '';
            document.getElementById('seoCaption').value = item.caption || '';
            document.getElementById('seoDescription').value = item.description || '';

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
            if (this.state.selectedItems.length > 0) {
                bar.classList.remove('d-none');
                cnt.textContent = this.state.selectedItems.length;
            } else {
                bar.classList.add('d-none');
            }
        }
    };

    // Scroll
    document.getElementById('pickerMediaScrollContainer').addEventListener('scroll', function() {
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - 50) {
            if (SM_MediaPicker.state.hasMore && !SM_MediaPicker.state.loading) {
                SM_MediaPicker.state.page++;
                SM_MediaPicker.loadMedia(true);
            }
        }
    });

    // Paste
    window.addEventListener('paste', function(e) {
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

    function savePickerSeoMetadata() {
        const id = document.getElementById('seoFileId').value;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('title', document.getElementById('seoTitle').value);
        fd.append('alt_text', document.getElementById('seoAltText').value);
        fd.append('caption', document.getElementById('seoCaption').value);
        fd.append('description', document.getElementById('seoDescription').value);

        document.querySelectorAll('.picker-tag-cb:checked').forEach(cb => {
            fd.append('tags[]', cb.value);
        });

        fetch('<?= url("/admin/media/save-seo") ?>', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            alert(res.message);
            if (res.success) {
                SM_MediaPicker.loadMedia(true);
            }
        });
    }

    // Search input
    let searchTimeout = null;
    document.getElementById('pickerSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            SM_MediaPicker.state.searchQuery = this.value;
            SM_MediaPicker.loadMedia();
        }, 300);
    });

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

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        SM_MediaPicker.init();
    });
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
