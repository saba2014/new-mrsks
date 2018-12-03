class ResCenter extends Layer {
    constructor(map, Leaflet, id, type) {
        super(map, Leaflet);
        this.id = id;
        this.type = type;
        this._type = "ResCenter";
        this.arguments = "";
        this.img = "img/icons/resCenter.svg";
        if (this.type) this.arguments += "&" + this.type + "_id=" + this.id + "&res_type=" + this.type;
        this.popup = new PopupResCenter(this.type, this._type);
        this.cheack_near=0;
    }

    pointToLayer(feature, latlng, data) {
        let myIcon = L.icon({
            iconUrl: data.img,
            iconSize: [30, 30]
        });
        let marker = new L.marker(latlng, {
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

    open_popup(popup, near){
        //console.log(this);
    }
}