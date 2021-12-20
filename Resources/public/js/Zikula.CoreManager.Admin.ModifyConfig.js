(function ($) {
    $(function() {
        $('#el-modify-config-form input[type="password"]').each(function () {
            $(this).wrap('<div class="input-group"></div>').after('<span class="input-group-addon show-password" style="cursor: pointer"><i class="fa fa-eye"></i></span>');
        });
        $(document.body).on('click', '#el-modify-config-form .show-password', function () {
            $(this).prev().attr('type', 'text');
        });
    });
})(jQuery);
