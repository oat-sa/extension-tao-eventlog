<div class="log">
    <div class="grid-row">
        <div class="col-12">
            <section class="log-browser">
                <header>
                    <h2>{{__ 'Log watcher'}}</h2>
                </header>
                {{#if cleanupNotice}}
                    <div class="feedback-info small eventlog-cleanup-notice">
                        <span class="icon-info"></span>
                        <strong>{{cleanupNotice.title}}</strong>
                        <div>{{cleanupNotice.description}}</div>
                        {{#if cleanupNotice.events}}
                            <div>{{cleanupNotice.events}}</div>
                        {{/if}}
                    </div>
                {{/if}}
                <div class="grid-row log-table-controls">
                    <div class="col-8">
                        <div class="log-table-filters"></div>
                    </div>
                    <div class="col-4">
                        <div class="log-table-actions">
                            <button class="btn-info export js-export"><span class="icon-export"></span>{{__ 'Export log entries'}}</button>
                        </div>
                    </div>
                </div>
                <div class="log-table"></div>
            </section>
        </div>
        <div class="col-5">
            <section class="event-viewer hidden">
                <div class="grid-row">
                    <div class="col-10">
                        <h3>{{__ "Event"}} #<span class="id"></span></h3>
                    </div>
                </div>

                {{#each dataTypes}}

                    <div class="grid-row">
                        <div class="col-12">
                            <strong>{{title}}</strong>
                        </div>
                    </div>
                    <div class="grid-row">
                        <div class="col-10 desc">
                            <span class="{{key}}"></span>
                        </div>
                    </div>

                {{/each}}

            </section>
        </div>
    </div>
</div>
