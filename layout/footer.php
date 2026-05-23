</div>
</div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/clike/clike.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.js"></script>

<!-- JS personalizado -->
<?php
$devpanelAssetVersion = $devpanelAssetVersion ?? static function (string $path): string {
    $fullPath = dirname(__DIR__) . $path;
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?>
<script src="/devpanel/assets/js/app.js?v=<?php echo $devpanelAssetVersion('/assets/js/app.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/docker.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/docker.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/domains.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/domains.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/backups.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/backups.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/users.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/users.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/logs.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/logs.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/system.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/system.js'); ?>"></script>
<script src="/devpanel/assets/js/modules/terminal.js?v=<?php echo $devpanelAssetVersion('/assets/js/modules/terminal.js'); ?>"></script>
<script src="/devpanel/assets/js/filemanager.js?v=<?php echo $devpanelAssetVersion('/assets/js/filemanager.js'); ?>"></script>

</body>

</html>
