export const SelectSearch = {
  name: "SelectSearch",
  props: {
    id: { type: String, default: () => "selectsearch-" + Math.random().toString(36).substr(2, 9) },
    options: { type: Array, required: true },
    modelValue: { type: [String, Number, Object, Array], default: null },
    placeholder: { type: String, default: "Select an option" },
    searchable: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    returnObject: { type: Boolean, default: false },
    optionLabel: { type: String, default: "label" },
    optionValue: { type: String, default: "value" },
    optionDisabled: { type: String, default: "disabled" },
    disabledOptions: { type: Array, default: () => [] },
    visibleGroups: { type: Array, default: () => [] },
    zIndex: { type: Number, default: 99999 },
    showGrouping: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
  },
  
  emits: ["update:modelValue", "change"],
  data: () => ({ searchTerm: "", isOpen: false, hoverIndex: -1, dropDirection: "bottom", dropdownPosition: { left: 0, top: 0, width: 0 } }),
  computed: {
    internalValue() {
      if (!this.multiple) return this.modelValue;
      
      if (typeof this.modelValue === 'string' && this.modelValue) {
        return this.modelValue.split('|').map(v => v.trim());
      }
      return Array.isArray(this.modelValue) ? this.modelValue : [];
    },
    
    normalizedOptions() {
      const e = this.options;
      let t = null;

      if (!this.multiple) {
        const hasEmpty = e.some((opt) => String(opt[this.optionValue]) === "");
        if (!hasEmpty) {
          t = { [this.optionLabel]: `Select ${this.placeholder}`, [this.optionValue]: "" };
        }
      }

      if (!this.showGrouping)
        return [{ group: null, items: t ? [t, ...e] : e }];

      const grouped = {};
      e.forEach((opt) => {
        const group = opt.group || null;
        if (!grouped[group]) grouped[group] = [];
        grouped[group].push(opt);
      });

      const visible = this.visibleGroups || [];
      let finalGroups = Object.keys(grouped)
        .filter((grp) => visible.length === 0 || visible.includes(grp))
        .map((grp) => ({ group: grp, items: grouped[grp] }));

      if (t) finalGroups.unshift({ group: null, items: [t] });

      return finalGroups;
    },
    allItems() {
      return this.normalizedOptions.flatMap((e) => e.items);
    },
    filteredOptions() {
      if (!this.searchable || !this.searchTerm) return this.normalizedOptions;
      const e = this.searchTerm.toLowerCase();
      return this.normalizedOptions
        .map((t) => ({
          ...t,
          items: t.items.filter((t) => String(t[this.optionLabel]).toLowerCase().includes(e)),
        }))
        .filter((e) => e.items.length > 0);
    },
    flatItems() {
      return this.filteredOptions.flatMap((e) => e.items);
    },
    selectedItems() {
      if (this.multiple) {
        const values = this.internalValue;
        return this.returnObject
          ? this.allItems.filter((e) =>
              values.some((t) => t[this.optionValue] == e[this.optionValue])
            )
          : this.allItems.filter(
              (e) =>
                values.includes(e[this.optionValue]) ||
                values.includes(String(e[this.optionValue]))
            );
      } else if (this.modelValue) {
        return this.returnObject
          ? [this.modelValue]
          : this.allItems.filter(
              (e) => String(e[this.optionValue]) == String(this.modelValue)
            );
      } else {
        return [
          {
            [this.optionLabel]: `Select ${this.placeholder}`,
            [this.optionValue]: '',
          },
        ];
      }
    },
    isInvalid() {
      return this.required && !this.selectedItems.length;
    },
  },
  methods: {
    handleScroll() {
      if (this.isOpen) {
        this.closeDropdown();
      }
    },
    isOptionDisabled(e) {
      return e[this.optionDisabled] || this.disabledOptions.includes(String(e[this.optionValue]));
    },
   updateDropdownPosition() {
      if (!this.isOpen || !this.$refs.wrapper) return;
      
      const rect = this.$refs.wrapper.getBoundingClientRect();
      const dropdownHeight = 250;
      const spaceBelow = window.innerHeight - rect.bottom;
      const spaceAbove = rect.top;

      this.dropDirection = spaceBelow < dropdownHeight && spaceAbove > spaceBelow ? "top" : "bottom";
      
      // Calculate position for fixed dropdown
      this.dropdownPosition = {
        left: rect.left,
        top: this.dropDirection === 'bottom' 
          ? rect.bottom + 4 
          : rect.top - dropdownHeight - 4,
        width: rect.width
      };
    },
    toggleDropdown() {
        if (this.disabled) return;

        document.querySelectorAll(".selectsearch-wrapper").forEach((e) => {
            e !== this.$refs.wrapper && e.__vueSelectSearch?.closeDropdown();
        });

        this.isOpen = !this.isOpen;

        if (this.isOpen) {
            this.$nextTick(() => {
              this.updateDropdownPosition();
            });

            this.$refs.wrapper.classList.add("selectsearch-open");
            this.searchable && this.$nextTick(() => this.$refs.searchInput?.focus());
        } else {
            this.closeDropdown();
        }
    },

    closeDropdown() {
      this.isOpen = false;
      this.hoverIndex = -1;
      this.$refs.wrapper?.classList.remove("selectsearch-open");
    },
    selectItem(e) {
      if (this.isOptionDisabled(e)) return;
      if ("" === String(e[this.optionValue])) {
        const val = this.multiple ? "" : null;
        this.$emit("update:modelValue", val);
        this.$emit("change", this.multiple ? "" : "");
        this.dispatchNativeChange();
        this.closeDropdown();
        return;
      }
      
      if (this.multiple) {
        let values = this.internalValue;
        const itemValue = this.returnObject ? e : String(e[this.optionValue]);
        const searchValue = this.returnObject ? e[this.optionValue] : itemValue;
        const index = values.findIndex(v => String(v) === String(searchValue));
        
        if (index >= 0) {
          values.splice(index, 1);
        } else {
          values.push(itemValue);
        }
        
        const pipeValue = values.join('|');
        this.$emit("update:modelValue", pipeValue);
        this.$emit("change", pipeValue);
      } else {
        const val = this.returnObject ? e : String(e[this.optionValue]);
        this.$emit("update:modelValue", val);
        this.$emit("change", val);
        this.closeDropdown();
      }
      this.dispatchNativeChange();
      this.searchTerm = "";
      this.$nextTick(() => this.scrollToHover());
    },
    clearSelection(e) {
      e.stopPropagation();
      const val = this.multiple ? "" : null;
      this.$emit("update:modelValue", val);
      this.$emit("change", val);
      this.dispatchNativeChange();
    },
    removeTag(e, t) {
      if ((t.stopPropagation(), !this.multiple)) return;
      let values = this.internalValue;
      const searchValue = this.returnObject ? e[this.optionValue] : e[this.optionValue];
      const index = values.findIndex(v => String(v) === String(searchValue));
      
      if (index >= 0) {
        values.splice(index, 1);
      }
      
      const pipeValue = values.join('|');
      this.$emit("update:modelValue", pipeValue);
      this.$emit("change", pipeValue);
      this.dispatchNativeChange();
    },
    dispatchNativeChange() {
      this.$nextTick(() => {
        const hiddenSelect = this.$el.querySelector("select");
        if (hiddenSelect) {
          while (hiddenSelect.options.length) hiddenSelect.remove(0);
          if (this.multiple) {
            this.selectedItems.forEach(item => {
              const opt = new Option(item[this.optionLabel], item[this.optionValue], true, true);
              hiddenSelect.add(opt);
            });
          } else {
            const val = this.selectedItems[0]?.[this.optionValue] ?? "";
            hiddenSelect.add(new Option(val, val, true, true));
          }
        }
        this.$el.dispatchEvent(new Event("change", { bubbles: true }));
      });
    },
    scrollToHover() {
      this.$nextTick(() => {
        const e = this.$refs.wrapper.querySelector(".selectsearch-dropdown");
        if (!e) return;
        const t = e.querySelector(".hovered");
        if (t) {
          const i = t.offsetTop, s = i + t.offsetHeight;
          if (i < e.scrollTop) e.scrollTop = i;
          else if (s > e.scrollTop + e.clientHeight) e.scrollTop = s - e.clientHeight;
        }
      });
    },
    handleWrapperKeydown(e) {
      if (this.disabled) return;
      switch (e.key) {
        case "Enter":
        case " ":
          e.preventDefault();
          this.toggleDropdown();
          break;
        case "ArrowDown":
          e.preventDefault();
          this.isOpen ? this.nextHover() : this.toggleDropdown();
          break;
        case "ArrowUp":
          e.preventDefault();
          this.isOpen ? this.prevHover() : this.toggleDropdown();
          break;
        case "Escape":
          e.preventDefault();
          this.closeDropdown();
          break;
        case "Tab":
          if (!this.isOpen) return;
          e.preventDefault();
          e.shiftKey ? this.prevHover() : this.nextHover();
          if (
            (!e.shiftKey && this.hoverIndex === this.flatItems.length - 1) ||
            (e.shiftKey && 0 === this.hoverIndex)
          ) this.closeDropdown();
      }
    },
    handleSearchKeydown(e) {
      switch (e.key) {
        case "ArrowDown":
        case "ArrowUp":
          e.preventDefault();
          e.stopPropagation();
          "ArrowDown" === e.key ? this.nextHover() : this.prevHover();
          break;
        case "Enter":
          e.preventDefault();
          this.flatItems[this.hoverIndex] && this.selectItem(this.flatItems[this.hoverIndex]);
          break;
        case "Escape":
          e.preventDefault();
          this.closeDropdown();
      }
    },
    nextHover(e = false) {
      if (!this.flatItems.length) return;
      let t = this.hoverIndex;
      const i = e ? -1 : 1;
      for (let e = 0; e < this.flatItems.length &&
        ((t = (t + i + this.flatItems.length) % this.flatItems.length),
          this.isOptionDisabled(this.flatItems[t])); e++);
      this.hoverIndex = t;
      this.scrollToHover();
    },
    prevHover() {
      this.nextHover(true);
    },
    handleClickOutside(e) {
      this.$refs.wrapper?.contains(e.target) || this.$refs.tagsContainer?.contains(e.target) || this.closeDropdown();
    },
    isSelected(e) {
      if (this.multiple) {
        const values = this.internalValue;
        return this.returnObject
          ? values.some(v => v[this.optionValue] == e[this.optionValue])
          : values.some(v => String(v) === String(e[this.optionValue]));
      }
      return this.returnObject
        ? this.modelValue === e
        : String(this.modelValue) === String(e[this.optionValue]);
    },
    randomLightBg: (e) => `hsl(${(137.5 * e) % 360}, 5%, ${96 + (e % 4)}%)`,
     handleScroll(e) {
    if (this.isOpen) {
      // Check if scroll is happening inside the dropdown
      const dropdown = this.$refs.wrapper?.querySelector('.selectsearch-dropdown');
      if (dropdown && dropdown.contains(e.target)) {
        // Allow scrolling inside dropdown
        return;
      }
      // Close dropdown for any external scroll
      this.closeDropdown();
    }
  },
  },
  watch: {
    modelValue: {
      handler() {
        this.hoverIndex = -1;
      },
      immediate: true,
      deep: true,
    },
    options: {
      handler(e) {
        const t = this.allItems.map((e) => e[this.optionValue]);
        if (this.multiple) {
          const values = this.internalValue;
          const validValues = values.filter((v) => t.includes(v) || t.includes(String(v)));
          if (validValues.length !== values.length) {
            const pipeValue = validValues.join('|');
            this.$emit("update:modelValue", pipeValue);
            this.dispatchNativeChange();
          }
        } else if (!this.multiple && this.modelValue != null && !t.includes(this.modelValue)) {
          if (this.returnObject) {
            const e = this.allItems.find((e) => e[this.optionValue] === this.modelValue[this.optionValue]);
            this.$emit("update:modelValue", e || null);
          } else this.$emit("update:modelValue", null);
          this.dispatchNativeChange();
        }
      },
      deep: true,
    },
  },
  mounted() {
    this.$refs.wrapper.__vueSelectSearch = this;
    document.addEventListener("click", this.handleClickOutside);
    document.addEventListener("scroll", this.handleScroll, true);
    this.$refs.wrapper.addEventListener("keydown", this.handleWrapperKeydown);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleClickOutside);
    document.removeEventListener("scroll", this.handleScroll, true);
    this.$refs.wrapper.removeEventListener("keydown", this.handleWrapperKeydown);
  },
  template: `
  <div>
    <div class="position-relative selectsearch-wrapper" ref="wrapper"
      :id="id"
      :class="{ 'opacity-50': disabled, 'is-invalid': isInvalid }"
      tabindex="0"
      role="combobox"
      :aria-expanded="isOpen"
      :aria-disabled="disabled"
      aria-haspopup="listbox">

      <select
        v-if="!multiple"
        :name="id"
        :required="required"
        tabindex="-1"
        aria-hidden="true"
        style="position:absolute;opacity:0;height:0;width:0;pointer-events:none;">
        <option :value="selectedItems[0]?.[optionValue] || ''" selected></option>
      </select>

      <select
        v-else
        multiple
        :name="id + '[]'"
        :required="required"
        tabindex="-1"
        aria-hidden="true"
        style="position:absolute;opacity:0;height:0;width:0;pointer-events:none;">
        <option
          v-for="(sel, i) in selectedItems"
          :key="i"
          :value="sel[optionValue]"
          selected
        ></option>
      </select>

      <div class="form-control d-flex align-items-center justify-content-between cursor-pointer text-dark"
        @click="toggleDropdown">
        
        <span v-if="multiple && selectedItems.length" class="text-truncate">
          {{ selectedItems.length }} item{{ selectedItems.length > 1 ? 's' : '' }} selected
        </span>

        <span v-else-if="!multiple && selectedItems.length" class="text-truncate">
          {{ selectedItems[0][optionLabel] }}
        </span>

        <span v-else class="text-muted">{{ placeholder }}</span>

        <div class="d-flex align-items-center ms-auto">
          <i v-if="clearable && selectedItems.length"
            class="bi bi-x-circle-fill text-muted me-2 cursor-pointer"
            @click="clearSelection"></i>
          <i class="bi bi-chevron-down"></i>
        </div>
      </div>

      <div v-if="isOpen"
          class="selectsearch-dropdown position-fixed bg-white border rounded shadow-sm"
          :class="{
              'mt-1': dropDirection === 'bottom',
              'mb-1': dropDirection === 'top'
          }"
          :style="{
              zIndex: zIndex,
              maxHeight: '250px',
              overflowY: 'auto',
              left: dropdownPosition.left + 'px',
              top: dropdownPosition.top + 'px',
              width: dropdownPosition.width + 'px'
          }"
          role="listbox">

        <div v-if="searchable" class="p-2 border-bottom border-secondary">
          <input type="text"
            ref="searchInput"
            v-model="searchTerm"
            class="form-control form-control-sm py-0"
            placeholder="Search..."
            @keydown="handleSearchKeydown">
        </div>

        <template v-for="(group, gIndex) in filteredOptions" :key="gIndex">
          <!-- Group Header -->
          <div v-if="showGrouping && group.group" 
               class="px-3 py-1 bg-light text-muted small fw-bold border-bottom">
            {{ group.group }}
          </div>
          
          <!-- Group Items -->
          <div v-for="(item, iIndex) in group.items" :key="iIndex"
            class="px-3 py-2 d-flex align-items-center justify-content-between cursor-pointer"
            :class="{
              'bg-lightest': hoverIndex === flatItems.indexOf(item) && !isSelected(item),
              'bg-secondary text-white': isSelected(item),
              'text-muted': isOptionDisabled(item)
            }"
            @mouseover="hoverIndex = flatItems.indexOf(item)"
            @mouseleave="hoverIndex = -1"
            @click="selectItem(item)">
            <span style="cursor: pointer;">{{ item[optionLabel] }}</span>
            <input v-if="multiple" type="checkbox" class="form-check-input"
              :checked="isSelected(item)"
              :disabled="isOptionDisabled(item)" readonly>
          </div>
        </template>

        <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-muted small">No results found</div>
      </div>
    </div>

    <!-- Selected Tags Container (Outside) -->
    <div
      v-if="multiple && selectedItems.length"
      ref="tagsContainer"
      class="d-flex flex-wrap gap-1 mt-1"
    >
      <span
        v-for="(sel, i) in selectedItems"
        :key="i"
        class="badge rounded-pill bg-light border text-dark d-flex align-items-center px-1 py-0 fw-normal small shadow-sm"
      >
        {{ sel[optionLabel] }}
        <i
          class="bi bi-x ms-1 cursor-pointer text-muted fs-6"
          @click="removeTag(sel, $event)"
        ></i>
      </span>
    </div>

  </div>
  `,
};