class CriticalObjects extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'CriticalObjects';
        this.popup = new Popup_CriticalObjects();
    }

    pointToLayer(feature, latlng, data) {
        var myIcon = L.icon({
            iconUrl: "img/icons/CriticalObject.png",
            iconSize: [30, 30],
        });
        var marker = new L.marker(latlng, {
            icon: myIcon
        });
        if (data._cluster !== undefined) {
            data._cluster.addLayer(marker);
        }
        return marker;
    }

    style(feature) {
        var ss = {
            color: "#FF0000",
            weight : 6
        };
        return ss;
    }
}