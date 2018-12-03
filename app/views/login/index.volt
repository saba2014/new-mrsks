<div id="wrapper">
    <div id="login">
        {{ flash.output() }}
        <div id="loginError"></div>
        <form  action="login?{{ redirect }}" autocomplete="on" method="POST">
            <!--"location.href = this.action + location.hash; return true;"-->
            <h1>Войти</h1>
            <p>
                <label for="username" class="uname" > Имя пользователя11 </label><br>
                <input id="username" name="username" required="required" type="text" placeholder="пример: Иванов" autofocus/>
            </p>
            <p>
                <label for="password" class="youpasswd"> Пароль </label><br>
                <input id="password" name="password" required="required" type="password" placeholder="пример: X8df!90EO" />
            </p>
            <p class="login button">
                <input type="submit" value="Войти" />
            </p>
        </form>
    </div>
</div>