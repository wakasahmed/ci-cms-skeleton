(function (window, document) {
    'use strict';

    if (typeof window.Sortable !== 'function') {
        return;
    }

    var form = document.getElementById('menu-manager-form');
    if (!form) {
        return;
    }

    var GROUP_NAME = 'menu-manager';
    var payloadField = document.getElementById('menu-manager-payload');
    var saveButton = document.getElementById('menu-manager-save');
    var resetButton = document.getElementById('menu-manager-reset');
    var announcer = document.getElementById('menu-manager-announcer');
    var maxDepth = parseInt(form.getAttribute('data-menu-max-depth'), 10) || 1;
    var supportsNesting = maxDepth > 1;
    var menuLabel = form.getAttribute('data-menu-label') || 'menu';
    var isDirty = false;
    var isSubmitting = false;

    function itemLabel(item)
    {
        var label = item.querySelector(':scope > .menu-manager-item-row .menu-manager-item-en');
        return label ? label.textContent : 'item';
    }

    function announce(message)
    {
        if (announcer) {
            announcer.textContent = message;
        }
    }

    function setHidden(item, action, visible)
    {
        var button = item.querySelector(':scope > .menu-manager-item-row [data-menu-action="'+action+'"]');
        if (button) {
            button.hidden = !visible;
        }
    }

    function setDisabled(item, action, disabled)
    {
        var button = item.querySelector(':scope > .menu-manager-item-row [data-menu-action="'+action+'"]');
        if (button) {
            button.disabled = disabled;
        }
    }

    function childrenList(item)
    {
        return item.querySelector(':scope > .menu-manager-children');
    }

    function ensureChildrenList(item)
    {
        var list = childrenList(item);
        if (list) {
            return list;
        }
        list = document.createElement('ul');
        list.className = 'menu-manager-list menu-manager-children';
        list.setAttribute('data-menu-list', 'active-children');
        list.setAttribute('aria-label', 'Items nested under '+itemLabel(item));
        item.appendChild(list);
        registerSortable(list);
        return list;
    }

    function removeChildrenList(item)
    {
        var list = childrenList(item);
        if (list) {
            list.remove();
        }
    }

    function flattenChildrenToAvailable(item)
    {
        var list = childrenList(item);
        if (!list) {
            return;
        }
        var availableList = document.querySelector('[data-menu-list="available"]');
        var children = Array.prototype.slice.call(list.children);
        children.forEach(function (child) {
            removeChildrenList(child);
            availableList.appendChild(child);
        });
        list.remove();
    }

    function syncAfterMove(item)
    {
        var listType = item.parentElement.getAttribute('data-menu-list');
        if (listType === 'available') {
            flattenChildrenToAvailable(item);
        } else if (listType === 'active-root') {
            if (supportsNesting) {
                ensureChildrenList(item);
            }
        } else if (listType === 'active-children') {
            removeChildrenList(item);
        }
    }

    function refreshItemControls(item)
    {
        var listType = item.parentElement ? item.parentElement.getAttribute('data-menu-list') : null;
        var isActive = listType === 'active-root' || listType === 'active-children';
        var isChild = listType === 'active-children';

        item.setAttribute('data-menu-status', isActive ? 'active' : 'available');

        setHidden(item, 'indent', isActive && !isChild);
        setHidden(item, 'outdent', isActive && isChild);
        setHidden(item, 'remove', isActive);
        setHidden(item, 'add', !isActive);

        var siblings = Array.prototype.slice.call(item.parentElement.children);
        var index = siblings.indexOf(item);
        setDisabled(item, 'move-up', index <= 0);
        setDisabled(item, 'move-down', index === -1 || index >= siblings.length - 1);
        setDisabled(item, 'indent', index <= 0);
    }

    function refreshAll()
    {
        document.querySelectorAll('.menu-manager-item').forEach(refreshItemControls);
        updateCounts();
    }

    function setCount(elementId, count)
    {
        var element = document.getElementById(elementId);
        if (element) {
            element.textContent = count+' item'+(count === 1 ? '' : 's');
        }
    }

    function updateCounts()
    {
        var activeCount = document.querySelectorAll('.menu-manager-item[data-menu-status="active"]').length;
        var availableCount = document.querySelectorAll('.menu-manager-item[data-menu-status="available"]').length;

        setCount('menu-manager-active-count', activeCount);
        setCount('menu-manager-available-count', availableCount);

        var activeEmpty = document.getElementById('menu-manager-active-empty');
        if (activeEmpty) {
            activeEmpty.hidden = activeCount > 0;
        }
        var availableEmpty = document.getElementById('menu-manager-available-empty');
        if (availableEmpty) {
            availableEmpty.hidden = availableCount > 0;
        }
    }

    function readList(list, withChildren)
    {
        return Array.prototype.slice.call(list.children).map(function (item) {
            var node = {page_id: parseInt(item.getAttribute('data-page-id'), 10)};
            if (withChildren) {
                var list = childrenList(item);
                node.children = list ? readList(list, false) : [];
            }
            return node;
        });
    }

    function serialize()
    {
        var activeList = document.querySelector('[data-menu-list="active-root"]');
        var availableList = document.querySelector('[data-menu-list="available"]');
        return JSON.stringify({
            active: readList(activeList, true),
            available: readList(availableList, false)
        });
    }

    function markDirty()
    {
        isDirty = true;
        if (saveButton) {
            saveButton.disabled = false;
        }
        if (resetButton) {
            resetButton.disabled = false;
        }
    }

    function afterMutation(message)
    {
        refreshAll();
        payloadField.value = serialize();
        markDirty();
        announce(message);
    }

    function moveUp(item)
    {
        var previous = item.previousElementSibling;
        if (!previous) {
            return;
        }
        item.parentElement.insertBefore(item, previous);
        afterMutation('Moved '+itemLabel(item)+' up.');
    }

    function moveDown(item)
    {
        var next = item.nextElementSibling;
        if (!next) {
            return;
        }
        item.parentElement.insertBefore(next, item);
        afterMutation('Moved '+itemLabel(item)+' down.');
    }

    function indent(item)
    {
        if (!supportsNesting) {
            return;
        }
        var previous = item.previousElementSibling;
        if (!previous) {
            return;
        }
        flattenChildrenToAvailable(item);
        var target = ensureChildrenList(previous);
        target.appendChild(item);
        syncAfterMove(item);
        afterMutation('Nested '+itemLabel(item)+' under '+itemLabel(previous)+'.');
    }

    function outdent(item)
    {
        var parentItem = item.parentElement.closest('.menu-manager-item');
        if (!parentItem) {
            return;
        }
        var rootList = parentItem.parentElement;
        rootList.insertBefore(item, parentItem.nextElementSibling);
        syncAfterMove(item);
        afterMutation('Moved '+itemLabel(item)+' to the top level.');
    }

    function removeFromMenu(item)
    {
        var availableList = document.querySelector('[data-menu-list="available"]');
        flattenChildrenToAvailable(item);
        availableList.appendChild(item);
        syncAfterMove(item);
        afterMutation('Removed '+itemLabel(item)+' from the '+menuLabel+'.');
    }

    function addToMenu(item)
    {
        var rootList = document.querySelector('[data-menu-list="active-root"]');
        rootList.appendChild(item);
        syncAfterMove(item);
        afterMutation('Added '+itemLabel(item)+' to the '+menuLabel+'.');
    }

    function handleMove(event)
    {
        if (event.to.getAttribute('data-menu-list') === 'active-children') {
            var list = childrenList(event.dragged);
            if (list && list.children.length > 0) {
                return false;
            }
        }
        return true;
    }

    function handleEnd(event)
    {
        syncAfterMove(event.item);
        afterMutation('Moved '+itemLabel(event.item)+'.');
    }

    function registerSortable(list)
    {
        Sortable.create(list, {
            group: GROUP_NAME,
            handle: '.menu-manager-drag-handle',
            animation: 150,
            delay: 150,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            ghostClass: 'menu-manager-ghost',
            chosenClass: 'menu-manager-chosen',
            dragClass: 'menu-manager-dragging',
            fallbackOnBody: true,
            forceFallback: true,
            revertOnSpill: true,
            onMove: handleMove,
            onEnd: handleEnd
        });
    }

    function initSortableLists()
    {
        document.querySelectorAll('[data-menu-list]').forEach(registerSortable);
    }

    function initControls()
    {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.menu-manager-control');
            if (!button || button.disabled || button.hidden) {
                return;
            }
            var item = button.closest('.menu-manager-item');
            if (!item) {
                return;
            }
            switch (button.getAttribute('data-menu-action')) {
                case 'move-up': moveUp(item); break;
                case 'move-down': moveDown(item); break;
                case 'indent': indent(item); break;
                case 'outdent': outdent(item); break;
                case 'remove': removeFromMenu(item); break;
                case 'add': addToMenu(item); break;
            }
        });
    }

    function initReset()
    {
        if (!resetButton) {
            return;
        }
        var modalElement = document.getElementById('menu-manager-reset-modal');
        var confirmButton = document.getElementById('menu-manager-reset-confirm');

        function discardChanges()
        {
            isDirty = false;
            window.location.reload();
        }

        resetButton.addEventListener('click', function () {
            if (!isDirty) {
                return;
            }
            if (modalElement && window.AdminUI) {
                window.AdminUI.showModal('menu-manager-reset-modal');
            } else if (window.confirm('Discard unsaved changes and restore the last saved '+menuLabel+' arrangement?')) {
                discardChanges();
            }
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', discardChanges);
        }
    }

    function initSubmit()
    {
        form.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }
            payloadField.value = serialize();
            isSubmitting = true;
            isDirty = false;
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
            }
        });
    }

    initSortableLists();
    initControls();
    initReset();
    initSubmit();
    refreshAll();
    payloadField.value = serialize();
}(window, document));
