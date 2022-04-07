class UserRegister {
    constructor (container) {
        this.container = container
        this.init()
    }

    init () {
        this.resolveElements()
        this.checkUser()
    }

    resolveElements () {
        this.user = document.querySelector('html').classList.contains('logged-in')
    }

    checkUser () {
        if (!this.user) {
            this.showRegistrationModal()
            //this.container.classList.add('blocked')
        } else {
            //this.container.classList.remove('blocked')
        }
    }

    showRegistrationModal () {
        this.modal = document.createElement('div')
        this.modal.classList.add('modal')
        this.modal.innerHTML = `
    <div class="modal-inner">
    <div class="modal-tabs-header">
      <a href="#" data-action="login">Anmelden</a>
      <a href="#" data-action="register">Registrien</a>
    </div>
      <div class="modal-tabs-content">
      <div data-tab="login">
      <form id="videoRegistrationForm"></form>
</div>
      <div data-tab="register">
      <form id="videoRegistrationForm">
        <div class="form-group">
          <label for="userLastName">Last name</label>
          <div class="form-control"><input type="text" name="userLastName" data-id="userLastName" required></div>
        </div>
        <div class="form-group">
          <label for="userFirstName">First name</label>
          <div class="form-control"><input type="text" name="userFirstName" data-id="userFirstName" required></div>
        </div>
        <div class="form-group">
          <label for="userCity">City</label>
          <div class="form-control"><input type="text" name="userCity" data-id="userCity" required></div>
        </div>
        <div class="form-group">
          <label for="userMunicipality">Municipality</label>
          <div class="form-control"><input type="text" name="userMunicipality" data-id="userMunicipality" required></div>
        </div>
        <div class="form-group">
          <label for="userCompany">Company including company name / Individual</label>
          <div class="form-control"><input type="text" name="userCompany" data-id="userCompany" required></div>
        </div>
        <button type="button" id="registerSubmit">Register</button>
      </form>
</div>
</div>
    </div>
    `
        document.body.appendChild(this.modal)
        document.getElementById('registerSubmit').addEventListener('click', ev => this.registerUser(ev.target))
    }

    validateForm (target) {
        const form = target.closest('#videoRegistrationForm')
        const inputs = form.querySelectorAll('input')
        let wrong = 0
        for (const input of Array.from(inputs)) {
            input.closest('.form-control').classList.remove('invalid')
            if (input.value.trim() === '') {
                wrong += 1
                input.closest('.form-control').classList.add('invalid')
                input.placeholder = 'Das Feld darf nicht leer sein'
            }
        }

        return wrong === 0
    }

    registerUser (target) {
        console.log('clicked')
        if (this.validateForm(target)) {
            let user = ''
            const form = target.closest('#videoRegistrationForm')
            const inputs = form.querySelectorAll('input')
            Array.from(inputs).forEach(input => {
                user = user + input.name + '=' + input.value + '&'
            })
            const data = new FormData();

            data.append( 'action', 'registerUser' );
            data.append( 'inputs', inputs );

            fetch(videocourseRegistration.ajax_url, {
                method: "POST",
                credentials: 'same-origin',
                body: data
            })
              .then((response) => response.json())
              .then((data) => {
                  console.log(data)
                  if (data) {
                      this.modal.remove()
                      this.container.classList.remove('blocked')
                      _paq.push(['trackEvent', 'VideoCourse', 'Registration', 'User', user])
                  }
              })
              .catch((error) => {
                  console.log('[WP Pageviews Plugin]');
                  console.error(error);
              });
        }
    }
}

const userCheck = new UserRegister(document.body)
