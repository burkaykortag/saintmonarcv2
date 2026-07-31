<?php
/**
 * Reusable Datagrid Component
 */
function renderDatagrid(array $options = []): string {
    return \App\Helpers\Ui::datagrid($options);
}
