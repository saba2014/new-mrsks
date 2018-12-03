$(document).ready(function () {
    $("#s_RegList").hide();
    $("#p_Report").hide();
    $("#p_Result").hide();
    $(".leaflet-control-layers").addClass("dontprint");
    $(".leaflet-control-distance").addClass("dontprint");
    $(".leaflet-control-scale-line").addClass("dontprint");
    $(".leaflet-control-zoom").addClass("dontprint");

    $("#minimap-window").resizable({
        containment: "#p_Map",
        alsoResize: "#minimap",
        maxHeight: 600,
        maxWidth: 400,
        minHeight: 300,
        minWidth: 300
    }).resize(function () {
        window.minimap.invalidateSize();
    });

    $("#legend-window").resizable({
        containment: "#p_Map",
        alsoResize: "#legend",
        maxHeight: 600,
        maxWidth: 400,
        minHeight: 300,
        minWidth: 300
    });

    $("#s_Input").on('keydown', function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            getObjs();
        }
    });
    $("#s_Input").attr("placeho---lder", "пример: Красноярск Ленина 32");

    $(window).resize(function (e) {
        var cWidth = document.documentElement.clientWidth;
        var rpWidth = cWidth - ($('#p_Report').width() + 16);
        $('<style media="print">body, html {width: ' + cWidth + 'px; height: 100%;}</style>').appendTo('head');
        $('<style media="print">#p_Report {top: 10px; left: ' + (rpWidth) + 'px;}</style>').appendTo('head');
    });
    if (location.hash === "") {
        /*window.map.setView([window.coord_1, window.coord_2], window.zoom, {
            animate: false
        });*/
    };

    var GetRequestfFromUrl = function(){
        var name = getNameOfParam(0);
        var value = getParameterByName(name);
        if ((name)&&(value)){
            //name = getSearchType(name);
            $("#s_Select").val(name);
            var search = new Index_handler_functions();
            search.changeSearchType();
            $("#s_Input").val(value);
            getObjs();
        }
    };

    var CheckCookies = function(){
        var miniF = $.cookie('minimapflag');
        var legF = $.cookie('legendflag');
        if (miniF === "1") {
            $('div#minimap-window').show();
            hideBootstrapButton('.minimapbutton');
        }
        else{
            $('div#minimap-window').hide();
            showBootstrapButton('.minimapbutton');
        }
        if (legF === "1"){
            $('div#legend-window').show();
            hideBootstrapButton('.legendbutton');
        }
        else{
            $('div#legend-window').hide();
            showBootstrapButton('.legendbutton');
        }
    };

    GetRequestfFromUrl();
    CheckCookies();
});