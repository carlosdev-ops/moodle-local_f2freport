/**
 * Filters manager for local_f2freport
 *
 * Handles form interactions, auto-submit on filter changes,
 * and date prefilling when "upcoming only" is checked.
 *
 * @module     local_f2freport/filters_manager
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    'use strict';

    /**
     * Field IDs to monitor for auto-submit
     * @type {string[]}
     */
    var FIELD_IDS = ['coursetext', 'datefrom', 'dateto', 'futureonly', 'includewaitlist'];

    /**
     * Debounce delay in milliseconds
     * @type {number}
     */
    var DEBOUNCE_DELAY = 400;

    /**
     * Initialize the filters manager
     *
     * @param {string} formSelector Optional CSS selector for the form (default: 'form.f2f-filter-form')
     */
    var init = function(formSelector) {
        formSelector = formSelector || 'form.f2f-filter-form';
        var $form = $(formSelector);

        if ($form.length === 0) {
            return;
        }

        setupAutoSubmit($form);
        setupUpcomingDatePrefill($form);
        setupResetButton($form);
    };

    /**
     * Setup auto-submit functionality for filter fields
     *
     * @param {jQuery} $form The form element
     */
    var setupAutoSubmit = function($form) {
        var timer = null;

        /**
         * Submit form after debounce delay
         */
        var submitDebounced = function() {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(function() {
                $form.submit();
            }, DEBOUNCE_DELAY);
        };

        FIELD_IDS.forEach(function(fieldId) {
            var $field = $('#' + fieldId);
            if ($field.length === 0) {
                return;
            }

            // Special handling for coursetext: submit on Enter key
            if (fieldId === 'coursetext') {
                $field.on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $form.submit();
                    }
                });
            } else {
                // All other fields: debounced auto-submit on change
                $field.on('change', submitDebounced);
            }
        });
    };

    /**
     * Prefill start date with today when "upcoming only" is checked
     *
     * @param {jQuery} $form The form element
     */
    var setupUpcomingDatePrefill = function($form) {
        var $upcomingCheckbox = $('#futureonly');
        var $startDateField = $('#datefrom');

        if ($upcomingCheckbox.length === 0 || $startDateField.length === 0) {
            return;
        }

        $upcomingCheckbox.on('change', function() {
            if ($upcomingCheckbox.is(':checked') && !$startDateField.val()) {
                var today = new Date();
                var year = today.getFullYear();
                var month = String(today.getMonth() + 1).padStart(2, '0');
                var day = String(today.getDate()).padStart(2, '0');
                var dateString = year + '-' + month + '-' + day;

                $startDateField.val(dateString);
            }
        });
    };

    /**
     * Setup reset button to clear all fields and submit
     *
     * @param {jQuery} $form The form element
     */
    var setupResetButton = function($form) {
        var $resetBtn = $('#f2f-reset');

        if ($resetBtn.length === 0) {
            return;
        }

        $resetBtn.on('click', function(e) {
            e.preventDefault();

            // Clear all filter fields
            $('#coursetext').val('');
            $('#datefrom').val('');
            $('#dateto').val('');
            $('#futureonly').prop('checked', false);
            $('#includewaitlist').prop('checked', false);

            // Submit the cleared form
            $form.submit();
        });
    };

    return {
        init: init
    };
});
