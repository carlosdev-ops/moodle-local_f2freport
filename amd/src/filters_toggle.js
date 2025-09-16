/**
 * Filters toggle functionality for local_f2freport
 *
 * This module handles the progressive disclosure of date inputs
 * when the corresponding "Activer" checkboxes are toggled.
 *
 * @module     local_f2freport/filters_toggle
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    'use strict';

    /**
     * Initialize the date toggle functionality
     */
    var init = function() {

        // Handle date toggle checkboxes
        $('.date-toggle').on('change', function() {
            var checkbox = $(this);
            var targetId = checkbox.data('target');
            var target = $('#' + targetId);

            if (checkbox.is(':checked')) {
                target.removeClass('d-none');
                checkbox.attr('aria-expanded', 'true');
                // Focus on first select when expanding
                target.find('select:first').focus();
            } else {
                target.addClass('d-none');
                checkbox.attr('aria-expanded', 'false');
                // Clear the date inputs when hiding
                target.find('select').val('');
            }
        });

        // Basic client-side date validation
        $('select[name*="[day]"], select[name*="[month]"], select[name*="[year]"]').on('change', function() {
            validateDateRange();
        });

        // Validate date range
        function validateDateRange() {
            var fromDay = $('select[name="datefrom[day]"]').val();
            var fromMonth = $('select[name="datefrom[month]"]').val();
            var fromYear = $('select[name="datefrom[year]"]').val();

            var toDay = $('select[name="dateto[day]"]').val();
            var toMonth = $('select[name="dateto[month]"]').val();
            var toYear = $('select[name="dateto[year]"]').val();

            // Only validate if both dates are complete
            if (fromDay && fromMonth && fromYear && toDay && toMonth && toYear) {
                var fromDate = new Date(fromYear, fromMonth - 1, fromDay);
                var toDate = new Date(toYear, toMonth - 1, toDay);

                var warning = $('#date-range-warning');

                if (fromDate > toDate) {
                    if (warning.length === 0) {
                        $('.local-f2freport-filters .card-body').prepend(
                            '<div id="date-range-warning" class="alert alert-warning alert-dismissible" role="alert">' +
                            'Warning: Start date is after end date. The dates will be automatically swapped when you submit.' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span></button>' +
                            '</div>'
                        );
                    }
                } else {
                    warning.remove();
                }
            }
        }

        // Initialize on page load - hide unchecked date inputs
        $('.date-toggle').each(function() {
            var checkbox = $(this);
            var targetId = checkbox.data('target');
            var target = $('#' + targetId);

            if (!checkbox.is(':checked')) {
                target.addClass('d-none');
                checkbox.attr('aria-expanded', 'false');
            } else {
                target.removeClass('d-none');
                checkbox.attr('aria-expanded', 'true');
            }
        });
    };

    return {
        init: init
    };
});