class Res extends Layer {
    constructor(map, Leaflet, id, parent) {
        super(map, Leaflet, id);
        this._type = 'Res';
        this._search = false;
        this._id = id;
        this._zoom = resScale;
        this._maxZoom = 20;
        this.near = 1;
        this.parent = parent;
        if (parent === undefined) {
        }
        this.arguments = '&res_id=' + this._id;
        this.popup = new Popup_res();
    }

    style(feature) {
        var ss = {
            color: feature.properties.color
        };
        return ss;
    }

    is_has() {
        return this.map.hasLayer(this.LayerGJSON);
    }

    onLayerAdd() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap = 1;
        if ((this.map.getZoom() > this._zoom)) {
            this._add();
        }
        if (this.parent) {
            this.parent.notifyParent(true);
        }
    }

    onLayerRemove() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap = 0;
        if (this.parent) {
            this.parent.notifyParent(true);
        }
    }

}