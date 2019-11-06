// DOM Ready
$(function () {
    $("#checkLinksButton").click(function () {

        if (typeof BLC_AUTH_TOKEN === 'undefined') {
            alert("Auth Token Unavailable");
            return;
        }

        var checkLinksButtonLabel = $("#checkLinksButton").html();

        // Create an AJAX Manager queue with max concurrency 10 requests
        var ajaxManager = $.manageAjax.create('ajaxManagerQueue', {
            queue: true,
            maxRequests: 10
        });

        $("#checkLinksButton").prop("disabled", true);
        $("#checkLinksButton").html("Loading...");

        var successCount = 0;
        var failedCount = 0;

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
                    if (data.result.isUp) {
                        // Success
                        successCount++;
                        updateRow(data);
                        refreshStats(successCount, failedCount);
                    } else {
                        // Fail
                        failedCount++;
                        updateRow(data);
                        refreshStats(successCount, failedCount);
                    }
                },
                error: function (data) {
                    // Fail
                    failedCount++;
                    updateRow(JSON.parse(data.responseText));
                    refreshStats(successCount, failedCount);
                }
            });
        });

        function updateRow(data)
        {
            var redirectIcon = '<span class="fa fa-rotate-right text-warning"></span>';
            var passIcon = '<span class="fa fa-check-circle text-success"></span>';
            var failIcon = '<span class="fa fa-times-circle text-danger"></span>';
            if (data.error) {
                var isUp = false;
                var httpCode = 'BAD REQ';
            } else {
                var isUp = (data.result.isUp === undefined) ? false : data.result.isUp;
                var httpCode = (data.result.code === undefined) ? 'BAD REQ' : data.result.code;
            }
            $row = $('#' + data.trId);
            var isRedirect = ((httpCode >= 300) && (httpCode <= 310)) ? true : false;
            $row.find("td:eq(2)").html(httpCode);

            if (isRedirect) {
                $row.find("td:eq(0)").html(redirectIcon);
            } else if (isUp) {
                $row.find("td:eq(0)").html(passIcon);
            } else {
                $row.find("td:eq(0)").html(failIcon);
            }
        }

        // Runs when all Ajax requests are complete
        $(document).ajaxStop(function () {

            // TODO: Have to sort table here or call functions that hide 2XX and 3XX

            // Rollback to default layout
            $("#checkLinksButton").removeAttr("disabled");
            $("#checkLinksButton").html(checkLinksButtonLabel);
        });
    });

    function refreshStats(successCount, failedCount)
    {
        $("#resultsPlaceholder").html('<div class="alert alert-secondary mt-3">\
        Success: ' + successCount + '<br>\
        <span class="text-danger">Failed: ' + failedCount + '</span>\
        </div>');
    }

});
