class Popup_res extends Popup_extended {
    constructor() {
        super();
    }

    set popup_text(item) {
        let name = "Отусутствует";
        if(item.properties.Label !== undefined) {
            name = item.properties.Label;
        }
        this._popup_text = this.get_hrefs(item) + '<b>Наименование:</b> ' + name;
    }
    
    get popup_text() {
        return this._popup_text;
    }
    
    bind_popup(layer) {
        layer.bindPopup(this.popup_text, {
            keepInView: true,
            className: "popup_res",
            cheack_near: this.cheack_near
        });
        layer.on("popupopen", function (oPopup) {//ИСПРАВИТЬ!!!
            if (window.search) {
                var e = [];
                e.latlng = oPopup.popup._latlng;
                window.N.onMapClick(e, oPopup.popup._content);
            }
        });
    }
}