/**
 * Auto-Save Module
 * Web 1.0 Portfolio Site - Blog Admin
 */
var AutoSave = (function() {
    'use strict';

    var DEBOUNCE_MS = 5000;
    var LOCAL_STORAGE_PREFIX = 'blog_autosave_';

    var state = {
        postId: null,
        csrfToken: null,
        saveTimeout: null,
        lastSavedContent: null,
        isSaving: false,
        statusElement: null,
        messageElement: null,
        indicatorElement: null
    };

    /**
     * Initialize auto-save for the post edit form
     */
    function init(postId, csrfToken) {
        state.postId = postId;
        state.csrfToken = csrfToken;

        // Find UI elements
        state.statusElement = document.getElementById('autosave-status');
        state.messageElement = document.getElementById('autosave-message');
        state.indicatorElement = document.getElementById('autosave-indicator');

        if (!state.statusElement) {
            console.warn('AutoSave: Status element not found');
            return;
        }

        // Store initial content for change detection
        state.lastSavedContent = getCurrentContent();

        // Check for localStorage recovery
        checkLocalStorageRecovery();

        // Attach event listeners
        attachEventListeners();

        updateStatus('Auto-save enabled', 'idle');
    }

    /**
     * Get current form content
     */
    function getCurrentContent() {
        return {
            title: document.getElementById('title').value,
            content_markdown: document.getElementById('content_markdown').value,
            excerpt: document.getElementById('excerpt').value
        };
    }

    /**
     * Check if content has changed since last save
     */
    function hasContentChanged() {
        if (!state.lastSavedContent) return true;

        var current = getCurrentContent();
        return current.title !== state.lastSavedContent.title ||
               current.content_markdown !== state.lastSavedContent.content_markdown ||
               current.excerpt !== state.lastSavedContent.excerpt;
    }

    /**
     * Attach event listeners to form fields
     */
    function attachEventListeners() {
        var fields = ['title', 'content_markdown', 'excerpt'];

        fields.forEach(function(fieldId) {
            var field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', scheduleAutoSave);
            }
        });

        // Save on visibility change (user switching tabs)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden' && hasContentChanged()) {
                performAutoSave();
            }
        });

        // Save before unload (fallback to localStorage)
        window.addEventListener('beforeunload', function() {
            if (hasContentChanged()) {
                saveToLocalStorage();
            }
        });
    }

    /**
     * Schedule an auto-save after debounce period
     */
    function scheduleAutoSave() {
        if (state.saveTimeout) {
            clearTimeout(state.saveTimeout);
        }

        if (!hasContentChanged()) {
            return;
        }

        updateStatus('Changes detected...', 'pending');

        state.saveTimeout = setTimeout(function() {
            performAutoSave();
        }, DEBOUNCE_MS);
    }

    /**
     * Perform the auto-save via AJAX
     */
    function performAutoSave() {
        if (state.isSaving) return;

        var content = getCurrentContent();

        // Don't save if both title and content are empty
        if (!content.title.trim() && !content.content_markdown.trim()) {
            return;
        }

        state.isSaving = true;
        updateStatus('Saving...', 'saving');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/blog/admin/ajax/auto-save.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-Token', state.csrfToken);

        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;

            state.isSaving = false;

            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        handleSaveSuccess(response);
                    } else {
                        handleSaveError(response.error);
                    }
                } catch (e) {
                    handleSaveError('Invalid response');
                }
            } else if (xhr.status === 401) {
                handleSessionExpired();
            } else {
                handleSaveError('Server error (' + xhr.status + ')');
            }
        };

        xhr.onerror = function() {
            state.isSaving = false;
            handleSaveError('Network error');
            saveToLocalStorage();
        };

        xhr.send(JSON.stringify({
            post_id: state.postId,
            title: content.title,
            content_markdown: content.content_markdown,
            excerpt: content.excerpt
        }));
    }

    /**
     * Handle successful save
     */
    function handleSaveSuccess(response) {
        state.lastSavedContent = getCurrentContent();

        // Clear localStorage draft since we saved to DB
        clearLocalStorage();

        // Update post_id if this was a new post
        if (!state.postId && response.post_id) {
            state.postId = response.post_id;
            // Update URL without reload
            var newUrl = window.location.pathname + '?id=' + response.post_id;
            history.replaceState(null, '', newUrl);
        }

        var time = new Date().toLocaleTimeString();
        updateStatus('Saved at ' + time, 'saved');
    }

    /**
     * Handle save error
     */
    function handleSaveError(message) {
        updateStatus('Save failed: ' + message, 'error');
        // Fallback to localStorage
        saveToLocalStorage();
    }

    /**
     * Handle session expired
     */
    function handleSessionExpired() {
        updateStatus('Session expired - please log in again', 'error');
        // Save to localStorage before potential redirect
        saveToLocalStorage();
    }

    /**
     * Save draft to localStorage
     */
    function saveToLocalStorage() {
        var key = getLocalStorageKey();
        var content = getCurrentContent();
        content.saved_at = new Date().toISOString();
        content.post_id = state.postId;

        try {
            localStorage.setItem(key, JSON.stringify(content));
            if (state.messageElement) {
                var msg = state.messageElement.textContent;
                if (msg.indexOf('localStorage') === -1) {
                    updateStatus(msg + ' (saved locally)', 'saved');
                }
            }
        } catch (e) {
            console.error('AutoSave: localStorage save failed', e);
        }
    }

    /**
     * Check for localStorage recovery on page load
     */
    function checkLocalStorageRecovery() {
        var key = getLocalStorageKey();
        var stored = localStorage.getItem(key);

        if (!stored) return;

        try {
            var draft = JSON.parse(stored);
            var current = getCurrentContent();

            // Only offer recovery if localStorage has different/newer content
            var hasDifference =
                draft.title !== current.title ||
                draft.content_markdown !== current.content_markdown ||
                draft.excerpt !== current.excerpt;

            if (hasDifference) {
                showRecoveryPrompt(draft);
            } else {
                // Content matches, clear localStorage
                clearLocalStorage();
            }
        } catch (e) {
            clearLocalStorage();
        }
    }

    /**
     * Show recovery prompt to user
     */
    function showRecoveryPrompt(draft) {
        var savedTime = draft.saved_at
            ? new Date(draft.saved_at).toLocaleString()
            : 'unknown time';

        // Create a simple prompt (Web 1.0 style)
        var promptDiv = document.createElement('div');
        promptDiv.id = 'autosave-recovery';
        promptDiv.style.cssText =
            'background:#ffffcc;border:2px solid #cccc00;padding:10px;margin-bottom:15px;';
        promptDiv.innerHTML =
            '<strong>Unsaved draft found!</strong><br>' +
            'Last saved: ' + savedTime + '<br>' +
            '<button type="button" id="restore-draft" class="btn btn-small">Restore Draft</button> ' +
            '<button type="button" id="discard-draft" class="btn btn-small btn-secondary">Discard</button>';

        var form = document.querySelector('form');
        form.parentNode.insertBefore(promptDiv, form);

        document.getElementById('restore-draft').onclick = function() {
            document.getElementById('title').value = draft.title || '';
            document.getElementById('content_markdown').value = draft.content_markdown || '';
            document.getElementById('excerpt').value = draft.excerpt || '';
            state.lastSavedContent = getCurrentContent();
            promptDiv.remove();
            clearLocalStorage();
            updateStatus('Draft restored', 'saved');
        };

        document.getElementById('discard-draft').onclick = function() {
            clearLocalStorage();
            promptDiv.remove();
        };
    }

    /**
     * Get localStorage key for current post
     */
    function getLocalStorageKey() {
        if (state.postId) {
            return LOCAL_STORAGE_PREFIX + 'post_' + state.postId;
        }
        return LOCAL_STORAGE_PREFIX + 'new';
    }

    /**
     * Clear localStorage draft
     */
    function clearLocalStorage() {
        try {
            localStorage.removeItem(getLocalStorageKey());
        } catch (e) {
            // Ignore
        }
    }

    /**
     * Update status display
     */
    function updateStatus(message, statusType) {
        if (!state.messageElement || !state.indicatorElement) return;

        state.messageElement.textContent = message;

        // Remove all status classes
        state.statusElement.className = '';

        // Add appropriate class
        switch (statusType) {
            case 'saving':
                state.indicatorElement.innerHTML = '&#9673;'; // Hollow circle
                state.statusElement.className = 'autosave-saving';
                break;
            case 'saved':
                state.indicatorElement.innerHTML = '&#10003;'; // Checkmark
                state.statusElement.className = 'autosave-saved';
                break;
            case 'error':
                state.indicatorElement.innerHTML = '&#10007;'; // X mark
                state.statusElement.className = 'autosave-error';
                break;
            default:
                state.indicatorElement.innerHTML = '&#9679;'; // Filled circle
        }
    }

    // Public API
    return {
        init: init
    };
})();
