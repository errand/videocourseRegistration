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
        this.user = document.querySelector('html').classList.contains('logged-in');
        this.popupButton = document.getElementById('authButton');
        this.alsoPopupButtons = document.querySelectorAll('.videocourse');
        this.logoutButton = document.getElementById('logoutButton');
        this.deleteUserButton = document.getElementById('deleteAccount');
        this.deleteUserButtonModal = document.getElementById('deleteAccountModal');
    }

    addEventListeners () {
        if (!this.user && this.popupButton) {
            this.popupButton.addEventListener('click', () => this.showRegistrationModal())
            this.alsoPopupButtons.forEach(button => button.addEventListener('click', () => this.showRegistrationModal()))
        } else {
            if(this.logoutButton) {
                this.logoutButton.addEventListener('click', () => this.logout())
            }
        }
        if(this.deleteUserButton) {
            this.deleteUserButtonConfirm = document.getElementById('userDeleteConfirm');
            this.deleteUserButton.addEventListener('click', ()=> {
                this.deleteUserButtonModal.style.display = 'block';
                this.deleteUserButtonConfirm.addEventListener('click', () => this.deleteUser(this.deleteUserButton.dataset.user));
                document.getElementById('userDeleteCancel').addEventListener('click', () => this.deleteUserButtonModal.style.display = 'none');
            })
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

        const passwordField = form.querySelector('[data-id="userPassword"]')

        if(passwordField && form.id === 'videoRegistrationForm') {
            const pwdRule = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{10,})/;
            if (!pwdRule.test(passwordField.value)) {
                passwordField.closest('.form-control').classList.add('invalid')
                log.style.display = 'block'
                log.innerText = document.querySelector('#passwordHelp .text').innerText;
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

            fetch('/wp/wp-login.php?action=lostpassword', {
                method: "POST",
                credentials: 'same-origin',
                body: data
            })
              .then(response => {
                  console.log(response)
                  return response.json();
              })
              .then(data => {
                  console.log(data)
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
            const form = target.closest('.form')
            const log = form.querySelector('.log')
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

            dataObjects = JSON.stringify(dataObjects);
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
                        log.style.display = 'block';
                        if (data.success) {
                            _paq.push(['trackEvent', 'VideoCourse', 'Registration', 'User', userEmail])
                            log.innerText = 'Registrierung erfolgreich abgeschlossen. Bitte überprüfen Sie die angegebene E-Mail'
                            log.classList.add('success')
                        } else {
                            //log.innerText = data.data
                            log.innerText = 'Möglicherweise wird eine solche E-Mail bereits verwendet. Versuchen Sie, Ihr Passwort wiederherzustellen.'
                        }
                        //document.location.reload(true);
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

    deleteUser(id) {
        const data = new FormData();
        data.append( 'action', 'deleteUser' );
        data.append( 'userId', id );

        this.deleteUserButtonConfirm.style.color = '#fff'
        this.deleteUserButtonConfirm.classList.add('processing')

        fetch(videocourseRegistration.ajax_url, {
            method: "POST",
            credentials: 'same-origin',
            body: data
        })
            .then(response => response.json())
            .then(data => {
                console.log(data)
                this.deleteUserButtonConfirm.classList.remove('processing')
                if (data.success) {
                    _paq.push(['trackEvent', 'VideoCourse', 'Deleted', 'User', id])
                    window.location.reload()
                }
            })
            .catch((error) => {
                console.log('[Video Registration Login]');
                console.error(error);
            });
    }

}

const userCheck = new UserRegister(document.body)
