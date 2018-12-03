class Track extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'Track';
        this.near = 0;
        this.cheack_near = 0;
        this.popup = new Popup_track();
    }

    check_popup(popup, near) {
    }

    pointToLayer(item, latlng, data) {
         let circle = L.circle([latlng.lat, latlng.lng], {
            color: '#b73eab',
            fillColor: '#b73eab',
            fillOpacity: 1,
            radius: 5
        });
        return circle;
    }


    style(item) {
        var ss = {
            color: "#b73eab",
            fillColor: "#b73eab"
        };
        return ss;
    }

    onLayerRemove() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap = 0;
        clearInterval(this.loop);
    }
}
