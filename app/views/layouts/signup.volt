<h2>
    Зарегистрируйтесь, используя эту форму
</h2>

<?php echo $this->tag->form("register"); ?>

    <p>
        <label for="name">
            Имя
        </label>

        {{ textField("name") }}
    </p>

    <p>
        <label for="email">
            E-Mail
        </label>

        {{ emailField("email") }}
    </p>
    
    <p>
        <label for="password">
            Password
        </label>

        {{ passwordField("password") }}
    </p>



    <p>
        <?php echo $this->tag->submitButton("Регистрация"); ?>
    </p>

</form>
