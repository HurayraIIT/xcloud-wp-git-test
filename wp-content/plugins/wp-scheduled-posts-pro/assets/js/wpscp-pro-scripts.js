jQuery(document).ready(function ($) {
    // metabox checkbox on/off
    $('#ignoresocialshare').click(function () {
        if ($(this).is(':checked')) {
            $('#facebokshare').attr('checked', 'checked')
            $('#twittershare').attr('checked', 'checked')
        } else {
            $('#facebokshare').removeAttr('checked')
            $('#twittershare').removeAttr('checked')
        }
    })

    function wpscpProMonthName(monthNumber = 0) {
        var allMonthName = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
        ]
        return allMonthName[monthNumber]
    }
    // insert selectbox or checkbox
    if (jQuery('#autoManualScheduleSelectBox').length) {
        wpscpProAutoManualAdminOptionBox()
    }
    function wpscpProAutoManualAdminOptionBox() {
        var options = wpscp_pro_ajax_object
        var scheduledBox = jQuery('#autoManualScheduleSelectBox')
        if (
            options.activeScheduleSystem == 'manual_schedule' &&
            options.schedule.length != []
        ) {
            var selectBoxMarkup = ''
            selectBoxMarkup +=
                '<p><label for="manualscheduleselectbox">' +
                options.PanelTitle +
                '</label></p>'
            selectBoxMarkup +=
                '<select name="wpscp-manual-schedule-date" id="manualscheduleselectbox">'
            selectBoxMarkup +=
                '<option value="">Select a schedule time</option>'
            for (var [key, value] of Object.entries(options.schedule)) {
                selectBoxMarkup +=
                    '<option value="' +
                    value.date +
                    '">' +
                    value.label +
                    '</option>'
            }
            selectBoxMarkup += '</select>'
            scheduledBox.append(selectBoxMarkup)
        } else if (
            options.activeScheduleSystem == 'auto_schedule' &&
            options.auto_date
        ) {
            var checkBoxMarkup = ''
            checkBoxMarkup +=
                '<p><label for="autoschedulecheckbox">' +
                options.PanelTitle +
                '</label></p>'
            checkBoxMarkup +=
                '<input type="checkbox" id="autoschedulecheckbox" value="' +
                options.auto_date.date +
                '" />' +
                options.auto_date.label
            scheduledBox.append(checkBoxMarkup)
        }
    }
    // for manual schedule
    wpscpProManualSelectBox()
    function wpscpProManualSelectBox() {
        var oldYear = jQuery('#aa').val()
        var oldMonth = jQuery('#mm').val()
        var oldday = jQuery('#jj').val()
        var oldhour = jQuery('#hh').val()
        var oldminute = jQuery('#mn').val()
        jQuery(document).on('change', '#manualscheduleselectbox', function (e) {
            if (this.value !== '') {
                var selectedDate = this.value
                var dateTimeArray = selectedDate.split(' ')
                var dateArray = dateTimeArray[0].split('-')
                var timeArray = dateTimeArray[1].split(':')
                // set year
                jQuery('#aa').val(dateArray[0])
                // set month
                jQuery('#mm').val(dateArray[1])
                // set day
                jQuery('#jj').val(dateArray[2])
                // set hour
                jQuery('#hh').val(timeArray[0])
                // set minute
                jQuery('#mn').val(timeArray[1])
                // change publish button text
                jQuery('#publish').val('Schedule')
            } else {
                // set year
                jQuery('#aa').val(oldYear)
                // set month
                jQuery('#mm').val(oldMonth)
                // set day
                jQuery('#jj').val(oldday)
                // set hour
                jQuery('#hh').val(oldhour)
                // set minute
                jQuery('#mn').val(oldminute)
                // change publish button text
                jQuery('#publish').val('Publish')
            }
        })
    }
    // for auto schedule
    wpscpProAutoScheduleCheckBox()
    function wpscpProAutoScheduleCheckBox() {
        var oldYear = jQuery('#aa').val()
        var oldMonth = jQuery('#mm').val()
        var oldday = jQuery('#jj').val()
        var oldhour = jQuery('#hh').val()
        var oldminute = jQuery('#mn').val()
        jQuery(document).on('click', '#autoschedulecheckbox', function () {
            if (this.checked) {
                var selectedDate = this.value
                var dateTimeArray = selectedDate.split(' ')
                var dateArray = dateTimeArray[0].split('-')
                var timeArray = dateTimeArray[1].split(':')
                // set year
                jQuery('#aa').val(dateArray[0])
                // set month
                jQuery('#mm').val(dateArray[1])
                // set day
                jQuery('#jj').val(dateArray[2])
                // set hour
                jQuery('#hh').val(timeArray[0])
                // set minute
                jQuery('#mn').val(timeArray[1])
                // change publish button text
                jQuery('#publish').val('Schedule')
            } else {
                // set year
                jQuery('#aa').val(oldYear)
                // set month
                jQuery('#mm').val(oldMonth)
                // set day
                jQuery('#jj').val(oldday)
                // set hour
                jQuery('#hh').val(oldhour)
                // set minute
                jQuery('#mn').val(oldminute)
                // change publish button text
                jQuery('#publish').val('Publish')
            }
        })
    }

    /**
     * Date Time Picker
     */
    if ($.isFunction($.fn.wpscpDateTimePicker)) {
        jQuery('#wpscp_schedule_republish_date').wpscpDateTimePicker()
        jQuery('#wpscp_schedule_draft_date').wpscpDateTimePicker()
    }
})
