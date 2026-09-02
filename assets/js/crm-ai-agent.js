(function () {
    'use strict';

    var API_URL = 'ai-agent-api.php';
    var STORAGE_KEY = 'crm_ai_chat_v1';
    var history = [];
    var uiMessages = [];
    var busy = false;

    function el(id) { return document.getElementById(id); }

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function scrollBottom() {
        var box = el('crmAiMessages');
        if (box) box.scrollTop = box.scrollHeight;
    }

    function saveChat() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                history: history.slice(-40),
                uiMessages: uiMessages.slice(-80)
            }));
        } catch (e) {}
    }

    function loadChat() {
        try {
            var raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return false;
            var data = JSON.parse(raw);
            if (!data || !Array.isArray(data.uiMessages)) return false;
            history = Array.isArray(data.history) ? data.history : [];
            uiMessages = data.uiMessages;
            return uiMessages.length > 0;
        } catch (e) {
            return false;
        }
    }

    function clearStoredChat() {
        try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
    }

    function appendMessage(role, text, extraHtml, skipSave) {
        var box = el('crmAiMessages');
        if (!box) return null;
        var div = document.createElement('div');
        div.className = 'crm-ai-msg crm-ai-msg--' + role;
        div.textContent = text || '';
        if (extraHtml) {
            var wrap = document.createElement('div');
            wrap.innerHTML = extraHtml;
            div.appendChild(wrap);
        }
        box.appendChild(div);
        scrollBottom();

        if (!skipSave && role !== 'typing') {
            // Don't persist live confirm buttons (tokens expire across pages)
            uiMessages.push({ role: role, text: text || '' });
            if (uiMessages.length > 80) uiMessages = uiMessages.slice(-80);
            saveChat();
        }
        return div;
    }

    function renderStoredMessages() {
        var box = el('crmAiMessages');
        if (!box) return;
        box.innerHTML = '';
        uiMessages.forEach(function (msg) {
            appendMessage(msg.role, msg.text, '', true);
        });
        scrollBottom();
    }

    function showTyping() {
        var box = el('crmAiMessages');
        if (!box) return null;
        var div = document.createElement('div');
        div.className = 'crm-ai-typing';
        div.id = 'crmAiTyping';
        div.innerHTML = '<span></span><span></span><span></span>';
        box.appendChild(div);
        scrollBottom();
        return div;
    }

    function hideTyping() {
        var t = el('crmAiTyping');
        if (t) t.remove();
    }

    function setBusy(state) {
        busy = state;
        var send = el('crmAiSend');
        var input = el('crmAiInput');
        if (send) send.disabled = state;
        if (input) input.disabled = state;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderPendingAction(pending) {
        if (!pending || !pending.token) return '';
        var summary = escapeHtml(pending.summary || 'Confirm this action?');
        var typeLabel = pending.type === 'create_reminder' ? 'Create reminder' : 'Create lead';
        return (
            '<div class="crm-ai-confirm" data-token="' + escapeHtml(pending.token) + '">' +
                '<strong>' + escapeHtml(typeLabel) + ' — confirmation needed</strong>' +
                '<p>' + summary + '</p>' +
                '<div class="crm-ai-confirm-actions">' +
                    '<button type="button" class="crm-ai-btn-confirm" data-ai-confirm>Confirm</button>' +
                    '<button type="button" class="crm-ai-btn-cancel" data-ai-cancel>Cancel</button>' +
                '</div>' +
            '</div>'
        );
    }

    function welcome() {
        var box = el('crmAiMessages');
        if (!box || box.childElementCount) return;
        appendMessage(
            'bot',
            'Hi! I\'m your infiCRM AI assistant.\n\nAsk about today\'s follow-ups, won projects, tasks, daily reports, or say things like:\n• "Show my open project tasks"\n• "Did anyone submit daily report today?"\n• "Create a lead for Rahul, phone 9876543210"\n\nCreates always need your Confirm click.'
        );
    }

    function sendMessage(text) {
        text = (text || '').trim();
        if (!text || busy) return;

        var input = el('crmAiInput');
        if (input) {
            input.value = '';
            autoGrow(input);
        }

        appendMessage('user', text);
        history.push({ role: 'user', text: text });
        if (history.length > 40) history = history.slice(-40);
        saveChat();
        setBusy(true);
        showTyping();

        fetch(API_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'chat',
                message: text,
                history: history.slice(0, -1)
            })
        })
            .then(function (r) {
                return r.text().then(function (body) {
                    var res = null;
                    try { res = JSON.parse(body); } catch (e) { res = null; }
                    if (!r.ok || !res) {
                        var snippet = (body || '').replace(/<[^>]+>/g, ' ').trim().slice(0, 180);
                        throw new Error(snippet || ('HTTP ' + r.status));
                    }
                    return res;
                });
            })
            .then(function (res) {
                hideTyping();
                if (!res || res.status !== 'success') {
                    appendMessage('error', (res && res.message) || 'Something went wrong.');
                    return;
                }
                var reply = res.reply || 'Done.';
                history.push({ role: 'model', text: reply });
                if (history.length > 40) history = history.slice(-40);
                appendMessage('bot', reply, renderPendingAction(res.pending_action));
                saveChat();
            })
            .catch(function (err) {
                hideTyping();
                appendMessage('error', (err && err.message) ? err.message : 'Network error. Please try again.');
            })
            .finally(function () {
                setBusy(false);
                var inputEl = el('crmAiInput');
                if (inputEl) {
                    inputEl.value = '';
                    inputEl.focus();
                    autoGrow(inputEl);
                }
            });
    }

    function confirmAction(token, card) {
        if (!token || busy) return;
        setBusy(true);
        var buttons = card.querySelectorAll('button');
        buttons.forEach(function (b) { b.disabled = true; });

        fetch(API_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'confirm', token: token })
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.status === 'success') {
                    card.innerHTML = '<strong>Created</strong><p>' + escapeHtml(res.message || 'Success') +
                        (res.link ? ' <a href="' + escapeHtml(res.link) + '">Open</a>' : '') + '</p>';
                    appendMessage('system', res.message || 'Action completed.');
                    if (typeof window.refreshCrmNotifBadge === 'function') {
                        window.refreshCrmNotifBadge();
                    }
                } else {
                    card.innerHTML = '<strong>Failed</strong><p>' + escapeHtml((res && res.message) || 'Could not complete action.') + '</p>';
                }
            })
            .catch(function () {
                card.innerHTML = '<strong>Failed</strong><p>Network error.</p>';
            })
            .finally(function () { setBusy(false); });
    }

    function cancelAction(token, card) {
        fetch(API_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cancel', token: token })
        }).catch(function () {});
        card.innerHTML = '<strong>Cancelled</strong><p>No changes were made.</p>';
    }

    function autoGrow(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 90) + 'px';
    }

    function openPanel() {
        var panel = el('crmAiPanel');
        if (!panel) return;
        panel.hidden = false;
        try { sessionStorage.setItem('crm_ai_panel_open', '1'); } catch (e) {}
        if (!el('crmAiMessages').childElementCount) {
            if (loadChat()) renderStoredMessages();
            else welcome();
        }
        var input = el('crmAiInput');
        if (input) input.focus();
    }

    function closePanel() {
        var panel = el('crmAiPanel');
        if (panel) panel.hidden = true;
        try { sessionStorage.setItem('crm_ai_panel_open', '0'); } catch (e) {}
    }

    ready(function () {
        var fab = el('crmAiFab');
        var panel = el('crmAiPanel');
        var form = el('crmAiForm');
        var input = el('crmAiInput');
        if (!fab || !panel || !form) return;

        // Restore chat history on every page
        if (loadChat()) {
            renderStoredMessages();
        }

        fab.addEventListener('click', function () {
            if (panel.hidden) openPanel();
            else closePanel();
        });

        var closeBtn = el('crmAiClose');
        if (closeBtn) closeBtn.addEventListener('click', closePanel);

        var clearBtn = el('crmAiClear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                history = [];
                uiMessages = [];
                clearStoredChat();
                var box = el('crmAiMessages');
                if (box) box.innerHTML = '';
                welcome();
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            sendMessage(input ? input.value : '');
        });

        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage(input.value);
                }
            });
            input.addEventListener('input', function () { autoGrow(input); });
        }

        var suggestions = el('crmAiSuggestions');
        if (suggestions) {
            suggestions.addEventListener('click', function (e) {
                var btn = e.target.closest('button[data-prompt]');
                if (!btn) return;
                sendMessage(btn.getAttribute('data-prompt'));
            });
        }

        var messages = el('crmAiMessages');
        if (messages) {
            messages.addEventListener('click', function (e) {
                var confirmBtn = e.target.closest('[data-ai-confirm]');
                var cancelBtn = e.target.closest('[data-ai-cancel]');
                var card = e.target.closest('.crm-ai-confirm');
                if (!card) return;
                var token = card.getAttribute('data-token');
                if (confirmBtn) confirmAction(token, card);
                if (cancelBtn) cancelAction(token, card);
            });
        }

        // Re-open panel if it was open before page navigation
        try {
            if (sessionStorage.getItem('crm_ai_panel_open') === '1') {
                panel.hidden = false;
                if (!messages.childElementCount) welcome();
            }
        } catch (e) {}
    });
})();
