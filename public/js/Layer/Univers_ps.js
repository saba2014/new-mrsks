class univers_ps extends PS {
    constructor(map, Leaflet, voltage, color, type_line) {
        super(map, Leaflet, voltage, color, type_line);
        this._type = 'UniversPs';
        this.univers_popup = new Popup();
    }

    onEachFeatureLayerGJSON(item, layer, data) {
        if ((item.properties.tplnr !== undefined)) {
            data.popup.popup_text = item;
            data.popup.bind_popup(layer, data.cheack_near);
        } else {
            data.univers_popup.popup_text = item;
            data.univers_popup.bind_popup(layer, data.cheack_near);
        }
    }
}