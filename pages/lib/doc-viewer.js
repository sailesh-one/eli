export const DocViewer = {
  name: 'DocViewerManager',
  data() {
    return {
      docs: [],
      activeDocId: null,
      loading: false,
      _switchTimeout: null
    };
  },
  methods: {
    openDoc(name, url) {
      let doc = this.docs.find(d => d.name === name);
      if (doc) {
        if (doc.url !== url) doc.url = url;
        if (doc.minimized) this.showDoc(doc.id);
        return;
      }
      for (const d of this.docs) d.minimized = true;
      const id = `${name}-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
      this.docs.push({ id, name, url, minimized: false });
      this.stageDocSwitch(id);
    },
    showDoc(id) {
      const doc = this.docs.find(d => d.id === id);
      if (!doc || (!doc.minimized && this.activeDocId === id && !this.loading)) return;
      for (const d of this.docs) d.minimized = d.id !== id;
      this.stageDocSwitch(id);
    },
    minimizeDoc(id) {
      const doc = this.docs.find(d => d.id === id);
      if (!doc || doc.minimized) return;
      doc.minimized = true;
      if (this.activeDocId === id) {
        const next = this.docs.find(d => !d.minimized);
        this.stageDocSwitch(next ? next.id : null);
      }
    },
    closeDoc(id) {
      const idx = this.docs.findIndex(d => d.id === id);
      if (idx === -1) return;
      const wasActive = this.activeDocId === id;
      this.docs.splice(idx, 1);
      if (wasActive) {
        const next = this.docs.find(d => !d.minimized);
        this.stageDocSwitch(next ? next.id : null);
      }
      if (!this.docs.length) {
        this.activeDocId = null;
        this.loading = false;
      }
    },
    handleIframeLoad() {
      this.loading = false;
    },
    stageDocSwitch(newId) {
      if (this.activeDocId === newId && !this.loading) return;
      if (this._switchTimeout) clearTimeout(this._switchTimeout);
      this.loading = true;
      this.activeDocId = null;
      this._switchTimeout = setTimeout(() => {
        this.activeDocId = newId;
        this.loading = !newId ? false : this.loading;
      }, 100);
    },
  },
  computed: {
    activeDoc() {
      return this.docs.find(d => d.id === this.activeDocId) || null;
    }
  },
  watch: {
    docs: {
      deep: true,
      handler(newDocs) {
        const maximized = newDocs.filter(d => !d.minimized);
        if (maximized.length > 1) {
          for (const d of this.docs) d.minimized = d.id !== this.activeDocId;
        }
        if (!newDocs.length) this.activeDocId = null;
        if (this.activeDocId && !newDocs.some(d => d.id === this.activeDocId && !d.minimized)) {
          const next = newDocs.find(d => !d.minimized);
          this.stageDocSwitch(next ? next.id : null);
        }
      }
    }
  },
  template: /*html*/ `
  <div>
    <div class="position-fixed start-0 bg-black bg-opacity-50 h-100 top-0 w-100" style="z-index: 1040; backdrop-filter: blur(2px) saturate(150%);" v-if="activeDoc || loading"></div>
    <div class="position-fixed bg-white border overflow-hidden rounded shadow-lg start-50 top-50 translate-middle" style="width: 90vw; height: 90vh; z-index: 1050; transition: transform 0.3s ease-in-out;" v-if="activeDoc || loading">
        <div class="d-flex align-items-center popup-head justify-content-between p-2 rounded-top text-white" v-if="activeDoc">
            <h2 class="fw-semibold">{{ activeDoc.name }}</h2>
            <div>
                <a :download="activeDoc.name" :href="activeDoc.url" class="p-2 text-dark" target="_blank" title="Download Document"><i class="bi bi-download fs-6"></i> </a>
                <a class="me-1 p-2 text-dark" @click="minimizeDoc(activeDoc.id)" title="Minimize"><i class="bi bi-fullscreen-exit fs-6"></i></a>
                <button class="btn btn-close" @click="closeDoc(activeDoc.id)" title="Close"></button>
            </div>
        </div>
        <div class="p-0 position-relative doc-width" style="height: calc(90vh - 44px);">
            <div class="d-flex align-items-center bg-white h-100 justify-content-center position-absolute w-100" style="z-index: 10; opacity: 0.8;" v-if="loading">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>
          <iframe :src="activeDoc.url" @load="handleIframeLoad" frameborder="0" height="100%" ref="docIframe" v-if="activeDoc" width="100%"></iframe>
        </div>
    </div>
    <div class="d-flex bg-light border-top bottom-0 end-0 p-1 position-fixed shadow-sm start-0" style="z-index: 1100;" v-if="docs.length">
        <div
            class="btn btn-sm align-items-center d-flex me-2"
            style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; height: 100%;"
            :class="doc.minimized ? 'btn-outline-dark' : 'btn-dark text-white'"
            :key="doc.id"
            @click="showDoc(doc.id)"
            v-for="doc in docs"
        >
            <i class="bi bi-fullscreen me-1"></i><span class="text-truncate">{{ doc.name.length > 7 ? doc.name.substring(0, 7) + '..' : doc.name }}</span>
            <button class="btn-close btn-close-sm ms-2" :class="doc.minimized ? 'btn-close-dark' : 'btn-close-white'" @click.stop="closeDoc(doc.id)"></button>
        </div>
    </div>
</div>`
};
// **** Usage ************
// const [{ DocViewer }] = await $importComponent([
//   '/pages/lib/doc-viewer.js'
// ]);
// components: { DocViewer }
// methods: {
//   openDoc(name, url) {
//    this.$refs.docViewer.openDoc(name, url);
//   }
// <DocViewer ref="docViewer" />
// <button class="btn btn-primary m-1" @click="openDoc('Doc 1', 'sample.doc')">Open Doc 1</button>
