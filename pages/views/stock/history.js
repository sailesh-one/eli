export const History = {
  name: 'History',
  props: {
    store: { type: Object, required: true }
  },

  computed: {
    // Use full grouped config, not flattened
    columns: vm => vm.store?.getHistoryConfig || [],
    list: vm => vm.store?.getDetails?.history || [],
  },

  methods: {
    open() {
      const el = document.getElementById("HistoryOffcanvas");
      const instance = bootstrap.Offcanvas.getOrCreateInstance(el);
      instance.show();
    },
    close() {
      const el = document.getElementById("HistoryOffcanvas");
      const instance = bootstrap.Offcanvas.getInstance(el);
      instance?.hide();
    },
   formatCellValue(row, col) {
      let val = Array.isArray(col.key)
        ? col.key
            .map(k => row[k])
            .filter(Boolean)
            .join(col.type === 'concat' ? ', ' : ' ')
        : row[col.key];

      if (!val || (col.type === 'concat' && val === '0')) return ''; // return empty for concat 0
      if (col.type === 'date') return $formatTime(val);
      return val;
    },
  },

  template: /*html*/`
    <div class="d-inline-block">
      <!-- 👉 Trigger Button -->
      <button @click="open" class="btn btn-sm btn-outline-dark shadow-sm">
        <i class="bi bi-clock-history"></i> History
      </button>

      <!-- 👉 Offcanvas -->
      <div class="offcanvas offcanvas-end h-100 rounded-start shadow-lg"
           tabindex="-1"
           id="HistoryOffcanvas"
           aria-labelledby="HistoryOffcanvasLabel"
           style="width: 1000px;">
        
        <div class="offcanvas-header border-bottom">
          <h5 class="offcanvas-title fw-bold text-dark" id="HistoryOffcanvasLabel">
            History
          </h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>

        <div class="offcanvas-body">

          <div v-if="list.length">
            <div class="table-responsive overflow-auto">
              <table class="table table-sm table-hover table-striped table-bordered align-middle mb-0 rounded-3 overflow-hidden">
                <thead class="bg-light text-dark small">
                  <tr>
                    <th v-for="group in columns" :key="group.title" class="fw-semibold text-nowrap px-2 py-1">
                      {{ group.title }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in list" :key="row.id">
                    <td v-for="group in columns" :key="group.title" class="px-2 py-1 small align-top">
                      <div v-for="col in group.data" :key="col.label||col.key" class="mb-1">
                        
                        <!-- text/concat/date -->
                        <template v-if="['text','concat','date'].includes(col.type)">
                          <strong v-if="col.label">{{ col.label }}: </strong>{{ formatCellValue(row, col) }}
                        </template>

                        <!-- badge -->
                        <template v-else-if="col.type==='badge'">
                          <span class="badge rounded-pill shadow-sm bg-secondary">
                            {{ formatCellValue(row, col) }}
                          </span>
                        </template>

                        <!-- fallback -->
                        <template v-else>
                          <span class="text-muted"></span>
                        </template>

                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <p v-else class="text-muted">No history found.</p>
        </div>
      </div>
    </div>
  `,
};
