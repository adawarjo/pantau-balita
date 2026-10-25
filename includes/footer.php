<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
$layout = $layout ?? 'app';
$flash = get_flash();
?>
<?php if ($layout === 'app'): ?>
            <footer class="app-footer small text-muted">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · <a href="<?= e(url('changelog.php')) ?>" class="text-muted">v<?= e(APP_VERSION) ?></a> · <a href="<?= e(url('disclaimer.php')) ?>" class="text-muted">Disclaimer</a> · Data Anda bersifat pribadi dan hanya dapat diakses oleh akun Anda.
            </footer>
        </main>
    </div>
</div>
<?php endif; ?>
<?php if ($layout === 'app' && ($activeMenu ?? '') !== 'disclaimer' && user_needs_disclaimer(current_user())) require APP_ROOT . '/includes/disclaimer_modal.php'; ?>
<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/sweetalert2/sweetalert2.all.min.js')) ?>"></script>
<script>window.APP_BASE = <?= json_encode(base_url()) ?>;</script>
<script>window.APP_FLASH = <?= json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/pwa.js')) ?>"></script>
<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>
</html>
