<div class="mp-export-modal">
    <h1>{{title}}</h1>
    <form class="grid-row js-export-form">
        <div class="col-12">
            <h2>{{__ 'CSV Options'}}</h2>
            <div class="grid-row">
                <label class="col-5" for="field_delimiter">{{__ 'Field delimiter'}}</label>
                <input class="col-7" name="field_delimiter" id="field_delimiter" size="2" value=";" type="text">
            </div>
            <div class="grid-row">
                <label class="col-5" for="field_encloser">{{__ 'Field encloser'}}</label>
                <input class="col-7" name="field_encloser" size="2" value='"' type="text">
            </div>
            <div class="grid-row">
                <label class="col-5" for="multi_values_delimiter">{{__ 'Multiple values delimiter'}}</label>
                <input class="col-7" name="multi_values_delimiter" size="2" value='|' type="text">
            </div>
            <label class="grid-row">
                <div class="col-5">{{__ 'First row column names'}}</div>
                <div class="col-7">
                    <input type="checkbox" name="first_row_column_names" checked>
                    <span class="icon-checkbox"></span>
                </div>
            </label>
            <button type="submit" class="btn-success"><span class="icon-upload"></span>{{__ 'Export'}}</button>
        </div>
    </form>
</div>
