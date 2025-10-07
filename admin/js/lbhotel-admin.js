(function ($) {
    'use strict';

    $(function () {
        var $categoryFields = $('.lbhotel-category-field');

        if (!$categoryFields.length) {
            return;
        }

        var $taxonomyInputs = $('#lbhotel_place_categorychecklist input[type="checkbox"], #taxonomy-lbhotel_place_category input[type="checkbox"]');
        var $taxonomySelect = $('#lbhotel_place_category');

        function collectSelectedTermIds() {
            var ids = [];

            $taxonomyInputs.filter(':checked').each(function () {
                var value = $(this).val();
                if (value) {
                    ids.push(String(value));
                }
            });

            if ($taxonomySelect.length) {
                var selectValue = $taxonomySelect.val();
                if (selectValue) {
                    ids.push(String(selectValue));
                }
            }

            return ids;
        }

        function syncCategoryFields() {
            var selectedIds = collectSelectedTermIds();

            if (!selectedIds.length) {
                $categoryFields.removeClass('is-active').hide();
                return;
            }

            $categoryFields.each(function () {
                var $field = $(this);
                var termId = String($field.data('term-id') || '');
                var slug = String($field.data('category') || '');
                var isActive = false;

                if (termId && selectedIds.indexOf(termId) !== -1) {
                    isActive = true;
                }

                if (!isActive && slug) {
                    if (selectedIds.indexOf(slug) !== -1) {
                        isActive = true;
                    }
                }

                if (isActive) {
                    if (!$field.hasClass('is-active')) {
                        $field.stop(true, true).slideDown(150);
                    }
                    $field.addClass('is-active');
                } else {
                    if ($field.hasClass('is-active')) {
                        $field.stop(true, true).slideUp(150);
                    }
                    $field.removeClass('is-active');
                }
            });
        }

        syncCategoryFields();

        $taxonomyInputs.on('change', syncCategoryFields);
        $taxonomySelect.on('change', syncCategoryFields);
    });
})(jQuery);
