class PSArea extends Electric {
    constructor(map, Leaflet, voltage, color) {
        super(map, Leaflet, voltage, color);
        this._type = 'PsArea';
        this._ps = 'Подстанции';
        this.unick = parseInt(this.voltage, 10);
        this.arguments = '&voltage=' + this.voltage+'&ps=' + this._ps;
        //this.popup = new Popup_ps();
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
}