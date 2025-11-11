const [{Dealerships}, {Add}, {View}] = await $importComponent([
  '/pages/views/admin/dealerships/index.js',
  '/pages/views/admin/dealerships/add.js',
  '/pages/views/admin/dealerships/view.js'
]);

export default {
  components: { Dealerships, Add, View },

  data() {
    return {
    };
  },

  mounted(){

    // console.log('getname', $routeGetName());
    // console.log('getparam', $routeGetParam());
    // console.log('getquery', $routeGetQuery());
    // console.log('getpath', $routeGetPath());
  },

computed: {
    slug1() {
      return this.$route.params.slug1;
    },
    slug2() {
      return this.$route.params.slug2;
    },

    currentComponent() {
      if (this.slug1 === 'add' || this.slug1 === 'edit') return 'Add';
      if (this.slug1 === 'view') return 'View';
      return 'Dealerships';
    },

    dealerId() {
      return this.slug1 === 'add' ? this.slug2 : null;
    },

    branchId() {
      return (this.slug1 === 'edit' || this.slug1 === 'view') ? this.slug2 : null;
    }
},

template: `
  <div class="home-layout">
    <component 
      :is="currentComponent" 
      :branch_id="branchId" 
      :dealer_id="dealerId"
    />
  </div>
`
};
