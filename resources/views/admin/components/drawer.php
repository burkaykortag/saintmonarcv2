<?php
/**
 * Reusable Drawer Component
 */
function renderDrawer(array $options = []): string {
    return \App\Helpers\Ui::drawer($options);
}
