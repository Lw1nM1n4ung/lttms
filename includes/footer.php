</main>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>&#9992; LTTMS</h3>
                <p><?= t('footer_agency') ?> — <?= t('footer_address') ?></p>
            </div>
            <div class="footer-col">
                <h3><?= t('footer_destinations') ?></h3>
                <ul>
                    <?php try { $footerDests = getActiveDestinations(); } catch (PDOException $e) { $footerDests = []; } ?>
                    <?php foreach ($footerDests as $fd): ?>
                        <li><?= sanitize($fd['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h3><?= t('footer_quick_links') ?></h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>/customer/packages.php"><?= t('nav_packages') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/register.php"><?= t('nav_register') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/login.php"><?= t('nav_login') ?></a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3><?= t('footer_contact') ?></h3>
                <p><?= t('footer_address') ?></p>
                <p>Phone: +95 9 123 456 789</p>
                <p>Email: info@lttms.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> LTTMS. <?= t('footer_rights') ?></p>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
