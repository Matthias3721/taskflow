<?php /** @var string $title */ ?>
<section class="auth-card">
    <h2>Rejestracja</h2>
    <p class="text-muted">Formularz rejestracji – logika zostanie dodana w kolejnych etapach.</p>
    <form id="register-form" class="form" action="#" method="post" novalidate>
        <label for="name">Imię i nazwisko</label>
        <input type="text" id="name" name="name" required autocomplete="name">

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autocomplete="email">

        <label for="password">Hasło</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">

        <button type="submit" class="btn btn-primary">Utwórz konto</button>
    </form>
    <p class="form-footer">Masz już konto? <a href="/login">Zaloguj się</a></p>
</section>
<script src="/assets/js/auth.js" defer></script>
