const template = /*html*/ `
<div>
  <div v-if="!checkNet.is" class="connection-banner" :style="{ backgroundColor: checkNet.color }"><p>{{ checkNet.msg }}</p></div>
  <div id="login_app" class="login_app modal-auth">
    <div v-if="show_modal" class="modal-overlay"></div>
    <div v-if="show_modal" class="modal fade show custom-modal">
      <div class="modal-dialog modal-dialog-centered w-380">
        <div class="modal-content custom-modal-content rounded-2">
          <div :class="second_step ? 'loginbg_step2' : 'loginbg'" class="p-4 bg-white position-relative">
            <div class="text-center mb-3">
            <div class="modal-logo mb-2 h1 font-weight-bold">
              ELI
            </div>
              <h5 class="text-dark">LOGIN</h5>
            </div>
            <div class="login-panel" v-if="show_login">
              <div :class="second_step ? 'login_frm_step2' : 'login_frm p-0 mt-4'">
                <div v-if="err" class="alert alert-danger small py-2" v-html="err"></div>
                <template v-if="first_step">
                  <div class="mb-3">
                    <input ref="email" class="form-control rounded-1 py-3" placeholder="Email Address" maxlength="100" @keypress="$isValidEmail" v-model.trim="user.email" autocomplete="off" />
                  </div>
                  <div class="d-flex justify-content-between">
                  <div class="captcha-box mb-3">
                    <img :src="captcha_img" class="captcha-img" />
                    <img src="/assets/images/refresh.png" class="captcha-refresh" @click="loadCaptcha" />
                  </div>
                  <div class="mb-3 ms-1">
                    <input ref="captcha" class="form-control rounded-1 py-3" placeholder="Captcha" maxlength="10" @keypress="$alphaNumOnly" @keyup.enter="sendOTP" v-model.trim="user.captcha" autocomplete="off" />
                  </div>
                </div>
                </template>
                <template v-if="second_step">
                  <div class="mb-3">
                    <input ref="otp" class="form-control rounded-1 py-3" @keyup.enter="verifyOTP" maxlength="6" @keypress="$numOnly" placeholder="Enter OTP" v-model.trim="user.otp" autocomplete="off" />
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <span class="small text-muted">{{ timer_msg }}</span>
                      <template v-if="!show_timer">
                        <a v-if="resend_cnt < resend_limit" @click="resendOTP" class="text-danger small text-decoration-none pointer">Resend OTP</a>
                        <span v-else class="small text-muted">Max limit reached.</span>
                      </template>
                    </div>
                  </div>
                </template>
                <div class="text-center mt-4">
                  <button v-if="first_step" @click="sendOTP" class="btn btn-dark w-100 py-2 btn-prime rounded-1" :disabled="loading">
                    {{ loading ? 'Processing...' : 'LOGIN' }}
                  </button>
                  <div v-if="second_step" class="d-flex justify-content-between mt-3 gap-2">
                    <button @click="reset" class="btn btn-outline-secondary w-50 btn-prime">Back</button>
                    <button @click="verifyOTP" class="btn btn-dark w-50 btn-prime" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <span v-else>Submit</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
`;

export const AuthComponent = {
  name: 'AuthComponent',
  template,
  data() {
    return {
      checkNet: { is:true, msg:'', color:'' } ,
      auth_user: null,
      userStore: null,
      show_modal: false,
      show_login: false,
      first_step: true,
      second_step: false,
      loading: false,
      show_timer: false,
      timer_msg: '',
      resend_cnt: 0,
      resend_limit: 5,
      timer: null,
      err: null,
      captcha_img: '',
      captcha_loading: false,
      boundCheckToken: null,
      expiry: 86400,
      user: {
        email: '',
        captcha: '',
        captcha_token: '',
        otp: '',
        otp_token: ''
      }
    };
  },
  async created() {
    this.checkNetworkStatus();
    const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);
    this.userStore = storeModule.useUserStore(); 
    this.boundCheckToken = this.checkToken.bind(this);
    window.addEventListener('storage', this.boundCheckToken);
    this.checkToken();
  },
  beforeDestroy() {
    window.removeEventListener('storage', this.boundCheckToken);
    this.clearTimer();
  },
  methods: {
  
    checkNetworkStatus() {
      const handleNetStatus = (msg, color, autoHide) => {
        this.checkNet.msg = msg;
        this.checkNet.color = color;
        this.checkNet.is = false;
        if (autoHide) {
          setTimeout(() => (this.checkNet.is = true), 2000);
        }
      };
      const checkSlowConnection = () => {
          const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
          if (connection) {
            if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g' || connection.effectiveType === '3g') {
              handleNetStatus('⚠️ Slow network connection detected', '#1a1508ff', true);
            }
          }
        };
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (connection) {
          connection.addEventListener('change', checkSlowConnection);
        }
        window.addEventListener('online', () => {
          handleNetStatus('✅ Back online', '#198754', true);
          checkSlowConnection();
        });
        window.addEventListener('offline', () => handleNetStatus('🔌 You’re offline', '#dc3545'));
        if (!navigator.onLine) {
          handleNetStatus('🔌 You’re offline', '#dc3545');
        } else {
          checkSlowConnection();
        }
    },

    async checkToken() {
      let isLoggedIn = await $isLoggedIn();
      if (!isLoggedIn) {
        if (!this.show_modal) this.open();
      } else {
        if (this.show_modal) this.close();
      }
    },
    
    open() {
      Object.assign(this.user, {
        email: '',
        captcha: '',
        captcha_token: '',
        otp: '',
        otp_token: ''
      });
      this.show_modal = this.show_login = true;
      this.first_step = true;
      this.second_step = false;
      this.err = null;
      this.loadCaptcha();
    },

    close() {
      this.show_modal = false;
      this.clearTimer();
    },

    reset() {
      this.first_step = true;
      this.second_step = false;
      this.err = null;
      this.user.otp = '';
      this.clearTimer();
    },

    resetErr() {
      this.err = null;
    },

    validate(fields) {
      for (const [key, msg] of Object.entries(fields)) {
        const value = this.user[key]?.trim();
        if (!value || (key === 'email' && !$isValidEmail(value)) || (key === 'otp' && !/^\d{6}$/.test(value))) {
          this.err = $sanitizeHTML(msg);
          this.$refs[key]?.focus();
          return false;
        }
      }
      return true;
    },

    async request(action, data = {}) {
      try {
        const res = await $http('POST',`${g.$base_url_api}/auth`, { action, ...data }, {}, { auth: false });
        this.checkReDirect(res);
        return res;
      } catch (e) {
        $log(e);
        this.checkReDirect(e);
        return { status: e.status, body: e.body };
      }
    },


    async loadCaptcha() {
      if (this.captcha_loading) return;
      this.captcha_loading = true;
      try {
        const res = await this.request('captcha');
        if (res?.status === 200) {
          const { captcha_image, captcha_token } = res.body.data;
          this.captcha_img = captcha_image;
          this.user.captcha_token = captcha_token;
        } else {
          this.err = (res?.body?.message || 'Failed to load captcha.');
        }
      } catch (e) {
        this.err = 'Unexpected error loading captcha.';
      } finally {
        this.captcha_loading = false;
      }
    },

    async sendOTP() {
      if (!this.validate({ email: 'Enter valid email address.', captcha: 'Enter captcha.' })) return;
      this.loading = true;
      this.resetErr();
      this.auth_user=$routeAuthRole();
      const res = await this.request('login', {
        auth_user: this.auth_user,
        email: this.user.email.trim(),
        captcha_code: this.user.captcha.trim(),
        captcha_token: this.user.captcha_token
      });
      this.loading = false;
      $log(res);
      if (res?.status === 200) {
        this.user.otp_token = res.body.data.otp_token;
        this.first_step = false;
        this.second_step = true;
        this.resend_cnt = 0;
        this.startTimer(res?.body?.data?.otp_timer || 30);
        $toast('success', res.body.message || 'OTP sent.');
      } else {
        this.err = $sanitizeHTML(Object.values(res?.body?.errors || {}).join('<br>'));
        $toast('danger', res.body.msg || 'Failed to send OTP.');
      }
      this.loadCaptcha();
    },

    async resendOTP() {
      if (this.resend_cnt >= this.resend_limit) return;
      this.loading = true;
      this.resetErr();
      const res = await this.request('otp_resend', {
        email: this.user.email.trim(),
        otp_token: this.user.otp_token
      });
      this.loading = false;
      if (res?.status === 200) {
        this.user.otp_token = res.body.data.otp_token;
        $toast('success', res.body.message || 'OTP resent.');
        this.resend_cnt++;
        this.startTimer(res?.body?.data?.otp_timer || 30);
      } else {
        $toast('danger', res.body.message || 'Resend failed.');
      }
    },

    
    async verifyOTP() {
      if (!this.validate({ otp: 'Enter valid 6-digit OTP.' })) return;

      this.loading = true;
      this.resetErr();
      const email = this.user.email?.trim();
      const otp = this.user.otp?.trim();
      const otp_token = this.user.otp_token;

      try {
        const res = await this.request('otpverify', { email, otp, otp_token });
        if (res?.status === 200 && res.body?.data) {
          const { auth_user, access_token, refresh_token } = res.body.data;
            await $secureStorage('set', 'auth', { access_token, refresh_token });
            await $secureStorage('set', 'logged', auth_user);
            await this.userStore.getUser();
          $toast('success', res.body.message || 'Logged in successfully.');
          this.close();
          let unAuth = await $routeGetMeta('requiresAuth');
          if(!unAuth){
            let defRoute = await this.userStore.getDefaultRoute();
            $routeTo(defRoute, 'refresh', 2);
          }
        } else {
          $toast('danger', res.body?.message || 'OTP verification failed.');
        }

      } catch (err) {
        $log('[OTP VERIFY ERROR]', err);
        $toast('danger', 'Something went wrong. Please try again.');
      } finally {
        this.loading = false;
      }
    },

    checkReDirect(res) {
      if (res.redirect) {
        $routeTo(res.redirect, 'refresh', 2);
      }
    },

    startTimer(duration = 60) {
      this.clearTimer();
      let time = duration;
      this.show_timer = true;
      this.timer_msg = `Resend OTP in ${time}s`;
      this.timer = setInterval(() => {
        time -= 1;
        this.timer_msg = `Resend OTP in ${time}s`;
        if (time <= 0) this.clearTimer();
      }, 1000);
    },

    clearTimer() {
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
      this.show_timer = false;
    }
  }
};