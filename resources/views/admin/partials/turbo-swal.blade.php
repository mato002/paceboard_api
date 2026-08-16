{{-- Turbo + SweetAlert2 for PaceBoard Admin --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    turbo-frame { display: block; }
    .turbo-progress-bar {
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #60a5fa);
    }
    turbo-frame[busy] .frame-skeleton {
        display: block;
    }
    .frame-skeleton {
        display: none;
        padding: 2rem;
        text-align: center;
        color: var(--muted);
        font-size: .875rem;
    }
    .frame-skeleton i { animation: spin 1s linear infinite; margin-right: .5rem; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .pagination-nav {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .pagination-btn, .pagination-page {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 .65rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        text-decoration: none;
        font-size: .85rem;
        font-weight: 500;
        transition: all .15s;
    }
    .pagination-btn:hover, .pagination-page:hover { border-color: var(--primary); color: var(--primary); }
    .pagination-btn.disabled, .pagination-page.disabled { opacity: .4; pointer-events: none; }
    .pagination-page.active { background: var(--primary); color: #fff; border-color: var(--primary); }
    .pagination-pages { display: flex; gap: .35rem; flex-wrap: wrap; }
    .pagination-ellipsis { padding: 0 .35rem; color: var(--muted); }
    .pagination-info { font-size: .8rem; color: var(--muted); padding: 0 .5rem; }

    .swal2-popup { font-family: 'Inter', system-ui, sans-serif !important; border-radius: 16px !important; }
    .swal2-title { font-weight: 700 !important; }
    .swal2-confirm { border-radius: 10px !important; font-weight: 600 !important; }
    .swal2-cancel { border-radius: 10px !important; font-weight: 600 !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', () => {
    const SwalTheme = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-outline',
        },
        buttonsStyling: false,
    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3800,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });

    function showFlashes() {
        const frames = document.querySelectorAll('turbo-frame[data-flash-success], turbo-frame[data-flash-error], turbo-frame[data-flash-warning]');

        frames.forEach((frame) => {
            const success = frame.dataset.flashSuccess;
            const error = frame.dataset.flashError;
            const warning = frame.dataset.flashWarning;

            if (success) {
                Toast.fire({ icon: 'success', title: success });
                delete frame.dataset.flashSuccess;
            }
            if (error) {
                Toast.fire({ icon: 'error', title: error });
                delete frame.dataset.flashError;
            }
            if (warning) {
                Toast.fire({ icon: 'warning', title: warning });
                delete frame.dataset.flashWarning;
            }
        });
    }

    function syncHeaderFromFrame() {
        const frame = document.getElementById('main-content');
        if (!frame) return;

        const title = frame.dataset.pageTitle;
        const subtitle = frame.dataset.pageSubtitle;
        const breadcrumb = frame.dataset.breadcrumb;

        if (title) {
            const el = document.querySelector('.page-title');
            if (el) el.textContent = title;
            document.title = title + ' — PaceBoard Admin';
        }
        if (subtitle !== undefined) {
            const el = document.querySelector('.page-subtitle');
            if (el) el.textContent = subtitle;
        }
        if (breadcrumb) {
            const el = document.querySelector('.breadcrumb-current');
            if (el) el.textContent = breadcrumb;
        }
    }

    function updateNavActive() {
        const path = window.location.pathname;
        document.querySelectorAll('.sidebar-nav .nav-link[href]').forEach((link) => {
            const href = link.getAttribute('href');
            link.classList.toggle('active', href === path);
        });
    }

    function bindConfirmForms() {
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            if (form.dataset.confirmBound) return;
            form.dataset.confirmBound = '1';

            form.addEventListener('submit', async (e) => {
                if (form.dataset.confirmed === '1') {
                    delete form.dataset.confirmed;
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                const isDanger = form.dataset.confirmDanger === 'true' || form.querySelector('.btn-danger');
                const result = await SwalTheme.fire({
                    title: form.dataset.confirmTitle || 'Are you sure?',
                    text: form.dataset.confirm || 'This action cannot be undone.',
                    icon: form.dataset.confirmIcon || (isDanger ? 'warning' : 'question'),
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmButton || 'Yes, proceed',
                    cancelButtonText: form.dataset.cancelButton || 'Cancel',
                    reverseButtons: true,
                    focusCancel: isDanger,
                });

                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        });
    }

    function bindConfirmLinks() {
        document.querySelectorAll('a[data-confirm]').forEach((link) => {
            if (link.dataset.confirmBound) return;
            link.dataset.confirmBound = '1';

            link.addEventListener('click', async (e) => {
                e.preventDefault();
                const result = await SwalTheme.fire({
                    title: link.dataset.confirmTitle || 'Are you sure?',
                    text: link.dataset.confirm,
                    icon: link.dataset.confirmIcon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: link.dataset.confirmButton || 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                });
                if (result.isConfirmed) {
                    if (link.dataset.turboFrame) {
                        Turbo.visit(link.href, { frame: link.dataset.turboFrame });
                    } else {
                        window.location.href = link.href;
                    }
                }
            });
        });
    }

    function initAdminUi() {
        showFlashes();
        syncHeaderFromFrame();
        updateNavActive();
        bindConfirmForms();
        bindConfirmLinks();
    }

    document.addEventListener('turbo:load', initAdminUi);
    document.addEventListener('turbo:frame-render', (e) => {
        initAdminUi();
    });

    document.addEventListener('turbo:frame-missing', (e) => {
        e.preventDefault();
        e.detail.visit(e.detail.response);
    });

    document.addEventListener('turbo:submit-start', (e) => {
        const form = e.target;
        if (form?.dataset?.skipLoader === 'true') return;

        Swal.fire({
            title: 'Processing…',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading(),
        });
    });

    document.addEventListener('turbo:submit-end', (e) => {
        if (Swal.isVisible() && Swal.isLoading()) {
            Swal.close();
        }
        if (e.detail.success === false) {
            Toast.fire({ icon: 'error', title: 'Something went wrong. Please try again.' });
        }
    });

    document.addEventListener('turbo:before-fetch-request', (e) => {
        const frame = e.target?.closest?.('turbo-frame') || e.target;
        if (frame?.id === 'main-content' || e.detail?.fetchOptions?.headers?.['Turbo-Frame']) {
            document.body.classList.add('turbo-loading');
        }
    });

    document.addEventListener('turbo:frame-render', () => {
        document.body.classList.remove('turbo-loading');
    });

    initAdminUi();
});
</script>