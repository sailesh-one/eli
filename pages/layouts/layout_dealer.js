const [Header, Topbar, Footer] = await $importComponent([
  '/views/components/common/common_header.js',
  '/views/components/common/common_topbar.js',
  '/views/components/common/common_footer.js',
]);

export default {
  components: {
    Header,
    Topbar,
    Footer
  },
  data() {
    return {
      userName: 'Example',
    };
  },
  template: `
    <div class="d-flex flex-column vh-100">
      <!-- Header -->
      <Header />

      <div class="d-flex flex-grow-1 overflow-hidden">
        <!-- Sidebar -->
        <Topbar class="bg-dark text-white" />

        <!-- Main Content -->
        <main class="flex-grow-1 bg-light p-4 overflow-auto">
          <router-view></router-view>
        </main>
      </div>

      <!-- Footer -->
      <Footer /> 
    </div>
  `
};


