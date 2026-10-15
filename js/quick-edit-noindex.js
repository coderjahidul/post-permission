(function ($) {
    if (typeof inlineEditPost === 'undefined') {
        return;
    }

    var wpInlineEdit = inlineEditPost.edit;

    inlineEditPost.edit = function (id) {
        wpInlineEdit.apply(this, arguments);

        var postId = 0;
        if (typeof id === 'object') {
            postId = parseInt(this.getId(id), 10);
        } else {
            postId = parseInt(id, 10);
        }

        if (!postId) {
            return;
        }

        var allowed = $('#post-' + postId).find('.nq-index-allow').attr('data-index-allow');
        $('#edit-' + postId).find('input[name="index_allow"]').prop('checked', allowed === '1');
    };
})(jQuery);
