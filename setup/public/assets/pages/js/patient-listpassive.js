$(document).ready(function () {
    $('input[name="startDate"]').on('changeDate', function (selected) {
        var minDate = new Date(selected.date.valueOf());
        $('input[name="endDate"]').datepicker('setStartDate', minDate);
    });

    $('input[name="endDate"]').on('changeDate', function (selected) {
        var maxDate = new Date(selected.date.valueOf());
        $('input[name="startDate"]').datepicker('setEndDate', maxDate);
    });
});
