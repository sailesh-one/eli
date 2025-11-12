export default {
  name: 'layoutIntro',
  template: /*html*/ `
    <div class="vh-100 d-flex align-items-center justify-content-center bg-dark text-white overflow-hidden position-relative">
      <!-- Background gradient layers using Bootstrap classes -->
      <div class="position-absolute top-0 start-0 w-100 h-100" 
           style="background: radial-gradient(circle at top left, rgba(123, 31, 162, 0.6), transparent); z-index:1;"></div>
      <div class="position-absolute bottom-0 end-0 w-100 h-100" 
           style="background: radial-gradient(circle at bottom right, rgba(30, 136, 229, 0.5), transparent); z-index:1;"></div>

      <!-- Main content card -->
      <div class="text-center p-5 rounded-4 shadow-lg position-relative" style="z-index:2; background: rgba(0,0,0,0.6);">
        <h1 class="display-2 fw-bold mb-3">{{ appName }}</h1>
        <p class="lead mb-4">{{ description }}</p>
      </div>

      <!-- Floating decorative circles -->
      <div class="position-absolute rounded-circle bg-primary opacity-25" style="width:200px; height:200px; top:10%; left:15%;"></div>
      <div class="position-absolute rounded-circle bg-warning opacity-25" style="width:150px; height:150px; bottom:15%; right:10%;"></div>
      <div class="position-absolute rounded-circle bg-success opacity-25" style="width:100px; height:100px; top:30%; right:25%;"></div>
    </div>
  `,
  computed: {
    appName() {
      return 'ELI BUSINESS';
    },
    description() {
      return 'Welcome to ELI BUSINESS! Manage your business efficiently on the go.';
    }
  }
};
