<?php

if(!APP_LOADED) {
    header('HTTP/1.0 403 Forbidden');
    die("You can't access this file directly");
}

$user = App\Mailcow::getUser();

?>
<!DOCTYPE html>
<html lang="en" data-theme="gourmet">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mail Status</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
        <link href="https://cdn.jsdelivr.net/npm/flyonui@1.3.0/dist/full.min.css" rel="stylesheet">
        <link rel="shortcut icon" href="/favicon.png" type="image/png">
        <link rel="icon" href="/favicon.png" type="image/png">
        <style>
            /* fix modal classes */
            .overlay-open\:opacity-100 {
                opacity: 1;
            }
            .bg-base-shadow\/70 {
                background-color: var(--fallback-bs,oklch(var(--bs)/1));
                opacity: 0.7;
            }
            pre code.hljs {
                font-size: 0.875em;
            }
        </style>
        <script>
            const darkMode = localStorage.getItem("darkMode") == "true" || false;
            localStorage.setItem("darkMode", darkMode);
            if(darkMode) {
                document.addEventListener('DOMContentLoaded', function(){
                    document.getElementById('theme-controller').checked = true;
                    document.querySelector('img.logo').src = '<?php echo $_ENV['LOGO_DARK']; ?>';
                }, false);
            }
            function storeDarkMode(checkbox) {
                localStorage.setItem("darkMode", checkbox.checked);
                document.querySelector('img.logo').src = checkbox.checked ? '<?php echo $_ENV['LOGO_DARK']; ?>' : '<?php echo $_ENV['LOGO_LIGHT']; ?>';
            }
        </script>
    </head>
    <body class="grid h-screen">
        <div class="h-20">
            <nav class="navbar rounded-box shadow">
                <div class="navbar-start">
                    <img class="logo h-12" src="<?php echo $_ENV['LOGO_LIGHT']; ?>" alt="Mail Status">
                </div>
                <div class="navbar-center max-md:hidden">
                <ul class="menu menu-horizontal gap-2 p-0 text-base rtl:ml-20">
                    <li><a href="<?php echo APP_ROOT; ?>?task=monitor"<?php echo $query['task'] != 'monitor' ?: ' class="active"'; ?>>Monitor</a></li>
                    <?php if($user->role !== 'user'): ?>
                    <li><a href="<?php echo APP_ROOT; ?>?task=dmarc"<?php echo $query['task'] != 'dmarc' ?: ' class="active"'; ?>>Dmarc</a></li>
                    <?php endif; ?>
                </ul>
                </div>
                <div class="navbar-end items-center gap-4">
                    <label class="relative flex">
                        <input id="theme-controller" type="checkbox" value="dark" onclick="storeDarkMode(this)" class="switch switch-primary theme-controller peer" />
                        <span class="peer-checked:text-primary-content absolute start-1 top-1 block size-4"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12a4 4 0 1 0 8 0a4 4 0 1 0-8 0m-5 0h1m8-9v1m8 8h1m-9 8v1M5.6 5.6l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg></span>
                        <span class="text-base-content peer-checked:text-base-content absolute end-1 top-1 block size-4" ><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3h.393a7.5 7.5 0 0 0 7.92 12.446A9 9 0 1 1 12 2.992z"/></svg></span>
                    </label>
                    <a class="btn btn-primary" href="<?php echo APP_ROOT; ?>?task=logout">Sign out</a>
                </div>
            </nav>
        </div>
        <div class="w-full overflow-x-auto" style="height:calc(100vh - 8.5rem)">