<?php /** @var string $title */ ?>
<section class="auth-card">
    <h2>Logowanie</h2>
    <div id="login-error" class="form-error" hidden></div>
    <form id="login-form" class="form" action="#" method="post" novalidate>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autocomplete="email">

        <label for="password">Hasło</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn btn-primary">Zaloguj się</button>
    </form>
    <p class="form-footer">Nie masz konta? <a href="/register">Zarejestruj się</a></p>
</section>
<script src="/assets/js/auth.js" defer></script>
