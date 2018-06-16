

function refreshuserlist(textobj) {

    params = "filter=" + textobj.value;

    var url = M.cfg.wwwroot + "/admin/tool/mnetusers/ajax/get_users.php?" + params;

    $.get(url, function(data, textStatus) {

        select = document.getElementById('id_users');

        // Clear the old options
        select.options.length = 0;

        // Load the new options
        index = 0;

        options = $.parseJSON(data);

        for (name in options) {
            select.options[index] = new Option(options[name], name);
            index++;
        }

    }, 'html');
}
