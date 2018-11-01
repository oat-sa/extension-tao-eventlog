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
    'ui/component',
    'ui/feedback',
    'layout/loading-bar',
    'tpl!taoEventLog/components/export/templates/layout',
    'util/url',
    'layout/logout-event',
    'jquery.fileDownload'
], function ($, _, __, component, feedback, loadingBar, layoutTpl, url, logoutEvent) {
    'use strict';

    /**
     * @param {Object} options - the component configuration
     * @returns {exporter} a new component
     */
    return function exporter(options) {
        if (!_.isPlainObject(options) || !_.isString(options.exportUrl)) {
            throw new TypeError('The exporter must be configured with exportUrl option');
        }

        return component({}, { title: __('Export') })
        .setTemplate(layoutTpl)

        // renders the component
        .on('render', function () {
            var self = this,
                $form = self.$component.find('.js-export-form');

            $form.on('submit', function (e) {
                e.preventDefault();
                loadingBar.start();

                var params = {},
                    exportUrl;

                _.each($form.serializeArray(), function(param){
                    params[param.name] = param.value;
                });

                exportUrl = url.build(options.exportUrl, params);

                $.fileDownload(exportUrl, {
                    successCallback : function () {
                        loadingBar.stop();
                        self.$component.modal('close');
                    },
                    failCallback : function (jqXHR) {
                        loadingBar.stop();
                        var response = $.parseJSON($(jqXHR).text());
                        if (response) {
                            feedback().error(new Error(response.message));
                        } else {
                            self.$component.modal('close');
                            logoutEvent();
                        }
                    }
                });
            });
        })
        .init(options);
    };
});
