const template = /*html*/ `
<div class="login-bg">
      <h1>Welcome, {{ userStore?.user?.name || '' }}</h1>
    </div>
`;


export default {
  name: 'loginPage',
  template,
  data() {
    return {
      userStore: null,
    }
  },
  async created() {
    const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);
    const store = storeModule.useUserStore();
    this.userStore = Vue.reactive(store); // Make it reactive for the template
  },
};

