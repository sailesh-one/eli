export const Executives = {
  name: 'Executives',
  props: {
    visible: { type: Boolean, default: false },
    title: { type: String, default: 'Assign Executive' },
    store: { type: Object, required: true },
    row: { type: Object, default: () => ({}) }
  },
  emits: ['close', 'select'],
  data: () => ({
    selectedBranch: '',
    selectedExec: null,
    isSubmitting: false,
  }),
  watch: {
    visible(val) {
      if (!val) {
        this.selectedBranch = '';
        this.selectedExec = null;
        this.isSubmitting = false;
      } else {
        const existingBranchId = this.row?.branch;
        const existingExecId = this.row?.executive;

        this.selectedBranch = existingBranchId && existingBranchId !== 0 ? String(existingBranchId) : '';
        this.selectedExec = null;

        if (existingExecId && existingExecId !== 0 && this.selectedBranch) {
          this.$nextTick(() => {
            const list = this.executiveOptions;
            const match = list.find(o => o?.value === String(existingExecId));
            if (match) {
              this.selectedExec = match;

              this.$nextTick(() => {
                try {
                  const refEl = this.$refs[`exec-${match.value}`];
                  if (refEl && refEl[0]) {
                    refEl[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                  }
                } catch (e) {
                  console.warn('Could not scroll to selected executive:', e);
                }
              });
            }
          });
        }
      }
    },

    // Watch selectedBranch to reset executive if branch is 0/empty
    selectedBranch(val) {
      if (!val || val === '0') {
        this.selectedExec = null;
      }
    }
  },
  computed: {
    branchOptions() {
      return this.store.masterLists?.branch || [{ value: '', label: 'Select Branch' }];
    },
    executiveOptions() {
      if (!this.selectedBranch) return [];
      return this.store.getExecutivesByBranchId(this.selectedBranch) || [];
    },
    modalTitle() {
      return this.row?.formatted_id
        ? `${this.title} (${this.row.formatted_id})`
        : this.title;
    },
  },
  methods: {
    async selectExec(exec) {
      if (!this.selectedBranch) return;

      this.isSubmitting = true;
      const execValue = exec ? exec.value : 0; // 0 means no executive
      this.selectedExec = exec || null;
      const id = this.row?.id;

      try {
        const success = await this.store.assignExecutive({
          id,
          branch: this.selectedBranch,
          executive: execValue
        });

        if (success) this.closeModal();

        this.$emit('select', {
          id,
          branch: this.selectedBranch,
          exec: execValue
        });
      } finally {
        this.isSubmitting = false;
      }
    },
    closeModal() {
      if (this.isSubmitting) return;
      this.selectedExec = null;
      this.selectedBranch = '';
      this.isSubmitting = false;
      this.$emit('close');
    },
  },
  template: /*html*/`
    <div v-if="visible" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4);">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 shadow-sm">
          <div class="modal-header popup-head">
            <h6 class="modal-title">{{ modalTitle }}</h6>
            <button type="button" class="btn-close" @click="closeModal" :disabled="isSubmitting"></button>
          </div>
          <div class="modal-body p-2" style="max-height: 300px; overflow-y: auto;">
            <!-- Branch dropdown -->
            <div class="mb-2">
              <select v-model="selectedBranch" class="form-select form-select-sm" :disabled="isSubmitting">
                <option v-for="b in branchOptions" :key="b.value" :value="b.value">
                  {{ b.label }}
                </option>
              </select>
            </div>

            <!-- Executives list -->
            <ul v-if="executiveOptions.length" class="list-group list-group-flush">
              <li v-for="exec in executiveOptions" 
                  :key="exec.value"
                  :ref="'exec-' + exec.value"
                  class="list-group-item d-flex align-items-center"
                  style="cursor: pointer;">
                <input type="radio"
                       :id="'exec-'+exec.value"
                       :value="exec"
                       v-model="selectedExec"
                       class="form-check-input me-2"
                       :disabled="isSubmitting">
                <label :for="'exec-'+exec.value" class="mb-0">{{ exec.label }}</label>
              </li>
            </ul>
            <p v-else class="text-muted small mb-0">No executives for this branch.</p>
          </div>
          <div class="modal-footer p-2">
            <button class="btn btn-sm btn-outline-secondary" @click="closeModal" :disabled="isSubmitting">Cancel</button>
            <button class="btn btn-sm btn-dark" 
                    :disabled="!selectedBranch || isSubmitting"
                    @click="selectExec(selectedExec)">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
              Select
            </button>
          </div>
        </div>
      </div>
    </div>
  `
};
