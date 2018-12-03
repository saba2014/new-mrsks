// this should be new brunch
class JWTAccessManager {
    constructor(usersRole = 0) {
        this.usersRole = usersRole;
    }

    // Главное назначение этого метода обменять пару usrname/password на токен, что будет
    // использоваться в дальнейшем для доступа к ресурсам. А включает он в себя роль, токен,
    // рефреш токен, время жизни токена
    login(event) {

        let username = document.getElementById('username').value;
        let password = document.getElementById('password').value;
        let path = "api/auth";

        // обмениваем на сервере пару username/password
        // на необходимые для доступа токены
        event.JWT = event.data;
        $.ajax({
            url: path,
            context: this,
            data: {username: username, password: password},
            type: "GET",
            success: event.data.LoginCallback
        });
    }


    // функция обратного вызова для обработки полученных
    // токенов в обмен на пару username/password в методе login
    LoginCallback(data) {

        if (data.message != 'Bad login or password') {
            var self = this;
            // интерпертируем роль, полученную от сервера (см. реализацию метода)
            this.shiftingRole(data['role'], function () {
                // Убрать позже из конструктора сохранение!!!
                let token = new TokenStorage(data['access_token'], data['refresh_token'], data['expire']);

                // необходимо полученные данные сохранить в localStorage и cookies
                token.saveTokens();

                // удалём окно логина с экрана
                self.removeLoginWindow();


                // этот костыль позволяет не обращаться к объекту карты,
                // когда мы находимся в панели администратора, т.к. там объекта карты может не быть
                if ((window.location.href).indexOf("admin") === -1) {
                    // принимаем решение отображать ли кнопку панели администратора или нет,
                    // в зависимости от полученной от сервера роли пользователя
                    self.checkRole();

                    map.fire('dragend');
                    // скорее всего этот метод предназначен для работы карты,
                    // в панели администратора он не нужен
                    getRegions();
                }
            });
        }
        else {
            $('#loginError').show();
            $('#enter').hide().fadeIn('fast');
            document.getElementById('loginError').innerText = 'Ошибка авторизации!\n' + 'Повторите ввод данных.';
        }
    }

    // функция для отображения кнопки доступа к панели администратора
    checkRole() {
        if (this.usersRole > 1) {
            adminenabled = true;
            $('.admin_button').removeClass('d-none').addClass('d-none d-md-block');
            $('.admin_tgl_button').removeClass('d-none').addClass('d-sm-block d-md-block d-xl-none');
            if (this.usersRole === 3)
                $('#numSearch').css('display', 'block');
        }
    };

    // метод предназначен для интерпретации роли и используется для поддержания написанного кода
    // хотя имеется подозрение, что во фронте можно было бы обойтись и без этого
    shiftingRole(role, successCallBack) {
        let path = "api/getRoleList";

        // получаем список ролей и коды с сервера
        // роли не сохраняются, сразу преобразуются в код
        $.ajax({
            url: path, context: this, type: "GET", success: function (data) {
                if (successCallBack !== undefined) {
                    this.usersRole = data.Roles.find(x => x.name === role).code;
                    successCallBack();
                }
            }
        });
    }

    // метод, получающий с сервера окно логина и размещающий его в центре экрана
    createLoginWindow() {

        if (document.getElementById('loginWindow') === null) {
            let loginWindow = document.createElement('div');
            document.body.appendChild(loginWindow);
            loginWindow.id = 'loginWindow';
            let token = new TokenStorage();
            token.deleteTokens();
            $.ajax({
                url: 'login/getLoginView',
                beforeSend: function (request) {
                },
                context: this,
                type: 'GET',
                success: function (data) {
                    loginWindow.innerHTML = data;
                    $('#loginError').hide();
                    let login_form = document.querySelectorAll('#login form');
                    if (login_form !== undefined && login_form[0] !== undefined) {
                        login_form[0].removeAttribute('action');
                    }
                    let login_button = document.querySelectorAll('#enter');
                    let self = this;
                    if (login_button !== undefined && login_button[0] !== undefined) {
                        login_button[0].type = 'button';
                        // передаём в обработчик параметр, а именно контекст вызовы this,
                        // в данном случае this это объект JWT, содержащий все необходимые для логина данные
                        $("#enter").on('click', (event) => {
                            event.data= self;
                            self.login(event);
                        });
                    }
                    $('#loginWindow').on('keyup', function (event) {
                        if (event.keyCode === 13)
                            $('#enter').click();
                    });
                },
                error: function (e) {
                    console.log('error 1');
                }
            });
        }
    }

    // метод для удаления окна логина с экрана
    removeLoginWindow() {
        document.getElementById('loginWindow').parentNode.removeChild(document.getElementById('loginWindow'));
    }

    // метод перенесен из файла logInModule.js, который более не используется
    getRole(callback) {
        $.ajax({
            url: 'login/checkUser',
            beforeSend: function (request) {
            },
            context: this,
            type: 'POST',
            success: function (data) {
                data = JSON.parse(data);
                this.shiftingRole(data['role'], callback);
            },
            error: function (e) {
                console.log('error 2');
                this.usersRole = 0;
                callback();
            }
        });
    }

}

// перенесено из logInModule, сам файл удалён,
// а этот метод заглушка, которая будет удалена после следующего рефакторинга
function logOut() {
    console.log('Hello, but I am useless! I was almost deleted by some developer =)');
}