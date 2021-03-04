function initializeHints(){

    /* To initialize BS3 popovers set this below */
    $(function () { 
        $("[data-toggle='popover']").popover(); 
    });

    $('.hint-block').each(function () {
        var $hint = $(this);

        $hint.parent().find('label').after('&nbsp<a tabindex="0" role="button" class="hint glyphicon glyphicon-question-sign"></a>');

        $hint.parent().find('a.hint').popover({
            html: true,
            trigger: 'focus',
            placement: 'right',
            title:  $hint.parent().find('label').html(),
            //title:  'Description',
            toggle: 'popover',
            container: 'body',
            content: $hint.html()
        });

        $hint.remove()
    });
}

$( document ).ready(function() {
    initializeHints();
});