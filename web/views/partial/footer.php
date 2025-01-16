<?php

if(!APP_LOADED) {
    header('HTTP/1.0 403 Forbidden');
    die("You can't access this file directly");
}
?>

            </div>
            <footer class="footer bg-base-200/60 items-center rounded-t-box px-6 py-4 shadow h-14">
                <p class="w-full text-center block">&copy;<?php echo date('Y'); ?> <a class="link link-hover font-medium" href="<?php echo $_ENV['FOOTER_COMPANY_URL']; ?>" target="_blank"><?php echo $_ENV['FOOTER_COMPANY']; ?></a></p>
            </footer>
        </div>
        <script type="module" src="https://cdn.jsdelivr.net/npm/flyonui@1.3.0/dist/js/overlay.js"></script>
    </body>
</html>