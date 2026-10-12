/*

 * Telkom Fallout System - soft navigation helper

 *

 * Rules:

 * - GET links/forms can navigate without a full document reload.

 * - POST/PUT/DELETE, uploads and downloads remain normal browser requests.

 * - Dashboard replaces only #contentShell .content.

 * - Public pages replace head/body while keeping this helper alive.

 */

(function () {

    'use strict';

    if (window.__TFNavigationInstalled) {

        return;

    }

    window.__TFNavigationInstalled = true;

    var activeRequest = null;

    var loadedExternalScripts = Object.create(null);

    function currentMode() {

        return document.body

            ? (document.body.getAttribute('data-tf-nav') || 'public')

            : 'public';

    }

    function isSameOrigin(url) {

        return url.origin === window.location.origin;

    }

    function shouldIgnoreAnchor(anchor, url) {

        if (!anchor || !url || !isSameOrigin(url)) {

            return true;

        }

        if (

            anchor.target === '_blank' ||

            anchor.hasAttribute('download') ||

            anchor.dataset.noSpa !== undefined ||

            anchor.rel === 'external'

        ) {

            return true;

        }

        if (url.protocol !== 'http:' && url.protocol !== 'https:') {

            return true;

        }

        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) {

            return true;

        }

        /* File downloads must remain native browser navigation. */

        if (url.pathname.indexOf('/download') !== -1 || url.pathname.indexOf('-download') !== -1) {

            return true;

        }

        return false;

    }

    function shouldIgnoreForm(form) {

        if (!form) {

            return true;

        }

        var method = (form.getAttribute('method') || 'GET').toUpperCase();

        if (method !== 'GET') {

            return true;

        }

        if (form.target === '_blank' || form.dataset.noSpa !== undefined) {

            return true;

        }

        var action = form.getAttribute('action') || window.location.href;

        var url = new URL(action, window.location.href);

        if (!isSameOrigin(url)) {

            return true;

        }

        if (url.pathname.indexOf('/download') !== -1 || url.pathname.indexOf('-download') !== -1) {

            return true;

        }

        return false;

    }

    // Page-wide loading overlay intentionally disabled for light SPA navigation.

    function showLoader() {

        // No overlay; keep the call site compatible with existing navigation flow.

    }

    function hideLoader() {

        // No overlay is created by this navigation helper.

    }

    function copyBodyAttributes(fromBody, toBody) {

        if (!fromBody || !toBody) {

            return;

        }

        Array.from(toBody.attributes).forEach(function (attr) {

            toBody.removeAttribute(attr.name);

        });

        Array.from(fromBody.attributes).forEach(function (attr) {

            toBody.setAttribute(attr.name, attr.value);

        });

    }

    function cleanupDashboardTransientNodes() {

        var keep = [

            '#sidebarBackdrop',

            '#sidebar',

            '.sidebar-close',

            '.main',

            '#logoutLoading',

        ];

        Array.from(document.body.children).forEach(function (child) {

            var shouldKeep = keep.some(function (selector) {

                return child.matches(selector);

            });

            if (!shouldKeep) {

                child.remove();

            }

        });

    }

    function closeDashboardMobileSidebar() {

        if (typeof window.closeMobileSidebar === 'function') {

            try {

                window.closeMobileSidebar();

            } catch (e) {}

        }

    }

    function updateDashboardActiveState(url) {

        var pathname = url.pathname.replace(/\/+$/, '') || '/';

        document.querySelectorAll('.nav-item[href]').forEach(function (item) {

            var itemUrl;

            try {

                itemUrl = new URL(item.href, window.location.href);

            } catch (e) {

                return;

            }

            var itemPath = itemUrl.pathname.replace(/\/+$/, '') || '/';

            item.classList.toggle('active', itemPath === pathname);

        });

        document.querySelectorAll('.witel-branch').forEach(function (branch) {

            branch.classList.remove('open');

        });

        document.querySelectorAll('.witel-branch').forEach(function (branch) {

            var hasCurrent = false;

            branch.querySelectorAll('.nav-item[href]').forEach(function (item) {

                try {

                    var itemUrl = new URL(item.href, window.location.href);

                    var itemPath = itemUrl.pathname.replace(/\/+$/, '') || '/';

                    if (itemPath === pathname) {

                        hasCurrent = true;

                    }

                } catch (e) {}

            });

            if (hasCurrent) {

                branch.classList.add('open');

            }

        });

        var adminLink = document.querySelector('.admin-nav-group .nav-item[href]');

        if (adminLink) {

            adminLink.classList.toggle('active', pathname.indexOf('/admin/users') === 0);

        }

    }

    function runInlineScript(source) {

        var originalDocumentAdd = document.addEventListener;

        var originalWindowAdd = window.addEventListener;

        document.addEventListener = function (type, listener, options) {

            if (type === 'DOMContentLoaded' && typeof listener === 'function') {

                try {

                    listener(new Event('DOMContentLoaded'));

                } catch (e) {}

                return;

            }

            return originalDocumentAdd.call(document, type, listener, options);

        };

        window.addEventListener = function (type, listener, options) {

            if (type === 'load' && typeof listener === 'function') {

                try {

                    listener(new Event('load'));

                } catch (e) {}

                return;

            }

            return originalWindowAdd.call(window, type, listener, options);

        };

        try {

            new Function(source)();

        } finally {

            document.addEventListener = originalDocumentAdd;

            window.addEventListener = originalWindowAdd;

        }

    }

    function loadExternalScript(src) {

        var absolute = new URL(src, window.location.href).href;

        if (loadedExternalScripts[absolute]) {

            return loadedExternalScripts[absolute];

        }

        loadedExternalScripts[absolute] = new Promise(function (resolve, reject) {

            var script = document.createElement('script');

            script.src = absolute;

            script.async = false;

            script.onload = resolve;

            script.onerror = reject;

            document.head.appendChild(script);

        });

        return loadedExternalScripts[absolute];

    }

    function runScripts(root) {

        if (!root) {

            return Promise.resolve();

        }

        var scripts = Array.from(root.querySelectorAll('script'));

        var chain = Promise.resolve();

        scripts.forEach(function (oldScript) {

            if (oldScript.src) {

                /*

                 * Mulai load CDN sekarang, tetapi jangan blokir perpindahan

                 * halaman. Inline script setelahnya tetap menunggu library

                 * tersebut selesai agar Chart.js / SheetJS tetap aman.

                 */

                chain = chain.then(function () {

                    return loadExternalScript(oldScript.src).catch(function () {

                        // Library opsional gagal; halaman tetap boleh tampil.

                    });

                });

                return;

            }

            var source = oldScript.textContent || '';

            if (source.trim() !== '') {

                chain = chain.then(function () {

                    try {

                        runInlineScript(source);

                    } catch (e) {

                        // Satu script opsional tidak boleh merusak navigasi.

                    }

                });

            }

        });

        return chain;

    }

    function removeInitialPageLoaders(root) {

        if (!root) {

            return;

        }

        var selectors = [

            '#pageLoading',

            '#trPageLoading',

            '#dfPageLoading',

            '#udPageLoading',

            '#edPageLoading',

            '#ahPageLoading',

            '#exPageLoading'

        ];

        selectors.forEach(function (selector) {

            root.querySelectorAll(selector).forEach(function (el) {

                el.remove();

            });

        });

    }

    async function fetchHtml(url, mode) {

        if (activeRequest) {

            activeRequest.abort();

        }

        var requestController = new AbortController();
        activeRequest = requestController;

        try {

            var response = await fetch(url.href, {

                method: 'GET',

                credentials: 'same-origin',

                cache: 'no-store',

                headers: {

                    'X-TF-SPA': '1',

                    'X-Requested-With': 'XMLHttpRequest',

                    'Accept': 'text/html,application/xhtml+xml'

                },

                signal: requestController.signal

            });

            if (!response.ok) {

                throw new Error('HTTP ' + response.status);

            }

            var contentType = response.headers.get('content-type') || '';

            if (contentType.indexOf('text/html') === -1) {

                throw new Error('Bukan respons HTML');

            }

            var html = await response.text();

            var parser = new DOMParser();

            return parser.parseFromString(html, 'text/html');

        } finally {

            if (activeRequest === requestController) {
                activeRequest = null;
            }

        }

    }

    function clearDashboardPageStyles() {
        document.head.querySelectorAll('[data-tf-spa-page-style="1"]').forEach(function (node) {
            node.remove();
        });
    }

    function syncDashboardStyles(nextDoc, nextContent) {
        /* Replace only styles copied for Detail Fallout by the previous SPA page. */
        clearDashboardPageStyles();

        var existingStyleText = new Set(
            Array.from(document.head.querySelectorAll('style:not([data-tf-spa-page-style])'))
                .map(function (node) { return (node.textContent || '').trim(); })
                .filter(Boolean)
        );

        var styleNodes = [];
        if (nextDoc && nextDoc.head) {
            styleNodes = styleNodes.concat(Array.from(nextDoc.head.querySelectorAll('style')));
        }
        if (nextContent) {
            styleNodes = styleNodes.concat(Array.from(nextContent.querySelectorAll('style')));
        }

        var installedStyleText = new Set();
        styleNodes.forEach(function (sourceStyle) {
            var css = (sourceStyle.textContent || '').trim();
            if (!css || existingStyleText.has(css) || installedStyleText.has(css)) {
                return;
            }

            var clone = document.createElement('style');
            clone.setAttribute('data-tf-spa-page-style', '1');
            if (sourceStyle.media) {
                clone.media = sourceStyle.media;
            }
            clone.textContent = css;
            document.head.appendChild(clone);
            installedStyleText.add(css);
        });

        /* Preserve any page-specific linked stylesheet that is missing in the persistent shell. */
        if (nextDoc && nextDoc.head) {
            Array.from(nextDoc.head.querySelectorAll('link[rel~="stylesheet"][href]')).forEach(function (sourceLink) {
                var href;
                try {
                    href = new URL(sourceLink.getAttribute('href'), nextDoc.baseURI || window.location.href).href;
                } catch (e) {
                    return;
                }

                var alreadyPresent = Array.from(document.head.querySelectorAll('link[rel~="stylesheet"][href]')).some(function (link) {
                    try {
                        return new URL(link.href, window.location.href).href === href;
                    } catch (e) {
                        return false;
                    }
                });

                if (alreadyPresent) {
                    return;
                }

                var clone = sourceLink.cloneNode(true);
                clone.href = href;
                clone.setAttribute('data-tf-spa-page-style', '1');
                document.head.appendChild(clone);
            });
        }
    }

    async function navigateDashboard(url, options) {

        options = options || {};

        try {

            var nextDoc = await fetchHtml(url, 'dashboard');

            var nextContent = nextDoc.querySelector('#contentShell .content, .main > .content');

            /*
             * Dashboard SPA only swaps .content; CSS emitted in the fetched
             * Detail Fallout response's <head> would otherwise be lost. Copy
             * those styles for this page only, and remove them on the next
             * dashboard navigation so other screens remain unchanged.
             */
            if (nextContent && /\/detail-fallout\/?$/.test(url.pathname)) {
                syncDashboardStyles(nextDoc, nextContent);
            } else {
                clearDashboardPageStyles();
            }

            /* Standalone admin create/edit pages fall back to full navigation. */

            if (!nextContent) {

                window.location.assign(url.href);

                return;

            }

            var currentContent = document.querySelector('#contentShell .content, .main > .content');

            if (!currentContent) {

                window.location.assign(url.href);

                return;

            }

            cleanupDashboardTransientNodes();

            currentContent.innerHTML = nextContent.innerHTML;

            currentContent.className = nextContent.className;

            document.title = nextDoc.title || document.title;

            document.body.setAttribute('data-tf-nav', 'dashboard');

            /*

             * Jangan menunggu script/CDN sebelum UI berpindah.

             * Konten tampil segera; script partial berjalan di background.

             */

            runScripts(currentContent).catch(function () {});

            if (options.push !== false) {

                window.history.pushState({}, '', url.href);

            }

            updateDashboardActiveState(url);

            closeDashboardMobileSidebar();

            window.scrollTo(0, 0);

        } catch (error) {

            if (error && error.name === 'AbortError') {

                return;

            }

            window.location.assign(url.href);

        } finally {

            hideLoader();

        }

    }

    function isRekapRoute(url) {
        return !!url && /^\/rekap-fallout(?:\/(?:semua|jaktim|jakpus|jaksel))?\/?$/i.test(url.pathname);
    }

    async function navigateRekapPublic(url, options) {
        options = options || {};
        var nextDoc = await fetchHtml(url, 'public');
        var selectors = [
            '.page-head-left',
            '.page-head-actions',
            '.stats',
            '#viewTabel',
            '#viewGrafik'
        ];

        // Validate every target first; do not partially mutate a page if markup differs.
        var replacements = selectors.map(function (selector) {
            var currentNode = document.querySelector(selector);
            var nextNode = nextDoc.querySelector(selector);
            if (!currentNode || !nextNode) {
                throw new Error('Markup Rekap tidak lengkap: ' + selector);
            }
            return { current: currentNode, next: nextNode };
        });

        replacements.forEach(function (item) {
            item.current.replaceWith(document.importNode(item.next, true));
        });

        document.title = nextDoc.title || document.title;

        if (options.push !== false) {
            window.history.pushState({}, '', url.href);
        }

        var view = url.searchParams.get('view') === 'grafik' ? 'grafik' : 'tabel';
        if (typeof window.rfSwitchView === 'function') {
            window.rfSwitchView(view);
        }
        if (typeof window.rfInitPagination === 'function') {
            window.rfInitPagination();
        }
    }

    async function navigatePublic(url, options) {

        options = options || {};

        try {

            var currentUrl = new URL(window.location.href);
            if (isRekapRoute(currentUrl) && isRekapRoute(url)) {
                await navigateRekapPublic(url, options);
                return;
            }

            showLoader('Memuat halaman...');

            var nextDoc = await fetchHtml(url, 'public');

            copyBodyAttributes(nextDoc.body, document.body);

            document.title = nextDoc.title || document.title;

            document.head.innerHTML = nextDoc.head.innerHTML;

            document.body.innerHTML = nextDoc.body.innerHTML;

            var root = document.body;

            await runScripts(root);

            if (options.push !== false) {

                window.history.pushState({}, '', url.href);

            }

            window.scrollTo(0, 0);

        } catch (error) {

            if (error && error.name === 'AbortError') {

                return;

            }

            window.location.assign(url.href);

        } finally {

            hideLoader();

        }

    }

    async function navigate(url, options) {

        if (!url || !isSameOrigin(url)) {

            return;

        }

        if (currentMode() === 'dashboard') {

            await navigateDashboard(url, options);

        } else {

            await navigatePublic(url, options);

        }

    }

    /*

     * Public SPA API untuk partial yang perlu berpindah GET

     * tanpa full document reload.

     */

    window.tfNavigate = function (url, options) {

        var target = new URL(url, window.location.href);

        if (!isSameOrigin(target)) {

            return;

        }

        return navigate(target, options || {push: true});

    };

    document.addEventListener('click', function (event) {

        if (

            event.defaultPrevented ||

            event.button !== 0 ||

            event.metaKey ||

            event.ctrlKey ||

            event.shiftKey ||

            event.altKey

        ) {

            return;

        }

        var anchor = event.target.closest('a[href]');

        if (!anchor) {

            return;

        }

        var url;

        try {

            url = new URL(anchor.href, window.location.href);

        } catch (e) {

            return;

        }

        if (shouldIgnoreAnchor(anchor, url)) {

            return;

        }

        /* Dashboard nav and public GET links are the only links handled here. */

        if (currentMode() === 'dashboard') {

            var path = url.pathname;

            var isDashboardNav =

                path === '/dashboard' ||

                path.indexOf('/dashboard/') === 0 ||

                path.indexOf('/admin/users') === 0;

            if (!isDashboardNav) {

                return;

            }

        }

        event.preventDefault();

        navigate(url, {push: true});

    });

    document.addEventListener('submit', function (event) {

        var form = event.target;

        if (currentMode() !== 'public' || shouldIgnoreForm(form)) {

            return;

        }

        var method = (form.getAttribute('method') || 'GET').toUpperCase();

        if (method !== 'GET') {

            return;

        }

        var action = form.getAttribute('action') || window.location.href;

        var url = new URL(action, window.location.href);

        var formData;

        try {

            formData = new FormData(form, event.submitter);

        } catch (e) {

            formData = new FormData(form);

        }

        Array.from(url.searchParams.keys()).forEach(function (key) {

            url.searchParams.delete(key);

        });

        formData.forEach(function (value, key) {

            if (typeof value === 'string') {

                url.searchParams.append(key, value);

            }

        });

        event.preventDefault();

        navigate(url, {push: true});

    });

    window.addEventListener('popstate', function () {

        var url = new URL(window.location.href);

        navigate(url, {push: false});

    });

})();
