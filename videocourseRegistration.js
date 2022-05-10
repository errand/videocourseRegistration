class UserRegister {
    constructor (container) {
        this.container = container
        this.init()
    }

    init () {
        this.resolveElements()
        this.addEventListeners()
    }

    resolveElements () {
        this.user = document.querySelector('html').classList.contains('logged-in')
        this.popupButton = document.getElementById('authButton')
        this.alsoPopupButtons = document.querySelectorAll('.videocourse');
        this.logoutButton = document.getElementById('logoutButton')
    }

    addEventListeners () {
        if (!this.user) {
            this.popupButton.addEventListener('click', () => this.showRegistrationModal())
            this.alsoPopupButtons.forEach(button => button.addEventListener('click', () => this.showRegistrationModal()))
        } else {
            if(this.logoutButton) {
                this.logoutButton.addEventListener('click', () => this.logout())
            }
        }
    }

    checkUser () {
        if (!this.user) {
            this.showRegistrationModal()
            this.container.classList.add('blocked')
        } else {
            this.container.classList.remove('blocked')
            this.popupButton.style.display = 'none'
        }
    }

    showRegistrationModal () {
        const modal = document.querySelector('.modal')
        modal.classList.add('show')

        document.getElementById('modalClose').addEventListener('click', () => modal.classList.remove('show'))
        document.getElementById('registerSubmit').addEventListener('click', ev => this.registerUser(ev.target))
        document.getElementById('loginSubmit').addEventListener('click', e => this.login(e))
        document.getElementById('loginRecover').addEventListener('click', e => this.recoverPassword(e.target))
        document.querySelector('[data-action="recover"]').addEventListener('click', () => {
            this.closeAllTabs()
            document.querySelector('[data-tab="recover"]').style.display = 'block';
        })
        document.querySelector('[data-action="login"]').addEventListener('click', () => {
            this.closeAllTabs()
            document.querySelector('[data-tab="login"]').style.display = 'block';
            document.querySelector('[data-action="login"]').classList.add('active')
        })
        document.querySelector('[data-action="register"]').addEventListener('click', () => {
            this.closeAllTabs()
            document.querySelector('[data-tab="register"]').style.display = 'block';
            document.querySelector('[data-action="register"]').classList.add('active')
        })
    }

    closeAllTabs() {
        [...document.querySelectorAll('.tab')].forEach(
          tab => {
            tab.style.display = 'none'
            tab.classList.remove('active')
          });

        [...document.querySelectorAll('.tab-link')].forEach(
          link => {
              link.classList.remove('active')
          });
    }

    validateForm (target) {
        const form = target.closest('.form')
        const log = form.querySelector('.log')
        const inputs = form.querySelectorAll('.form-input')
        let wrong = 0
        for (const input of Array.from(inputs)) {
            input.closest('.form-control').classList.remove('invalid')
            log.style.display = 'none'
             if (input.value.trim() === '') {
                wrong += 1
                input.closest('.form-control').classList.add('invalid')
                log.style.display = 'block'
            }
        }

        const emailField = form.querySelector('[data-id="userEmail"]');

        if(emailField) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            if (!re.test(emailField.value)) {
                emailField.closest('.form-control').classList.add('invalid')
                log.style.display = 'block'
                wrong += 1
            }
        }

        if (form.querySelector('[data-id="userPasswordConfirm"]')) {

            const userPasswordConfirm = form.querySelector('[data-id="userPasswordConfirm"]');
            const userPassword = form.querySelector('[data-id="userPassword"]');

            if(userPassword.value !== userPasswordConfirm.value) {
                log.style.display = 'block'
                log.innerText = 'Passwörter müssen übereinstimmen'
                wrong += 1
            }
        }

        return wrong === 0
    }

    recoverPassword(target) {
        if (this.validateForm(target)) {
            const form = target.closest('#videoRecoverForm')
            const userEmail = form.querySelector('[data-id="userEmail"]').value
            const security = document.getElementById('forgotsecurity').value
            const log = form.querySelector('.log')

            target.classList.add('processing')

            const data = new FormData();

            data.append( 'action', 'recoverPassword' );
            data.append( 'email', userEmail );
            data.append( 'security', security );

            fetch(videocourseRegistration.ajax_url, {
                method: "POST",
                credentials: 'same-origin',
                body: data
            })
              .then(response => response.json())
              .then(data => {
                  if (data) {
                      log.style.display = 'block'
                      log.innerText = data.message
                      target.classList.remove('processing')
                  }
              })
              .catch((error) => {
                  console.log(error)
                  console.log('[Recover Password]');
                  console.error(error);
                  log.style.display = 'block'
                  log.innerText = error
                  target.classList.remove('processing')
              });
        }
    }

    registerUser (target) {
        if (this.validateForm(target)) {
            let dataObjects = {};
            let userKommune;
            let userAnrede;
            const modal = target.closest('.modal')
            const form = target.closest('#videoRegistrationForm')
            const inputs = form.querySelectorAll('input')
            const userEmail = form.querySelector('[data-id="userEmail"]').value

            target.classList.add('processing')

            Array.from(inputs).forEach(input => {
                if (input.type != 'radio' || input.type === 'radio' && input.checked) {
                    Object.assign(dataObjects,{
                        [input.name]:input.value
                    })
                }
            })
            //just for select user Kommune ))
            userKommune = document.getElementById('userStadtKommune');
            Object.assign(dataObjects,{
                'userStadtKommune': userKommune.value
            });

            userAnrede = document.getElementById('userAnrede');
            Object.assign(dataObjects,{
                'userAnrede': userAnrede.value
            });

            dataObjects = JSON.stringify(dataObjects);
            //console.log(dataObjects);
            const data = new FormData();

            data.append( 'action', 'registerUser' );
            data.append( 'inputs', dataObjects );

            fetch(videocourseRegistration.ajax_url, {
                method: "POST",
                credentials: 'same-origin',
                body: data
            })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        target.classList.remove('processing')
                        this.container.classList.remove('blocked')
                        _paq.push(['trackEvent', 'VideoCourse', 'Registration', 'User', userEmail])
                        document.location.reload(true);
                    }
                })
                .catch((error) => {
                    console.log('[Video Registration]');
                    console.error(error);
                });
        }
    }

    logout() {
        const data = new FormData();
        data.append( 'action', 'logoutUser' );
        fetch(videocourseRegistration.ajax_url, {
            method: "POST",
            credentials: 'same-origin',
            body: data
        })
            .then(response => response.json())
            .then(data => {
                if (data) {
                    window.location.reload()
                }
            })
            .catch((error) => {
                console.log('[Video Registration]');
                console.error(error);
            });
    }

    login(e) {
        if (!this.validateForm(e.target)) {
            return
        }
        const form = e.target.closest('.form');
        const login = form.querySelector('[data-id="userLogin"]').value
        const password = form.querySelector('[data-id="userPassword"]').value

        e.target.classList.add('processing')

        const data = new FormData();
        data.append( 'action', 'loginUser' );
        data.append( 'login', login );
        data.append( 'password', password );

        fetch(videocourseRegistration.ajax_url, {
            method: "POST",
            credentials: 'same-origin',
            body: data
        })
            .then(response => response.json())
            .then(data => {
                if (data.loggedin) {
                    _paq.push(['trackEvent', 'VideoCourse', 'Login', 'User', login])
                    e.target.classList.remove('processing')
                    window.location.reload()
                } else {
                    form.querySelector('.log').style.display = 'block'
                    form.querySelector('.log').innerText = 'Falscher Benutzername oder falsches Passwort'
                }

            })
            .catch((error) => {
                console.log('[Video Registration Login]');
                console.error(error);
            });
    }

}

const userCheck = new UserRegister(document.body)
