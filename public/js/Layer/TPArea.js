class TPArea extends Electric {
    constructor(map, Leaflet, voltage, color) {
        super(map, Leaflet, voltage, color);
        this._type = 'TpArea';
        this._ps = 'ТП';
        this.unick = parseInt(this.voltage, 10);
        this.arguments = '&voltage=' + this.voltage + '&ps=' + this._ps;
        //this.popup = new Popup_ps();
    }

    style(feature) {
        var ss = {
            color: feature.properties.kVoltage
        };
        return ss;
    }

    is_has() {
        return this.map.hasLayer(this.LayerGJSON);
    }

    pointToLayer(feature, latlng, data) {

    }
}