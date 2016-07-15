/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 * @author Ivan Klimchuk <klimchuk@1pt.com>
 */

define([
    'jquery',
    'lodash',
    'i18n',
    'ui/feedback',
    'taoEventLog/components/export/exporter',
    'ui/modal'
], function ($, _, __, feedback, exporter) {
    'use strict';

    /**
     * Wrap the exporter in a modal popup
     *
     * @param {Object} options - the exporter's configuration
     * @param {String} exportUrl
     */
    return function modalExporter(options) {

        var feedbackOptions = {
            timeout: {
                warning: 8000
            },
            encodeHtml: false
        };

        return exporter(options)
            .on('warning', function (err) {
                feedback().warning(err, feedbackOptions);
            })
            .on('error', function (err) {
                feedback().error(err, feedbackOptions);
            })
            .on('render', function () {
                var self = this;
                this.$component
                    .addClass('modal')
                    .on('close.modal', function () {
                        self.$component.modal('close');
                    })
                    .modal({ width: 600 });
            })
            .render('body');
    };
});
