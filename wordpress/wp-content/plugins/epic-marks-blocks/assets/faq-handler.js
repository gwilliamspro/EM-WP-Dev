/**
 * Epic Marks FAQ Handler
 * Handles category/FAQ toggle logic, expand/collapse controls
 */
(function() {
    'use strict';

    /**
     * Initialize a single FAQ section
     */
    function initSection(section) {
        if (!section) return;

        const groupsEl = section.querySelector('[data-faq-root]');
        if (!groupsEl) return;

        // Mark section as initialized
        section.setAttribute('data-initialized', 'true');

        // Get configuration
        const openFirstCat = section.getAttribute('data-open-first-cat') === 'true';
        const openFirstFaq = section.getAttribute('data-open-first-faq') === 'true';

        // Get all categories and FAQ items
        const categories = Array.from(section.querySelectorAll('.em-faq__cat'));
        const items = Array.from(section.querySelectorAll('.em-faq__item'));

        /**
         * Get all FAQ items that belong to a category
         */
        function faqsForCategory(catEl) {
            const faqs = [];
            let el = catEl.nextElementSibling;
            while (el && !el.classList.contains('em-faq__cat')) {
                if (el.classList.contains('em-faq__item')) {
                    faqs.push(el);
                }
                el = el.nextElementSibling;
            }
            return faqs;
        }

        /**
         * Sync FAQ items visibility based on category state
         */
        function syncCategory(catEl) {
            const open = catEl.hasAttribute('open');
            const faqs = faqsForCategory(catEl);

            faqs.forEach(function(faq) {
                if (open) {
                    faq.classList.add('is-visible');
                } else {
                    faq.classList.remove('is-visible');
                    faq.removeAttribute('open');
                }
            });
        }

        // Add toggle event listener to each category
        categories.forEach(function(cat) {
            cat.addEventListener('toggle', function() {
                syncCategory(cat);
            });
        });

        // Initialize all FAQ items as hidden
        items.forEach(function(faq) {
            faq.classList.remove('is-visible');
            faq.removeAttribute('open');
        });

        // Open first category if configured
        if (openFirstCat && categories.length > 0) {
            categories[0].setAttribute('open', 'open');
        }

        // Sync all categories to update FAQ visibility
        categories.forEach(syncCategory);

        // Open first FAQ in open categories if configured
        if (openFirstFaq) {
            categories.forEach(function(cat) {
                if (cat.hasAttribute('open')) {
                    const faqs = faqsForCategory(cat);
                    if (faqs.length > 0) {
                        faqs[0].setAttribute('open', 'open');
                    }
                }
            });
        }

        // Setup expand/collapse controls
        const controls = section.querySelector('[data-faq-controls]');
        if (controls) {
            const btnExpand = controls.querySelector('[data-expand-cats]');
            const btnCollapse = controls.querySelector('[data-collapse-cats]');

            if (btnExpand) {
                btnExpand.addEventListener('click', function() {
                    categories.forEach(function(cat) {
                        cat.setAttribute('open', 'open');
                    });
                    categories.forEach(syncCategory);
                });
            }

            if (btnCollapse) {
                btnCollapse.addEventListener('click', function() {
                    categories.forEach(function(cat) {
                        cat.removeAttribute('open');
                    });
                    categories.forEach(syncCategory);
                });
            }
        }
    }

    /**
     * Initialize all FAQ sections on the page
     */
    function initAll() {
        document.querySelectorAll('.em-service-faq').forEach(initSection);
    }

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Re-initialize if Shopify section is reloaded (for Shopify compatibility)
    document.addEventListener('shopify:section:load', function(e) {
        const section = e.target && e.target.querySelector ? e.target.querySelector('.em-service-faq') : null;
        if (section) initSection(section);
    });

    document.addEventListener('shopify:section:select', function(e) {
        const section = e.target && e.target.querySelector ? e.target.querySelector('.em-service-faq') : null;
        if (section) initSection(section);
    });

})();
