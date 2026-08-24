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
 * Copyright (c) 2016-2026  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

define([
    'jquery',
    'lodash',
    'i18n',
    'util/url',
    'ui/datatable',
    'tpl!taoEventLog/controller/TaoEventLog/show/layout',
    'taoEventLog/components/export/modalExporter',
    'ui/dateRange/dateRange'
], function ($, _, __, url, datatable, layoutTpl, exporter, dateRangeFactory) {
    'use strict';

    //the endpoints
    const listUrl = url.route('search', 'TaoEventLog', 'taoEventLog');

    return {

        /**
         * Controller entry point
         */
        start: function start() {
            const isFromPortal = $('.content').attr('data-is-from-portal') === '1';
            const dataTypes = [
                {key: 'event_name', title: __('Event Name')},
                {key: 'action', title: __('Action')},
                {key: 'user_id', title: __('User ID')},
                {key: 'user_login', title: __('User Login')}
            ];
            const datatableModel = [{
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
                id: 'user_login',
                label: __('User Login'),
                sortable: true,
                filterable: true
            }];

            if (!isFromPortal) {
                dataTypes.push({key: 'user_roles', title: __('User Roles')});
                datatableModel.push({
                    id: 'user_roles',
                    label: __('User Roles'),
                    sortable: true,
                    filterable: true,
                    transform: function (roles) {
                        const rolesArray = roles.split(', ');
                        const rolesCount = rolesArray.length;
                        let roleFiltered;
                        let result;

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
                });
            }

            dataTypes.push(
                {key: 'occurred', title: __('Occurred')},
                {key: 'properties', title: __('Properties')}
            );
            datatableModel.push({
                id: 'occurred',
                label: __('Occurred'),
                sortable: true
            });

            const data = {
                dataTypes: dataTypes
            };

            const $layout = $(layoutTpl(data));
            const $eventFilter = $('.log-browser .log-table-filters', $layout);
            const $eventList = $('.log-browser .log-table', $layout);
            const $eventViewer = $('.event-viewer', $layout);
            const $exportLink = $('.js-export', $layout);

            const filterRange = dateRangeFactory($eventFilter);
            let currentFilter = {
                filtercolumns: {}
            };

            const updateEventDetails = function updateEventDetails(event) {
                let key;
                let json;
                let str;
                for (key in event) {
                    if (event.hasOwnProperty(key)) {
                        if (key === 'properties') {
                            json = JSON.parse(event[key]);
                            if (json !== null && typeof json !== 'object') {
                                json = JSON.parse(json);
                            }
                            str = JSON.stringify(json, null, 2);
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

            filterRange.on('submit reset', function() {
                $eventList.datatable('options', {
                    params : {
                        periodStart : this.getStart(),
                        periodEnd : this.getEnd()
                    }
                });

                $eventList.datatable('refresh');
            });

            $exportLink.on('click', function () {
                currentFilter.filtercolumns = _.merge(currentFilter.filtercolumns, {
                    from : filterRange.getStart(),
                    to : filterRange.getEnd()
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
                model: datatableModel,

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
