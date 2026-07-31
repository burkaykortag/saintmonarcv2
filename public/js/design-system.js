/**
 * SaintMonarc Enterprise Design System - JS Module
 * Standart klavye kısayolları, Tema Yönetimi (Dark/Light/Auto) ve Sürükle-Bırak sıralama hafızası.
 */

// 1. THEME ENGINE MODULE
const ThemeEngine = {
    init() {
        const savedTheme = localStorage.getItem('sm-theme') || 'dark';
        this.setTheme(savedTheme);
    },
    setTheme(theme) {
        if (theme === 'auto') {
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', systemPrefersDark ? 'dark' : 'light');
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }
        localStorage.setItem('sm-theme', theme);
    },
    toggle() {
        const current = localStorage.getItem('sm-theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        this.setTheme(next);
    }
};

// 2. SHORTCUT SYSTEM MODULE
const ShortcutSystem = {
    init() {
        document.addEventListener('keydown', (e) => {
            // Do not trigger shortcuts when user typing in input fields
            const activeTag = document.activeElement.tagName.toLowerCase();
            if (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select' || document.activeElement.isContentEditable) {
                return;
            }

            const key = e.key.toLowerCase();
            switch (key) {
                case 'n':
                    e.preventDefault();
                    window.location.href = '/SaintMonarc/admin/products/create';
                    break;
                case 'o':
                    e.preventDefault();
                    window.location.href = '/SaintMonarc/admin/orders';
                    break;
                case 'm':
                    e.preventDefault();
                    window.location.href = '/SaintMonarc/admin/media';
                    break;
                case 'c':
                    e.preventDefault();
                    window.location.href = '/SaintMonarc/admin/components';
                    break;
            }
        });
    }
};

// 3. DASHBOARD WIDGET ORDER SAVER (LOCAL STORAGE)
const DashboardPersonalization = {
    init(gridId) {
        const grid = document.getElementById(gridId);
        if (!grid) return;

        // Restore custom ordered widgets from localStorage
        const orderKey = `sm-widget-order-${gridId}`;
        const savedOrder = JSON.parse(localStorage.getItem(orderKey));

        if (savedOrder) {
            const widgets = Array.from(grid.children);
            const widgetMap = {};
            widgets.forEach(w => {
                if (w.id) widgetMap[w.id] = w;
            });

            // Reorder grid children
            savedOrder.forEach(id => {
                if (widgetMap[id]) {
                    grid.appendChild(widgetMap[id]);
                }
            });
        }

        // Listen for drops to save new ordering layout
        grid.addEventListener('dragend', () => {
            const currentWidgets = Array.from(grid.children);
            const currentIds = currentWidgets.map(w => w.id).filter(id => id);
            localStorage.setItem(orderKey, JSON.stringify(currentIds));
        });
    }
};

// 4. SIDEBAR ACCORDION MODULE
const SidebarAccordion = {
    init() {
        const menu = document.getElementById('sidebarMenu');
        if (!menu) return;

        const categories = menu.querySelectorAll('.menu-category');
        categories.forEach(cat => {
            // Find sibling anchor tags until next menu-category
            const wrapper = document.createElement('div');
            wrapper.className = 'menu-category-content';
            wrapper.style.transition = 'max-height 0.25s ease-out';
            wrapper.style.overflow = 'hidden';

            let sibling = cat.nextElementSibling;
            const toWrap = [];
            while (sibling && !sibling.classList.contains('menu-category')) {
                toWrap.push(sibling);
                sibling = sibling.nextElementSibling;
            }

            // Insert wrapper after category header
            cat.parentNode.insertBefore(wrapper, cat.nextSibling);
            
            let hasActiveChild = false;
            toWrap.forEach(el => {
                wrapper.appendChild(el);
                if (el.classList.contains('active')) {
                    hasActiveChild = true;
                }
            });

            // Chevron Indicator
            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', 'chevron-down');
            icon.style.width = '12px';
            icon.style.height = '12px';
            icon.style.marginLeft = 'auto';
            icon.style.transition = 'transform 0.2s';
            cat.style.display = 'flex';
            cat.style.alignItems = 'center';
            cat.style.cursor = 'pointer';
            cat.style.userSelect = 'none';
            cat.appendChild(icon);

            // Set Initial State
            if (hasActiveChild) {
                wrapper.style.maxHeight = wrapper.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
            } else {
                wrapper.style.maxHeight = '0px';
            }

            // Toggle Click Handler
            cat.addEventListener('click', () => {
                const isOpen = wrapper.style.maxHeight !== '0px';
                if (isOpen) {
                    wrapper.style.maxHeight = '0px';
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    wrapper.style.maxHeight = wrapper.scrollHeight + 'px';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });

        // Re-trigger Lucide to draw new chevron icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
};

// Initializing Modules
document.addEventListener('DOMContentLoaded', () => {
    ThemeEngine.init();
    ShortcutSystem.init();
    DashboardPersonalization.init('draggableGridSales');
    SidebarAccordion.init();
});
