export const Executives = {
  name: 'Executives',
  props: {
    visible: { type: Boolean, default: false },
    title: { type: String, default: 'Assign Executive' },
    store: { type: Object, required: true },
    attachKey: { type: String, default: '' },
    row: { type: Object, default: () => ({}) }  
  },
  emits: ['close', 'select'],
  data: () => ({
    selected: null,
  }),
  watch: {
    visible(val) {
      if (!val) {
        this.selected = null;
      } else {
        // On opening, safely check existing value
        const existingId = this.row?.[this.attachKey]; 
        if (existingId && Array.isArray(this.options) && this.options.length) {
          const match = this.options.find(o => o?.value === existingId);
          if (match) {
            this.selected = match;

            // Scroll safely after DOM render
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
        }
      }
    },
  },
  computed: {
    options() {
      return Array.isArray(this.store.getExecutives) ? this.store.getExecutives : [];
    },
    modalTitle() {
      return this.row?.formatted_id ? `${this.title} (${this.row.formatted_id})` : this.title;
    }
  },
  methods: {
   async selectExec(exec) {
    if (!exec) return;
    this.selected = exec;
    const id = this.row?.id; // Use the encrypted id field

    // Send only the value of the executive
    const success = await this.store.assignExecutive({ 
        id, 
        executive: exec.value 
    });

    if (success) this.closeModal();
    this.$emit('select', { id, exec: exec.value });
    },
    closeModal() {
      this.selected = null;
      this.$emit('close');
    },
  },
  template: /*html*/`
    <div v-if="visible" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4);">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 shadow-sm">
          <div class="modal-header popup-head">
            <h6 class="modal-title">{{ modalTitle }}</h6>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-2" style="max-height: 250px; overflow-y: auto;">
            <ul class="list-group list-group-flush">
              <li v-for="exec in options" 
                  :key="exec.value"
                  :ref="'exec-' + exec.value"
                  class="list-group-item d-flex align-items-center"
                  style="cursor: pointer;">
                <input type="radio"
                       :id="'exec-'+exec.value"
                       :value="exec"
                       v-model="selected"
                       class="form-check-input me-2">
                <label :for="'exec-'+exec.value" class="mb-0">{{ exec.label }}</label>
              </li>
            </ul>
          </div>
          <div class="modal-footer p-2 border-0">
            <button class="btn btn-sm btn-outline-secondary rounded-pill" @click="closeModal">Cancel</button>
            <button class="btn btn-sm btn-dark rounded-pill" 
                    :disabled="!selected"
                    @click="selectExec(selected)">
              Select
            </button>
          </div>
        </div>
      </div>
    </div>
  `
};
