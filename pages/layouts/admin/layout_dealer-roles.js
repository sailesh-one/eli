const [{ Roles }] = await $importComponent([
  '/pages/views/admin/roles/index.js',
]);

export default {
  components: { Roles },
  data() {
    return {
      role_type: 1,
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
      <Roles :role_type="role_type"/>
    </div>
  `,
};
