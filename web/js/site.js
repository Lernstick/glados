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