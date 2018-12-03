class Index_handler_functions {
    constructor() {
        /*var miniF = $.cookie("minimapflag");
        var legF = $.cookie("minimapflag");
        minimapenabled = 1;*/
    }

    set_s_Select(element) {
        let value = $(element).attr("val_attr");
        $("#s_Select").val(value);
        // if(value === "rucadastrText")
        //     $('#searchAdditionalDropdown').collapse('show');
        // else
        //     $('#searchAdditionalDropdown').collapse('hide');
        $("#searchDropdown").dropdown("toggle");

        this.changeSearchType();
    }

    changeSearchType() {
        var a = $("#s_Select").val();
        window.search = false;
        if (a === "rucadastrText") {
            $("#s_RegList").show();
            $("#s_Input").attr("placeholder", "пример: Березовский Лопатино контур пашни 500");
        } else {
            $("#s_RegList").hide();
        }
        if (a === "nominatim") {
            $("#s_Input").attr("placeholder", "пример: Красноярск Ленина 32");
        }
        if (a === "tplnr") {
            $("#s_Input").attr("placeholder", "пример: TP010-0021872");
        }
        if (a === "rucadastr_new") {
            $("#s_Input").attr("placeholder", "пример: 24:4:107003:740");
        }
        if (a === "techSearch") {
            $("#s_Input").attr("placeholder", "пример: TP010-0022140 или VL110-000473");
        }
        if (a === "latlonSearch") {
            $("#s_Input").attr("placeholder", "пример: 56.344434 92.432342");
            if ($(".leaflet-control-distance-active").is(":visible") !== true) {
                window.search = true;
            }
            enableClickHandler();
        }
        if (a === "worker") {
            $("#s_Input").attr("placeholder", "пример: 861379031582121");
        }
        if (a === "res") {
            $("#s_Input").attr("placeholder", "пример: Тункинский РЭС или Шушенский РЭС");
        }
    }

    adminbutton() {
        window.open('admin');
        return false;
    }

    minimapbutton(minimap) {
        //$('div#minimap-window').show();
        $('div#minimap-window').attr('style', 'display:block !important');
        var tempCenter = map.getCenter();
        minimap.setView(tempCenter);
        hideBootstrapButton('.minimapbutton');
        minimapenabled = 1;
        $.cookie('minimapflag', minimapenabled, {expires: 90});
        $('div#minimap-window').attr('style', 'display:block !important');
       // $('div#minimap-window').show();
        minimap.invalidateSize();
        return false;
    }

    minimap_hide() {
        $('div#minimap-window').hide();
        minimapenabled = 0;
        $.cookie('minimapflag', minimapenabled, {expires: 90});
        showBootstrapButton('.minimapbutton');
        return false;
    }

    legendbutton(legendnabled) {
        legendnabled = 1;
        $.cookie('legendflag', legendnabled, {expires: 90});
        //$('div#legend-window').show();
        $('div#legend-window').attr('style','display:block !important')
        hideBootstrapButton('.legendbutton');
        return false;
    }

    legend_hide(legendenabled) {
        $('div#legend-window').hide();
        legendenabled = 0;
        $.cookie('legendflag', legendenabled, {expires: 90});
        showBootstrapButton('.legendbutton');
    }

    s_Logout() {
        let token = new TokenStorage();
        let ref = token.refreshT;
        token.deleteFromCookies();
        window.location.href = 'login/end?refreshToken='+ref;
        $('#spin').spin(false);
        return false;
    }

    s_Close_result(Layer) {
        $('#p_Result').hide();
        Layer.RemoveGS();
        return false;
    }

    s_Close_report() {
        $('#p_Report').hide();
        return false;
    }

    cleanReport() {
        $("#p_Report").children("#text").children("#dist").html("");
        $("#p_Report").children("#text").children("#uObject").html("");
        $("#p_Report").children("#text").children("#uLine").html("");
        $("#p_Report").children("#text").children("#uPStation").html("");
        return false;
    }

    sPrintIt() {
        window.print();
        return false;
    }

    toggle(id) {
        $(id).toggle();
        $(id + '_on').toggle();
        $(id + '_off').toggle();
        return false;
    }
}
