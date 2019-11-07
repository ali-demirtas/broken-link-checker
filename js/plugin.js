// DOM Ready
$(function () {

    $("#checkLinksButton").click(function () {

        var blcLinkStats = {
            Success : 0,
            Redirect: 0,
            Error: 0
        };

        if (typeof BLC_AUTH_TOKEN === 'undefined') {
            alert("Auth Token Unavailable");
            return;
        }

        var checkLinksButtonLabel = $("#checkLinksButton").html();
        // Disable the form while scan is running
        $("#checkLinksButton").prop("disabled", true);
        $("#checkLinksButton").html("Loading...");

        // Create an AJAX Manager queue with max concurrency 10 requests
        var ajaxManager = $.manageAjax.create('ajaxManagerQueue', {
            queue: true,
            maxRequests: 10
        });

        // Iterate through each link in the table and get status
        $("#allLinksTable tbody tr").each(function () {

            $rowId = $(this).attr('id');
            $row = $('#' + $rowId);

            var link = $row.find("td:eq(3)").text();
            var params = "q=" + encodeURIComponent(link) + "&trId=" + $rowId + "&token=" + encodeURIComponent(BLC_AUTH_TOKEN);

            // In Progress
            $row.find("td:eq(0)").html('<span class="spinner-border spinner-border-sm"></span>');

            // $.ajax({
            ajaxManager.add({
                type: "get",
                url: DOMAIN_BASE + 'broken-link-checker',
                data: params,
                success: function (data) {
                    blcLinkStats[data.result.linkStatus]++;
                    updateRow(data);
                    updateStats(blcLinkStats);
                },
                error: function (data) {
                    console.log("API Failure:" + data);
                }
            });
        });

        function updateRow(data)
        {
            var successIcon = '<span class="fa fa-check-circle text-success BLCSuccess"></span>';
            var redirectIcon = '<span class="fa fa-rotate-right text-warning BLCRedirect"></span>';
            var errorIcon = '<span class="fa fa-times-circle text-danger BLCError"></span>';

            $row = $('#' + data.trId);
            $row.find("td:eq(1)").html(data.result.code);

            switch (data.result.linkStatus) {
                case "Success":
                    $row.find("td:eq(0)").html(successIcon);
                break;
                case "Redirect":
                    $row.find("td:eq(0)").html(redirectIcon);
                break;
                case "Error":
                    $row.find("td:eq(0)").html(errorIcon);
                break;
            }
        }

        function updateStats(blcLinkStats)
        {

            $("#resultsPlaceholder").html('<div class="alert alert-light border-dark mt-2">\
            <div class="custom-control custom-switch">\
              <input type="checkbox" class="custom-control-input" id="toggleSuccess" disabled checked>\
              <label class="custom-control-label" for="toggleSuccess"><span class="text-success">Success: ' + blcLinkStats.Success + '</span></label>\
            </div>\
            <div class="custom-control custom-switch">\
              <input type="checkbox" class="custom-control-input" id="toggleRedirect" disabled checked>\
              <label class="custom-control-label" for="toggleRedirect"><span class="text-warning">Redirect: ' + blcLinkStats.Redirect + '</span></label>\
            </div>\
            <div class="custom-control custom-switch">\
              <input type="checkbox" class="custom-control-input" id="toggleError" disabled checked>\
              <label class="custom-control-label" for="toggleError"><span class="text-danger">Error: ' + blcLinkStats.Error + '</span></label>\
            </div>\
            </div>');
        }

        // Runs when all Ajax requests are complete
        $(document).ajaxStop(function () {

            // Let User toggle the results
            $("#toggleSuccess").removeAttr("disabled");
            $("#toggleRedirect").removeAttr("disabled");
            $("#toggleError").removeAttr("disabled");

            // Rollback to default layout
            $("#checkLinksButton").removeAttr("disabled");
            $("#checkLinksButton").html(checkLinksButtonLabel);
        });
    });

    // Event to Show/Hide Results
    $(document).on('click', '#toggleSuccess,#toggleRedirect,#toggleError', function () {
        var resultClass = '.BLC' + this.id.replace('toggle', '');
        if (this.checked) {
            $(resultClass).parent().parent().show();
        } else {
            $(resultClass).parent().parent().hide();
        }
    });

});
