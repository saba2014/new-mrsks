class Opory extends Electric {
    constructor(map, Leaflet, voltage) {
        super(map, Leaflet, voltage);
        this._type = 'Opory';
        this.popup = new Popup_opory();
        this.type_image = "opory";
    }

    pointToLayer(feature, latlng, data) {
        let coltyp = feature.properties.kVoltage;
        let marker = new data.L.circleMarker(latlng, {
            radius: 4,
            weight: 1,
            fillColor: coltyp,
            opacity: 0.9,
            fillOpacity: 0.6,
        });
        if (feature.properties.highlight) {
            data.__proto__.add_animation(feature, marker, data);
        }
        return marker;
    }

    style(feature) {
        var ss = {
            color: "#000000"
        };
        return ss;
    }
}