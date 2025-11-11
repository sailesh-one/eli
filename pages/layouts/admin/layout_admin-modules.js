const [{ Modules }] = await $importComponent([
  '/pages/views/admin/modules/index.js'
]);


export default {
  components: { Modules },
  data() {
    return {
      is_dealer: 0,
    };
  },
  methods: {
    goTo(page) {
      $routeTo(`/${page}`);
    }
  },
  template: `
    <div class="home-layout">
      <Modules :is_dealer="is_dealer" />
    </div>
  `,
};
