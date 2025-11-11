export const ImageViewer = {
  name: 'ImageViewer',
  data() {
    return {
      name: '',
      urls: [],
      activeIndex: 0,
      defaultImage: '/assets/images/default-image.jpg',
    };
  },
  computed: {
    isFirstImage() {
      return this.activeIndex === 0;
    },
    isLastImage() {
      return this.activeIndex === this.urls.length - 1;
    },
  },
  watch: {
    activeIndex(newVal) {
      this.scrollThumbTo(newVal);
    },
  },
  methods: {
    openImages(name, urls = [], imageIndex = 0) {
      this.name = name;
      this.urls = Array.isArray(urls) ? urls : [];
      this.activeIndex = Math.min(Math.max(imageIndex, 0), this.urls.length - 1);

      this.$nextTick(() => this.scrollThumbTo(this.activeIndex));

      const modalEl = document.getElementById('imageGalleryComp');
      new bootstrap.Modal(modalEl).show();
    },

    handleImageError(event) {
      event.target.src = this.defaultImage;
    },

    selectImage(index) {
      if (index === this.activeIndex || index < 0 || index >= this.urls.length) return;
      this.activeIndex = index;
    },

    scrollThumbTo(index) {
      this.$nextTick(() => {
        const strip = this.$refs.thumbs;
        if (!strip) return;
        const img = strip.querySelectorAll('img')[index];
        img?.scrollIntoView({ behavior: 'smooth', inline: 'center' });
      });
    },
  },
  template: /*html*/`
    <div class="modal fade" id="imageGalleryComp" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
        <div class="modal-content border-0 rounded shadow-sm">

          <!-- Header -->
          <div class="modal-header popup-head">
            <h6 class="modal-title fw-bold text-dark small text-truncate fs-6">{{ name }}</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <!-- Carousel -->
          <div class="modal-body p-2">
            <div class="d-flex align-items-center gap-2">

              <!-- Prev Button -->
              <button v-if="urls.length > 1"
                class="btn border-0 d-flex align-items-center justify-content-center flex-shrink-0"
                :disabled="isFirstImage"
                @click="selectImage(activeIndex - 1)"
                style="width:36px; height:36px;">
                <i class="bi bi-chevron-left fs-5"></i>
              </button>

              <!-- Main Carousel -->
              <div id="mainCarousel" ref="mainCarousel" class="carousel slide flex-fill">
                <div class="carousel-inner rounded bg-light">
                  <div v-for="(url, index) in urls" :key="index"
                    class="carousel-item"
                    :class="{ active: index === activeIndex }">
                    <div class="ratio ratio-16x9">
                      <img :src="url"
                        class="d-block w-100 h-100 object-fit-contain"
                        :alt="name + ' image ' + (index + 1)"
                        @error="handleImageError"
                        style="max-height:65vh;">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Next Button -->
              <button v-if="urls.length > 1"
                class="btn border-0 d-flex align-items-center justify-content-center flex-shrink-0"
                :disabled="isLastImage"
                @click="selectImage(activeIndex + 1)"
                style="width:36px; height:36px;">
                <i class="bi bi-chevron-right fs-5"></i>
              </button>

            </div>

            <!-- Thumbnails -->
            <div v-if="urls.length > 1"
              ref="thumbs"
              class="d-flex flex-nowrap overflow-auto gap-2 mt-3 pt-2 border-top">
              <div v-for="(url, index) in urls" :key="index" class="flex-shrink-0">
                <img :src="url"
                  @click="selectImage(index)"
                  class="rounded cursor-pointer"
                  :class="index === activeIndex ? 'border border-dark border-2 shadow-sm' : 'opacity-50'"
                  @error="handleImageError"
                  style="width:clamp(55px, 12vw, 80px); height:clamp(40px, 8vw, 60px); object-fit:cover;">
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  `
};