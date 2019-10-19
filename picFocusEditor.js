$(function () {
    function modifyFocus ( jq, x, y ) {
        var pointer = jq.find("*[data-pic-focus-pointer]");
        if ( pointer.hasClass('horizontal') ) {
            x = x / jq.width();
            jq.data('pic-focus-x', x.toFixed(2));
            pointer.css('left', x * 100 + '%');
            jq.find('input[name=focus_x]').val(x.toFixed(2));
        } else {
            y = y / jq.height();
            jq.data('pic-focus-y', y.toFixed(2));
            pointer.css('top', y * 100 + '%');
            jq.find('input[name=focus_y]').val(y.toFixed(2));
        }
    }
    $(document).on('mousemove', "*[data-pic-focus-control]", function (event) {
        var jq = $(this);
        if ( !jq.data('pic-focus-set') ) {
            modifyFocus( jq, event.offsetX, event.offsetY );
        }
        if (event.buttons == 1) {
            modifyFocus( jq, event.offsetX, event.offsetY );
            jq.attr('data-pic-focus-set',1);
            jq.data('pic-focus-set',1);
        }
    });
    $(document).on('click', "*[data-pic-focus-control]", function (event) {
        var jq = $(this);
        modifyFocus( jq, event.offsetX, event.offsetY );
        jq.attr('data-pic-focus-set',1);
        jq.data('pic-focus-set',1);
    });
    var lastHoveredEditor = null;
    $(document).on('mouseenter', "*[data-pic-focus-control]", function (event) {
        lastHoveredEditor = $(this);
    });
    $(document).on('mouseleave', "*[data-pic-focus-control]", function (event) {
        lastHoveredEditor = null;
    });
    $(document).on('keyup', "body", function (event) {
        if ( lastHoveredEditor != null && ( event.key == "z" || event.which == 90 ) ) {
            event.preventDefault();
            var pointer = lastHoveredEditor.find("*[data-pic-focus-pointer]");
            pointer.toggleClass("horizontal");
            pointer.toggleClass("vertical");
        }
    });
    $(document).on('contextmenu', "*[data-pic-focus-control]", function (event) {
        event.preventDefault();
        var jq = $(this);
        var pointer = $(this).find("*[data-pic-focus-pointer]");
        pointer.toggleClass("horizontal");
        pointer.toggleClass("vertical");
    });
});