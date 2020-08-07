jQuery(document).ready(function($) {

  if ($.isFunction($.fn.wpColorPicker)) {
    $('.wp-options-framework .wp-color-picker').wpColorPicker();
  }

  if ($.isFunction($.fn.datetimepicker)) {
    $.datetimepicker.setLocale('ru');
    $('.wp-options-framework .wp-date-picker').each(function() {
      var yearStart = $(this).data('start-year'),
          yearEnd = $(this).data('end-year');
          timePicker = (Number ($(this).data('timepicker')) > 0),
          format = timePicker ? 'Y-m-d H:i:s' : 'Y-m-d';    

      $(this).datetimepicker({
        timepicker: timePicker,
        yearStart: yearStart,
        yearEnd: yearEnd,
        step: 5,  
        dayOfWeekStart : 1,
        format: format
      });          
    })    
  }

  $('.wp-options-framework .wpsf-browse').click(function() {
      var receiver = $(this).prev("input");
      tb_show("", "media-upload.php?post_id=0&amp;type=file&amp;TB_iframe=true");

      window.original_send_to_editor = window.send_to_editor;

      window.send_to_editor = function(html) {

            $(html).filter("a").each( function(k, v) {
                $(receiver).val($(v).attr("href"));
            });

            $(html).filter("img").each( function(k, v) {
                $(receiver).val($(v).attr("src"));
            });

          tb_remove();
          window.send_to_editor = window.original_send_to_editor;
      }

      return false;
  });
});
