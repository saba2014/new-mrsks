class EmergencyReserve extends Layer {
    constructor(map, Leaflet, id, type) {
        super(map, Leaflet);
        this.id = id;
        this.type = type;
        this._type = "EmergencyReserve";
        this.arguments = "";
        this.img = "img/icons/emergency.svg";
        if (this.type) this.arguments += "&" + this.type + "_id=" + this.id;
        this.popup = new PopupResCenter(this.type, this._type);
        this.cheack_near=0;
    }

    pointToLayer(feature, latlng, data) {
        var myIcon = L.icon({
            iconUrl: data.img,
            iconSize: [30, 30]
        });
        var marker = new L.marker(latlng, {
            icon: myIcon
        });
        marker.category = 'riseETC';
        if (data._cluster !== undefined) {
            data._cluster.addLayer(marker);
        }
        return marker;
    }

    check_popup(){

    }
}