(function ($) {
    'use strict';

    function getUserData(option) {
        if (!option.id || !option.element) {
            return null;
        }

        var $option = $(option.element);
        return {
            name: $option.attr('data-user-name') || option.text || '',
            email: $option.attr('data-user-email') || '',
            phone: $option.attr('data-user-phone') || '',
            avatar: $option.attr('data-user-avatar') || '',
            selected: $option.prop('selected')
        };
    }

    function createAvatar(user) {
        var initial = user.name.trim().charAt(0).toUpperCase() || 'U';
        var $avatar = $('<span>', { 'class': 'admin-user-select-avatar', 'aria-hidden': 'true' });

        if (user.avatar) {
            $avatar.append($('<img>', { src: user.avatar, alt: '' }));
        } else {
            $avatar.text(initial);
        }

        return $avatar;
    }

    function createUserResult(option) {
        var user = getUserData(option);

        if (!user) {
            return option.text;
        }

        var $card = $('<span>', { 'class': 'admin-user-select-card' });
        var $details = $('<span>', { 'class': 'admin-user-select-details' });

        $details.append($('<span>', { 'class': 'admin-user-select-name', text: user.name }));
        if (user.email) {
            $details.append($('<span>', { 'class': 'admin-user-select-email', text: user.email }));
        }
        if (user.phone) {
            $details.append($('<span>', { 'class': 'admin-user-select-phone', text: user.phone }));
        }

        $card.append(createAvatar(user), $details);
        if (user.selected) {
            $card.append($('<i>', {
                'class': 'bi bi-check-lg admin-user-select-check',
                'aria-hidden': 'true'
            }));
        }

        return $card;
    }

    function createSelectedUser(option) {
        var user = getUserData(option);

        if (!user) {
            return option.text;
        }

        return $('<span>', { 'class': 'admin-user-select-card is-selected-value' })
            .append(createAvatar(user))
            .append($('<span>', { 'class': 'admin-user-select-name', text: user.name }));
    }

    function initializeUserSelects(root) {
        $(root).find('select[data-user-select]').each(function () {
            var $select = $(this);

            if ($select.data('adminUserSelectInitialized')) {
                return;
            }

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                dropdownParent: $select.closest('.modal').length ? $select.closest('.modal') : $(document.body),
                multiple: $select.prop('multiple'),
                placeholder: $select.attr('data-placeholder') || undefined,
                dropdownCssClass: 'admin-user-select-dropdown',
                templateResult: createUserResult,
                templateSelection: $select.prop('multiple') ? undefined : createSelectedUser
            });

            $select.data('adminUserSelectInitialized', true);
        });
    }

    // Select or clear every option of a multiple user select from its action links.
    function setAllUsersSelected(selector, selected) {
        var $select = $(selector);

        if (!$select.length || !$select.prop('multiple')) {
            return;
        }

        $select.find('option:not(:disabled)').prop('selected', selected);
        $select.trigger('change');
    }

    $(document).on('click', '[data-user-select-all]', function () {
        setAllUsersSelected($(this).attr('data-user-select-all'), true);
    });

    $(document).on('click', '[data-user-select-clear]', function () {
        setAllUsersSelected($(this).attr('data-user-select-clear'), false);
    });

    $(function () {
        initializeUserSelects(document);
    });

    window.AdminUserSelect = {
        initialize: initializeUserSelects
    };
}(jQuery));
