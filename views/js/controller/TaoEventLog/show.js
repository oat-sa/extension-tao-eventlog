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
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

define([
    'jquery',
    'i18n',
    'helpers',
    'ui/datatable',
    'tpl!taoEventLog/controller/TaoEventLog/show/layout'
], function ($, __, helpers, datatable, layoutTpl) {
    'use strict';

    //the endpoints
    var listUrl = helpers._url('search', 'TaoEventLog', 'taoEventLog');

    return {

        /**
         * Controller entry point
         */
        start: function start() {

            var $layout = $(layoutTpl());
            var $eventList = $('.log-browser .log-table', $layout);
            var $eventViewer = $('.event-viewer', $layout);
            
            var updateEventDetails = function updateEventDetails(event) {
                for (var k in event) {
                    if (event.hasOwnProperty(k)) {
                        if (k == 'properties') {
                            var json = JSON.parse(JSON.parse(event[k]));
                            var str = JSON.stringify(json, undefined, 2);
                            $('.' + k, $eventViewer).html(
                                '<pre>' + str + '</pre>'
                            );
                        } else {
                            $('.' + k, $eventViewer).html(event[k]);
                        }
                    }
                }
            };

            //append the layout to the current view container
            $('.content').append($layout);
            
            //set up the student list
            $eventList.datatable({
                url: listUrl,
                filter: true,
                rowSelection: true,
                model: [{
                    id: 'event_name',
                    label: __('Event Name'),
                    sortable: true,
                    filterable: true
                },{
                    id: 'action',
                    label: __('Action'),
                    sortable: true,
                    filterable: true
                },{
                    id: 'user_id',
                    label: __('User ID'),
                    sortable: true,
                    filterable: true
                }, {
                    id: 'user_roles',
                    label: __('User Roles'),
                    sortable: true,
                    filterable: true,
                    transform: function(roles) {
                        return roles ? roles.split(',').join('<br>') : '';
                    }
                }, {
                    id: 'occurred',
                    label: __('Occurred'),
                    sortable: true,
                    filterable: true
                }],
                
                listeners: {
                    /**
                     * When a row is selected, we update the student viewer
                     */
                    selected: function selectRow(e, event) {
                        updateEventDetails(event);

                        //the 1st time it comes hidden
                        $eventViewer.removeClass('hidden');
                    },

                    /**
                     * Set pagination to start position when using filtering
                     * @param e
                     * @param options
                     */
                    filter: function filtered(e, options) {
                        options.page = 1;
                    }

                }
            }).on('create.datatable', function () {
                // the page is now ready
                $layout.trigger('dispatched');
            });
        }
    };
});
