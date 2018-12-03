class univers_Line extends Line {
    constructor(map, Leaflet, voltage, color, type_line) {
        super(map, Leaflet, voltage, color, type_line);
        this._type = 'UniversLines';
        this.univers_popup = new Popup();
    }

    onEachFeatureLayerGJSON(item, layer, data) {
        item.properties.name = item.properties.d_name;
        if ((item.properties.tplnr !== undefined)) {
            data.popup.popup_text = item;
            data.popup.bind_popup(layer, data.cheack_near);
        } else {
            data.univers_popup.popup_text = item;
            data.univers_popup.bind_popup(layer, data.cheack_near);
        }
    }
}