class TrainingRes extends Layer {
    constructor(map, Leaflet, id) {
        super(map, Leaflet, id);
        this._type = 'Res';
        this._search = false;
        this._id = id;
        this.near = 1;
        this.arguments = '&res_id=' + this._id;
        this.popup = new Popup_res();
    }

    style(feature) {
        var ss = {
            color: "#000000",
            dashArray: "5, 10",
            fillOpacity: 0.00
        };
        return ss;
    }

    is_has() {
        return this.map.hasLayer(this.LayerGJSON);
    }

    onLayerAdd(tplnr, location) {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap = 1;
        if ((this.map.getZoom() > this._zoom)) {
            this._add();
        }
    }

    actualiseLayerGJSON() {
        if (this.map.hasLayer(this.LayerGJSON)) {
            this.LayerGJSON.refresh(this._get_api_url());
            if (this.back) {
                this.Back();
            }
        }
    }

    load() {
        let layers = this._layers;
        let self = this.options.data;
        if(layers === undefined) {
            return;
        }
        Object.keys(layers).map(function(objectKey, index) {
            var value = layers[objectKey];
            self.map.fitBounds(value.getBounds());
            value.off('click');
        });
    }

    _add() {
        if (!this.map.hasLayer(this.LayerGJSON)) {
            this.map.addLayer(this.LayerGJSON);
        }
        this.LayerGJSON.off('data:loading');
        this.LayerGJSON.off('data:loaded');
        this.LayerGJSON.on('data:loading', this.loading);
        this.LayerGJSON.on('data:loaded', this.load);
        this.actualiseLayerGJSON();
    }
    _get_api_url() {
        var url = 'api/getobjs?type=' + this._type + this.arguments + "&";
        if(this.api_url !== undefined) {
            url = this.api_url;
        }
        return url;
    }
}