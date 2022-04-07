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
        <div class="form-row">
            <div class="form-group">
              <div class="form-control"><input type="text" name="userLastName" data-id="userLastName" placeholder="Vorname" required></div>
            </div>
            <div class="form-group">
              <div class="form-control"><input type="text" name="userFirstName" data-id="userFirstName" placeholder="Nachname" required></div>
            </div>
        </div>
        <div class="form-group">
          <div class="form-control"><input type="email" name="userEmail" data-id="userEmail" placeholder="E-Mail" required></div>
        </div>
        <div class="form-row">
           <div class="form-group">
              <div class="form-control"><input type="text" name="userCity" data-id="userCity" placeholder="Stadt" required></div>
           </div>
           <div class="form-group">
              <div class="form-control">
                  <select name="userMunicipality" data-id="userMunicipality">
                    <option selected disabled>Kommune</option>
                    <option name="BadenWürttemberg">Baden-Württemberg</option>
                    <option name="Bayern">Bayern</option>
                    <option name="Berlin">Berlin</option>
                    <option name="Brandenburg">Brandenburg</option>
                    <option name="Bremen">Bremen</option>
                    <option name="Hamburg">Hamburg</option>
                    <option name="Hessen">Hessen</option>
                    <option name="MecklenburgVorpommern">Mecklenburg-Vorpommern</option>
                    <option name="Niedersachsen">Niedersachsen</option>
                    <option name="NordrheinWestfalen">Nordrhein-Westfalen</option>
                    <option name="RheinlandPfalz">Rheinland-Pfalz</option>
                    <option name="Saarland">Saarland</option>
                    <option name="SachsenAnhalt">Sachsen-Anhalt</option>
                    <option name="SchleswigHolstein">Schleswig-Holstein</option>
                    <option name="Thüringen">Thüringen</option>
                  </select>
              </div>
            </div>
        </div>
        <div class="form-group">
          <div class="form-control"><input type="text" name="userCompany" data-id="userCompany" placeholder="Unternehmen"></div>
        </div>
        <div class="form-group">
          <div class="form-control">
          <input type="checkbox" name="userIndividual" id="userIndividual" data-id="userIndividual"> 
          <label for="userIndividual">Privatperson</label></div>
        </div>
        <div class="form-group">
          <div class="form-control"><input type="text" name="userLogin" data-id="userLogin" placeholder="Login" required></div>
        </div>
        <div class="form-group">
          <div class="form-control"><input type="password" name="userPassword" data-id="userPassword" placeholder="Passwort" required></div>
        </div>
        <button type="button" id="registerSubmit">Registrieren</button>
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
                  console.log('[Video Registration]');
                  console.error(error);
              });
        }
    }
}

const userCheck = new UserRegister(document.body)
