<h2>
    Авторизация
</h2>

{{ form("signup/login/" + redirect) }}

    <p>
        <label for="name">
            Имя
        </label>

        {{ textField("name") }}
    </p>
    
    <p>
        <label for="password">
            Password
        </label>

        {{ passwordField("password") }}
    </p>



    <p>
        {{ submitButton("Логин") }}
    </p>

</form>

