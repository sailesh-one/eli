const [{ MMVS }] = await $importComponent([
  '/pages/views/admin/mmv/index.js',
]);
export default {
  components: { MMVS },
  data() {
    return {
      role_type: 0,
    };
  },
  async created() {
  },
  methods: {
    goTo(page) {
      $routeTo(`/${page}`);
    },
  },
  template: `
    <div class="home-layout">
      <MMVS/>
    </div>
  `,
};