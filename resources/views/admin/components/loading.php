<?php
/**
 * Reusable Loading Component
 */
function renderLoader(array $options = []): string {
    return \App\Helpers\Ui::loader($options);
}
