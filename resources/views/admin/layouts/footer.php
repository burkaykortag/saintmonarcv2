        </div> <!-- Close Container Fluid -->
        
        <!-- Footer Area -->
        <footer class="mt-auto py-4 px-5" style="background-color: var(--sm-dark); border-top: 1px solid var(--sm-border); font-size: 13px; color: var(--sm-text-muted);">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div>
                    &copy; <?= date('Y') ?> <span style="color: var(--sm-gold); font-weight: 500;">SaintMonarc</span>. Tüm Hakları Saklıdır.
                </div>
                <div class="d-flex gap-4">
                    <span>Sürüm: <strong>v1.0.0</strong></span>
                    <span>PHP: <strong><?= PHP_VERSION ?></strong></span>
                    <span>Ortam: <strong class="text-uppercase" style="color: var(--sm-gold);"><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'local') ?></strong></span>
                </div>
            </div>
        </footer>
    </div> <!-- Close Page Content Wrapper -->
</div> <!-- Close Wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

<!-- Sidebar Toggle Javascript -->
<script>
    const menuToggle = document.getElementById("menu-toggle");
    const wrapper = document.getElementById("wrapper");
    const sidebar = document.getElementById("sidebar-wrapper");

    menuToggle.addEventListener("click", event => {
        event.preventDefault();
        wrapper.classList.toggle("toggled");
        sidebar.classList.toggle("toggled");
    });
</script>
<!-- Enterprise Design System JavaScript -->
<script src="/SaintMonarc/public/js/design-system.js"></script>
<!-- Enterprise PIM V2 JavaScript -->
<script src="/SaintMonarc/public/js/pim.js"></script>
<!-- Central Address System JavaScript -->
<script src="/SaintMonarc/public/js/address-system.js"></script>
</body>
</html>
