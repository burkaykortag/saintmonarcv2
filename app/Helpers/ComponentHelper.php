<?php

declare(strict_types=1);

namespace App\Helpers;

class ComponentHelper {
    public static function card(string $title, string $value, string $iconClass = '', string $color = '#D4AF37'): string {
        return "
        <div class='card shadow-sm border-0' style='background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid rgba(255,255,255,0.08) !important;'>
            <div class='card-body p-4'>
                <div class='d-flex align-items-center justify-content-between'>
                    <div>
                        <span class='text-muted fs-7 font-weight-500 text-uppercase' style='letter-spacing: 0.5px;'>{$title}</span>
                        <h3 class='mt-2 mb-0 font-weight-600' style='font-size: 26px; color: #ffffff;'>{$value}</h3>
                    </div>
                    <div class='p-3 rounded-3' style='background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: {$color};'>
                        <i class='{$iconClass}' style='font-size: 24px;'></i>
                    </div>
                </div>
            </div>
        </div>";
    }

    public static function badge(string $text, string $type = 'success'): string {
        $colors = [
            'success' => 'background: rgba(34, 197, 94, 0.1); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.2);',
            'warning' => 'background: rgba(234, 179, 8, 0.1); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.2);',
            'danger' => 'background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2);',
            'info' => 'background: rgba(59, 130, 246, 0.1); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.2);',
            'gold' => 'background: rgba(197, 168, 128, 0.1); color: #e5d1b8; border: 1px solid rgba(197, 168, 128, 0.2);',
        ];
        $style = $colors[$type] ?? $colors['success'];
        return "<span class='badge px-3 py-2 rounded-pill font-weight-600' style='{$style} font-size:11px; text-transform: uppercase;'>{$text}</span>";
    }

    public static function alert(string $message, string $type = 'info'): string {
        $colors = [
            'success' => 'background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.2); color: #86efac;',
            'error' => 'background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); color: #fca5a5;',
            'info' => 'background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); color: #93c5fd;',
        ];
        $style = $colors[$type] ?? $colors['info'];
        return "
        <div class='alert border-0 p-4 rounded-3 d-flex align-items-center' style='{$style}' role='alert'>
            <div class='fs-6'>{$message}</div>
        </div>";
    }

    public static function breadcrumb(array $crumbs): string {
        $html = "<nav aria-label='breadcrumb'><ol class='breadcrumb m-0'>";
        $total = count($crumbs);
        $i = 0;
        foreach ($crumbs as $title => $url) {
            $i++;
            if ($i === $total) {
                $html .= "<li class='breadcrumb-item active' aria-current='page' style='color: #c5a880; font-weight: 500;'>{$title}</li>";
            } else {
                $html .= "<li class='breadcrumb-item'><a href='{$url}' style='color: #64748b; text-decoration: none; transition: color 0.2s;'>{$title}</a></li>";
            }
        }
        $html .= "</ol></nav>";
        return $html;
    }

    public static function emptyState(string $message, string $subMessage = ''): string {
        return "
        <div class='text-center p-5 rounded-4' style='background: rgba(255,255,255,0.01); border: 1px dashed rgba(255,255,255,0.1);'>
            <div class='mb-3' style='font-size: 48px; color: #64748b;'><i class='bi bi-inbox'></i></div>
            <h5 class='text-white font-weight-500'>{$message}</h5>
            <p class='text-muted fs-6 mb-0'>{$subMessage}</p>
        </div>";
    }

    public static function loadingSpinner(): string {
        return "
        <div class='d-flex justify-content-center align-items-center p-5'>
            <div class='spinner-border' style='color: #c5a880;' role='status'>
                <span class='visually-hidden'>Yükleniyor...</span>
            </div>
        </div>";
    }
}
