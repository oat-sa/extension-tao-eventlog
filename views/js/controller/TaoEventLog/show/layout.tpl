<header class="section-header flex-container-full">
    <h2>{{__ 'Log watcher'}}</h2>
</header>
<div class="log">
    <section class="log-browser">
        <div class="filters"></div>
        <div class="log-table"></div>
    </section>
    <section class="event-viewer hidden">
        
        <h2>{{__ "Event"}} #<span class="id"></span></h2>
        
        <ul class="list">
            <li><strong>{{__ "Event Name"}}</strong>: <span class="event_name"></span></li>
            <li><strong>{{__ "Action"}}</strong>: <span class="action"></span></li>
            <li><strong>{{__ "User ID"}}</strong>: <span class="user_id"></span></li>
            <li><strong>{{__ "User Roles"}}</strong>: <span class="user_roles"></span></li>
            <li><strong>{{__ "Occurred"}}</strong>: <span class="occurred"></span></li>
        </ul>

        <p><strong>{{__ "Properties"}}</strong>:</p>
        <div class="code properties"></div>
    </section>
</div>
