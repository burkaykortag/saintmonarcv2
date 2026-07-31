<?php
/**
 * Reusable Form Components
 */
function renderInput(array $options = []): string {
    return \App\Helpers\Ui::input($options);
}

function renderSelect(array $options = []): string {
    return \App\Helpers\Ui::select($options);
}
