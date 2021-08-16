/**
 * Make dropdown to dropup if the dropdown menu overlaps the bottom screen
 * @see https://stackoverflow.com/a/33548516/2768341
 */
$(document).on("shown.bs.dropdown", ".dropdown-imitation", function () {
    // calculate the required sizes, spaces
    var $ul = $(this).children(".dropdown-menu");
    var $button = $(this).children(".dropdown-toggle");
    var ulOffset = $ul.offset();
    // how much space would be left on the top if the dropdown opened that direction
    var spaceUp = (ulOffset.top - $button.height() - $ul.height()) - $(window).scrollTop();
    // how much space is left at the bottom
    var spaceDown = $(window).scrollTop() + $(window).height() - (ulOffset.top + $ul.height());
    // switch to dropup only if there is no space at the bottom AND there is space at the top
    if (spaceDown < 1 && spaceUp > spaceDown) {
        $(this).addClass("dropup");
    }
}).on("hidden.bs.dropdown", ".dropdown-imitation", function() {
    // always reset after close
    $(this).removeClass("dropup");
    $(this).children(".dropdown-menu").css("max-height", '');
});

/**
 * Make dropdown size variable w.r.t. the viewport
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 */
var registerObservers = function () {

    // called when target is visible by some amount
    function onIntersection(entries, opts){
        entries.forEach(entry => {
            var $ul = $(entry.target);
            $ul.css("max-height", '');
            var maxHeight = entry.intersectionRatio*entry.boundingClientRect.height;
            if (maxHeight >= 36 && maxHeight <= parseInt($ul.css("max-height"))) {
                $ul.css("max-height", maxHeight+"px");
            }
        })
    }

    $(".dropdown-imitation").each(function(index) {
        $ul = $(this).children(".dropdown-menu");
        var observer = new IntersectionObserver(onIntersection, {
            root: null,   // default is the viewport
            threshold: .01 // percentage of the target visible area which will trigger "onIntersection"
        });
        observer.observe($ul.get(0));
    });
}

$(document).ajaxComplete(registerObservers);
$(document).ready(registerObservers);

/**
 * Create a growl message from javascript.
 *
 * @param string type can be 'info', 'warning', 'danger', 'success', 'info'
 * @param string message
 * @see http://bootstrap-growl.remabledesigns.com/
 * @see views/_notification.php
 */
function growl(message, type = 'info', url = null){
    $.notify({
            message: message,
            url: url
    }, {
        type: type,
        showProgressbar: true,
        timer: 1000,
        placement: {
            from: 'top',
            align: 'right'
        },
        url_target: '', // open the url in the same window/tab
        template: '<div data-notify="container" class="col-xs-11 col-sm-3 alert alert-{0}" role="alert">' +
            '<button type="button" class="close" data-notify="dismiss">' +
                '<span aria-hidden="true">×</span>' + 
            '</button>' +
            '<span data-notify="icon" class="glyphicon glyphicon-{0}-sign"></span> ' +
            '<span data-notify="title">{1}</span> ' +
            '<span data-notify="message">{2}</span>' +
            '<div class="progress kv-progress-bar" data-notify="progressbar">' +
                '<div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' +
            '</div>' +
            '<a data-notify="url" href="{3}" target="{4}"></a>' +
        '</div>'
    });
}
