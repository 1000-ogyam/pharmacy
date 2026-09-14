<section class="auth-stage">
    <div class="auth-intro">
        <figure class="auth-portrait">
            <img src="<?= e(asset('img/pharmacist-hero.png')) ?>" alt="Pharmacist at PL Pharmaceuticals">
        </figure>
        <div class="auth-copy">
            <img class="auth-hero-logo" src="<?= e(asset('img/pl-logo.png')) ?>" alt="PL Pharmaceuticals Ltd">
            <p class="eyebrow">PL Pharmaceuticals · Ghana</p>
            <h1>Care you can dispense with confidence.</h1>
            <p>Wholesale, retail, inventory, and clinical work in one workspace — so every sale, batch, and prescription stays accurate at the counter.</p>
        </div>
    </div>
    <div class="auth-card">
        <img class="auth-card-logo" src="<?= e(asset('img/pl-logo.png')) ?>" alt="PL Pharmaceuticals Ltd">
        <h2>Sign in</h2>
        <p class="lead">Use your staff account to open the branch workspace.</p>
        <?php \App\Core\View::include('partials.flash-messages'); ?>
        <form method="post" action="<?= e(url('/login')) ?>">
            <?= csrf_field() ?>
            <div class="form-row" style="margin-bottom:12px;">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
                <?php foreach (errors('email') as $err): ?><div class="help"><?= e($err) ?></div><?php endforeach; ?>
            </div>
            <div class="form-row" style="margin-bottom:18px;">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>
            <button class="btn btn-block btn-lg" type="submit">Sign in</button>
        </form>
        <?php if (config('app.debug')): ?>
        <p class="auth-hint">Demo: admin@plpharma.com / Password123!</p>
        <?php endif; ?>
    </div>
</section>
