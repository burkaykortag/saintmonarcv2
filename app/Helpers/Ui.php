<?php

declare(strict_types=1);

namespace App\Helpers;

class Ui {
    /**
     * Renders a premium button.
     */
    public static function button(array $options = []): string {
        $text = $options['text'] ?? 'Button';
        $type = $options['type'] ?? 'primary'; // primary, secondary, gold, danger, warning, success, outline, ghost, link
        $size = $options['size'] ?? 'medium'; // small, medium, large
        $isLoading = $options['loading'] ?? false;
        $isDisabled = $options['disabled'] ?? false;
        $icon = $options['icon'] ?? ''; // Lucide icon name
        $attributes = $options['attributes'] ?? '';

        $sizeClass = [
            'small' => 'btn-sm py-1 px-3 fs-7',
            'medium' => 'py-2 px-4 fs-6',
            'large' => 'btn-lg py-3 px-5 fs-5'
        ][$size] ?? 'py-2 px-4 fs-6';

        $btnClass = '';
        switch ($type) {
            case 'primary':
                $btnClass = 'btn btn-primary';
                break;
            case 'secondary':
                $btnClass = 'btn btn-secondary';
                break;
            case 'gold':
                $btnClass = 'btn btn-warning';
                break;
            case 'danger':
                $btnClass = 'btn btn-danger';
                break;
            case 'warning':
                $btnClass = 'btn btn-warning bg-warning bg-opacity-25 border-warning text-warning';
                break;
            case 'success':
                $btnClass = 'btn btn-success';
                break;
            case 'outline':
                $btnClass = 'btn btn-outline-secondary';
                break;
            case 'ghost':
                $btnClass = 'btn btn-link text-white text-decoration-none';
                break;
            case 'link':
                $btnClass = 'btn btn-link text-warning';
                break;
            default:
                $btnClass = 'btn btn-primary';
        }

        $disabledAttr = ($isDisabled || $isLoading) ? 'disabled' : '';
        $iconHtml = '';
        if ($isLoading) {
            $iconHtml = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';
        } elseif ($icon) {
            $iconHtml = '<i data-lucide="' . $icon . '" class="me-2" style="width:16px; height:16px;"></i>';
        }

        return "<button type='button' class='btn {$btnClass} {$sizeClass} d-inline-flex align-items-center justify-content-center border-0' style='border-radius:12px; transition: var(--transition-premium);' {$disabledAttr} {$attributes}>
            {$iconHtml}<span>{$text}</span>
        </button>";
    }

    /**
     * Renders a premium card.
     */
    public static function card(array $options = []): string {
        $title = $options['title'] ?? '';
        $value = $options['value'] ?? '';
        $icon = $options['icon'] ?? '';
        $color = $options['color'] ?? 'var(--sm-gold)';
        $body = $options['body'] ?? '';
        $footer = $options['footer'] ?? '';

        $iconHtml = $icon ? "<div class='p-2 rounded-3' style='background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border); color: {$color};'><i data-lucide='{$icon}' style='width:22px; height:22px;'></i></div>" : '';

        $titleHtml = $title ? "<span class='text-muted fs-7 text-uppercase font-weight-600' style='letter-spacing: 0.5px;'>{$title}</span>" : '';
        $valueHtml = $value ? "<h3 class='font-weight-800 m-0 mt-2' style='font-size: 28px; color:#ffffff;'>{$value}</h3>" : '';

        return "
        <div class='card p-4 border-0 h-100'>
            <div class='d-flex align-items-center justify-content-between mb-2'>
                <div>
                    {$titleHtml}
                    {$valueHtml}
                </div>
                {$iconHtml}
            </div>
            <div class='card-body p-0 mt-2'>
                {$body}
            </div>
            " . ($footer ? "<div class='card-footer p-0 bg-transparent border-0 mt-3'>{$footer}</div>" : "") . "
        </div>";
    }

    /**
     * Renders a premium DataGrid.
     */
    public static function datagrid(array $options = []): string {
        $headers = $options['headers'] ?? [];
        $rows = $options['rows'] ?? [];
        $bulkActions = $options['bulk_actions'] ?? [];
        $pagination = $options['pagination'] ?? '';

        $headerHtml = '';
        foreach ($headers as $h) {
            $headerHtml .= "<th class='py-3'>{$h}</th>";
        }

        $rowsHtml = '';
        if (empty($rows)) {
            $colsCount = count($headers) + 1;
            $rowsHtml = "<tr><td colspan='{$colsCount}' class='text-center py-5 text-muted'>Gösterilecek veri bulunamadı.</td></tr>";
        } else {
            foreach ($rows as $r) {
                $rowsHtml .= "<tr style='border-bottom: 1px solid var(--sm-border); font-size:13px; vertical-align:middle;'>";
                $rowsHtml .= "<td class='py-3' style='width: 40px;'><input type='checkbox' class='form-check-input' style='background-color: var(--bg-hover); border-color: var(--sm-border);'></td>";
                foreach ($r as $val) {
                    $rowsHtml .= "<td class='py-3'>{$val}</td>";
                }
                $rowsHtml .= "</tr>";
            }
        }

        $bulkActionsHtml = '';
        if (!empty($bulkActions)) {
            $bulkActionsHtml = "<div class='d-flex align-items-center gap-2 mb-3 bg-opacity-10 bg-warning p-2 rounded-3' style='border: 1px solid var(--sm-border);'>";
            $bulkActionsHtml .= "<span class='text-warning fs-7 font-weight-600'><i class='bi bi-info-circle me-1'></i>Toplu İşlemler:</span>";
            foreach ($bulkActions as $actName => $actLink) {
                $bulkActionsHtml .= "<a href='{$actLink}' class='btn btn-sm btn-secondary text-white py-1 px-3 fs-8'>{$actName}</a>";
            }
            $bulkActionsHtml .= "</div>";
        }

        return "
        <div class='w-100'>
            {$bulkActionsHtml}
            <div class='table-responsive rounded-4' style='border: 1px solid var(--sm-border); background: var(--bg-card);'>
                <table class='table table-dark table-hover border-0 m-0' style='background: transparent;'>
                    <thead>
                        <tr style='border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:12px;'>
                            <th class='py-3' style='width: 40px;'><input type='checkbox' class='form-check-input' id='selectAllRows' style='background-color: var(--bg-hover); border-color: var(--sm-border);'></th>
                            {$headerHtml}
                        </tr>
                    </thead>
                    <tbody>
                        {$rowsHtml}
                    </tbody>
                </table>
            </div>
            " . ($pagination ? "<div class='mt-4 d-flex justify-content-between align-items-center flex-wrap gap-3'>{$pagination}</div>" : "") . "
        </div>";
    }

    /**
     * Renders a premium input element.
     */
    public static function input(array $options = []): string {
        $name = $options['name'] ?? '';
        $label = $options['label'] ?? '';
        $type = $options['type'] ?? 'text';
        $placeholder = $options['placeholder'] ?? '';
        $value = $options['value'] ?? '';
        $required = ($options['required'] ?? false) ? 'required' : '';

        $labelHtml = $label ? "<label class='form-label text-muted fs-7 mb-1 font-weight-500'>{$label}</label>" : '';

        return "
        <div class='mb-3'>
            {$labelHtml}
            <input type='{$type}' name='{$name}' class='search-input w-100 text-white' style='padding-left:16px;' placeholder='{$placeholder}' value='{$value}' {$required}>
        </div>";
    }

    /**
     * Renders a premium select element.
     */
    public static function select(array $options = []): string {
        $name = $options['name'] ?? '';
        $label = $options['label'] ?? '';
        $optionsList = $options['options'] ?? [];
        $selectedValue = $options['selected'] ?? '';

        $labelHtml = $label ? "<label class='form-label text-muted fs-7 mb-1 font-weight-500'>{$label}</label>" : '';

        $optionsHtml = '';
        foreach ($optionsList as $val => $text) {
            $sel = (string)$val === (string)$selectedValue ? 'selected' : '';
            $optionsHtml .= "<option value='{$val}' {$sel}>{$text}</option>";
        }

        return "
        <div class='mb-3'>
            {$labelHtml}
            <select name='{$name}' class='form-select border-0 text-white fs-7' style='background: rgba(255,255,255,0.03); padding: 10px 16px; border: 1px solid var(--sm-border) !important; border-radius:12px;'>
                {$optionsHtml}
            </select>
        </div>";
    }

    /**
     * Renders a premium modal.
     */
    public static function modal(array $options = []): string {
        $id = $options['id'] ?? 'modal-id';
        $title = $options['title'] ?? 'Modal Title';
        $body = $options['body'] ?? '';
        $footer = $options['footer'] ?? '';
        $size = $options['size'] ?? 'medium'; // small, medium, large, fullscreen

        $sizeClass = [
            'small' => 'modal-sm',
            'medium' => '',
            'large' => 'modal-lg',
            'fullscreen' => 'modal-fullscreen'
        ][$size] ?? '';

        return "
        <div class='modal fade' id='{$id}' tabindex='-1' aria-hidden='true'>
            <div class='modal-dialog modal-dialog-centered {$sizeClass}'>
                <div class='modal-content border-0 text-white' style='background: var(--bg-card); border-radius: var(--radius-premium); border: 1px solid var(--sm-border) !important; box-shadow: 0 25px 60px rgba(0,0,0,0.8);'>
                    <div class='modal-header border-bottom border-secondary border-opacity-10 p-4'>
                        <h5 class='modal-title font-weight-700 fs-6'>{$title}</h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body p-4 fs-7'>
                        {$body}
                    </div>
                    " . ($footer ? "<div class='modal-footer border-top border-secondary border-opacity-10 p-4 bg-transparent'>{$footer}</div>" : "") . "
                </div>
            </div>
        </div>";
    }

    /**
     * Renders a premium drawer.
     */
    public static function drawer(array $options = []): string {
        $id = $options['id'] ?? 'drawer-id';
        $title = $options['title'] ?? 'Drawer Title';
        $body = $options['body'] ?? '';
        $direction = $options['direction'] ?? 'right'; // left, right, bottom

        $placementClass = [
            'left' => 'offcanvas-start',
            'right' => 'offcanvas-end',
            'bottom' => 'offcanvas-bottom'
        ][$direction] ?? 'offcanvas-end';

        return "
        <div class='offcanvas {$placementClass} border-0 text-white' tabindex='-1' id='{$id}' style='background: var(--bg-darker); border-left: 1px solid var(--sm-border) !important; box-shadow: -10px 0 40px rgba(0,0,0,0.5);'>
            <div class='offcanvas-header border-bottom border-secondary border-opacity-10 p-4'>
                <h5 class='offcanvas-title font-weight-700 fs-6'>{$title}</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='offcanvas' aria-label='Close'></button>
            </div>
            <div class='offcanvas-body p-4 fs-7'>
                {$body}
            </div>
        </div>";
    }

    /**
     * Renders an empty state.
     */
    public static function emptyState(array $options = []): string {
        $message = $options['message'] ?? 'Veri Bulunmuyor';
        $subMessage = $options['sub_message'] ?? 'Arama kriterlerinize uyan kayıt bulunamadı.';
        $icon = $options['icon'] ?? 'inbox';

        return "
        <div class='text-center p-5 rounded-4' style='background: rgba(255,255,255,0.01); border: 1px dashed var(--sm-border);'>
            <div class='mb-3 text-muted' style='font-size: 48px;'><i data-lucide='{$icon}' class='d-inline-block mx-auto'></i></div>
            <h5 class='text-white font-weight-600 fs-6'>{$message}</h5>
            <p class='text-muted fs-7 mb-0'>{$subMessage}</p>
        </div>";
    }

    /**
     * Renders skeleton loaders.
     */
    public static function loader(array $options = []): string {
        $type = $options['type'] ?? 'card'; // card, table, list

        if ($type === 'table') {
            return "
            <div class='w-100 p-4 rounded-4' style='background: var(--bg-card); border: 1px solid var(--sm-border);'>
                <div class='placeholder-glow mb-3'><span class='placeholder col-4 py-3 bg-secondary bg-opacity-25' style='border-radius:6px;'></span></div>
                <div class='placeholder-glow mb-2'><span class='placeholder col-12 py-2 bg-secondary bg-opacity-25' style='border-radius:4px;'></span></div>
                <div class='placeholder-glow mb-2'><span class='placeholder col-12 py-2 bg-secondary bg-opacity-25' style='border-radius:4px;'></span></div>
                <div class='placeholder-glow mb-2'><span class='placeholder col-12 py-2 bg-secondary bg-opacity-25' style='border-radius:4px;'></span></div>
            </div>";
        }

        return "
        <div class='card p-4 border-0 placeholder-glow'>
            <span class='placeholder col-6 mb-3 bg-secondary bg-opacity-25' style='border-radius:4px;'></span>
            <span class='placeholder col-8 mb-2 bg-secondary bg-opacity-25' style='border-radius:4px;'></span>
            <span class='placeholder col-4 bg-secondary bg-opacity-25' style='border-radius:4px;'></span>
        </div>";
    }
}
