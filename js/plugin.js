// DOM Ready
$(function () {
    $("#checkLinksButton").click(function () {
        var checkedCount = 0;
        var failedCount = 0;
        $("#allLinksTable tbody tr").each(function () {

            checkedCount++;

            $row = $(this);
            var link = $row.find("td:eq(3)").text();

            $("#checkLinksButton").prop("disabled", true);
            $("#checkLinksButton").html("Loading...");

            var params = "q=" + encodeURI(link);

            // In Progress
            $row.find("td:eq(0)").html('<span class="oi oi-loop-circular text-info"></span>');

            // Disable async to prevent excess load | Deprecated: Remove later.
            $.ajax({
                type: "get",
                url: DOMAIN_BASE+'broken-link-checker',
                data: params,
                async: false,
                success: function (data) {
                    if (data.result.isUp) {
                        // Success
                        $row.find("td:eq(0)").html('<span class="oi oi-circle-check text-success"></span>');
                        $row.find("td:eq(2)").html(data.result.code);
                        refreshStats(checkedCount, failedCount);
                    } else {
                        // Fail
                        $row.find("td:eq(0)").html('<span class="oi oi-circle-x text-danger"></span>');
                        $row.find("td:eq(2)").html(data.result.code);
                        failedCount++;
                        refreshStats(checkedCount, failedCount);
                    }
                },
                error: function (data) {
                    // Fail
                    $row.find("td:eq(0)").html('<span class="oi oi-circle-x text-danger"></span>');
                    $row.find("td:eq(2)").html(0);
                    failedCount++;
                    refreshStats(checkedCount, failedCount);
                }
            });
        });

        // Sort the table
        sortTable("allLinksTable");

        // Rollback to default layout
        $("#checkLinksButton").removeAttr("disabled");
        $("#checkLinksButton").html("Check All Links");

    });

    function refreshStats(checkedCount, failedCount)
    {
        $("#resultsPlaceholder").html('<div class="alert alert-secondary mt-3">\
        Checked: ' + checkedCount + '<br>\
        <span class="text-danger">Failed: ' + failedCount + '</span>\
        </div>');
    }

});
