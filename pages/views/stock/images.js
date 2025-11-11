const [{ ImageViewer }] = await $importComponent([
  '/pages/lib/image-viewer.js'
]);

export const Images = {
  name: 'images',
  components: { ImageViewer },
  props: {
    store: { type: Object, required: true }
  },
  data() {
    return {
      allowedTypes: ["image/jpeg", "image/jpg", "image/png"],
      uploadQueue: [],
      isProcessingQueue: false,
      deleteTarget: { 
        id: null,
        key: null, 
        label: "", 
        src: "" 
      }
    };
  },

  computed: {
    mergedImagesConfig() {
      const master = this.store.getImagesConfig || {};
      const detailImages = this.store.getDetails?.images || {};

      return Object.fromEntries(
        Object.entries(master).map(([key, img]) => {
          const detail = detailImages[key] || {};
          const part = img.imgPart || {};

          return [
            key,
            {
              ...img,
              id: detail.imgId || part.imgId || null,
              label: img.fieldLabel || detail.imgName || part.imgName || key,
              src: detail.imgPath || detail.url || img.src || "",
              file: img.file || null,
              isUploading: img.isUploading || false,
              queueStatus: (detail.imgPath || detail.url) ? "done" : (img.queueStatus || "idle"),
              isLoading: true,
              placeholder: detail.imgOverlayLogo || part.imgOverlayLogo || "",
              imgPart: detail.imgId ? detail : part
            }
          ];
        })
      );
    }
  },

  watch: {
    'store.detail.images': {
      handler(newVal) {
        const master = this.store.getImagesConfig || {};
        if (!newVal) return;

        Object.keys(master).forEach(key => {
          const detail = newVal[key];
          master[key].src = detail?.imgPath || detail?.url || "";
          master[key].file = null;
          master[key].queueStatus = (detail?.imgPath || detail?.url) ? "done" : "idle";
          master[key].isLoading = true;
        });
      },
      deep: true,
      immediate: true
    }
  },

  async created() {
    this.store?.init?.();
  },

  methods: {
    triggerFileUpload(img, key) {
      this.$refs[`fileInput_${key}`][0].click();
    },

    handleFileChange(event, img, key) {
      const file = event.target.files[0];
      if (!file) return;

      if (!this.allowedTypes.includes(file.type)) {
        $toast("warning", "Only JPG, JPEG, and PNG files are allowed.");
        this.$refs[`fileInput_${key}`][0].value = "";
        return;
      }

      const storeImg = this.store.getImagesConfig[key];
      storeImg.file = file;
      storeImg.queueStatus = "idle";
      storeImg.src = URL.createObjectURL(file); // preview
      storeImg.isLoading = false;
    },

    removeSelectedFile(img, key) {
      const storeImg = this.store.getImagesConfig[key];
      storeImg.file = null;
      storeImg.src = "";
      storeImg.queueStatus = "idle";
      storeImg.isLoading = true;
      this.$refs[`fileInput_${key}`][0].value = "";
    },

    async enqueueUpload(img, key) {
      const storeImg = this.store.getImagesConfig[key];
      if (!storeImg.file) {
        $toast("warning", "Please select an image first.");
        return;
      }

      storeImg.queueStatus = "queued";
      this.uploadQueue.push(key);
      this.processQueue();
    },

    async processQueue() {
      if (this.isProcessingQueue || this.uploadQueue.length === 0) return;
      this.isProcessingQueue = true;

      const key = this.uploadQueue.shift();
      const imgData = this.store.getImagesConfig[key];

      try {
        if (!imgData) throw new Error(`Image configuration not found for key: ${key}`);

        imgData.isUploading = true;
        imgData.queueStatus = "uploading";

        const result = await this.store.uploadImage(key, imgData.file);

        if (result.success) {
          imgData.queueStatus = "done";
          this.store.detail.images = {
            ...this.store.detail.images,
            [key]: { url: result.data?.url || imgData.src }
          };
        } else {
          throw new Error(result.error || `Failed to upload image for ${imgData.label}`);
        }
      } catch (err) {
        console.error(err);
        imgData.queueStatus = "idle";
      } finally {
        imgData.isUploading = false;
        this.isProcessingQueue = false;
        this.processQueue();
      }
    },

    deleteImage(img, key) {
      this.deleteTarget = {
        key,
        label: img.label,
        id: img.id,
        src: img.src || img.placeholder
      };
      const modal = new bootstrap.Modal(document.getElementById("deleteConfirmModal"));
      modal.show();
    },

    async handleDeleteConfirmed() {
      const key = this.deleteTarget.key;
      if (!key) return;

      const result = await this.store.deleteImage(key, this.deleteTarget.id);

      if (!result.success) {
      } else {
        const detailImages = { ...this.store.detail.images };
        delete detailImages[key];
        this.store.detail.images = detailImages;
      }

      const modalEl = document.getElementById("deleteConfirmModal");
      const modal = bootstrap.Modal.getInstance(modalEl);
      modal.hide();

      this.deleteTarget = { 
        id: null,
        key: null, 
        label: "", 
        src: "" 
      };
    },

    openGallery(img, key = '') {
      const keys = Object.keys(this.mergedImagesConfig)
        .filter(k => this.mergedImagesConfig[k].src && !this.mergedImagesConfig[k].file);
      const urls = keys.map(k => this.mergedImagesConfig[k].src);

      if (!urls.length) return;

      const startIndex = keys.indexOf(key);
      this.$refs.imageViewer.openImages('Images', urls, startIndex);
    }
  },

  template: /*html*/`
    <ImageViewer ref="imageViewer" />
    

    <div class="container my-5">

    <div v-if="store.isProcessing && Object.keys(store.getImagesConfig || {}).length === 0" class="row g-4 justify-content-center">
      <div v-for="n in 8" :key="n" class="col-6 col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
          <div class="ratio ratio-4x3 bg-light animate-pulse"></div>
          <div class="card-body text-center py-2 px-1 bg-light bg-opacity-50">
            <small class="fw-semibold text-muted text-uppercase d-block">&nbsp;</small>
          </div>
        </div>
      </div>
    </div>

      <div class="row g-4 justify-content-center">
        <div v-for="(img, key) in mergedImagesConfig" :key="key" class="col-6 col-md-4 col-lg-3">
          <div class="card border-0 shadow-sm h-100 rounded-4 position-relative overflow-hidden">

            <button 
              v-if="img.src && !img.file" 
              class="btn btn-sm btn-light text-danger shadow-sm position-absolute top-0 end-0 m-2 z-1"
              @click.stop="deleteImage(img, key)"
              title="Delete Existing">
              <i class="bi bi-trash-fill fs-6"></i>
            </button>

            <div class="ratio ratio-4x3 bg-light position-relative overflow-hidden">
              <!-- Skeleton -->
              <div 
                v-if="img.isLoading" 
                class="position-absolute top-0 start-0 w-100 h-100 bg-secondary bg-opacity-25 animate-pulse">
              </div>

              <img
                :src="img.src || img.placeholder"
                loading="lazy"
                class="w-100 h-100 bg-white"
                style="object-fit: contain;"
                :alt="img.label"
                @load="img.isLoading = false"
                @error="img.isLoading = false"
                role="button"
                @click="img.src && !img.file ? openGallery(img, key) : triggerFileUpload(img, key)"
              />
              
              <div 
                v-if="img.file" 
                class="position-absolute bottom-0 start-0 end-0 d-flex justify-content-between align-items-center p-2 bg-white bg-opacity-75">
                
                <button 
                  class="btn btn-sm btn-dark rounded-pill fw-bold"
                  @click.stop="removeSelectedFile(img, key)" title="Remove Selected">
                  <i class="bi bi-x-circle fs-6"></i>
                </button>

                <button 
                  class="btn btn-sm btn-dark gap-2 fw-bold rounded-pill shadow pulse-button d-flex align-items-center"
                  :disabled="img.queueStatus === 'queued' || img.queueStatus === 'uploading'"
                  @click.stop="enqueueUpload(img, key)" title="Upload Selected">

                  <template v-if="img.queueStatus === 'queued'">
                    <i class="bi bi-hourglass-split me-1"></i> Queued
                  </template>

                  <template v-else-if="img.queueStatus === 'uploading'">
                    <span class="spinner-border spinner-border-sm me-2"></span> Uploading...
                  </template>

                  <template v-else>
                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload
                  </template>
                </button>
              </div>

              <input 
                type="file" 
                accept=".jpg,.jpeg,.png"
                class="d-none" 
                :ref="'fileInput_' + key" 
                @change="e => handleFileChange(e, img, key)" 
              />
            </div>

            <div class="card-body text-center py-2 px-1 bg-light bg-opacity-50">
              <small class="fw-semibold text-muted text-uppercase d-block">{{ img?.label }}</small>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header popup-head">
              <h5 class="modal-title fw-bold text-danger">Confirm Delete</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2 text-center">
              <p>
                Are you sure you want to delete the 
                <span class="fw-semibold text-capitalize">{{ deleteTarget.label }}</span> image?
              </p>
              <img :src="deleteTarget.src" alt="preview" class="img-fluid rounded shadow-sm border" style="max-height:200px; object-fit:contain;">
            </div>
            <div class="modal-footer border-0 pt-2">
              <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-danger rounded-pill" @click="handleDeleteConfirmed">
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
};
