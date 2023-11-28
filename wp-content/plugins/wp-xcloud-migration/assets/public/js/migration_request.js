jQuery(function ($) {
    if ($('canvas').hasClass('loader')) {
        var loader = $('.loader').ClassyLoader({
            start: 'top',
            width: 120,
            height: 120,
            percentage: migration?.percentage === null ? 0 : migration?.percentage,
            speed: 1,
            diameter: 50,
            showText: true,
            fontSize: '30px',
            roundedLine: true,
            fontColor: '#33ba7c',
            lineColor: '#33ba7c',
            remainingLineColor: '#cce7df',
            lineWidth: 15
        });
    }

    $('.blinkdot').dotAnimation({
        speed: 300,
        dotElement: '.',
        numDots: 3
    });

    let started_item = $('#started_item');
    let intervalId;

    window.last_percentage = migration?.percentage === null ? 0 : migration?.percentage;

    function updateData() {
        jQuery.get(migration.request_url, function (data) {

            if (($('section').hasClass('xc-migrate-1') && data?.percentage !== null) ||
                data.percentage === 100 || (data.data === null && $('section').hasClass('xc-migrate-2'))) {
                window.location.reload();
            }

            if(data.data === undefined || data.data === null) {
                return;
            }

            started_item.find('[data-id="' + (data.running_task.task_index_id - 1) + '"]').removeClass('started');
            started_item.find('[data-id="' + (data.running_task.task_index_id - 1) + '"]').addClass('done');
            started_item.find('[data-task="' + (data.running_task.task_index_id - 1) + '"]').find('.status').text('');
            started_item.find('[data-task="' + (data.running_task.task_index_id - 1) + '"]').find('.blinkdot').removeClass('show');

            started_item.find('[data-id="' + data.running_task.task_index_id + '"]').addClass('started');
            started_item.find('[data-task="' + data.running_task.task_index_id + '"]').find('.status').text(data.running_task.tasks[data.data.status]);
            started_item.find('[data-task="' + data.running_task.task_index_id + '"]').find('.blinkdot').addClass('show');

            if ($('canvas').hasClass('loader') && window.last_percentage.toString() !== data?.percentage.toString()) {
                loader.setPercent(data.percentage).draw();
                window.last_percentage = data.percentage;
            }
        });
    }

    function checkVisibility() {
        if (!document.hidden) {
            intervalId = setInterval(updateData, 5000);
        } else {
            clearInterval(intervalId);
        }
    }

    document.addEventListener("visibilitychange", checkVisibility);
    checkVisibility();
});
