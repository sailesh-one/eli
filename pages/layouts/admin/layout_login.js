const [{ useUserStore }] = await $importComponent([
  '/pages/stores/userStore.js'
]);

const template = /*html*/ `
  <div class="login-bg login-bg-two">
    <h1 class="welcome-text">Welcome{{ userStore?.user?.name ? ', ' + userStore.user.name : '' }}</h1>
    <hr class="login-divider" />
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
    this.userStore = useUserStore();
  },
};