<div class="log">
    <section class="log-browser">

        <form class="search-events">
            <div class="grid-row">
                <div class="col-3">
                    <label for="term">Full text search:</label>
                </div>
                <div class="col-6">
                    <input type="text" placeholder="String for search" id="term">
                </div>
            </div>
            <div class="grid-row">
                <div class="col-3">
                    <label for="from">Date range:</label>
                </div>
                <div class="col-6">
                    <input type="text" id="from" class="from-time" placeholder="date-from">
                    <input type="text" placeholder="date-to" class="to-time">
                </div>
            </div>
            <div class="grid-row">
                <div class="col-3">
                    <label for="user_id">User Identifier</label>
                </div>
                <div class="col-6">
                    <input type="text" name="userId" placeholder="User identifier">
                </div>
            </div>
            <div class="grid-row">
                <div class="col-3">
                    <label for="ip">IP address</label>
                </div>
                <div class="col-6">
                    <input type="text" name="ip" placeholder="User IP address" id="ip">
                </div>
            </div>
            <button class="btn-info">Find</button>
        </form>

        <div class="filters"></div>
        <div class="log-table"></div>
    </section>
    <section class="event-viewer hidden">
        <ul class="list">
            <li><b>User identifier</b>: <span class="user_id"></span></li>
            <li><b>Name</b>: <span class="name"></span></li>
            <li><b>IP address</b>: <span class="ip"></span></li>
            <li><b>Date</b>: <span class="time"></span></li>
            <li><b>Event</b>: <span class="event"></span></li>
        </ul>

        <p><b>Description</b>:</p>
        <div class="code desc"></div>
    </section>
</div>
