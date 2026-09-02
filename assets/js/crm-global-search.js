(function () {
    'use strict';

    var searchTimer = null;
    var activeIndex = -1;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function navigateToRecord(type, id) {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = type === 'lead' ? 'add-lead-master.php' : 'add-customer.php';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = type === 'lead' ? 'leadId' : 'customerId';
        input.value = id;
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
    }

    function hideResults() {
        var box = document.getElementById('crmGlobalSearchResults');
        if (box) {
            box.style.display = 'none';
            box.innerHTML = '';
        }
        activeIndex = -1;
    }

    function renderResults(data) {
        var box = document.getElementById('crmGlobalSearchResults');
        if (!box) return;

        var leads = data.leads || [];
        var customers = data.customers || [];
        var html = '';

        if (!leads.length && !customers.length) {
            html = '<div class="crm-search-empty">No results found</div>';
        } else {
            if (leads.length) {
                html += '<div class="crm-search-section-label">Leads</div>';
                leads.forEach(function (lead) {
                    var subtitle = [lead.sContactperson, lead.sPhone, lead.sEmail].filter(Boolean).join(' · ');
                    html += '<button type="button" class="crm-search-item" data-type="lead" data-id="' + lead.iLead_id + '">' +
                        '<span class="crm-search-item-icon crm-search-item-icon--lead"><i class="bx bx-briefcase"></i></span>' +
                        '<span class="crm-search-item-body">' +
                            '<strong>' + escapeHtml(lead.sCompany_name || lead.sLead_name || 'Lead #' + lead.iLead_id) + '</strong>' +
                            '<small>' + escapeHtml(subtitle || lead.sLead_name || '') + '</small>' +
                        '</span>' +
                    '</button>';
                });
            }
            if (customers.length) {
                html += '<div class="crm-search-section-label">Customers</div>';
                customers.forEach(function (customer) {
                    var subtitle = [customer.sContactname, customer.sPhone, customer.sEmail].filter(Boolean).join(' · ');
                    html += '<button type="button" class="crm-search-item" data-type="customer" data-id="' + customer.iCustomerid + '">' +
                        '<span class="crm-search-item-icon crm-search-item-icon--customer"><i class="bx bx-buildings"></i></span>' +
                        '<span class="crm-search-item-body">' +
                            '<strong>' + escapeHtml(customer.sCompanyname || 'Customer #' + customer.iCustomerid) + '</strong>' +
                            '<small>' + escapeHtml(subtitle || '') + '</small>' +
                        '</span>' +
                    '</button>';
                });
            }
        }

        box.innerHTML = html;
        box.style.display = 'block';

        box.querySelectorAll('.crm-search-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                navigateToRecord(btn.getAttribute('data-type'), btn.getAttribute('data-id'));
            });
        });
    }

    function runSearch(query) {
        fetch('api.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'globalsearch', query: query })
        })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.status === 'success' && res.data) {
                    renderResults(res.data);
                } else {
                    hideResults();
                }
            })
            .catch(function () {
                hideResults();
            });
    }

    function initGlobalSearch() {
        var input = document.getElementById('crmGlobalSearchInput');
        var box = document.getElementById('crmGlobalSearchResults');
        var toggle = document.getElementById('crmMobileSearchToggle');
        var searchWrap = document.getElementById('crmHeaderSearch');

        if (!input || !box) return;

        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(searchTimer);
            if (q.length < 2) {
                hideResults();
                return;
            }
            searchTimer = setTimeout(function () {
                runSearch(q);
            }, 280);
        });

        input.addEventListener('keydown', function (e) {
            var items = box.querySelectorAll('.crm-search-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                items.forEach(function (el, i) {
                    el.classList.toggle('is-active', i === activeIndex);
                });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                items.forEach(function (el, i) {
                    el.classList.toggle('is-active', i === activeIndex);
                });
            } else if (e.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
                e.preventDefault();
                items[activeIndex].click();
            } else if (e.key === 'Escape') {
                hideResults();
                input.blur();
            }
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.crm-search-wrap')) {
                hideResults();
            }
        });

        if (toggle && searchWrap) {
            toggle.addEventListener('click', function () {
                searchWrap.classList.toggle('is-mobile-open');
                if (searchWrap.classList.contains('is-mobile-open')) {
                    input.focus();
                } else {
                    hideResults();
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', initGlobalSearch);
})();
