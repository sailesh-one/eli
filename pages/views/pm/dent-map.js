export const DentMap = {
  name: 'DentMap',
  props: {
    store: { type: Object, required: true },
  },

  data() {
    return {
      uploadQueue: [],
      isProcessingQueue: false,
      deleteTarget: { key: null, label: "", src: "", id: null },
      primaryImageUrl: '/assets/images/dent-map.svg',
      imageSize: { width: 1509, height: 1080 },
      displayedSize: { width: 0, height: 0, offsetX: 0, offsetY: 0 },
      selectedSpot: null,
      impactColors: {},
      groupedLegend: [],
      resizeTimeout: null
    };
  },

  computed: {
    imperfections() {
      return this.store?.getDetails?.dent_map || [];
    },

    validImperfections() {
      return this.imperfections.filter(
        (imp) =>
          imp.removed !== 'y' &&
          imp.imperfectionType !== 'Downcoat' &&
          imp.xPos > 0 &&
          imp.yPos > 0
      );
    },

    scaledSpots() {
      if (!this.displayedSize.width || !this.imageSize.width) return [];

      const { width: dispW, height: dispH, offsetX, offsetY } = this.displayedSize;
      const scaleX = dispW / this.imageSize.width;
      const scaleY = dispH / this.imageSize.height;

      return this.validImperfections
        .map((imp) => ({
          id: imp.id,
          imperfectionType: imp.imperfectionType,
          imperfectionImpact: imp.imperfectionImpact,
          imperfectionPart: imp.imperfectionPart,
          imperfectionPosition: imp.imperfectionPosition,
          remarks: imp.remarks,
          images: imp.imageLink ? [imp.imageLink] : (imp.images || []),
          scaledX: parseInt(imp.xPos) * scaleX + offsetX,
          scaledY: parseInt(imp.yPos) * scaleY + offsetY,
          color: this.getImpactColor(imp.imperfectionImpact)
        }))
        // Sort spots so higher impact appears on top (zIndex)
        .sort((a, b) => {
          // If impact is numeric, sort descending; else fallback alphabetical
          const numA = parseFloat(a.imperfectionImpact);
          const numB = parseFloat(b.imperfectionImpact);
          if (!isNaN(numA) && !isNaN(numB)) return numB - numA;
          return a.imperfectionImpact.localeCompare(b.imperfectionImpact);
        });
    }
  },

  async created() {
    await this.store?.init?.();
  },

  mounted() {
    this.observeImageResize();
  },

  beforeUnmount() {
    if (this.resizeObserver) this.resizeObserver.disconnect();
    if (this.resizeTimeout) clearTimeout(this.resizeTimeout);
  },

  watch: {
    imperfections: {
      immediate: true,
      handler() {
        this.generateImpactColors();
        this.updateLegend();
      }
    }
  },

  methods: {
    generateImpactColors() {
      const fallbackColors = [
        '#dc2626', '#2563eb', '#16a34a', '#f59e0b',
        '#9333ea', '#0891b2', '#ea580c', '#b91c1c',
        '#64748b', '#475569'
      ];

      // Get unique imperfectionImpact values
      const impacts = [...new Set(this.imperfections.map(i => i.imperfectionImpact))].sort();

      // Assign colors sequentially
      this.impactColors = {};
      impacts.forEach((impact, idx) => {
        this.impactColors[impact] = fallbackColors[idx % fallbackColors.length];
      });
    },

    updateLegend() {
      // Legend grouped by impact
      this.groupedLegend = Object.keys(this.impactColors)
        .sort()
        .map(impact => ({
          impact,
          color: this.impactColors[impact]
        }));
    },

    getImpactColor(name) {
      return this.impactColors[name] || '#6c757d';
    },

    observeImageResize() {
      const img = this.$refs.dentMapImg;
      const container = this.$refs.dentMapContainer;

      if (!img || !container) return;

      this.resizeObserver = new ResizeObserver(() => {
        if (this.resizeTimeout) clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
          const rect = img.getBoundingClientRect();
          const contRect = container.getBoundingClientRect();

          const imageAspect = this.imageSize.width / this.imageSize.height;
          const containerAspect = rect.width / rect.height;

          let displayedWidth = rect.width;
          let displayedHeight = rect.height;
          let offsetX = rect.left - contRect.left;
          let offsetY = rect.top - contRect.top;

          if (imageAspect > containerAspect) {
            displayedHeight = rect.width / imageAspect;
            offsetY += (rect.height - displayedHeight) / 2;
          } else {
            displayedWidth = rect.height * imageAspect;
            offsetX += (rect.width - displayedWidth) / 2;
          }

          this.displayedSize = { width: displayedWidth, height: displayedHeight, offsetX, offsetY };
        }, 100);
      });

      this.resizeObserver.observe(img);
    },

    handleSpotClick(spot) {
      this.selectedSpot = this.selectedSpot?.id === spot.id ? null : spot;
    },

    getSpotStyle(spot) {
      const isSelected = this.selectedSpot?.id === spot.id;
      const zIndex = isSelected ? 20 : 10; // base zIndex
      return {
        left: `${spot.scaledX}px`,
        top: `${spot.scaledY}px`,
        transform: 'translate(-50%, -50%)',
        position: 'absolute',
        zIndex,
        cursor: 'pointer'
      };
    },


    openImageViewer(name, urls, index) {
        this.$refs.viewer?.openImages?.(name, urls, index);
    },

  },

  template: /*html*/`
    <div class="container p-0 m-0 ps-2">
        <ImageViewer ref="viewer" />
      <div class="card border-0 shadow-sm rounded-1">
        <div class="card-header bg-dark bg-opacity-10 py-2">
          <h6 class="mb-0 text-dark fw-semibold">Map</h6>
        </div>

        <div class="card-body p-0 position-relative">
          <div ref="dentMapContainer" class="position-relative w-100">
            <img ref="dentMapImg" :src="primaryImageUrl" alt="Vehicle Dent Map" class="img-fluid w-100 d-block object-fit-contain" style="max-height:60vh; pointer-events:none;" />

            <!-- Damage Spots -->
            <div v-for="spot in scaledSpots" :key="spot.id" class="position-absolute" :style="getSpotStyle(spot)" @click="handleSpotClick(spot)" @touchstart="handleSpotClick(spot)">
              <div v-if="selectedSpot?.id === spot.id" class="rounded-circle border border-2 border-secondary shadow-lg d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                <div class="rounded-circle border border-white" :style="{ width: '9px', height: '9px', backgroundColor: spot.color }"></div>
              </div>
              <div v-else class="rounded-circle border border-white shadow-sm" :style="{ width: '14px', height: '14px', backgroundColor: spot.color }"></div>
            </div>

            <!-- Spot Detail Popup -->
            <div v-if="selectedSpot" class="position-absolute bottom-0 start-0 end-0 bg-white border-top shadow p-3 rounded-3 m-2" style="z-index: 20; max-height: 50%; overflow-y: auto;">
              <button @click="selectedSpot = null" type="button" class="btn-close position-absolute top-0 end-0 m-2"></button>

              <div class="row g-2">
                <div class="col-12 col-md-6">
                  <small class="text-muted d-block">Type</small>
                  <strong>{{ selectedSpot.imperfectionType }}</strong>
                </div>
                <div class="col-12 col-md-6">
                  <small class="text-muted d-block">Impact</small>
                  <span class="badge text-white" :style="{ backgroundColor: selectedSpot.color }">
                    {{ selectedSpot.imperfectionImpact }}
                  </span>
                </div>
                <div class="col-12 col-md-6">
                  <small class="text-muted d-block">Part</small>
                  <strong>{{ selectedSpot.imperfectionPart }}</strong>
                </div>
                <div class="col-12 col-md-6">
                  <small class="text-muted d-block">Position</small>
                  <strong>{{ selectedSpot.imperfectionPosition }}</strong>
                </div>
                <div class="col-12" v-if="selectedSpot.remarks">
                  <small class="text-muted d-block">Remarks</small>
                  <p class="mb-0">{{ selectedSpot.remarks }}</p>
                </div>
              </div>

              <!-- Thumbnails -->
              <div class="d-flex flex-wrap mt-3" v-if="selectedSpot.images?.length">
                <div
                  v-for="(img, idx) in selectedSpot.images"
                  @touchstart.passive="handleSpotClick(spot)"
                  :key="idx"
                  class="me-2 mb-2"
                  role="button"
                  @click="openImageViewer(selectedSpot.imperfectionPart, selectedSpot.images, idx)"
                >
                  <img
                    :src="img"
                    alt="Damage Image"
                    class="img-thumbnail rounded shadow-sm"
                    style="width: 90px; height: 90px; object-fit: cover; cursor: pointer;"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div v-if="groupedLegend.length" class="card border-0 shadow-sm rounded-1 mt-3">
        <div class="card-body py-2 d-flex flex-wrap align-items-center">
          <small class="text-muted me-3">Legend:</small>
          <span v-for="legend in groupedLegend" :key="legend.impact" class="d-inline-flex align-items-center me-3 mb-1">
            <span class="rounded-circle me-2" :style="{ display: 'inline-block', backgroundColor: legend.color, width: '12px', height: '12px' }"></span>
            <span class="fw-semibold text-muted">{{ legend.impact }}</span>
          </span>
        </div>
      </div>
    </div>
  `
};
