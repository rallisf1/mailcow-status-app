<?php

if(!APP_LOADED) {
    header('HTTP/1.0 403 Forbidden');
    die("You can't access this file directly");
}
?>
<div class="flex h-full items-center justify-center">
    <div class="card sm:max-w-sm">
        <div class="card-body">
            <h5 class="card-title mb-2.5">Page not found</h5>
            <p class="mb-4">The page you are looking for can't be found.</p>
            <div class="card-actions">
                <button onclick="history.back()" class="btn btn-primary">Go Back</button>
            </div>
        </div>
    </div>
</div>
