<div id="container_login">
    {{ content() }}
    <div id="wrapper">
        <div id="login">
            <form autocomplete="on">
                <!--"location.href = this.action + location.hash; return true;"-->
                <h1>Войти</h1>
                <div id="loginError"></div>
                <p>
                    <label for="username" class="uname" > Имя пользователя </label><br>
                    <input id="username" name="username" required="required" type="text" placeholder="пример: Иванов" autofocus/>
                </p>
                <p>
                    <label for="password" class="youpasswd"> Пароль </label><br>
                    <input id="password" name="password" required="required" type="password" placeholder="пример: X8df!90EO" />
                </p>
                <p class="login button">
                    <button id="enter">Войти</button>
                </p>
            </form>
        </div>
    </div>
</div>
