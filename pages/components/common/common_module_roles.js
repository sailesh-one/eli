export const ModuleRoles = {
  name: 'ModuleRoles',
  data() {
    return {
      list: [],
      loading: false,
      get: null,
      type: null,
      targetId: null,
      targetName: null,
    };
  },
  methods: {
    async fetchRoles() {
      if (!this.targetId) return;
      this.loading = true;
      try {
        const res = await $http("POST", `${g.$base_url_api}/master-data`, {
          action: "getRoleModules",
          get: this.get,
          type: this.type,
          id: this.targetId,
        }, {});
        this.list = res.body?.status === "ok" ? res.body.data.list || [] : [];
      } catch {
        this.list = [];
      } finally {
        this.loading = false;
      }
    },
    open({ get, type, id, name }) {
      this.get = get;
      this.type = type;
      this.targetId = id;
      this.targetName = name;
      const canvas = new bootstrap.Offcanvas(document.getElementById("ModuleRolesOffcanvas"));
      canvas.show();
      this.fetchRoles();
    },
    close() {
      const el = document.getElementById("ModuleRolesOffcanvas");
      const instance = bootstrap.Offcanvas.getInstance(el);
      instance?.hide();
    }
  },
  template: /*html*/ `
<div class="offcanvas offcanvas-end h-100 rounded-start shadow-lg" tabindex="-1" id="ModuleRolesOffcanvas" aria-labelledby="ModuleRolesOffcanvasLabel">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold text-dark" id="ModuleRolesOffcanvasLabel">
        Mapped {{ get }} for {{ type }} - <span class="badge bg-dark">{{ targetName || '...' }}</span>
    </h5>
    <button type="button" class="btn-close" @click="close"></button>
  </div>

  <div class="offcanvas-body">
    <div v-if="loading" class="d-flex justify-content-center align-items-center h-100">
      <div class="spinner-border text-dark" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else>
      <ul v-if="list.length" class="list-group list-group-flush">
        <li v-for="row in list" :key="row.id" class="list-group-item px-0 py-3 border-bottom">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <span class="badge text-bg-light border border-secondary text-secondary rounded-pill me-2">#{{ row.id }}</span>
              <h6 class="fw-semibold mb-0">{{ row.name }}</h6>
            </div>
          </div>

          <div v-if="row.sublist && row.sublist.length" class="mt-2 ms-4">
            <span
              v-for="sub in row.sublist"
              :key="sub.id"
              class="badge rounded-pill text-bg-secondary me-2 mb-1 fw-normal">
              #{{ sub.id }} {{ sub.name }}
            </span>
          </div>
        </li>
      </ul>
      <div v-else class="text-center text-muted fst-italic py-5">
        <p>No roles mapped.</p>
      </div>
    </div>
  </div>
</div>
  `,
};
