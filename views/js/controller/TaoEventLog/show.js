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
    'util/url',
    'ui/datatable',
    'tpl!taoEventLog/controller/TaoEventLog/show/layout',
    'taoEventLog/components/export/modalExporter',
    'ui/dateRange'
], function ($, __, url, datatable, layoutTpl, exporter, dateRangeFactory) {
    'use strict';

    //the endpoints
    var listUrl = url.route('search', 'TaoEventLog', 'taoEventLog');

    return {

        /**
         * Controller entry point
         */
        start: function start() {

            var data = {
                dataTypes: [
                    {key: 'event_name', title: __('Event Name')},
                    {key: 'action', title: __('Action')},
                    {key: 'user_id', title: __('User ID')},
                    {key: 'user_roles', title: __('User Roles')},
                    {key: 'occurred', title: __('Occurred')},
                    {key: 'properties', title: __('Properties')}
                ]
            };

            var $layout = $(layoutTpl(data));
            var $eventFilter = $('.log-browser .log-table-filters', $layout);
            var $eventList = $('.log-browser .log-table', $layout);
            var $eventViewer = $('.event-viewer', $layout);
            var $exportLink = $('.js-export', $layout);

            var $filterRange = dateRangeFactory({
                pickerType: 'datetimepicker',
                renderTo: $eventFilter,
                pickerConfig: {
                    // configurations from lib/jquery.timePicker.js
                    dateFormat: 'yy-mm-dd',
                    timeFormat: 'HH:mm:ss'
                }
            });
            var currentFilter = {
                filtercolumns: {}
            };

            var updateEventDetails = function updateEventDetails(event) {
                var key, json, str;
                for (key in event) {
                    if (event.hasOwnProperty(key)) {
                        if (key === 'properties') {
                            json = JSON.parse(event[key]);
                            if (json !== null && typeof json !== 'object') {
                                json = JSON.parse(json);
                            }
                            str = JSON.stringify(json, undefined, 2);
                            $('.' + key, $eventViewer).html(
                                '<pre>' + str + '</pre>'
                            );
                        } else if (key === 'user_roles') {
                            $('.' + key, $eventViewer).html(event['user_roles'].split(',').join('<br>'));
                        } else {
                            $('.' + key, $eventViewer).html(event[key]);
                        }
                    }
                }
            };

            $filterRange.on('submit', function() {
                $eventList.datatable('options', {
                    params: {
                        periodStart: $filterRange.getStart(),
                        periodEnd: $filterRange.getEnd()
                    }
                });

                $eventList.datatable('refresh');
            });

            $exportLink.on('click', function () {
                currentFilter.filtercolumns = _.merge(currentFilter.filtercolumns, {
                    from: $filterRange.getStart(),
                    to: $filterRange.getEnd()
                });

                exporter({
                    title: __('Export Log Entries'),
                    exportUrl: url.route('export', 'TaoEventLog', 'taoEventLog', currentFilter)
                });
            });

            //append the layout to the current view container
            $('.content').append($layout);

            //set up the student list
            $eventList.datatable({
                url: listUrl,
                sortby: 'occurred',
                sortorder: 'desc',
                filter: true,
                rowSelection: true,
                filterStrategy: 'multiple',
                model: [{
                    id: 'identifier',
                    label: __('ID'),
                    transform: function (id, row) {
                        return row.raw.id;
                    }
                }, {
                    id: 'event_name',
                    label: __('Event Name'),
                    sortable: true,
                    filterable: true
                }, {
                    id: 'action',
                    label: __('Action'),
                    sortable: true,
                    filterable: true
                }, {
                    id: 'user_id',
                    label: __('User ID'),
                    sortable: true,
                    filterable: true
                }, {
                    id: 'user_roles',
                    label: __('User Roles'),
                    sortable: true,
                    filterable: true,
                    transform: function (roles) {
                        var rolesArray = roles.split(', ');
                        var rolesCount = rolesArray.length;
                        var roleFiltered;
                        var result;

                        if(rolesCount > 1) {
                            if(currentFilter.filtercolumns.user_roles) {
                                roleFiltered = _.find(rolesArray, function (item) {
                                    return item.toLowerCase().indexOf(currentFilter.filtercolumns.user_roles.toLowerCase()) > -1;
                                });
                                if(roleFiltered) {
                                    result = __('%s and %s roles', roleFiltered, (rolesCount - 1));
                                } else {
                                    result = __('%s roles', rolesCount);
                                }
                            } else {
                                result = __('%s roles', rolesCount);
                            }
                        } else if(rolesCount === 1){
                            result = roles;
                        }

                        return result;
                    }
                }, {
                    id: 'occurred',
                    label: __('Occurred'),
                    sortable: true
                }],

                listeners: {
                    /**
                     * When a row is selected, we update the student viewer
                     */
                    selected: function selectRow(e, event) {

                        updateEventDetails(event.raw);

                        //the 1st time it comes hidden
                        if($eventViewer.is(".hidden")) {
                            $layout.find(".col-12").removeClass("col-12").addClass("col-7");
                            $eventViewer.removeClass('hidden');
                        }
                    },

                    /**
                     * Set pagination to start position when using filtering
                     * @param e
                     * @param options
                     */
                    filter: function filtered(e, options) {
                        options.page = 1;
                    },

                    /**
                     * Grab context of current query (filter, sort, ...)
                     * @param e
                     * @param ajaxConfig
                     */
                    query: function onQuery(e, ajaxConfig) {
                        currentFilter = _.pick(ajaxConfig && ajaxConfig.data, ['filterquery', 'filtercolumns', 'sortby', 'sortorder', 'sorttype']);
                    }
                }
            });
        }
    };
});
