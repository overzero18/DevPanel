<?php
// Example reusable card component
?>
<div class="card devpanel-card">
    <div class="card-body">
        <h5 class="card-title"><?php echo isset($title) ? htmlspecialchars($title) : 'Título'; ?></h5>
        <p class="card-text"><?php echo isset($body) ? htmlspecialchars($body) : 'Contenido'; ?></p>
    </div>
</div>
